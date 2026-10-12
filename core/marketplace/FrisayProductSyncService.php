<?php

namespace Frisay;

class ProductSyncService
{
	public static function ensureSchema(): void
	{
		$products = \DB::execute("SHOW TABLES LIKE 'frisay_products'");

		if (empty($products)) {
			\DB::execute(
				"CREATE TABLE `frisay_products` (
					`id_product` int(11) NOT NULL,
					`remote_id` int(11) NOT NULL DEFAULT 0,
					`stock_code` varchar(64) NOT NULL DEFAULT '',
					`barcode` varchar(64) NOT NULL DEFAULT '',
					`sale_price` decimal(20,2) NOT NULL DEFAULT 0.00,
					`quantity` int(11) NOT NULL DEFAULT 0,
					`last_status` varchar(32) NOT NULL DEFAULT '',
					`last_error` text NULL,
					`last_sync_at` datetime NULL,
					`date_add` datetime NOT NULL,
					`date_upd` datetime NOT NULL,
					PRIMARY KEY (`id_product`),
					KEY `remote_id` (`remote_id`),
					KEY `stock_code` (`stock_code`),
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

	public static function api(): FrisayApi
	{
		return new FrisayApi(
			(string) \Settings::get('FRISAY_API_URL'),
			(string) \Settings::get('FRISAY_API_KEY')
		);
	}

	/** @param mixed $result */
	public static function isApiError($result): bool
	{
		return FrisayApi::isApiError($result);
	}

	/** @return array<string, mixed>|null */
	public static function findMapping(int $idProduct): ?array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return null;
		}

		$row = \DB::getRowSafe('frisay_products', 'id_product = ?', [$idProduct]);

		return is_array($row) ? $row : null;
	}

	public static function findByRemoteId(int $remoteId): ?array
	{
		self::ensureSchema();

		if ($remoteId <= 0) {
			return null;
		}

		$row = \DB::getRowSafe('frisay_products', 'remote_id = ?', [$remoteId]);

		return is_array($row) ? $row : null;
	}

	/** @param array<string, mixed>|null $mapping */
	public static function isLinked(?array $mapping): bool
	{
		return is_array($mapping) && (int) ($mapping['remote_id'] ?? 0) > 0;
	}

	/**
	 * @return array{ok: bool, message: string, id_product?: int, action?: string, mapping?: array<string, mixed>}
	 */
	public static function pullProduct(int $remoteId = 0, string $code = '', array $options = []): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'FriSay API ayarları tanımlı değil'];
		}

		if ($remoteId <= 0 && preg_match('/^\d+$/', trim($code))) {
			$remoteId = (int) $code;
		}

		if ($remoteId <= 0) {
			return ['ok' => false, 'message' => 'Uzak FriSay ürün ID girin (yalnızca sayı)'];
		}

		$remote = self::api()->getProduct($remoteId);

		if (!$remote) {
			return ['ok' => false, 'message' => 'Uzak FriSay ürünü bulunamadı'];
		}

		$data = self::normalizeRemoteProduct($remote);
		$remoteId = (int) ($data['remote_id'] ?? $remoteId);

		if ($remoteId <= 0) {
			return ['ok' => false, 'message' => 'Uzak ürün ID alınamadı'];
		}

		$existingMap = self::findByRemoteId($remoteId);

		if ($existingMap && (int) ($existingMap['id_product'] ?? 0) > 0) {
			$idProduct = (int) $existingMap['id_product'];
			self::saveMapping($idProduct, self::mappingFromData($data, 'linked'));

			return [
				'ok' => true,
				'message' => 'Ürün zaten eşli (#' . $idProduct . ')',
				'id_product' => $idProduct,
				'action' => 'skipped',
				'mapping' => self::findMapping($idProduct),
			];
		}

		$idProduct = self::findLocalProductId($data);

		if ($idProduct > 0) {
			self::saveMapping($idProduct, self::mappingFromData($data, 'linked'));

			return [
				'ok' => true,
				'message' => 'Mevcut mağaza ürünüyle eşleştirildi (#' . $idProduct . ')',
				'id_product' => $idProduct,
				'action' => 'linked',
				'mapping' => self::findMapping($idProduct),
			];
		}

		$created = self::createShopProduct($data, $options);

		if (empty($created['success'])) {
			return ['ok' => false, 'message' => (string) ($created['message'] ?? 'Ürün oluşturulamadı')];
		}

		$idProduct = (int) ($created['id_product'] ?? 0);
		self::saveMapping($idProduct, self::mappingFromData($data, 'imported'));

		return [
			'ok' => true,
			'message' => (string) ($created['message'] ?? 'Ürün çekildi'),
			'id_product' => $idProduct,
			'action' => 'created',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string, created?: int, linked?: int, skipped?: int, failed?: int, has_more?: bool, next_page?: int, items?: array<int, array<string, mixed>>}
	 */
	public static function pullBatch(int $page = 1, int $limit = 10, array $options = []): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'FriSay API ayarları tanımlı değil'];
		}

		$page = max(1, $page);
		$limit = max(1, min(25, $limit));
		$list = self::api()->listProducts($page, $limit);

		if (empty($list['ok'])) {
			return ['ok' => false, 'message' => (string) ($list['message'] ?? 'Ürün listesi alınamadı')];
		}

		$itemsRemote = $list['items'] ?? [];
		$created = 0;
		$linked = 0;
		$skipped = 0;
		$failed = 0;
		$items = [];
		$firstError = '';

		foreach ($itemsRemote as $row) {
			if (!is_array($row)) {
				continue;
			}

			$remoteId = (int) ($row['id'] ?? ($row['id_product'] ?? 0));
			$result = self::pullProduct($remoteId, '', $options);
			$action = (string) ($result['action'] ?? '');

			if (!empty($result['ok'])) {
				if ($action === 'created') {
					$created++;
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
				'remote_id' => $remoteId,
				'ok' => !empty($result['ok']),
				'action' => $action,
				'message' => (string) ($result['message'] ?? ''),
				'id_product' => (int) ($result['id_product'] ?? 0),
			];
		}

		return [
			'ok' => true,
			'message' => $created . ' eklendi, ' . $linked . ' eşlendi, ' . $skipped . ' atlandı'
				. ($failed > 0 ? (', ' . $failed . ' hata' . ($firstError !== '' ? (' — ' . $firstError) : '')) : ''),
			'created' => $created,
			'linked' => $linked,
			'skipped' => $skipped,
			'failed' => $failed,
			'has_more' => !empty($list['has_more']),
			'next_page' => (int) ($list['next_page'] ?? ($page + 1)),
			'items' => $items,
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function sendProduct(int $idProduct): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'FriSay API ayarları tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Mağaza ürünü bulunamadı'];
		}

		$mapping = self::findMapping($idProduct);
		$remoteId = $mapping ? (int) ($mapping['remote_id'] ?? 0) : 0;
		$payload = self::buildRemoteFields($product);
		$api = self::api();

		if ($remoteId > 0) {
			$result = $api->updateProduct($remoteId, $payload);
		} else {
			$result = $api->createProduct($payload);
			$remoteId = (int) (($result['data']['id'] ?? 0) ?: ($result['id'] ?? 0));
		}

		if (self::isApiError($result)) {
			self::saveMapping($idProduct, [
				'remote_id' => $remoteId,
				'stock_code' => (string) ($payload['stock_code'] ?? ''),
				'barcode' => (string) ($payload['barcode'] ?? ''),
				'sale_price' => (float) ($payload['price'] ?? 0),
				'quantity' => (int) ($payload['stock'] ?? 0),
				'last_status' => 'failed',
				'last_error' => (string) ($result['message'] ?? 'Gönderilemedi'),
				'last_sync_at' => date('Y-m-d H:i:s'),
			]);

			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'FriSay ürünü gönderilemedi')];
		}

		$remoteId = (int) (($result['data']['id'] ?? 0) ?: ($result['id'] ?? $remoteId));

		self::saveMapping($idProduct, [
			'remote_id' => $remoteId,
			'stock_code' => (string) ($payload['stock_code'] ?? ''),
			'barcode' => (string) ($payload['barcode'] ?? ''),
			'sale_price' => (float) ($payload['price'] ?? 0),
			'quantity' => (int) ($payload['stock'] ?? 0),
			'last_status' => 'synced',
			'last_error' => '',
			'last_sync_at' => date('Y-m-d H:i:s'),
		]);

		$wasLinked = $mapping && (int) ($mapping['remote_id'] ?? 0) > 0;

		return [
			'ok' => true,
			'message' => $wasLinked
				? 'Uzak FriSay ürünü güncellendi (#' . $remoteId . ')'
				: 'Ürün uzak FriSay mağazasına eklendi (#' . $remoteId . ')',
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
			return ['ok' => false, 'message' => 'FriSay API ayarları tanımlı değil'];
		}

		$limit = max(1, min(25, $limit));
		$rows = \DB::execute(
			'SELECT p.id_product
			 FROM products p
			 LEFT JOIN frisay_products fp ON fp.id_product = p.id_product
			 WHERE p.active = 1 AND (fp.id_product IS NULL OR fp.remote_id = 0)
			 ORDER BY p.id_product ASC
			 LIMIT ' . (int) $limit
		) ?: [];

		$sent = 0;
		$failed = 0;
		$items = [];

		foreach ($rows as $row) {
			$idProduct = (int) ($row['id_product'] ?? 0);
			$result = self::sendProduct($idProduct);

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
			 LEFT JOIN frisay_products fp ON fp.id_product = p.id_product
			 WHERE p.active = 1 AND (fp.id_product IS NULL OR fp.remote_id = 0)'
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
			return ['ok' => false, 'message' => 'FriSay API ayarları tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Mağaza ürünü bulunamadı'];
		}

		$remoteId = (int) preg_replace('/\D+/', '', trim($code));

		if ($remoteId <= 0) {
			return ['ok' => false, 'message' => 'Uzak FriSay ürün ID girin (yalnızca sayı)'];
		}

		$remote = self::api()->getProduct($remoteId);

		if (!$remote) {
			return ['ok' => false, 'message' => 'Uzak FriSay ürün #' . $remoteId . ' bulunamadı'];
		}

		$data = self::normalizeRemoteProduct($remote);
		$remoteId = (int) ($data['remote_id'] ?? $remoteId);

		self::saveMapping($idProduct, self::mappingFromData($data, 'linked'));

		return [
			'ok' => true,
			'message' => 'Uzak FriSay ürünüyle bağlantı oluşturuldu (#' . $remoteId . ')',
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
			return ['ok' => false, 'message' => 'FriSay API ayarları tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);
		$mapping = self::findMapping($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Ürün bulunamadı'];
		}

		if (!self::isLinked($mapping)) {
			return ['ok' => false, 'message' => 'Önce uzak FriSay ürünüyle eşleştirin veya gönderin'];
		}

		$remoteId = (int) ($mapping['remote_id'] ?? 0);
		$salePrice = $saleOverride !== null && $saleOverride > 0
			? $saleOverride
			: (float) ($mapping['sale_price'] ?? 0);

		if ($salePrice <= 0) {
			$salePrice = (float) ($product['price'] ?? 0);
		}

		$stock = max(0, (int) \Product::getStock($product));
		$oldQty = (float) ($mapping['quantity'] ?? 0);
		$oldSale = (float) ($mapping['sale_price'] ?? 0);
		$payload = [
			'price' => $salePrice,
			'stock' => $stock,
			'active' => !empty($product['active']) ? 1 : 0,
		];

		if ($listOverride !== null && $listOverride > 0) {
			$payload['old_price'] = $listOverride;
			$payload['list_price'] = $listOverride;
		}

		$result = self::api()->quickUpdate($remoteId, $payload);
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
				'frisay',
				(string) ($mapping['stock_code'] ?? $remoteId),
				$oldQty,
				$stock,
				'PHANTOM_STOCK_CHANGE',
				$idProduct
			);
		}

		if ($oldSale > 0 && abs($oldSale - (float) $salePrice) >= 0.0001) {
			\MarketplaceLog::priceUpdate(
				'frisay',
				(string) ($mapping['stock_code'] ?? $remoteId),
				$oldSale,
				(float) $salePrice,
				$idProduct
			);
		}

		return [
			'ok' => true,
			'message' => 'Uzak FriSay fiyat/stok güncellendi',
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
			return ['ok' => false, 'message' => 'FriSay bağlantısı bulunamadı'];
		}

		\DB::execute('DELETE FROM frisay_products WHERE id_product = ?', [$idProduct]);

		return [
			'ok' => true,
			'message' => 'FriSay bağlantısı silindi (uzak mağazadaki ürün durmaya devam eder)',
		];
	}

	/**
	 * @param array<string, mixed> $fields
	 */
	private static function saveMapping(int $idProduct, array $fields): void
	{
		if ($idProduct <= 0) {
			return;
		}

		$now = date('Y-m-d H:i:s');
		$existing = self::findMapping($idProduct);
		$row = [
			'id_product' => $idProduct,
			'remote_id' => (int) ($fields['remote_id'] ?? ($existing['remote_id'] ?? 0)),
			'stock_code' => (string) ($fields['stock_code'] ?? ($existing['stock_code'] ?? '')),
			'barcode' => (string) ($fields['barcode'] ?? ($existing['barcode'] ?? '')),
			'sale_price' => (float) ($fields['sale_price'] ?? ($existing['sale_price'] ?? 0)),
			'quantity' => (int) ($fields['quantity'] ?? ($existing['quantity'] ?? 0)),
			'last_status' => (string) ($fields['last_status'] ?? ($existing['last_status'] ?? '')),
			'last_error' => (string) ($fields['last_error'] ?? ($existing['last_error'] ?? '')),
			'last_sync_at' => (string) ($fields['last_sync_at'] ?? ($existing['last_sync_at'] ?? $now)),
			'date_upd' => $now,
		];

		if ($existing) {
			\DB::execute(
				'UPDATE frisay_products
				 SET remote_id = ?, stock_code = ?, barcode = ?, sale_price = ?, quantity = ?,
				     last_status = ?, last_error = ?, last_sync_at = ?, date_upd = ?
				 WHERE id_product = ?',
				[
					$row['remote_id'],
					$row['stock_code'],
					$row['barcode'],
					$row['sale_price'],
					$row['quantity'],
					$row['last_status'],
					$row['last_error'],
					$row['last_sync_at'],
					$row['date_upd'],
					$idProduct,
				]
			);

			return;
		}

		\DB::execute(
			'INSERT INTO frisay_products
			 (id_product, remote_id, stock_code, barcode, sale_price, quantity, last_status, last_error, last_sync_at, date_add, date_upd)
			 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			[
				$idProduct,
				$row['remote_id'],
				$row['stock_code'],
				$row['barcode'],
				$row['sale_price'],
				$row['quantity'],
				$row['last_status'],
				$row['last_error'],
				$row['last_sync_at'],
				$now,
				$now,
			]
		);
	}

	/**
	 * @param array<string, mixed> $remote
	 * @return array<string, mixed>
	 */
	private static function normalizeRemoteProduct(array $remote): array
	{
		$name = trim((string) ($remote['name'] ?? ($remote['product_name'] ?? '')));
		$categoryName = trim((string) (
			$remote['category']
			?? $remote['category_name']
			?? ''
		));
		$brandName = trim((string) (
			$remote['brand']
			?? $remote['brand_name']
			?? ''
		));

		return [
			'remote_id' => (int) ($remote['id'] ?? ($remote['id_product'] ?? 0)),
			'product_name' => $name,
			'stock_code' => trim((string) ($remote['stock_code'] ?? '')),
			'barcode' => trim((string) ($remote['barcode'] ?? '')),
			'price' => (float) ($remote['price'] ?? 0),
			'old_price' => (float) ($remote['old_price'] ?? ($remote['list_price'] ?? 0)),
			'stock' => (int) ($remote['stock'] ?? 0),
			'short_description' => (string) ($remote['short_description'] ?? ''),
			'description' => (string) ($remote['description'] ?? ''),
			'category_name' => $categoryName,
			'brand_name' => $brandName,
			'active' => !empty($remote['active']) ? 1 : 0,
		];
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private static function mappingFromData(array $data, string $status): array
	{
		return [
			'remote_id' => (int) ($data['remote_id'] ?? 0),
			'stock_code' => (string) ($data['stock_code'] ?? ''),
			'barcode' => (string) ($data['barcode'] ?? ''),
			'sale_price' => (float) ($data['price'] ?? 0),
			'quantity' => (int) ($data['stock'] ?? 0),
			'last_status' => $status,
			'last_error' => '',
			'last_sync_at' => date('Y-m-d H:i:s'),
		];
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private static function findLocalProductId(array $data): int
	{
		$stockCode = trim((string) ($data['stock_code'] ?? ''));
		$barcode = trim((string) ($data['barcode'] ?? ''));

		if ($stockCode !== '') {
			$id = (int) (\DB::getValue('SELECT id_product FROM products WHERE stock_code = ? LIMIT 1', [$stockCode]) ?: 0);

			if ($id > 0) {
				return $id;
			}
		}

		if ($barcode !== '') {
			$id = (int) (\DB::getValue('SELECT id_product FROM products WHERE barcode = ? LIMIT 1', [$barcode]) ?: 0);

			if ($id > 0) {
				return $id;
			}
		}

		return 0;
	}

	/**
	 * @param array<string, mixed> $product
	 * @return array<string, mixed>
	 */
	private static function buildRemoteFields(array $product): array
	{
		$cat = \Category::getByIdAdmin((int) ($product['id_category'] ?? 0));
		$brand = \Brand::getByIdAdmin((int) ($product['id_brand'] ?? 0));
		$stock = max(0, (int) \Product::getStock($product));

		return [
			'name' => (string) ($product['product_name'] ?? ''),
			'category' => trim((string) ($cat['category_name'] ?? '')) ?: 'Genel',
			'brand' => trim((string) ($brand['brand_name'] ?? '')) ?: 'Genel',
			'price' => (float) ($product['price'] ?? 0),
			'old_price' => (float) ($product['old_price'] ?? 0),
			'stock' => $stock,
			'active' => !empty($product['active']) ? 1 : 0,
			'barcode' => (string) ($product['barcode'] ?? ''),
			'stock_code' => (string) ($product['stock_code'] ?? ''),
			'short_description' => (string) ($product['short_description'] ?? ''),
			'description' => (string) ($product['description'] ?? ''),
		];
	}

	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options
	 * @return array{success: bool, message?: string, id_product?: int}
	 */
	private static function createShopProduct(array $data, array $options): array
	{
		$categoryResult = self::resolveOrCreateCategoryId((string) ($data['category_name'] ?? 'FriSay'));

		if (!$categoryResult['success']) {
			return ['success' => false, 'message' => (string) $categoryResult['message']];
		}

		$brandResult = self::resolveOrCreateBrandId((string) ($data['brand_name'] ?? 'FriSay'));

		if (!$brandResult['success']) {
			return ['success' => false, 'message' => (string) $brandResult['message']];
		}

		$name = trim((string) ($data['product_name'] ?? ''));

		if ($name === '') {
			return ['success' => false, 'message' => 'Uzak ürün adı boş'];
		}

		$result = \Product::save([
			'product_name' => $name,
			'short_description' => (string) ($data['short_description'] ?? ''),
			'description' => (string) ($data['description'] ?? ''),
			'id_category' => (int) ($categoryResult['id'] ?? 0),
			'id_brand' => (int) ($brandResult['id'] ?? 0),
			'barcode' => (string) ($data['barcode'] ?? ''),
			'stock_code' => (string) ($data['stock_code'] ?? ''),
			'price' => (float) ($data['price'] ?? 0),
			'old_price' => (float) ($data['old_price'] ?? 0),
			'stock' => (int) ($data['stock'] ?? 0),
			'active' => (int) ($data['active'] ?? 1),
		]);

		if (empty($result['success'])) {
			return ['success' => false, 'message' => (string) ($result['message'] ?? 'Ürün kaydedilemedi')];
		}

		return [
			'success' => true,
			'id_product' => (int) ($result['id'] ?? ($result['id_product'] ?? 0)),
			'message' => (string) ($result['message'] ?? 'Ürün eklendi'),
		];
	}

	/** @return array{success: bool, id?: int, message?: string} */
	private static function resolveOrCreateCategoryId(string $name): array
	{
		$name = trim($name);

		if ($name === '') {
			$name = 'FriSay';
		}

		$id = (int) \DB::getValue(
			'SELECT id_category FROM categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			return ['success' => true, 'id' => $id];
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
			return ['success' => false, 'message' => (string) ($result['message'] ?? 'Kategori oluşturulamadı')];
		}

		return ['success' => true, 'id' => (int) ($result['id'] ?? ($result['id_category'] ?? 0))];
	}

	/** @return array{success: bool, id?: int, message?: string} */
	private static function resolveOrCreateBrandId(string $name): array
	{
		$name = trim($name);

		if ($name === '') {
			$name = 'FriSay';
		}

		$id = (int) \DB::getValue(
			'SELECT id_brand FROM brands WHERE LOWER(brand_name) = LOWER(?) LIMIT 1',
			[$name]
		);

		if ($id > 0) {
			return ['success' => true, 'id' => $id];
		}

		$result = \Brand::save([
			'brand_name' => $name,
			'active' => 1,
		]);

		if (empty($result['success'])) {
			return ['success' => false, 'message' => (string) ($result['message'] ?? 'Marka oluşturulamadı')];
		}

		return ['success' => true, 'id' => (int) ($result['id'] ?? ($result['id_brand'] ?? 0))];
	}
}
