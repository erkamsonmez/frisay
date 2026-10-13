<?php
/**
 * Search-engine sitemaps. Routes:
 * /sitemap.xml (index), /sitemap-pages.xml, /sitemap-categories.xml,
 * /sitemap-products-{n}.xml. Published products are paged; no fixed 5,000 limit.
 * Content and category update dates are emitted only if supplied by the database.
 */
define('IN_SCRIPT', true);
require_once __DIR__ . '/config/settings.php';

$section = (string) ($_GET['section'] ?? 'index');
$page = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$page = $page === false ? 0 : $page;
$base = rtrim((string) Settings::get('DOMAIN'), '/');
const SITEMAP_SIZE = 1000;

function sitemapEsc(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function sitemapLastmod(array $row): string
{
    foreach (['date_upd', 'updated_at', 'date_modified'] as $key) {
        $candidate = (string) ($row[$key] ?? '');
        $ts = $candidate !== '' ? strtotime($candidate) : false;
        if ($ts !== false && $ts > 0 && $ts <= time()) {
            return date('Y-m-d', $ts);
        }
    }
    return '';
}

function sitemapUrlItem(string $url, string $lastmod = ''): void
{
    echo "  <url><loc>" . sitemapEsc($url) . "</loc>";
    if ($lastmod !== '') {
        echo '<lastmod>' . sitemapEsc($lastmod) . '</lastmod>';
    }
    echo "</url>\n";
}

function sitemapIndexItem(string $url): void
{
    echo '  <sitemap><loc>' . sitemapEsc($url) . "</loc></sitemap>\n";
}

$productCount = Product::countActive();
$productPages = (int) ceil($productCount / SITEMAP_SIZE);

if (!in_array($section, ['index', 'pages', 'categories', 'products'], true)
    || ($section === 'products' && ($page < 1 || $page > $productPages))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Sitemap not found';
    exit;
}

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=900');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

if ($section === 'index') {
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    sitemapIndexItem($base . '/sitemap-pages.xml');
    sitemapIndexItem($base . '/sitemap-categories.xml');
    for ($i = 1; $i <= $productPages; $i++) {
        sitemapIndexItem($base . '/sitemap-products-' . $i . '.xml');
    }
    echo "</sitemapindex>\n";
    exit;
}

echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
if ($section === 'pages') {
    foreach (['/', '/catalog', '/special', '/contact'] as $path) {
        sitemapUrlItem($base . $path);
    }
    foreach (Cms::getPages() as $slug => $cms) {
        $slug = trim((string) $slug, '/');
        if ($slug !== '') {
            sitemapUrlItem($base . '/' . $slug, sitemapLastmod($cms));
        }
    }
} elseif ($section === 'categories') {
    $rootId = Category::getRootId();
    $categories = DB::execute('SELECT * FROM categories WHERE active = 1 ORDER BY id_category ASC') ?: [];
    foreach ($categories as $category) {
        if ((int) ($category['id_category'] ?? 0) === $rootId || (string) ($category['category_link'] ?? '') === '') {
            continue;
        }
        sitemapUrlItem(Category::getUrl($category), sitemapLastmod($category));
    }
} else {
    $products = Product::getActiveList(null, SITEMAP_SIZE, ($page - 1) * SITEMAP_SIZE);
    foreach ($products as $product) {
        sitemapUrlItem(Product::getLink($product), sitemapLastmod($product));
    }
}
echo "</urlset>\n";
