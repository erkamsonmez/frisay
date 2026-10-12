<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

/**
 * PrestaShop tablolarından marka, kategori ve müşteri içe aktarma.
 * Aynı isim / e-posta varsa mevcut kayıt güncellenmez.
 */
class PrestashopImportService
{
	public const SETTING_PREFIX = 'PS_IMPORT_PREFIX';
	public const SETTING_LANG_ID = 'PS_IMPORT_LANG_ID';
	public const SETTING_COOKIE_KEY = 'PS_IMPORT_COOKIE_KEY';
	public const SETTING_SKIP_ROOT = 'PS_IMPORT_SKIP_ROOT';
	public const SETTING_SKIP_GUESTS = 'PS_IMPORT_SKIP_GUESTS';

	public static function ensureSchema(): void
	{
		DB::execute(
			"CREATE TABLE IF NOT EXISTS `prestashop_import_map` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`entity` varchar(16) NOT NULL,
				`ps_id` int(11) NOT NULL,
				`local_id` int(11) NOT NULL,
				`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `entity_ps` (`entity`, `ps_id`),
				KEY `entity_local` (`entity`, `local_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	public static function ensureDefaultSettings(): void
	{
		if (Settings::get(self::SETTING_PREFIX) === '') {
			Settings::set(self::SETTING_PREFIX, 'ps_');
		}

		if (Settings::get(self::SETTING_LANG_ID) === '') {
			Settings::set(self::SETTING_LANG_ID, '1');
		}

		if (Settings::get(self::SETTING_SKIP_ROOT) === '') {
			Settings::set(self::SETTING_SKIP_ROOT, '0');
		}

		if (Settings::get(self::SETTING_SKIP_GUESTS) === '') {
			Settings::set(self::SETTING_SKIP_GUESTS, '1');
		}
	}

	/** @return array{prefix:string,lang_id:int,cookie_key:string,skip_root:bool,skip_guests:bool} */
	public static function getSettings(): array
	{
		self::ensureDefaultSettings();

		return [
			'prefix' => self::prefix(),
			'lang_id' => max(1, (int) Settings::get(self::SETTING_LANG_ID)),
			'cookie_key' => trim((string) Settings::get(self::SETTING_COOKIE_KEY)),
			'skip_root' => Settings::get(self::SETTING_SKIP_ROOT) === '1',
			'skip_guests' => Settings::get(self::SETTING_SKIP_GUESTS) !== '0',
		];
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function saveSettings(array $data): void
	{
		$prefix = self::sanitizePrefix((string) ($data['prefix'] ?? 'ps_'));
		Settings::set(self::SETTING_PREFIX, $prefix);
		Settings::set(self::SETTING_LANG_ID, (string) max(1, (int) ($data['lang_id'] ?? 1)));
		Settings::set(self::SETTING_COOKIE_KEY, trim((string) ($data['cookie_key'] ?? '')));
		Settings::set(self::SETTING_SKIP_ROOT, !empty($data['skip_root']) ? '1' : '0');
		Settings::set(self::SETTING_SKIP_GUESTS, !empty($data['skip_guests']) ? '1' : '0');
	}

	/**
	 * @return list<array{table:string,label:string,exists:bool,count:int,required:bool}>
	 */
	public static function getSourceStatus(): array
	{
		$items = [
			['suffix' => 'manufacturer', 'label' => 'ps_manufacturer', 'required' => true],
			['suffix' => 'category', 'label' => 'ps_category', 'required' => true],
			['suffix' => 'category_lang', 'label' => 'ps_category_lang', 'required' => true],
			['suffix' => 'customer', 'label' => 'ps_customer', 'required' => true],
			['suffix' => 'address', 'label' => 'ps_address', 'required' => false],
			['suffix' => 'lang', 'label' => 'ps_lang', 'required' => false],
		];

		$status = [];

		foreach ($items as $item) {
			$table = self::table($item['suffix']);
			$exists = self::tableExists($table);
			$count = 0;

			if ($exists) {
				$count = (int) DB::getValue('SELECT COUNT(*) FROM `' . $table . '`');
			}

			$status[] = [
				'table' => $table,
				'label' => $item['label'],
				'exists' => $exists,
				'count' => $count,
				'required' => $item['required'],
			];
		}

		return $status;
	}

	/** @return array{success:bool,message:string,stats:array<string, mixed>} */
	public static function importAll(): array
	{
		$brands = self::importBrands();
		$categories = self::importCategories();
		$customers = self::importCustomers();

		$success = !empty($brands['success']) && !empty($categories['success']) && !empty($customers['success']);

		return [
			'success' => $success,
			'message' => adminT('Import finished'),
			'stats' => [
				'brands' => $brands['stats'] ?? [],
				'categories' => $categories['stats'] ?? [],
				'customers' => $customers['stats'] ?? [],
			],
		];
	}

	/** @return array{success:bool,message:string,stats:array<string, mixed>} */
	public static function importBrands(): array
	{
		self::prepareRuntime();
		$table = self::table('manufacturer');

		if (!self::tableExists($table)) {
			return self::fail(adminT('PrestaShop tables were not found. Check the table prefix.'), 'brand');
		}

		$rows = DB::execute(
			'SELECT id_manufacturer, name, active FROM `' . $table . '` ORDER BY id_manufacturer ASC'
		);

		if ($rows === false) {
			return self::fail(adminT('PrestaShop tables were not found. Check the table prefix.'), 'brand');
		}

		$stats = self::emptyStats();

		foreach ($rows as $row) {
			$stats['total']++;
			$psId = (int) ($row['id_manufacturer'] ?? 0);
			$name = trim((string) ($row['name'] ?? ''));

			if ($psId <= 0 || $name === '') {
				$stats['errors']++;
				self::pushError($stats, '#' . $psId . ': ' . adminT('Brand name is required'));
				continue;
			}

			$mapped = self::getMappedId('brand', $psId);

			if ($mapped > 0) {
				$stats['skipped']++;
				continue;
			}

			$existing = (int) DB::getValue(
				'SELECT id_brand FROM brands WHERE LOWER(brand_name) = LOWER(?) LIMIT 1',
				[$name]
			);

			if ($existing > 0) {
				self::saveMap('brand', $psId, $existing);
				$stats['skipped']++;
				continue;
			}

			$slug = self::uniqueSlug($name, '', 'brand');
			$result = Brand::save([
				'brand_name' => mb_substr($name, 0, 48),
				'brand_link' => $slug,
				'active' => !empty($row['active']) ? 1 : 0,
			]);

			if (empty($result['success'])) {
				$stats['errors']++;
				self::pushError($stats, $name . ': ' . (string) ($result['message'] ?? ''));
				continue;
			}

			self::saveMap('brand', $psId, (int) $result['id']);
			$stats['created']++;
		}

		return self::ok(adminT('Brands imported'), $stats);
	}

	/** @return array{success:bool,message:string,stats:array<string, mixed>} */
	public static function importCategories(): array
	{
		self::prepareRuntime();
		$categoryTable = self::table('category');
		$langTable = self::table('category_lang');

		if (!self::tableExists($categoryTable) || !self::tableExists($langTable)) {
			return self::fail(adminT('PrestaShop tables were not found. Check the table prefix.'), 'category');
		}

		$skipRoot = Settings::get(self::SETTING_SKIP_ROOT) === '1';
		$langMap = self::langIdMap();
		$preferredLangId = max(1, (int) Settings::get(self::SETTING_LANG_ID));
		$defaultLang = Lang::getDefault();

		$rows = DB::execute(
			'SELECT id_category, id_parent, id_shop_default, level_depth, position, active, is_root_category
			 FROM `' . $categoryTable . '`
			 WHERE id_category >= 1
			 ORDER BY level_depth ASC, position ASC, id_category ASC'
		);

		if ($rows === false) {
			return self::fail(adminT('PrestaShop tables were not found. Check the table prefix.'), 'category');
		}

		$stats = self::emptyStats();

		foreach ($rows as $row) {
			$stats['total']++;
			$psId = (int) ($row['id_category'] ?? 0);
			$psParent = (int) ($row['id_parent'] ?? 0);

			if ($psId <= 0) {
				$stats['errors']++;
				continue;
			}

			if ($skipRoot && (int) ($row['is_root_category'] ?? 0) === 1) {
				self::saveMap('category', $psId, 0);
				$stats['skipped']++;
				continue;
			}

			$mapped = self::getMappedId('category', $psId);

			if ($mapped > 0) {
				$stats['skipped']++;
				continue;
			}

			$shopId = max(1, (int) ($row['id_shop_default'] ?? 1));
			$translations = self::categoryLangs($langTable, $psId, $shopId, $langMap, $preferredLangId, $defaultLang);

			if ($translations === []) {
				$stats['errors']++;
				self::pushError($stats, '#' . $psId . ': ' . adminT('Category name is required'));
				continue;
			}

			$defaultEntry = $translations[$defaultLang] ?? reset($translations);
			$name = trim((string) ($defaultEntry['category_name'] ?? ''));
			$slug = trim((string) ($defaultEntry['category_link'] ?? ''));

			if ($name === '') {
				$stats['errors']++;
				self::pushError($stats, '#' . $psId . ': ' . adminT('Category name is required'));
				continue;
			}

			$parentLocal = 0;

			if ($psParent > 0) {
				$parentLocal = self::getMappedId('category', $psParent);
			}

			$existing = (int) DB::getValue(
				'SELECT id_category FROM categories WHERE LOWER(category_name) = LOWER(?) AND id_parent = ? LIMIT 1',
				[$name, $parentLocal]
			);

			if ($existing <= 0 && $slug !== '') {
				$existing = (int) DB::getValue(
					'SELECT id_category FROM categories WHERE category_link = ? LIMIT 1',
					[$slug]
				);
			}

			if ($existing > 0) {
				self::saveMap('category', $psId, $existing);
				$stats['skipped']++;
				continue;
			}

			$slug = self::uniqueSlug($name, $slug, 'category');
			$translations[$defaultLang]['category_link'] = $slug;

			$result = Category::save([
				'category_name' => mb_substr($name, 0, 64),
				'category_link' => $slug,
				'meta_title' => (string) ($defaultEntry['meta_title'] ?? ''),
				'meta_description' => (string) ($defaultEntry['meta_description'] ?? ''),
				'id_parent' => $parentLocal,
				'active' => !empty($row['active']) ? 1 : 0,
				'langs' => $translations,
			]);

			if (empty($result['success'])) {
				$stats['errors']++;
				self::pushError($stats, $name . ': ' . (string) ($result['message'] ?? ''));
				continue;
			}

			self::saveMap('category', $psId, (int) $result['id']);
			$stats['created']++;
		}

		return self::ok(adminT('Categories imported'), $stats);
	}

	/** @return array{success:bool,message:string,stats:array<string, mixed>} */
	public static function importCustomers(): array
	{
		self::prepareRuntime();
		Customer::ensureSchema();
		$table = self::table('customer');

		if (!self::tableExists($table)) {
			return self::fail(adminT('PrestaShop tables were not found. Check the table prefix.'), 'customer');
		}

		$skipGuests = Settings::get(self::SETTING_SKIP_GUESTS) !== '0';
		$idGroup = class_exists('CustomerGroup', false) ? CustomerGroup::getDefaultId() : 0;

		$sql = 'SELECT id_customer, firstname, lastname, email, passwd, active, is_guest, deleted, date_add
			FROM `' . $table . '`
			WHERE deleted = 0';

		if ($skipGuests) {
			$sql .= ' AND is_guest = 0';
		}

		$sql .= ' ORDER BY id_customer ASC';
		$rows = DB::execute($sql);

		if ($rows === false) {
			return self::fail(adminT('PrestaShop tables were not found. Check the table prefix.'), 'customer');
		}

		$stats = self::emptyStats();

		foreach ($rows as $row) {
			$stats['total']++;
			$psId = (int) ($row['id_customer'] ?? 0);
			$email = strtolower(trim((string) ($row['email'] ?? '')));

			if ($psId <= 0) {
				$stats['errors']++;
				continue;
			}

			$mapped = self::getMappedId('customer', $psId);

			if ($mapped > 0) {
				$stats['skipped']++;
				continue;
			}

			if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$stats['errors']++;
				self::pushError($stats, '#' . $psId . ': ' . adminT('Please enter a valid email'));
				continue;
			}

			$existing = (int) DB::getValue(
				'SELECT id_user FROM users WHERE email = ? LIMIT 1',
				[$email]
			);

			if ($existing > 0) {
				self::saveMap('customer', $psId, $existing);
				$stats['skipped']++;
				continue;
			}

			$fullName = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['lastname'] ?? ''));

			if ($fullName === '') {
				$fullName = (string) strstr($email, '@', true);
			}

			$fullName = trim(preg_replace('/\s+/', ' ', strip_tags($fullName)) ?? '');

			if ($fullName === '') {
				$fullName = 'Customer ' . $psId;
			}

			$phone = self::customerPhone($psId);

			if ($phone === '' || self::phoneTaken($phone)) {
				$phone = self::uniquePlaceholderPhone($psId);
			}

			$passwordHash = self::normalizeImportedPassword((string) ($row['passwd'] ?? ''));
			$dateAdd = self::normalizeDateTime((string) ($row['date_add'] ?? ''));

			$payload = [
				'user_full_name' => mb_substr($fullName, 0, 128),
				'phone' => $phone,
				'email' => mb_substr($email, 0, 128),
				'password' => $passwordHash,
				'active' => !empty($row['active']) ? 1 : 0,
				'id_group' => $idGroup,
			];

			if ($dateAdd !== '') {
				$payload['date_add'] = $dateAdd;
			}

			$id = DB::insert('users', $payload);

			if (!$id) {
				$stats['errors']++;
				self::pushError($stats, $email . ': ' . adminT('Customer could not be created'));
				continue;
			}

			self::saveMap('customer', $psId, (int) $id);
			$stats['created']++;
		}

		return self::ok(adminT('Customers imported'), $stats);
	}

	private static function prepareRuntime(): void
	{
		self::ensureSchema();
		self::ensureDefaultSettings();
		@set_time_limit(0);
	}

	private static function prefix(): string
	{
		return self::sanitizePrefix((string) Settings::get(self::SETTING_PREFIX));
	}

	private static function sanitizePrefix(string $prefix): string
	{
		$prefix = trim($prefix);

		if ($prefix === '') {
			return 'ps_';
		}

		$clean = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix);

		return ($clean !== null && $clean !== '') ? $clean : 'ps_';
	}

	private static function table(string $suffix): string
	{
		$suffix = preg_replace('/[^a-zA-Z0-9_]/', '', $suffix) ?? '';

		return self::prefix() . $suffix;
	}

	private static function tableExists(string $table): bool
	{
		$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table) ?? '';

		if ($table === '') {
			return false;
		}

		$like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $table);
		$rows = DB::execute('SHOW TABLES LIKE ?', [$like]);

		return is_array($rows) && $rows !== [];
	}

	private static function getMappedId(string $entity, int $psId): int
	{
		if ($psId <= 0) {
			return 0;
		}

		return (int) DB::getValue(
			'SELECT local_id FROM prestashop_import_map WHERE entity = ? AND ps_id = ? LIMIT 1',
			[$entity, $psId]
		);
	}

	private static function saveMap(string $entity, int $psId, int $localId): void
	{
		$existing = (int) DB::getValue(
			'SELECT id FROM prestashop_import_map WHERE entity = ? AND ps_id = ? LIMIT 1',
			[$entity, $psId]
		);

		if ($existing > 0) {
			DB::update(
				'prestashop_import_map',
				['local_id' => $localId],
				'id = :where_id',
				['where_id' => $existing]
			);

			return;
		}

		DB::insert('prestashop_import_map', [
			'entity' => $entity,
			'ps_id' => $psId,
			'local_id' => $localId,
		]);
	}

	/**
	 * @return array<int, string> id_lang => iso
	 */
	private static function langIdMap(): array
	{
		$map = [];
		$langTable = self::table('lang');

		if (!self::tableExists($langTable)) {
			return $map;
		}

		$rows = DB::execute('SELECT id_lang, iso_code FROM `' . $langTable . '`');

		if (!is_array($rows)) {
			return $map;
		}

		$available = Lang::getAvailable();

		foreach ($rows as $row) {
			$code = strtolower(trim((string) ($row['iso_code'] ?? '')));

			if ($code === '' || !in_array($code, $available, true)) {
				continue;
			}

			$map[(int) $row['id_lang']] = $code;
		}

		return $map;
	}

	/**
	 * @param array<int, string> $langMap
	 * @return array<string, array{category_name:string,category_link:string,meta_title:string,meta_description:string}>
	 */
	private static function categoryLangs(
		string $langTable,
		int $psId,
		int $shopId,
		array $langMap,
		int $preferredLangId,
		string $defaultLang
	): array {
		$rows = DB::execute(
			'SELECT id_lang, id_shop, name, link_rewrite, meta_title, meta_description, description
			 FROM `' . $langTable . '`
			 WHERE id_category = ?
			 ORDER BY id_shop ASC, id_lang ASC',
			[$psId]
		);

		if (!is_array($rows) || $rows === []) {
			return [];
		}

		$byLang = [];

		foreach ($rows as $row) {
			$idLang = (int) ($row['id_lang'] ?? 0);
			$idShop = (int) ($row['id_shop'] ?? 0);

			if (isset($byLang[$idLang]) && $idShop !== $shopId) {
				continue;
			}

			if ($idShop === $shopId || !isset($byLang[$idLang])) {
				$byLang[$idLang] = $row;
			}
		}

		$out = [];

		foreach ($byLang as $idLang => $row) {
			$code = $langMap[$idLang] ?? '';
			$entry = self::categoryLangEntry($row);

			if ($entry['category_name'] === '') {
				continue;
			}

			if ($code !== '') {
				$out[$code] = $entry;
			}
		}

		if (!isset($out[$defaultLang])) {
			$fallback = $byLang[$preferredLangId] ?? reset($byLang);
			$entry = self::categoryLangEntry(is_array($fallback) ? $fallback : []);

			if ($entry['category_name'] !== '') {
				$out[$defaultLang] = $entry;
			}
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array{category_name:string,category_link:string,meta_title:string,meta_description:string}
	 */
	private static function categoryLangEntry(array $row): array
	{
		$name = trim(strip_tags((string) ($row['name'] ?? '')));
		$slug = trim((string) ($row['link_rewrite'] ?? ''));
		$metaTitle = trim(strip_tags((string) ($row['meta_title'] ?? '')));
		$metaDescription = trim(strip_tags((string) ($row['meta_description'] ?? '')));

		if ($metaDescription === '') {
			$metaDescription = mb_substr(trim(strip_tags((string) ($row['description'] ?? ''))), 0, 512);
		}

		if ($slug !== '') {
			$slug = Tools::createSlug($slug);
		}

		return [
			'category_name' => mb_substr($name, 0, 64),
			'category_link' => mb_substr($slug, 0, 128),
			'meta_title' => mb_substr($metaTitle, 0, 255),
			'meta_description' => mb_substr($metaDescription, 0, 512),
		];
	}

	private static function uniqueSlug(string $name, string $preferred, string $type): string
	{
		$slug = Tools::createSlug($preferred !== '' ? $preferred : $name);

		if ($slug === '') {
			$slug = $type;
		}

		$slug = mb_substr($slug, 0, 120);
		$base = $slug;
		$i = 2;

		while ($i < 500) {
			$unique = $type === 'brand'
				? Brand::isLinkUnique($slug)
				: Category::isLinkUnique($slug);

			if ($unique) {
				return $slug;
			}

			$suffix = '-' . $i;
			$slug = mb_substr($base, 0, 120 - strlen($suffix)) . $suffix;
			$i++;
		}

		return $base . '-' . substr(md5($name . microtime()), 0, 6);
	}

	private static function customerPhone(int $psId): string
	{
		$addressTable = self::table('address');

		if (!self::tableExists($addressTable)) {
			return '';
		}

		$rows = DB::execute(
			'SELECT phone, phone_mobile FROM `' . $addressTable . '`
			 WHERE id_customer = ? AND deleted = 0
			 ORDER BY id_address DESC LIMIT 8',
			[$psId]
		);

		if ($rows === false) {
			$rows = DB::execute(
				'SELECT phone, phone_mobile FROM `' . $addressTable . '`
				 WHERE id_customer = ?
				 ORDER BY id_address DESC LIMIT 8',
				[$psId]
			);
		}

		if (!is_array($rows)) {
			return '';
		}

		foreach ($rows as $row) {
			foreach (['phone_mobile', 'phone'] as $field) {
				$phone = Customer::normalizePhone((string) ($row[$field] ?? ''));

				if (Customer::isValidPhone($phone)) {
					return $phone;
				}
			}
		}

		return '';
	}

	private static function phoneTaken(string $phone): bool
	{
		return (int) DB::getValue('SELECT id_user FROM users WHERE phone = ? LIMIT 1', [$phone]) > 0;
	}

	private static function uniquePlaceholderPhone(int $psId): string
	{
		$n = 0;

		do {
			$raw = '8' . str_pad((string) ($psId + ($n * 100000)), 10, '0', STR_PAD_LEFT);
			$phone = mb_substr($raw, 0, 15);
			$n++;
		} while (self::phoneTaken($phone) && $n < 50);

		if (self::phoneTaken($phone)) {
			$phone = '8' . substr((string) time(), -9) . (string) random_int(0, 9);
		}

		return $phone;
	}

	public static function normalizeImportedPassword(string $hash): string
	{
		$hash = trim($hash);

		if ($hash === '') {
			return Customer::hashPassword(bin2hex(random_bytes(16)));
		}

		$info = password_get_info($hash);

		if ($info['algo'] !== null && $info['algo'] !== 0) {
			return $hash;
		}

		if (strlen($hash) === 32 && ctype_xdigit($hash)) {
			return 'prestashop:' . strtolower($hash);
		}

		return $hash;
	}

	private static function normalizeDateTime(string $value): string
	{
		$value = trim($value);

		if ($value === '' || $value === '0000-00-00 00:00:00') {
			return '';
		}

		$ts = strtotime($value);

		if ($ts === false) {
			return '';
		}

		return date('Y-m-d H:i:s', $ts);
	}

	/** @return array{total:int,created:int,skipped:int,errors:int,error_messages:list<string>} */
	private static function emptyStats(): array
	{
		return [
			'total' => 0,
			'created' => 0,
			'skipped' => 0,
			'errors' => 0,
			'error_messages' => [],
		];
	}

	/**
	 * @param array<string, mixed> $stats
	 */
	private static function pushError(array &$stats, string $message): void
	{
		$message = trim($message);

		if ($message === '' || count($stats['error_messages']) >= 25) {
			return;
		}

		$stats['error_messages'][] = $message;
	}

	/**
	 * @param array<string, mixed> $stats
	 * @return array{success:bool,message:string,stats:array<string, mixed>}
	 */
	private static function ok(string $message, array $stats): array
	{
		return [
			'success' => true,
			'message' => $message,
			'stats' => $stats,
		];
	}

	/** @return array{success:bool,message:string,stats:array<string, mixed>} */
	private static function fail(string $message, string $entity): array
	{
		return [
			'success' => false,
			'message' => $message,
			'stats' => self::emptyStats() + ['entity' => $entity],
		];
	}
}
