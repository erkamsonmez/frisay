<?php
declare(strict_types=1);
require dirname(__DIR__) . '/libs/Smarty.class.php';
require dirname(__DIR__) . '/core/Theme.php';
class Settings {
    public static function get(string $key): string { return ''; }
}
$root = dirname(__DIR__);
$smarty = new Smarty\Smarty();
$smarty->setTemplateDir($root . '/templates');
$smarty->setCompileDir(sys_get_temp_dir() . '/frisay-cnc-smarty-compile');
$smarty->setCacheDir(sys_get_temp_dir() . '/frisay-cnc-smarty-cache');
$smarty->registerPlugin('modifier', 'translate', static fn($s) => $s);
$smarty->assign([
    'themeOptions' => Theme::getResolvedOptions('cnc-industrial'),
    'homeCategories' => [],
    'featuredProducts' => [],
    'dealProducts' => [],
    'categoryBlocks' => [],
    'hooks' => ['home_slider'=>'','home_promo_slider'=>'','home_bottom'=>'','main_menu'=>''],
    'domain' => 'https://ustarafi.com/',
    'siteName' => 'Usta Rafı',
    'isLoggedIn' => false,
    'cart' => ['count'=>0],
    'siteLogos' => ['header'=>'','hero'=>''],
    'menuCategories' => [],
    'minute' => '1',
    'searchQuery' => '',
]);
$home=$smarty->fetch('cnc-industrial/home.tpl');
if (!str_contains($home, 'Üretimin ritmini') || !str_contains($home, 'Teknik odaklı katalog')) {
    throw new RuntimeException('Default homepage fields did not render');
}
$header=$smarty->fetch('cnc-industrial/_mini/header1.tpl');
if (!str_contains($header, 'PROFESYONEL TALAŞLI') || !str_contains($header, 'ENDÜSTRİYEL EKİPMANLAR')) {
    throw new RuntimeException('Default header fields did not render');
}
$opts=Theme::getResolvedOptions('cnc-industrial');
$opts['hero_title_first']='KİŞİSEL BAŞLIK';
$opts['hero_description']='<script>alert(1)</script>';
$opts['benefits_show']='0';
$opts['header']='header2';
$smarty->assign('themeOptions',$opts);
$changed=$smarty->fetch('cnc-industrial/home.tpl');
if (!str_contains($changed, 'KİŞİSEL BAŞLIK') || str_contains($changed, '<script>alert(1)</script>') || str_contains($changed, 'Teknik odaklı katalog')) {
    throw new RuntimeException('Content edit, escaping or block visibility failed');
}
$second=$smarty->fetch('cnc-industrial/_mini/header2.tpl');
if (!str_contains($second, 'cnc-header--compact')) throw new RuntimeException('Compact header is not selected');
// Render the actual admin edit form, not just the storefront.
$smarty->registerPlugin('modifier', 'adminT', static fn($s) => $s);
$smarty->assign([
    'flash'=>'',
    'adminUrl'=>'/admin/',
    'adminToken'=>'test-token',
    'editTheme'=>'cnc-industrial',
    'activeTheme'=>'cnc-industrial',
    'themeMeta'=>Theme::getMeta('cnc-industrial'),
    'themeOptionDefs'=>Theme::getOptionDefinitions('cnc-industrial'),
    'themeOptions'=>Theme::getOptions('cnc-industrial'),
    'headerVariants'=>Theme::discoverHeaderVariants('cnc-industrial'),
    'colorDefs'=>Theme::getColorDefinitions('cnc-industrial'),
    'colorGroups'=>Theme::getColorGroups('cnc-industrial'),
    'themeColors'=>Theme::getColors('cnc-industrial'),
    'colorPickerValues'=>Theme::getColors('cnc-industrial'),
    'previewUrl'=>'https://ustarafi.com/templates/cnc-industrial/theme-preview.svg',
    'siteLogos'=>[
       ['key'=>'header','file'=>'logo.png','url'=>'','exists'=>false,'label'=>'Ana logo'],
       ['key'=>'hero','file'=>'cnc-hero.webp','url'=>'','exists'=>false,'label'=>'Banner'],
    ],
    'footerDescription'=>'',
    'footerDescriptionDefault'=>'',
]);
$admin=$smarty->fetch('admin/theme-customize.tpl');
foreach (['id="opt_hero_title_first"', 'id="opt_hero_description"', 'id="opt_hero_image_show"', 'id="color_sm-primary"', 'id="opt_tagline"'] as $needle) {
    if (!str_contains($admin,$needle)) throw new RuntimeException('Admin field not rendered: '.$needle);
}
if (!str_contains($admin,'<textarea name="opt_hero_description"')) throw new RuntimeException('Textarea field not rendered');
echo 'Smarty storefront and admin customization tests passed',PHP_EOL;
