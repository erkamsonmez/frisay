<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class AkakceFeedService
{
	public const PANEL_URL = 'https://www.akakce.com/';

	private const CACHE_FILE = 'akakce_feed.xml';

	public static function cachePath(): string
	{
		return dirname(__DIR__, 3) . '/cache/' . self::CACHE_FILE;
	}

	public static function isEnabled(): bool
	{
		return Settings::get('AKAKCE_ENABLED') === '1';
	}

	public static function getFeedToken(): string
	{
		return trim((string) Settings::get('AKAKCE_FEED_TOKEN'));
	}

	public static function buildFeedUrl(string $domain): string
	{
		$token = self::getFeedToken();

		return rtrim($domain, '/') . '/api/module.php?m=akakce&action=feed&token=' . rawurlencode($token);
	}

	public static function ttlSeconds(): int
	{
		return max(60, (int) (Settings::get('AKAKCE_CACHE_TTL') ?: 360) * 60);
	}

	/** @return array{exists:bool,ttl:int,age:int,mtime:int,expired:bool,remaining:int,last_regen:string} */
	public static function cacheInfo(): array
	{
		$path = self::cachePath();
		$ttl = self::ttlSeconds();
		$exists = is_file($path);
		$mtime = $exists ? (int) filemtime($path) : 0;
		$age = ($exists && $mtime > 0) ? max(0, time() - $mtime) : 0;
		$expired = !$exists || $age >= $ttl;

		return [
			'exists' => $exists,
			'ttl' => $ttl,
			'age' => $age,
			'mtime' => $mtime,
			'expired' => $expired,
			'remaining' => $expired ? 0 : ($ttl - $age),
			'last_regen' => (string) (Settings::get('AKAKCE_LAST_REGEN') ?: ''),
		];
	}

	public static function buildFeed(): string
	{
		$cachePath = self::cachePath();
		$info = self::cacheInfo();

		if ($info['exists'] && !$info['expired']) {
			return (string) file_get_contents($cachePath);
		}

		$xml = self::generateXml();
		$dir = dirname($cachePath);

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		file_put_contents($cachePath, $xml);
		Settings::set('AKAKCE_LAST_REGEN', date('d.m.Y H:i'));

		return $xml;
	}

	public static function regenerate(): array
	{
		@unlink(self::cachePath());
		$xml = self::buildFeed();
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$count = $dom->getElementsByTagName('product')->length;

		return [
			'success' => true,
			'refreshed' => true,
			'message' => 'Feed yenilendi. ' . $count . ' ürün işlendi.',
			'product_count' => $count,
			'generated_at' => Settings::get('AKAKCE_LAST_REGEN') ?: date('d.m.Y H:i'),
		];
	}

	public static function refreshIfExpired(): array
	{
		$info = self::cacheInfo();

		if ($info['exists'] && !$info['expired']) {
			return [
				'success' => true,
				'refreshed' => false,
				'message' => 'Önbellek hâlâ geçerli',
				'age' => $info['age'],
				'ttl' => $info['ttl'],
				'generated_at' => $info['last_regen'],
			];
		}

		return self::regenerate();
	}

	/** @return array{success: bool, total: int, preview: array<int, array<string, string>>, generated_at: string} */
	public static function preview(int $limit = 5): array
	{
		$xml = self::generateXml();
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$items = $dom->getElementsByTagName('product');
		$preview = [];
		$max = min($limit, $items->length);

		for ($i = 0; $i < $max; $i++) {
			$item = $items->item($i);
			$row = [];

			if (!$item) {
				continue;
			}

			foreach ($item->childNodes as $child) {
				if ($child->nodeType !== XML_ELEMENT_NODE) {
					continue;
				}

				$row[$child->localName] = $child->nodeValue;
			}

			$preview[] = $row;
		}

		return [
			'success' => true,
			'total' => $items->length,
			'preview' => $preview,
			'generated_at' => date('d.m.Y H:i:s'),
		];
	}

	public static function generateXml(): string
	{
		$domain = rtrim((string) Settings::get('DOMAIN'), '/');
		$inclOutstock = Settings::get('AKAKCE_INCLUDE_OUTSTOCK') === '1';
		$includeVariations = Settings::get('AKAKCE_INCLUDE_VARIATIONS') !== '0';
		$excludeCatIds = array_filter(
			array_map('intval', explode(',', (string) Settings::get('AKAKCE_EXCLUDE_CATS')))
		);
		$expressHour = self::expressDeliveryTime();
		$cargoId = (int) Settings::get('AKAKCE_CARGO_ID');
		$skuMode = Settings::get('AKAKCE_SKU_FIELD') === 'id' ? 'id' : 'stock_code';
		$useProductCargoDay = Settings::get('AKAKCE_USE_PRODUCT_CARGO_DAY') === '1';
		$defaultDay = max(0, (int) Settings::get('AKAKCE_DAY_OF_DELIVERY'));

		$products = self::fetchProducts($excludeCatIds);
		$items = [];

		foreach ($products as $p) {
			$variations = [];

			if ($includeVariations && class_exists('ProductVariation')) {
				$idProduct = (int) ($p['id_product'] ?? 0);
				if ($idProduct > 0 && ProductVariation::hasVariations($idProduct)) {
					$variations = ProductVariation::getByProduct($idProduct, true);
				}
			}

			if ($variations !== []) {
				foreach ($variations as $variation) {
					$items[] = self::buildItemFromProduct($p, $variation, $domain, $skuMode, $useProductCargoDay, $defaultDay, $expressHour, $cargoId);
				}
			} else {
				$items[] = self::buildItemFromProduct($p, null, $domain, $skuMode, $useProductCargoDay, $defaultDay, $expressHour, $cargoId);
			}
		}

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElement('products');
		$dom->appendChild($root);

		foreach ($items as $item) {
			if ($item === null) {
				continue;
			}

			if (!$inclOutstock && (int) $item['quantity'] <= 0) {
				continue;
			}

			$node = $dom->createElement('product');
			self::addText($dom, $node, 'sku', $item['sku']);
			self::addCdata($dom, $node, 'name', $item['name']);
			self::addCdata($dom, $node, 'url', $item['url']);
			self::addCdata($dom, $node, 'imgUrl', $item['imgUrl']);
			self::addText($dom, $node, 'price', $item['price']);
			self::addText($dom, $node, 'shipPrice', $item['shipPrice']);
			self::addText($dom, $node, 'dayOfDelivery', $item['dayOfDelivery']);
			self::addText($dom, $node, 'expressDeliveryTime', $item['expressDeliveryTime']);
			self::addText($dom, $node, 'quantity', $item['quantity']);
			self::addCdata($dom, $node, 'productBrand', $item['productBrand']);
			self::addCdata($dom, $node, 'productCategory', $item['productCategory']);
			self::addText($dom, $node, 'barcode', $item['barcode']);
			$root->appendChild($node);
		}

		return $dom->saveXML();
	}

	public static function expressDeliveryTime(): int
	{
		$raw = Settings::get('AKAKCE_EXPRESS_HOUR');

		if ($raw === '' || $raw === null) {
			return 16;
		}

		$hour = (int) $raw;

		if ($hour < 0) {
			$hour = 0;
		}

		if ($hour > 23) {
			$hour = 23;
		}

		return $hour;
	}

	/**
	 * Ürün fiyatına göre varsayılan (veya seçilen) kargo diliminden ücret.
	 */
	public static function shipPriceForAmount(float $amount, int $cargoId = 0, bool $isVirtual = false): float
	{
		if ($isVirtual) {
			return 0.0;
		}

		if (!class_exists('Cargo')) {
			return 0.0;
		}

		$fee = Cargo::getFeeForAmount($amount, $cargoId > 0 ? $cargoId : null);

		return $fee === null ? 0.0 : max(0.0, (float) $fee);
	}

	/**
	 * @param array<string, mixed> $product
	 * @param array<string, mixed>|null $variation
	 * @return array<string, string>|null
	 */
	private static function buildItemFromProduct(
		array $product,
		?array $variation,
		string $domain,
		string $skuMode,
		bool $useProductCargoDay,
		int $defaultDay,
		int $expressHour,
		int $cargoId
	): ?array {
		$name = trim((string) ($product['product_name'] ?? ''));

		if ($name === '') {
			return null;
		}

		$price = (float) ($product['price'] ?? 0);
		$qty = (int) round((float) ($product['stock'] ?? 0));
		$barcode = trim((string) ($product['barcode'] ?? ''));
		$sku = '';

		if ($skuMode === 'id') {
			$sku = (string) (int) ($product['id_product'] ?? 0);
		} else {
			$sku = trim((string) ($product['stock_code'] ?? ''));
			if ($sku === '') {
				$sku = (string) (int) ($product['id_product'] ?? 0);
			}
		}

		if ($variation !== null) {
			$label = ProductVariation::formatLabel($variation);
			if ($label !== '') {
				$name .= ' ' . $label;
			}
			$price = ProductVariation::getEffectivePrice($variation, $price);
			$qty = (int) round((float) ($variation['stock'] ?? 0));
			$varBarcode = trim((string) ($variation['barcode'] ?? ''));
			if ($varBarcode !== '') {
				$barcode = $varBarcode;
			}
			$varSku = trim((string) ($variation['sku'] ?? ''));
			if ($varSku !== '') {
				$sku = $varSku;
			} elseif ($skuMode !== 'id') {
				$sku = $sku . '-' . (int) ($variation['id_variation'] ?? 0);
			} else {
				$sku = (string) (int) ($product['id_product'] ?? 0) . '-' . (int) ($variation['id_variation'] ?? 0);
			}
		}

		if ($sku === '') {
			$sku = (string) (int) ($product['id_product'] ?? 0);
		}

		$isVirtual = !empty($product['is_virtual']);
		$ship = self::shipPriceForAmount($price, $cargoId, $isVirtual);
		$day = $useProductCargoDay
			? max(0, (int) ($product['cargo_day'] ?? $defaultDay))
			: $defaultDay;

		$brand = trim((string) ($product['brand_name'] ?? ''));
		if ($brand === '') {
			$brand = trim((string) Settings::get('AKAKCE_BRAND_FALLBACK'));
		}

		return [
			'sku' => mb_substr($sku, 0, 64),
			'name' => mb_substr($name, 0, 200),
			'url' => self::toAbsoluteUrl($domain, (string) ($product['url'] ?? '')),
			'imgUrl' => self::toAbsoluteUrl($domain, (string) ($product['image_url'] ?? '')),
			'price' => number_format($price, 2, '.', ''),
			'shipPrice' => number_format($ship, 2, '.', ''),
			'dayOfDelivery' => (string) $day,
			'expressDeliveryTime' => (string) $expressHour,
			'quantity' => (string) max(0, $qty),
			'productBrand' => $brand,
			'productCategory' => trim((string) ($product['category_name'] ?? '')),
			'barcode' => $barcode,
		];
	}

	/** @return array<int, array<string, mixed>> */
	private static function fetchProducts(array $excludeCatIds): array
	{
		$sql = '
			SELECT
				p.id_product,
				p.product_name,
				p.product_link,
				p.price,
				p.stock,
				p.barcode,
				p.stock_code,
				p.cargo_day,
				p.product_type,
				p.id_category,
				c.category_link,
				c.category_name,
				b.brand_name,
				i.id_image
			FROM products p
			LEFT JOIN categories c ON c.id_category = p.id_category
			LEFT JOIN brands b ON b.id_brand = p.id_brand
			LEFT JOIN images i ON i.id_product = p.id_product AND i.cover = 1
			WHERE p.active = 1
		';

		if ($excludeCatIds !== []) {
			$placeholders = implode(',', array_fill(0, count($excludeCatIds), '?'));
			$sql .= ' AND p.id_category NOT IN (' . $placeholders . ')';
		}

		$sql .= ' ORDER BY p.id_product ASC';

		$rows = DB::execute($sql, $excludeCatIds !== [] ? array_values($excludeCatIds) : []);

		if (!is_array($rows)) {
			return [];
		}

		return array_map(static function (array $row): array {
			return Product::enrich($row);
		}, $rows);
	}

	private static function addText(DOMDocument $dom, DOMNode $parent, string $tag, string $value): void
	{
		$node = $dom->createElement($tag);
		$node->appendChild($dom->createTextNode($value));
		$parent->appendChild($node);
	}

	private static function addCdata(DOMDocument $dom, DOMNode $parent, string $tag, string $value): void
	{
		$node = $dom->createElement($tag);
		$node->appendChild($dom->createCDATASection($value));
		$parent->appendChild($node);
	}

	private static function toAbsoluteUrl(string $domain, string $value): string
	{
		$value = trim($value);

		if ($value === '') {
			return rtrim($domain, '/') . '/';
		}

		if (preg_match('~^https?://~i', $value)) {
			return $value;
		}

		return rtrim($domain, '/') . '/' . ltrim($value, '/');
	}
}
