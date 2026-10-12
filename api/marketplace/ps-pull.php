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

$offset = max(0, (int) Tools::getValue('offset', 0));
$limit = max(1, min(50, (int) Tools::getValue('limit', 20)));
$result = Prestashop\ProductSyncService::pullBatch($offset, $limit, Prestashop\PrestashopAdminPages::pullFiltersFromRequest());

echo json_encode([
	'success' => !empty($result['ok']),
	'message' => (string) ($result['message'] ?? ''),
	'created' => (int) ($result['created'] ?? 0),
	'linked' => (int) ($result['linked'] ?? 0),
	'updated' => (int) ($result['updated'] ?? 0),
	'skipped' => (int) ($result['skipped'] ?? 0),
	'failed' => (int) ($result['failed'] ?? 0),
	'has_more' => !empty($result['has_more']),
	'next_offset' => (int) ($result['next_offset'] ?? $offset),
	'items' => $result['items'] ?? [],
], JSON_UNESCAPED_UNICODE);
exit;
