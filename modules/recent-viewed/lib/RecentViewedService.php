<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class RecentViewedService
{
	private const COOKIE = 'fshop_recent_viewed';
	private const DEFAULT_LIMIT = 8;
	private const DEFAULT_STORE = 24;

	public static function getTitle(): string
	{
		$title = trim((string) Settings::get('RECENT_VIEWED_TITLE'));

		return $title !== '' ? $title : 'Son baktığınız ürünler';
	}

	public static function isEnabled(): bool
	{
		return Settings::get('RECENT_VIEWED_ENABLED') !== '0';
	}

	public static function getDisplayLimit(): int
	{
		$limit = (int) Settings::get('RECENT_VIEWED_LIMIT');

		return max(1, min(20, $limit > 0 ? $limit : self::DEFAULT_LIMIT));
	}

	public static function getStoreLimit(): int
	{
		$limit = (int) Settings::get('RECENT_VIEWED_STORE');

		return max(5, min(50, $limit > 0 ? $limit : self::DEFAULT_STORE));
	}

	/** @return int[] */
	public static function getStoredIds(): array
	{
		$raw = (string) ($_COOKIE[self::COOKIE] ?? '');

		if ($raw === '') {
			return [];
		}

		$data = json_decode($raw, true);

		if (!is_array($data)) {
			return [];
		}

		$ids = [];

		foreach ($data as $id) {
			$id = (int) $id;

			if ($id > 0) {
				$ids[] = $id;
			}
		}

		return array_values(array_unique($ids));
	}

	public static function track(int $idProduct): void
	{
		if ($idProduct <= 0 || !self::isEnabled()) {
			return;
		}

		$ids = self::getStoredIds();
		$ids = array_values(array_filter($ids, static function (int $id) use ($idProduct): bool {
			return $id !== $idProduct;
		}));
		array_unshift($ids, $idProduct);
		$ids = array_slice($ids, 0, self::getStoreLimit());

		self::writeCookie($ids);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getProductsForHome(): array
	{
		if (!self::isEnabled()) {
			return [];
		}

		return self::loadProducts(array_slice(self::getStoredIds(), 0, self::getDisplayLimit()));
	}

	/**
	 * @param int[] $excludeIds
	 * @return array<int, array<string, mixed>>
	 */
	public static function getProductsForCart(array $excludeIds = []): array
	{
		if (!self::isEnabled()) {
			return [];
		}

		$ids = self::filterIds(self::getStoredIds(), $excludeIds);

		return self::loadProducts(array_slice($ids, 0, 6));
	}

	/**
	 * @param int[] $excludeIds
	 * @return array<int, array<string, mixed>>
	 */
	public static function getForYouProducts(array $excludeIds = []): array
	{
		$ids = self::filterIds(self::parseIdList((string) Settings::get('SANA_OZEL_PRODUCT_IDS')), $excludeIds);

		return self::loadProducts(array_slice($ids, 0, 6), true);
	}

	public static function ensureCartHook(): void
	{
		$installed = (int) DB::getValue('SELECT installed FROM modules WHERE name = ? LIMIT 1', ['recent-viewed']);

		if ($installed !== 1) {
			return;
		}

		$exists = (int) DB::getValue(
			'SELECT id_hook FROM module_display_hooks WHERE module_name = ? AND hook_name = ? LIMIT 1',
			['recent-viewed', 'cart']
		);

		if ($exists > 0) {
			return;
		}

		$position = (int) DB::getValue(
			'SELECT MAX(position) FROM module_display_hooks WHERE module_name = ?',
			['recent-viewed']
		);

		DB::insert('module_display_hooks', [
			'module_name' => 'recent-viewed',
			'hook_name' => 'cart',
			'position' => $position + 1,
		]);
	}

	/**
	 * @param int[] $ids
	 * @return array<int, array<string, mixed>>
	 */
	private static function loadProducts(array $ids, bool $inStockOnly = false): array
	{
		$products = [];

		foreach ($ids as $id) {
			$id = (int) $id;

			if ($id <= 0) {
				continue;
			}

			$row = Product::getById($id);

			if (!$row) {
				continue;
			}

			if ($inStockOnly && empty($row['in_stock'])) {
				continue;
			}

			$products[] = $row;
		}

		return $products;
	}

	/**
	 * @param int[] $ids
	 * @param int[] $excludeIds
	 * @return int[]
	 */
	private static function filterIds(array $ids, array $excludeIds): array
	{
		$exclude = [];

		foreach ($excludeIds as $id) {
			$id = (int) $id;

			if ($id > 0) {
				$exclude[$id] = true;
			}
		}

		$out = [];

		foreach ($ids as $id) {
			$id = (int) $id;

			if ($id > 0 && empty($exclude[$id])) {
				$out[] = $id;
			}
		}

		return array_values(array_unique($out));
	}

	/** @return int[] */
	private static function parseIdList(string $raw): array
	{
		$ids = [];

		foreach (preg_split('/[,\s]+/', $raw) ?: [] as $id) {
			$id = (int) $id;

			if ($id > 0) {
				$ids[] = $id;
			}
		}

		return array_values(array_unique($ids));
	}

	/** @param array<string, mixed> $input */
	public static function saveSettings(array $input): void
	{
		Settings::set('RECENT_VIEWED_ENABLED', !empty($input['enabled']) ? '1' : '0');
		Settings::set('RECENT_VIEWED_TITLE', trim((string) ($input['title'] ?? '')));
		Settings::set('RECENT_VIEWED_LIMIT', (string) max(1, min(20, (int) ($input['limit'] ?? self::DEFAULT_LIMIT))));
		Settings::set('RECENT_VIEWED_STORE', (string) max(5, min(50, (int) ($input['store'] ?? self::DEFAULT_STORE))));
	}

	/** @return array<string, mixed> */
	public static function getSettings(): array
	{
		return [
			'enabled' => self::isEnabled(),
			'title' => self::getTitle(),
			'limit' => self::getDisplayLimit(),
			'store' => self::getStoreLimit(),
		];
	}

	/** @param int[] $ids */
	private static function writeCookie(array $ids): void
	{
		$payload = json_encode(array_values(array_map('intval', $ids)), JSON_UNESCAPED_UNICODE);

		if ($payload === false) {
			return;
		}

		$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
		$folder = trim((string) Settings::get('FOLDER'), '/');
		$path = $folder !== '' ? '/' . $folder . '/' : '/';

		setcookie(self::COOKIE, $payload, [
			'expires' => time() + (86400 * 30),
			'path' => $path,
			'domain' => '',
			'secure' => $secure,
			'httponly' => true,
			'samesite' => 'Lax',
		]);

		$_COOKIE[self::COOKIE] = $payload;
	}
}
