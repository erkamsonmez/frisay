<?php

namespace Prestashop;

class OrderService
{
	private const CANCEL_HINTS = [
		'cancel', 'cancelled', 'canceled', 'refund', 'refunded',
		'iptal', 'iade', 'payment_error',
	];

	/**
	 * @return array{ok: bool, message: string, count?: int, stock_updates?: int, orders?: array<int, array<string, mixed>>}
	 */
	public static function syncOrders(?string $start = null, ?string $end = null): array
	{
		ProductSyncService::ensureSchema();

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'PrestaShop API ayarları tanımlı değil'];
		}

		$orders = ProductSyncService::api()->getOrders($start, $end);
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
			'message' => $count . ' PrestaShop siparişi senkronize edildi'
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
			return ['ok' => false, 'message' => 'PrestaShop API ayarları tanımlı değil'];
		}

		$detail = ProductSyncService::api()->getOrderByReference($orderNumber);

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

	/** @return array<int, array<string, mixed>> */
	public static function getRecent(int $limit = 50): array
	{
		return \MarketplaceTables::getRecentOrders('prestashop', $limit);
	}

	/**
	 * @param array<string, mixed> $order
	 * @return int stock movement; -1 skipped cancelled new
	 */
	private static function upsertOrder(array $order, string $now): int
	{
		$orderNumber = trim((string) ($order['order_number'] ?? ''));
		$packageId = (string) ((int) ($order['order_id'] ?? 0));

		if ($orderNumber === '') {
			$orderNumber = $packageId;
		}

		if ($orderNumber === '' || $orderNumber === '0') {
			return 0;
		}

		$customer = is_array($order['customer'] ?? null) ? $order['customer'] : [];
		$customerName = trim((string) ($customer['firstname'] ?? '') . ' ' . (string) ($customer['lastname'] ?? ''));
		$status = (string) ($order['status'] ?? '');
		$existing = \MarketplaceTables::findOrder('prestashop', $orderNumber, $packageId);
		$isCancelled = self::isCancelStatus($status);

		if ($isCancelled && !$existing) {
			return -1;
		}

		$lines = [];

		foreach ($order['products'] ?? [] as $item) {
			if (!is_array($item)) {
				continue;
			}

			$stockCode = trim((string) ($item['stock_code'] ?? ''));
			$barcode = trim((string) ($item['barcode'] ?? ''));
			$psProductId = (int) ($item['product_id'] ?? 0);
			$idProduct = self::findProductId($stockCode, $barcode, $psProductId);

			if ($idProduct > 0 && $psProductId > 0) {
				$mapping = ProductSyncService::findMapping($idProduct);

				if (!ProductSyncService::isLinked($mapping)) {
					ProductSyncService::linkExistingProduct($idProduct, (string) $psProductId);
				}
			}

			$lines[] = [
				'productName' => (string) ($item['product_name'] ?? ''),
				'product_name' => (string) ($item['product_name'] ?? ''),
				'stockCode' => $stockCode,
				'barcode' => $barcode,
				'quantity' => (int) ($item['quantity'] ?? 0),
				'salePrice' => (float) ($item['price'] ?? 0),
				'price' => (float) ($item['price'] ?? 0),
				'id_product' => $idProduct,
				'ps_product_id' => $psProductId,
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

		$orderDate = null;
		$rawDate = (string) ($order['order_date'] ?? '');

		if ($rawDate !== '') {
			$ts = strtotime($rawDate);
			$orderDate = $ts ? date('Y-m-d H:i:s', $ts) : null;
		}

		$delivery = is_array($order['delivery_address'] ?? null) ? $order['delivery_address'] : [];
		if ($customerName === '') {
			$customerName = trim((string) ($delivery['firstname'] ?? '') . ' ' . (string) ($delivery['lastname'] ?? ''));
		}

		$tracking = trim((string) ($order['cargo_tracking_number'] ?? ($order['shipping_number'] ?? '')));
		$provider = trim((string) ($order['cargo_provider'] ?? ''));
		if ($provider === '' && is_array($order['carrier'] ?? null)) {
			$provider = trim((string) ($order['carrier']['name'] ?? ''));
		}
		$trackLink = '';
		if ($tracking !== '' && class_exists('Cargo')) {
			$trackLink = (string) \Cargo::buildTrackingUrl($tracking, $provider);
		}

		$row = [
			'order_number' => $orderNumber,
			'shipment_package_id' => $packageId,
			'status' => $status,
			'customer_name' => mb_substr($customerName, 0, 255),
			'total_price' => (float) ($order['total'] ?? 0),
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
		$orderId = \MarketplaceTables::upsertOrder('prestashop', $row, true);

		if ($orderId <= 0) {
			return 0;
		}

		if ($isNew) {
			\MarketplaceLog::newOrder('prestashop', $orderNumber, $lines);
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

	private static function findProductId(string $stockCode, string $barcode, int $psProductId): int
	{
		if ($psProductId > 0) {
			$map = ProductSyncService::findByPsId($psProductId);

			if ($map && (int) ($map['id_product'] ?? 0) > 0) {
				return (int) $map['id_product'];
			}
		}

		if ($stockCode !== '') {
			$map = \DB::getRowSafe('prestashop_products', 'reference = ?', [$stockCode]);

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
					'prestashop',
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

	/**
	 * @param array<string, mixed> $order
	 * @param array<int, mixed> $lines
	 * @return array{ok: bool, message: string}
	 */
	public static function cancelOnMarketplace(array $order, array $lines): array
	{
		unset($order, $lines);

		return [
			'ok' => true,
			'message' => 'Sipariş yerelde iptal edildi (PrestaShop sipariş durumu değişmez)',
		];
	}
}
