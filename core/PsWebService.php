<?php

/**
 * PrestaShop WebService uyumluluk katmanı (geçiş).
 * Mevcut /api/v1 JSON Web API'sine dokunmaz.
 *
 * Entegrasyon firmalarının PrestaShop istemcisi
 * /api/orders?ws_key=...  ve  /api/orders/{id}?ws_key=...
 * ile XML veya JSON alır; kök eleman <prestashop> kalır.
 */
class PsWebService
{
	private const XLINK_NS = 'http://www.w3.org/1999/xlink';

	/** @var array<string, array{get?:bool,post?:bool,put?:bool,singular:string,perm:string,desc:string}> */
	private const RESOURCES = [
		'addresses' => ['get' => true, 'singular' => 'address', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'The Customer, Brand and Customer addresses'],
		'carriers' => ['get' => true, 'singular' => 'carrier', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'The Carriers'],
		'carts' => ['get' => true, 'singular' => 'cart', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => "Customer's carts"],
		'categories' => ['get' => true, 'singular' => 'category', 'perm' => ApiKey::PERM_CATEGORIES_READ, 'desc' => 'The product categories'],
		'combinations' => ['get' => true, 'singular' => 'combination', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'The product combinations'],
		'countries' => ['get' => true, 'singular' => 'country', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'The countries'],
		'currencies' => ['get' => true, 'singular' => 'currency', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'The currencies'],
		'customers' => ['get' => true, 'singular' => 'customer', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => "The e-shop's customers"],
		'languages' => ['get' => true, 'singular' => 'language', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'Shop languages'],
		'manufacturers' => ['get' => true, 'singular' => 'manufacturer', 'perm' => ApiKey::PERM_BRANDS_READ, 'desc' => 'The product brands'],
		'order_carriers' => ['get' => true, 'post' => true, 'put' => true, 'singular' => 'order_carrier', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'The Order carriers'],
		'order_histories' => ['get' => true, 'post' => true, 'singular' => 'order_history', 'perm' => ApiKey::PERM_ORDERS_WRITE, 'desc' => 'The Order histories'],
		'order_states' => ['get' => true, 'singular' => 'order_state', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'The Order statuses'],
		'orders' => ['get' => true, 'put' => true, 'singular' => 'order', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'The Customers orders'],
		'product_features' => ['get' => true, 'singular' => 'product_feature', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'The product features'],
		'product_option_values' => ['get' => true, 'singular' => 'product_option_value', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'The product options value'],
		'products' => ['get' => true, 'post' => true, 'put' => true, 'singular' => 'product', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'The products'],
		'shops' => ['get' => true, 'singular' => 'shop', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'Shops from multi-shop feature'],
		'shop_groups' => ['get' => true, 'singular' => 'shop_group', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'Shop groups from multi-shop feature'],
		'specific_prices' => ['get' => true, 'post' => true, 'put' => true, 'singular' => 'specific_price', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'Specific price management'],
		'states' => ['get' => true, 'singular' => 'state', 'perm' => ApiKey::PERM_ORDERS_READ, 'desc' => 'The available states of countries'],
		'stock_availables' => ['get' => true, 'put' => true, 'post' => true, 'singular' => 'stock_available', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'Available quantities'],
		'tags' => ['get' => true, 'singular' => 'tag', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'The Products tags'],
		'tax_rule_groups' => ['get' => true, 'singular' => 'tax_rule_group', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'Tax rule groups'],
		'tax_rules' => ['get' => true, 'singular' => 'tax_rule', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'Tax rules entity'],
		'taxes' => ['get' => true, 'singular' => 'tax', 'perm' => ApiKey::PERM_PRODUCTS_READ, 'desc' => 'The tax rate'],
	];

	/** @var string|null */
	private static $rawBody = null;

	/** @var array<string, mixed> */
	private static $requestLog = [];

	public static function dispatch(): void
	{
		self::beginRequestLog();

		try {
			self::dispatchInner();
		} catch (Throwable $e) {
			self::fail(500, $e->getMessage());
		}
	}

	private static function dispatchInner(): void
	{
		self::authenticate();

		$path = self::parsePath();
		$resource = $path['resource'];
		$id = $path['id'];
		$sub = $path['sub'];
		$subId = $path['sub_id'];
		$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
		$override = strtoupper((string) ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_REQUEST['_method'] ?? '')));
		if (in_array($override, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
			$method = $override;
		}
		if ($method === 'GET' && $resource === 'products' && stripos(self::rawBody(), '<product') !== false) {
			$method = $id > 0 ? 'PUT' : 'POST';
		}
		self::requestLogPathInfo($resource, $id, $sub);
		self::$requestLog['method'] = $method;

		if ($resource === '' && $method === 'GET') {
			self::respondIndex();
		}

		if ($resource === 'images') {
			if ($method === 'GET') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_READ);
				self::handleImages($id, $sub, $subId);
			}
			if ($method === 'POST' || $method === 'PUT') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_UPDATE);
				self::postProductImage($id, $sub, $subId);
			}
			if ($method === 'DELETE') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_UPDATE);
				self::deleteProductImage($id, $sub, $subId);
			}
			self::fail(405, 'Method not allowed for images');
		}

		if (!isset(self::RESOURCES[$resource])) {
			self::fail(404, 'Resource not found: ' . $resource);
		}

		$meta = self::RESOURCES[$resource];

		if ($method === 'GET') {
			if (empty($meta['get'])) {
				self::fail(405, 'Method GET not allowed for ' . $resource);
			}
			if ($resource === 'order_histories' || $resource === 'order_carriers') {
				ApiKey::requirePermission(ApiKey::PERM_ORDERS_READ);
			} elseif ($resource === 'stock_availables') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_READ);
			} else {
				ApiKey::requirePermission($meta['perm']);
			}
			self::handleGet($resource, $id);
		}

		if ($method === 'PUT') {
			if (empty($meta['put'])) {
				self::fail(405, 'Method PUT not allowed for ' . $resource);
			}
			if ($resource === 'orders') {
				ApiKey::requirePermission(ApiKey::PERM_ORDERS_WRITE);
				self::putOrder($id);
			}
			if ($resource === 'stock_availables') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_UPDATE);
				self::putStock($id);
			}
			if ($resource === 'products') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_UPDATE);
				self::putProduct($id);
			}
			if ($resource === 'specific_prices') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_UPDATE);
				self::postSpecificPrice();
			}
			if ($resource === 'order_carriers') {
				ApiKey::requirePermission(ApiKey::PERM_ORDERS_WRITE);
				self::postOrderCarrier($id);
			}
			self::fail(405, 'Method PUT not allowed for ' . $resource);
		}

		if ($method === 'POST') {
			if (empty($meta['post'])) {
				self::fail(405, 'Method POST not allowed for ' . $resource);
			}
			if ($resource === 'order_histories') {
				ApiKey::requirePermission(ApiKey::PERM_ORDERS_WRITE);
				self::postOrderHistory();
			}
			if ($resource === 'order_carriers') {
				ApiKey::requirePermission(ApiKey::PERM_ORDERS_WRITE);
				self::postOrderCarrier($id);
			}
			if ($resource === 'products') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_CREATE);
				self::postProduct($id);
			}
			if ($resource === 'stock_availables') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_UPDATE);
				self::putStock($id);
			}
			if ($resource === 'specific_prices') {
				ApiKey::requirePermission(ApiKey::PERM_PRODUCTS_UPDATE);
				self::postSpecificPrice();
			}
			self::fail(405, 'Method POST not allowed for ' . $resource);
		}

		self::fail(405, 'Unsupported method');
	}

	private static function authenticate(): void
	{
		require_once dirname(__DIR__) . '/core/ApiKey.php';
		ApiKey::ensureSchema();

		$provided = self::extractWsKey();
		$ip = RateLimit::clientIp();

		if ($provided === '') {
			if (RateLimit::isLimited(RateLimit::SCOPE_WEBAPI . '_fail', $ip, 30, 900)) {
				self::fail(429, 'Too many failed attempts');
			}
			RateLimit::record(RateLimit::SCOPE_WEBAPI . '_fail', $ip);
			header('WWW-Authenticate: Basic realm="Prestashop webservice"');
			self::fail(401, 'Authentication key is empty or invalid');
		}

		$auth = ApiKey::authenticateProvidedKey($provided);

		if (!$auth['ok']) {
			if (RateLimit::isLimited(RateLimit::SCOPE_WEBAPI . '_fail', $ip, 30, 900)) {
				self::fail(429, 'Too many failed attempts');
			}
			RateLimit::record(RateLimit::SCOPE_WEBAPI . '_fail', $ip);
			header('WWW-Authenticate: Basic realm="Prestashop webservice"');
			self::fail(401, (string) ($auth['message'] ?? 'Authentication key is empty or invalid'));
		}

		// Product link/sync clients (RestSharp etc.) burst many GETs; keep a high per-key ceiling.
		$keyId = max(0, (int) ($auth['id'] ?? 0));
		$bucket = $ip . '|k' . $keyId;
		$maxPerWindow = 6000;
		$windowSeconds = 900;

		if (RateLimit::isLimited(RateLimit::SCOPE_PSWS, $bucket, $maxPerWindow, $windowSeconds)) {
			header('Retry-After: 60');
			self::fail(429, 'Request limit exceeded');
		}

		RateLimit::record(RateLimit::SCOPE_PSWS, $bucket);
	}

	private static function extractWsKey(): string
	{
		foreach (['REDIRECT_HTTP_AUTHORIZATION', 'REDIRECT_REDIRECT_HTTP_AUTHORIZATION'] as $envKey) {
			if (empty($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER[$envKey])) {
				$_SERVER['HTTP_AUTHORIZATION'] = (string) $_SERVER[$envKey];
			}
		}

		$key = trim((string) ($_GET['ws_key'] ?? ''));

		if ($key !== '') {
			return $key;
		}

		$user = trim((string) ($_SERVER['PHP_AUTH_USER'] ?? ''));

		if ($user !== '') {
			return $user;
		}

		$auth = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));

		if ($auth === '' && function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
			if (is_array($headers)) {
				foreach ($headers as $name => $value) {
					if (strtolower((string) $name) === 'authorization') {
						$auth = trim((string) $value);
						break;
					}
				}
			}
		}

		if (preg_match('/^Basic\s+(.+)$/i', $auth, $m)) {
			$decoded = base64_decode($m[1], true);
			if (is_string($decoded) && $decoded !== '') {
				$parts = explode(':', $decoded, 2);

				return trim((string) ($parts[0] ?? ''));
			}
		}

		if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
			return trim($m[1]);
		}

		return trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
	}

	/** @return array{resource:string,id:int,sub:string,sub_id:int} */
	private static function parsePath(): array
	{
		$path = trim((string) ($_GET['ps_path'] ?? ''), '/');

		if ($path === '') {
			$uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
			$folder = trim((string) Settings::get('FOLDER'), '/');
			if ($folder !== '') {
				$prefix = '/' . $folder;
				if (strpos($uri, $prefix . '/') === 0 || $uri === $prefix) {
					$uri = substr($uri, strlen($prefix)) ?: '/';
				}
			}
			if (preg_match('#/api(?:/(.*))?$#', $uri, $m)) {
				$path = trim((string) ($m[1] ?? ''), '/');
			}
		}

		$path = preg_replace('#\.xml$|\.json$#i', '', $path) ?: '';
		$parts = array_values(array_filter(explode('/', $path), static function ($part) {
			return $part !== '';
		}));

		$resource = strtolower((string) ($parts[0] ?? ''));
		$id = 0;
		$sub = '';
		$subId = 0;

		if (isset($parts[1]) && ctype_digit((string) $parts[1])) {
			$id = (int) $parts[1];
			$sub = strtolower((string) ($parts[2] ?? ''));
			$subId = isset($parts[3]) && ctype_digit((string) $parts[3]) ? (int) $parts[3] : 0;
		} elseif (isset($parts[1])) {
			$sub = strtolower((string) $parts[1]);
			$id = isset($parts[2]) && ctype_digit((string) $parts[2]) ? (int) $parts[2] : 0;
			$subId = isset($parts[3]) && ctype_digit((string) $parts[3]) ? (int) $parts[3] : 0;
		}

		return [
			'resource' => $resource,
			'id' => $id,
			'sub' => $sub,
			'sub_id' => $subId,
		];
	}

	private static function handleGet(string $resource, int $id): void
	{
		$schema = strtolower(trim((string) ($_GET['schema'] ?? '')));
		if ($schema === 'blank' || $schema === 'synopsis') {
			self::respondSchema($resource, $schema);
		}

		if ($id > 0) {
			$row = self::loadOne($resource, $id);
			if ($row === null) {
				self::fail(404, 'Id not found: ' . $id);
			}
			$fields = self::displayFields();
			if ($fields !== null) {
				$row = self::pickFields($row, $fields);
			}
			self::respondEntity(self::RESOURCES[$resource]['singular'], $row);
		}

		$ids = self::listIds($resource);
		$full = self::isDisplayFull();
		$fields = self::displayFields();
		if ($resource === 'products' || $resource === 'stock_availables') {
			@set_time_limit(0);
		}
		if (in_array($resource, [
			'languages',
			'order_states',
			'countries',
			'states',
			'shops',
			'shop_groups',
			'taxes',
			'tax_rule_groups',
			'tax_rules',
			'manufacturers',
		], true)) {
			$full = true;
		}
		$rows = [];

		foreach ($ids as $rowId) {
			if ($full || $fields !== null) {
				try {
					$item = self::loadOne($resource, $rowId);
				} catch (Throwable $e) {
					continue;
				}
				if ($item === null) {
					continue;
				}
				if ($fields !== null) {
					$item = self::pickFields($item, $fields);
				}
				$rows[] = $item;
			} else {
				$rows[] = ['id' => $rowId];
			}
		}

		self::respondList($resource, self::RESOURCES[$resource]['singular'], $rows, $full || $fields !== null);
	}

	/** @return list<int> */
	private static function listIds(string $resource): array
	{
		$limit = self::parseLimit($resource);
		$sort = self::parseSort($resource);
		$filters = self::parseFilters();

		if ($resource === 'orders') {
			return self::orderIds($filters, $sort, $limit);
		}
		if ($resource === 'products') {
			return self::productIds($filters, $sort, $limit);
		}
		if ($resource === 'customers') {
			return self::customerIds($filters, $sort, $limit);
		}
		if ($resource === 'categories') {
			return self::tableIds('categories', 'id_category', $filters, $sort, $limit, ['active']);
		}
		if ($resource === 'manufacturers') {
			return self::tableIds('brands', 'id_brand', $filters, $sort, $limit, ['active']);
		}
		if ($resource === 'addresses') {
			return self::tableIds('orders', 'id_order', $filters, $sort, $limit, []);
		}
		if ($resource === 'carts') {
			return self::tableIds('orders', 'id_order', $filters, $sort, $limit, []);
		}
		if ($resource === 'carriers') {
			Cargo::ensureSchema();

			return self::tableIds('cargo_companies', 'id_cargo', $filters, $sort, $limit, ['active']);
		}
		if ($resource === 'stock_availables') {
			return self::productIds($filters, $sort, $limit);
		}
		if ($resource === 'order_states') {
			return array_keys(self::orderStateCatalog());
		}
		if ($resource === 'languages') {
			$all = array_keys(self::languages());
			$iso = strtolower((string) ($filters['iso_code'] ?? ''));
			if ($iso !== '') {
				$matched = [];
				foreach (self::languages() as $langId => $code) {
					if ($code === $iso || strpos($code, $iso) !== false) {
						$matched[] = $langId;
					}
				}

				return $matched !== [] ? $matched : $all;
			}

			return $all;
		}
		if ($resource === 'currencies') {
			return array_keys(self::currencyCatalog());
		}
		if ($resource === 'shops' || $resource === 'shop_groups' || $resource === 'countries') {
			return [1];
		}
		if ($resource === 'states') {
			return self::stateIds($filters);
		}
		if ($resource === 'tax_rule_groups' || $resource === 'taxes' || $resource === 'tax_rules') {
			return array_keys(self::taxCatalog());
		}
		if ($resource === 'order_histories' || $resource === 'order_carriers') {
			$orderFilter = (string) ($filters['id_order'] ?? '');
			if ($orderFilter !== '' && ctype_digit($orderFilter)) {
				$oid = (int) $orderFilter;

				return Order::getByIdAdmin($oid) ? [$oid] : [];
			}

			return self::orderIds($filters, $sort, $limit);
		}
		if ($resource === 'specific_prices') {
			return self::specificPriceIds($filters);
		}
		if ($resource === 'combinations'
			|| $resource === 'product_option_values'
			|| $resource === 'product_features'
			|| $resource === 'tags'
		) {
			return [];
		}

		return [];
	}

	/** @return array<string, mixed>|null */
	private static function loadOne(string $resource, int $id): ?array
	{
		if ($resource === 'orders') {
			return self::formatOrder($id);
		}
		if ($resource === 'addresses') {
			return self::formatAddress($id);
		}
		if ($resource === 'customers') {
			return self::formatCustomer($id);
		}
		if ($resource === 'products') {
			return self::formatProduct($id);
		}
		if ($resource === 'categories') {
			return self::formatCategory($id);
		}
		if ($resource === 'manufacturers') {
			return self::formatManufacturer($id);
		}
		if ($resource === 'carriers') {
			return self::formatCarrier($id);
		}
		if ($resource === 'carts') {
			return self::formatCart($id);
		}
		if ($resource === 'order_states') {
			return self::formatOrderState($id);
		}
		if ($resource === 'languages') {
			return self::formatLanguage($id);
		}
		if ($resource === 'currencies') {
			return self::formatCurrency($id);
		}
		if ($resource === 'shops') {
			return $id === 1 ? self::formatShop() : null;
		}
		if ($resource === 'shop_groups') {
			return $id === 1 ? [
				'id' => 1,
				'name' => 'Default',
				'active' => '1',
				'share_customer' => '0',
				'share_stock' => '0',
				'share_order' => '0',
				'deleted' => '0',
			] : null;
		}
		if ($resource === 'countries') {
			return $id === 1 ? [
				'id' => 1,
				'id_zone' => 1,
				'id_currency' => 1,
				'iso_code' => 'TR',
				'call_prefix' => 90,
				'active' => 1,
				'contains_states' => 1,
				'need_identification_number' => 0,
				'need_zip_code' => 0,
				'zip_code_format' => '',
				'display_tax_label' => 1,
				'name' => 'Türkiye',
			] : null;
		}
		if ($resource === 'stock_availables') {
			return self::formatStock($id);
		}
		if ($resource === 'tax_rule_groups') {
			return self::formatTaxRuleGroup($id);
		}
		if ($resource === 'tax_rules') {
			return self::formatTaxRule($id);
		}
		if ($resource === 'taxes') {
			return self::formatTax($id);
		}
		if ($resource === 'order_histories') {
			return self::formatOrderHistory($id);
		}
		if ($resource === 'order_carriers') {
			return self::formatOrderCarrier($id);
		}
		if ($resource === 'specific_prices') {
			return self::formatSpecificPrice($id);
		}
		if ($resource === 'states') {
			return self::formatState($id);
		}

		return null;
	}

	private static function putOrder(int $id): void
	{
		$body = self::parseBody();
		$node = is_array($body['order'] ?? null) ? $body['order'] : $body;
		if ($id <= 0) {
			$id = (int) self::scalar($node['id'] ?? 0);
		}
		if ($id <= 0) {
			self::fail(400, 'Order id is required');
		}

		$order = Order::getByIdAdmin($id);

		if (!$order) {
			self::fail(404, 'Id not found: ' . $id);
		}

		$payload = [];

		if (array_key_exists('current_state', $node)) {
			$payload['status'] = self::friSayStatusFromPs((int) self::scalar($node['current_state']));
		}
		if (array_key_exists('shipping_number', $node)) {
			$payload['tracking_number'] = (string) self::scalar($node['shipping_number']);
		}
		if (array_key_exists('id_carrier', $node)) {
			$carrierId = (int) self::scalar($node['id_carrier']);
			$cargo = Cargo::getById($carrierId);
			if ($cargo) {
				$payload['cargo_company'] = (string) ($cargo['name'] ?? '');
			}
		}

		if ($payload === []) {
			self::fail(400, 'Nothing to update');
		}

		$result = Order::updateFromApi($id, $payload);

		if (empty($result['success'])) {
			self::fail(400, (string) ($result['message'] ?? 'Update failed'));
		}

		$row = self::formatOrder($id);
		self::respondEntity('order', $row ?: ['id' => $id]);
	}

	private static function putStock(int $id): void
	{
		$body = self::parseBody();
		$nodes = self::extractStockAvailableNodes($body);

		if ($nodes === []) {
			self::fail(400, 'id_product and quantity are required');
		}

		$updated = [];
		$errors = [];

		foreach ($nodes as $node) {
			if (!is_array($node)) {
				continue;
			}

			$productId = (int) self::scalar($node['id_product'] ?? 0);

			if ($productId <= 0) {
				$productId = (int) self::scalar($node['id'] ?? 0);
			}

			if ($productId <= 0) {
				$productId = $id;
			}

			if (!array_key_exists('quantity', $node)) {
				$errors[] = 'quantity is required for product ' . max(0, $productId);
				continue;
			}

			$qty = (int) self::scalar($node['quantity']);

			if ($productId <= 0 || $qty < 0) {
				$errors[] = 'id_product and quantity are required';
				continue;
			}

			$result = Product::patchQuick($productId, ['stock' => $qty]);

			if (empty($result['success'])) {
				$errors[] = (string) ($result['message'] ?? ('Stock update failed for product ' . $productId));
				continue;
			}

			$row = self::formatStock($productId);
			$updated[] = $row ?: [
				'id' => $productId,
				'id_product' => $productId,
				'quantity' => $qty,
			];
		}

		if ($updated === []) {
			self::fail(400, $errors[0] ?? 'id_product and quantity are required');
		}

		if (count($updated) === 1) {
			self::respondEntity('stock_available', $updated[0]);
		}

		self::respondList('stock_availables', 'stock_available', $updated, true);
	}

	/**
	 * Normalize PUT/POST body into a list of stock_available rows.
	 * Supports single node, numeric list (bulk XML from RestSharp), and stock_availables wrapper.
	 *
	 * @param array<string, mixed> $body
	 * @return list<array<string, mixed>>
	 */
	private static function extractStockAvailableNodes(array $body): array
	{
		if (isset($body['stock_available'])) {
			$node = $body['stock_available'];

			if (!is_array($node)) {
				return [];
			}

			if (self::isListArray($node)) {
				$out = [];

				foreach ($node as $item) {
					if (is_array($item)) {
						$out[] = $item;
					}
				}

				return $out;
			}

			return [$node];
		}

		if (isset($body['stock_availables']) && is_array($body['stock_availables'])) {
			$wrap = $body['stock_availables'];

			if (isset($wrap['stock_available']) && is_array($wrap['stock_available'])) {
				return self::extractStockAvailableNodes(['stock_available' => $wrap['stock_available']]);
			}

			if (self::isListArray($wrap)) {
				$out = [];

				foreach ($wrap as $item) {
					if (is_array($item)) {
						$out[] = $item;
					}
				}

				return $out;
			}
		}

		if (isset($body['id_product']) || isset($body['quantity']) || isset($body['id'])) {
			return [$body];
		}

		return [];
	}

	/** @param array<mixed> $arr */
	private static function isListArray(array $arr): bool
	{
		if ($arr === []) {
			return true;
		}

		return array_keys($arr) === range(0, count($arr) - 1);
	}

	private static function postOrderCarrier(int $id = 0): void
	{
		$body = self::parseBody();
		$node = is_array($body['order_carrier'] ?? null) ? $body['order_carrier'] : $body;
		$orderId = (int) self::scalar($node['id_order'] ?? 0);
		if ($orderId <= 0) {
			$orderId = $id > 0 ? $id : (int) self::scalar($node['id'] ?? 0);
		}

		if ($orderId <= 0 || !Order::getByIdAdmin($orderId)) {
			self::fail(404, 'Id not found: ' . $orderId);
		}

		$payload = [];
		if (array_key_exists('tracking_number', $node)) {
			$payload['tracking_number'] = (string) self::scalar($node['tracking_number']);
		}
		if (array_key_exists('id_carrier', $node)) {
			$carrierId = (int) self::scalar($node['id_carrier']);
			if ($carrierId > 0 && class_exists('Cargo')) {
				$cargo = Cargo::getById($carrierId);
				if ($cargo) {
					$payload['cargo_company'] = (string) ($cargo['name'] ?? '');
				}
			}
		}

		if ($payload !== []) {
			$result = Order::updateFromApi($orderId, $payload);
			if (empty($result['success'])) {
				self::fail(400, (string) ($result['message'] ?? 'Carrier update failed'));
			}
		}

		http_response_code(201);
		self::respondEntity('order_carrier', self::formatOrderCarrier($orderId) ?: ['id' => $orderId]);
	}

	private static function postOrderHistory(): void
	{
		$body = self::parseBody();
		$node = is_array($body['order_history'] ?? null) ? $body['order_history'] : $body;
		$orderId = (int) self::scalar($node['id_order'] ?? 0);
		$stateId = (int) self::scalar($node['id_order_state'] ?? ($node['id_order_state'] ?? 0));

		if ($orderId <= 0 || $stateId <= 0) {
			self::fail(400, 'id_order and id_order_state are required');
		}

		$result = Order::updateFromApi($orderId, ['status' => self::friSayStatusFromPs($stateId)]);

		if (empty($result['success'])) {
			self::fail(400, (string) ($result['message'] ?? 'Status update failed'));
		}

		http_response_code(201);
		self::respondEntity('order_history', self::formatOrderHistory($orderId) ?: [
			'id' => $orderId,
			'id_order' => $orderId,
			'id_order_state' => $stateId,
			'id_employee' => 0,
			'date_add' => date('Y-m-d H:i:s'),
		]);
	}

	private static function postProduct(int $id = 0): void
	{
		self::saveProductFromBody($id, true);
	}

	private static function putProduct(int $id): void
	{
		self::saveProductFromBody($id, false);
	}

	private static function saveProductFromBody(int $id, bool $isNew): void
	{
		$body = self::parseBody();
		$node = is_array($body['product'] ?? null) ? $body['product'] : $body;
		$payload = self::mapPrestashopProduct($node);
		$xmlId = (int) self::scalar($node['id'] ?? 0);
		if ($id <= 0 && $xmlId > 0) {
			$id = $xmlId;
		}
		if ($id <= 0) {
			$id = self::findExistingProductId($payload);
		}
		if (!$isNew && $id <= 0) {
			self::fail(400, 'Product id is required');
		}

		$existed = $id > 0 && Product::getByIdAdmin($id);
		if ($existed) {
			$payload = self::mergeExistingProduct($payload, Product::getByIdAdmin($id) ?: [], $node);
		}
		$payload['product_link'] = self::uniqueProductSlug(
			(string) ($payload['product_link'] ?? ''),
			$existed ? $id : 0,
			(string) ($payload['stock_code'] ?? '')
		);
		$result = Product::save($payload, $id);

		if (empty($result['success'])) {
			$payload['product_link'] = self::uniqueProductSlug(
				(string) ($payload['product_link'] ?? 'urun'),
				$existed ? $id : 0,
				(string) ($payload['stock_code'] ?? '') . '-' . substr(sha1(uniqid('', true)), 0, 6)
			);
			$result = Product::save($payload, $id);
		}

		if (empty($result['success'])) {
			self::fail(400, (string) ($result['message'] ?? 'Product save failed'));
		}

		$savedId = (int) ($result['id'] ?? $id);
		self::reapplyStoredSpecificPrice($savedId);
		$row = self::formatProduct($savedId);
		if (!$existed) {
			http_response_code(201);
		}
		self::respondEntity('product', $row ?: ['id' => $savedId]);
	}

	/**
	 * @param array<string, mixed> $node
	 * @return array<string, mixed>
	 */
	private static function mapPrestashopProduct(array $node): array
	{
		$name = self::bodyText($node['name'] ?? '');
		$slug = self::bodyText($node['link_rewrite'] ?? '');
		$description = self::bodyText($node['description'] ?? '');
		$short = self::bodyText($node['description_short'] ?? '');
		$metaTitle = self::bodyText($node['meta_title'] ?? '');
		$metaDescription = self::bodyText($node['meta_description'] ?? '');

		$idBrand = (int) self::scalar($node['id_manufacturer'] ?? 0);
		if ($idBrand <= 0) {
			$firstBrand = DB::getValue('SELECT id_brand FROM brands WHERE active = 1 ORDER BY id_brand ASC LIMIT 1');
			$idBrand = (int) $firstBrand;
		}

		$idCategory = (int) self::scalar($node['id_category_default'] ?? 0);
		if ($idCategory <= 0) {
			$idCategory = self::firstAssocId($node, 'categories', 'category');
		}
		if ($idCategory <= 0) {
			$firstCat = DB::getValue('SELECT id_category FROM categories WHERE active = 1 ORDER BY id_category ASC LIMIT 1');
			$idCategory = (int) $firstCat;
		}
		$taxGroup = (int) self::scalar($node['id_tax_rules_group'] ?? 0);
		$vat = Tax::getDefaultRate();
		if ($taxGroup > 0 && isset(self::taxCatalog()[$taxGroup])) {
			$vat = (float) self::taxCatalog()[$taxGroup]['rate'];
		}

		$virtual = (int) self::scalar($node['is_virtual'] ?? 0) === 1;
		$exVat = (float) self::scalar($node['price'] ?? 0);

		$payload = [
			'product_name' => $name,
			'product_link' => $slug,
			'description' => $description,
			'short_description' => $short,
			'meta_title' => $metaTitle,
			'meta_description' => $metaDescription,
			'id_category' => $idCategory,
			'id_brand' => $idBrand,
			'id_supplier' => (int) self::scalar($node['id_supplier'] ?? 0),
			'stock_code' => (string) self::scalar($node['reference'] ?? ''),
			'barcode' => (string) self::scalar($node['ean13'] ?? ($node['upc'] ?? '')),
			'price' => self::priceInclusive($exVat, $vat),
			'cost' => (float) self::scalar($node['wholesale_price'] ?? 0),
			'vat' => $vat,
			'active' => (int) self::scalar($node['active'] ?? 1) === 1 ? 1 : 0,
			'product_type' => $virtual ? 'virtual' : 'physical',
		];
		if (array_key_exists('quantity', $node)) {
			$payload['stock'] = max(0, (int) self::scalar($node['quantity'] ?? 0));
		}

		return $payload;
	}

	/**
	 * Prapazar fiyat güncellemesinde ad=slug, stok/maliyet gelmez; mevcut değerleri koru.
	 *
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $existing
	 * @param array<string, mixed> $node
	 * @return array<string, mixed>
	 */
	private static function mergeExistingProduct(array $payload, array $existing, array $node): array
	{
		$name = trim((string) ($payload['product_name'] ?? ''));
		$slug = trim((string) ($payload['product_link'] ?? ''));
		if ($name === '' || ($slug !== '' && strcasecmp($name, $slug) === 0)) {
			$payload['product_name'] = (string) ($existing['product_name'] ?? '');
		}
		if (trim((string) ($payload['description'] ?? '')) === '') {
			$payload['description'] = (string) ($existing['description'] ?? '');
		}
		if (trim((string) ($payload['short_description'] ?? '')) === '') {
			$payload['short_description'] = (string) ($existing['short_description'] ?? '');
		}
		if (!array_key_exists('quantity', $node)) {
			$payload['stock'] = (float) ($existing['stock'] ?? 0);
		}
		if ((float) ($payload['cost'] ?? 0) <= 0 && (float) ($existing['cost'] ?? 0) > 0) {
			$payload['cost'] = (float) $existing['cost'];
		}
		if (trim((string) ($payload['product_link'] ?? '')) === '') {
			$payload['product_link'] = (string) ($existing['product_link'] ?? '');
		}

		return $payload;
	}

	private static function priceInclusive(float $exVat, float $vat): float
	{
		if ($exVat <= 0) {
			return 0.0;
		}
		if ($vat <= 0) {
			return round($exVat, 2);
		}

		return round($exVat * (1 + $vat / 100), 2);
	}

	private static function priceExclusive(float $inclusive, float $vat): float
	{
		if ($inclusive <= 0) {
			return 0.0;
		}
		if ($vat <= 0) {
			return $inclusive;
		}

		return $inclusive / (1 + $vat / 100);
	}

	/**
	 * PrestaShop product.price is the catalog (before specific_prices), not the selling price.
	 *
	 * @param array<string, mixed> $product
	 */
	private static function catalogInclusivePrice(array $product): float
	{
		$price = (float) ($product['price'] ?? 0);
		$old = (float) ($product['old_price'] ?? 0);

		return $old > $price ? $old : $price;
	}

	/** @param array<string, mixed> $payload */
	private static function findExistingProductId(array $payload): int
	{
		$barcode = trim((string) ($payload['barcode'] ?? ''));
		if ($barcode !== '') {
			$found = DB::getValue('SELECT id_product FROM products WHERE barcode = ? LIMIT 1', [$barcode]);
			if ($found) {
				return (int) $found;
			}
		}

		$code = trim((string) ($payload['stock_code'] ?? ''));
		if ($code !== '') {
			$found = DB::getValue('SELECT id_product FROM products WHERE stock_code = ? LIMIT 1', [$code]);
			if ($found) {
				return (int) $found;
			}
		}

		$slug = trim((string) ($payload['product_link'] ?? ''));
		if ($slug !== '') {
			$found = DB::getValue('SELECT id_product FROM products WHERE product_link = ? LIMIT 1', [$slug]);
			if ($found) {
				return (int) $found;
			}
		}

		return 0;
	}

	private static function uniqueProductSlug(string $slug, int $excludeId, string $ref = ''): string
	{
		$slug = Tools::createSlug($slug);
		if ($slug === '') {
			$slug = 'urun';
		}

		self::releaseOrphanProductLang($slug);
		if ($ref !== '') {
			self::releaseOrphanProductLang($slug . '-' . Tools::createSlug($ref));
		}

		if (Product::isLinkUnique($slug, $excludeId)) {
			return $slug;
		}

		if ($excludeId > 0) {
			$existing = Product::getByIdAdmin($excludeId);
			$keep = trim((string) ($existing['product_link'] ?? ''));
			if ($keep !== '') {
				return $keep;
			}
		}

		$candidates = [];
		if ($ref !== '') {
			$candidates[] = $slug . '-' . Tools::createSlug($ref);
		}
		for ($n = 2; $n <= 80; $n++) {
			$candidates[] = $slug . '-' . $n;
		}
		$candidates[] = $slug . '-' . substr(sha1(uniqid((string) mt_rand(), true)), 0, 8);

		foreach ($candidates as $candidate) {
			$candidate = mb_substr($candidate, 0, 128);
			self::releaseOrphanProductLang($candidate);
			if (Product::isLinkUnique($candidate, $excludeId)) {
				return $candidate;
			}
		}

		return mb_substr($slug . '-' . (string) time(), 0, 128);
	}

	private static function releaseOrphanProductLang(string $slug): void
	{
		$slug = trim($slug);
		if ($slug === '') {
			return;
		}

		$orphans = DB::execute(
			'SELECT DISTINCT pl.id_product
			 FROM product_lang pl
			 LEFT JOIN products p ON p.id_product = pl.id_product
			 WHERE pl.product_link = ? AND p.id_product IS NULL',
			[$slug]
		) ?: [];

		foreach ($orphans as $row) {
			$orphanId = (int) ($row['id_product'] ?? 0);
			if ($orphanId > 0) {
				DB::execute('DELETE FROM product_lang WHERE id_product = ?', [$orphanId]);
			}
		}
	}

	/** @param mixed $value */
	private static function bodyText($value): string
	{
		if (is_scalar($value) || $value === null) {
			return trim((string) $value);
		}
		if (!is_array($value)) {
			return '';
		}
		if (array_key_exists('language', $value)) {
			return self::bodyText($value['language']);
		}
		if (isset($value[0]) && !is_array($value[0])) {
			return trim((string) $value[0]);
		}
		if (isset($value['#text'])) {
			return trim((string) $value['#text']);
		}

		foreach ($value as $key => $child) {
			if ($key === '@attributes' || $key === 'id') {
				continue;
			}
			$text = self::bodyText($child);
			if ($text !== '') {
				return $text;
			}
		}

		return '';
	}

	/** @param array<string, mixed> $node */
	private static function firstAssocId(array $node, string $group, string $singular): int
	{
		$assoc = $node['associations'] ?? [];
		if (!is_array($assoc)) {
			return 0;
		}
		$block = $assoc[$group] ?? [];
		if (!is_array($block)) {
			return 0;
		}
		$item = $block[$singular] ?? $block;
		if (isset($item[0]) && is_array($item[0])) {
			$item = $item[0];
		}

		return (int) self::scalar(is_array($item) ? ($item['id'] ?? $item) : $item);
	}

	private static function handleImages(int $productId, string $kind, int $imageId): void
	{
		if ($kind === '' && $productId <= 0) {
			self::respondImageTypeIndex();
		}

		if ($kind !== 'products') {
			self::fail(404, 'Image resource not found');
		}

		if ($productId <= 0) {
			self::respondProductImageList(0);
		}

		if (!Product::getByIdAdmin($productId)) {
			self::fail(404, 'Id not found: ' . $productId);
		}

		if ($imageId <= 0) {
			self::respondProductImageList($productId);
		}

		$file = dirname(__DIR__) . '/img/products/' . $imageId . '.jpg';

		if (!is_file($file)) {
			self::fail(404, 'Image not found');
		}

		header('Content-Type: image/jpeg');
		header('Content-Length: ' . (string) filesize($file));
		readfile($file);
		exit;
	}

	private static function respondImageTypeIndex(): void
	{
		$doc = self::xmlDoc();
		$image = $doc->createElement('image');
		foreach (['general', 'products', 'categories', 'manufacturers', 'suppliers', 'stores'] as $type) {
			$el = $doc->createElement($type);
			$el->setAttributeNS(self::XLINK_NS, 'xlink:href', self::baseUrl() . '/api/images/' . $type);
			$image->appendChild($el);
		}
		$doc->documentElement->appendChild($image);
		self::sendXml($doc);
	}

	private static function respondProductImageList(int $productId): void
	{
		$doc = self::xmlDoc();
		$image = $doc->createElement('image');
		if ($productId > 0) {
			$image->setAttribute('id', (string) $productId);
			$image->setAttributeNS(self::XLINK_NS, 'xlink:href', self::baseUrl() . '/api/images/products/' . $productId);
			foreach (Product::getImages($productId) as $row) {
				$id = (int) ($row['id_image'] ?? 0);
				if ($id <= 0) {
					continue;
				}
				$decl = $doc->createElement('declination');
				$decl->setAttribute('id', (string) $id);
				$decl->setAttributeNS(self::XLINK_NS, 'xlink:href', self::baseUrl() . '/api/images/products/' . $productId . '/' . $id);
				$image->appendChild($decl);
			}
		}
		$doc->documentElement->appendChild($image);
		self::sendXml($doc);
	}

	private static function postProductImage(int $productId, string $kind, int $imageId): void
	{
		if ($kind !== 'products' || $productId <= 0) {
			self::fail(404, 'Image resource not found');
		}

		if (!Product::getByIdAdmin($productId)) {
			self::fail(404, 'Id not found: ' . $productId);
		}

		$result = null;
		$file = self::uploadedImageFile();
		if (is_array($file) && !empty($file['tmp_name']) && is_readable((string) $file['tmp_name'])) {
			$binary = file_get_contents((string) $file['tmp_name']);
			if (is_string($binary) && $binary !== '') {
				$result = Product::importImageBinary($productId, $binary);
			}
		}
		if (!is_array($result) || empty($result['success'])) {
			$raw = self::rawBody();
			if ($raw !== '') {
				$result = Product::importImageBinary($productId, $raw);
			}
		}

		if (!is_array($result) || empty($result['success'])) {
			self::fail(400, (string) ($result['message'] ?? 'Image upload failed'));
		}

		http_response_code(201);
		self::respondEntity('image', [
			'id' => (int) ($result['id_image'] ?? $result['id'] ?? 0),
			'id_product' => $productId,
			'position' => 1,
			'cover' => (int) ($result['cover'] ?? 1),
			'legend' => '',
		]);
	}

	private static function deleteProductImage(int $productId, string $kind, int $imageId): void
	{
		if ($kind !== 'products' || $productId <= 0 || $imageId <= 0) {
			self::fail(404, 'Image resource not found');
		}

		if (!Product::getByIdAdmin($productId)) {
			self::fail(404, 'Id not found: ' . $productId);
		}

		$belongs = false;
		foreach (Product::getImages($productId) as $row) {
			if ((int) ($row['id_image'] ?? 0) === $imageId) {
				$belongs = true;
				break;
			}
		}

		if ($belongs) {
			$result = Product::deleteImage($imageId);
			if (empty($result['success'])) {
				self::fail(400, (string) ($result['message'] ?? 'Image delete failed'));
			}
		}

		http_response_code(200);
		self::respondEntity('image', [
			'id' => $imageId,
			'id_product' => $productId,
		]);
	}

	/** @return array<string, mixed>|null */
	private static function uploadedImageFile(): ?array
	{
		foreach (['image', 'file', 'images'] as $key) {
			$candidate = $_FILES[$key] ?? null;
			if (!is_array($candidate)) {
				continue;
			}
			if (!empty($candidate['tmp_name']) && is_string($candidate['tmp_name'])) {
				return $candidate;
			}
			if (isset($candidate['tmp_name'][0]) && is_string($candidate['tmp_name'][0])) {
				return [
					'name' => is_array($candidate['name'] ?? null) ? (string) ($candidate['name'][0] ?? '') : '',
					'tmp_name' => $candidate['tmp_name'][0],
					'type' => is_array($candidate['type'] ?? null) ? (string) ($candidate['type'][0] ?? '') : '',
					'error' => is_array($candidate['error'] ?? null) ? (int) ($candidate['error'][0] ?? 0) : 0,
					'size' => is_array($candidate['size'] ?? null) ? (int) ($candidate['size'][0] ?? 0) : 0,
				];
			}
		}
		foreach ($_FILES as $candidate) {
			if (!is_array($candidate) || empty($candidate['tmp_name'])) {
				continue;
			}
			if (is_string($candidate['tmp_name'])) {
				return $candidate;
			}
		}

		return null;
	}

	private static function postSpecificPrice(): void
	{
		$body = self::parseBody();
		$node = is_array($body['specific_price'] ?? null) ? $body['specific_price'] : $body;
		$productId = (int) self::scalar($node['id_product'] ?? 0);
		$id = (int) self::scalar($node['id'] ?? 0);
		if ($id <= 0) {
			$id = $productId > 0 ? $productId : 1;
		}

		$row = [
			'id' => $id,
			'id_shop_group' => (int) self::scalar($node['id_shop_group'] ?? 0),
			'id_shop' => (int) self::scalar($node['id_shop'] ?? 0),
			'id_cart' => (int) self::scalar($node['id_cart'] ?? 0),
			'id_product' => $productId,
			'id_product_attribute' => (int) self::scalar($node['id_product_attribute'] ?? 0),
			'id_currency' => (int) self::scalar($node['id_currency'] ?? 0),
			'id_country' => (int) self::scalar($node['id_country'] ?? 0),
			'id_group' => (int) self::scalar($node['id_group'] ?? 0),
			'id_customer' => (int) self::scalar($node['id_customer'] ?? 0),
			'id_specific_price_rule' => (int) self::scalar($node['id_specific_price_rule'] ?? 0),
			'price' => (string) self::scalar($node['price'] ?? '-1.000000'),
			'from_quantity' => (int) self::scalar($node['from_quantity'] ?? 1),
			'reduction' => (string) self::scalar($node['reduction'] ?? '0.000000'),
			'reduction_tax' => (int) self::scalar($node['reduction_tax'] ?? 1),
			'reduction_type' => (string) self::scalar($node['reduction_type'] ?? 'amount'),
			'from' => (string) self::scalar($node['from'] ?? '0000-00-00 00:00:00'),
			'to' => (string) self::scalar($node['to'] ?? '0000-00-00 00:00:00'),
		];
		self::storeSpecificPrice($id, $productId, $row);
		self::applySpecificPriceToProduct($productId, $row);

		http_response_code(201);
		self::respondEntity('specific_price', $row);
	}

	private static function ensureSpecificPricesSchema(): void
	{
		static $ready = false;
		if ($ready) {
			return;
		}
		$ready = true;
		$exists = DB::execute("SHOW TABLES LIKE 'psws_specific_prices'");
		if (!empty($exists)) {
			return;
		}
		DB::execute(
			"CREATE TABLE `psws_specific_prices` (
				`id` int(11) NOT NULL,
				`id_product` int(11) NOT NULL DEFAULT 0,
				`data` mediumtext NOT NULL,
				PRIMARY KEY (`id`),
				KEY `id_product` (`id_product`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return list<int>
	 */
	private static function specificPriceIds(array $filters): array
	{
		self::ensureSpecificPricesSchema();
		$pid = trim((string) ($filters['id_product'] ?? ''));
		if ($pid !== '' && ctype_digit($pid)) {
			$rows = DB::execute(
				'SELECT id FROM psws_specific_prices WHERE id_product = ? ORDER BY id DESC',
				[(int) $pid]
			) ?: [];
		} else {
			$rows = DB::execute('SELECT id FROM psws_specific_prices ORDER BY id DESC') ?: [];
		}
		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) ($row['id'] ?? 0);
		}

		return $ids;
	}

	/** @return array<string, mixed>|null */
	private static function formatSpecificPrice(int $id): ?array
	{
		self::ensureSpecificPricesSchema();
		$row = DB::getRowSafe('psws_specific_prices', 'id = ?', [$id]);
		if (!$row) {
			return null;
		}
		$decoded = json_decode((string) ($row['data'] ?? ''), true);

		return is_array($decoded) ? $decoded : null;
	}

	/** @param array<string, mixed> $row */
	private static function storeSpecificPrice(int $id, int $productId, array $row): void
	{
		if ($id <= 0) {
			return;
		}
		self::ensureSpecificPricesSchema();
		$json = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			$json = '{}';
		}
		$existing = DB::getRowSafe('psws_specific_prices', 'id = ?', [$id]);
		if ($existing) {
			DB::update(
				'psws_specific_prices',
				['id_product' => $productId, 'data' => $json],
				'id = :where_id',
				['where_id' => $id]
			);

			return;
		}
		DB::insert('psws_specific_prices', [
			'id' => $id,
			'id_product' => $productId,
			'data' => $json,
		]);
	}

	/** @param array<string, mixed> $sp */
	private static function applySpecificPriceToProduct(int $productId, array $sp): void
	{
		if ($productId <= 0) {
			return;
		}
		$product = Product::getByIdAdmin($productId);
		if (!$product) {
			return;
		}

		$vat = (float) ($product['vat'] ?? 0);
		$catalog = self::catalogInclusivePrice($product);
		$type = strtolower((string) ($sp['reduction_type'] ?? 'amount'));
		$reduction = (float) str_replace(',', '.', (string) ($sp['reduction'] ?? '0'));
		$reductionTax = (int) ($sp['reduction_tax'] ?? 1);
		if ($reduction <= 0 || $catalog <= 0) {
			return;
		}

		if ($type === 'percentage') {
			$newPrice = $catalog * (1 - min(100.0, $reduction) / 100);
		} else {
			$disc = $reductionTax === 1 ? $reduction : $reduction * (1 + $vat / 100);
			$newPrice = $catalog - $disc;
		}
		$newPrice = max(0, round($newPrice, 2));
		$oldPrice = $catalog > $newPrice ? round($catalog, 2) : 0.0;
		Product::patchQuick($productId, [
			'price' => $newPrice,
			'old_price' => $oldPrice,
		]);
	}

	private static function reapplyStoredSpecificPrice(int $productId): void
	{
		if ($productId <= 0) {
			return;
		}
		self::ensureSpecificPricesSchema();
		$json = DB::getValue(
			'SELECT data FROM psws_specific_prices WHERE id_product = ? ORDER BY id DESC LIMIT 1',
			[$productId]
		);
		if (!is_string($json) || $json === '') {
			return;
		}
		$decoded = json_decode($json, true);
		if (!is_array($decoded)) {
			return;
		}
		self::applySpecificPriceToProduct($productId, $decoded);
	}

	/** @return array<string, mixed>|null */
	private static function formatOrder(int $id): ?array
	{
		$order = Order::getByIdAdmin($id);

		if (!$order) {
			return null;
		}

		$langId = self::defaultLangId();
		$carrierId = self::carrierIdForOrder($order);
		$customerId = (int) ($order['id_user'] ?? 0);
		$addressId = $id;
		$total = (float) ($order['total'] ?? 0);
		$subtotal = (float) ($order['subtotal'] ?? 0);
		$shipping = (float) ($order['shipping'] ?? 0);
		$discount = (float) ($order['coupon_discount'] ?? 0) + (float) ($order['promotion_discount'] ?? 0)
			+ (float) ($order['payment_discount'] ?? 0) + (float) ($order['manual_discount'] ?? 0);
		$wrap = (float) ($order['gift_wrap_fee'] ?? 0);
		$vat = 0.0;
		$rows = [];

		foreach ($order['items'] ?? [] as $item) {
			$lineVat = (float) ($item['vat'] ?? 0);
			if ($lineVat > $vat) {
				$vat = $lineVat;
			}
			$qty = (float) ($item['qty'] ?? 0);
			$unit = (float) ($item['price'] ?? 0);
			$excl = $lineVat > 0 ? round($unit / (1 + $lineVat / 100), 6) : $unit;
			$rows[] = [
				'id' => (int) ($item['id_order_detail'] ?? 0),
				'product_id' => (int) ($item['id_product'] ?? 0),
				'product_attribute_id' => (int) ($item['id_variation'] ?? 0),
				'product_quantity' => (int) $qty,
				'product_name' => (string) ($item['product_name'] ?? ''),
				'product_reference' => (string) ($item['stock_code'] ?? ''),
				'product_ean13' => (string) ($item['barcode'] ?? ''),
				'product_isbn' => '',
				'product_upc' => '',
				'product_price' => self::money($excl),
				'id_customization' => 0,
				'unit_price_tax_incl' => self::money($unit),
				'unit_price_tax_excl' => self::money($excl),
			];
		}

		$taxRate = $vat > 0 ? $vat : 20.0;
		$paidExcl = $taxRate > 0 ? round($total / (1 + $taxRate / 100), 6) : $total;
		$shipExcl = $taxRate > 0 ? round($shipping / (1 + $taxRate / 100), 6) : $shipping;
		$status = (int) ($order['status'] ?? 1);
		$dateAdd = (string) ($order['date_add'] ?? '');
		$dateUpd = (string) ($order['date_delivered'] ?? $dateAdd);
		$gift = !empty($order['gift_wrap']) ? 1 : 0;

		return [
			'id' => $id,
			'id_address_delivery' => self::linked('addresses', $addressId),
			'id_address_invoice' => self::linked('addresses', $addressId),
			'id_cart' => self::linked('carts', $id),
			'id_currency' => self::linked('currencies', 1),
			'id_lang' => self::linked('languages', $langId),
			'id_customer' => self::linked('customers', $customerId),
			'id_carrier' => self::linked('carriers', $carrierId),
			'current_state' => self::linked('order_states', self::psStateFromFriSay(
				$status,
				(string) ($order['payment_method'] ?? '') . ' ' . (string) ($order['payment_label'] ?? '')
			)),
			'module' => (string) ($order['payment_method'] ?? ''),
			'invoice_number' => 0,
			'invoice_date' => $dateAdd,
			'delivery_number' => $id,
			'delivery_date' => (string) ($order['date_delivered'] ?? '0000-00-00 00:00:00'),
			'valid' => in_array($status, [Order::STATUS_CANCELLED, Order::STATUS_RETURNED], true) ? 0 : 1,
			'date_add' => $dateAdd,
			'date_upd' => $dateUpd,
			'shipping_number' => (string) ($order['tracking_number'] ?? ''),
			'note' => (string) ($order['note'] ?? ''),
			'id_shop_group' => 1,
			'id_shop' => 1,
			'secure_key' => md5('fshop-order-' . $id . '-' . (string) ($order['reference'] ?? '')),
			'payment' => (string) ($order['payment_label'] ?? $order['payment_method'] ?? ''),
			'recyclable' => 0,
			'gift' => $gift,
			'gift_message' => '',
			'mobile_theme' => 0,
			'total_discounts' => self::money($discount),
			'total_discounts_tax_incl' => self::money($discount),
			'total_discounts_tax_excl' => self::money($discount),
			'total_paid' => self::money($total),
			'total_paid_tax_incl' => self::money($total),
			'total_paid_tax_excl' => self::money($paidExcl),
			'total_paid_real' => self::money($total),
			'total_products' => self::money($subtotal),
			'total_products_wt' => self::money($subtotal),
			'total_shipping' => self::money($shipping),
			'total_shipping_tax_incl' => self::money($shipping),
			'total_shipping_tax_excl' => self::money($shipExcl),
			'carrier_tax_rate' => number_format($taxRate, 3, '.', ''),
			'total_wrapping' => self::money($wrap),
			'total_wrapping_tax_incl' => self::money($wrap),
			'total_wrapping_tax_excl' => self::money($wrap),
			'round_mode' => 0,
			'round_type' => 0,
			'conversion_rate' => '1.000000',
			'reference' => (string) ($order['reference'] ?? (string) $id),
			'associations' => [
				'order_rows' => $rows,
			],
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatAddress(int $id): ?array
	{
		$order = Order::getByIdAdmin($id);

		if ($order) {
			$names = self::splitName((string) ($order['customer_name'] ?? ''));

			return [
				'id' => $id,
				'id_customer' => self::linked('customers', (int) ($order['id_user'] ?? 0)),
				'id_manufacturer' => 0,
				'id_supplier' => 0,
				'id_warehouse' => 0,
				'id_country' => self::linked('countries', 1),
				'id_state' => self::linkedState((string) ($order['address_district'] ?? '')),
				'alias' => 'Teslimat',
				'company' => (string) ($order['company_name'] ?? ''),
				'lastname' => $names['last'],
				'firstname' => $names['first'],
				'vat_number' => (string) ($order['tax_number'] ?? ''),
				'address1' => (string) ($order['address_text'] ?? ''),
				'address2' => (string) ($order['address_district'] ?? ''),
				'postcode' => '',
				'city' => (string) ($order['address_city'] ?? ''),
				'other' => (string) ($order['tax_office'] ?? ''),
				'phone' => (string) ($order['customer_phone'] ?? ''),
				'phone_mobile' => (string) ($order['customer_phone'] ?? ''),
				'dni' => (string) ($order['tax_number'] ?? ''),
				'deleted' => 0,
				'date_add' => (string) ($order['date_add'] ?? ''),
				'date_upd' => (string) ($order['date_add'] ?? ''),
			];
		}

		$row = DB::getRowSafe('user_addresses', 'id_address = ?', [$id]);

		if (!$row) {
			return null;
		}

		$names = self::splitName((string) ($row['full_name'] ?? ''));

		return [
			'id' => $id,
			'id_customer' => self::linked('customers', (int) ($row['id_user'] ?? 0)),
			'id_manufacturer' => 0,
			'id_supplier' => 0,
			'id_warehouse' => 0,
			'id_country' => self::linked('countries', 1),
			'id_state' => self::linkedState((string) ($row['district'] ?? '')),
			'alias' => (string) ($row['label'] ?? 'Adres'),
			'company' => (string) ($row['company_name'] ?? ''),
			'lastname' => $names['last'],
			'firstname' => $names['first'],
			'vat_number' => (string) ($row['tax_number'] ?? ''),
			'address1' => (string) ($row['address_text'] ?? ''),
			'address2' => (string) ($row['district'] ?? ''),
			'postcode' => '',
			'city' => (string) ($row['city'] ?? ''),
			'other' => (string) ($row['tax_office'] ?? ''),
			'phone' => (string) ($row['phone'] ?? ''),
			'phone_mobile' => (string) ($row['phone'] ?? ''),
			'dni' => (string) ($row['tax_number'] ?? ''),
			'deleted' => 0,
			'date_add' => (string) ($row['date_add'] ?? ''),
			'date_upd' => (string) ($row['date_add'] ?? ''),
		];
	}

	private static function ensureStatesSchema(): void
	{
		static $ready = false;
		if ($ready) {
			return;
		}
		$ready = true;
		$exists = DB::execute("SHOW TABLES LIKE 'psws_states'");
		if (!empty($exists)) {
			return;
		}
		DB::execute(
			"CREATE TABLE `psws_states` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(128) NOT NULL,
				`iso_code` varchar(8) NOT NULL DEFAULT '',
				PRIMARY KEY (`id`),
				UNIQUE KEY `name` (`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	private static function stateIdForDistrict(string $district): int
	{
		$name = trim($district);
		if ($name === '') {
			return 0;
		}
		self::ensureStatesSchema();
		$id = (int) DB::getValue('SELECT id FROM psws_states WHERE name = ? LIMIT 1', [$name]);
		if ($id > 0) {
			return $id;
		}
		$inserted = DB::insert('psws_states', [
			'name' => $name,
			'iso_code' => '',
		]);
		if ($inserted) {
			$id = (int) $inserted;
			DB::update(
				'psws_states',
				['iso_code' => 'D' . $id],
				'id = :where_id',
				['where_id' => $id]
			);

			return $id;
		}

		return (int) DB::getValue('SELECT id FROM psws_states WHERE name = ? LIMIT 1', [$name]);
	}

	/**
	 * @return array{id:int,href:string}|int
	 */
	private static function linkedState(string $district)
	{
		$id = self::stateIdForDistrict($district);
		if ($id <= 0) {
			return 0;
		}

		return self::linked('states', $id);
	}

	/** @return array<string, mixed>|null */
	private static function formatState(int $id): ?array
	{
		if ($id <= 0) {
			return null;
		}
		self::ensureStatesSchema();
		$row = DB::getRowSafe('psws_states', 'id = ?', [$id]);
		if (!$row) {
			return null;
		}
		$iso = trim((string) ($row['iso_code'] ?? ''));
		if ($iso === '') {
			$iso = 'D' . $id;
		}

		return [
			'id' => $id,
			'id_zone' => 1,
			'id_country' => self::linked('countries', 1),
			'iso_code' => $iso,
			'name' => (string) ($row['name'] ?? ''),
			'active' => 1,
		];
	}

	/**
	 * @param array<string, string> $filters
	 * @return list<int>
	 */
	private static function stateIds(array $filters): array
	{
		self::ensureStatesSchema();
		self::seedStatesFromOrders();
		$country = preg_replace('/\D+/', '', (string) ($filters['id_country'] ?? '')) ?: '';
		if ($country !== '' && (int) $country !== 1) {
			return [];
		}
		$rows = DB::execute('SELECT id FROM psws_states ORDER BY name ASC') ?: [];
		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) ($row['id'] ?? 0);
		}

		return $ids;
	}

	private static function seedStatesFromOrders(): void
	{
		static $seeded = false;
		if ($seeded) {
			return;
		}
		$seeded = true;
		$orderRows = DB::execute(
			"SELECT DISTINCT address_district AS name FROM orders WHERE address_district <> '' AND address_district IS NOT NULL"
		) ?: [];
		$addressRows = DB::execute(
			"SELECT DISTINCT district AS name FROM user_addresses WHERE district <> '' AND district IS NOT NULL"
		) ?: [];
		foreach (array_merge($orderRows, $addressRows) as $row) {
			self::stateIdForDistrict((string) ($row['name'] ?? ''));
		}
	}

	/** @return array<string, mixed>|null */
	private static function formatCustomer(int $id): ?array
	{
		if ($id <= 0) {
			return null;
		}

		$user = DB::getRowSafe('users', 'id_user = ?', [$id]);
		$names = self::splitName((string) ($user['user_full_name'] ?? ''));

		if (!$user) {
			$order = DB::getRowSafe('orders', 'id_user = ?', [$id]);
			if (!$order) {
				$order = DB::getRowSafe('orders', 'id_order = ?', [$id]);
			}
			if (!$order) {
				return null;
			}
			$names = self::splitName((string) ($order['customer_name'] ?? ''));

			return [
				'id' => $id,
				'id_default_group' => 1,
				'id_lang' => self::defaultLangId(),
				'newsletter_date_add' => '0000-00-00 00:00:00',
				'ip_registration_newsletter' => '',
				'last_passwd_gen' => '',
				'secure_key' => md5('fshop-customer-' . $id),
				'deleted' => 0,
				'passwd' => '',
				'lastname' => $names['last'],
				'firstname' => $names['first'],
				'email' => (string) ($order['customer_email'] ?? ''),
				'id_gender' => 0,
				'birthday' => '0000-00-00',
				'newsletter' => 0,
				'optin' => 0,
				'website' => '',
				'company' => (string) ($order['company_name'] ?? ''),
				'siret' => (string) ($order['tax_number'] ?? ''),
				'ape' => '',
				'outstanding_allow_amount' => '0.000000',
				'show_public_prices' => 0,
				'id_risk' => 0,
				'max_payment_days' => 0,
				'active' => 1,
				'note' => '',
				'is_guest' => 1,
				'id_shop' => 1,
				'id_shop_group' => 1,
				'date_add' => (string) ($order['date_add'] ?? ''),
				'date_upd' => (string) ($order['date_add'] ?? ''),
			];
		}

		return [
			'id' => $id,
			'id_default_group' => (int) ($user['id_group'] ?? 1) ?: 1,
			'id_lang' => self::defaultLangId(),
			'newsletter_date_add' => '0000-00-00 00:00:00',
			'ip_registration_newsletter' => '',
			'last_passwd_gen' => '',
			'secure_key' => md5('fshop-customer-' . $id),
			'deleted' => 0,
			'passwd' => '',
			'lastname' => $names['last'],
			'firstname' => $names['first'],
			'email' => (string) ($user['email'] ?? ''),
			'id_gender' => 0,
			'birthday' => '0000-00-00',
			'newsletter' => 0,
			'optin' => 0,
			'website' => '',
			'company' => '',
			'siret' => '',
			'ape' => '',
			'outstanding_allow_amount' => '0.000000',
			'show_public_prices' => 0,
			'id_risk' => 0,
			'max_payment_days' => 0,
			'active' => (int) ($user['active'] ?? 1),
			'note' => '',
			'is_guest' => 0,
			'id_shop' => 1,
			'id_shop_group' => 1,
			'date_add' => (string) ($user['date_add'] ?? ''),
			'date_upd' => (string) ($user['date_add'] ?? ''),
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatProduct(int $id): ?array
	{
		$product = Product::getByIdAdmin($id);

		if (!$product) {
			return null;
		}

		$langId = self::defaultLangId();
		$catId = (int) ($product['id_category'] ?? 0);
		$brandId = (int) ($product['id_brand'] ?? 0);
		$images = Product::getImages($id);
		$imageIds = [];
		foreach ($images as $img) {
			$imageIds[] = (int) ($img['id_image'] ?? 0);
		}
		$name = (string) ($product['product_name'] ?? '');
		$desc = (string) ($product['description'] ?? '');
		$short = (string) ($product['short_description'] ?? '');

		return [
			'id' => $id,
			'id_manufacturer' => $brandId > 0 ? self::linked('manufacturers', $brandId) : 0,
			'id_supplier' => (int) ($product['id_supplier'] ?? 0),
			'id_category_default' => $catId > 0 ? self::linked('categories', $catId) : 0,
			'new' => 0,
			'cache_default_attribute' => 0,
			'id_default_image' => $imageIds[0] ?? 0,
			'id_default_combination' => 0,
			'id_tax_rules_group' => self::linked('tax_rule_groups', self::taxGroupIdForRate((float) ($product['vat'] ?? 0))),
			'position_in_category' => 0,
			'type' => 'simple',
			'id_shop_default' => 1,
			'reference' => (string) ($product['stock_code'] ?? ''),
			'supplier_reference' => '',
			'location' => '',
			'width' => '0.000000',
			'height' => '0.000000',
			'depth' => '0.000000',
			'weight' => '0.000000',
			'quantity_discount' => 0,
			'ean13' => (string) ($product['barcode'] ?? ''),
			'isbn' => '',
			'upc' => '',
			'mpn' => '',
			'cache_is_pack' => 0,
			'cache_has_attachments' => 0,
			'is_virtual' => (string) ($product['product_type'] ?? '') === 'virtual' ? 1 : 0,
			'state' => 1,
			'additional_delivery_times' => 1,
			'on_sale' => self::catalogInclusivePrice($product) > (float) ($product['price'] ?? 0) ? 1 : 0,
			'online_only' => 0,
			'ecotax' => '0.000000',
			'minimal_quantity' => 1,
			'low_stock_threshold' => '',
			'low_stock_alert' => 0,
			'price' => self::money(self::priceExclusive(self::catalogInclusivePrice($product), (float) ($product['vat'] ?? 0))),
			'wholesale_price' => self::money((float) ($product['cost'] ?? 0)),
			'unity' => '',
			'unit_price_ratio' => '0.000000',
			'additional_shipping_cost' => '0.000000',
			'customizable' => 0,
			'text_fields' => 0,
			'uploadable_files' => 0,
			'active' => (int) ($product['active'] ?? 0),
			'redirect_type' => '404',
			'id_type_redirected' => 0,
			'available_for_order' => 1,
			'available_date' => '0000-00-00',
			'show_condition' => 0,
			'condition' => 'new',
			'show_price' => 1,
			'indexed' => 1,
			'visibility' => 'both',
			'advanced_stock_management' => 0,
			'date_add' => (string) ($product['date_add'] ?? ''),
			'date_upd' => (string) ($product['date_upd'] ?? ($product['date_add'] ?? '')),
			'pack_stock_type' => 3,
			'meta_description' => (string) ($product['meta_description'] ?? ''),
			'meta_keywords' => '',
			'meta_title' => (string) ($product['meta_title'] ?? ''),
			'link_rewrite' => (string) ($product['product_link'] ?? ''),
			'name' => self::langValue($name, $langId),
			'description' => self::langValue($desc, $langId),
			'description_short' => self::langValue($short, $langId),
			'available_now' => '',
			'available_later' => '',
			'associations' => [
				'categories' => $catId > 0 ? [['id' => $catId]] : [],
				'images' => array_map(static function ($imgId) {
					return ['id' => $imgId];
				}, $imageIds),
				'stock_availables' => [['id' => $id, 'id_product_attribute' => 0]],
			],
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatCategory(int $id): ?array
	{
		$row = Category::getByIdAdmin($id);

		if (!$row) {
			return null;
		}

		$langId = self::defaultLangId();
		$parent = (int) ($row['id_parent'] ?? 0);

		return [
			'id' => $id,
			'id_parent' => $parent > 0 ? self::linked('categories', $parent) : 0,
			'level_depth' => $parent > 0 ? 2 : 1,
			'nb_products_recursive' => '',
			'active' => (int) ($row['active'] ?? 1),
			'id_shop_default' => 1,
			'is_root_category' => $parent <= 0 ? 1 : 0,
			'position' => (int) ($row['position'] ?? 0),
			'date_add' => (string) ($row['date_add'] ?? ''),
			'date_upd' => (string) ($row['date_add'] ?? ''),
			'name' => self::langValue((string) ($row['category_name'] ?? ''), $langId),
			'link_rewrite' => (string) ($row['category_link'] ?? ''),
			'description' => '',
			'meta_title' => '',
			'meta_description' => '',
			'meta_keywords' => '',
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatManufacturer(int $id): ?array
	{
		$row = Brand::getByIdAdmin($id);

		if (!$row) {
			return null;
		}

		$langId = self::defaultLangId();

		return [
			'id' => $id,
			'active' => (int) ($row['active'] ?? 1),
			'name' => (string) ($row['brand_name'] ?? ''),
			'date_add' => (string) ($row['date_add'] ?? ''),
			'date_upd' => (string) ($row['date_add'] ?? ''),
			'description' => self::langValue('', $langId),
			'short_description' => self::langValue('', $langId),
			'meta_title' => '',
			'meta_description' => '',
			'meta_keywords' => '',
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatCarrier(int $id): ?array
	{
		if ($id <= 0) {
			return [
				'id' => 0,
				'deleted' => 0,
				'is_module' => 0,
				'id_tax_rules_group' => 1,
				'id_reference' => 0,
				'name' => '—',
				'active' => 1,
				'is_free' => 0,
				'shipping_handling' => 0,
				'shipping_external' => 0,
				'range_behavior' => 0,
				'shipping_method' => 1,
				'max_width' => 0,
				'max_height' => 0,
				'max_depth' => 0,
				'max_weight' => 0,
				'grade' => 0,
				'external_module_name' => '',
				'need_range' => 0,
				'position' => 0,
				'delay' => '',
			];
		}

		$row = Cargo::getById($id);

		if (!$row) {
			return null;
		}

		return [
			'id' => $id,
			'deleted' => 0,
			'is_module' => 0,
			'id_tax_rules_group' => 1,
			'id_reference' => $id,
			'name' => (string) ($row['name'] ?? ''),
			'active' => (int) ($row['active'] ?? 1),
			'is_free' => 0,
			'shipping_handling' => 0,
			'shipping_external' => 0,
			'range_behavior' => 0,
			'shipping_method' => 1,
			'max_width' => 0,
			'max_height' => 0,
			'max_depth' => 0,
			'max_weight' => 0,
			'grade' => 0,
			'external_module_name' => '',
			'need_range' => 0,
			'position' => (int) ($row['position'] ?? 0),
			'delay' => '',
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatCart(int $id): ?array
	{
		$order = Order::getByIdAdmin($id);

		if (!$order) {
			return null;
		}

		return [
			'id' => $id,
			'id_address_delivery' => self::linked('addresses', $id),
			'id_address_invoice' => self::linked('addresses', $id),
			'id_currency' => self::linked('currencies', 1),
			'id_customer' => self::linked('customers', (int) ($order['id_user'] ?? 0)),
			'id_guest' => 0,
			'id_lang' => self::linked('languages', self::defaultLangId()),
			'id_shop_group' => 1,
			'id_shop' => 1,
			'id_carrier' => self::linked('carriers', self::carrierIdForOrder($order)),
			'recyclable' => 0,
			'gift' => !empty($order['gift_wrap']) ? 1 : 0,
			'gift_message' => '',
			'mobile_theme' => 0,
			'delivery_option' => '',
			'secure_key' => md5('fshop-cart-' . $id),
			'allow_seperated_package' => 0,
			'date_add' => (string) ($order['date_add'] ?? ''),
			'date_upd' => (string) ($order['date_add'] ?? ''),
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatOrderCarrier(int $id): ?array
	{
		$order = Order::getByIdAdmin($id);
		if (!$order) {
			return null;
		}

		$carrierId = self::carrierIdForOrder($order);
		$shipping = (float) ($order['shipping'] ?? 0);

		return [
			'id' => $id,
			'id_order' => self::linked('orders', $id),
			'id_carrier' => $carrierId > 0 ? self::linked('carriers', $carrierId) : 0,
			'id_order_invoice' => $id,
			'weight' => '0.000000',
			'shipping_cost_tax_excl' => self::money($shipping),
			'shipping_cost_tax_incl' => self::money($shipping),
			'tracking_number' => (string) ($order['tracking_number'] ?? ''),
			'date_add' => (string) ($order['date_add'] ?? date('Y-m-d H:i:s')),
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatOrderHistory(int $id): ?array
	{
		$order = Order::getByIdAdmin($id);
		if (!$order) {
			return null;
		}

		$status = (int) ($order['status'] ?? 1);
		$psState = self::psStateFromFriSay(
			$status,
			(string) ($order['payment_method'] ?? '') . ' ' . (string) ($order['payment_label'] ?? '')
		);

		return [
			'id' => $id,
			'id_employee' => 0,
			'id_order_state' => self::linked('order_states', $psState),
			'id_order' => self::linked('orders', $id),
			'date_add' => (string) ($order['date_upd'] ?? ($order['date_add'] ?? date('Y-m-d H:i:s'))),
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatOrderState(int $id): ?array
	{
		$row = self::orderStateCatalog()[$id] ?? null;
		if ($row === null) {
			return null;
		}

		$langId = self::defaultLangId();

		return [
			'id' => $id,
			'unremovable' => (int) $row['unremovable'],
			'delivery' => (int) $row['delivery'],
			'hidden' => (int) $row['hidden'],
			'send_email' => (int) $row['send_email'],
			'module_name' => '',
			'invoice' => (int) $row['invoice'],
			'color' => (string) $row['color'],
			'logable' => (int) $row['logable'],
			'shipped' => (int) $row['shipped'],
			'paid' => (int) $row['paid'],
			'pdf_delivery' => (int) $row['pdf_delivery'],
			'pdf_invoice' => (int) $row['pdf_invoice'],
			'deleted' => 0,
			'name' => self::langValue((string) $row['name'], $langId),
			'template' => self::langValue((string) $row['template'], $langId),
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatLanguage(int $id): ?array
	{
		$langs = self::languages();

		if (!isset($langs[$id])) {
			return null;
		}

		$code = $langs[$id];
		$isTr = $code === 'tr';

		return [
			'id' => $id,
			'name' => $isTr ? 'Türkçe (Turkish)' : Lang::label($code),
			'iso_code' => $code,
			'locale' => $isTr ? 'tr-TR' : ($code === 'en' ? 'en-US' : $code),
			'language_code' => $isTr ? 'tr-tr' : $code,
			'active' => 1,
			'is_rtl' => 0,
			'is_default' => $id === 1 ? 1 : 0,
			'date_format_lite' => 'Y-m-d',
			'date_format_full' => 'Y-m-d H:i:s',
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatCurrency(int $id): ?array
	{
		$catalog = self::currencyCatalog();
		if (!isset($catalog[$id])) {
			return null;
		}

		$row = $catalog[$id];
		$name = (string) $row['name'];
		$symbol = (string) $row['symbol'];

		return [
			'id' => $id,
			'names' => self::langNodes($name),
			'name' => self::attrText($name, ['notFilterable' => 'true']),
			'symbol' => self::langNodes($symbol),
			'iso_code' => (string) $row['iso'],
			'numeric_iso_code' => (string) $row['numeric'],
			'precision' => 2,
			'conversion_rate' => (string) $row['rate'],
			'deleted' => 0,
			'active' => 1,
			'unofficial' => 0,
			'modified' => 0,
			'pattern' => self::langNodes(''),
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatTax(int $id): ?array
	{
		$tax = self::taxCatalog()[$id] ?? null;
		if ($tax === null) {
			return null;
		}

		$name = (string) $tax['name'];

		return [
			'id' => $id,
			'rate' => number_format((float) $tax['rate'], 3, '.', ''),
			'active' => (int) $tax['active'],
			'deleted' => 0,
			'name' => self::langValue($name, self::defaultLangId()),
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatTaxRuleGroup(int $id): ?array
	{
		$tax = self::taxCatalog()[$id] ?? null;
		if ($tax === null) {
			return null;
		}

		return [
			'id' => $id,
			'name' => (string) $tax['name'],
			'active' => (int) $tax['active'],
			'deleted' => 0,
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatTaxRule(int $id): ?array
	{
		$tax = self::taxCatalog()[$id] ?? null;
		if ($tax === null) {
			return null;
		}

		return [
			'id' => $id,
			'id_tax_rules_group' => self::linked('tax_rule_groups', $id),
			'id_country' => self::linked('countries', 1),
			'id_state' => 0,
			'zipcode_from' => '0',
			'zipcode_to' => '0',
			'id_tax' => self::linked('taxes', $id),
			'behavior' => 0,
			'description' => '',
		];
	}

	/** @return array<string, mixed> */
	private static function formatShop(): array
	{
		return [
			'id' => 1,
			'id_shop_group' => 1,
			'id_category' => 2,
			'id_theme' => 1,
			'active' => 1,
			'deleted' => 0,
			'name' => (string) (Settings::get('SITE_NAME') ?: 'FriSay'),
		];
	}

	/** @return array<string, mixed>|null */
	private static function formatStock(int $id): ?array
	{
		$product = Product::getById($id);

		if (!$product) {
			return null;
		}

		return [
			'id' => $id,
			'id_product' => $id,
			'id_product_attribute' => 0,
			'id_shop' => 1,
			'id_shop_group' => 0,
			'quantity' => (int) ($product['stock'] ?? 0),
			'depends_on_stock' => 0,
			'out_of_stock' => 2,
			'location' => '',
		];
	}

	private static function respondIndex(): void
	{
		$items = [];
		foreach (self::RESOURCES as $name => $meta) {
			$items[$name] = [
				'get' => !empty($meta['get']),
				'post' => !empty($meta['post']),
				'put' => !empty($meta['put']),
				'delete' => false,
				'href' => self::href($name),
				'desc' => (string) ($meta['desc'] ?? $name),
			];
		}
		$items['images'] = [
			'get' => true,
			'post' => true,
			'put' => true,
			'delete' => true,
			'href' => self::href('images'),
			'desc' => 'The images',
		];

		if (self::wantsJson()) {
			self::sendJson(['api' => $items]);
		}

		$doc = self::xmlDoc();
		$root = $doc->documentElement;
		$api = $doc->createElement('api');
		$api->setAttribute('shopName', (string) (Settings::get('SITE_NAME') ?: 'FriSay'));
		$root->appendChild($api);

		foreach ($items as $name => $meta) {
			$el = $doc->createElement($name);
			self::xmlIndexFlags($el, $meta);
			$desc = $doc->createElement('description');
			self::xmlIndexFlags($desc, $meta);
			$desc->appendChild($doc->createTextNode(' ' . $meta['desc']));
			$el->appendChild($desc);
			foreach (['blank', 'synopsis'] as $schemaType) {
				$schema = $doc->createElement('schema');
				$schema->setAttributeNS(self::XLINK_NS, 'xlink:href', $meta['href'] . '?schema=' . $schemaType);
				$schema->setAttribute('type', $schemaType);
				$el->appendChild($schema);
			}
			$api->appendChild($el);
		}

		self::sendXml($doc);
	}

	/** @param array{href:string,get:bool,put:bool,post:bool,delete:bool} $meta */
	private static function xmlIndexFlags(DOMElement $el, array $meta): void
	{
		$el->setAttributeNS(self::XLINK_NS, 'xlink:href', $meta['href']);
		$el->setAttribute('get', $meta['get'] ? 'true' : 'false');
		$el->setAttribute('put', $meta['put'] ? 'true' : 'false');
		$el->setAttribute('post', $meta['post'] ? 'true' : 'false');
		$el->setAttribute('delete', $meta['delete'] ? 'true' : 'false');
		$el->setAttribute('head', 'true');
	}

	private static function respondSchema(string $resource, string $type): void
	{
		$ids = self::listIds($resource);
		$sampleId = isset($ids[0]) ? (int) $ids[0] : 1;
		$row = self::loadOne($resource, $sampleId);
		if ($row === null) {
			$row = ['id' => ''];
		}

		self::respondEntity(self::RESOURCES[$resource]['singular'], self::blankify($row, $type === 'synopsis'));
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private static function blankify(array $row, bool $synopsis): array
	{
		$out = [];
		foreach ($row as $key => $value) {
			if ($key === 'associations' && is_array($value)) {
				$out[$key] = $value;
				continue;
			}
			if (is_array($value) && isset($value['id'], $value['href'])) {
				$out[$key] = $synopsis ? self::attrText('', ['format' => 'isUnsignedId']) : '';
				continue;
			}
			if (is_array($value) && isset($value['language'])) {
				$out[$key] = self::langValue('', (int) ($value['language']['id'] ?? 1));
				continue;
			}
			if (is_array($value) && isset($value['languages']) && is_array($value['languages'])) {
				$out[$key] = self::langNodes('');
				continue;
			}
			if (is_array($value) && array_key_exists('value', $value) && isset($value['attrs']) && is_array($value['attrs'])) {
				$attrs = $value['attrs'];
				if ($synopsis && !isset($attrs['format'])) {
					$attrs = array_merge(self::synopsisAttrs($key), $attrs);
				}
				$out[$key] = self::attrText('', $attrs);
				continue;
			}
			$out[$key] = $synopsis ? self::attrText('', self::synopsisAttrs($key)) : '';
		}

		return $out;
	}

	/** @return array<string, string> */
	private static function synopsisAttrs(string $key): array
	{
		if ($key === 'id' || strpos($key, 'id_') === 0) {
			return ['format' => 'isUnsignedId'];
		}
		if (in_array($key, ['active', 'deleted', 'unofficial', 'modified', 'new', 'available_for_order', 'show_price', 'indexed'], true)) {
			return ['format' => 'isBool'];
		}
		if ($key === 'rate' || $key === 'conversion_rate' || strpos($key, 'price') !== false || strpos($key, 'total_') === 0) {
			return ['format' => 'isUnsignedFloat'];
		}
		if ($key === 'iso_code') {
			return ['format' => 'isLanguageIsoCode'];
		}
		if ($key === 'name') {
			return ['required' => 'true', 'maxSize' => '64', 'format' => 'isGenericName'];
		}

		return ['format' => 'isGenericName'];
	}

	/**
	 * @param list<array<string, mixed>> $rows
	 */
	private static function respondList(string $plural, string $singular, array $rows, bool $full): void
	{
		if (self::wantsJson()) {
			$list = [];
			foreach ($rows as $row) {
				$list[] = self::jsonEntity($row, $full);
			}
			self::sendJson([$plural => $list]);
		}

		$doc = self::xmlDoc();
		$wrap = $doc->createElement($plural);
		$doc->documentElement->appendChild($wrap);

		foreach ($rows as $row) {
			if ($full) {
				try {
					$el = $doc->createElement($singular);
					self::xmlFill($doc, $el, $row, $singular);
					$wrap->appendChild($el);
				} catch (Throwable $e) {
					continue;
				}
			} else {
				$id = (int) ($row['id'] ?? 0);
				$el = $doc->createElement($singular);
				$el->setAttribute('id', (string) $id);
				$el->setAttributeNS(self::XLINK_NS, 'xlink:href', self::href($plural, $id));
				$wrap->appendChild($el);
			}
		}

		self::sendXml($doc);
	}

	/** @param array<string, mixed> $row */
	private static function respondEntity(string $singular, array $row): void
	{
		if (self::wantsJson()) {
			self::sendJson([$singular => self::jsonEntity($row, true)]);
		}

		$doc = self::xmlDoc();
		$el = $doc->createElement($singular);
		$doc->documentElement->appendChild($el);
		self::xmlFill($doc, $el, $row, $singular);
		self::sendXml($doc);
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private static function jsonEntity(array $row, bool $full): array
	{
		if (!$full) {
			return ['id' => (int) ($row['id'] ?? 0)];
		}

		$out = [];
		foreach ($row as $key => $value) {
			if ($key === 'associations') {
				$out[$key] = self::jsonAssociations($value);
				continue;
			}
			if (is_array($value) && isset($value['id'], $value['href'])) {
				$out[$key] = (string) $value['id'];
				continue;
			}
			if (is_array($value) && isset($value['language'])) {
				$out[$key] = $value['language']['value'] ?? '';
				continue;
			}
			if (is_array($value) && isset($value['languages']) && is_array($value['languages'])) {
				$first = $value['languages'][0] ?? [];
				$out[$key] = $first['value'] ?? '';
				continue;
			}
			if (is_array($value) && array_key_exists('value', $value) && isset($value['attrs']) && is_array($value['attrs'])) {
				$out[$key] = $value['value'];
				continue;
			}
			$out[$key] = $value;
		}

		return $out;
	}

	/** @param mixed $assoc */
	private static function jsonAssociations($assoc): array
	{
		if (!is_array($assoc)) {
			return [];
		}

		$out = [];
		if (isset($assoc['order_rows']) && is_array($assoc['order_rows'])) {
			$out['order_rows'] = $assoc['order_rows'];
		}
		if (isset($assoc['categories']) && is_array($assoc['categories'])) {
			$out['categories'] = $assoc['categories'];
		}
		if (isset($assoc['images']) && is_array($assoc['images'])) {
			$out['images'] = $assoc['images'];
		}
		if (isset($assoc['stock_availables']) && is_array($assoc['stock_availables'])) {
			$out['stock_availables'] = $assoc['stock_availables'];
		}

		return $out;
	}

	/** @param array<string, mixed> $row */
	private static function xmlFill(DOMDocument $doc, DOMElement $el, array $row, string $singular): void
	{
		foreach ($row as $key => $value) {
			if ($key === 'associations') {
				self::xmlAssociations($doc, $el, is_array($value) ? $value : [], $singular);
				continue;
			}
			if (is_array($value) && isset($value['id'], $value['href'])) {
				$child = $doc->createElement($key);
				$child->appendChild($doc->createTextNode(self::xmlSafe((string) $value['id'])));
				$child->setAttributeNS(self::XLINK_NS, 'xlink:href', (string) $value['href']);
				$el->appendChild($child);
				continue;
			}
			if (is_array($value) && isset($value['language'])) {
				$child = $doc->createElement($key);
				$child->appendChild(self::xmlLanguageNode($doc, (int) ($value['language']['id'] ?? 1), (string) ($value['language']['value'] ?? '')));
				$el->appendChild($child);
				continue;
			}
			if (is_array($value) && isset($value['languages']) && is_array($value['languages'])) {
				$child = $doc->createElement($key);
				foreach ($value['languages'] as $langRow) {
					if (!is_array($langRow)) {
						continue;
					}
					$child->appendChild(self::xmlLanguageNode(
						$doc,
						(int) ($langRow['id'] ?? 1),
						(string) ($langRow['value'] ?? '')
					));
				}
				$el->appendChild($child);
				continue;
			}
			if (is_array($value) && array_key_exists('value', $value) && isset($value['attrs']) && is_array($value['attrs'])) {
				$child = $doc->createElement($key);
				foreach ($value['attrs'] as $attr => $attrVal) {
					$child->setAttribute((string) $attr, (string) $attrVal);
				}
				$text = self::xmlSafe((string) $value['value']);
				if ($text !== '') {
					$child->appendChild($doc->createTextNode($text));
				}
				$el->appendChild($child);
				continue;
			}
			$text = is_scalar($value) || $value === null ? self::xmlSafe((string) $value) : '';
			$child = $doc->createElement($key);
			if ($key === 'shipping_number') {
				$child->setAttribute('notFilterable', 'true');
			}
			if ($text !== '') {
				$child->appendChild($doc->createTextNode($text));
			}
			$el->appendChild($child);
		}
	}

	/** @param array<string, mixed> $assoc */
	private static function xmlAssociations(DOMDocument $doc, DOMElement $parent, array $assoc, string $singular): void
	{
		$wrap = $doc->createElement('associations');
		$parent->appendChild($wrap);

		if ($singular === 'order' && isset($assoc['order_rows']) && is_array($assoc['order_rows'])) {
			$rowsEl = $doc->createElement('order_rows');
			$rowsEl->setAttribute('nodeType', 'order_row');
			$rowsEl->setAttribute('virtualEntity', 'true');
			$wrap->appendChild($rowsEl);
			foreach ($assoc['order_rows'] as $row) {
				if (!is_array($row)) {
					continue;
				}
				$rowEl = $doc->createElement('order_row');
				foreach ($row as $k => $v) {
					if ($k === 'product_id') {
						$child = $doc->createElement($k);
						$child->appendChild($doc->createTextNode(self::xmlSafe((string) $v)));
						$child->setAttributeNS(self::XLINK_NS, 'xlink:href', self::href('products', (int) $v));
						$rowEl->appendChild($child);
						continue;
					}
					if ($k === 'id_customization') {
						$child = $doc->createElement($k);
						$child->appendChild($doc->createTextNode(self::xmlSafe((string) $v)));
						$child->setAttributeNS(self::XLINK_NS, 'xlink:href', self::href('customizations', (int) $v));
						$rowEl->appendChild($child);
						continue;
					}
					$child = $doc->createElement($k);
					$child->appendChild($doc->createTextNode(self::xmlSafe((string) $v)));
					$rowEl->appendChild($child);
				}
				$rowsEl->appendChild($rowEl);
			}
		}

		foreach (['categories' => 'category', 'images' => 'image', 'stock_availables' => 'stock_available'] as $group => $node) {
			if (empty($assoc[$group]) || !is_array($assoc[$group])) {
				continue;
			}
			$groupEl = $doc->createElement($group);
			$wrap->appendChild($groupEl);
			foreach ($assoc[$group] as $item) {
				$id = (int) (is_array($item) ? ($item['id'] ?? 0) : $item);
				if ($id <= 0) {
					continue;
				}
				$itemEl = $doc->createElement($node);
				$itemEl->setAttribute('id', (string) $id);
				if ($group === 'images') {
					$idNode = $parent->getElementsByTagName('id')->item(0);
					$productId = $idNode ? (int) $idNode->nodeValue : 0;
					$itemEl->setAttributeNS(self::XLINK_NS, 'xlink:href', self::baseUrl() . '/api/images/products/' . $productId . '/' . $id);
				} elseif ($group === 'stock_availables') {
					$itemEl->setAttributeNS(self::XLINK_NS, 'xlink:href', self::href('stock_availables', $id));
				} else {
					$itemEl->setAttributeNS(self::XLINK_NS, 'xlink:href', self::href($group, $id));
				}
				$groupEl->appendChild($itemEl);
			}
		}
	}

	private static function xmlDoc(): DOMDocument
	{
		$doc = new DOMDocument('1.0', 'UTF-8');
		$doc->formatOutput = false;
		$root = $doc->createElement('prestashop');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', self::XLINK_NS);
		$doc->appendChild($root);

		return $doc;
	}

	private static function sendXml(DOMDocument $doc): void
	{
		$code = http_response_code();
		$xml = $doc->saveXML();
		if (!is_string($xml)) {
			$xml = '';
		}
		self::touchRequestLog(is_int($code) && $code >= 100 ? $code : 200, '', $xml, 'xml');
		header('Content-Type: text/xml; charset=utf-8');
		header('Cache-Control: no-store');
		echo $xml;
		exit;
	}

	/** @param array<string, mixed> $payload */
	private static function sendJson(array $payload): void
	{
		$code = http_response_code();
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			$json = '{}';
		}
		self::touchRequestLog(is_int($code) && $code >= 100 ? $code : 200, '', $json, 'json');
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		echo $json;
		exit;
	}

	private static function fail(int $code, string $message): void
	{
		http_response_code($code);
		self::touchRequestLog($code, $message);

		if (self::wantsJson()) {
			self::sendJson([
				'errors' => [
					'error' => [
						'code' => $code,
						'message' => $message,
					],
				],
			]);
		}

		$doc = self::xmlDoc();
		$errors = $doc->createElement('errors');
		$error = $doc->createElement('error');
		$error->appendChild($doc->createElement('code', (string) $code));
		$msg = $doc->createElement('message');
		$msg->appendChild($doc->createTextNode($message));
		$error->appendChild($msg);
		$errors->appendChild($error);
		$doc->documentElement->appendChild($errors);
		self::sendXml($doc);
	}

	private static function wantsJson(): bool
	{
		$format = strtoupper(trim((string) ($_GET['output_format'] ?? '')));
		if ($format === 'JSON' || $format === 'JSONP') {
			return true;
		}
		if ($format === 'XML') {
			return false;
		}

		$accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

		return strpos($accept, 'application/json') !== false && strpos($accept, 'application/xml') === false
			&& strpos($accept, 'text/xml') === false;
	}

	private static function isDisplayFull(): bool
	{
		$display = strtolower(trim((string) ($_GET['display'] ?? '')));

		return $display === 'full';
	}

	/** @return list<string>|null */
	private static function displayFields(): ?array
	{
		$display = trim((string) ($_GET['display'] ?? ''));

		if ($display === '' || strtolower($display) === 'full') {
			return null;
		}

		$display = trim($display, '[]');
		$fields = [];
		foreach (explode(',', $display) as $field) {
			$field = preg_replace('/[^a-z0-9_]/i', '', trim($field)) ?: '';
			if ($field !== '') {
				$fields[] = $field;
			}
		}

		return $fields !== [] ? $fields : null;
	}

	/**
	 * @param array<string, mixed> $row
	 * @param list<string> $fields
	 * @return array<string, mixed>
	 */
	private static function pickFields(array $row, array $fields): array
	{
		$out = [];
		if (!in_array('id', $fields, true)) {
			array_unshift($fields, 'id');
		}
		foreach ($fields as $field) {
			if (array_key_exists($field, $row)) {
				$out[$field] = $row[$field];
			}
		}

		return $out;
	}

	/**
	 * PrestaShop varsayılanı 1000’dir; Prapazar sayfalama göndermez.
	 * Ürün / stok listesinde limit yoksa tüm kayıtlar döner (0 = sınırsız).
	 *
	 * @return array{offset:int,limit:int}
	 */
	private static function parseLimit(string $resource = ''): array
	{
		$catalog = $resource === 'products' || $resource === 'stock_availables';
		$max = $catalog ? 100000 : 1000;
		$raw = trim((string) ($_GET['limit'] ?? ''));

		if ($raw === '') {
			return [
				'offset' => 0,
				'limit' => $catalog ? 0 : 1000,
			];
		}

		if (strpos($raw, ',') !== false) {
			[$offset, $limit] = array_map('intval', explode(',', $raw, 2));

			return [
				'offset' => max(0, $offset),
				'limit' => min($max, max(1, $limit)),
			];
		}

		return ['offset' => 0, 'limit' => min($max, max(1, (int) $raw))];
	}

	/**
	 * @param array{offset:int,limit:int} $limit
	 */
	private static function sqlLimit(array $limit): string
	{
		$offset = max(0, (int) ($limit['offset'] ?? 0));
		$count = (int) ($limit['limit'] ?? 0);

		if ($count <= 0) {
			if ($offset <= 0) {
				return '';
			}

			return ' LIMIT 18446744073709551615 OFFSET ' . $offset;
		}

		return ' LIMIT ' . $count . ' OFFSET ' . $offset;
	}

	/** @return array{column:string,dir:string} */
	private static function parseSort(string $resource): array
	{
		$raw = trim((string) ($_GET['sort'] ?? ''), '[]');
		$dir = 'DESC';
		$column = $resource === 'orders' ? 'id_order' : 'id';

		if (preg_match('/^([a-z0-9_]+)_(ASC|DESC)$/i', $raw, $m)) {
			$dir = strtoupper($m[2]) === 'ASC' ? 'ASC' : 'DESC';
			$asked = strtolower($m[1]);
			if ($resource === 'orders') {
				$map = ['id' => 'id_order', 'date_add' => 'date_add', 'reference' => 'reference'];
				$column = $map[$asked] ?? 'id_order';
			} elseif ($resource === 'products' || $resource === 'stock_availables') {
				$map = ['id' => 'id_product', 'date_add' => 'date_add', 'active' => 'active'];
				$column = $map[$asked] ?? 'id_product';
			} elseif ($resource === 'customers') {
				$column = $asked === 'id' ? 'id_user' : 'id_user';
			} elseif ($resource === 'categories') {
				$column = $asked === 'id' ? 'id_category' : 'id_category';
			} elseif ($resource === 'manufacturers') {
				$column = 'id_brand';
			} elseif ($resource === 'carriers') {
				$column = 'id_cargo';
			} elseif ($resource === 'addresses' || $resource === 'carts') {
				$column = 'id_order';
			}
		}

		return ['column' => $column, 'dir' => $dir];
	}

	/** @return array<string, string> */
	private static function parseFilters(): array
	{
		$raw = $_GET['filter'] ?? [];
		if (!is_array($raw)) {
			return [];
		}

		$out = [];
		foreach ($raw as $key => $value) {
			$key = preg_replace('/[^a-z0-9_]/i', '', (string) $key) ?: '';
			if ($key === '') {
				continue;
			}
			$out[$key] = trim((string) $value, "[] \t\n\r");
		}

		return $out;
	}

	/**
	 * @param array<string, string> $filters
	 * @param array{column:string,dir:string} $sort
	 * @param array{offset:int,limit:int} $limit
	 * @return list<int>
	 */
	private static function orderIds(array $filters, array $sort, array $limit): array
	{
		$sql = 'SELECT id_order FROM orders WHERE 1=1';
		$params = [];

		if (!empty($filters['id']) && strpos($filters['id'], ',') !== false) {
			[$from, $to] = array_map('intval', explode(',', $filters['id'], 2));
			$sql .= ' AND id_order BETWEEN ? AND ?';
			$params[] = min($from, $to);
			$params[] = max($from, $to);
		} elseif (!empty($filters['id']) && ctype_digit($filters['id'])) {
			$sql .= ' AND id_order = ?';
			$params[] = (int) $filters['id'];
		}

		if (!empty($filters['reference'])) {
			$sql .= ' AND reference = ?';
			$params[] = $filters['reference'];
		}

		if (!empty($filters['current_state']) && ctype_digit($filters['current_state'])) {
			$sql .= ' AND status = ?';
			$params[] = self::friSayStatusFromPs((int) $filters['current_state']);
		}

		self::applyDateFilter($sql, $params, $filters['date_add'] ?? '');

		$allowed = ['id_order', 'date_add', 'reference'];
		$column = in_array($sort['column'], $allowed, true) ? $sort['column'] : 'id_order';
		$sql .= ' ORDER BY ' . $column . ' ' . $sort['dir'] . ' LIMIT ' . (int) $limit['limit'] . ' OFFSET ' . (int) $limit['offset'];

		$rows = DB::execute($sql, $params) ?: [];
		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) $row['id_order'];
		}

		return $ids;
	}

	/**
	 * @param array<string, string> $filters
	 * @param array{column:string,dir:string} $sort
	 * @param array{offset:int,limit:int} $limit
	 * @return list<int>
	 */
	private static function productIds(array $filters, array $sort, array $limit): array
	{
		$sql = 'SELECT id_product FROM products WHERE 1=1';
		$params = [];

		if (isset($filters['active']) && ($filters['active'] === '0' || $filters['active'] === '1')) {
			$sql .= ' AND active = ?';
			$params[] = (int) $filters['active'];
		}

		if (!empty($filters['id']) && ctype_digit($filters['id'])) {
			$sql .= ' AND id_product = ?';
			$params[] = (int) $filters['id'];
		}

		if (!empty($filters['id_product']) && ctype_digit($filters['id_product'])) {
			$sql .= ' AND id_product = ?';
			$params[] = (int) $filters['id_product'];
		}

		$allowed = ['id_product', 'date_add', 'active'];
		$column = in_array($sort['column'], $allowed, true) ? $sort['column'] : 'id_product';
		$sql .= ' ORDER BY ' . $column . ' ' . $sort['dir'] . self::sqlLimit($limit);

		$rows = DB::execute($sql, $params) ?: [];
		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) $row['id_product'];
		}

		return $ids;
	}

	/**
	 * @param array<string, string> $filters
	 * @param array{column:string,dir:string} $sort
	 * @param array{offset:int,limit:int} $limit
	 * @return list<int>
	 */
	private static function customerIds(array $filters, array $sort, array $limit): array
	{
		$sql = 'SELECT id_user FROM users WHERE 1=1';
		$params = [];

		if (!empty($filters['email'])) {
			$sql .= ' AND email = ?';
			$params[] = $filters['email'];
		}

		if (isset($filters['active']) && ($filters['active'] === '0' || $filters['active'] === '1')) {
			$sql .= ' AND active = ?';
			$params[] = (int) $filters['active'];
		}

		$sql .= ' ORDER BY id_user ' . $sort['dir'] . ' LIMIT ' . (int) $limit['limit'] . ' OFFSET ' . (int) $limit['offset'];
		$rows = DB::execute($sql, $params) ?: [];
		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) $row['id_user'];
		}

		return $ids;
	}

	/**
	 * @param array<string, string> $filters
	 * @param array{column:string,dir:string} $sort
	 * @param array{offset:int,limit:int} $limit
	 * @param list<string> $filterCols
	 * @return list<int>
	 */
	private static function tableIds(string $table, string $pk, array $filters, array $sort, array $limit, array $filterCols): array
	{
		$sql = 'SELECT `' . $pk . '` FROM `' . $table . '` WHERE 1=1';
		$params = [];

		if (in_array('active', $filterCols, true) && isset($filters['active']) && ($filters['active'] === '0' || $filters['active'] === '1')) {
			$sql .= ' AND active = ?';
			$params[] = (int) $filters['active'];
		}

		$dir = $sort['dir'] === 'ASC' ? 'ASC' : 'DESC';
		$sql .= ' ORDER BY `' . $pk . '` ' . $dir . ' LIMIT ' . (int) $limit['limit'] . ' OFFSET ' . (int) $limit['offset'];
		$rows = DB::execute($sql, $params) ?: [];
		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) $row[$pk];
		}

		return $ids;
	}

	/** @param list<mixed> $params */
	private static function applyDateFilter(string &$sql, array &$params, string $range): void
	{
		$range = trim($range);
		if ($range === '') {
			return;
		}

		if (strpos($range, ',') !== false) {
			[$from, $to] = array_map('trim', explode(',', $range, 2));
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
				$from .= ' 00:00:00';
			}
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
				$to .= ' 23:59:59';
			}
			$sql .= ' AND date_add >= ? AND date_add <= ?';
			$params[] = $from;
			$params[] = $to;

			return;
		}

		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $range)) {
			$sql .= ' AND date_add >= ? AND date_add <= ?';
			$params[] = $range . ' 00:00:00';
			$params[] = $range . ' 23:59:59';
		}
	}

	/** @return array<string, mixed> */
	private static function parseBody(): array
	{
		$raw = trim(self::rawBody());

		if ($raw === '') {
			return is_array($_POST) ? $_POST : [];
		}

		$json = json_decode($raw, true);
		if (is_array($json)) {
			return $json;
		}

		if (class_exists('SimpleXMLElement')) {
			$xml = @simplexml_load_string($raw);
			if ($xml instanceof SimpleXMLElement) {
				$decoded = json_decode(json_encode($xml), true);

				return is_array($decoded) ? $decoded : [];
			}
		}

		return [];
	}

	/** @param mixed $value */
	private static function scalar($value): string
	{
		if (is_array($value)) {
			if (isset($value['id'])) {
				return self::scalar($value['id']);
			}
			if (isset($value[0])) {
				return self::scalar($value[0]);
			}

			return trim((string) ($value['#text'] ?? ''));
		}

		return trim((string) $value);
	}

	/** @return array{first:string,last:string} */
	private static function splitName(string $full): array
	{
		$full = trim($full);
		$parts = preg_split('/\s+/u', $full, 2) ?: [];

		return [
			'first' => (string) ($parts[0] ?? ''),
			'last' => (string) ($parts[1] ?? ''),
		];
	}

	private static function money(float $value): string
	{
		return number_format($value, 6, '.', '');
	}

	/** @return array{id:int,href:string} */
	private static function linked(string $resource, int $id): array
	{
		return [
			'id' => $id,
			'href' => self::href($resource, $id),
		];
	}

	/** @return array{language:array{id:int,value:string}} */
	private static function langValue(string $value, int $langId): array
	{
		return [
			'language' => [
				'id' => $langId,
				'value' => $value,
			],
		];
	}

	/** @return array{languages:list<array{id:int,value:string}>} */
	private static function langNodes(string $value): array
	{
		return [
			'languages' => [
				[
					'id' => self::defaultLangId(),
					'value' => $value,
				],
			],
		];
	}

	/**
	 * @param array<string, string> $attrs
	 * @return array{value:string,attrs:array<string, string>}
	 */
	private static function attrText(string $value, array $attrs): array
	{
		return [
			'value' => $value,
			'attrs' => $attrs,
		];
	}

	private static function xmlLanguageNode(DOMDocument $doc, int $langId, string $value): DOMElement
	{
		$lang = $doc->createElement('language');
		$value = self::xmlSafe($value);
		if ($value !== '') {
			$lang->appendChild($doc->createTextNode($value));
		}
		$lang->setAttribute('id', (string) $langId);
		$lang->setAttributeNS(self::XLINK_NS, 'xlink:href', self::href('languages', $langId));

		return $lang;
	}

	private static function xmlSafe(string $text): string
	{
		$clean = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text);
		if (!is_string($clean)) {
			$clean = $text;
		}
		if (function_exists('mb_convert_encoding')) {
			$utf = @mb_convert_encoding($clean, 'UTF-8', 'UTF-8');
			if (is_string($utf)) {
				$clean = $utf;
			}
		}

		return $clean;
	}

	/**
	 * Mağaza para birimi PrestaShop id=1 (TRY → Türk Lirası / ₺ / 949).
	 *
	 * @return array<int, array{name:string,symbol:string,iso:string,numeric:string,rate:string}>
	 */
	private static function currencyCatalog(): array
	{
		static $catalog = null;
		if (is_array($catalog)) {
			return $catalog;
		}

		$code = strtoupper(trim((string) (Settings::get('SHOP_CURRENCY') ?: 'TRY')));
		if ($code === '') {
			$code = 'TRY';
		}

		$numerics = [
			'TRY' => '949',
			'USD' => '840',
			'EUR' => '978',
			'GBP' => '826',
			'CHF' => '756',
			'RUB' => '643',
			'AED' => '784',
			'SAR' => '682',
		];

		$catalog = [
			1 => [
				'name' => Currency::shortLabel($code),
				'symbol' => Currency::symbol($code),
				'iso' => $code,
				'numeric' => $numerics[$code] ?? '',
				'rate' => '1.000000',
			],
		];

		return $catalog;
	}

	/**
	 * FriSay taxes tablosu → PrestaShop taxes / tax_rule_groups / tax_rules (aynı id).
	 *
	 * @return array<int, array{id:int,name:string,rate:float,active:int}>
	 */
	private static function taxCatalog(): array
	{
		static $catalog = null;
		if (is_array($catalog)) {
			return $catalog;
		}

		$catalog = [];
		foreach (Tax::getAdminList() as $row) {
			$id = (int) ($row['id_tax'] ?? 0);
			if ($id <= 0) {
				continue;
			}
			$catalog[$id] = [
				'id' => $id,
				'name' => (string) ($row['name'] ?? ''),
				'rate' => (float) ($row['rate'] ?? 0),
				'active' => (int) ($row['active'] ?? 0),
			];
		}

		return $catalog;
	}

	private static function taxGroupIdForRate(float $rate): int
	{
		$best = 0;
		$bestDiff = 1000.0;
		foreach (self::taxCatalog() as $id => $tax) {
			$diff = abs((float) $tax['rate'] - $rate);
			if ($diff < 0.001) {
				return (int) $id;
			}
			if ($diff < $bestDiff) {
				$bestDiff = $diff;
				$best = (int) $id;
			}
		}

		if ($best > 0) {
			return $best;
		}

		$first = array_key_first(self::taxCatalog());

		return $first !== null ? (int) $first : 1;
	}

	private static function href(?string $resource = '', int $id = 0): string
	{
		$url = self::baseUrl() . '/api/' . ltrim((string) $resource, '/');
		if ($id > 0) {
			$url .= '/' . $id;
		}

		return $url;
	}

	private static function baseUrl(): string
	{
		$site = rtrim((string) Settings::get('SITE_URL'), '/');
		if ($site !== '' && filter_var($site, FILTER_VALIDATE_URL)) {
			return $site;
		}

		global $domain;

		return rtrim((string) ($domain ?? ''), '/');
	}

	private static function defaultLangId(): int
	{
		return 1;
	}

	/** @return array<int, string> */
	private static function languages(): array
	{
		$codes = Lang::getAvailable();
		$ordered = array_values(array_unique(array_merge(['tr'], $codes)));
		$out = [];
		$i = 1;
		foreach ($ordered as $code) {
			$out[$i] = $code;
			$i++;
		}

		return $out !== [] ? $out : [1 => 'tr'];
	}

	/**
	 * PrestaShop 1.7 varsayılan sipariş durumları (Prapazar id=4 = kargoya verildi).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function orderStateCatalog(): array
	{
		return [
			1 => [
				'name' => 'Çek ile ödeme bekleniyor', 'template' => 'cheque', 'color' => '#4169E1',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 0,
				'logable' => 0, 'shipped' => 0, 'paid' => 0, 'pdf_delivery' => 0, 'pdf_invoice' => 0,
				'fri' => Order::STATUS_PENDING,
			],
			2 => [
				'name' => 'Ödeme kabul edildi', 'template' => 'payment', 'color' => '#3498D8',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 1,
				'logable' => 1, 'shipped' => 0, 'paid' => 1, 'pdf_delivery' => 0, 'pdf_invoice' => 1,
				'fri' => Order::STATUS_PROCESSING,
			],
			3 => [
				'name' => 'Hazırlanıyor', 'template' => 'preparation', 'color' => '#FF8C00',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 1,
				'logable' => 1, 'shipped' => 0, 'paid' => 1, 'pdf_delivery' => 0, 'pdf_invoice' => 1,
				'fri' => Order::STATUS_PROCESSING,
			],
			4 => [
				'name' => 'Kargoya verildi', 'template' => 'shipped', 'color' => '#8A2BE2',
				'unremovable' => 1, 'delivery' => 1, 'hidden' => 0, 'send_email' => 1, 'invoice' => 1,
				'logable' => 1, 'shipped' => 1, 'paid' => 1, 'pdf_delivery' => 0, 'pdf_invoice' => 1,
				'fri' => Order::STATUS_SHIPPED,
			],
			5 => [
				'name' => 'Teslim edildi', 'template' => 'delivered', 'color' => '#108510',
				'unremovable' => 1, 'delivery' => 1, 'hidden' => 0, 'send_email' => 1, 'invoice' => 1,
				'logable' => 1, 'shipped' => 1, 'paid' => 1, 'pdf_delivery' => 0, 'pdf_invoice' => 1,
				'fri' => Order::STATUS_DELIVERED,
			],
			6 => [
				'name' => 'İptal edildi', 'template' => 'order_canceled', 'color' => '#DC143C',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 0,
				'logable' => 0, 'shipped' => 0, 'paid' => 0, 'pdf_delivery' => 0, 'pdf_invoice' => 0,
				'fri' => Order::STATUS_CANCELLED,
			],
			7 => [
				'name' => 'İade edildi', 'template' => 'refund', 'color' => '#EC2E15',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 0,
				'logable' => 0, 'shipped' => 0, 'paid' => 0, 'pdf_delivery' => 0, 'pdf_invoice' => 0,
				'fri' => Order::STATUS_RETURNED,
			],
			8 => [
				'name' => 'Ödeme hatası', 'template' => 'payment_error', 'color' => '#8f0621',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 0,
				'logable' => 0, 'shipped' => 0, 'paid' => 0, 'pdf_delivery' => 0, 'pdf_invoice' => 0,
				'fri' => Order::STATUS_PENDING,
			],
			9 => [
				'name' => 'Stokta yok (ödendi)', 'template' => 'outofstock', 'color' => '#FF69B4',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 1,
				'logable' => 1, 'shipped' => 0, 'paid' => 1, 'pdf_delivery' => 0, 'pdf_invoice' => 1,
				'fri' => Order::STATUS_PROCESSING,
			],
			10 => [
				'name' => 'Havale ile ödeme bekleniyor', 'template' => 'bankwire', 'color' => '#34209E',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 0,
				'logable' => 0, 'shipped' => 0, 'paid' => 0, 'pdf_delivery' => 0, 'pdf_invoice' => 0,
				'fri' => Order::STATUS_PENDING,
			],
			11 => [
				'name' => 'Uzaktan ödeme kabul edildi', 'template' => 'payment', 'color' => '#3498D8',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 1,
				'logable' => 1, 'shipped' => 0, 'paid' => 1, 'pdf_delivery' => 0, 'pdf_invoice' => 1,
				'fri' => Order::STATUS_PROCESSING,
			],
			12 => [
				'name' => 'Stokta yok (ödenmedi)', 'template' => 'outofstock', 'color' => '#FF69B4',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 0,
				'logable' => 0, 'shipped' => 0, 'paid' => 0, 'pdf_delivery' => 0, 'pdf_invoice' => 0,
				'fri' => Order::STATUS_PENDING,
			],
			13 => [
				'name' => 'Kapıda ödeme bekleniyor', 'template' => 'cashondelivery', 'color' => '#4169E1',
				'unremovable' => 1, 'delivery' => 0, 'hidden' => 0, 'send_email' => 1, 'invoice' => 0,
				'logable' => 0, 'shipped' => 0, 'paid' => 0, 'pdf_delivery' => 0, 'pdf_invoice' => 0,
				'fri' => Order::STATUS_PENDING,
			],
			14 => [
				'name' => 'Beklemede', 'template' => '', 'color' => '#34219E',
				'unremovable' => 0, 'delivery' => 0, 'hidden' => 0, 'send_email' => 0, 'invoice' => 0,
				'logable' => 1, 'shipped' => 0, 'paid' => 1, 'pdf_delivery' => 0, 'pdf_invoice' => 0,
				'fri' => Order::STATUS_PROCESSING,
			],
		];
	}

	/** @return array<int, string> */
	private static function orderStates(): array
	{
		$out = [];
		foreach (self::orderStateCatalog() as $id => $row) {
			$out[$id] = (string) $row['name'];
		}

		return $out;
	}

	private static function friSayStatusFromPs(int $psId): int
	{
		$cat = self::orderStateCatalog();
		if (isset($cat[$psId]['fri'])) {
			return (int) $cat[$psId]['fri'];
		}

		return Order::STATUS_PROCESSING;
	}

	private static function psStateFromFriSay(int $fri, string $paymentHint = ''): int
	{
		if ($fri === Order::STATUS_PENDING) {
			$hint = strtolower($paymentHint);
			if (strpos($hint, 'bank') !== false || strpos($hint, 'havale') !== false
				|| strpos($hint, 'eft') !== false || strpos($hint, 'wire') !== false) {
				return 10;
			}

			return 1;
		}
		if ($fri === Order::STATUS_PROCESSING) {
			return 2;
		}
		if ($fri === Order::STATUS_SHIPPED) {
			return 4;
		}
		if ($fri === Order::STATUS_DELIVERED) {
			return 5;
		}
		if ($fri === Order::STATUS_CANCELLED) {
			return 6;
		}
		if ($fri === Order::STATUS_RETURNED || $fri === Order::STATUS_RETURN_PENDING) {
			return 7;
		}

		return 2;
	}

	/** @param array<string, mixed> $order */
	private static function carrierIdForOrder(array $order): int
	{
		$name = trim((string) ($order['cargo_company'] ?? ''));
		if ($name !== '') {
			foreach (Cargo::getList() as $row) {
				if (strcasecmp((string) ($row['name'] ?? ''), $name) === 0) {
					return (int) $row['id_cargo'];
				}
			}
		}

		$default = Cargo::getDefault();

		return $default ? (int) $default['id_cargo'] : 0;
	}

	private static function rawBody(): string
	{
		if (self::$rawBody === null) {
			$raw = file_get_contents('php://input');
			self::$rawBody = is_string($raw) ? $raw : '';
		}

		return self::$rawBody;
	}

	private static function beginRequestLog(): void
	{
		$query = $_GET;
		if (isset($query['ws_key'])) {
			$query['ws_key'] = self::redactSecret((string) $query['ws_key']);
		}

		$body = self::rawBody();
		if (strlen($body) > 65536) {
			$body = substr($body, 0, 65536) . "\n… [truncated]";
		}

		self::$requestLog = [
			'at' => date('Y-m-d H:i:s'),
			'ip' => RateLimit::clientIp(),
			'method' => strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
			'uri' => self::redactUri((string) ($_SERVER['REQUEST_URI'] ?? '')),
			'resource' => '',
			'id' => 0,
			'sub' => '',
			'query' => $query,
			'ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
			'content_type' => (string) ($_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '')),
			'accept' => substr((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 0, 180),
			'auth' => self::requestAuthKind(),
			'body' => $body,
			'status' => 0,
			'error' => '',
			'format' => '',
			'response' => '',
		];

		register_shutdown_function([self::class, 'flushRequestLog']);
	}

	private static function requestLogPathInfo(string $resource, int $id, string $sub): void
	{
		if (self::$requestLog === []) {
			return;
		}

		self::$requestLog['resource'] = $resource;
		self::$requestLog['id'] = $id;
		self::$requestLog['sub'] = $sub;
	}

	private static function touchRequestLog(int $status, string $error = '', string $preview = '', string $format = ''): void
	{
		if (self::$requestLog === []) {
			return;
		}

		self::$requestLog['status'] = $status;
		if ($error !== '') {
			self::$requestLog['error'] = $error;
		}
		if ($format !== '') {
			self::$requestLog['format'] = $format;
		}
		if ($preview !== '') {
			if (strlen($preview) > 2500) {
				$preview = substr($preview, 0, 2500) . "\n… [truncated]";
			}
			self::$requestLog['response'] = $preview;
		}
	}

	public static function flushRequestLog(): void
	{
		if (self::$requestLog === []) {
			return;
		}

		$entry = self::$requestLog;
		self::$requestLog = [];

		if ((int) ($entry['status'] ?? 0) <= 0) {
			$code = http_response_code();
			$entry['status'] = is_int($code) && $code >= 100 ? $code : 0;
		}

		// Admin API log: errors only (4xx / 5xx)
		$status = (int) ($entry['status'] ?? 0);
		if ($status < 400) {
			return;
		}

		$dir = dirname(__DIR__) . '/logs';
		if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
			return;
		}

		$path = $dir . '/psws-requests.jsonl';
		$line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (!is_string($line) || $line === '') {
			return;
		}

		@file_put_contents($path, $line . "\n", FILE_APPEND | LOCK_EX);

		if (is_file($path) && filesize($path) > 2097152) {
			self::trimRequestLogFile($path);
		}
	}

	private static function trimRequestLogFile(string $path): void
	{
		$lines = @file($path, FILE_IGNORE_NEW_LINES) ?: [];
		if (count($lines) <= 300) {
			return;
		}

		@file_put_contents($path, implode("\n", array_slice($lines, -300)) . "\n", LOCK_EX);
	}

	/** @return list<array<string, mixed>> */
	public static function getRequestLogs(int $limit = 80): array
	{
		$path = dirname(__DIR__) . '/logs/psws-requests.jsonl';
		if (!is_file($path)) {
			return [];
		}

		$lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
		$lines = array_slice($lines, -max(1, $limit * 3));
		$out = [];
		for ($i = count($lines) - 1; $i >= 0; $i--) {
			$row = json_decode((string) $lines[$i], true);
			if (!is_array($row)) {
				continue;
			}

			$status = (int) ($row['status'] ?? 0);
			if ($status < 400) {
				continue;
			}

			$out[] = $row;
			if (count($out) >= $limit) {
				break;
			}
		}

		return $out;
	}

	public static function clearRequestLogs(): bool
	{
		$path = dirname(__DIR__) . '/logs/psws-requests.jsonl';
		if (!is_file($path)) {
			return true;
		}

		return @unlink($path);
	}

	private static function requestAuthKind(): string
	{
		if (trim((string) ($_GET['ws_key'] ?? '')) !== '') {
			return 'query';
		}
		if (trim((string) ($_SERVER['PHP_AUTH_USER'] ?? '')) !== '') {
			return 'basic';
		}
		if (trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? '')) !== '') {
			return 'x-api-key';
		}

		$auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
		if (stripos($auth, 'Bearer ') === 0) {
			return 'bearer';
		}
		if (stripos($auth, 'Basic ') === 0) {
			return 'basic-header';
		}

		return 'none';
	}

	private static function redactUri(string $uri): string
	{
		return (string) preg_replace('/([?&]ws_key=)[^&]*/i', '$1***', $uri);
	}

	private static function redactSecret(string $value): string
	{
		$len = strlen($value);
		if ($len <= 8) {
			return '***';
		}

		return substr($value, 0, 4) . '…' . substr($value, -4);
	}
}
