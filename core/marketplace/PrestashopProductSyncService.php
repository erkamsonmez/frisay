<?php

namespace Prestashop;

class ProductSyncService
{
	public static function ensureSchema(): void
	{
		$products = \DB::execute("SHOW TABLES LIKE 'prestashop_products'");

		if (empty($products)) {
			\DB::execute(
				"CREATE TABLE `prestashop_products` (
					`id_product` int(11) NOT NULL,
					`ps_product_id` int(11) NOT NULL DEFAULT 0,
					`reference` varchar(64) NOT NULL DEFAULT '',
					`barcode` varchar(64) NOT NULL DEFAULT '',
					`sale_price` decimal(20,2) NOT NULL DEFAULT 0.00,
					`quantity` int(11) NOT NULL DEFAULT 0,
					`last_status` varchar(32) NOT NULL DEFAULT '',
					`last_error` text NULL,
					`last_sync_at` datetime NULL,
					`date_add` datetime NOT NULL,
					`date_upd` datetime NOT NULL,
					PRIMARY KEY (`id_product`),
					KEY `ps_product_id` (`ps_product_id`),
					KEY `reference` (`reference`),
					KEY `barcode` (`barcode`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		\MarketplaceTables::ensureSchema();
	}

	public static function isConfigured(): bool
	{
		return self::api()->isConfigured();
	}

	/** @return array{ok:bool,message:string,httpCode?:int,shop_url?:string,product_count?:int,sample_ids?:list<int>} */
	public static function testConnection(): array
	{
		return self::api()->testConnection();
	}

	public static function api(): PrestashopApi
	{
		return new PrestashopApi(
			(string) \Settings::get('PRESTASHOP_API_KEY'),
			(string) \Settings::get('PRESTASHOP_URL'),
			(int) (\Settings::get('PRESTASHOP_LANG_ID') ?: 1)
		);
	}

	/** @return array<string, mixed>|null */
	public static function findMapping(int $idProduct): ?array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return null;
		}

		$row = \DB::getRowSafe('prestashop_products', 'id_product = ?', [$idProduct]);

		return is_array($row) ? $row : null;
	}

	public static function findByPsId(int $psProductId): ?array
	{
		self::ensureSchema();

		if ($psProductId <= 0) {
			return null;
		}

		$row = \DB::getRowSafe('prestashop_products', 'ps_product_id = ?', [$psProductId]);

		return is_array($row) ? $row : null;
	}

	/** @param array<string, mixed>|null $mapping */
	public static function isLinked(?array $mapping): bool
	{
		return is_array($mapping) && (int) ($mapping['ps_product_id'] ?? 0) > 0;
	}

	/**
	 * PrestaShop ürününü FShop'a çeker (yoksa oluşturur, varsa eşler).
	 *
	 * @param array<string, mixed> $options
	 * @return array{ok: bool, message: string, id_product?: int, action?: string, mapping?: array<string, mixed>}
	 */
	public static function pullProduct(int $psProductId = 0, string $reference = '', array $options = []): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'PrestaShop API ayarları tanımlı değil'];
		}

		$api = self::api();

		if ($psProductId <= 0 && preg_match('/^\d+$/', trim($reference))) {
			$psProductId = (int) $reference;
		}

		if ($psProductId <= 0) {
			return ['ok' => false, 'message' => 'PrestaShop ürün ID girin (yalnızca sayı)'];
		}

		$fetched = $api->fetchProduct($psProductId);

		if (empty($fetched['ok']) || empty($fetched['product']) || !is_array($fetched['product'])) {
			return ['ok' => false, 'message' => (string) ($fetched['message'] ?? 'PrestaShop ürünü bulunamadı')];
		}

		$psProduct = $fetched['product'];

		$data = self::normalizePsProduct($psProduct);
		$psId = (int) ($data['ps_product_id'] ?? 0);

		if ($psId <= 0) {
			return ['ok' => false, 'message' => 'PrestaShop ürün ID alınamadı'];
		}

		$existingMap = self::findByPsId($psId);
		$idProduct = ($existingMap && (int) ($existingMap['id_product'] ?? 0) > 0)
			? (int) $existingMap['id_product']
			: self::findLocalProductId($data);

		if ($idProduct > 0) {
			$updated = self::updateShopProduct($idProduct, $data);
			$imageCount = self::importPsImages($idProduct, $psProduct, true);
			self::saveMapping($idProduct, [
				'ps_product_id' => $psId,
				'reference' => (string) ($data['stock_code'] ?? ''),
				'barcode' => (string) ($data['barcode'] ?? ''),
				'sale_price' => (float) ($data['price'] ?? 0),
				'quantity' => (int) ($data['stock'] ?? 0),
				'last_status' => 'updated',
				'last_error' => empty($updated['success']) ? (string) ($updated['message'] ?? '') : '',
				'last_sync_at' => date('Y-m-d H:i:s'),
			]);

			if (empty($updated['success'])) {
				return [
					'ok' => false,
					'message' => (string) ($updated['message'] ?? 'Ürün güncellenemedi'),
					'id_product' => $idProduct,
				];
			}

			return [
				'ok' => true,
				'message' => 'Mevcut ürün güncellendi (#' . $idProduct . ')'
					. ($imageCount > 0 ? (', ' . $imageCount . ' görsel') : ''),
				'id_product' => $idProduct,
				'action' => 'updated',
				'mapping' => self::findMapping($idProduct),
			];
		}

		$created = self::createShopProduct($data, $options);

		if (empty($created['success'])) {
			return ['ok' => false, 'message' => (string) ($created['message'] ?? 'Ürün oluşturulamadı')];
		}

		$idProduct = (int) ($created['id_product'] ?? 0);
		$imageCount = self::importPsImages($idProduct, $psProduct, false);
		self::saveMapping($idProduct, [
			'ps_product_id' => $psId,
			'reference' => (string) ($data['stock_code'] ?? ''),
			'barcode' => (string) ($data['barcode'] ?? ''),
			'sale_price' => (float) ($data['price'] ?? 0),
			'quantity' => (int) ($data['stock'] ?? 0),
			'last_status' => 'imported',
			'last_error' => '',
			'last_sync_at' => date('Y-m-d H:i:s'),
		]);

		return [
			'ok' => true,
			'message' => (string) ($created['message'] ?? 'Ürün çekildi')
				. ($imageCount > 0 ? (', ' . $imageCount . ' görsel') : ''),
			'id_product' => $idProduct,
			'action' => 'created',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @param array<string, mixed> $options
	 * @return array{ok: bool, message: string, created?: int, linked?: int, skipped?: int, failed?: int, has_more?: bool, next_offset?: int, items?: array<int, array<string, mixed>>}
	 */
	public static function pullBatch(int $offset = 0, int $limit = 10, array $options = []): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'PrestaShop API ayarları tanımlı değil'];
		}

		$limit = max(1, min(50, $limit));
		$offset = max(0, $offset);
		$listed = self::api()->listProducts($offset, $limit, $options);

		if (empty($listed['ok'])) {
			return [
				'ok' => false,
				'message' => (string) ($listed['message'] ?? 'PrestaShop ürün listesi okunamadı'),
				'created' => 0,
				'linked' => 0,
				'skipped' => 0,
				'failed' => 0,
				'has_more' => false,
				'next_offset' => $offset,
				'items' => [],
			];
		}

		$ids = $listed['items'];

		if ($ids === []) {
			return [
				'ok' => $offset > 0,
				'message' => $offset > 0
					? 'Daha fazla ürün yok'
					: (string) ($listed['message'] ?? 'Filtreye uyan PrestaShop ürünü yok'),
				'created' => 0,
				'linked' => 0,
				'skipped' => 0,
				'failed' => 0,
				'has_more' => false,
				'next_offset' => $offset,
				'items' => [],
			];
		}

		$created = 0;
		$linked = 0;
		$updated = 0;
		$skipped = 0;
		$failed = 0;
		$items = [];
		$firstError = '';

		foreach ($ids as $row) {
			$psId = (int) ($row['id'] ?? 0);
			$result = self::pullProduct($psId, '', $options);
			$action = (string) ($result['action'] ?? '');

			if (!empty($result['ok'])) {
				if ($action === 'created') {
					$created++;
				} elseif ($action === 'updated') {
					$updated++;
				} elseif ($action === 'linked') {
					$linked++;
				} else {
					$skipped++;
				}
			} else {
				$failed++;

				if ($firstError === '') {
					$firstError = (string) ($result['message'] ?? 'bilinmeyen hata');
				}
			}

			$items[] = [
				'ps_product_id' => $psId,
				'ok' => !empty($result['ok']),
				'action' => $action,
				'message' => (string) ($result['message'] ?? ''),
				'id_product' => (int) ($result['id_product'] ?? 0),
			];
		}

		$hasMore = count($ids) >= $limit;

		return [
			'ok' => true,
			'message' => $created . ' eklendi, ' . $updated . ' güncellendi, ' . $linked . ' eşlendi, ' . $skipped . ' atlandı'
				. ($failed > 0 ? (', ' . $failed . ' hata' . ($firstError !== '' ? (' — ' . $firstError) : '')) : ''),
			'created' => $created,
			'linked' => $linked,
			'updated' => $updated,
			'skipped' => $skipped,
			'failed' => $failed,
			'has_more' => $hasMore,
			'next_offset' => $offset + count($ids),
			'items' => $items,
		];
	}

	/**
	 * FShop ürününü PrestaShop'a gönderir (yoksa oluşturur, varsa günceller).
	 *
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function sendProduct(int $idProduct, bool $uploadImages = true): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'PrestaShop API ayarları tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Mağaza ürünü bulunamadı'];
		}

		$mapping = self::findMapping($idProduct);
		$psId = $mapping ? (int) ($mapping['ps_product_id'] ?? 0) : 0;
		$fields = self::buildPsFields($product);
		$api = self::api();

		if ($psId > 0) {
			$result = $api->updateProduct($psId, $fields);
		} else {
			$result = $api->createProduct($fields);
			$psId = (int) ($result['id'] ?? 0);
		}

		if (self::isApiError($result)) {
			self::saveMapping($idProduct, [
				'ps_product_id' => $psId,
				'reference' => (string) ($fields['reference'] ?? ''),
				'barcode' => (string) ($fields['ean13'] ?? ''),
				'sale_price' => (float) ($fields['price'] ?? 0),
				'quantity' => (int) ($fields['quantity'] ?? 0),
				'last_status' => 'failed',
				'last_error' => (string) ($result['message'] ?? 'Gönderilemedi'),
				'last_sync_at' => date('Y-m-d H:i:s'),
			]);

			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'PrestaShop gönderilemedi')];
		}

		$psId = (int) ($result['id'] ?? $psId);

		if ($uploadImages && $psId > 0 && empty($mapping['ps_product_id'])) {
			self::uploadShopImages($psId, $idProduct);
		}

		self::saveMapping($idProduct, [
			'ps_product_id' => $psId,
			'reference' => (string) ($fields['reference'] ?? ''),
			'barcode' => (string) ($fields['ean13'] ?? ''),
			'sale_price' => (float) ($fields['price'] ?? 0),
			'quantity' => (int) ($fields['quantity'] ?? 0),
			'last_status' => 'synced',
			'last_error' => '',
			'last_sync_at' => date('Y-m-d H:i:s'),
		]);

		return [
			'ok' => true,
			'message' => ($mapping && (int) ($mapping['ps_product_id'] ?? 0) > 0)
				? 'PrestaShop ürünü güncellendi (#' . $psId . ')'
				: 'Ürün PrestaShop\'a eklendi (#' . $psId . ')',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string, sent?: int, failed?: int, remaining?: int, items?: array<int, array<string, mixed>>}
	 */
	public static function sendBatch(int $limit = 10): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'PrestaShop API ayarları tanımlı değil'];
		}

		$limit = max(1, min(25, $limit));
		$rows = \DB::execute(
			'SELECT p.id_product
			 FROM products p
			 LEFT JOIN prestashop_products ps ON ps.id_product = p.id_product
			 WHERE p.active = 1 AND (ps.id_product IS NULL OR ps.ps_product_id = 0)
			 ORDER BY p.id_product ASC
			 LIMIT ' . (int) $limit
		) ?: [];

		$sent = 0;
		$failed = 0;
		$items = [];

		foreach ($rows as $row) {
			$idProduct = (int) ($row['id_product'] ?? 0);
			$result = self::sendProduct($idProduct, true);

			if (!empty($result['ok'])) {
				$sent++;
			} else {
				$failed++;
			}

			$items[] = [
				'id_product' => $idProduct,
				'ok' => !empty($result['ok']),
				'message' => (string) ($result['message'] ?? ''),
			];
		}

		$remaining = (int) (\DB::getValue(
			'SELECT COUNT(*)
			 FROM products p
			 LEFT JOIN prestashop_products ps ON ps.id_product = p.id_product
			 WHERE p.active = 1 AND (ps.id_product IS NULL OR ps.ps_product_id = 0)'
		) ?: 0);

		return [
			'ok' => true,
			'message' => $sent . ' ürün gönderildi'
				. ($failed > 0 ? (', ' . $failed . ' hata') : '')
				. ($remaining > 0 ? (', ' . $remaining . ' kaldı') : ''),
			'sent' => $sent,
			'failed' => $failed,
			'remaining' => $remaining,
			'items' => $items,
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function linkExistingProduct(int $idProduct, string $code = ''): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'PrestaShop API ayarları tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Mağaza ürünü bulunamadı'];
		}

		$psId = (int) preg_replace('/\D+/', '', trim($code));

		if ($psId <= 0) {
			return ['ok' => false, 'message' => 'PrestaShop ürün ID girin (yalnızca sayı)'];
		}

		$fetched = self::api()->fetchProduct($psId);

		if (empty($fetched['ok']) || empty($fetched['product']) || !is_array($fetched['product'])) {
			return ['ok' => false, 'message' => (string) ($fetched['message'] ?? ('PrestaShop ürün #' . $psId . ' bulunamadı'))];
		}

		$data = self::normalizePsProduct($fetched['product']);
		$psId = (int) ($data['ps_product_id'] ?? $psId);

		if ($psId <= 0) {
			return ['ok' => false, 'message' => 'PrestaShop ürün ID alınamadı'];
		}

		self::saveMapping($idProduct, [
			'ps_product_id' => $psId,
			'reference' => (string) ($data['stock_code'] ?? ''),
			'barcode' => (string) ($data['barcode'] ?? ($product['barcode'] ?? '')),
			'sale_price' => (float) ($data['price'] ?? 0),
			'quantity' => (int) ($data['stock'] ?? 0),
			'last_status' => 'linked',
			'last_error' => '',
			'last_sync_at' => date('Y-m-d H:i:s'),
		]);

		return [
			'ok' => true,
			'message' => 'PrestaShop ürünüyle bağlantı oluşturuldu (#' . $psId . ')',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function updatePriceStock(int $idProduct, ?float $saleOverride = null, ?float $listOverride = null): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'PrestaShop API ayarları tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);
		$mapping = self::findMapping($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Ürün bulunamadı'];
		}

		if (!self::isLinked($mapping)) {
			return ['ok' => false, 'message' => 'Önce PrestaShop ürünüyle eşleştirin veya gönderin'];
		}

		$psId = (int) ($mapping['ps_product_id'] ?? 0);
		$salePrice = $saleOverride !== null && $saleOverride > 0
			? $saleOverride
			: (float) ($mapping['sale_price'] ?? 0);

		if ($salePrice <= 0) {
			$salePrice = (float) ($product['price'] ?? 0);
		}

		$stock = max(0, (int) \Product::getStock($product));
		$oldQty = (float) ($mapping['quantity'] ?? 0);
		$oldSale = (float) ($mapping['sale_price'] ?? 0);
		$result = self::api()->updateProduct($psId, [
			'price' => $salePrice,
			'quantity' => $stock,
			'reference' => (string) ($mapping['reference'] ?? ($product['stock_code'] ?? '')),
			'ean13' => (string) ($mapping['barcode'] ?? ($product['barcode'] ?? '')),
			'name' => (string) ($product['product_name'] ?? ''),
			'description' => (string) ($product['description'] ?? ''),
			'description_short' => (string) ($product['short_description'] ?? ''),
			'id_category_default' => self::defaultPsCategoryId(),
			'id_tax_rules_group' => self::taxGroupId(),
			'id_shop_default' => self::shopId(),
			'active' => !empty($product['active']) ? 1 : 0,
		]);
		$now = date('Y-m-d H:i:s');

		if (self::isApiError($result)) {
			self::saveMapping($idProduct, [
				'sale_price' => $salePrice,
				'quantity' => $stock,
				'last_status' => 'failed',
				'last_error' => (string) ($result['message'] ?? 'Fiyat/stok güncelleme hatası'),
				'last_sync_at' => $now,
			]);

			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Fiyat/stok güncelleme hatası')];
		}

		self::saveMapping($idProduct, [
			'sale_price' => $salePrice,
			'quantity' => $stock,
			'last_status' => 'synced',
			'last_error' => '',
			'last_sync_at' => $now,
		]);

		if (abs($oldQty - (float) $stock) >= 0.0001) {
			\MarketplaceLog::stockChange(
				'prestashop',
				(string) ($mapping['reference'] ?? $psId),
				$oldQty,
				$stock,
				'PHANTOM_STOCK_CHANGE',
				$idProduct
			);
		}

		if ($oldSale > 0 && abs($oldSale - (float) $salePrice) >= 0.0001) {
			\MarketplaceLog::priceUpdate(
				'prestashop',
				(string) ($mapping['reference'] ?? $psId),
				$oldSale,
				(float) $salePrice,
				$idProduct
			);
		}

		return [
			'ok' => true,
			'message' => 'PrestaShop fiyat/stok güncellendi',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string}
	 */
	public static function unlinkProduct(int $idProduct): array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz ürün'];
		}

		if (!self::findMapping($idProduct)) {
			return ['ok' => false, 'message' => 'PrestaShop bağlantısı bulunamadı'];
		}

		\DB::execute('DELETE FROM prestashop_products WHERE id_product = ?', [$idProduct]);

		return [
			'ok' => true,
			'message' => 'PrestaShop bağlantısı silindi (ürün PrestaShop mağazasında durmaya devam eder)',
		];
	}

	/** @param mixed $result */
	public static function isApiError($result): bool
	{
		return PrestashopApi::isApiError($result);
	}

	/**
	 * @param array<string, mixed> $psProduct
	 * @return array<string, mixed>
	 */
	private static function normalizePsProduct(array $psProduct): array
	{
		$api = self::api();
		$psId = $api->scalarId($psProduct['id'] ?? 0);
		$name = $api->scalarText($psProduct['name'] ?? '');
		$description = $api->scalarText($psProduct['description'] ?? '');
		$short = $api->scalarText($psProduct['description_short'] ?? '');

		if ($name === '') {
			$name = 'PrestaShop ürün #' . $psId;
		}

		$stock = 0;
		$stockRow = $psId > 0 ? $api->getStockAvailable($psId, 0) : null;

		if (is_array($stockRow)) {
			$stock = $api->scalarId($stockRow['quantity'] ?? 0);
		}

		$categoryName = '';
		$categoryId = $api->scalarId($psProduct['id_category_default'] ?? 0);

		if ($categoryId > 0) {
			$cat = $api->getCategory($categoryId);

			if (is_array($cat)) {
				$categoryName = $api->scalarText($cat['name'] ?? '');
			}
		}

		$brandName = '';
		$manufacturerId = $api->scalarId($psProduct['id_manufacturer'] ?? 0);

		if ($manufacturerId > 0) {
			$man = $api->getManufacturer($manufacturerId);

			if (is_array($man)) {
				$brandName = $api->scalarText($man['name'] ?? '');
			}
		}

		$barcode = $api->scalarText($psProduct['ean13'] ?? ($psProduct['upc'] ?? ''));
		$stockCode = $api->scalarText($psProduct['reference'] ?? '');
		$price = (float) str_replace(',', '.', $api->scalarText($psProduct['price'] ?? 0));

		return [
			'ps_product_id' => $psId,
			'product_name' => $name,
			'description' => $description !== '' ? \Security::sanitizeHtml($description) : '',
			'short_description' => $short !== '' ? mb_substr(trim(strip_tags($short)), 0, 255) : '',
			'brand_name' => $brandName,
			'category_name' => $categoryName,
			'barcode' => $barcode,
			'stock_code' => $stockCode,
			'price' => max(0, $price),
			'old_price' => 0.0,
			'stock' => max(0, $stock),
			'active' => $api->scalarId($psProduct['active'] ?? 1) > 0 ? 1 : 0,
		];
	}

	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options
	 * @return array{success: bool, message: string, id_product?: int}
	 */
	private static function createShopProduct(array $data, array $options): array
	{
		$categoryMode = (string) ($options['category_mode'] ?? 'create');
		$brandMode = (string) ($options['brand_mode'] ?? 'create');
		$idCategory = (int) ($options['id_category'] ?? 0);
		$idBrand = (int) ($options['id_brand'] ?? 0);

		if ($categoryMode === 'create' || $idCategory <= 0) {
			$categoryResult = self::resolveOrCreateCategoryId((string) ($data['category_name'] ?? ''));

			if (!$categoryResult['success']) {
				return ['success' => false, 'message' => (string) $categoryResult['message']];
			}

			$idCategory = (int) ($categoryResult['id'] ?? 0);
		}

		if ($brandMode === 'create' || $idBrand <= 0) {
			$brandResult = self::resolveOrCreateBrandId((string) ($data['brand_name'] ?? ''));

			if (!$brandResult['success']) {
				return ['success' => false, 'message' => (string) $brandResult['message']];
			}

			$idBrand = (int) ($brandResult['id'] ?? 0);
		}

		if ($idCategory <= 0) {
			return ['success' => false, 'message' => 'Geçerli bir kategori seçin'];
		}

		if ($idBrand <= 0) {
			return ['success' => false, 'message' => 'Geçerli bir marka seçin'];
		}

		$name = trim((string) ($data['product_name'] ?? ''));

		if ($name === '') {
			return ['success' => false, 'message' => 'PrestaShop ürün adı boş'];
		}

		$result = \Product::save([
			'product_name' => $name,
			'short_description' => (string) ($data['short_description'] ?? ''),
			'description' => (string) ($data['description'] ?? ''),
			'id_category' => $idCategory,
			'id_brand' => $idBrand,
			'barcode' => (string) ($data['barcode'] ?? ''),
			'stock_code' => (string) ($data['stock_code'] ?? ''),
			'price' => (float) ($data['price'] ?? 0),
			'old_price' => (float) ($data['old_price'] ?? 0),
			'stock' => (int) ($data['stock'] ?? 0),
			'active' => 1,
		], 0);

		if (empty($result['success'])) {
			return ['success' => false, 'message' => (string) ($result['message'] ?? 'Ürün kaydedilemedi')];
		}

		return [
			'success' => true,
			'message' => 'Ürün PrestaShop\'tan eklendi',
			'id_product' => (int) ($result['id'] ?? 0),
		];
	}

	/** @param array<string, mixed> $data */
	private static function findLocalProductId(array $data): int
	{
		$barcode = trim((string) ($data['barcode'] ?? ''));
		$stockCode = trim((string) ($data['stock_code'] ?? ''));

		if ($barcode !== '') {
			$id = (int) (\DB::getValue('SELECT id_product FROM products WHERE barcode = ? LIMIT 1', [$barcode]) ?: 0);

			if ($id > 0) {
				return $id;
			}
		}

		if ($stockCode !== '') {
			$id = (int) (\DB::getValue('SELECT id_product FROM products WHERE stock_code = ? LIMIT 1', [$stockCode]) ?: 0);

			if ($id > 0) {
				return $id;
			}
		}

		return 0;
	}

	/** @param array<string, mixed> $data */
	private static function updateShopProduct(int $idProduct, array $data): array
	{
		$existing = \Product::getByIdAdmin($idProduct);

		if (!$existing) {
			return ['success' => false, 'message' => 'Mağaza ürünü bulunamadı'];
		}

		$row = [
			'description' => (string) ($data['description'] ?? ''),
			'short_description' => mb_substr((string) ($data['short_description'] ?? ''), 0, 512),
			'price' => max(0, (float) ($data['price'] ?? 0)),
			'doviz_price' => max(0, (float) ($data['price'] ?? 0)),
			'stock' => max(0, (int) ($data['stock'] ?? 0)),
			'date_upd' => date('Y-m-d H:i:s'),
		];
		$name = trim((string) ($data['product_name'] ?? ''));

		if ($name !== '') {
			$row['product_name'] = $name;
		}

		if (trim((string) ($data['barcode'] ?? '')) !== '') {
			$row['barcode'] = (string) $data['barcode'];
		}

		if (trim((string) ($data['stock_code'] ?? '')) !== '') {
			$row['stock_code'] = (string) $data['stock_code'];
		}

		\DB::update('products', $row, 'id_product = :where_id', ['where_id' => $idProduct]);

		return ['success' => true, 'message' => 'Ürün güncellendi'];
	}

	/**
	 * @param array<string, mixed> $psProduct
	 */
	private static function importPsImages(int $idProduct, array $psProduct, bool $onlyIfMissing = false): int
	{
		if ($idProduct <= 0) {
			return 0;
		}

		if ($onlyIfMissing && \Product::getImages($idProduct) !== []) {
			return 0;
		}

		$api = self::api();
		$psId = $api->scalarId($psProduct['id'] ?? 0);
		$imageIds = $api->getProductImageIds($psProduct);
		$count = 0;

		foreach ($imageIds as $imageId) {
			if ($count >= 6) {
				break;
			}

			$binary = $api->getImageBinary($psId, $imageId);

			if ($binary === '') {
				continue;
			}

			$result = \Product::importImageBinary($idProduct, $binary);

			if (!empty($result['success'])) {
				$count++;
			}
		}

		return $count;
	}

	/** @param array<string, mixed> $product */
	private static function buildPsFields(array $product): array
	{
		$stock = max(0, (int) \Product::getStock($product));

		return [
			'name' => (string) ($product['product_name'] ?? ''),
			'description' => (string) ($product['description'] ?? ''),
			'description_short' => (string) ($product['short_description'] ?? ''),
			'reference' => (string) ($product['stock_code'] ?? ''),
			'ean13' => mb_substr((string) ($product['barcode'] ?? ''), 0, 13),
			'price' => (float) ($product['price'] ?? 0),
			'wholesale_price' => (float) ($product['cost'] ?? 0),
			'quantity' => $stock,
			'active' => !empty($product['active']) ? 1 : 0,
			'id_category_default' => self::defaultPsCategoryId(),
			'id_tax_rules_group' => self::taxGroupId(),
			'id_shop_default' => self::shopId(),
			'id_manufacturer' => 0,
		];
	}

	private static function uploadShopImages(int $psProductId, int $idProduct): void
	{
		$root = dirname(__DIR__, 2);
		$count = 0;

		foreach (\Product::getImages($idProduct) as $image) {
			if ($count >= 6) {
				break;
			}

			$idImage = (int) ($image['id_image'] ?? 0);
			$path = $root . '/img/products/' . $idImage . '.jpg';

			if ($idImage <= 0 || !is_file($path)) {
				continue;
			}

			$result = self::api()->uploadProductImage($psProductId, $path);

			if (!empty($result['ok'])) {
				$count++;
			}
		}
	}

	private static function defaultPsCategoryId(): int
	{
		$id = (int) (\Settings::get('PRESTASHOP_DEFAULT_CATEGORY') ?: 2);

		return $id > 0 ? $id : 2;
	}

	private static function taxGroupId(): int
	{
		$id = (int) (\Settings::get('PRESTASHOP_TAX_GROUP') ?: 1);

		return $id > 0 ? $id : 1;
	}

	private static function shopId(): int
	{
		$id = (int) (\Settings::get('PRESTASHOP_SHOP_ID') ?: 1);

		return $id > 0 ? $id : 1;
	}

	/** @return array{success: bool, id: int, message: string} */
	private static function resolveOrCreateCategoryId(string $name): array
	{
		$name = trim($name);

		if ($name === '') {
			$name = 'PrestaShop';
		}

		$id = (int) \DB::getValue(
			'SELECT id_category FROM categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			return ['success' => true, 'id' => $id, 'message' => ''];
		}

		$parentId = (int) \DB::getValue(
			'SELECT id_category FROM categories WHERE id_parent = 0 AND active = 1 ORDER BY id_category ASC LIMIT 1'
		);

		$result = \Category::save([
			'category_name' => $name,
			'id_parent' => max(0, $parentId),
			'active' => 1,
		]);

		if (empty($result['success'])) {
			return [
				'success' => false,
				'id' => 0,
				'message' => (string) ($result['message'] ?? 'Kategori oluşturulamadı'),
			];
		}

		return ['success' => true, 'id' => (int) ($result['id'] ?? 0), 'message' => ''];
	}

	/** @return array{success: bool, id: int, message: string} */
	private static function resolveOrCreateBrandId(string $name): array
	{
		$name = trim($name);

		if ($name === '') {
			$name = 'PrestaShop';
		}

		$id = (int) \DB::getValue(
			'SELECT id_brand FROM brands WHERE LOWER(brand_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			return ['success' => true, 'id' => $id, 'message' => ''];
		}

		$result = \Brand::save([
			'brand_name' => $name,
			'active' => 1,
		]);

		if (!empty($result['success'])) {
			return ['success' => true, 'id' => (int) ($result['id'] ?? 0), 'message' => ''];
		}

		$slug = \Tools::createSlug($name);

		if ($slug !== '') {
			$result = \Brand::save([
				'brand_name' => $name,
				'brand_link' => $slug . '-' . substr(sha1($name), 0, 6),
				'active' => 1,
			]);

			if (!empty($result['success'])) {
				return ['success' => true, 'id' => (int) ($result['id'] ?? 0), 'message' => ''];
			}
		}

		return [
			'success' => false,
			'id' => 0,
			'message' => (string) ($result['message'] ?? 'Marka oluşturulamadı'),
		];
	}

	/** @param array<string, mixed> $fields */
	private static function saveMapping(int $idProduct, array $fields): void
	{
		self::ensureSchema();

		$now = date('Y-m-d H:i:s');
		$existing = self::findMapping($idProduct);
		$row = [
			'ps_product_id' => (int) ($fields['ps_product_id'] ?? ($existing['ps_product_id'] ?? 0)),
			'reference' => (string) ($fields['reference'] ?? ($existing['reference'] ?? '')),
			'barcode' => (string) ($fields['barcode'] ?? ($existing['barcode'] ?? '')),
			'sale_price' => (float) ($fields['sale_price'] ?? ($existing['sale_price'] ?? 0)),
			'quantity' => (int) ($fields['quantity'] ?? ($existing['quantity'] ?? 0)),
			'last_status' => (string) ($fields['last_status'] ?? ($existing['last_status'] ?? '')),
			'last_error' => (string) ($fields['last_error'] ?? ''),
			'last_sync_at' => (string) ($fields['last_sync_at'] ?? $now),
			'date_upd' => $now,
		];

		if ($existing) {
			\DB::update('prestashop_products', $row, 'id_product = :where_id', ['where_id' => $idProduct]);
		} else {
			$row['id_product'] = $idProduct;
			$row['date_add'] = $now;
			\DB::insert('prestashop_products', $row);
		}
	}
}
