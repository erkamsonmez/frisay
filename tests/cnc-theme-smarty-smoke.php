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
echo 'Smarty templates compile and dynamic homepage options render correctly',PHP_EOL;
