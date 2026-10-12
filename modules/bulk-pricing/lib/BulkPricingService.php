<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class BulkPricingService
{
	public const FIELD_PRICE = 'price';
	public const FIELD_COST = 'cost';
	public const FIELD_OLD_PRICE = 'old_price';

	/** @return array{id_category: int, id_brand: int, active: int, query: string, price_min: ?float, price_max: ?float} */
	public static function parseFilters(array $input): array
	{
		$active = -1;

		if (array_key_exists('active_filter', $input)) {
			$active = (int) $input['active_filter'];
		}

		return [
			'id_category' => max(0, (int) ($input['id_category'] ?? 0)),
			'id_brand' => max(0, (int) ($input['id_brand'] ?? 0)),
			'active' => $active,
			'query' => trim((string) ($input['q'] ?? '')),
			'price_min' => self::parseOptionalAmount($input['price_min'] ?? ''),
			'price_max' => self::parseOptionalAmount($input['price_max'] ?? ''),
		];
	}

	/** @return float|null */
	private static function parseOptionalAmount($raw): ?float
	{
		$raw = trim(str_replace(',', '.', (string) $raw));

		if ($raw === '') {
			return null;
		}

		if (!is_numeric($raw)) {
			return null;
		}

		return max(0.0, (float) $raw);
	}

	/**
	 * @return array{mode: string, direction: string, value: float, fields: string[], formula: string, margin_base: string, margins: array<int, array{min: float, max: ?float, percent: float}>}
	 */
	public static function parseAdjustment(array $input): array
	{
		$fields = [];

		foreach ([self::FIELD_PRICE, self::FIELD_COST, self::FIELD_OLD_PRICE] as $field) {
			if (!empty($input['field_' . $field])) {
				$fields[] = $field;
			}
		}

		$mode = (string) ($input['adjust_mode'] ?? 'percent');

		if (!in_array($mode, ['percent', 'fixed', 'formula'], true)) {
			$mode = 'percent';
		}

		$direction = (string) ($input['adjust_direction'] ?? 'increase');
		$direction = $direction === 'decrease' ? 'decrease' : 'increase';

		$value = (float) str_replace(',', '.', (string) ($input['adjust_value'] ?? '0'));
		$formula = trim((string) ($input['formula'] ?? ''));
		$marginBase = (string) ($input['rate_base'] ?? 'cost');
		$marginBase = $marginBase === 'price' ? 'price' : 'cost';

		if ($mode === 'formula' && $fields === []) {
			$fields = [self::FIELD_PRICE];
		}

		return [
			'mode' => $mode,
			'direction' => $direction,
			'value' => max(0, $value),
			'fields' => $fields,
			'formula' => $formula,
			'margin_base' => $marginBase,
			'margins' => self::parseMarginTiers($input),
		];
	}

	/**
	 * @return array<int, array{min: float, max: ?float, percent: float}>
	 */
	public static function parseMarginTiers(array $input): array
	{
		$mins = $input['rate_min'] ?? [];
		$maxs = $input['rate_max'] ?? [];
		$percents = $input['rate_percent'] ?? [];

		if (!is_array($mins)) {
			$mins = [$mins];
		}

		if (!is_array($maxs)) {
			$maxs = [$maxs];
		}

		if (!is_array($percents)) {
			$percents = [$percents];
		}

		$count = max(count($mins), count($maxs), count($percents));
		$rows = [];

		for ($i = 0; $i < $count; $i++) {
			$minRaw = trim(str_replace(',', '.', (string) ($mins[$i] ?? '')));
			$maxRaw = trim(str_replace(',', '.', (string) ($maxs[$i] ?? '')));
			$pctRaw = trim(str_replace(',', '.', (string) ($percents[$i] ?? '')));

			if ($minRaw === '' && $maxRaw === '' && $pctRaw === '') {
				continue;
			}

			if ($pctRaw === '' || !is_numeric($pctRaw)) {
				continue;
			}

			$min = $minRaw === '' || !is_numeric($minRaw) ? 0.0 : max(0.0, (float) $minRaw);
			$max = ($maxRaw === '' || !is_numeric($maxRaw)) ? null : max(0.0, (float) $maxRaw);
			$percent = max(0.0, (float) $pctRaw);

			if ($max !== null && $max < $min) {
				$tmp = $min;
				$min = $max;
				$max = $tmp;
			}

			$rows[] = [
				'min' => $min,
				'max' => $max,
				'percent' => $percent,
			];
		}

		return $rows;
	}

	public static function validateAdjustment(array $adjustment): ?string
	{
		if (($adjustment['mode'] ?? '') === 'formula') {
			$error = self::validateFormula((string) ($adjustment['formula'] ?? ''));

			if ($error !== null) {
				return $error;
			}

			if (self::formulaUsesVar((string) ($adjustment['formula'] ?? ''), 'k') && ($adjustment['margins'] ?? []) === []) {
				return 'Formülde k kullanıyorsanız en az bir kar oranı dilimi girin. Örn. 0–100 TL = %12.';
			}

			return null;
		}

		if ($adjustment['fields'] === []) {
			return 'En az bir fiyat alanı seçin (satış, alış veya eski fiyat).';
		}

		if ($adjustment['value'] <= 0) {
			return 'Geçerli bir oran veya tutar girin.';
		}

		if ($adjustment['mode'] === 'percent' && $adjustment['value'] > 100 && $adjustment['direction'] === 'decrease') {
			return 'Yüzde indirim en fazla %100 olabilir.';
		}

		return null;
	}

	public static function countMatching(array $filters): int
	{
		$params = [];
		$where = self::buildWhere($filters, $params);

		return (int) DB::getValue(
			'SELECT COUNT(*) FROM products p WHERE ' . $where,
			$params
		);
	}

	/** @return array<int, array<string, mixed>> */
	public static function fetchMatching(array $filters, int $limit = 0, int $offset = 0): array
	{
		$params = [];
		$where = self::buildWhere($filters, $params);
		$sql = 'SELECT p.id_product, p.product_name, p.stock_code, p.barcode, p.price, p.cost, p.old_price,
				p.doviz, p.doviz_price, p.doviz_cost, p.doviz_old_price,
				b.brand_name, c.category_name
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			WHERE ' . $where . '
			ORDER BY p.id_product DESC';

		if ($limit > 0) {
			$sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, (int) $offset);
		}

		return DB::execute($sql, $params) ?: [];
	}

	/** @return array<int, array<string, mixed>> */
	public static function buildPreviewRows(array $filters, array $adjustment, int $limit = 8): array
	{
		$rows = self::fetchMatching($filters, $limit);
		$preview = [];

		foreach ($rows as $row) {
			$changes = self::buildChanges($row, $adjustment);
			$margin = self::describeMarginForProduct($row, $adjustment);
			$preview[] = array_merge($row, [
				'changes' => $changes,
				'has_change' => $changes !== [],
				'margin_percent' => $margin['percent'],
				'margin_k' => $margin['k'],
			]);
		}

		return $preview;
	}

	/** @return array{updated: int, skipped: int} */
	public static function apply(array $filters, array $adjustment): array
	{
		$products = self::fetchMatching($filters);
		$updated = 0;
		$skipped = 0;
		$shopCurrency = strtolower(trim(Currency::getShopCurrency()));

		foreach ($products as $product) {
			$id = (int) ($product['id_product'] ?? 0);

			if ($id <= 0) {
				$skipped++;
				continue;
			}

			$payload = self::buildUpdatePayload($product, $adjustment, $shopCurrency);

			if ($payload === []) {
				$skipped++;
				continue;
			}

			if (DB::update('products', $payload, 'id_product = :where_id', ['where_id' => $id]) === false) {
				$skipped++;
				continue;
			}

			$updated++;

			if (class_exists('Module', false)) {
				Module::runHook('product.updated', [$id, Product::getByIdAdmin($id) ?: $product, false]);
			}
		}

		return ['updated' => $updated, 'skipped' => $skipped];
	}

	public static function formatAdjustmentLabel(array $adjustment): string
	{
		if (($adjustment['mode'] ?? '') === 'formula') {
			$label = 'Satış fiyatı · formül ' . (string) ($adjustment['formula'] ?? '');

			if (self::formulaUsesVar((string) ($adjustment['formula'] ?? ''), 'k')) {
				$label .= ' · k = 1 + kar%/100';
			}

			return $label;
		}

		$fieldLabels = [
			self::FIELD_PRICE => 'Satış fiyatı',
			self::FIELD_COST => 'Alış fiyatı',
			self::FIELD_OLD_PRICE => 'Eski fiyat',
		];

		$fields = [];

		foreach ($adjustment['fields'] as $field) {
			$fields[] = $fieldLabels[$field] ?? $field;
		}

		$action = $adjustment['direction'] === 'decrease' ? 'indirim' : 'zam';

		if ($adjustment['mode'] === 'percent') {
			$detail = '%' . number_format($adjustment['value'], 2, ',', '.') . ' ' . $action;
		} else {
			$detail = number_format($adjustment['value'], 2, ',', '.') . ' TL ' . $action;
		}

		return implode(', ', $fields) . ' · ' . $detail;
	}

	/** @return array<string, array{before: float, after: float}> */
	private static function buildChanges(array $product, array $adjustment): array
	{
		$changes = [];
		$shopCurrency = strtolower(trim(Currency::getShopCurrency()));
		$payload = self::buildUpdatePayload($product, $adjustment, $shopCurrency);

		foreach ($adjustment['fields'] as $field) {
			if (!array_key_exists($field, $payload)) {
				continue;
			}

			$changes[$field] = [
				'before' => (float) ($product[$field] ?? 0),
				'after' => (float) $payload[$field],
			];
		}

		return $changes;
	}

	/** @return array<string, float> */
	private static function buildUpdatePayload(array $product, array $adjustment, string $shopCurrency): array
	{
		if (($adjustment['mode'] ?? '') === 'formula') {
			return self::buildFormulaPayload($product, $adjustment, $shopCurrency);
		}

		$payload = [];
		$productCurrency = strtolower(trim((string) ($product['doviz'] ?? $shopCurrency)));
		$isFx = $productCurrency !== '' && $productCurrency !== $shopCurrency;

		foreach ($adjustment['fields'] as $field) {
			$current = (float) ($product[$field] ?? 0);
			$newValue = self::adjustValue($current, $adjustment);

			if (abs($newValue - $current) < 0.00001) {
				continue;
			}

			$payload[$field] = $newValue;

			if (!$isFx) {
				continue;
			}

			$fxField = self::mapFxField($field);

			if ($fxField === null) {
				continue;
			}

			$fxCurrent = (float) ($product[$fxField] ?? 0);

			if ($fxCurrent > 0) {
				$payload[$fxField] = self::adjustValue($fxCurrent, $adjustment);
			}
		}

		return $payload;
	}

	private static function mapFxField(string $field): ?string
	{
		$map = [
			self::FIELD_PRICE => 'doviz_price',
			self::FIELD_COST => 'doviz_cost',
			self::FIELD_OLD_PRICE => 'doviz_old_price',
		];

		return $map[$field] ?? null;
	}

	private static function adjustValue(float $current, array $adjustment): float
	{
		$current = max(0, $current);

		if ($adjustment['mode'] === 'percent') {
			$factor = (float) $adjustment['value'] / 100;

			if ($adjustment['direction'] === 'decrease') {
				$current *= max(0, 1 - $factor);
			} else {
				$current *= 1 + $factor;
			}
		} else {
			$delta = (float) $adjustment['value'];

			if ($adjustment['direction'] === 'decrease') {
				$current -= $delta;
			} else {
				$current += $delta;
			}
		}

		return max(0, round($current, 2));
	}

	public static function formulaUsesVar(string $formula, string $var): bool
	{
		$normalized = self::normalizeFormula($formula);

		return $var !== '' && strpos($normalized, $var) !== false;
	}

	/**
	 * %12 → 1.12
	 */
	public static function percentToMultiplier(float $percent): float
	{
		return 1.0 + ($percent / 100.0);
	}

	/**
	 * @param array<string, mixed> $product
	 * @param array<string, mixed> $adjustment
	 */
	public static function resolveMarginK(array $product, array $adjustment): ?float
	{
		$info = self::describeMarginForProduct($product, $adjustment);

		return $info['k'];
	}

	/**
	 * @param array<string, mixed> $product
	 * @param array<string, mixed> $adjustment
	 * @return array{percent: ?float, k: ?float}
	 */
	public static function describeMarginForProduct(array $product, array $adjustment): array
	{
		if (!self::formulaUsesVar((string) ($adjustment['formula'] ?? ''), 'k')) {
			return ['percent' => null, 'k' => null];
		}

		$baseField = (($adjustment['margin_base'] ?? 'cost') === 'price') ? 'price' : 'cost';
		$amount = (float) ($product[$baseField] ?? 0);

		foreach ($adjustment['margins'] ?? [] as $row) {
			$min = (float) ($row['min'] ?? 0);
			$max = $row['max'] ?? null;
			$percent = (float) ($row['percent'] ?? 0);

			if ($amount + 0.0000001 < $min) {
				continue;
			}

			if ($max !== null && $amount > ((float) $max) + 0.0000001) {
				continue;
			}

			return [
				'percent' => $percent,
				'k' => self::percentToMultiplier($percent),
			];
		}

		return ['percent' => null, 'k' => null];
	}

	/** @return array<string, float> */
	private static function buildFormulaPayload(array $product, array $adjustment, string $shopCurrency): array
	{
		$cost = (float) ($product['cost'] ?? 0);
		$sale = (float) ($product['price'] ?? 0);
		$formula = (string) ($adjustment['formula'] ?? '');
		$normalized = self::normalizeFormula($formula);

		if (strpos($normalized, 'a') !== false && $cost <= 0) {
			return [];
		}

		$k = 1.0;

		if (strpos($normalized, 'k') !== false) {
			$resolved = self::resolveMarginK($product, $adjustment);

			if ($resolved === null) {
				return [];
			}

			$k = $resolved;
		}

		$evaluated = self::evaluateFormula($formula, $cost, $sale, $k);

		if (empty($evaluated['ok'])) {
			return [];
		}

		$newValue = (float) $evaluated['value'];
		$current = $sale;

		if (abs($newValue - $current) < 0.00001) {
			return [];
		}

		$payload = [self::FIELD_PRICE => $newValue];
		$productCurrency = strtolower(trim((string) ($product['doviz'] ?? $shopCurrency)));
		$isFx = $productCurrency !== '' && $productCurrency !== $shopCurrency;

		if ($isFx) {
			$fxCurrent = (float) ($product['doviz_price'] ?? 0);

			if ($current > 0 && $fxCurrent > 0) {
				$payload['doviz_price'] = max(0, round($fxCurrent * ($newValue / $current), 4));
			}
		}

		return $payload;
	}

	public static function validateFormula(string $formula): ?string
	{
		$formula = trim($formula);

		if ($formula === '') {
			return 'Formül yazın. Örn. (a+75)/0.95 veya (s+75)*1.5';
		}

		$normalized = self::normalizeFormula($formula);

		if ($normalized === '') {
			return 'Formül boş.';
		}

		if (!preg_match('/[ask]/', $normalized)) {
			return 'Formülde a (alış), s (satış) veya k (kar çarpanı) kullanın.';
		}

		if (preg_match('/[^0-9ask+\-*\/().]/', $normalized)) {
			return 'Formülde yalnızca sayılar, a, s, k ve + - * / ( ) kullanılabilir.';
		}

		$test = self::evaluateFormula($formula, 100.0, 200.0, 1.12);

		if (empty($test['ok'])) {
			return (string) ($test['message'] ?? 'Formül geçersiz.');
		}

		return null;
	}

	/**
	 * @return array{ok: bool, value?: float, message?: string}
	 */
	public static function evaluateFormula(string $formula, float $cost, float $sale, float $k = 1.0): array
	{
		$normalized = self::normalizeFormula($formula);

		if ($normalized === '') {
			return ['ok' => false, 'message' => 'Formül boş.'];
		}

		if (preg_match('/[^0-9ask+\-*\/().]/', $normalized)) {
			return ['ok' => false, 'message' => 'Formülde geçersiz karakter var.'];
		}

		try {
			$tokens = self::tokenizeFormula($normalized);
			$parser = ['tokens' => $tokens, 'pos' => 0, 'a' => $cost, 's' => $sale, 'k' => $k];
			$value = self::parseFormulaExpr($parser);

			if ($parser['pos'] < count($parser['tokens'])) {
				return ['ok' => false, 'message' => 'Formül tam okunamadı. Operatörleri kontrol edin.'];
			}

			if (!is_finite($value)) {
				return ['ok' => false, 'message' => 'Formül sonucu geçersiz (sıfıra bölme vb.).'];
			}

			if ($value < 0) {
				return ['ok' => false, 'message' => 'Formül sonucu negatif olamaz.'];
			}

			return ['ok' => true, 'value' => round($value, 2)];
		} catch (\RuntimeException $e) {
			return ['ok' => false, 'message' => $e->getMessage()];
		}
	}

	private static function normalizeFormula(string $formula): string
	{
		$formula = strtolower(trim($formula));
		$formula = str_replace(',', '.', $formula);
		$formula = preg_replace('/\s+/', '', $formula) ?? '';
		$formula = preg_replace('/(\d|\.)(a|s|k|\()/', '$1*$2', $formula) ?? $formula;
		$formula = preg_replace('/(a|s|k)(\()/', '$1*$2', $formula) ?? $formula;
		$formula = preg_replace('/(\))(a|s|k|\d|\()/', '$1*$2', $formula) ?? $formula;
		$formula = preg_replace('/(a|s|k)(a|s|k)/', '$1*$2', $formula) ?? $formula;

		return $formula;
	}

	/**
	 * @return array<int, array{type: string, value: string}>
	 */
	private static function tokenizeFormula(string $formula): array
	{
		$tokens = [];
		$length = strlen($formula);
		$i = 0;

		while ($i < $length) {
			$ch = $formula[$i];

			if ($ch === 'a' || $ch === 's' || $ch === 'k') {
				$tokens[] = ['type' => 'var', 'value' => $ch];
				$i++;
				continue;
			}

			if ($ch === '+' || $ch === '-' || $ch === '*' || $ch === '/' || $ch === '(' || $ch === ')') {
				$tokens[] = ['type' => 'op', 'value' => $ch];
				$i++;
				continue;
			}

			if (ctype_digit($ch) || $ch === '.') {
				$num = '';
				$dots = 0;

				while ($i < $length && (ctype_digit($formula[$i]) || $formula[$i] === '.')) {
					if ($formula[$i] === '.') {
						$dots++;

						if ($dots > 1) {
							throw new \RuntimeException('Sayıda birden fazla nokta var.');
						}
					}

					$num .= $formula[$i];
					$i++;
				}

				if ($num === '.' || $num === '') {
					throw new \RuntimeException('Geçersiz sayı.');
				}

				$tokens[] = ['type' => 'num', 'value' => $num];
				continue;
			}

			throw new \RuntimeException('Beklenmeyen karakter: ' . $ch);
		}

		return $tokens;
	}

	/**
	 * @param array{tokens: array<int, array{type: string, value: string}>, pos: int, a: float, s: float} $parser
	 */
	private static function parseFormulaExpr(array &$parser): float
	{
		$left = self::parseFormulaTerm($parser);

		while (self::formulaOpIs($parser, '+') || self::formulaOpIs($parser, '-')) {
			$op = self::consumeFormulaOp($parser);
			$right = self::parseFormulaTerm($parser);
			$left = $op === '+' ? ($left + $right) : ($left - $right);
		}

		return $left;
	}

	/**
	 * @param array{tokens: array<int, array{type: string, value: string}>, pos: int, a: float, s: float} $parser
	 */
	private static function parseFormulaTerm(array &$parser): float
	{
		$left = self::parseFormulaFactor($parser);

		while (self::formulaOpIs($parser, '*') || self::formulaOpIs($parser, '/')) {
			$op = self::consumeFormulaOp($parser);
			$right = self::parseFormulaFactor($parser);

			if ($op === '/') {
				if (abs($right) < 1e-12) {
					throw new \RuntimeException('Sıfıra bölme.');
				}

				$left = $left / $right;
			} else {
				$left = $left * $right;
			}
		}

		return $left;
	}

	/**
	 * @param array{tokens: array<int, array{type: string, value: string}>, pos: int, a: float, s: float} $parser
	 */
	private static function parseFormulaFactor(array &$parser): float
	{
		if (self::formulaOpIs($parser, '+')) {
			self::consumeFormulaOp($parser);

			return self::parseFormulaFactor($parser);
		}

		if (self::formulaOpIs($parser, '-')) {
			self::consumeFormulaOp($parser);

			return -1 * self::parseFormulaFactor($parser);
		}

		$token = self::peekFormulaToken($parser);

		if ($token === null) {
			throw new \RuntimeException('Formül eksik.');
		}

		if ($token['type'] === 'num') {
			$parser['pos']++;

			return (float) $token['value'];
		}

		if ($token['type'] === 'var') {
			$parser['pos']++;

			if ($token['value'] === 'a') {
				return (float) $parser['a'];
			}

			if ($token['value'] === 's') {
				return (float) $parser['s'];
			}

			return (float) ($parser['k'] ?? 1.0);
		}

		if ($token['type'] === 'op' && $token['value'] === '(') {
			$parser['pos']++;
			$value = self::parseFormulaExpr($parser);

			if (!self::formulaOpIs($parser, ')')) {
				throw new \RuntimeException('Kapanmayan parantez.');
			}

			$parser['pos']++;

			return $value;
		}

		throw new \RuntimeException('Formül anlaşılamadı.');
	}

	/**
	 * @param array{tokens: array<int, array{type: string, value: string}>, pos: int} $parser
	 * @return array{type: string, value: string}|null
	 */
	private static function peekFormulaToken(array $parser): ?array
	{
		return $parser['tokens'][$parser['pos']] ?? null;
	}

	/**
	 * @param array{tokens: array<int, array{type: string, value: string}>, pos: int} $parser
	 */
	private static function formulaOpIs(array $parser, string $op): bool
	{
		$token = self::peekFormulaToken($parser);

		return $token !== null && $token['type'] === 'op' && $token['value'] === $op;
	}

	/**
	 * @param array{tokens: array<int, array{type: string, value: string}>, pos: int} $parser
	 */
	private static function consumeFormulaOp(array &$parser): string
	{
		$token = self::peekFormulaToken($parser);

		if ($token === null || $token['type'] !== 'op') {
			throw new \RuntimeException('Operatör bekleniyordu.');
		}

		$parser['pos']++;

		return $token['value'];
	}

	private static function buildWhere(array $filters, array &$params): string
	{
		$sql = '1=1';

		if ($filters['id_category'] > 0) {
			$sql .= ' AND p.id_category = ?';
			$params[] = $filters['id_category'];
		}

		if ($filters['id_brand'] > 0) {
			$sql .= ' AND p.id_brand = ?';
			$params[] = $filters['id_brand'];
		}

		if ($filters['active'] >= 0) {
			$sql .= ' AND p.active = ?';
			$params[] = $filters['active'];
		}

		if (isset($filters['price_min']) && $filters['price_min'] !== null) {
			$sql .= ' AND p.price >= ?';
			$params[] = (float) $filters['price_min'];
		}

		if (isset($filters['price_max']) && $filters['price_max'] !== null) {
			$sql .= ' AND p.price <= ?';
			$params[] = (float) $filters['price_max'];
		}

		if ($filters['query'] !== '') {
			$like = '%' . $filters['query'] . '%';
			$sql .= ' AND (p.product_name LIKE ? OR p.stock_code LIKE ? OR p.barcode LIKE ?)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		return $sql;
	}

	/** @return array<int, array{id_category: int, category_name: string}> */
	public static function getCategoryOptions(): array
	{
		$rows = DB::execute(
			'SELECT id_category, category_name FROM categories WHERE active = 1 ORDER BY category_name ASC'
		) ?: [];

		return array_map(static function (array $row): array {
			$row = Lang::applyCategory($row);

			return [
				'id_category' => (int) ($row['id_category'] ?? 0),
				'category_name' => (string) ($row['category_name'] ?? ''),
			];
		}, $rows);
	}

	/**
	 * Filtered product price list as .xlsx download (exits).
	 *
	 * @param array{id_category: int, id_brand: int, active: int, query: string, price_min: ?float, price_max: ?float} $filters
	 */
	public static function exportPricingExcel(array $filters): void
	{
		self::loadXlsxLibs();

		$products = self::fetchMatching($filters);
		$books = [[
			'<b>ID</b>',
			'<b>Stok Kodu</b>',
			'<b>Barkod</b>',
			'<b>Ürün Adı</b>',
			'<b>Satış Fiyatı</b>',
			'<b>Alış Fiyatı</b>',
			'<b>Eski Fiyat</b>',
			'<b>Kategori</b>',
			'<b>Marka</b>',
		]];

		foreach ($products as $row) {
			$books[] = [
				(int) ($row['id_product'] ?? 0),
				(string) ($row['stock_code'] ?? ''),
				(string) ($row['barcode'] ?? ''),
				(string) ($row['product_name'] ?? ''),
				(float) ($row['price'] ?? 0),
				(float) ($row['cost'] ?? 0),
				(float) ($row['old_price'] ?? 0),
				(string) ($row['category_name'] ?? ''),
				(string) ($row['brand_name'] ?? ''),
			];
		}

		$name = 'toplu-fiyat-' . date('Ymd-His') . '.xlsx';
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Cache-Control: max-age=0');
		SimpleXLSXGen::fromArray($books)->downloadAs($name);
		exit;
	}

	/**
	 * @param array<string, mixed> $file $_FILES entry
	 * @return array{success: bool, message: string, updated?: int, skipped?: int, errors?: string[]}
	 */
	public static function importPricingFromUpload(array $file): array
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return ['success' => false, 'message' => 'Excel dosyası seçin.'];
		}

		$ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

		if ($ext !== 'xlsx') {
			return ['success' => false, 'message' => 'Yalnızca .xlsx dosyaları kabul edilir.'];
		}

		self::loadXlsxLibs();

		$xlsx = SimpleXLSX::parse($file['tmp_name']);

		if (!$xlsx) {
			return ['success' => false, 'message' => 'Excel okunamadı: ' . SimpleXLSX::parseError()];
		}

		return self::importPricingFromRows($xlsx->rows());
	}

	/**
	 * @param array<int, array<int, mixed>> $rows
	 * @return array{success: bool, message: string, updated?: int, skipped?: int, errors?: string[]}
	 */
	public static function importPricingFromRows(array $rows): array
	{
		if (count($rows) < 2) {
			return ['success' => false, 'message' => 'Excel dosyasında veri satırı yok.'];
		}

		$headers = array_map([self::class, 'normalizeHeader'], $rows[0]);
		$map = self::buildExcelColumnMap($headers);

		if (!isset($map['id_product']) && !isset($map['stock_code']) && !isset($map['barcode'])) {
			return ['success' => false, 'message' => 'Excel’de ID, Stok Kodu veya Barkod sütunu bulunamadı.'];
		}

		if (!isset($map['price']) && !isset($map['cost']) && !isset($map['old_price'])) {
			return ['success' => false, 'message' => 'Excel’de Satış Fiyatı, Alış Fiyatı veya Eski Fiyat sütunu bulunamadı.'];
		}

		$updated = 0;
		$skipped = 0;
		$errors = [];
		$shopCurrency = strtolower(trim(Currency::getShopCurrency()));

		for ($i = 1, $count = count($rows); $i < $count; $i++) {
			$row = $rows[$i];
			$lineNo = $i + 1;

			if (self::isExcelRowEmpty($row, $map)) {
				continue;
			}

			$result = self::importPricingRow($row, $map, $lineNo, $shopCurrency);

			if (!empty($result['success'])) {
				$updated++;
				continue;
			}

			if (!empty($result['skipped'])) {
				$skipped++;
				continue;
			}

			$skipped++;
			$errors[] = (string) ($result['message'] ?? ('Satır ' . $lineNo . ': hata'));
		}

		if ($updated === 0 && $errors !== []) {
			return [
				'success' => false,
				'message' => implode(' ', array_slice($errors, 0, 3)),
				'updated' => 0,
				'skipped' => $skipped,
				'errors' => $errors,
			];
		}

		$message = $updated . ' ürün fiyatı güncellendi';

		if ($skipped > 0) {
			$message .= ', ' . $skipped . ' satır atlandı';
		}

		if ($errors !== []) {
			$message .= '. Hatalar: ' . implode('; ', array_slice($errors, 0, 5));

			if (count($errors) > 5) {
				$message .= ' (+' . (count($errors) - 5) . ' hata daha)';
			}
		}

		return [
			'success' => true,
			'message' => $message,
			'updated' => $updated,
			'skipped' => $skipped,
			'errors' => $errors,
		];
	}

	/**
	 * @param array<int, mixed> $row
	 * @param array<string, int> $map
	 * @return array{success?: bool, skipped?: bool, message?: string}
	 */
	private static function importPricingRow(array $row, array $map, int $lineNo, string $shopCurrency): array
	{
		$id = self::resolveProductIdFromExcelRow($row, $map);

		if ($id <= 0) {
			return ['message' => 'Satır ' . $lineNo . ': ürün bulunamadı (ID / stok kodu / barkod).'];
		}

		$product = DB::getRowSafe(
			'products',
			'id_product = ?',
			[$id]
		);

		if (!$product) {
			return ['message' => 'Satır ' . $lineNo . ': ürün #' . $id . ' bulunamadı.'];
		}

		$payload = [];

		foreach ([self::FIELD_PRICE, self::FIELD_COST, self::FIELD_OLD_PRICE] as $field) {
			if (!isset($map[$field])) {
				continue;
			}

			$raw = self::excelCell($row, $map[$field]);

			if ($raw === null || $raw === '') {
				continue;
			}

			if (!is_numeric(str_replace(',', '.', (string) $raw))) {
				return ['message' => 'Satır ' . $lineNo . ': geçersiz ' . $field . ' değeri.'];
			}

			$newValue = max(0, round((float) str_replace(',', '.', (string) $raw), 2));
			$current = (float) ($product[$field] ?? 0);

			if (abs($newValue - $current) < 0.00001) {
				continue;
			}

			$payload[$field] = $newValue;

			$productCurrency = strtolower(trim((string) ($product['doviz'] ?? $shopCurrency)));
			$isFx = $productCurrency !== '' && $productCurrency !== $shopCurrency;

			if (!$isFx) {
				continue;
			}

			$fxField = self::mapFxField($field);

			if ($fxField === null) {
				continue;
			}

			$fxCurrent = (float) ($product[$fxField] ?? 0);

			if ($current > 0 && $fxCurrent > 0) {
				$payload[$fxField] = max(0, round($fxCurrent * ($newValue / $current), 4));
			}
		}

		if ($payload === []) {
			return ['skipped' => true];
		}

		if (DB::update('products', $payload, 'id_product = :where_id', ['where_id' => $id]) === false) {
			return ['message' => 'Satır ' . $lineNo . ': güncelleme başarısız.'];
		}

		if (class_exists('Module', false)) {
			Module::runHook('product.updated', [$id, Product::getByIdAdmin($id) ?: array_merge($product, $payload), false]);
		}

		return ['success' => true];
	}

	/**
	 * @param array<int, mixed> $row
	 * @param array<string, int> $map
	 */
	private static function resolveProductIdFromExcelRow(array $row, array $map): int
	{
		if (isset($map['id_product'])) {
			$id = (int) preg_replace('/\D+/', '', (string) self::excelCell($row, $map['id_product']));

			if ($id > 0) {
				$exists = (int) DB::getValue('SELECT id_product FROM products WHERE id_product = ?', [$id]);

				if ($exists > 0) {
					return $exists;
				}
			}
		}

		if (isset($map['stock_code'])) {
			$stockCode = trim((string) self::excelCell($row, $map['stock_code']));

			if ($stockCode !== '') {
				$id = (int) DB::getValue('SELECT id_product FROM products WHERE stock_code = ? LIMIT 1', [$stockCode]);

				if ($id > 0) {
					return $id;
				}
			}
		}

		if (isset($map['barcode'])) {
			$barcode = trim((string) self::excelCell($row, $map['barcode']));

			if ($barcode !== '') {
				$id = (int) DB::getValue('SELECT id_product FROM products WHERE barcode = ? LIMIT 1', [$barcode]);

				if ($id > 0) {
					return $id;
				}
			}
		}

		return 0;
	}

	/**
	 * @param array<int, mixed> $row
	 * @param array<string, int> $map
	 */
	private static function isExcelRowEmpty(array $row, array $map): bool
	{
		foreach ($map as $index) {
			$val = self::excelCell($row, $index);

			if ($val !== null && trim((string) $val) !== '') {
				return false;
			}
		}

		return true;
	}

	/** @param array<int, mixed> $row */
	private static function excelCell(array $row, int $index)
	{
		return array_key_exists($index, $row) ? $row[$index] : null;
	}

	private static function normalizeHeader($header): string
	{
		$header = strip_tags((string) $header);
		$header = html_entity_decode($header, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$header = strtolower(trim($header));
		$header = preg_replace('/\s+/', ' ', $header);

		return $header ?? '';
	}

	/**
	 * @param array<int, string> $headers
	 * @return array<string, int>
	 */
	private static function buildExcelColumnMap(array $headers): array
	{
		$aliases = [
			'id_product' => ['id', 'product id', 'ürün id', 'urun id', 'id_product'],
			'stock_code' => ['stock code', 'stok kodu', 'sku', 'stok kod'],
			'barcode' => ['barcode', 'barkod'],
			'product_name' => ['product name', 'ürün adı', 'urun adi', 'ürün adi'],
			'price' => ['price', 'fiyat', 'satış fiyatı', 'satis fiyati', 'satış', 'satis'],
			'cost' => ['cost', 'alış', 'alis', 'alış fiyatı', 'alis fiyati', 'maliyet'],
			'old_price' => ['old price', 'eski fiyat', 'eski', 'liste fiyatı', 'liste fiyati'],
		];

		$map = [];

		foreach ($headers as $index => $header) {
			if ($header === '') {
				continue;
			}

			foreach ($aliases as $field => $names) {
				if (in_array($header, $names, true) && !isset($map[$field])) {
					$map[$field] = (int) $index;
					break;
				}
			}
		}

		return $map;
	}

	private static function loadXlsxLibs(): void
	{
		$root = dirname(__DIR__, 3);

		if (!class_exists('SimpleXLSX', false)) {
			require_once $root . '/libs/SimpleXLSX.php';
		}

		if (!class_exists('SimpleXLSXGen', false)) {
			require_once $root . '/libs/SimpleGEN.php';
		}
	}
}
