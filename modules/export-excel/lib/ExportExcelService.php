<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class ExportExcelService
{
	/**
	 * @param array<string, mixed> $filters
	 */
	public static function exportProducts(string $query = '', int $idCategory = 0, int $idBrand = 0, int $activeFilter = -1, array $filters = []): void
	{
		self::loadXlsxLibs();

		$total = Product::countAdmin($query, $idCategory, $idBrand, $activeFilter, 0, $filters);
		$books = [self::productCatalogHeaders()];

		$exportProducts = Product::getAdminList($query, $idCategory, $idBrand, $activeFilter, max(1, $total), 0, 0, 'p.id_product DESC', $filters);
		$exportLang = Lang::getDefault();
		$productIds = [];

		foreach ($exportProducts as $gd) {
			$productIds[] = (int) ($gd['id_product'] ?? 0);
		}

		$variationsMap = ProductVariation::getMapByProductIds($productIds);

		foreach ($exportProducts as $gd) {
			$gd = Lang::applyProductForLang($gd, $exportLang);
			$idProduct = (int) ($gd['id_product'] ?? 0);
			$books[] = self::productCatalogRow($gd, 'product');

			foreach ($variationsMap[$idProduct] ?? [] as $variation) {
				$books[] = self::variationCatalogRow($gd, $variation);
			}
		}

		$name = 'product-list.xlsx';
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		SimpleXLSXGen::fromArray($books)->downloadAs($name);
		exit;
	}

	/** @return array{success:bool,message:string} */
	public static function importProductsFromUpload(array $file): array
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return ['success' => false, 'message' => adminT('Select an Excel file')];
		}

		$ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

		if ($ext !== 'xlsx') {
			return ['success' => false, 'message' => adminT('Only .xlsx files are allowed')];
		}

		self::loadXlsxLibs();

		$xlsx = SimpleXLSX::parse($file['tmp_name']);

		if (!$xlsx) {
			return ['success' => false, 'message' => 'Excel okunamadı: ' . SimpleXLSX::parseError()];
		}

		return Product::importFromExcel($xlsx->rows());
	}

	/**
	 * @param array<string, mixed> $filters
	 */
	public static function exportPriceStock(string $query = '', int $idCategory = 0, int $idBrand = 0, int $activeFilter = -1, array $filters = []): void
	{
		self::loadXlsxLibs();

		$total = Product::countAdmin($query, $idCategory, $idBrand, $activeFilter, 0, $filters);
		$exportProducts = Product::getAdminList($query, $idCategory, $idBrand, $activeFilter, max(1, $total), 0, 0, 'p.id_product DESC', $filters);
		$exportLang = Lang::getDefault();
		$productIds = [];

		foreach ($exportProducts as $gd) {
			$productIds[] = (int) ($gd['id_product'] ?? 0);
		}

		$variationsMap = ProductVariation::getMapByProductIds($productIds);
		$books = [[
			'<b>Product ID</b>',
			'<b>Variation ID</b>',
			'<b>Type</b>',
			'<b>Product Name</b>',
			'<b>Variation</b>',
			'<b>Stock Code</b>',
			'<b>Barcode</b>',
			'<b>Price</b>',
			'<b>Cost</b>',
			'<b>Old Price</b>',
			'<b>Stock</b>',
		]];

		foreach ($exportProducts as $gd) {
			$gd = Lang::applyProductForLang($gd, $exportLang);
			$idProduct = (int) ($gd['id_product'] ?? 0);
			$variations = $variationsMap[$idProduct] ?? [];
			$basePrice = (float) ($gd['price'] ?? 0);

			$cost = (float) ($gd['cost'] ?? 0);

			if ($variations === []) {
				$books[] = [
					$idProduct,
					0,
					'product',
					(string) ($gd['product_name'] ?? ''),
					'',
					(string) ($gd['stock_code'] ?? ''),
					(string) ($gd['barcode'] ?? ''),
					$basePrice,
					$cost,
					(float) ($gd['old_price'] ?? 0),
					(float) ($gd['stock'] ?? 0),
				];
				continue;
			}

			$books[] = [
				$idProduct,
				0,
				'product',
				(string) ($gd['product_name'] ?? ''),
				'',
				(string) ($gd['stock_code'] ?? ''),
				(string) ($gd['barcode'] ?? ''),
				$basePrice,
				$cost,
				(float) ($gd['old_price'] ?? 0),
				'',
			];

			foreach ($variations as $variation) {
				$books[] = [
					$idProduct,
					(int) ($variation['id_variation'] ?? 0),
					'variation',
					(string) ($gd['product_name'] ?? ''),
					ProductVariation::formatLabel($variation),
					(string) ($variation['sku'] ?? ''),
					(string) ($variation['barcode'] ?? ''),
					ProductVariation::getEffectivePrice($variation, $basePrice),
					'',
					'',
					(float) ($variation['stock'] ?? 0),
				];
			}
		}

		$name = 'product-price-stock.xlsx';
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		SimpleXLSXGen::fromArray($books)->downloadAs($name);
		exit;
	}

	/** @return array{success:bool,message:string} */
	public static function importPriceStockFromUpload(array $file): array
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return ['success' => false, 'message' => adminT('Select an Excel file')];
		}

		$ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

		if ($ext !== 'xlsx') {
			return ['success' => false, 'message' => adminT('Only .xlsx files are allowed')];
		}

		self::loadXlsxLibs();

		$xlsx = SimpleXLSX::parse($file['tmp_name']);

		if (!$xlsx) {
			return ['success' => false, 'message' => 'Excel okunamadı: ' . SimpleXLSX::parseError()];
		}

		return self::importPriceStockFromRows($xlsx->rows());
	}

	/**
	 * @param array<int, array<int, mixed>> $rows
	 * @return array{success:bool,message:string}
	 */
	public static function importPriceStockFromRows(array $rows): array
	{
		if (count($rows) < 2) {
			return ['success' => false, 'message' => adminT('Excel file has no data rows')];
		}

		$headers = [];

		foreach ($rows[0] as $header) {
			$headers[] = self::normalizeHeader($header);
		}

		$map = self::buildPriceStockColumnMap($headers);

		if (!isset($map['price']) && !isset($map['old_price']) && !isset($map['stock']) && !isset($map['cost'])) {
			return ['success' => false, 'message' => adminT('Price, cost or stock column was not found')];
		}

		$updated = 0;
		$skipped = 0;
		$errors = [];

		for ($i = 1, $count = count($rows); $i < $count; $i++) {
			if (self::isRowEmpty($rows[$i])) {
				continue;
			}

			$result = self::importPriceStockRow($rows[$i], $map, $i + 1);

			if (!empty($result['success'])) {
				if (!empty($result['unchanged'])) {
					$skipped++;
				} else {
					$updated++;
				}
				continue;
			}

			$errors[] = (string) ($result['message'] ?? ('Satır ' . ($i + 1)));
		}

		if ($updated === 0 && $errors !== []) {
			return ['success' => false, 'message' => implode(' ', array_slice($errors, 0, 3))];
		}

		$message = $updated . ' ' . adminT('price/stock rows updated');

		if ($skipped > 0) {
			$message .= ', ' . $skipped . ' ' . adminT('unchanged');
		}

		if ($errors) {
			$message .= '. ' . adminT('Errors') . ': ' . implode('; ', array_slice($errors, 0, 5));

			if (count($errors) > 5) {
				$message .= ' (+' . (count($errors) - 5) . ')';
			}
		}

		return ['success' => true, 'message' => $message];
	}

	public static function exportOrders(int $status = 0, array $filters = []): void
	{
		self::loadXlsxLibs();

		$filters = Order::normalizeAdminFilters($filters);
		$total = Order::countAdmin($status, $filters['date_from'], $filters['date_to'], $filters);
		$rows = Order::enrichAdminRows(
			Order::getAdminList($status, max(1, $total), 0, $filters['date_from'], $filters['date_to'], $filters),
			true
		);

		$books = [[
			'<b>Reference</b>',
			'<b>Customer</b>',
			'<b>Email</b>',
			'<b>Phone</b>',
			'<b>Status</b>',
			'<b>Payment</b>',
			'<b>Product</b>',
			'<b>Stock code</b>',
			'<b>Barcode</b>',
			'<b>Unit price</b>',
			'<b>Qty</b>',
			'<b>Line total</b>',
			'<b>Subtotal</b>',
			'<b>Shipping</b>',
			'<b>Discount</b>',
			'<b>Order total</b>',
			'<b>Date</b>',
			'<b>Note</b>',
		]];

		foreach ($rows as $row) {
			$discount = (float) ($row['coupon_discount'] ?? 0)
				+ (float) ($row['promotion_discount'] ?? 0)
				+ (float) ($row['payment_discount'] ?? 0)
				+ (float) ($row['manual_discount'] ?? 0);

			$base = [
				(string) ($row['reference'] ?? ''),
				(string) ($row['customer_name'] ?? ''),
				(string) ($row['customer_email'] ?? ''),
				(string) ($row['customer_phone'] ?? ''),
				(string) ($row['status_label'] ?? ''),
				(string) ($row['payment_label'] ?? ''),
			];
			$tail = [
				(float) ($row['subtotal'] ?? 0),
				(float) ($row['shipping'] ?? 0),
				$discount,
				(float) ($row['total'] ?? 0),
				(string) ($row['date_add'] ?? ''),
				(string) ($row['note'] ?? ''),
			];

			$lines = is_array($row['lines'] ?? null) ? $row['lines'] : [];

			if ($lines === []) {
				$books[] = array_merge($base, ['', '', '', '', '', ''], $tail);
				continue;
			}

			foreach ($lines as $line) {
				$books[] = array_merge($base, [
					(string) ($line['product_name'] ?? ''),
					(string) ($line['stock_code'] ?? ''),
					(string) ($line['barcode'] ?? ''),
					(float) ($line['price'] ?? 0),
					(float) ($line['qty'] ?? 0),
					(float) ($line['total'] ?? 0),
				], $tail);
			}
		}

		$name = 'order-list.xlsx';
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		SimpleXLSXGen::fromArray($books)->downloadAs($name);
		exit;
	}

	public static function setFlash(string $message, string $type = 'success'): void
	{
		$_SESSION['export_excel_flash'] = [
			'message' => $message,
			'type' => $type,
		];
	}

	/** @return array{message:string,type:string}|null */
	public static function consumeFlash(): ?array
	{
		if (empty($_SESSION['export_excel_flash']) || !is_array($_SESSION['export_excel_flash'])) {
			return null;
		}

		$flash = $_SESSION['export_excel_flash'];
		unset($_SESSION['export_excel_flash']);

		return [
			'message' => (string) ($flash['message'] ?? ''),
			'type' => (string) ($flash['type'] ?? 'success'),
		];
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

	private static function decodeHtmlEntities(string $string): string
	{
		return html_entity_decode($string, ENT_QUOTES | ENT_XHTML | ENT_HTML5, 'UTF-8');
	}

	/** @return list<string> */
	private static function productCatalogHeaders(): array
	{
		return [
			'<b>Product Name</b>',
			'<b>Barcode</b>',
			'<b>Stock Code</b>',
			'<b>Desi</b>',
			'<b>Price</b>',
			'<b>Old Price</b>',
			'<b>Vat</b>',
			'<b>Stock</b>',
			'<b>short Description</b>',
			'<b>Description</b>',
			'<b>Meta Title</b>',
			'<b>Meta Description</b>',
			'<b>Slug</b>',
			'<b>Category Name</b>',
			'<b>Brand Name</b>',
			'<b>Images</b>',
			'<b>Active</b>',
			'<b>Product ID</b>',
			'<b>Parent Stock Code</b>',
			'<b>Variation</b>',
			'<b>Type</b>',
			'<b>Variation ID</b>',
		];
	}

	/**
	 * @param array<string, mixed> $gd
	 * @return list<mixed>
	 */
	private static function productCatalogRow(array $gd, string $type): array
	{
		$idProduct = (int) ($gd['id_product'] ?? 0);

		return [
			$gd['product_name'] ?? '',
			$gd['barcode'] ?? '',
			$gd['stock_code'] ?? '',
			$gd['desi'] ?? '',
			$gd['price'] ?? '',
			$gd['old_price'] ?? '',
			$gd['vat'] ?? '',
			$gd['stock'] ?? '',
			SimpleXLSXGen::raw((string) ($gd['short_description'] ?? '')),
			SimpleXLSXGen::raw(self::decodeHtmlEntities((string) ($gd['description'] ?? ''))),
			$gd['meta_title'] ?? '',
			$gd['meta_description'] ?? '',
			$gd['product_link'] ?? '',
			$gd['category_name'] ?? '',
			$gd['brand_name'] ?? '',
			Product::getExportImageUrls($idProduct),
			$gd['active_label'] ?? '',
			$idProduct,
			'',
			'',
			$type,
			'',
		];
	}

	/**
	 * @param array<string, mixed> $gd
	 * @param array<string, mixed> $variation
	 * @return list<mixed>
	 */
	private static function variationCatalogRow(array $gd, array $variation): array
	{
		$idProduct = (int) ($gd['id_product'] ?? 0);
		$basePrice = (float) ($gd['price'] ?? 0);
		$varPrice = $variation['price'] !== null && $variation['price'] !== ''
			? (float) $variation['price']
			: $basePrice;
		$active = !empty($variation['active']) ? 'Aktif' : 'Pasif';

		return [
			$gd['product_name'] ?? '',
			$variation['barcode'] ?? '',
			$variation['sku'] ?? '',
			$gd['desi'] ?? '',
			$varPrice,
			'',
			$gd['vat'] ?? '',
			$variation['stock'] ?? 0,
			SimpleXLSXGen::raw((string) ($gd['short_description'] ?? '')),
			SimpleXLSXGen::raw(self::decodeHtmlEntities((string) ($gd['description'] ?? ''))),
			$gd['meta_title'] ?? '',
			$gd['meta_description'] ?? '',
			$gd['product_link'] ?? '',
			$gd['category_name'] ?? '',
			$gd['brand_name'] ?? '',
			'',
			$active,
			$idProduct,
			(string) ($gd['stock_code'] ?? ''),
			ProductVariation::formatLabel($variation),
			'variation',
			(int) ($variation['id_variation'] ?? 0),
		];
	}

	/**
	 * @param array<int, mixed> $row
	 * @param array<string, int> $map
	 * @return array{success:bool,message?:string,unchanged?:bool}
	 */
	private static function importPriceStockRow(array $row, array $map, int $lineNo): array
	{
		$type = mb_strtolower(self::mappedCell($row, $map, 'type'), 'UTF-8');
		$variationId = (int) self::mappedCell($row, $map, 'variation_id');
		$isVariation = $variationId > 0 || in_array($type, ['variation', 'varyasyon', 'variant'], true);

		if ($isVariation) {
			return self::importPriceStockVariation($row, $map, $lineNo, $variationId);
		}

		$idProduct = self::resolvePriceStockProductId($row, $map);

		if ($idProduct <= 0) {
			return ['success' => false, 'message' => 'Satır ' . $lineNo . ': ürün bulunamadı'];
		}

		$patch = [];
		$price = self::mappedCell($row, $map, 'price');
		$cost = self::mappedCell($row, $map, 'cost');
		$oldPrice = self::mappedCell($row, $map, 'old_price');
		$stock = self::mappedCell($row, $map, 'stock');

		if ($price !== '') {
			$patch['price'] = $price;
		}

		if ($cost !== '') {
			$patch['cost'] = $cost;
		}

		if ($oldPrice !== '') {
			$patch['old_price'] = $oldPrice;
		}

		if ($stock !== '' && !ProductVariation::hasVariations($idProduct)) {
			$patch['stock'] = $stock;
		}

		if ($patch === []) {
			return ['success' => true, 'unchanged' => true];
		}

		$result = Product::patchQuick($idProduct, $patch);

		if (empty($result['success'])) {
			return ['success' => false, 'message' => 'Satır ' . $lineNo . ': ' . (string) ($result['message'] ?? 'güncellenemedi')];
		}

		return ['success' => true];
	}

	/**
	 * @param array<int, mixed> $row
	 * @param array<string, int> $map
	 * @return array{success:bool,message?:string,unchanged?:bool}
	 */
	private static function importPriceStockVariation(array $row, array $map, int $lineNo, int $variationId): array
	{
		$variation = $variationId > 0 ? ProductVariation::getById($variationId) : null;

		if (!$variation) {
			$idProduct = (int) self::mappedCell($row, $map, 'product_id');
			$sku = self::mappedCell($row, $map, 'stock_code');
			$barcode = self::mappedCell($row, $map, 'barcode');

			if ($idProduct > 0 && $sku !== '') {
				$variation = ProductVariation::getByProductSku($idProduct, $sku);
			}

			if (!$variation && $sku !== '') {
				$variation = ProductVariation::findUniqueBySku($sku);
			}

			if (!$variation && $barcode !== '') {
				$variation = ProductVariation::findUniqueByBarcode($barcode);
			}
		}

		if (!$variation) {
			return ['success' => false, 'message' => 'Satır ' . $lineNo . ': varyasyon bulunamadı'];
		}

		$patch = [];
		$price = self::mappedCell($row, $map, 'price');
		$stock = self::mappedCell($row, $map, 'stock');

		if ($price !== '') {
			$patch['price'] = $price;
		}

		if ($stock !== '') {
			$patch['stock'] = $stock;
		}

		if ($patch === []) {
			return ['success' => true, 'unchanged' => true];
		}

		$result = ProductVariation::patchPriceStock((int) $variation['id_variation'], $patch);

		if (empty($result['success'])) {
			return ['success' => false, 'message' => 'Satır ' . $lineNo . ': ' . (string) ($result['message'] ?? 'güncellenemedi')];
		}

		return ['success' => true, 'unchanged' => !empty($result['unchanged'])];
	}

	/**
	 * @param array<int, mixed> $row
	 * @param array<string, int> $map
	 */
	private static function resolvePriceStockProductId(array $row, array $map): int
	{
		$id = (int) self::mappedCell($row, $map, 'product_id');

		if ($id > 0 && Product::getByIdAdmin($id)) {
			return $id;
		}

		$sku = self::mappedCell($row, $map, 'stock_code');

		if ($sku !== '') {
			$found = (int) DB::getValue('SELECT id_product FROM products WHERE stock_code = ? LIMIT 1', [$sku]);

			if ($found > 0) {
				return $found;
			}
		}

		$barcode = self::mappedCell($row, $map, 'barcode');

		if ($barcode !== '') {
			$found = (int) DB::getValue('SELECT id_product FROM products WHERE barcode = ? LIMIT 1', [$barcode]);

			if ($found > 0) {
				return $found;
			}
		}

		return 0;
	}

	/**
	 * @param list<string> $headers
	 * @return array<string, int>
	 */
	private static function buildPriceStockColumnMap(array $headers): array
	{
		$aliases = [
			'product_id' => ['product id', 'ürün id', 'urun id', 'id'],
			'variation_id' => ['variation id', 'varyasyon id', 'id_variation'],
			'type' => ['type', 'tip', 'tür', 'tur'],
			'product_name' => ['product name', 'ürün adı', 'urun adi'],
			'variation' => ['variation', 'varyasyon', 'variant'],
			'stock_code' => ['stock code', 'stok kodu', 'sku'],
			'barcode' => ['barcode', 'barkod'],
			'price' => ['price', 'fiyat', 'satış fiyatı', 'satis fiyati'],
			'cost' => ['cost', 'cost price', 'alış fiyatı', 'alis fiyati', 'maliyet', 'maliyet fiyatı', 'maliyet fiyati', 'wholesale price'],
			'old_price' => ['old price', 'eski fiyat'],
			'stock' => ['stock', 'stok'],
		];
		$map = [];

		foreach ($headers as $index => $header) {
			if ($header === '') {
				continue;
			}

			foreach ($aliases as $field => $names) {
				if (in_array($header, $names, true)) {
					$map[$field] = $index;
					break;
				}
			}
		}

		return $map;
	}

	/**
	 * @param array<int, mixed> $row
	 * @param array<string, int> $map
	 */
	private static function mappedCell(array $row, array $map, string $key): string
	{
		if (!isset($map[$key])) {
			return '';
		}

		$index = $map[$key];
		$value = isset($row[$index]) ? trim((string) $row[$index]) : '';

		return $value === '' ? '' : $value;
	}

	private static function normalizeHeader($header): string
	{
		$header = strip_tags((string) $header);
		$header = html_entity_decode($header, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$header = strtolower(trim($header));
		$header = preg_replace('/\s+/', ' ', $header);

		return $header ?? '';
	}

	private static function isRowEmpty(array $row): bool
	{
		foreach ($row as $cell) {
			if (trim((string) $cell) !== '') {
				return false;
			}
		}

		return true;
	}
}
