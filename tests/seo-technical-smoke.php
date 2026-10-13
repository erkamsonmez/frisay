<?php
declare(strict_types=1);

class Settings
{
    public static array $data = ['DOMAIN' => 'https://ustarafi.com/', 'SITE_NAME' => 'Usta Rafı'];
    public static function get(string $key): string { return self::$data[$key] ?? ''; }
    public static function set(string $key, string $val): bool { self::$data[$key] = $val; return true; }
}
class Security
{
    public static function jsonForHtmlScript($data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
class SiteAssets
{
    public static function resolveLogoUrl(string $name): string { return 'https://ustarafi.com/img/logo.png'; }
}
class FakeSmarty
{
    public array $vars = [];
    public array $displays = [];
    public function assign($key, $val = null): void
    {
        if (is_array($key)) { $this->vars = array_merge($this->vars, $key); }
        else { $this->vars[$key] = $val; }
    }
    public function getTemplateVars(string $key) { return $this->vars[$key] ?? null; }
    public function display(string $name): void { $this->displays[] = $name; }
}
function assertSameSeo($expected, $actual, string $reason): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($reason . ' expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

require dirname(__DIR__) . '/core/Seo.php';
require dirname(__DIR__) . '/core/SchemaOrg.php';
require dirname(__DIR__) . '/config/page.php';
define('_THEME_BASE_DIR_', 'templates/cnc-industrial/');
$domain = Settings::get('DOMAIN');

http_response_code(200);
$_SERVER['HTTP_HOST'] = 'test.internal:8000';
$_SERVER['HTTPS'] = 'off';
$_SERVER['REQUEST_URI'] = '/?utm_campaign=test';
assertSameSeo('https://ustarafi.com/', Seo::technicalMeta('home')['canonical'], 'home canonical');

$_SERVER['REQUEST_URI'] = '/problar/prob-34?utm_medium=ad';
$meta = Seo::technicalMeta('product', [
    'productUrl' => 'https://ustarafi.com/problar/prob-34',
    'imageUrl' => 'https://ustarafi.com/img/products/34.jpg',
]);
assertSameSeo('https://ustarafi.com/problar/prob-34', $meta['canonical'], 'product canonical');
assertSameSeo('product', $meta['ogType'], 'product OG type');
assertSameSeo('https://ustarafi.com/img/products/34.jpg', $meta['ogImage'], 'product image');

$meta = Seo::technicalMeta('product', ['productUrl' => 'https://malicious.example/x']);
assertSameSeo('https://ustarafi.com/problar/prob-34', $meta['canonical'], 'external canonical rejected');

$_SERVER['REQUEST_URI'] = '/problar?page=3&sort=newest';
$context = [
    'catalogBaseUrl' => 'https://ustarafi.com/problar',
    'pagination' => ['page' => 3],
    'catalogFilter' => ['hasActive' => false],
];
$meta = Seo::technicalMeta('category', $context, ['page' => '3', 'sort' => 'newest']);
assertSameSeo('https://ustarafi.com/problar?page=3', $meta['canonical'], 'paginated self canonical');
assertSameSeo('index, follow', $meta['robots'], 'paginated indexable');

$meta = Seo::technicalMeta('category', $context, ['price_min' => '100', 'page' => '3']);
assertSameSeo('https://ustarafi.com/problar', $meta['canonical'], 'filter canonical');
assertSameSeo('noindex, follow', $meta['robots'], 'filtered noindex');

$meta = Seo::technicalMeta('category', $context, ['sort' => 'price_asc']);
assertSameSeo('noindex, follow', $meta['robots'], 'sorted noindex');

$_SERVER['REQUEST_URI'] = '/catalog?page=2';
$meta = Seo::technicalMeta('catalog', ['catalogBaseUrl' => 'https://ustarafi.com/catalog', 'pagination' => ['page' => 2]], ['page' => 2]);
assertSameSeo('https://ustarafi.com/catalog?page=2', $meta['canonical'], 'catalog page 2 canonical');

foreach (['search', 'cart', 'checkout', 'login', 'my-account', '404', 'truck', 'card-payment'] as $private) {
    assertSameSeo('noindex, follow', Seo::technicalMeta($private)['robots'], $private . ' noindex');
}
http_response_code(404);
assertSameSeo('noindex, follow', Seo::technicalMeta('cms')['robots'], '404 response noindex');
http_response_code(200);

// Page::add must propagate exactly the same canonical to SEO and schema JSON-LD.
$_SERVER['REQUEST_URI'] = '/problar/prob-34?utm_source=test';
$smarty = new FakeSmarty();
$smarty->assign('productUrl', 'https://ustarafi.com/problar/prob-34');
$smarty->assign('imageUrl', 'https://ustarafi.com/img/products/34.jpg');
Page::add('product', 'Z sıfırlama probu', false, false, 'Açıklama');
assertSameSeo('https://ustarafi.com/problar/prob-34', $smarty->vars['seoMeta']['canonical'], 'Page::add SEO metadata');
$structured = array_map(static fn($json) => json_decode($json, true, 512, JSON_THROW_ON_ERROR), $smarty->vars['schemaJsonLd']);
$pageSchema = array_values(array_filter($structured, static fn($data) => $data['@type'] === 'WebPage'))[0];
assertSameSeo('https://ustarafi.com/problar/prob-34', $pageSchema['url'], 'WebPage JSON-LD canonical');
if (count($smarty->displays) !== 3) throw new RuntimeException('Page render layout changed');

$ht = file_get_contents(dirname(__DIR__) . '/.htaccess');
$robotsAt = strpos($ht, 'RewriteRule ^robots\.txt$ robots.php [L]');
$staticAt = strpos($ht, '# 3. Gerçek dosya');
if ($robotsAt === false || $staticAt === false || $robotsAt > $staticAt) {
    throw new RuntimeException('robots.txt rewriting must precede physical-file bypass');
}
if (!str_contains($ht, 'RewriteRule ^cache/assets/css/[^/]+\.css$ - [L]')) {
    throw new RuntimeException('Googlebot cannot load public CSS bundles');
}
if (!is_file(dirname(__DIR__) . '/container/front/catalog.php')) {
    throw new RuntimeException('Catalog links still point to a missing route');
}
$markup = file_get_contents(dirname(__DIR__) . '/templates/cnc-industrial/header.tpl');
foreach (['seoMeta.canonical', 'seoMeta.robots', 'seoMeta.ogType', 'seoMeta.ogImage'] as $key) {
    if (!str_contains($markup, $key)) throw new RuntimeException('Template misses metadata variable: '.$key);
}

echo "SEO canonical, noindex, OG, SchemaOrg, routing and robots checks passed", PHP_EOL;
