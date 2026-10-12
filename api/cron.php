<?php

require_once dirname(__DIR__) . '/config/install_gate.php';

if (!fshop_is_installed()) {
	fshop_redirect_to_installer();
}

define('IN_SCRIPT', true);
require_once dirname(__DIR__) . '/config/settings.php';

header('Content-Type: application/json; charset=utf-8');

$token = trim((string) Tools::getValue('token', ''));

if ($token === '') {
	$token = trim((string) ($_SERVER['HTTP_X_CRON_TOKEN'] ?? ''));
}

$shopToken = (string) Settings::get('SHOP_TOKEN');

if ($shopToken === '' || !hash_equals($shopToken, $token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Geçersiz token']);
	exit;
}

$action = strtolower(trim((string) Tools::getValue('action', 'currency')));

if ($action === 'currency' || $action === 'doviz') {
	$updated = Product::refreshCurrencyPrices();

	echo json_encode([
		'success' => true,
		'message' => $updated . ' ürün fiyatı güncellendi',
		'updated' => $updated,
	]);
	exit;
}

if ($action === 'abandoned-cart' || $action === 'abandoned_cart') {
	Module::bootstrap('cron');
	$file = Module::path('abandoned-cart') . '/lib/AbandonedCartService.php';

	if (!is_file($file)) {
		http_response_code(404);
		echo json_encode(['success' => false, 'message' => 'abandoned-cart modülü yüklü değil']);
		exit;
	}

	require_once $file;
	$result = AbandonedCartService::runAutoReminders();

	echo json_encode([
		'success' => true,
		'message' => $result['sent'] . ' hatırlatma gönderildi, ' . $result['skipped'] . ' atlandı',
		'sent' => $result['sent'],
		'skipped' => $result['skipped'],
	]);
	exit;
}

if ($action === 'feeds' || $action === 'xml') {
	$results = [];
	$jobs = [
		[
			'akakce',
			dirname(__DIR__) . '/modules/akakce/lib/AkakceFeedService.php',
			'AkakceFeedService',
		],
		[
			'fiyattrend',
			dirname(__DIR__) . '/modules/fiyattrend/lib/FiyattrendFeedService.php',
			'FiyattrendFeedService',
		],
		[
			'google-shopping',
			dirname(__DIR__) . '/modules/google-shopping/google-shopping.php',
			'GoogleShoppingModule',
		],
	];

	foreach ($jobs as $job) {
		$name = $job[0];
		$file = $job[1];
		$class = $job[2];

		if (!is_file($file)) {
			$results[] = ['module' => $name, 'skipped' => true, 'reason' => 'modül dosyası yok'];
			continue;
		}

		require_once $file;

		if (!class_exists($class) || !method_exists($class, 'refreshIfExpired')) {
			$results[] = ['module' => $name, 'skipped' => true, 'reason' => 'yenileme desteklenmiyor'];
			continue;
		}

		if (method_exists($class, 'isEnabled') && !$class::isEnabled()) {
			$results[] = ['module' => $name, 'skipped' => true, 'reason' => 'devre dışı'];
			continue;
		}

		$results[] = array_merge(['module' => $name], $class::refreshIfExpired());
	}

	$refreshed = 0;
	foreach ($results as $row) {
		if (!empty($row['refreshed'])) {
			$refreshed++;
		}
	}

	echo json_encode([
		'success' => true,
		'message' => $refreshed . ' XML feed yenilendi',
		'refreshed' => $refreshed,
		'feeds' => $results,
	], JSON_UNESCAPED_UNICODE);
	exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Bilinmeyen cron işlemi']);
