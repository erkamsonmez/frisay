<?php

namespace Trendyol;

/**
 * Trendyol Satıcı API — Ürün / Stok / Fiyat / Sipariş / Soru-Cevap
 * Doküman: https://developers.trendyol.com
 */
class TrendyolApi
{
	/** Türkiye vitrini — V2 kategori/ürün isteklerinde zorunlu header. */
	public const STOREFRONT_CODE = 'TR';

	private string $supplierId;
	private string $apiKey;
	private string $apiSecret;
	private string $baseUri;

	/** @var array<int, array<string, mixed>> */
	private array $attributeListCache = [];

	public function __construct(string $supplierId, string $apiKey, string $apiSecret)
	{
		$this->supplierId = trim($supplierId);
		$this->apiKey = trim($apiKey);
		$this->apiSecret = trim($apiSecret);
		$this->baseUri = 'https://apigw.trendyol.com/integration/';
	}

	private function ordersEndpoint(): string
	{
		return "order/sellers/{$this->supplierId}/v2/orders";
	}

	private function productsV2Endpoint(): string
	{
		return "product/sellers/{$this->supplierId}/v2/products";
	}

	public function isConfigured(): bool
	{
		return $this->supplierId !== '' && $this->apiKey !== '' && $this->apiSecret !== '';
	}

	public function getSupplierId(): string
	{
		return $this->supplierId;
	}

	public function getOrders()
	{
		$status = 'Created,Picking,Invoiced,Shipped,Cancelled,Delivered,UnDelivered,Returned,AtCollectionPoint,UnPacked,UnSupplied';
		$endpoint = $this->ordersEndpoint() . "?status={$status}&orderByField=PackageLastModifiedDate&orderByDirection=DESC&size=200";

		return $this->request('GET', $endpoint);
	}

	public function getOrderDetail($idOrder)
	{
		$endpoint = $this->ordersEndpoint() . '?orderNumber=' . rawurlencode((string) $idOrder);

		return $this->request('GET', $endpoint);
	}

	/**
	 * Tedarik edememe / paket iptali
	 *
	 * @param array<int, array{lineId: int|string, quantity: int}> $lines
	 * @return array<string, mixed>|null
	 */
	public function cancelPackageItems(string $packageId, array $lines, int $reasonId = 500): ?array
	{
		$packageId = trim($packageId);

		if ($packageId === '' || $lines === []) {
			return [
				'success' => false,
				'message' => 'Paket veya satır bilgisi eksik',
			];
		}

		$endpoint = "order/sellers/{$this->supplierId}/shipment-packages/"
			. rawurlencode($packageId)
			. '/items/unsupplied';

		return $this->request('PUT', $endpoint, [
			'lines' => array_values($lines),
			'reasonId' => $reasonId,
		]);
	}

	public function getReturnOrders($claimItemStatus = null, $page = 0, $size = 50, $startDate = null, $endDate = null)
	{
		$params = [
			'page' => (int) $page,
			'size' => min(200, max(1, (int) $size)),
		];

		if ($claimItemStatus) {
			$params['claimItemStatus'] = $claimItemStatus;
		}

		if ($startDate !== null) {
			$params['startDate'] = (int) $startDate;
		}

		if ($endDate !== null) {
			$params['endDate'] = (int) $endDate;
		}

		$endpoint = "order/sellers/{$this->supplierId}/claims?" . http_build_query($params);

		return $this->request('GET', $endpoint);
	}

	public function getClaimById($claimId)
	{
		$endpoint = "order/sellers/{$this->supplierId}/claims?claimIds=" . urlencode((string) $claimId);

		return $this->request('GET', $endpoint);
	}

	public function approveClaimItems($claimId, array $claimLineItemIdList)
	{
		$endpoint = "order/sellers/{$this->supplierId}/claims/" . urlencode((string) $claimId) . '/items/approve';
		$data = [
			'claimLineItemIdList' => array_values($claimLineItemIdList),
			'params' => new \stdClass(),
		];

		return $this->request('PUT', $endpoint, $data);
	}

	public function getOrderInf($idOrder, $startDate, $endDate)
	{
		date_default_timezone_set('Europe/Istanbul');

		$start = (int) round(strtotime($startDate . ' 00:00:00') * 1000);
		$end = (int) round(strtotime($endDate . ' 23:59:59') * 1000);

		if ($idOrder) {
			$endpoint = $this->ordersEndpoint() . '?orderNumber=' . (int) $idOrder;
		} else {
			$endpoint = $this->ordersEndpoint() . "?startDate={$start}&endDate={$end}&orderByField=PackageLastModifiedDate&orderByDirection=DESC&size=200";
		}

		return $this->request('GET', $endpoint);
	}

	public function getBuybox($barcode)
	{
		return $this->getBuyboxInformation([(string) $barcode]);
	}

	public function getBuyboxInformation(array $barcodes): ?array
	{
		$barcodes = array_values(array_filter(array_map('strval', $barcodes), static function ($b) {
			return trim($b) !== '';
		}));
		$barcodes = array_slice($barcodes, 0, 10);

		if (empty($barcodes)) {
			return null;
		}

		$endpoint = "product/sellers/{$this->supplierId}/products/buybox-information";

		return $this->request('POST', $endpoint, ['barcodes' => $barcodes]);
	}

	public function getProducts($limit, $page = 0)
	{
		$endpoint = $this->productsV2Endpoint() . '?approved=true&size=' . (int) $limit . '&page=' . (int) $page;

		return $this->request('GET', $endpoint);
	}

	public function createProduct($data)
	{
		$endpoint = $this->productsV2Endpoint();

		return $this->request('POST', $endpoint, $data);
	}

	public function updateProduct($data)
	{
		$endpoint = $this->productsV2Endpoint();

		return $this->request('PUT', $endpoint, $data);
	}

	public function getBatch($data)
	{
		$endpoint = "product/sellers/{$this->supplierId}/products/batch-requests/" . $data;

		return $this->request('GET', $endpoint);
	}

	public function getProduct($barcode)
	{
		$barcode = trim((string) $barcode);

		if ($barcode === '') {
			return [
				'success' => false,
				'httpCode' => 0,
				'message' => 'Barkod boş',
				'body' => null,
			];
		}

		$encoded = rawurlencode($barcode);
		$candidates = [
			$this->productsV2Endpoint() . "?barcode={$encoded}&page=0&size=50",
			"product/sellers/{$this->supplierId}/products/approved?barcode={$encoded}&page=0&size=50",
			$this->productsV2Endpoint() . "?stockCode={$encoded}&page=0&size=50",
		];

		$lastOk = null;
		$lastError = null;

		foreach ($candidates as $endpoint) {
			$result = $this->request('GET', $endpoint);

			if (!is_array($result)) {
				continue;
			}

			if (array_key_exists('success', $result) && $result['success'] === false) {
				$lastError = $result;
				continue;
			}

			$lastOk = $result;

			if ($this->filterResponseHasProduct($result)) {
				return $result;
			}
		}

		if (is_array($lastOk)) {
			return $lastOk;
		}

		return is_array($lastError) ? $lastError : [
			'success' => false,
			'httpCode' => 0,
			'message' => 'Trendyol ürün sorgusu başarısız',
			'body' => null,
		];
	}

	/** @param mixed $result */
	private function filterResponseHasProduct($result): bool
	{
		if (!is_array($result)) {
			return false;
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return false;
		}

		if (isset($result['content']) && is_array($result['content']) && $result['content'] !== []) {
			return isset($result['content'][0]) && is_array($result['content'][0]);
		}

		if (isset($result['barcode']) || isset($result['title']) || isset($result['productName']) || isset($result['variants'])) {
			return true;
		}

		if (isset($result[0]) && is_array($result[0]) && (isset($result[0]['barcode']) || isset($result[0]['title']))) {
			return true;
		}

		return false;
	}

	public function getCategories($name = null)
	{
		$endpoint = 'product/product-categories';

		if ($name !== null && trim((string) $name) !== '') {
			$endpoint .= '?name=' . rawurlencode(trim((string) $name));
		}

		return $this->request('GET', $endpoint);
	}

	public function getBrand($name)
	{
		$endpoint = 'product/brands/by-name?name=' . rawurlencode((string) $name);

		return $this->request('GET', $endpoint);
	}

	public function getAttirupes($id)
	{
		$id = (int) $id;
		$result = $this->getCategoryAttributeList($id);

		if (!is_array($result) || (array_key_exists('success', $result) && $result['success'] === false)) {
			return $result;
		}

		$rows = $result['categoryAttributes'] ?? ($result['categoryAttributeList'] ?? []);

		if (!is_array($rows)) {
			$rows = [];
		}

		$filtered = [];

		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}

			$allowCustom = !empty($row['allowCustom']);
			$existingValues = $row['attributeValues'] ?? [];

			if (!$allowCustom && (!is_array($existingValues) || $existingValues === [])) {
				$attributeId = (int) ($row['attribute']['id'] ?? 0);

				if ($attributeId > 0) {
					$row['attributeValues'] = $this->getAttributeValues($id, $attributeId);
				}
			}

			$filtered[] = $row;
		}

		usort($filtered, static function (array $a, array $b): int {
			$ra = !empty($a['required']) || !empty($a['mandatory']) || !empty($a['isMandatory']) ? 0 : 1;
			$rb = !empty($b['required']) || !empty($b['mandatory']) || !empty($b['isMandatory']) ? 0 : 1;

			if ($ra !== $rb) {
				return $ra - $rb;
			}

			$na = (string) (($a['attribute']['name'] ?? '') ?: '');
			$nb = (string) (($b['attribute']['name'] ?? '') ?: '');

			return strcasecmp($na, $nb);
		});

		$result['categoryAttributes'] = $filtered;

		return $result;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getCategoryAttributeList(int $categoryId): array
	{
		if ($categoryId <= 0) {
			return ['categoryAttributes' => []];
		}

		if (isset($this->attributeListCache[$categoryId])) {
			return $this->attributeListCache[$categoryId];
		}

		$result = $this->request('GET', "product/categories/{$categoryId}/attributes");
		$this->attributeListCache[$categoryId] = is_array($result) ? $result : ['categoryAttributes' => []];

		return $this->attributeListCache[$categoryId];
	}

	/**
	 * @return array<int, int>
	 */
	public function originAttributeIds(int $categoryId): array
	{
		$result = $this->getCategoryAttributeList($categoryId);
		$rows = $result['categoryAttributes'] ?? ($result['categoryAttributeList'] ?? []);
		$ids = [];

		if (!is_array($rows)) {
			return [];
		}

		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}

			$name = (string) (($row['attribute']['name'] ?? '') ?: '');

			if (!self::isOriginAttributeName($name)) {
				continue;
			}

			$id = (int) ($row['attribute']['id'] ?? 0);

			if ($id > 0) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * @return array<int, array{id: int, name: string, attributeValueId: int, attributeValue: string}>
	 */
	public function getAttributeValues(int $categoryId, int $attributeId): array
	{
		if ($categoryId <= 0 || $attributeId <= 0) {
			return [];
		}

		$out = [];
		$page = 0;
		$size = 1000;

		while ($page < 5) {
			$endpoint = "product/categories/{$categoryId}/attributes/{$attributeId}/values?page={$page}&size={$size}";
			$result = $this->request('GET', $endpoint);

			if (!is_array($result) || (array_key_exists('success', $result) && $result['success'] === false)) {
				break;
			}

			$content = $result['content'] ?? [];

			if (!is_array($content) || $content === []) {
				break;
			}

			foreach ($content as $item) {
				if (!is_array($item)) {
					continue;
				}

				$valueId = (int) ($item['attributeValueId'] ?? $item['id'] ?? 0);
				$name = (string) ($item['attributeValue'] ?? $item['name'] ?? '');

				if ($valueId <= 0 && $name === '') {
					continue;
				}

				$out[] = [
					'id' => $valueId,
					'name' => $name,
					'attributeValueId' => $valueId,
					'attributeValue' => $name,
				];
			}

			$totalPages = (int) ($result['totalPages'] ?? 1);

			if ($page + 1 >= $totalPages || count($content) < $size) {
				break;
			}

			$page++;
		}

		return $out;
	}

	public static function isOriginAttributeName(string $name): bool
	{
		$name = trim(mb_strtolower($name));

		if ($name === '') {
			return false;
		}

		return (bool) preg_match('/men[sş]ei|mensei|\borigin\b|menşe/u', $name);
	}

	/**
	 * Trendyol origin alanı 2 haneli ISO ülke kodu ister (Türkiye → TR).
	 */
	public static function resolveOriginCode(?string $raw): string
	{
		$raw = trim((string) $raw);
		$key = mb_strtolower($raw);
		$map = [
			'tr' => 'TR',
			'türkiye' => 'TR',
			'turkiye' => 'TR',
			'turkey' => 'TR',
			'cn' => 'CN',
			'çin' => 'CN',
			'cin' => 'CN',
			'china' => 'CN',
			'de' => 'DE',
			'almanya' => 'DE',
			'germany' => 'DE',
			'it' => 'IT',
			'italya' => 'IT',
			'italy' => 'IT',
			'bd' => 'BD',
			'bangladeş' => 'BD',
			'banglades' => 'BD',
			'bangladesh' => 'BD',
			'in' => 'IN',
			'hindistan' => 'IN',
			'india' => 'IN',
			'vn' => 'VN',
			'vietnam' => 'VN',
			'pk' => 'PK',
			'pakistan' => 'PK',
			'us' => 'US',
			'abd' => 'US',
			'usa' => 'US',
		];

		if ($key !== '' && isset($map[$key])) {
			return $map[$key];
		}

		if (preg_match('/^[A-Za-z]{2}$/', $raw)) {
			return strtoupper($raw);
		}

		return 'TR';
	}

	/**
	 * Menşei katalog değerlerinden Türkiye'yi (yoksa ilk değeri) seçer.
	 *
	 * @param array<int, array<string, mixed>> $values
	 */
	public static function pickDefaultOriginValueId(array $values): int
	{
		$fallback = 0;

		foreach ($values as $item) {
			if (!is_array($item)) {
				continue;
			}

			$id = (int) ($item['id'] ?? $item['attributeValueId'] ?? 0);
			$name = mb_strtolower(trim((string) ($item['name'] ?? $item['attributeValue'] ?? '')));

			if ($id <= 0) {
				continue;
			}

			if ($fallback === 0) {
				$fallback = $id;
			}

			if (preg_match('/t[uü]rkiye|turkey|^tr$/', $name)) {
				return $id;
			}
		}

		return $fallback;
	}

	public function getQuestion($page = 0, $size = 25, $status = null)
	{
		$params = [
			'orderByField' => 'CreatedDate',
			'orderByDirection' => 'DESC',
			'size' => min(50, max(1, (int) $size)),
			'page' => max(0, (int) $page),
		];

		if ($status !== null && $status !== '') {
			$params['status'] = $status;
		}

		$endpoint = "qna/sellers/{$this->supplierId}/questions/filter?" . http_build_query($params);

		return $this->request('GET', $endpoint);
	}

	public function postQuestion($id, $text)
	{
		$endpoint = "qna/sellers/{$this->supplierId}/questions/" . (int) $id . '/answers';
		$data = ['text' => $text];

		return $this->request('POST', $endpoint, $data);
	}

	public function updateStockPrice($barcode, $listPrice, $salePrice, $stock, $sku = null)
	{
		$item = [
			'barcode' => (string) $barcode,
			'quantity' => (int) $stock,
			'salePrice' => (float) $salePrice,
			'listPrice' => (float) $listPrice,
		];

		if ($sku !== null && trim((string) $sku) !== '') {
			$item['stockCode'] = (string) $sku;
		}

		return $this->updateStockPriceItems([$item]);
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 * @return array<string, mixed>|null
	 */
	public function updateStockPriceItems(array $items)
	{
		$endpoint = "inventory/sellers/{$this->supplierId}/products/price-and-inventory";

		$clean = [];

		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}

			unset($item['id_variation']);
			$clean[] = $item;
		}

		return $this->request('POST', $endpoint, ['items' => array_values($clean)]);
	}

	/**
	 * @param array<string, mixed> $data items: [{barcode, archived}]
	 * @return array<string, mixed>|null
	 */
	public function archiveProducts($data)
	{
		$endpoint = "product/sellers/{$this->supplierId}/products/archive-state";

		return $this->request('PUT', $endpoint, $data);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function request(string $method, string $endpoint, array $data = []): ?array
	{
		if (!$this->isConfigured()) {
			return [
				'success' => false,
				'httpCode' => 0,
				'message' => 'Trendyol API kimlik bilgileri eksik',
				'body' => null,
			];
		}

		if (!function_exists('curl_init')) {
			return [
				'success' => false,
				'httpCode' => 0,
				'message' => 'cURL eklentisi yok',
				'body' => null,
			];
		}

		$url = $this->baseUri . $endpoint;
		$auth = base64_encode($this->apiKey . ':' . $this->apiSecret);
		$userAgent = $this->supplierId . ' - SelfIntegration';

		$ch = curl_init();

		if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true) && !empty($data)) {
			$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		}

		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => [
				'Authorization: Basic ' . $auth,
				'Content-Type: application/json',
				'User-Agent: ' . $userAgent,
				'Accept: application/json',
				'storeFrontCode: ' . self::STOREFRONT_CODE,
			],
			CURLOPT_TIMEOUT => 60,
		]);

		$response = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		$decoded = json_decode((string) $response, true);

		if ($httpCode >= 200 && $httpCode < 300) {
			if ($decoded === null) {
				return ['success' => true, 'httpStatus' => $httpCode];
			}

			return $decoded;
		}

		$message = $error ?: ('HTTP ' . $httpCode);

		if (is_array($decoded)) {
			if (!empty($decoded['message'])) {
				$message = (string) $decoded['message'];
			} elseif (!empty($decoded['errors']) && is_array($decoded['errors'])) {
				$parts = [];

				foreach ($decoded['errors'] as $err) {
					if (is_string($err)) {
						$parts[] = $err;
					} elseif (is_array($err) && isset($err['message'])) {
						$parts[] = (string) $err['message'];
					} elseif (is_array($err) && isset($err['key'])) {
						$parts[] = (string) $err['key'];
					}
				}

				if ($parts !== []) {
					$message = implode('; ', $parts);
				}
			}
		}

		return [
			'success' => false,
			'httpCode' => $httpCode,
			'message' => $message,
			'body' => is_array($decoded) ? $decoded : (string) $response,
		];
	}

	/**
	 * @param array<int|string, mixed> $attirupe
	 * @return array<int, array<string, mixed>>
	 */
	public function convertAttributes($attirupe): array
	{
		$attributes = [];

		foreach ($attirupe as $attributeId => $attributeValue) {
			if ($attributeValue === '' || $attributeValue === null) {
				continue;
			}

			if (is_numeric($attributeValue)) {
				$attributes[] = [
					'attributeId' => (int) $attributeId,
					'attributeValueId' => (int) $attributeValue,
				];
			} else {
				$attributes[] = [
					'attributeId' => (int) $attributeId,
					'customAttributeValue' => (string) $attributeValue,
				];
			}
		}

		return $attributes;
	}
}
