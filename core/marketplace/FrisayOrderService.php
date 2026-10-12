<?php

namespace Frisay;

class OrderService
{
	private const CANCEL_HINTS = [
		'cancel', 'cancelled', 'canceled', 'refund', 'refunded',
		'iptal', 'iade',
	];

	/**
	 * @return array{ok: bool, message: string, count?: int, stock_updates?: int, orders?: array<int, array<string, mixed>>}
	 */
	public static function syncOrders(?string $start = null, ?string $end = null): array
	{
		ProductSyncService::ensureSchema();

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'FriSay API ayarları tanımlı değil'];
		}

		$api = ProductSyncService::api();
		$orders = [];
		$page = 0;

		do {
			$chunk = $api->getOrders($start, $end, $page, 50);
			$orders = array_merge($orders, $chunk);
			$page++;
		} while (count($chunk) >= 50 && $page < 10);

		$count = 0;
		$stockUpdates = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($orders as $order) {
			if (!is_array($order)) {
				continue;
			}

			$moved = self::upsertOrder($order, $now);

			if ($moved < 0) {
				continue;
			}

			$stockUpdates += $moved;
			$count++;
		}

		return [
			'ok' => true,
			'message' => $count . ' FriSay siparişi senkronize edildi'
				. ($stockUpdates > 0 ? (', ' . $stockUpdates . ' stok hareketi') : ''),
			'count' => $count,
			'stock_updates' => $stockUpdates,
			'orders' => self::getRecent(50),
		];
	}

	/**
	 * @return array{ok: bool, message: string, count?: int}
	 */
	public static function importByOrderNumber(string $orderNumber): array
	{
		ProductSyncService::ensureSchema();

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'FriSay API ayarları tanımlı değil'];
		}

		$orderNumber = trim($orderNumber);
		$detail = null;

		if (preg_match('/^\d+$/', $orderNumber)) {
			$detail = ProductSyncService::api()->getOrder((int) $orderNumber);
		}

		if (!$detail) {
			$chunk = ProductSyncService::api()->getOrders(null, null, 0, 100);

			foreach ($chunk as $order) {
				if (!is_array($order)) {
					continue;
				}

				$num = trim((string) ($order['orderNumber'] ?? ($order['id'] ?? '')));

				if (strcasecmp($num, $orderNumber) === 0) {
					$detail = $order;
					break;
				}
			}
		}

		if (!$detail || !is_array($detail)) {
			return ['ok' => false, 'message' => 'Sipariş bulunamadı'];
		}

		$moved = self::upsertOrder($detail, date('Y-m-d H:i:s'));

		if ($moved < 0) {
			return ['ok' => false, 'message' => 'İptal sipariş ' . \MarketplaceOrderOps::shopName() . '\'ta yok; içe aktarılmadı'];
		}

		return [
			'ok' => true,
			'message' => 'Sipariş içe aktarıldı/güncellendi'
				. ($moved > 0 ? (', ' . $moved . ' stok hareketi') : ''),
			'count' => 1,
		];
	}

	/**
	 * @param array<string, mixed> $order
	 * @param array<int, mixed> $lines
	 * @return array{ok: bool, message: string}
	 */
	public static function cancelOnMarketplace(array $order, array $lines): array
	{
		unset($lines);

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'FriSay API ayarları tanımlı değil'];
		}

		$remoteId = (int) ($order['shipment_package_id'] ?? 0);

		if ($remoteId <= 0) {
			return ['ok' => false, 'message' => 'Uzak sipariş ID yok'];
		}

		$result = ProductSyncService::api()->updateOrder($remoteId, ['status' => 5]);

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Uzak mağazada iptal başarısız')];
		}

		return ['ok' => true, 'message' => 'Uzak FriSay siparişi iptal edildi'];
	}

	/** @return array<int, array<string, mixed>> */
	public static function getRecent(int $limit = 50): array
	{
		return \MarketplaceTables::getRecentOrders('frisay', $limit);
	}

	/**
	 * @param array<string, mixed> $order
	 * @return int stock movement; -1 skipped cancelled new
	 */
	private static function upsertOrder(array $order, string $now): int
	{
		$orderNumber = trim((string) ($order['orderNumber'] ?? ''));
		$packageId = (string) ((int) ($order['id'] ?? 0));

		if ($orderNumber === '') {
			$orderNumber = $packageId;
		}

		if ($orderNumber === '' || $orderNumber === '0') {
			return 0;
		}

		$customerName = trim(
			(string) ($order['customerFirstName'] ?? '') . ' ' . (string) ($order['customerLastName'] ?? '')
		);
		$status = (string) ($order['statusLabel'] ?? ($order['status'] ?? ''));
		$existing = \MarketplaceTables::findOrder('frisay', $orderNumber, $packageId);
		$isCancelled = self::isCancelStatus($status) || (int) ($order['statusCode'] ?? 0) === 5;

		if ($isCancelled && !$existing) {
			return -1;
		}

		$lines = [];

		foreach ($order['lines'] ?? [] as $item) {
			if (!is_array($item)) {
				continue;
			}

			$stockCode = trim((string) ($item['stockCode'] ?? ''));
			$barcode = trim((string) ($item['barcode'] ?? ''));
			$remoteProductId = (int) ($item['contentId'] ?? ($item['id_product'] ?? 0));
			$idProduct = self::findProductId($stockCode, $barcode, $remoteProductId);

			if ($idProduct > 0 && $remoteProductId > 0) {
				$mapping = ProductSyncService::findMapping($idProduct);

				if (!ProductSyncService::isLinked($mapping)) {
					ProductSyncService::linkExistingProduct($idProduct, (string) $remoteProductId);
				}
			}

			$lines[] = [
				'productName' => (string) ($item['productName'] ?? ''),
				'product_name' => (string) ($item['productName'] ?? ''),
				'stockCode' => $stockCode,
				'barcode' => $barcode,
				'quantity' => (int) ($item['quantity'] ?? 0),
				'salePrice' => (float) ($item['lineUnitPrice'] ?? ($item['lineAmount'] ?? 0)),
				'price' => (float) ($item['lineUnitPrice'] ?? ($item['lineAmount'] ?? 0)),
				'id_product' => $idProduct,
				'remote_id' => $remoteProductId,
				'productImage' => (string) ($item['image'] ?? ''),
				'image' => (string) ($item['image'] ?? ''),
			];
		}

		$idProduct = 0;

		foreach ($lines as $line) {
			if ((int) ($line['id_product'] ?? 0) > 0) {
				$idProduct = (int) $line['id_product'];
				break;
			}
		}

		$orderDate = self::parseOrderDate($order);
		$shipment = is_array($order['shipmentAddress'] ?? null) ? $order['shipmentAddress'] : [];

		if ($customerName === '') {
			$customerName = trim((string) ($shipment['firstName'] ?? '') . ' ' . (string) ($shipment['lastName'] ?? ''));
		}

		$tracking = trim((string) ($order['trackingNumber'] ?? ''));
		$provider = trim((string) ($order['cargoCompany'] ?? ''));
		$trackLink = '';

		if ($tracking !== '' && class_exists('Cargo')) {
			$trackLink = (string) \Cargo::buildTrackingUrl($tracking, $provider);
		}

		$row = [
			'order_number' => $orderNumber,
			'shipment_package_id' => $packageId,
			'status' => $status,
			'customer_name' => mb_substr($customerName, 0, 255),
			'total_price' => (float) ($order['packageTotalPrice'] ?? 0),
			'cargo_tracking_number' => $tracking,
			'cargo_tracking_link' => $trackLink,
			'cargo_provider' => $provider,
			'id_product' => $idProduct,
			'lines_json' => json_encode($lines, JSON_UNESCAPED_UNICODE),
			'raw_json' => json_encode($order, JSON_UNESCAPED_UNICODE),
			'order_date' => $orderDate,
			'last_sync_at' => $now,
		];

		$stockDeducted = (int) ($existing['stock_deducted'] ?? 0);
		$moved = 0;
		$isNew = !$existing;
		$orderId = \MarketplaceTables::upsertOrder('frisay', $row, true);

		if ($orderId <= 0) {
			return 0;
		}

		if ($isNew) {
			\MarketplaceLog::newOrder('frisay', $orderNumber, $lines);
		}

		if (!$isCancelled && $stockDeducted === 0) {
			$moved = self::applyLineStock($lines, false, $orderNumber);
			\MarketplaceTables::updateOrderById($orderId, ['stock_deducted' => 1, 'last_sync_at' => $now]);
		}

		if ($isCancelled && $stockDeducted === 1) {
			$moved = self::applyLineStock($lines, true, $orderNumber);
			\MarketplaceTables::updateOrderById($orderId, ['stock_deducted' => 2, 'last_sync_at' => $now]);
		}

		return $moved;
	}

	/** @param array<string, mixed> $order */
	private static function parseOrderDate(array $order): ?string
	{
		$ms = (int) ($order['orderDate'] ?? 0);

		if ($ms > 0) {
			return date('Y-m-d H:i:s', (int) floor($ms / 1000));
		}

		$raw = trim((string) ($order['order_date'] ?? ''));

		if ($raw === '') {
			return null;
		}

		$ts = strtotime($raw);

		return $ts ? date('Y-m-d H:i:s', $ts) : null;
	}

	private static function isCancelStatus(string $status): bool
	{
		$key = strtolower(preg_replace('/[\s_\-]+/', '', $status) ?: '');

		if ($key === '') {
			return false;
		}

		foreach (self::CANCEL_HINTS as $hint) {
			$hintKey = strtolower(preg_replace('/[\s_\-]+/', '', $hint) ?: '');

			if ($hintKey !== '' && strpos($key, $hintKey) !== false) {
				return true;
			}
		}

		return \MarketplaceOrderOps::isCancelStatus($status);
	}

	private static function findProductId(string $stockCode, string $barcode, int $remoteId): int
	{
		if ($remoteId > 0) {
			$map = ProductSyncService::findByRemoteId($remoteId);

			if ($map && (int) ($map['id_product'] ?? 0) > 0) {
				return (int) $map['id_product'];
			}
		}

		if ($stockCode !== '') {
			$map = \DB::getRowSafe('frisay_products', 'stock_code = ?', [$stockCode]);

			if ($map && (int) ($map['id_product'] ?? 0) > 0) {
				return (int) $map['id_product'];
			}

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
	 * @param array<int, array<string, mixed>> $lines
	 */
	private static function applyLineStock(array $lines, bool $restore, string $orderNumber = ''): int
	{
		$moved = 0;

		foreach ($lines as $line) {
			$qty = max(0, (int) ($line['quantity'] ?? 0));
			$idProduct = (int) ($line['id_product'] ?? 0);

			if ($qty <= 0 || $idProduct <= 0) {
				continue;
			}

			$product = \Product::getByIdAdmin($idProduct);
			$oldStock = $product ? \Product::getStock($product) : 0;
			$ref = trim((string) ($product['stock_code'] ?? ($line['stockCode'] ?? '')));
			$ok = false;

			if ($restore) {
				\Product::increaseStock($idProduct, $qty);
				$ok = true;
				$moved++;
			} elseif (\Product::decreaseStock($idProduct, $qty)) {
				$ok = true;
				$moved++;
			}

			if ($ok) {
				$newStock = $restore ? ($oldStock + $qty) : max(0, $oldStock - $qty);
				\MarketplaceLog::stockChange(
					'frisay',
					$ref !== '' ? $ref : (string) $idProduct,
					$oldStock,
					$newStock,
					$restore ? ('ORDER_CANCEL [' . $orderNumber . ']') : ('ORDER [' . $orderNumber . ']'),
					$idProduct,
					$orderNumber
				);
			}
		}

		return $moved;
	}
}
