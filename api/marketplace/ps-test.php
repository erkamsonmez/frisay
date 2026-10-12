<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

$token = (string) Tools::getValue('token');
$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = Prestashop\ProductSyncService::testConnection();

echo json_encode([
	'success' => !empty($result['ok']),
	'message' => (string) ($result['message'] ?? ''),
	'httpCode' => (int) ($result['httpCode'] ?? 0),
	'shop_url' => (string) ($result['shop_url'] ?? ''),
	'product_count' => (int) ($result['product_count'] ?? 0),
	'sample_ids' => $result['sample_ids'] ?? [],
], JSON_UNESCAPED_UNICODE);
exit;
