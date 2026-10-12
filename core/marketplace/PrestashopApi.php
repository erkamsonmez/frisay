<?php

namespace Prestashop;

/**
 * PrestaShop WebService (XML) istemcisi.
 */
class PrestashopApi
{
	private string $apiKey;
	private string $baseUrl;
	private int $langId;

	public function __construct(string $apiKey, string $shopUrl, int $langId = 1)
	{
		$this->apiKey = trim($apiKey);
		$this->baseUrl = self::normalizeShopUrl($shopUrl);
		$this->langId = $langId > 0 ? $langId : 1;
	}

	public static function normalizeShopUrl(string $shopUrl): string
	{
		$url = rtrim(trim($shopUrl), '/');

		if ($url === '') {
			return '';
		}

		$url = preg_replace('#/api/?$#i', '', $url) ?: $url;

		return rtrim($url, '/');
	}

	public function isConfigured(): bool
	{
		return $this->apiKey !== '' && $this->baseUrl !== '' && (bool) filter_var($this->baseUrl, FILTER_VALIDATE_URL);
	}

	/** @param mixed $result */
	public static function isApiError($result): bool
	{
		if (!is_array($result)) {
			return true;
		}

		if (array_key_exists('ok', $result) && $result['ok'] === false) {
			return true;
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return true;
		}

		return false;
	}

	/** @param mixed $value */
	public function scalarText($value): string
	{
		if (is_array($value)) {
			if (isset($value['language'])) {
				return $this->scalarText($this->pickLanguage($value['language']));
			}

			if (isset($value['@attributes']['id']) && count($value) <= 2) {
				return trim((string) ($value['@attributes']['id'] ?? ''));
			}

			if (isset($value[0])) {
				return $this->scalarText($value[0]);
			}

			foreach (['#text', '_', 'value', 'name'] as $key) {
				if (isset($value[$key]) && !is_array($value[$key])) {
					return trim((string) $value[$key]);
				}
			}

			return '';
		}

		return trim((string) $value);
	}

	/** @param mixed $value */
	public function scalarId($value): int
	{
		if (is_array($value)) {
			if (isset($value['id'])) {
				return $this->scalarId($value['id']);
			}

			if (isset($value['@attributes']['id'])) {
				return (int) $value['@attributes']['id'];
			}
		}

		return (int) $this->scalarText($value);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function getOrders(?string $start = null, ?string $end = null): array
	{
		$query = [
			'display' => 'full',
			'sort' => '[id_DESC]',
			'limit' => '80',
		];

		if ($start !== null && $start !== '') {
			$from = date('Y-m-d', strtotime($start) ?: time());
			$to = $end !== null && $end !== ''
				? date('Y-m-d', strtotime($end) ?: time())
				: date('Y-m-d');
			$query['date'] = '1';
			$query['filter[date_add]'] = '[' . $from . ',' . $to . ']';
		}

		$data = $this->get('orders', $query);

		if (self::isApiError($data)) {
			return [];
		}

		$rows = $this->collection($data['orders']['order'] ?? ($data['order'] ?? []));
		$out = [];

		foreach ($rows as $row) {
			$normalized = $this->normalizeOrder($row);

			if ($normalized !== null) {
				$out[] = $normalized;
			}
		}

		return $out;
	}

	/** @return array<string, mixed>|null */
	public function getOrderByReference(string $reference): ?array
	{
		$reference = trim($reference);

		if ($reference === '') {
			return null;
		}

		if (ctype_digit($reference)) {
			$data = $this->get('orders/' . (int) $reference);
			$order = is_array($data['order'] ?? null) ? $data['order'] : $data;

			return self::isApiError($data) ? null : $this->normalizeOrder($order);
		}

		$data = $this->get('orders', [
			'display' => 'full',
			'filter[reference]' => '[' . $reference . ']',
			'limit' => '1',
		]);

		if (self::isApiError($data)) {
			return null;
		}

		$rows = $this->collection($data['orders']['order'] ?? ($data['order'] ?? []));

		return $rows !== [] ? $this->normalizeOrder($rows[0]) : null;
	}

	/**
	 * @return array<int, array{id:int,active:int}>
	 */
	public function listProductIds(int $offset = 0, int $limit = 10, bool $activeOnly = false): array
	{
		$result = $this->listProducts($offset, $limit, ['active_only' => $activeOnly]);

		return $result['items'];
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array{ok:bool,message:string,items:array<int, array{id:int,active:int}>,httpCode?:int}
	 */
	public function listProducts(int $offset = 0, int $limit = 10, array $filters = []): array
	{
		$limit = max(1, min(100, $limit));
		$offset = max(0, $offset);
		$query = [
			'display' => '[id,active,reference,id_category_default,id_manufacturer]',
			'limit' => $offset . ',' . $limit,
			'sort' => '[id_ASC]',
		];
		$this->applyProductFilters($query, $filters);

		$data = $this->get('products', $query);

		if (self::isApiError($data)) {
			unset($query['display']);
			$data = $this->get('products', $query);
		}

		if (self::isApiError($data)) {
			return [
				'ok' => false,
				'message' => $this->friendlyError((string) ($data['message'] ?? 'Ürün listesi alınamadı'), (int) ($data['httpCode'] ?? 0)),
				'items' => [],
				'httpCode' => (int) ($data['httpCode'] ?? 0),
			];
		}

		$out = [];

		foreach ($this->extractResourceRows($data, 'products', 'product') as $row) {
			if (!is_array($row)) {
				continue;
			}

			$id = $this->scalarId($row['id'] ?? ($row['@attributes']['id'] ?? 0));

			if ($id > 0) {
				$out[] = [
					'id' => $id,
					'active' => $this->scalarId($row['active'] ?? 1),
				];
			}
		}

		return [
			'ok' => true,
			'message' => $out === [] ? 'Filtreye uyan ürün yok' : (count($out) . ' ürün bulundu'),
			'items' => $out,
		];
	}

	/**
	 * @return array{ok:bool,message:string,httpCode?:int,shop_url?:string,product_count?:int,sample_ids?:list<int>}
	 */
	public function testConnection(): array
	{
		if (!$this->isConfigured()) {
			return ['ok' => false, 'message' => 'Mağaza URL ve WebService API anahtarı gerekli'];
		}

		$root = $this->get('');

		if (self::isApiError($root)) {
			$code = (int) ($root['httpCode'] ?? 0);
			$message = $this->friendlyError((string) ($root['message'] ?? 'Bağlantı kurulamadı'), $code);

			return [
				'ok' => false,
				'message' => $message,
				'httpCode' => $code,
				'shop_url' => $this->baseUrl,
			];
		}

		$list = $this->listProducts(0, 5, []);

		if (empty($list['ok'])) {
			return [
				'ok' => false,
				'message' => 'API açıldı ama products izni yok veya liste okunamadı: ' . (string) ($list['message'] ?? ''),
				'httpCode' => (int) ($list['httpCode'] ?? 0),
				'shop_url' => $this->baseUrl,
			];
		}

		$ids = [];

		foreach ($list['items'] as $row) {
			$ids[] = (int) $row['id'];
		}

		$activeList = $this->listProducts(0, 5, ['active_only' => true]);
		$activeCount = count($activeList['items'] ?? []);

		return [
			'ok' => true,
			'message' => 'Bağlantı başarılı. Örnek ' . count($ids) . ' ürün okundu'
				. ($ids !== [] ? (' (ID: ' . implode(', ', $ids) . ')') : '')
				. ($activeCount > 0 ? ('. Aktif ürünler görünüyor.') : (count($ids) > 0 ? '. Aktif filtresi boş — “Tüm ürünler” ile çekin.' : '. Mağazada ürün yok.')),
			'shop_url' => $this->baseUrl,
			'product_count' => count($ids),
			'sample_ids' => $ids,
		];
	}

	/**
	 * @return array{ok:bool,message:string,product?:array<string,mixed>}
	 */
	public function fetchProduct(int $id): array
	{
		if ($id <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz ürün ID'];
		}

		$data = $this->get('products/' . $id);

		if (self::isApiError($data)) {
			return ['ok' => false, 'message' => (string) ($data['message'] ?? ('Ürün #' . $id . ' bulunamadı'))];
		}

		$product = is_array($data['product'] ?? null) ? $data['product'] : $data;

		return ['ok' => true, 'message' => '', 'product' => $product];
	}

	/**
	 * @param array<string, mixed> $fields
	 * @return array{ok?:bool,success?:bool,id?:int,message?:string}
	 */
	public function createProduct(array $fields): array
	{
		$xml = $this->buildProductXml(0, $fields);
		$data = $this->sendXml('products', 'POST', $xml);

		if (self::isApiError($data)) {
			return ['ok' => false, 'success' => false, 'message' => (string) ($data['message'] ?? 'Ürün oluşturulamadı')];
		}

		$id = $this->scalarId($data['product']['id'] ?? ($data['id'] ?? 0));

		if ($id > 0) {
			$this->setStock($id, (int) ($fields['quantity'] ?? 0));
		}

		return ['ok' => true, 'success' => true, 'id' => $id, 'message' => 'Ürün oluşturuldu'];
	}

	/**
	 * @param array<string, mixed> $fields
	 * @return array{ok?:bool,success?:bool,id?:int,message?:string}
	 */
	public function updateProduct(int $id, array $fields): array
	{
		if ($id <= 0) {
			return ['ok' => false, 'success' => false, 'message' => 'Geçersiz ürün ID'];
		}

		$existing = $this->fetchProduct($id);

		if (empty($existing['ok']) || empty($existing['product'])) {
			return ['ok' => false, 'success' => false, 'message' => (string) ($existing['message'] ?? 'Ürün bulunamadı')];
		}

		$merged = array_merge($existing['product'], $fields);
		$merged['id'] = $id;
		$xml = $this->buildProductXml($id, $merged);
		$data = $this->sendXml('products/' . $id, 'PUT', $xml);

		if (self::isApiError($data)) {
			return ['ok' => false, 'success' => false, 'message' => (string) ($data['message'] ?? 'Ürün güncellenemedi')];
		}

		if (array_key_exists('quantity', $fields)) {
			$this->setStock($id, (int) $fields['quantity']);
		}

		return ['ok' => true, 'success' => true, 'id' => $id, 'message' => 'Ürün güncellendi'];
	}

	/** @return array<string, mixed>|null */
	public function getStockAvailable(int $productId, int $combinationId = 0): ?array
	{
		$data = $this->get('stock_availables', [
			'display' => 'full',
			'filter[id_product]' => '[' . $productId . ']',
			'filter[id_product_attribute]' => '[' . $combinationId . ']',
			'limit' => '1',
		]);

		if (self::isApiError($data)) {
			return null;
		}

		$rows = $this->collection($data['stock_availables']['stock_available'] ?? ($data['stock_available'] ?? []));

		return $rows[0] ?? null;
	}

	/** @return array<string, mixed>|null */
	public function getCategory(int $id): ?array
	{
		$data = $this->get('categories/' . $id);

		if (self::isApiError($data)) {
			return null;
		}

		return is_array($data['category'] ?? null) ? $data['category'] : $data;
	}

	/** @return array<string, mixed>|null */
	public function getManufacturer(int $id): ?array
	{
		$data = $this->get('manufacturers/' . $id);

		if (self::isApiError($data)) {
			return null;
		}

		return is_array($data['manufacturer'] ?? null) ? $data['manufacturer'] : $data;
	}

	/**
	 * Dil / Home kategori / vergi / mağaza ID — kullanıcıdan istenmez.
	 *
	 * @return array{lang_id:int,category_id:int,tax_group:int,shop_id:int}
	 */
	public function discoverDefaults(): array
	{
		$out = [
			'lang_id' => 0,
			'category_id' => 0,
			'tax_group' => 0,
			'shop_id' => 0,
		];

		$langs = $this->get('languages', [
			'display' => 'full',
			'filter[active]' => '[1]',
		]);

		if (!self::isApiError($langs)) {
			foreach ($this->collection($langs['languages']['language'] ?? ($langs['language'] ?? [])) as $row) {
				if (!is_array($row)) {
					continue;
				}

				$id = $this->scalarId($row['id'] ?? 0);

				if ($id <= 0) {
					continue;
				}

				if ($out['lang_id'] === 0) {
					$out['lang_id'] = $id;
				}

				if ((int) $this->scalarText($row['is_default'] ?? 0) === 1) {
					$out['lang_id'] = $id;
					break;
				}
			}
		}

		$shops = $this->get('shops', ['display' => 'full']);

		if (!self::isApiError($shops)) {
			foreach ($this->collection($shops['shops']['shop'] ?? ($shops['shop'] ?? [])) as $row) {
				if (!is_array($row)) {
					continue;
				}

				$id = $this->scalarId($row['id'] ?? 0);

				if ($id <= 0) {
					continue;
				}

				if ($out['shop_id'] === 0) {
					$out['shop_id'] = $id;
				}

				if ((int) $this->scalarText($row['id_shop_group'] ?? 1) >= 0
					&& ((int) $this->scalarText($row['active'] ?? 1) === 1)
					&& ((int) $this->scalarText($row['deleted'] ?? 0) === 0)
				) {
					$out['shop_id'] = $id;
					break;
				}
			}
		}

		$home = $this->getCategory(2);

		if (is_array($home) && $this->scalarId($home['id'] ?? 0) === 2) {
			$out['category_id'] = 2;
		} else {
			$cats = $this->get('categories', [
				'display' => '[id,id_parent,is_root_category,active]',
				'filter[active]' => '[1]',
				'limit' => '40',
			]);

			if (!self::isApiError($cats)) {
				foreach ($this->collection($cats['categories']['category'] ?? ($cats['category'] ?? [])) as $row) {
					if (!is_array($row)) {
						continue;
					}

					$id = $this->scalarId($row['id'] ?? 0);
					$isRoot = (int) $this->scalarText($row['is_root_category'] ?? 0);
					$parent = $this->scalarId($row['id_parent'] ?? 0);

					if ($id > 0 && $isRoot === 0 && $parent > 0) {
						$out['category_id'] = $id;
						break;
					}
				}
			}
		}

		$taxes = $this->get('tax_rule_groups', [
			'display' => '[id,active,deleted]',
			'limit' => '20',
		]);

		if (!self::isApiError($taxes)) {
			foreach ($this->collection($taxes['tax_rule_groups']['tax_rule_group'] ?? ($taxes['tax_rule_group'] ?? [])) as $row) {
				if (!is_array($row)) {
					continue;
				}

				$id = $this->scalarId($row['id'] ?? 0);

				if ($id > 0
					&& (int) $this->scalarText($row['deleted'] ?? 0) === 0
					&& (int) $this->scalarText($row['active'] ?? 1) === 1
				) {
					$out['tax_group'] = $id;
					break;
				}
			}
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $psProduct
	 * @return list<int>
	 */
	public function getProductImageIds(array $psProduct): array
	{
		$ids = [];
		$assoc = $psProduct['associations']['images'] ?? null;

		if (is_array($assoc)) {
			$rows = isset($assoc['image'])
				? $this->collection($assoc['image'])
				: $this->collection($assoc);

			foreach ($rows as $row) {
				$id = $this->scalarId($row);

				if ($id > 0) {
					$ids[] = $id;
				}
			}
		}

		$defaultId = $this->scalarId($psProduct['id_default_image'] ?? 0);

		if ($defaultId > 0) {
			array_unshift($ids, $defaultId);
		}

		$ids = array_values(array_unique(array_filter($ids)));

		if ($ids === []) {
			$psId = $this->scalarId($psProduct['id'] ?? 0);

			if ($psId > 0) {
				$listed = $this->get('images/products/' . $psId);

				if (!self::isApiError($listed)) {
					foreach ($this->extractResourceRows($listed, 'images', 'image') as $row) {
						if (!is_array($row)) {
							continue;
						}

						$id = $this->scalarId($row);

						if ($id > 0) {
							$ids[] = $id;
						}
					}
				}
			}
		}

		return array_values(array_unique($ids));
	}

	public function getImageBinary(int $productId, int $imageId): string
	{
		if ($productId <= 0 || $imageId <= 0 || !$this->isConfigured()) {
			return '';
		}

		$paths = [
			'images/products/' . $productId . '/' . $imageId,
			'images/products/' . $productId . '/' . $imageId . '/large_default',
		];

		foreach ($paths as $path) {
			$raw = $this->rawRequest($this->apiUrl($path), 'GET', '', [
				'Accept: image/jpeg, image/png, image/webp, */*',
			]);

			if (($raw['code'] ?? 0) < 200 || ($raw['code'] ?? 0) >= 300) {
				continue;
			}

			$body = (string) ($raw['body'] ?? '');
			$trim = ltrim($body);

			if ($body === '' || (isset($trim[0]) && $trim[0] === '<')) {
				continue;
			}

			if (@getimagesizefromstring($body)) {
				return $body;
			}
		}

		return '';
	}

	/**
	 * @return array{ok?:bool,message?:string}
	 */
	public function uploadProductImage(int $productId, string $filePath): array
	{
		if ($productId <= 0 || !is_file($filePath)) {
			return ['ok' => false, 'message' => 'Görsel dosyası yok'];
		}

		$url = $this->baseUrl . '/api/images/products/' . $productId;

		if (!function_exists('curl_init')) {
			return ['ok' => false, 'message' => 'cURL eklentisi yok'];
		}

		$ch = curl_init($url);
		$this->applyCurl($ch);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Basic ' . base64_encode($this->apiKey . ':'),
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, [
			'image' => new \CURLFile($filePath, mime_content_type($filePath) ?: 'image/jpeg', basename($filePath)),
		]);
		$body = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err = curl_error($ch);
		curl_close($ch);

		if ($body === false || $code < 200 || $code >= 300) {
			return ['ok' => false, 'message' => $err !== '' ? $err : ('HTTP ' . $code)];
		}

		return ['ok' => true, 'message' => 'Görsel yüklendi'];
	}

	private function setStock(int $productId, int $qty): void
	{
		$row = $this->getStockAvailable($productId, 0);

		if (!$row) {
			return;
		}

		$stockId = $this->scalarId($row['id'] ?? 0);

		if ($stockId <= 0) {
			return;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<prestashop xmlns:xlink="http://www.w3.org/1999/xlink"><stock_available>'
			. '<id>' . $stockId . '</id>'
			. '<id_product>' . $productId . '</id_product>'
			. '<id_product_attribute>0</id_product_attribute>'
			. '<quantity>' . max(0, $qty) . '</quantity>'
			. '</stock_available></prestashop>';

		$this->sendXml('stock_availables/' . $stockId, 'PUT', $xml);
	}

	/**
	 * @param array<string, mixed> $order
	 * @return array<string, mixed>|null
	 */
	private function normalizeOrder(array $order): ?array
	{
		$id = $this->scalarId($order['id'] ?? 0);
		$reference = $this->scalarText($order['reference'] ?? '');

		if ($id <= 0 && $reference === '') {
			return null;
		}

		$stateId = $this->scalarId($order['current_state'] ?? 0);
		$status = $stateId > 0 ? $this->orderStateName($stateId) : '';
		$customerId = $this->scalarId($order['id_customer'] ?? 0);
		$addressId = $this->scalarId($order['id_address_delivery'] ?? 0);
		$carrierId = $this->scalarId($order['id_carrier'] ?? 0);
		$customer = $customerId > 0 ? $this->getResource('customers/' . $customerId, 'customer') : [];
		$address = $addressId > 0 ? $this->getResource('addresses/' . $addressId, 'address') : [];
		$carrier = $carrierId > 0 ? $this->getResource('carriers/' . $carrierId, 'carrier') : [];

		$products = [];
		$rows = $this->collection($order['associations']['order_rows']['order_row'] ?? []);

		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}

			$products[] = [
				'product_id' => $this->scalarId($row['product_id'] ?? 0),
				'product_name' => $this->scalarText($row['product_name'] ?? ''),
				'quantity' => $this->scalarId($row['product_quantity'] ?? 0),
				'price' => (float) str_replace(',', '.', $this->scalarText($row['unit_price_tax_incl'] ?? ($row['product_price'] ?? 0))),
				'stock_code' => $this->scalarText($row['product_reference'] ?? ''),
				'barcode' => $this->scalarText($row['product_ean13'] ?? ''),
				'image' => '',
			];
		}

		return [
			'order_id' => $id,
			'order_number' => $reference !== '' ? $reference : (string) $id,
			'status' => $status !== '' ? $status : (string) $stateId,
			'total' => (float) str_replace(',', '.', $this->scalarText($order['total_paid_tax_incl'] ?? ($order['total_paid'] ?? 0))),
			'order_date' => $this->scalarText($order['date_add'] ?? ''),
			'shipping_number' => $this->scalarText($order['shipping_number'] ?? ''),
			'cargo_tracking_number' => $this->scalarText($order['shipping_number'] ?? ''),
			'cargo_provider' => $this->scalarText($carrier['name'] ?? ''),
			'carrier' => ['name' => $this->scalarText($carrier['name'] ?? '')],
			'customer' => [
				'firstname' => $this->scalarText($customer['firstname'] ?? ''),
				'lastname' => $this->scalarText($customer['lastname'] ?? ''),
			],
			'delivery_address' => [
				'firstname' => $this->scalarText($address['firstname'] ?? ''),
				'lastname' => $this->scalarText($address['lastname'] ?? ''),
				'address1' => $this->scalarText($address['address1'] ?? ''),
				'city' => $this->scalarText($address['city'] ?? ''),
				'phone' => $this->scalarText($address['phone_mobile'] ?? ($address['phone'] ?? '')),
			],
			'products' => $products,
		];
	}

	private function orderStateName(int $id): string
	{
		$data = $this->getResource('order_states/' . $id, 'order_state');

		return $this->scalarText($data['name'] ?? '');
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getResource(string $path, string $root): array
	{
		$data = $this->get($path);

		if (self::isApiError($data)) {
			return [];
		}

		return is_array($data[$root] ?? null) ? $data[$root] : $data;
	}

	/**
	 * @param array<string, mixed> $fields
	 */
	private function buildProductXml(int $id, array $fields): string
	{
		$lang = $this->langId;
		$name = $this->cdata($this->scalarText($fields['name'] ?? ''));
		$desc = $this->cdata($this->scalarText($fields['description'] ?? ''));
		$short = $this->cdata($this->scalarText($fields['description_short'] ?? ''));
		$catId = max(1, $this->scalarId($fields['id_category_default'] ?? 2));
		$taxId = max(0, $this->scalarId($fields['id_tax_rules_group'] ?? 1));
		$shopId = max(1, $this->scalarId($fields['id_shop_default'] ?? 1));
		$manId = max(0, $this->scalarId($fields['id_manufacturer'] ?? 0));
		$active = $this->scalarId($fields['active'] ?? 1) > 0 ? 1 : 0;
		$price = number_format((float) str_replace(',', '.', $this->scalarText($fields['price'] ?? 0)), 6, '.', '');
		$wholesale = number_format((float) str_replace(',', '.', $this->scalarText($fields['wholesale_price'] ?? 0)), 6, '.', '');
		$reference = $this->cdata($this->scalarText($fields['reference'] ?? ''));
		$ean = htmlspecialchars(mb_substr($this->scalarText($fields['ean13'] ?? ''), 0, 13), ENT_XML1 | ENT_QUOTES, 'UTF-8');
		$idXml = $id > 0 ? '<id>' . $id . '</id>' : '';

		return '<?xml version="1.0" encoding="UTF-8"?>'
			. '<prestashop xmlns:xlink="http://www.w3.org/1999/xlink"><product>'
			. $idXml
			. '<id_manufacturer>' . $manId . '</id_manufacturer>'
			. '<id_category_default>' . $catId . '</id_category_default>'
			. '<id_shop_default>' . $shopId . '</id_shop_default>'
			. '<id_tax_rules_group>' . $taxId . '</id_tax_rules_group>'
			. '<state>1</state>'
			. '<price>' . $price . '</price>'
			. '<wholesale_price>' . $wholesale . '</wholesale_price>'
			. '<active>' . $active . '</active>'
			. '<available_for_order>1</available_for_order>'
			. '<show_price>1</show_price>'
			. '<reference>' . $reference . '</reference>'
			. '<ean13>' . $ean . '</ean13>'
			. '<name><language id="' . $lang . '">' . $name . '</language></name>'
			. '<description><language id="' . $lang . '">' . $desc . '</language></description>'
			. '<description_short><language id="' . $lang . '">' . $short . '</language></description_short>'
			. '<link_rewrite><language id="' . $lang . '">' . $this->cdata($this->slug($this->scalarText($fields['name'] ?? 'product'))) . '</language></link_rewrite>'
			. '<associations><categories><category><id>' . $catId . '</id></category></categories></associations>'
			. '</product></prestashop>';
	}

	private function cdata(string $value): string
	{
		$value = str_replace(']]>', ']]&gt;', $value);

		return '<![CDATA[' . $value . ']]>';
	}

	private function slug(string $name): string
	{
		$name = mb_strtolower(trim($name), 'UTF-8');
		$name = preg_replace('/[^a-z0-9]+/i', '-', $name) ?: 'product';

		return trim($name, '-') ?: 'product';
	}

	/**
	 * @param mixed $language
	 * @return mixed
	 */
	private function pickLanguage($language)
	{
		if (!is_array($language)) {
			return $language;
		}

		if (isset($language['@attributes']['id']) && (int) $language['@attributes']['id'] === $this->langId) {
			return $language;
		}

		foreach ($language as $row) {
			if (is_array($row) && isset($row['@attributes']['id']) && (int) $row['@attributes']['id'] === $this->langId) {
				return $row;
			}
		}

		return $language[0] ?? $language;
	}

	/**
	 * @param array<string, string> $query
	 * @param array<string, mixed> $filters
	 */
	private function applyProductFilters(array &$query, array $filters): void
	{
		if (!empty($filters['active_only'])) {
			$query['filter[active]'] = '[1]';
		}

		$categoryId = (int) ($filters['category_id'] ?? 0);

		if ($categoryId > 0) {
			$query['filter[id_category_default]'] = '[' . $categoryId . ']';
		}

		$manufacturerId = (int) ($filters['manufacturer_id'] ?? 0);

		if ($manufacturerId > 0) {
			$query['filter[id_manufacturer]'] = '[' . $manufacturerId . ']';
		}

		$reference = trim((string) ($filters['reference'] ?? ''));

		if ($reference !== '') {
			$query['filter[reference]'] = '%[' . $reference . ']%';
		}

		$name = trim((string) ($filters['name'] ?? ''));

		if ($name !== '') {
			$query['filter[name]'] = '%[' . $name . ']%';
		}
	}

	/**
	 * JSON: {"products":[{"id":1}]}  XML: {"products":{"product":[...]}}
	 *
	 * @param array<string, mixed> $data
	 * @return array<int, mixed>
	 */
	private function extractResourceRows(array $data, string $plural, string $singular): array
	{
		if (isset($data[$plural]) && is_array($data[$plural])) {
			$block = $data[$plural];

			if (isset($block[$singular])) {
				return $this->collection($block[$singular]);
			}

			if ($block === [] || array_key_exists(0, $block) || array_keys($block) === range(0, count($block) - 1)) {
				return $this->collection($block);
			}

			if (isset($block['id']) || isset($block['@attributes'])) {
				return [$block];
			}
		}

		if (isset($data[$singular])) {
			return $this->collection($data[$singular]);
		}

		return [];
	}

	private function friendlyError(string $message, int $httpCode = 0): string
	{
		$lower = mb_strtolower($message);

		if (strpos($lower, 'ssl') !== false || strpos($lower, 'certificate') !== false) {
			return 'SSL sertifika hatası: ' . $message;
		}

		if ($httpCode === 401 || $httpCode === 403) {
			return 'Yetki reddedildi (HTTP ' . $httpCode . '). API anahtarını ve WebService’te products GET iznini kontrol edin.';
		}

		if ($httpCode === 404) {
			return 'API adresi bulunamadı (HTTP 404). Mağaza URL’sine /api eklemeyin; rewrite kapalıysa adresi .../index.php olarak kaydedin.';
		}

		if (strpos($lower, '<html') !== false || strpos($lower, '<!doctype') !== false) {
			return 'Mağaza HTML sayfası döndü — URL yanlış veya webservice kapalı.';
		}

		if ($httpCode > 0 && $message === ('HTTP ' . $httpCode)) {
			return 'PrestaShop yanıtı: HTTP ' . $httpCode;
		}

		return $message !== '' ? $message : ('HTTP ' . $httpCode);
	}

	/**
	 * @param mixed $value
	 * @return array<int, mixed>
	 */
	private function collection($value): array
	{
		if (!is_array($value) || $value === []) {
			return [];
		}

		if (isset($value['id']) || isset($value['@attributes']) || isset($value['product_id'])) {
			return [$value];
		}

		$isList = array_keys($value) === range(0, count($value) - 1);

		return $isList ? $value : [$value];
	}

	/**
	 * @param array<string, string> $query
	 * @return array<string, mixed>
	 */
	private function get(string $resource, array $query = []): array
	{
		$query['output_format'] = 'JSON';
		$url = $this->apiUrl($resource, $query);

		return $this->decodeResponse($this->rawRequest($url, 'GET'));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function sendXml(string $resource, string $method, string $xml): array
	{
		$url = $this->apiUrl($resource, ['output_format' => 'JSON']);

		return $this->decodeResponse($this->rawRequest($url, $method, $xml, [
			'Content-Type: text/xml; charset=utf-8',
			'Authorization: Basic ' . base64_encode($this->apiKey . ':'),
		]));
	}

	/**
	 * @param array<string, string> $query
	 */
	private function apiUrl(string $resource, array $query = []): string
	{
		$resource = ltrim($resource, '/');
		$query['ws_key'] = $this->apiKey;

		return $this->baseUrl . '/api/' . $resource . '?' . http_build_query($query);
	}

	/**
	 * @param list<string> $headers
	 * @return array{body:string,code:int,error:string}
	 */
	private function rawRequest(string $url, string $method, string $body = '', array $headers = []): array
	{
		if (!$this->isConfigured()) {
			return ['body' => '', 'code' => 0, 'error' => 'PrestaShop API ayarları eksik'];
		}

		if (!function_exists('curl_init')) {
			return ['body' => '', 'code' => 0, 'error' => 'cURL eklentisi yok'];
		}

		$ch = curl_init($url);
		$this->applyCurl($ch);
		$defaultHeaders = [
			'Authorization: Basic ' . base64_encode($this->apiKey . ':'),
			'Accept: application/json, application/xml, text/xml, */*',
		];
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers !== [] ? array_merge($defaultHeaders, $headers) : $defaultHeaders);

		if ($body !== '') {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		$response = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = (string) curl_error($ch);
		curl_close($ch);

		return [
			'body' => is_string($response) ? $response : '',
			'code' => $code,
			'error' => $error,
		];
	}

	/**
	 * @param array{body:string,code:int,error:string} $raw
	 * @return array<string, mixed>
	 */
	private function decodeResponse(array $raw): array
	{
		if ($raw['error'] !== '' && $raw['body'] === '') {
			return ['ok' => false, 'success' => false, 'message' => $raw['error']];
		}

		if ($raw['code'] < 200 || $raw['code'] >= 300) {
			$message = $raw['error'] !== '' ? $raw['error'] : ('HTTP ' . $raw['code']);
			$trimmed = ltrim($raw['body']);

			if ($trimmed !== '' && ($trimmed[0] === '<' && stripos($trimmed, '<prestashop') === false && stripos($trimmed, '<?xml') === false)) {
				$message = 'Mağaza HTML döndü (HTTP ' . $raw['code'] . ')';
			}

			$decoded = $this->parseBody($raw['body']);

			if (is_array($decoded)) {
				$errors = $decoded['errors']['error'] ?? ($decoded['error'] ?? null);

				if (is_array($errors)) {
					$message = $this->scalarText($errors['message'] ?? ($errors[0]['message'] ?? $message));
				}
			}

			return ['ok' => false, 'success' => false, 'message' => $message, 'httpCode' => $raw['code']];
		}

		$decoded = $this->parseBody($raw['body']);

		return is_array($decoded) ? $decoded : ['ok' => true, 'success' => true, 'raw' => $raw['body']];
	}

	/** @return array<string, mixed>|null */
	private function parseBody(string $body): ?array
	{
		$body = trim($body);

		if ($body === '') {
			return [];
		}

		$json = json_decode($body, true);

		if (is_array($json)) {
			return $json;
		}

		if (!class_exists('SimpleXMLElement')) {
			return null;
		}

		$previous = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($body);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if ($xml === false) {
			return null;
		}

		$encoded = json_encode($xml);

		return is_string($encoded) ? (json_decode($encoded, true) ?: []) : [];
	}

	/** @param \CurlHandle|resource $ch */
	private function applyCurl($ch): void
	{
		$opts = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_SSL_VERIFYPEER => \Settings::get('PRESTASHOP_SSL_VERIFY') !== '0',
			CURLOPT_SSL_VERIFYHOST => \Settings::get('PRESTASHOP_SSL_VERIFY') !== '0' ? 2 : 0,
			CURLOPT_USERPWD => $this->apiKey . ':',
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERAGENT => 'FriSay-PrestaShop',
		];

		if (!class_exists('UpdateChecker', false)) {
			require_once dirname(__DIR__) . '/UpdateChecker.php';
		}

		$ca = \UpdateChecker::caBundlePath();

		if ($ca !== '') {
			$opts[CURLOPT_CAINFO] = $ca;
		}

		curl_setopt_array($ch, $opts);
	}
}
