<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

$pos = new PosModule();
$pos->requireApiAuth();
$pos->requireApiToken();

$result = $pos->createSale(
	trim((string) Tools::getValue('payment_method')),
	trim((string) Tools::getValue('customer_name')),
	trim((string) Tools::getValue('customer_phone')),
	trim((string) Tools::getValue('note')),
	(float) Tools::getValue('cash_paid', 0),
	-1,
	[
		'sale_type' => trim((string) Tools::getValue('sale_type', 'store')),
		'id_cargo' => (int) Tools::getValue('id_cargo', 0),
		'id_address' => (int) Tools::getValue('id_address', 0),
		'address_city' => trim((string) Tools::getValue('address_city')),
		'address_district' => trim((string) Tools::getValue('address_district')),
		'address_text' => trim((string) Tools::getValue('address_text')),
		'customer_email' => trim((string) Tools::getValue('customer_email')),
		'shipping_fee' => (float) Tools::getValue('shipping_fee', 0),
	]
);

echo json_encode($result);
