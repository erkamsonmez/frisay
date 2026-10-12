<?php

class AdminNotification
{
	public static function ensureSchema(): void
	{
		$table = DB::execute("SHOW TABLES LIKE 'admin_notifications'");

		if (empty($table)) {
			DB::execute(
				"CREATE TABLE `admin_notifications` (
					`id_notification` int(11) NOT NULL AUTO_INCREMENT,
					`type` varchar(32) NOT NULL DEFAULT 'info',
					`title` varchar(255) NOT NULL DEFAULT '',
					`message` text NOT NULL,
					`link` varchar(255) NOT NULL DEFAULT '',
					`is_read` tinyint(1) NOT NULL DEFAULT 0,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_notification`),
					KEY `is_read` (`is_read`),
					KEY `date_add` (`date_add`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}
	}

	public static function add(string $title, string $message, string $link = '', string $type = 'info'): ?int
	{
		self::ensureSchema();

		$title = trim($title);
		$message = trim($message);

		if ($title === '') {
			return null;
		}

		$id = DB::insert('admin_notifications', [
			'type' => mb_substr($type, 0, 32),
			'title' => mb_substr($title, 0, 255),
			'message' => $message,
			'link' => mb_substr($link, 0, 255),
			'is_read' => 0,
		]);

		return $id ? (int) $id : null;
	}

	public static function adminHref(string $path): string
	{
		if (class_exists('Admin', false)) {
			return Admin::url($path);
		}

		$domain = rtrim((string) Settings::get('DOMAIN'), '/');
		$uri = 'admin';

		if (class_exists('App', false)) {
			$raw = trim((string) App::env('ADMIN_URI', 'admin'), "/ \t\n\r\0\x0B");
			$sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw);

			if ($sanitized !== null && $sanitized !== '') {
				$uri = $sanitized;
			}
		}

		return $domain . '/' . $uri . '/' . ltrim($path, '/');
	}

	public static function notifyNewOrder(array $order): ?int
	{
		$idOrder = (int) ($order['id_order'] ?? 0);

		if ($idOrder <= 0) {
			return null;
		}

		$total = class_exists('Tools', false)
			? Tools::displayPrice((float) ($order['total'] ?? 0))
			: (string) ($order['total'] ?? '0');
		$reference = trim((string) ($order['reference'] ?? ''));

		if ($reference === '') {
			$reference = (string) $idOrder;
		}

		$qty = 0.0;

		if (!empty($order['items']) && is_array($order['items'])) {
			foreach ($order['items'] as $item) {
				$qty += (float) ($item['qty'] ?? 0);
			}
		} else {
			$qty = (float) DB::getValue(
				'SELECT COALESCE(SUM(qty), 0) FROM order_detail WHERE id_order = ?',
				[$idOrder]
			);
		}

		$qtyInt = max(1, (int) round($qty));

		return self::addUnique(
			'New order',
			sprintf('Order #%s · %d items · %s', $reference, $qtyInt, $total),
			self::adminHref('order?id=' . $idOrder),
			'order',
			'order?id=' . $idOrder
		);
	}

	public static function addUnique(string $title, string $message, string $link, string $type, string $linkNeedle = ''): ?int
	{
		self::ensureSchema();

		$needle = $linkNeedle !== '' ? $linkNeedle : $link;
		$existing = ($needle !== '') ? self::findIdByLink($type, $needle) : null;

		if ($existing) {
			return $existing;
		}

		return self::add($title, $message, $link, $type);
	}

	public static function findIdByLink(string $type, string $needle): ?int
	{
		$needle = trim($needle);

		if ($needle === '') {
			return null;
		}

		$rows = DB::execute(
			'SELECT id_notification, link FROM admin_notifications WHERE type = ? AND link LIKE ? ORDER BY id_notification DESC LIMIT 50',
			[$type, '%' . $needle . '%']
		) ?: [];

		$quoted = preg_quote($needle, '/');

		foreach ($rows as $row) {
			$link = (string) ($row['link'] ?? '');

			if ($link === $needle || preg_match('/' . $quoted . '(?:\D|$)/', $link)) {
				return (int) $row['id_notification'];
			}
		}

		return null;
	}

	/** Bekleyen siparişleri bildirim listesine yazar (eksik kayıtları tamamlar). */
	public static function syncOpenOrders(): void
	{
		self::ensureSchema();

		$orders = DB::execute(
			'SELECT id_order, reference, customer_name, total
			FROM orders
			WHERE status IN (?, ?)
			ORDER BY id_order DESC
			LIMIT 50',
			[Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_PROCESSING]
		) ?: [];

		foreach ($orders as $order) {
			self::notifyNewOrder($order);
		}
	}

	/** @return array<string, int> */
	public static function getFilterCounts(): array
	{
		$unread = self::countUnreadByType();

		return [
			'order' => Order::countAdmin(Order::STATUS_PENDING) + Order::countAdmin(Order::STATUS_PAID) + Order::countAdmin(Order::STATUS_PROCESSING),
			'contact' => class_exists('Contact', false) ? Contact::countUnread() : (int) ($unread['contact'] ?? 0),
			'cancel_request' => class_exists('CancelRequest', false) ? CancelRequest::countPending() : (int) ($unread['cancel_request'] ?? 0),
			'return_request' => class_exists('ReturnRequest', false) ? ReturnRequest::countPending() : (int) ($unread['return_request'] ?? 0),
			'stock' => (int) ($unread['stock'] ?? 0),
			'info' => (int) ($unread['info'] ?? 0),
		];
	}

	public static function typeMeta(): array
	{
		return [
			'order' => [
				'label' => 'New order',
				'icon' => 'shopping-bag',
				'color' => '#f97316',
				'btn' => 'btn-outline-secondary',
			],
			'contact' => [
				'label' => 'New message',
				'icon' => 'message-square',
				'color' => '#3b82f6',
				'btn' => 'btn-outline-secondary',
			],
			'cancel_request' => [
				'label' => 'New cancel request',
				'icon' => 'x-circle',
				'color' => '#ef4444',
				'btn' => 'btn-outline-secondary',
			],
			'return_request' => [
				'label' => 'New return request',
				'icon' => 'undo-2',
				'color' => '#f59e0b',
				'btn' => 'btn-outline-secondary',
			],
			'stock' => [
				'label' => 'Out of stock',
				'icon' => 'package',
				'color' => '#111827',
				'btn' => 'btn-outline-secondary',
			],
			'info' => [
				'label' => 'Other notifications',
				'icon' => 'bell',
				'color' => '#6b7280',
				'btn' => 'btn-outline-secondary',
			],
		];
	}

	public static function normalizeType(string $type): string
	{
		$type = trim($type);

		return isset(self::typeMeta()[$type]) ? $type : '';
	}

	public static function countUnread(string $type = ''): int
	{
		self::ensureSchema();

		$type = self::normalizeType($type);

		if ($type === '') {
			return (int) DB::getValue(
				'SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0'
			);
		}

		return (int) DB::getValue(
			'SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0 AND type = ?',
			[$type]
		);
	}

	/** @return array<string, int> */
	public static function countUnreadByType(): array
	{
		self::ensureSchema();

		$rows = DB::execute(
			'SELECT type, COUNT(*) AS cnt FROM admin_notifications WHERE is_read = 0 GROUP BY type'
		) ?: [];

		$counts = [];

		foreach ($rows as $row) {
			$counts[(string) ($row['type'] ?? '')] = (int) ($row['cnt'] ?? 0);
		}

		return $counts;
	}

	public static function getList(int $limit = 50, bool $unreadOnly = false, string $type = ''): array
	{
		self::ensureSchema();

		$type = self::normalizeType($type);
		$wheres = [];
		$params = [];

		if ($unreadOnly) {
			$wheres[] = 'is_read = 0';
		}

		if ($type !== '') {
			$wheres[] = 'type = ?';
			$params[] = $type;
		}

		$where = $wheres !== [] ? ('WHERE ' . implode(' AND ', $wheres)) : '';
		$rows = DB::execute(
			'SELECT * FROM admin_notifications ' . $where . ' ORDER BY id_notification DESC LIMIT ' . (int) $limit,
			$params
		) ?: [];

		foreach ($rows as &$row) {
			$row = self::decorateRow($row);
		}
		unset($row);

		return $rows;
	}

	/** @return array<string, mixed>|null */
	public static function getLatest(): ?array
	{
		self::ensureSchema();

		$rows = DB::execute(
			'SELECT * FROM admin_notifications ORDER BY id_notification DESC LIMIT 1'
		) ?: [];

		return isset($rows[0]) && is_array($rows[0]) ? self::decorateRow($rows[0]) : null;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	public static function decorateRow(array $row): array
	{
		$type = self::normalizeType((string) ($row['type'] ?? '')) ?: 'info';
		$meta = self::typeMeta()[$type];
		$row['type'] = $type;
		$row['date_formatted'] = Tools::formatDate3($row['date_add'] ?? '');
		$row['is_read'] = (int) ($row['is_read'] ?? 0);
		$row['title'] = self::localizeTitle((string) ($row['title'] ?? ''));
		$row['message'] = self::localizeMessage((string) ($row['message'] ?? ''));
		$row['type_label'] = self::translate((string) $meta['label']);
		$row['icon'] = (string) $meta['icon'];
		$row['color'] = (string) $meta['color'];
		$row['time_ago'] = self::formatTimeAgo((string) ($row['date_add'] ?? ''));

		if (trim((string) $row['message']) === '') {
			$row['message'] = (string) $row['title'];
		}

		return $row;
	}

	private static function translate(string $text): string
	{
		if (!class_exists('AdminLang', false)) {
			require_once dirname(__FILE__) . '/AdminLang.php';
		}

		return AdminLang::translate($text);
	}

	private static function localizeTitle(string $title): string
	{
		if (preg_match('/^Yeni sipariş #(.+)$/u', $title, $matches)) {
			return sprintf(self::translate('New order #%s'), $matches[1]);
		}

		if (preg_match('/^Kritik stok: (.+)$/u', $title, $matches)) {
			return sprintf(self::translate('Critical stock: %s'), $matches[1]);
		}

		$legacy = [
			'Yeni iptal talebi' => 'New cancel request',
			'Yeni iade talebi' => 'New return request',
			'Yeni iletişim mesajı' => 'New contact message',
			'Sipariş sorusu' => 'Order question',
			'Ürün stoku bitti' => 'Product out of stock',
		];

		if (isset($legacy[$title])) {
			$title = $legacy[$title];
		}

		return self::translate($title);
	}

	private static function localizeMessage(string $message): string
	{
		if ($message === '') {
			return '';
		}

		$patterns = [
			'/^Sipariş #(.+) için iptal talebi oluşturuldu\.$/u' => 'Cancel request created for order #%s.',
			'/^Sipariş #(.+) için iade talebi oluşturuldu\.$/u' => 'Return request created for order #%s.',
			'/^Cancel request created for order #(.+)\.$/u' => 'Cancel request created for order #%s.',
			'/^Return request created for order #(.+)\.$/u' => 'Return request created for order #%s.',
			'/^Order #(.+) · (\d+) items · (.+)$/u' => 'Order #%s · %d items · %s',
			'/^Sipariş #(.+) · (\d+) ürün · (.+)$/u' => 'Order #%s · %d items · %s',
			'/^(.+) stokta kalmadı\.$/u' => '%s is out of stock.',
			'/^(.+) is out of stock\.$/u' => '%s is out of stock.',
		];

		foreach ($patterns as $pattern => $templateKey) {
			if (preg_match($pattern, $message, $matches)) {
				$args = array_slice($matches, 1);

				return vsprintf(self::translate($templateKey), $args);
			}
		}

		if (preg_match('/^(.+) — (Genel|General)$/u', $message, $matches)) {
			return $matches[1] . ' — ' . self::translate('General');
		}

		return self::translate($message);
	}

	private static function formatTimeAgo(string $dateAdd): string
	{
		$ts = strtotime($dateAdd);

		if ($ts === false) {
			return $dateAdd;
		}

		$diff = max(0, time() - $ts);

		if ($diff < 60) {
			return self::translate('just now');
		}

		if ($diff < 3600) {
			return sprintf(self::translate('%d min'), (int) floor($diff / 60));
		}

		if ($diff < 86400) {
			return sprintf(self::translate('%d h'), (int) floor($diff / 3600));
		}

		return Tools::formatDate3($dateAdd);
	}

	public static function markRead(int $idNotification): bool
	{
		self::ensureSchema();

		$updated = DB::update(
			'admin_notifications',
			['is_read' => 1],
			'id_notification = :id_notification',
			['id_notification' => $idNotification]
		);

		return $updated !== false && $updated > 0;
	}

	public static function markAllRead(string $type = ''): void
	{
		self::ensureSchema();

		$type = self::normalizeType($type);

		if ($type !== '') {
			DB::update(
				'admin_notifications',
				['is_read' => 1],
				'is_read = 0 AND type = :type',
				['type' => $type]
			);

			return;
		}

		DB::update(
			'admin_notifications',
			['is_read' => 1],
			'is_read = 0',
			[]
		);
	}
}
