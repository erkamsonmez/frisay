<?php

/**
 * Akakçe XML Feed
 * URL: /api/module.php?m=akakce&action=feed&token=TOKEN
 */

if (!defined('IN_SCRIPT')) {
	exit;
}

$requestToken = trim((string) Tools::getValue('token', ''));
$savedToken = AkakceFeedService::getFeedToken();

if ($savedToken === '' || !hash_equals($savedToken, $requestToken)) {
	http_response_code(401);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Geçersiz token']);
	exit;
}

if (!AkakceFeedService::isEnabled()) {
	http_response_code(503);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Feed devre dışı']);
	exit;
}

try {
	$xml = AkakceFeedService::buildFeed();
	$info = AkakceFeedService::cacheInfo();

	header('Content-Type: application/xml; charset=UTF-8');
	header('Content-Disposition: inline; filename="akakce-feed.xml"');
	header('X-Feed-Generator: FShop Akakce Module');
	header('Cache-Control: public, max-age=' . (int) $info['remaining'] . ', must-revalidate');
	if ($info['mtime'] > 0) {
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $info['mtime']) . ' GMT');
	}

	echo $xml;
} catch (Exception $e) {
	http_response_code(500);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Feed üretim hatası: ' . $e->getMessage()]);
}
