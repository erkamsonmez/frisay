<?php

class Welcome
{
	public const PRODUCT_TARGET = 10;
	public const META_OK_MIN = 120;

	/**
	 * @return array<string, mixed>
	 */
	public static function getReport(): array
	{
		Product::ensureSchema();
		Category::ensureSchema();
		Brand::ensureSchema();
		Lang::ensureSchema();

		$setup = self::setupChecks();
		$seo = [
			'products' => self::seoStats('products', 'id_product', 'product_lang'),
			'categories' => self::seoStats('categories', 'id_category', 'category_lang'),
			'brands' => self::seoStats('brands', 'id_brand', 'brand_lang'),
		];

		$setupScore = 0;
		$weight = 100 / max(1, count($setup['items']));

		foreach ($setup['items'] as $item) {
			$setupScore += $weight * (float) ($item['progress'] ?? 0);
		}

		$setupScore = (int) round($setupScore);
		$seoAverage = self::seoAverage($seo);
		$doneCount = 0;
		$next = null;

		foreach ($setup['items'] as $item) {
			if (!empty($item['done'])) {
				++$doneCount;
			} elseif ($next === null) {
				$next = $item;
			}
		}

		$totalChecks = count($setup['items']);

		return [
			'setup_score' => $setupScore,
			'setup_tone' => self::tone($setupScore),
			'setup' => $setup,
			'done_count' => $doneCount,
			'total_checks' => $totalChecks,
			'next' => $next,
			'seo' => $seo,
			'seo_cards' => [
				[
					'key' => 'products',
					'label' => 'Products',
					'url' => Admin::url('products'),
					'edit' => 'Edit products',
					'row' => $seo['products'],
				],
				[
					'key' => 'categories',
					'label' => 'Categories',
					'url' => Admin::url('categories'),
					'edit' => 'Edit categories',
					'row' => $seo['categories'],
				],
				[
					'key' => 'brands',
					'label' => 'Brands',
					'url' => Admin::url('brands'),
					'edit' => 'Edit brands',
					'row' => $seo['brands'],
				],
			],
			'seo_average' => $seoAverage,
			'seo_average_tone' => self::tone($seoAverage),
			'guide' => self::guideSteps(),
		];
	}

	public static function tone(int $score): string
	{
		if ($score >= 80) {
			return 'ok';
		}

		if ($score >= 50) {
			return 'warn';
		}

		return 'bad';
	}

	/**
	 * @param array<string, array<string, mixed>> $seo
	 */
	private static function seoAverage(array $seo): int
	{
		$scores = [];

		foreach ($seo as $row) {
			if ((int) ($row['total'] ?? 0) > 0) {
				$scores[] = (int) ($row['score'] ?? 0);
			}
		}

		if ($scores === []) {
			return 0;
		}

		return (int) round(array_sum($scores) / count($scores));
	}

	/**
	 * @return array{items: array<int, array<string, mixed>>, payments: array<int, array<string, mixed>>}
	 */
	private static function setupChecks(): array
	{
		$productCount = self::tableCount('products');
		$brandCount = self::tableCount('brands');
		$categoryCount = self::tableCount('categories');
		$cargo = self::cargoStatus();
		$payments = self::paymentStatus();
		$adminChanged = Admin::uri() !== 'admin';

		$items = [
			[
				'id' => 'products',
				'title' => 'At least 10 products added?',
				'help' => 'Add products with price, stock and images so customers can shop.',
				'url' => Admin::url('products'),
				'action' => 'Add product',
				'action_url' => Admin::url('product'),
				'icon' => 'package',
				'done' => $productCount >= self::PRODUCT_TARGET,
				'progress' => min(1.0, $productCount / self::PRODUCT_TARGET),
				'detail' => $productCount . ' / ' . self::PRODUCT_TARGET,
			],
			[
				'id' => 'brands',
				'title' => 'At least 1 brand',
				'help' => 'Brands group products and improve filters and SEO landing pages.',
				'url' => Admin::url('brands'),
				'action' => 'Add brand',
				'action_url' => Admin::url('brand'),
				'icon' => 'award',
				'done' => $brandCount >= 1,
				'progress' => $brandCount >= 1 ? 1.0 : 0.0,
				'detail' => (string) $brandCount,
			],
			[
				'id' => 'categories',
				'title' => 'At least 1 category',
				'help' => 'Categories build the store menu and product listing pages.',
				'url' => Admin::url('categories'),
				'action' => 'Add category',
				'action_url' => Admin::url('category'),
				'icon' => 'folder-tree',
				'done' => $categoryCount >= 1,
				'progress' => $categoryCount >= 1 ? 1.0 : 0.0,
				'detail' => (string) $categoryCount,
			],
			[
				'id' => 'shipping',
				'title' => 'Shipping settings configured?',
				'help' => 'Add at least one active carrier so checkout can calculate shipping.',
				'url' => Admin::url('cargos'),
				'action' => 'Shipping',
				'action_url' => Admin::url('cargos'),
				'icon' => 'truck',
				'done' => $cargo['done'],
				'progress' => $cargo['done'] ? 1.0 : 0.0,
				'detail' => $cargo['detail'],
			],
			[
				'id' => 'payments',
				'title' => 'Payment modules installed and configured?',
				'help' => 'Install a payment module (bank transfer, iyzico, PayTR…) and fill its API or IBAN settings.',
				'url' => Admin::url('modules'),
				'action' => 'Modules',
				'action_url' => Admin::url('modules'),
				'icon' => 'credit-card',
				'done' => $payments['done'],
				'progress' => $payments['done'] ? 1.0 : 0.0,
				'detail' => $payments['detail'],
			],
			[
				'id' => 'admin_uri',
				'title' => 'Admin URL changed?',
				'help' => 'The default /admin path is easy for bots. Set ADMIN_URI in config/env.php to a private slug. Do not rename the admin/ folder.',
				'url' => Admin::url('welcome'),
				'action' => 'How to change',
				'action_url' => Admin::url('welcome') . '#admin-url',
				'icon' => 'shield',
				'done' => $adminChanged,
				'progress' => $adminChanged ? 1.0 : 0.0,
				'detail' => '/' . Admin::uri() . '/',
			],
		];

		foreach ($items as &$item) {
			$item['percent'] = (int) round((float) ($item['progress'] ?? 0) * 100);
		}
		unset($item);

		return [
			'items' => $items,
			'payments' => $payments['list'],
			'admin_uri' => Admin::uri(),
			'admin_changed' => $adminChanged,
		];
	}

	/**
	 * @return array{done: bool, detail: string}
	 */
	private static function cargoStatus(): array
	{
		Cargo::ensureSchema();
		$list = Cargo::getList(true);
		$count = count($list);

		if ($count <= 0) {
			return [
				'done' => false,
				'detail' => '0',
			];
		}

		return [
			'done' => true,
			'detail' => (string) $count,
		];
	}

	/**
	 * @return array{done: bool, detail: string, list: array<int, array<string, mixed>>}
	 */
	private static function paymentStatus(): array
	{
		$list = [];
		$configuredActive = 0;

		foreach (Module::getAdminList() as $row) {
			if (empty($row['installed'])) {
				continue;
			}

			$module = Module::getInstance((string) $row['name']);

			if (!$module || !$module->isPayment) {
				continue;
			}

			$configured = self::moduleIsConfigured($module);
			$active = !empty($row['active']);

			if ($active && $configured) {
				++$configuredActive;
			}

			$list[] = [
				'name' => $module->name,
				'title' => $module->title,
				'active' => $active,
				'configured' => $configured,
				'url' => Admin::url('module-' . $module->name),
			];
		}

		$detail = $configuredActive > 0
			? (string) $configuredActive
			: '0';

		return [
			'done' => $configuredActive > 0,
			'detail' => $detail,
			'list' => $list,
		];
	}

	private static function moduleIsConfigured(ModuleBase $module): bool
	{
		if ($module->name === 'bankwire') {
			return trim((string) Settings::get('BANKWIRE_IBAN')) !== ''
				|| trim((string) Settings::get('BANKWIRE_HOLDER')) !== '';
		}

		if (!method_exists($module, 'isConfigured')) {
			return true;
		}

		try {
			$ref = new ReflectionMethod($module, 'isConfigured');

			if ($ref->getNumberOfRequiredParameters() > 0) {
				return true;
			}

			$result = $ref->isStatic()
				? $ref->invoke(null)
				: $ref->invoke($module);

			return (bool) $result;
		} catch (Throwable $e) {
			return false;
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function seoStats(string $table, string $idColumn, string $langTable): array
	{
		$table = preg_replace('/[^a-z0-9_]/', '', $table) ?? '';
		$idColumn = preg_replace('/[^a-z0-9_]/', '', $idColumn) ?? '';
		$langTable = preg_replace('/[^a-z0-9_]/', '', $langTable) ?? '';
		$lang = preg_replace('/[^a-z0-9_-]/', '', (string) Lang::getDefault()) ?? 'tr';

		$empty = [
			'total' => 0,
			'empty' => 0,
			'incomplete' => 0,
			'ok' => 0,
			'score' => 0,
			'empty_title' => 0,
			'tone' => 'bad',
			'ok_pct' => 0,
			'incomplete_pct' => 0,
			'empty_pct' => 0,
		];

		if ($table === '' || $idColumn === '') {
			return $empty;
		}

		if (!self::tableExists($table)) {
			return $empty;
		}

		$sql = 'SELECT
				COUNT(*) AS total,
				SUM(CASE WHEN CHAR_LENGTH(meta_d) = 0 THEN 1 ELSE 0 END) AS empty_desc,
				SUM(CASE WHEN CHAR_LENGTH(meta_d) BETWEEN 1 AND ' . (self::META_OK_MIN - 1) . ' THEN 1 ELSE 0 END) AS short_desc,
				SUM(CASE WHEN CHAR_LENGTH(meta_d) >= ' . self::META_OK_MIN . ' THEN 1 ELSE 0 END) AS ok_desc,
				SUM(CASE WHEN CHAR_LENGTH(meta_t) = 0 THEN 1 ELSE 0 END) AS empty_title
			FROM (
				SELECT
					TRIM(COALESCE(NULLIF(pl.meta_description, \'\'), NULLIF(t.meta_description, \'\'), \'\')) AS meta_d,
					TRIM(COALESCE(NULLIF(pl.meta_title, \'\'), NULLIF(t.meta_title, \'\'), \'\')) AS meta_t
				FROM `' . $table . '` t
				LEFT JOIN `' . $langTable . '` pl ON pl.`' . $idColumn . '` = t.`' . $idColumn . '` AND pl.lang = ?
			) seo';

		if (!self::tableExists($langTable)) {
			$sql = 'SELECT
					COUNT(*) AS total,
					SUM(CASE WHEN CHAR_LENGTH(TRIM(COALESCE(meta_description, \'\'))) = 0 THEN 1 ELSE 0 END) AS empty_desc,
					SUM(CASE WHEN CHAR_LENGTH(TRIM(COALESCE(meta_description, \'\'))) BETWEEN 1 AND ' . (self::META_OK_MIN - 1) . ' THEN 1 ELSE 0 END) AS short_desc,
					SUM(CASE WHEN CHAR_LENGTH(TRIM(COALESCE(meta_description, \'\'))) >= ' . self::META_OK_MIN . ' THEN 1 ELSE 0 END) AS ok_desc,
					SUM(CASE WHEN CHAR_LENGTH(TRIM(COALESCE(meta_title, \'\'))) = 0 THEN 1 ELSE 0 END) AS empty_title
				FROM `' . $table . '`';
			$row = DB::execute($sql);
		} else {
			$row = DB::execute($sql, [$lang]);
		}

		if (!is_array($row) || !isset($row[0]) || !is_array($row[0])) {
			return $empty;
		}

		$stats = $row[0];
		$total = (int) ($stats['total'] ?? 0);
		$emptyDesc = (int) ($stats['empty_desc'] ?? 0);
		$shortDesc = (int) ($stats['short_desc'] ?? 0);
		$okDesc = (int) ($stats['ok_desc'] ?? 0);
		$score = 0;

		if ($total > 0) {
			$score = (int) round((($okDesc * 1.0) + ($shortDesc * 0.4)) / $total * 100);
		}

		$score = max(0, min(100, $score));

		return [
			'total' => $total,
			'empty' => $emptyDesc,
			'incomplete' => $shortDesc,
			'ok' => $okDesc,
			'empty_title' => (int) ($stats['empty_title'] ?? 0),
			'score' => $score,
			'tone' => self::tone($score),
			'ok_pct' => $total > 0 ? (int) round($okDesc / $total * 100) : 0,
			'incomplete_pct' => $total > 0 ? (int) round($shortDesc / $total * 100) : 0,
			'empty_pct' => $total > 0 ? (int) round($emptyDesc / $total * 100) : 0,
		];
	}

	private static function tableCount(string $table): int
	{
		$table = preg_replace('/[^a-z0-9_]/', '', $table) ?? '';

		if ($table === '' || !self::tableExists($table)) {
			return 0;
		}

		$row = DB::execute('SELECT COUNT(*) AS c FROM `' . $table . '`');

		if (!is_array($row) || !isset($row[0])) {
			return 0;
		}

		return (int) ($row[0]['c'] ?? 0);
	}

	private static function tableExists(string $table): bool
	{
		$found = DB::execute("SHOW TABLES LIKE '" . $table . "'");

		return !empty($found);
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private static function guideSteps(): array
	{
		return [
			[
				'n' => '1',
				'title' => 'Build the catalog',
				'text' => 'Create categories and brands first, then add products. Aim for at least 10 products with images, price and stock.',
				'url' => Admin::url('product'),
				'label' => 'Add product',
			],
			[
				'n' => '2',
				'title' => 'Set shipping',
				'text' => 'Add a carrier and price ranges (or free shipping). Checkout cannot finish without a shipping method.',
				'url' => Admin::url('cargos'),
				'label' => 'Shipping',
			],
			[
				'n' => '3',
				'title' => 'Enable payments',
				'text' => 'Start with Bank transfer (IBAN) or a card gateway such as iyzico / PayTR. The module must be installed, enabled and filled in.',
				'url' => Admin::url('modules'),
				'label' => 'Modules',
			],
			[
				'n' => '4',
				'title' => 'Hide the admin URL',
				'text' => 'In config/env.php set ADMIN_URI to a private slug (letters, numbers, dash). Bookmark the new address. The physical admin/ folder stays as-is.',
				'url' => Admin::url('welcome') . '#admin-url',
				'label' => 'See instructions',
			],
			[
				'n' => '5',
				'title' => 'Write SEO texts',
				'text' => 'Fill meta descriptions for products, categories and brands. Google shows about 120–160 characters. Empty or very short texts lower your SEO score.',
				'url' => Admin::url('products'),
				'label' => 'Products',
			],
			[
				'n' => '6',
				'title' => 'Open the store',
				'text' => 'Pick a theme, check Settings (store name, contact, currency) and place a test order.',
				'url' => Admin::url('templates'),
				'label' => 'Themes',
			],
		];
	}
}
