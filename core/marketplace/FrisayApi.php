<?php

namespace Frisay;

/**
 * Uzak FriSay / FShop Web API istemcisi (`docs/WEBAPI.md`).
 */
class FrisayApi
{
	private string $baseUrl;
	private string $apiKey;

	public function __construct(string $shopUrl, string $apiKey)
	{
		$this->apiKey = trim($apiKey);
		$this->baseUrl = self::normalizeBase($shopUrl);
	}

	public static function normalizeBase(string $shopUrl): string
	{
		$url = rtrim(trim($shopUrl), '/');

		if ($url === '') {
			return '';
		}

		if (!preg_match('#/api/v1$#i', $url)) {
			$url .= '/api/v1';
		}

		return $url;
	}

	public function isConfigured(): bool
	{
		if ($this->apiKey === '' || $this->baseUrl === '') {
			return false;
		}

		$parts = parse_url($this->baseUrl);
		$scheme = strtolower((string) ($parts['scheme'] ?? ''));

		return in_array($scheme, ['http', 'https'], true)
			&& trim((string) ($parts['host'] ?? '')) !== '';
	}

	/** @param mixed $result */
	public static function isApiError($result): bool
	{
		if (!is_array($result)) {
			return true;
		}

		if (isset($result['ok']) && $result['ok'] === false) {
			return true;
		}

		if (isset($result['success']) && $result['success'] === false) {
			return true;
		}

		return false;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function getOrders(?string $start = null, ?string $end = null, int $page = 0, int $size = 50): array
	{
		$query = [
			'page' => max(0, $page),
			'size' => max(1, min(100, $size)),
			'status' => 0,
		];

		if ($start !== null && $start !== '') {
			$query['date_from'] = date('Y-m-d', strtotime($start) ?: time());
		}

		if ($end !== null && $end !== '') {
			$query['date_to'] = date('Y-m-d', strtotime($end) ?: time());
		}

		$data = $this->request('GET', '/orders', null, $query);

		if (self::isApiError($data)) {
			return [];
		}

		$content = $data['content'] ?? ($data['data'] ?? []);

		return is_array($content) ? array_values($content) : [];
	}

	/** @return array<string, mixed>|null */
	public function getOrder(int $id): ?array
	{
		if ($id <= 0) {
			return null;
		}

		$data = $this->request('GET', '/orders/' . $id);

		if (self::isApiError($data)) {
			return null;
		}

		if (isset($data['content']) && is_array($data['content'])) {
			return $data['content'];
		}

		return $data;
	}

	/**
	 * @return array{ok:bool,message:string,items?:array<int,array<string,mixed>>,has_more?:bool,next_page?:int}
	 */
	public function listProducts(int $page = 1, int $limit = 10): array
	{
		$data = $this->request('GET', '/products', null, [
			'page' => max(1, $page),
			'limit' => max(1, min(50, $limit)),
		]);

		if (self::isApiError($data)) {
			return ['ok' => false, 'message' => (string) ($data['message'] ?? 'Ürün listesi alınamadı'), 'items' => []];
		}

		$items = $data['data'] ?? ($data['content'] ?? ($data['items'] ?? []));

		if (!is_array($items)) {
			$items = [];
		}

		$meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
		$totalPages = (int) ($meta['pages'] ?? ($data['totalPages'] ?? ($data['total_pages'] ?? 0)));
		$hasMore = $totalPages > 0 ? $page < $totalPages : count($items) >= $limit;

		return [
			'ok' => true,
			'message' => '',
			'items' => array_values($items),
			'has_more' => $hasMore,
			'next_page' => $page + 1,
		];
	}

	/** @return array<string, mixed>|null */
	public function getProduct(int $id): ?array
	{
		if ($id <= 0) {
			return null;
		}

		$data = $this->request('GET', '/products/' . $id);

		if (self::isApiError($data)) {
			return null;
		}

		if (isset($data['data']) && is_array($data['data'])) {
			return $data['data'];
		}

		return $data;
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function createProduct(array $payload): array
	{
		return $this->request('POST', '/products', $payload);
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function updateProduct(int $id, array $payload): array
	{
		return $this->request('PATCH', '/products/' . $id, $payload);
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function quickUpdate(int $id, array $payload): array
	{
		return $this->request('PATCH', '/products/' . $id . '/quick', $payload);
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public function updateOrder(int $id, array $payload): array
	{
		return $this->request('PATCH', '/orders/' . $id, $payload);
	}

	/**
	 * @param array<string, mixed>|null $body
	 * @param array<string, scalar> $query
	 * @return array<string, mixed>
	 */
	public function request(string $method, string $path, ?array $body = null, array $query = []): array
	{
		if (!$this->isConfigured()) {
			return ['ok' => false, 'success' => false, 'message' => 'FriSay API ayarları eksik'];
		}

		if (!function_exists('curl_init')) {
			return ['ok' => false, 'success' => false, 'message' => 'cURL eklentisi yok'];
		}

		$url = $this->baseUrl . '/' . ltrim($path, '/');

		if ($query !== []) {
			$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
		}

		$ch = curl_init($url);
		$headers = [
			'X-API-Key: ' . $this->apiKey,
			'Accept: application/json',
			'User-Agent: FriSay-Marketplace',
		];

		$method = strtoupper($method);

		if ($body !== null) {
			$headers[] = 'Content-Type: application/json';
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
		}

		$opts = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
		];

		if (!class_exists('UpdateChecker', false)) {
			require_once dirname(__DIR__) . '/UpdateChecker.php';
		}

		$ca = \UpdateChecker::caBundlePath();

		if ($ca !== '') {
			$opts[CURLOPT_CAINFO] = $ca;
		}

		curl_setopt_array($ch, $opts);
		$response = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = (string) curl_error($ch);
		curl_close($ch);

		if ($response === false) {
			return ['ok' => false, 'success' => false, 'message' => $error !== '' ? $error : 'İstek başarısız'];
		}

		$decoded = json_decode((string) $response, true);

		if ($code < 200 || $code >= 300) {
			$message = $error !== '' ? $error : ('HTTP ' . $code);

			if (is_array($decoded) && !empty($decoded['message'])) {
				$message = (string) $decoded['message'];
			}

			return ['ok' => false, 'success' => false, 'message' => $message, 'httpCode' => $code];
		}

		if (!is_array($decoded)) {
			return ['ok' => true, 'success' => true, 'raw' => $response];
		}

		if (!isset($decoded['ok'])) {
			$decoded['ok'] = true;
		}

		return $decoded;
	}
}
