<?php
/** Public robots.txt using the configured production hostname for its sitemap. */
define('IN_SCRIPT', true);
require_once __DIR__ . '/config/settings.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=900');
$domain = rtrim((string) Settings::get('DOMAIN'), '/');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Allow: /cache/assets/css/\n";
foreach (['admin', 'install', 'config', 'cache', 'core', 'container', 'api', 'backups', 'logs'] as $path) {
    echo 'Disallow: /' . $path . "/\n";
}
echo "\nSitemap: " . $domain . "/sitemap.xml\n";
