<?php

class AdminMenuFavorites
{
	public const SETTING_PREFIX = 'ADMIN_FAVORITE_MENUS_';

	/** @return string[] */
	public static function defaultKeys(): array
	{
		return [
			'dashboard',
			'orders',
			'cancellations',
			'returns',
			'customers',
		];
	}

	public static function settingKey(?int $idAdmin = null): string
	{
		$id = $idAdmin !== null ? $idAdmin : Admin::getId();

		return self::SETTING_PREFIX . max(0, $id);
	}

	/** @return string[] */
	public static function getKeys(?int $idAdmin = null): array
	{
		$raw = trim((string) Settings::get(self::settingKey($idAdmin)));

		if ($raw === '') {
			return self::defaultKeys();
		}

		$decoded = json_decode($raw, true);

		if (!is_array($decoded)) {
			return self::defaultKeys();
		}

		return self::sanitizeKeys($decoded);
	}

	/**
	 * @param mixed $keys
	 * @return string[]
	 */
	public static function sanitizeKeys($keys): array
	{
		$clean = [];

		if (!is_array($keys)) {
			return [];
		}

		foreach ($keys as $key) {
			$key = self::normalizeKey((string) $key);

			if ($key === '' || isset($clean[$key])) {
				continue;
			}

			$clean[$key] = $key;

			if (count($clean) >= 24) {
				break;
			}
		}

		return array_values($clean);
	}

	public static function saveKeys(array $keys, ?int $idAdmin = null): bool
	{
		$keys = self::sanitizeKeys($keys);
		$json = json_encode($keys, JSON_UNESCAPED_UNICODE);

		return Settings::set(self::settingKey($idAdmin), $json !== false ? $json : '[]');
	}

	public static function normalizeKey(string $key): string
	{
		$key = trim($key);

		if ($key === '') {
			return '';
		}

		if (strpos($key, 'url:') === 0) {
			return substr($key, 0, 180);
		}

		$key = strtolower($key);

		return preg_replace('/[^a-z0-9._-]/', '', $key) ?: '';
	}

	/**
	 * @param array<string, mixed> $badges
	 * @param array<string, array<int, array<string, mixed>>> $hookItems
	 * @return array<string, array<string, mixed>>
	 */
	public static function catalog(string $adminUrl, array $badges = [], array $hookItems = [], string $posUrl = '', bool $posAvailable = false): array
	{
		$adminUrl = rtrim($adminUrl, '/') . '/';
		$items = [
			'dashboard' => self::item('dashboard', 'Dashboard', 'layout-dashboard', $adminUrl . 'dashboard', ['dashboard']),
			'welcome' => self::item('welcome', 'Welcome', 'rocket', $adminUrl . 'welcome', ['welcome']),
			'customers' => self::item('customers', 'Customers', 'users', $adminUrl . 'customers', ['customers', 'customer']),
			'customer-groups' => self::item('customer-groups', 'Customer groups', 'users-round', $adminUrl . 'customer-groups', ['customer-groups']),
			'messages' => self::item('messages', 'Messages', 'message-square', $adminUrl . 'messages', ['messages', 'message'], (int) ($badges['messages'] ?? 0)),
			'notifications' => self::item('notifications', 'Notifications', 'bell', $adminUrl . 'notifications', ['notifications'], (int) ($badges['notifications'] ?? 0)),
			'orders' => self::item('orders', 'Orders', 'shopping-bag', $adminUrl . 'orders', ['orders', 'order'], (int) ($badges['orders'] ?? 0)),
			'returns' => self::item('returns', 'Returns', 'undo-2', $adminUrl . 'returns', ['returns', 'return'], (int) ($badges['returns'] ?? 0)),
			'cancellations' => self::item('cancellations', 'Cancellations', 'x-circle', $adminUrl . 'cancellations', ['cancellations', 'cancel'], (int) ($badges['cancellations'] ?? 0)),
			'coupons' => self::item('coupons', 'Coupons', 'ticket-percent', $adminUrl . 'coupons', ['coupons', 'coupon', 'cart-promotion']),
			'cargos' => self::item('cargos', 'Shipping', 'truck', $adminUrl . 'cargos', ['cargos']),
			'stock-analysis' => self::item('stock-analysis', 'Stock Analysis', 'bar-chart-3', $adminUrl . 'stock-analysis', ['stock-analysis']),
			'products' => self::item('products', 'Products', 'package', $adminUrl . 'products', ['products', 'product']),
			'categories' => self::item('categories', 'Categories', 'folder-tree', $adminUrl . 'categories', ['categories', 'category']),
			'brands' => self::item('brands', 'Brands', 'tag', $adminUrl . 'brands', ['brands', 'brand']),
			'suppliers' => self::item('suppliers', 'Suppliers', 'truck', $adminUrl . 'suppliers', ['suppliers', 'supplier']),
			'cms' => self::item('cms', 'Pages', 'file-text', $adminUrl . 'cms', ['cms', 'cms-edit']),
			'languages' => self::item('languages', 'Languages', 'languages', $adminUrl . 'languages', ['languages']),
			'translations' => self::item('translations', 'UI Translations', 'book-a', $adminUrl . 'translations', ['translations']),
			'currencies' => self::item('currencies', 'Currencies', 'coins', $adminUrl . 'currencies', ['currencies']),
			'taxes' => self::item('taxes', 'Taxes', 'percent', $adminUrl . 'taxes', ['taxes']),
			'seo' => self::item('seo', 'SEO', 'search', $adminUrl . 'seo', ['seo']),
			'marketplace-products' => self::item('marketplace-products', 'Marketplace products', 'store', $adminUrl . 'marketplace-products', ['marketplace-products']),
			'marketplace-orders' => self::item('marketplace-orders', 'Marketplace orders', 'shopping-cart', $adminUrl . 'marketplace-orders', ['marketplace-orders']),
			'marketplace-logs' => self::item('marketplace-logs', 'Logs', 'scroll-text', $adminUrl . 'marketplace-logs', ['marketplace-logs']),
			'marketplace-questions' => self::item('marketplace-questions', 'Questions', 'message-circle-question', $adminUrl . 'marketplace-questions', ['marketplace-questions']),
			'marketplace-settings' => self::item('marketplace-settings', 'Settings', 'settings', $adminUrl . 'marketplace-settings', ['marketplace-settings']),
			'modules' => self::item('modules', 'Modules', 'blocks', $adminUrl . 'modules', ['modules', 'module']),
			'module-store' => self::item('module-store', 'Module Store', 'store', 'https://frisay.com/modules', [], 0, '_blank'),
			'templates' => self::item('templates', 'Themes', 'palette', $adminUrl . 'templates', ['templates', 'theme-customize']),
			'settings' => self::item('settings', 'Settings', 'settings', $adminUrl . 'settings', ['settings']),
			'upgrade' => self::item('upgrade', 'System update', 'arrow-up-circle', $adminUrl . 'upgrade', ['upgrade'], (int) ($badges['upgrade'] ?? 0)),
			'account' => self::item('account', 'My Account', 'user-cog', $adminUrl . 'account', ['account']),
			'performance' => self::item('performance', 'Performance', 'zap', $adminUrl . 'performance', ['performance']),
			'security' => self::item('security', 'Security', 'shield', $adminUrl . 'security', ['security']),
			'api' => self::item('api', 'API', 'link-2', $adminUrl . 'api', ['api']),
			'support' => self::item('support', 'Support', 'life-buoy', $adminUrl . 'support', ['support']),
		];

		if ($posAvailable && $posUrl !== '') {
			$items['pos'] = self::item('pos', 'POS', 'monitor', $posUrl, ['pos']);
		}

		foreach ($hookItems as $groupItems) {
			if (!is_array($groupItems)) {
				continue;
			}

			foreach ($groupItems as $hookItem) {
				if (!is_array($hookItem)) {
					continue;
				}

				$key = self::normalizeKey((string) ($hookItem['slug'] ?? ''));

				if ($key === '') {
					$key = self::keyFromUrl((string) ($hookItem['url'] ?? ''));
				}

				if ($key === '' || isset($items[$key])) {
					continue;
				}

				$items[$key] = self::item(
					$key,
					(string) ($hookItem['label'] ?? $key),
					'puzzle',
					(string) ($hookItem['url'] ?? ''),
					[$key],
					(int) ($hookItem['badge'] ?? 0),
					(string) ($hookItem['target'] ?? '')
				);
			}
		}

		return $items;
	}

	/**
	 * @param array<string, array<string, mixed>> $catalog
	 * @return array<int, array<string, mixed>>
	 */
	public static function resolveItems(array $catalog, array $keys, string $pageName): array
	{
		$list = [];

		foreach ($keys as $key) {
			if (!isset($catalog[$key])) {
				continue;
			}

			$item = $catalog[$key];
			$item['active'] = in_array($pageName, $item['match'], true);
			$list[] = $item;
		}

		return $list;
	}

	public static function keyFromUrl(string $url): string
	{
		$url = trim($url);

		if ($url === '') {
			return '';
		}

		$path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
		$adminUri = trim(Admin::uri(), '/');

		if ($adminUri !== '' && preg_match('#/' . preg_quote($adminUri, '#') . '/([^/?#]+)#', $path, $match)) {
			return self::normalizeKey($match[1]);
		}

		$base = basename($path);

		if ($base !== '' && $base !== '/') {
			return self::normalizeKey($base);
		}

		return 'url:' . substr(md5($url), 0, 16);
	}

	/**
	 * @param string[] $match
	 * @return array<string, mixed>
	 */
	private static function item(
		string $key,
		string $label,
		string $icon,
		string $url,
		array $match,
		int $badge = 0,
		string $target = ''
	): array {
		return [
			'key' => $key,
			'label' => $label,
			'icon' => $icon,
			'url' => $url,
			'match' => $match,
			'badge' => max(0, $badge),
			'target' => $target,
			'active' => false,
		];
	}
}
