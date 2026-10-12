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

if ($idProduct <= 0) {
	echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

$platform = strtolower(trim((string) Tools::getValue('platform', 'trendyol')));
$meta = [];
$brandId = (int) Tools::getValue('brand_id', 0);
$categoryId = (int) Tools::getValue('category_id', 0);

if ($brandId > 0) {
	$meta['brand_id'] = $brandId;
}

if ($categoryId > 0) {
	$meta['category_id'] = $categoryId;
}

$saleRaw = Tools::getValue('sale_price', null);
$listRaw = Tools::getValue('list_price', null);

if ($saleRaw !== null && $saleRaw !== '') {
	$meta['sale_price'] = (float) str_replace(',', '.', (string) $saleRaw);
}

if ($listRaw !== null && $listRaw !== '') {
	$meta['list_price'] = (float) str_replace(',', '.', (string) $listRaw);
}

$attrsRaw = Tools::getValue('attributes');

if (is_string($attrsRaw) && $attrsRaw !== '') {
	$decoded = json_decode($attrsRaw, true);

	if (is_array($decoded)) {
		$meta['attributes'] = $decoded;
	}
} elseif (is_array($attrsRaw)) {
	$meta['attributes'] = $attrsRaw;
}

$variantsRaw = Tools::getValue('variants');

if (is_string($variantsRaw) && $variantsRaw !== '') {
	$decodedVariants = json_decode($variantsRaw, true);

	if (is_array($decodedVariants)) {
		$meta['variants'] = $decodedVariants;
	}
} elseif (is_array($variantsRaw)) {
	$meta['variants'] = $variantsRaw;
}

if ($platform === 'prestashop') {
	$result = Prestashop\ProductSyncService::sendProduct($idProduct);
	$mapping = $result['mapping'] ?? Prestashop\ProductSyncService::findMapping($idProduct);
} elseif ($platform === 'frisay') {
	$result = Frisay\ProductSyncService::sendProduct($idProduct);
	$mapping = $result['mapping'] ?? Frisay\ProductSyncService::findMapping($idProduct);
} elseif ($platform === 'hepsiburada') {
	$result = Hepsiburada\ProductSyncService::sync($idProduct, $meta);
	$mapping = $result['mapping'] ?? Hepsiburada\ProductSyncService::findMapping($idProduct);
} elseif ($platform === 'n11') {
	$result = N11\ProductSyncService::sync($idProduct, $meta);
	$mapping = $result['mapping'] ?? N11\ProductSyncService::findMapping($idProduct);
} else {
	$result = Trendyol\ProductSyncService::sync($idProduct, $meta);
	$mapping = $result['mapping'] ?? Trendyol\ProductSyncService::findMapping($idProduct);
}

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'mapping' => $mapping,
	'platform' => $platform,
], JSON_UNESCAPED_UNICODE);
exit;
