<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

if ((string) Tools::getValue('send', '') === '0') {
	Marketplace::setAllowMarketplaceStockPush(false);
}

$platform = strtolower(trim((string) Tools::getValue('platform', 'trendyol')));
$start = trim((string) Tools::getValue('start_date', ''));
$end = trim((string) Tools::getValue('end_date', ''));

if ($platform === 'all') {
	$messages = [];
	$count = 0;
	$orders = [];

	foreach (Marketplace::orderPlatformKeys() as $key) {
		if (!Marketplace::isPlatformConfigured($key)) {
			continue;
		}

		$one = Marketplace::syncOrdersForPlatform(
			$key,
			$start !== '' ? $start : null,
			$end !== '' ? $end : null
		);
		$messages[] = (string) ($one['message'] ?? $key);
		$count += (int) ($one['count'] ?? 0);
		if (!empty($one['orders']) && is_array($one['orders'])) {
			$orders = array_merge($orders, $one['orders']);
		}
	}

	$result = [
		'ok' => true,
		'message' => $messages === [] ? 'Yapılandırılmış pazaryeri yok' : implode(' · ', $messages),
		'count' => $count,
		'orders' => $orders,
	];
} else {
	if (!Marketplace::isOrderPlatform($platform)) {
		$platform = 'trendyol';
	}

	$result = Marketplace::syncOrdersForPlatform(
		$platform,
		$start !== '' ? $start : null,
		$end !== '' ? $end : null
	);
}

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'count' => $result['count'] ?? 0,
	'orders' => $result['orders'] ?? [],
	'platform' => $platform,
], JSON_UNESCAPED_UNICODE);
exit;
