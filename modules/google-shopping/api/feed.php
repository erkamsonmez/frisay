<?php

/**
 * Google Shopping XML Feed
 * URL: /api/module.php?m=google-shopping&action=feed&token=TOKEN
 *
 * Token, Admin → Google Shopping → Yapılandır ekranından alınır.
 * Google Merchant Center'da bu URL'yi "Zamanlanmış Getirme" olarak ekleyin.
 */

if (!defined('IN_SCRIPT')) {
    exit;
}

// Token doğrulama
$requestToken = trim((string) Tools::getValue('token', ''));
$savedToken   = trim((string) Settings::get('GSF_FEED_TOKEN'));

if ($savedToken === '' || !hash_equals($savedToken, $requestToken)) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Geçersiz token']);
    exit;
}

// Modül aktif mi?
if (Settings::get('GSF_ENABLED') !== '1') {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Feed devre dışı']);
    exit;
}

try {
    $xml = GoogleShoppingModule::buildFeed();
    $info = GoogleShoppingModule::cacheInfo();

    header('Content-Type: application/xml; charset=UTF-8');
    header('Content-Disposition: inline; filename="google-shopping-feed.xml"');
    header('X-Feed-Generator: FShop Google Shopping Module');
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
