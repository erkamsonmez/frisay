<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

$baseUrl = KuveytturkModule::getBaseUrl();

if (!Order::hasPendingPayment() || empty($cart) || !empty($cart['empty'])) {
	header('Location: ' . $baseUrl . 'checkout');
	exit;
}

$pendingData = $_SESSION['pending_order_data'] ?? null;

if (!is_array($pendingData) || (string) ($pendingData['payment_method'] ?? '') !== 'kuveytturk') {
	header('Location: ' . $baseUrl . 'checkout');
	exit;
}

/** @var KuveytturkModule|null $kuveytturk */
$kuveytturk = Module::getPaymentModule('kuveytturk');

if (!$kuveytturk || !KuveytturkModule::isConfigured()) {
	header('Location: ' . $baseUrl . 'checkout');
	exit;
}

$reference = trim((string) ($pendingData['_kuveytturk_reference'] ?? ''));

if ($reference === '') {
	$reference = Order::reserveReference();
	$pendingData['_kuveytturk_reference'] = $reference;
	$_SESSION['pending_order_data'] = $pendingData;
}

KuveytturkModule::persistPendingCheckout($reference, $pendingData, $cart);

$previewOrder = KuveytturkModule::buildPreviewOrder($pendingData, $cart);
$paymentError = '';

if (Tools::getValue('fail')) {
	$paymentError = (string) ($_SESSION['kuveytturk_payment_error'] ?? 'Ödeme işlemi banka tarafından onaylanmadı veya iptal edildi.');
	unset($_SESSION['kuveytturk_payment_error']);
}

if (Tools::isSubmit('submitKuveytturk')) {
	$cardHolderName = trim((string) Tools::getValue('name'));
	$cardNumber = preg_replace('/\D/', '', (string) Tools::getValue('cardNo'));
	$expireMonth = str_pad(trim((string) Tools::getValue('mm')), 2, '0', STR_PAD_LEFT);
	$expireYear = trim((string) Tools::getValue('yy'));
	$cvv = trim((string) Tools::getValue('cvv'));

	if ($cardHolderName === '' || $cardNumber === '' || $expireMonth === '' || $expireYear === '' || $cvv === '') {
		$paymentError = 'Lütfen tüm kart bilgilerini eksiksiz doldurunuz.';
	} elseif (strlen($cardNumber) < 15) {
		$paymentError = 'Geçerli bir 16 haneli kart numarası giriniz.';
	} else {
		if (strlen($expireYear) === 4) {
			$expireYear = substr($expireYear, -2);
		}

		$total = (float) ($previewOrder['total'] ?? 0);
		$amountKurus = (int) round($total * 100);

		$callbackUrl = $baseUrl . 'api/module.php?m=kuveytturk&action=callback';

		$paymentData = [
			'MerchantId' => Settings::get('KUVEYTTURK_MERCHANT_ID'),
			'CustomerId' => Settings::get('KUVEYTTURK_CUSTOMER_ID'),
			'UserName' => Settings::get('KUVEYTTURK_USERNAME'),
			'Password' => Settings::get('KUVEYTTURK_PASSWORD'),
			'MerchantOrderId' => $reference,
			'Amount' => $amountKurus,
			'CardHolderName' => $cardHolderName,
			'CardNumber' => $cardNumber,
			'CardExpireDateMonth' => $expireMonth,
			'CardExpireDateYear' => $expireYear,
			'CardCVV2' => $cvv,
			'IdentityTaxNumber' => '11111111111',
			'Description' => 'FShop Sipariş ' . $reference,
			'OkUrl' => $callbackUrl,
			'FailUrl' => $callbackUrl,
		];

		$helper = new KuveytTurkHelper();

		$helper->logTransaction(
			(int) ($cart['id_cart'] ?? 0),
			null,
			$reference,
			'paygate',
			$total,
			json_encode(['MerchantOrderId' => $reference, 'Amount' => $amountKurus], JSON_UNESCAPED_UNICODE),
			null,
			null,
			null,
			null,
			'pending'
		);

		$result = $helper->sendPayGateRequest($paymentData);
		$bankHtml = KuveytTurkHelper::ensurePlainBody((string) ($result['response'] ?? ''));

		// Hâlâ gzip ise asla ekrana basma
		$stillCompressed = strlen($bankHtml) >= 2 && $bankHtml[0] === "\x1f" && $bankHtml[1] === "\x8b";

		$looksLikeHtml = !$stillCompressed && $bankHtml !== '' && (
			stripos($bankHtml, '<html') !== false
			|| stripos($bankHtml, '<!doctype') !== false
			|| stripos($bankHtml, '<form') !== false
			|| stripos($bankHtml, '<script') !== false
		);

		if (!empty($result['success']) && $looksLikeHtml) {
			while (ob_get_level() > 0) {
				ob_end_clean();
			}
			if (function_exists('apache_setenv')) {
				@apache_setenv('no-gzip', '1');
			}
			@ini_set('zlib.output_compression', '0');
			header('Content-Type: text/html; charset=utf-8');
			header('Content-Encoding: identity');
			header('Content-Security-Policy: default-src * \'unsafe-inline\' \'unsafe-eval\' data: https:; form-action * https:;');
			echo $bankHtml;
			exit;
		}

		if ($stillCompressed) {
			$paymentError = 'Banka yanıtı açılamadı (gzip). Sunucuda zlib/gzip desteğini kontrol edin veya modül dosyalarını güncelleyin.';
		} elseif (!empty($result['success']) && $bankHtml !== '' && !$looksLikeHtml) {
			$paymentError = 'Banka 3D sayfası okunamadı. Lütfen tekrar deneyin.';
		} else {
			$paymentError = !empty($result['error']) ? $result['error'] : 'KuveytTürk 3D Ödeme sunucusuna bağlanılamadı.';
		}

		$helper->logTransaction(
			(int) ($cart['id_cart'] ?? 0),
			null,
			$reference,
			'paygate_error',
			$total,
			json_encode(['MerchantOrderId' => $reference, 'Amount' => $amountKurus], JSON_UNESCAPED_UNICODE),
			(string) ($result['response'] ?? ''),
			(string) ($result['http_code'] ?? 'error'),
			$paymentError,
			null,
			'error'
		);
	}
}

$months = [];
for ($m = 1; $m <= 12; $m++) {
	$months[] = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
}

$years = [];
$startYear = (int) date('Y');
for ($y = $startYear; $y <= $startYear + 12; $y++) {
	$years[] = [
		'full' => (string) $y,
		'short' => substr((string) $y, -2),
	];
}

$skipPageRender = true;

$smarty->assign([
	'pageName' => 'kuveytturk-payment',
	'pageTitle' => 'KuveytTürk ile Ödeme',
	'pageDesc' => 'KuveytTürk 3D Secure Güvenli Ödeme',
	'css' => 'checkout.css',
	'js' => false,
	'paymentError' => $paymentError,
	'order' => $previewOrder,
	'months' => $months,
	'years' => $years,
	'breadcrumb' => [
		['name' => 'Anasayfa', 'url' => $baseUrl],
		['name' => 'Ödeme', 'url' => $baseUrl . 'checkout'],
		['name' => 'KuveytTürk', 'url' => ''],
	],
]);

$smarty->display(_THEME_BASE_DIR_ . 'header.tpl');
$smarty->display('file:' . dirname(__DIR__) . '/assets/templates/front/payment_page.tpl');
$smarty->display(_THEME_BASE_DIR_ . 'footer.tpl');
