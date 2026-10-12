<?php

class Mail
{
	private static string $lastError = '';

	public static function getLastError(): string
	{
		return self::$lastError;
	}

	public static function usesSmtp(): bool
	{
		return Settings::get('MAIL_DRIVER') === 'smtp';
	}

	public static function isConfigured(): bool
	{
		if (self::usesSmtp()) {
			return trim(Settings::get('SMTP_HOST')) !== ''
				&& trim(Settings::get('SMTP_USER')) !== ''
				&& Settings::get('SMTP_PASS') !== '';
		}

		return trim(self::getFromEmail()) !== '';
	}

	public static function send(string $to, string $subject, string $bodyHtml): bool
	{
		self::$lastError = '';
		$to = trim($to);

		if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
			self::$lastError = 'Geçersiz alıcı e-posta adresi';
			return false;
		}

		$body = self::wrapTemplate($bodyHtml);

		if (self::usesSmtp()) {
			if (!self::isConfigured()) {
				self::$lastError = 'SMTP ayarları eksik';
				return false;
			}

			$sent = SmtpMailer::send($to, $subject, $body);
			if (!$sent) {
				self::$lastError = SmtpMailer::getLastError() ?: 'SMTP gönderimi başarısız';
			}

			return $sent;
		}

		return self::sendViaPhpMail($to, $subject, $body);
	}

	/** @return array<int, string> */
	public static function getLayoutPlaceholders(): array
	{
		return [
			'{site_name}',
			'{site_url}',
			'{logo_url}',
			'{contact_email}',
			'{contact_phone}',
		];
	}

	public static function getDefaultEmailHeader(): string
	{
		return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff;">'
			. '<tr><td align="center" style="padding:28px 20px 20px;border-bottom:1px solid #eeeeee;">'
			. '<a href="{site_url}" style="text-decoration:none;display:inline-block;">'
			. '<img src="{logo_url}" alt="{site_name}" width="160" style="display:block;max-width:160px;height:auto;border:0;">'
			. '</a></td></tr></table>';
	}

	public static function getDefaultEmailFooter(): string
	{
		return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8f9fa;">'
			. '<tr><td align="center" style="padding:22px 20px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#888888;">'
			. '<p style="margin:0 0 8px;font-weight:bold;color:#555555;">{site_name}</p>'
			. '<p style="margin:0 0 8px;">{contact_phone} · <a href="mailto:{contact_email}" style="color:#888888;text-decoration:none;">{contact_email}</a></p>'
			. '<p style="margin:0;"><a href="{site_url}" style="color:#888888;text-decoration:underline;">{site_url}</a></p>'
			. '<p style="margin:16px 0 0;font-size:11px;color:#aaaaaa;">Bu e-posta {site_name} tarafından gönderilmiştir.</p>'
			. '</td></tr></table>';
	}

	public static function getEmailHeader(): string
	{
		$stored = trim((string) Settings::get('MAIL_HEADER'));

		return $stored !== '' ? $stored : self::getDefaultEmailHeader();
	}

	public static function getEmailFooter(): string
	{
		$stored = trim((string) Settings::get('MAIL_FOOTER'));

		return $stored !== '' ? $stored : self::getDefaultEmailFooter();
	}

	public static function previewTemplate(string $contentHtml): string
	{
		return self::wrapTemplate($contentHtml);
	}

	private static function sendViaPhpMail(string $to, string $subject, string $bodyHtml): bool
	{
		if (!function_exists('mail')) {
			self::$lastError = 'PHP mail() is disabled on this server. Configure SMTP in Admin → Settings.';
			return false;
		}

		$from = self::getFromEmail();

		if ($from === '') {
			self::$lastError = 'Gönderen e-posta tanımlı değil (İletişim e-postası alanını doldurun)';
			return false;
		}

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		$headers = [
			'MIME-Version: 1.0',
			'Content-type: text/html; charset=utf-8',
			'From: ' . self::encodeHeader($siteName) . ' <' . $from . '>',
			'Reply-To: ' . $from,
		];

		$sent = @mail($to, $encodedSubject, $bodyHtml, implode("\r\n", $headers));

		if (!$sent) {
			self::$lastError = 'PHP mail() gönderimi başarısız. Sunucunuzda mail() etkin mi kontrol edin.';
		}

		return $sent;
	}

	private static function getFromEmail(): string
	{
		if (self::usesSmtp()) {
			return trim(Settings::get('SMTP_FROM_EMAIL'))
				?: trim(Settings::get('SMTP_USER'));
		}

		return trim(Settings::get('CONTACT_EMAIL'));
	}

	public static function sendWelcome(string $to, string $fullName): bool
	{
		global $domain;

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$name = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
		$loginUrl = htmlspecialchars(rtrim($domain, '/') . '/login', ENT_QUOTES, 'UTF-8');

		$body = '<h2 style="margin:0 0 16px;">Hoş geldiniz!</h2>'
			. '<p>Merhaba <strong>' . $name . '</strong>,</p>'
			. '<p>' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' ailesine katıldığınız için teşekkür ederiz. Hesabınız başarıyla oluşturuldu.</p>'
			. '<p>Siparişlerinizi takip etmek ve alışverişe devam etmek için hesabınıza giriş yapabilirsiniz.</p>'
			. self::ctaButton($loginUrl, 'Giriş Yap');

		return self::send($to, $siteName . ' - Hoş geldiniz', $body);
	}

	public static function sendPasswordReset(string $to, string $fullName, string $resetUrl): bool
	{
		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$name = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
		$url = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

		$body = '<h2 style="margin:0 0 16px;">Şifre Sıfırlama</h2>'
			. '<p>Merhaba <strong>' . $name . '</strong>,</p>'
			. '<p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın. Bu bağlantı 1 saat geçerlidir.</p>'
			. self::ctaButton($url, 'Şifremi Sıfırla')
			. '<p style="font-size:13px;color:#666;">Bu isteği siz yapmadıysanız bu e-postayı yok sayabilirsiniz.</p>'
			. '<p style="font-size:12px;color:#999;word-break:break-all;">' . $url . '</p>';

		return self::send($to, $siteName . ' - Şifre Sıfırlama', $body);
	}

	public static function sendAdminPasswordReset(string $to, string $fullName, string $resetUrl): bool
	{
		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$name = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
		$url = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

		$body = '<h2 style="margin:0 0 16px;">Yönetim Paneli Şifre Sıfırlama</h2>'
			. '<p>Merhaba <strong>' . $name . '</strong>,</p>'
			. '<p>Yönetim paneli şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın. Bu bağlantı 1 saat geçerlidir.</p>'
			. self::ctaButton($url, 'Şifremi Sıfırla')
			. '<p style="font-size:13px;color:#666;">Bu isteği siz yapmadıysanız bu e-postayı yok sayabilirsiniz.</p>'
			. '<p style="font-size:12px;color:#999;word-break:break-all;">' . $url . '</p>';

		return self::send($to, $siteName . ' - Yönetim Paneli Şifre Sıfırlama', $body);
	}

	/**
	 * @param array<string, mixed> $order
	 */
	public static function sendOrderConfirmation(array $order): bool
	{
		$to = trim((string) ($order['customer_email'] ?? ''));

		if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
			return false;
		}

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$reference = trim((string) ($order['reference'] ?? ''));
		$subject = $siteName . ' — Siparişiniz alındı' . ($reference !== '' ? ' #' . $reference : '');

		return self::send($to, $subject, self::buildOrderConfirmationBody($order));
	}

	/** @return array<string, string> */
	public static function getSampleTypes(): array
	{
		return [
			'order_admin' => 'New order (admin)',
			'order_customer' => 'Order confirmation (customer)',
			'welcome' => 'Welcome',
			'password' => 'Forgot password',
			'stock' => 'Out of stock',
			'layout' => 'Layout test',
		];
	}

	public static function sendSample(string $to, string $type): bool
	{
		global $domain;

		$to = trim($to);
		$type = preg_replace('/[^a-z_]/', '', strtolower($type)) ?: 'layout';
		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$base = rtrim((string) $domain, '/');

		if ($type === 'welcome') {
			return self::sendWelcome($to, 'Test Kullanıcı');
		}

		if ($type === 'password') {
			return self::sendPasswordReset($to, 'Test Kullanıcı', $base . '/reset-password?token=test');
		}

		if ($type === 'order_customer') {
			$order = self::sampleOrder();
			$reference = trim((string) ($order['reference'] ?? ''));
			$subject = $siteName . ' — Siparişiniz alındı' . ($reference !== '' ? ' #' . $reference : '') . ' [TEST]';

			return self::send($to, $subject, self::buildOrderConfirmationBody($order));
		}

		if ($type === 'order_admin') {
			return self::sendAdminNewOrder($to, self::sampleOrder(), true);
		}

		if ($type === 'stock') {
			$product = [
				'id_product' => 1,
				'product_name' => 'Örnek ürün',
				'stock_code' => 'SKU-1001',
			];
			$subject = $siteName . ' — Kritik stok: ' . $product['product_name'] . ' [TEST]';

			return self::send($to, $subject, self::buildCriticalStockBody($product, 0, 5, 0, '#'));
		}

		$body = '<h2 style="margin:0 0 16px;">Örnek e-posta</h2>'
			. '<p>Merhaba <strong>Ali Yılmaz</strong>,</p>'
			. self::infoBox('<p style="margin:0;">Bu bir şablon testidir. Sipariş, hoş geldiniz ve diğer mailler aynı çerçeveyi kullanır.</p>')
			. self::ctaButton('#', 'Örnek buton');

		return self::send($to, 'Test Mail - ' . $siteName, $body);
	}

	/**
	 * @param array<string, mixed> $order
	 */
	public static function sendAdminNewOrder(string $to, array $order, bool $isTest = false): bool
	{
		$reference = trim((string) ($order['reference'] ?? ''));
		$idOrder = (int) ($order['id_order'] ?? 0);

		if ($reference === '' && $idOrder > 0) {
			$reference = (string) $idOrder;
		}

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$subject = $siteName . ' — Yeni sipariş' . ($reference !== '' ? ' #' . $reference : '');

		if ($isTest) {
			$subject .= ' [TEST]';
		}

		return self::send($to, $subject, self::buildAdminNewOrderBody($order));
	}

	/**
	 * @param array<string, mixed> $order
	 */
	private static function buildAdminNewOrderBody(array $order): string
	{
		$customer = htmlspecialchars(trim((string) ($order['customer_name'] ?? '')), ENT_QUOTES, 'UTF-8');
		$total = (string) ($order['total_formatted'] ?? Tools::displayPrice((float) ($order['total'] ?? 0)));
		$idOrder = (int) ($order['id_order'] ?? 0);
		$reference = trim((string) ($order['reference'] ?? ''));

		if ($reference === '') {
			$reference = $idOrder > 0 ? (string) $idOrder : '';
		}

		$referenceHtml = htmlspecialchars($reference !== '' ? $reference : '—', ENT_QUOTES, 'UTF-8');
		$adminUrl = htmlspecialchars(class_exists('Admin', false) ? Admin::url('order?id=' . $idOrder) : '#', ENT_QUOTES, 'UTF-8');

		return '<h2 style="margin:0 0 16px;">Yeni sipariş</h2>'
			. self::infoBox(
				'<p style="margin:0 0 8px;"><strong>Sipariş no:</strong> ' . $referenceHtml . '</p>'
				. '<p style="margin:0 0 8px;"><strong>Müşteri:</strong> ' . ($customer !== '' ? $customer : 'Misafir') . '</p>'
				. '<p style="margin:0;"><strong>Toplam:</strong> ' . htmlspecialchars($total, ENT_QUOTES, 'UTF-8') . '</p>'
			)
			. self::ctaButton($adminUrl, 'Siparişi görüntüle');
	}

	/**
	 * @param array<string, mixed> $product
	 */
	public static function buildCriticalStockBody(
		array $product,
		int $newStock,
		int $threshold,
		int $idVariation = 0,
		string $adminUrl = '#'
	): string {
		$name = htmlspecialchars((string) ($product['product_name'] ?? 'Ürün'), ENT_QUOTES, 'UTF-8');
		$stockCode = htmlspecialchars((string) ($product['stock_code'] ?? ''), ENT_QUOTES, 'UTF-8');
		$variationNote = '';

		if ($idVariation > 0 && class_exists('ProductVariation', false)) {
			$variation = ProductVariation::getById($idVariation);
			$label = trim((string) ($variation['label'] ?? ''));

			if ($label !== '') {
				$variationNote = ' (' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ')';
			}
		}

		return '<h2 style="margin:0 0 16px;">Kritik stok uyarısı</h2>'
			. self::infoBox(
				'<p style="margin:0 0 8px;"><strong>Ürün:</strong> ' . $name . $variationNote . '</p>'
				. ($stockCode !== '' ? '<p style="margin:0 0 8px;"><strong>Stok kodu:</strong> ' . $stockCode . '</p>' : '')
				. '<p style="margin:0 0 8px;"><strong>Güncel stok:</strong> ' . $newStock . '</p>'
				. '<p style="margin:0;"><strong>Eşik:</strong> ' . $threshold . ' ve altı</p>'
			)
			. self::ctaButton($adminUrl, 'Ürünü düzenle', '#b91c1c');
	}

	/** @return array<string, mixed> */
	private static function sampleOrder(): array
	{
		$id = (int) DB::getValue('SELECT id_order FROM orders ORDER BY id_order DESC LIMIT 1');

		if ($id > 0) {
			$order = Order::getByIdAdmin($id);

			if (is_array($order) && $order) {
				return $order;
			}
		}

		$price = 1321.58;

		return [
			'id_order' => 21336,
			'reference' => 'ECZ0021336',
			'customer_name' => 'Test Müşteri',
			'customer_email' => 'test@example.com',
			'total' => $price,
			'total_formatted' => Tools::displayPrice($price),
			'subtotal' => $price,
			'subtotal_formatted' => Tools::displayPrice($price),
			'shipping' => 0,
			'shipping_formatted' => Tools::displayPrice(0),
			'status' => 1,
			'status_label' => 'Ödeme Bekliyor',
			'payment_method' => 'bankwire',
			'payment_label' => 'Havale / EFT',
			'items' => [[
				'product_name' => 'Örnek ürün',
				'variation_label' => '',
				'qty' => 1,
				'qty_label' => '1 adet',
				'total' => $price,
				'total_formatted' => Tools::displayPrice($price),
			]],
			'coupon_discount' => 0,
			'promotion_discount' => 0,
			'payment_discount' => 0,
			'manual_discount' => 0,
			'has_gift_wrap' => false,
		];
	}

	private static function encodeHeader(string $text): string
	{
		return '=?UTF-8?B?' . base64_encode($text) . '?=';
	}

	private static function wrapTemplate(string $body): string
	{
		$header = self::replaceLayoutPlaceholders(self::getEmailHeader());
		$content = self::wrapContentSection($body);
		$footer = self::replaceLayoutPlaceholders(self::getEmailFooter());
		$siteName = htmlspecialchars(Settings::get('SITE_NAME') ?: 'FShop', ENT_QUOTES, 'UTF-8');

		return '<!DOCTYPE html>'
			. '<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
			. '<title>' . $siteName . '</title></head>'
			. '<body style="margin:0;padding:0;background:#f4f4f5;">'
			. '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f5;">'
			. '<tr><td align="center" style="padding:24px 12px;">'
			. '<table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e8e8e8;">'
			. '<tr><td>' . $header . '</td></tr>'
			. '<tr><td>' . $content . '</td></tr>'
			. '<tr><td>' . $footer . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	private static function wrapContentSection(string $body): string
	{
		if (strpos($body, '<') === false) {
			$body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'), false);
		}

		return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">'
			. '<tr><td style="padding:28px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.65;color:#333333;">'
			. $body
			. '</td></tr></table>';
	}

	public static function ctaButton(string $url, string $label, string $bg = '#1a1a1a'): string
	{
		$bg = preg_match('/^#[0-9a-fA-F]{3,8}$/', $bg) ? $bg : '#1a1a1a';

		return '<p style="margin:24px 0;"><a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:' . $bg . ';color:#fff;text-decoration:none;border-radius:6px;">'
			. htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
			. '</a></p>';
	}

	public static function infoBox(string $innerHtml): string
	{
		return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px;background:#f5f5f5;border:1px solid #eeeeee;">'
			. '<tr><td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#333333;">'
			. $innerHtml
			. '</td></tr></table>';
	}

	private static function buildOrderConfirmationBody(array $order): string
	{
		global $domain;

		$name = htmlspecialchars(trim((string) ($order['customer_name'] ?? '')), ENT_QUOTES, 'UTF-8');
		$reference = htmlspecialchars(trim((string) ($order['reference'] ?? '')), ENT_QUOTES, 'UTF-8');
		$idOrder = (int) ($order['id_order'] ?? 0);
		$payment = htmlspecialchars((string) ($order['payment_label'] ?? ''), ENT_QUOTES, 'UTF-8');
		$status = htmlspecialchars((string) ($order['status_label'] ?? ''), ENT_QUOTES, 'UTF-8');
		$orderUrl = htmlspecialchars(rtrim((string) $domain, '/') . '/my-account?order=' . $idOrder, ENT_QUOTES, 'UTF-8');
		$hello = $name !== '' ? 'Merhaba <strong>' . $name . '</strong>,' : 'Merhaba,';

		$html = '<h2 style="margin:0 0 16px;">Siparişiniz alındı</h2>'
			. '<p>' . $hello . ' siparişiniz için teşekkür ederiz. Detaylar aşağıdadır.</p>'
			. self::infoBox(
				'<p style="margin:0 0 8px;"><strong>Sipariş no:</strong> ' . ($reference !== '' ? $reference : '#' . $idOrder) . '</p>'
				. '<p style="margin:0 0 8px;"><strong>Durum:</strong> ' . $status . '</p>'
				. '<p style="margin:0;"><strong>Ödeme:</strong> ' . $payment . '</p>'
			)
			. self::buildOrderItemsTable($order)
			. self::ctaButton($orderUrl, 'Siparişi görüntüle');

		$note = trim((string) Settings::get('ORDER_SUCCESS_NOTE'));

		if ($note !== '') {
			$html .= '<p style="margin:16px 0 0;padding:12px 14px;background:#ecfdf5;border-left:3px solid #20B968;border-radius:6px;font-size:13px;color:#166534;">'
				. nl2br(htmlspecialchars($note, ENT_QUOTES, 'UTF-8'), false)
				. '</p>';
		}

		return $html;
	}

	/**
	 * @param array<string, mixed> $order
	 */
	private static function buildOrderItemsTable(array $order): string
	{
		$items = is_array($order['items'] ?? null) ? $order['items'] : [];
		$rows = '';

		foreach ($items as $item) {
			$name = htmlspecialchars(trim((string) ($item['product_name'] ?? '')), ENT_QUOTES, 'UTF-8');
			$variation = trim((string) ($item['variation_label'] ?? ''));

			if ($variation !== '') {
				$name .= ' <span style="color:#888888;">(' . htmlspecialchars($variation, ENT_QUOTES, 'UTF-8') . ')</span>';
			}

			$qty = self::formatOrderQty($item);
			$lineTotal = htmlspecialchars((string) ($item['total_formatted'] ?? Tools::displayPrice((float) ($item['total'] ?? 0))), ENT_QUOTES, 'UTF-8');

			$rows .= '<tr>'
				. '<td style="padding:10px 8px 10px 0;border-bottom:1px solid #eeeeee;font-size:14px;color:#333333;">' . $name . '</td>'
				. '<td style="padding:10px 8px;border-bottom:1px solid #eeeeee;font-size:13px;color:#666666;text-align:center;white-space:nowrap;">' . htmlspecialchars($qty, ENT_QUOTES, 'UTF-8') . '</td>'
				. '<td style="padding:10px 0 10px 8px;border-bottom:1px solid #eeeeee;font-size:14px;color:#333333;text-align:right;font-weight:bold;white-space:nowrap;">' . $lineTotal . '</td>'
				. '</tr>';
		}

		if ($rows === '') {
			$rows = '<tr><td colspan="3" style="padding:12px 0;color:#888888;">Ürün bilgisi bulunamadı.</td></tr>';
		}

		$html = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 8px;">'
			. '<tr>'
			. '<td style="padding:0 8px 8px 0;font-size:12px;color:#888888;font-weight:bold;">Ürün</td>'
			. '<td style="padding:0 8px 8px;font-size:12px;color:#888888;font-weight:bold;text-align:center;">Adet</td>'
			. '<td style="padding:0 0 8px 8px;font-size:12px;color:#888888;font-weight:bold;text-align:right;">Tutar</td>'
			. '</tr>'
			. $rows
			. '</table>'
			. self::buildOrderTotals($order);

		return $html;
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private static function formatOrderQty(array $item): string
	{
		$label = trim((string) ($item['qty_label'] ?? ''));

		if ($label !== '') {
			return $label;
		}

		$qty = (float) ($item['qty'] ?? 0);

		if (abs($qty - round($qty)) < 0.001) {
			return (string) (int) round($qty) . ' adet';
		}

		return rtrim(rtrim(number_format($qty, 3, ',', ''), '0'), ',') . ' adet';
	}

	/**
	 * @param array<string, mixed> $order
	 */
	private static function buildOrderTotals(array $order): string
	{
		$lines = [];
		$lines[] = self::totalRow('Ara toplam', (string) ($order['subtotal_formatted'] ?? Tools::displayPrice((float) ($order['subtotal'] ?? 0))));

		if ((float) ($order['coupon_discount'] ?? 0) > 0) {
			$label = 'Kupon';
			$code = trim((string) ($order['coupon_code'] ?? ''));

			if ($code !== '') {
				$label .= ' (' . $code . ')';
			}

			$lines[] = self::totalRow($label, '-' . (string) ($order['coupon_discount_formatted'] ?? Tools::displayPrice((float) $order['coupon_discount'])));
		}

		if ((float) ($order['promotion_discount'] ?? 0) > 0) {
			$label = trim((string) ($order['promotion_name'] ?? ''));

			if ($label === '') {
				$label = 'Kampanya';
			}

			$lines[] = self::totalRow($label, '-' . (string) ($order['promotion_discount_formatted'] ?? Tools::displayPrice((float) $order['promotion_discount'])));
		}

		if ((float) ($order['payment_discount'] ?? 0) > 0) {
			$label = trim((string) ($order['payment_discount_label'] ?? ''));

			if ($label === '') {
				$label = 'Ödeme indirimi';
			}

			$lines[] = self::totalRow($label, '-' . (string) ($order['payment_discount_formatted'] ?? Tools::displayPrice((float) $order['payment_discount'])));
		}

		if ((float) ($order['manual_discount'] ?? 0) > 0) {
			$lines[] = self::totalRow('Sipariş indirimi', '-' . (string) ($order['manual_discount_formatted'] ?? Tools::displayPrice((float) $order['manual_discount'])));
		}

		$lines[] = self::totalRow('Kargo', (string) ($order['shipping_formatted'] ?? Tools::displayPrice((float) ($order['shipping'] ?? 0))));

		if (!empty($order['has_gift_wrap'])) {
			$gift = (float) ($order['gift_wrap_fee'] ?? 0) > 0
				? (string) ($order['gift_wrap_fee_formatted'] ?? Tools::displayPrice((float) $order['gift_wrap_fee']))
				: 'Ücretsiz';
			$lines[] = self::totalRow('Hediye paketi', $gift);
		}

		$total = (string) ($order['total_formatted'] ?? Tools::displayPrice((float) ($order['total'] ?? 0)));
		$lines[] = '<tr><td style="padding:12px 0 4px;font-size:16px;font-weight:bold;color:#333333;">Toplam</td>'
			. '<td style="padding:12px 0 4px;font-size:16px;font-weight:bold;color:#333333;text-align:right;">' . htmlspecialchars($total, ENT_QUOTES, 'UTF-8') . '</td></tr>';

		return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:8px 0 0;border-top:1px solid #eeeeee;">'
			. implode('', $lines)
			. '</table>';
	}

	private static function totalRow(string $label, string $value): string
	{
		return '<tr>'
			. '<td style="padding:8px 0 0;font-size:13px;color:#666666;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>'
			. '<td style="padding:8px 0 0;font-size:13px;color:#333333;text-align:right;">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>'
			. '</tr>';
	}

	private static function replaceLayoutPlaceholders(string $text): string
	{
		global $domain;

		if (!class_exists('SiteAssets', false)) {
			require_once dirname(__DIR__) . '/core/SiteAssets.php';
		}

		$siteUrl = rtrim((string) $domain, '/');
		$siteName = (string) (Settings::get('SITE_NAME') ?: 'FShop');
		$map = [
			'{site_name}' => htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'),
			'{site_url}' => htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'),
			'{logo_url}' => htmlspecialchars(SiteAssets::getLogoUrl('header'), ENT_QUOTES, 'UTF-8'),
			'{contact_email}' => htmlspecialchars((string) Settings::get('CONTACT_EMAIL'), ENT_QUOTES, 'UTF-8'),
			'{contact_phone}' => htmlspecialchars((string) Settings::get('CONTACT_PHONE'), ENT_QUOTES, 'UTF-8'),
		];

		return str_replace(array_keys($map), array_values($map), $text);
	}
}
