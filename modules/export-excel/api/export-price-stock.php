<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!class_exists('Admin')) {
	require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

require_once dirname(__DIR__) . '/lib/ExportExcelService.php';

if (!Admin::isLoggedIn()) {
	http_response_code(403);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

$token = (string) Tools::getValue('token');
$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
	http_response_code(403);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek'], JSON_UNESCAPED_UNICODE);
	exit;
}

$query = trim((string) Tools::getValue('q'));
$idCategory = (int) Tools::getValue('category');
$idBrand = (int) Tools::getValue('brand');
$activeFilter = Tools::getIsset('active') ? (int) Tools::getValue('active') : -1;
$filters = [
	'reference' => trim((string) Tools::getValue('reference')),
	'category_q' => trim((string) Tools::getValue('category_q')),
	'id_min' => trim((string) Tools::getValue('id_min')),
	'id_max' => trim((string) Tools::getValue('id_max')),
	'price_excl_min' => trim((string) Tools::getValue('price_excl_min')),
	'price_excl_max' => trim((string) Tools::getValue('price_excl_max')),
	'price_incl_min' => trim((string) Tools::getValue('price_incl_min')),
	'price_incl_max' => trim((string) Tools::getValue('price_incl_max')),
	'stock_min' => trim((string) Tools::getValue('stock_min')),
	'stock_max' => trim((string) Tools::getValue('stock_max')),
];

ExportExcelService::exportPriceStock($query, $idCategory, $idBrand, $activeFilter, $filters);
