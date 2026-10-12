<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

$idProduct = (int) Tools::getValue('id_product', 0);
$paused = (int) Tools::getValue('paused', 1) === 1;
$platform = strtolower(trim((string) Tools::getValue('platform', 'trendyol')));

if ($idProduct <= 0) {
	echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

if ($platform !== 'trendyol') {
	echo json_encode(['success' => false, 'message' => 'Bu işlem şu an yalnızca Trendyol için'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = Trendyol\ProductSyncService::setArchived($idProduct, $paused);

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'paused' => $paused ? 1 : 0,
	'platform' => $platform,
], JSON_UNESCAPED_UNICODE);
exit;
