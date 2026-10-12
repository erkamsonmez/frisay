<?php

define('IN_SCRIPT', true);

require_once dirname(__DIR__) . '/config/install_gate.php';

if (!fshop_is_installed()) {
	http_response_code(503);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Kurulum tamamlanmamış']);
	exit;
}

require_once dirname(__DIR__) . '/config/admin_bootstrap.php';

while (ob_get_level() > 0) {
	ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Oturum gerekli']);
	exit;
}

$token = (string) Tools::getValue('token');

if ($token === '' || !hash_equals((string) $adminToken, $token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
	exit;
}

$action = (string) Tools::getValue('action', 'save');

if ($action !== 'save') {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
	exit;
}

$keys = Tools::getValue('keys');

if (is_string($keys) && $keys !== '') {
	$decoded = json_decode($keys, true);
	$keys = is_array($decoded) ? $decoded : [];
}

if (!is_array($keys)) {
	$keys = [];
}

if (!AdminMenuFavorites::saveKeys($keys)) {
	echo json_encode(['success' => false, 'message' => 'Kaydedilemedi']);
	exit;
}

echo json_encode([
	'success' => true,
	'keys' => AdminMenuFavorites::getKeys(),
]);
