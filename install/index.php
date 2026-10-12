<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/install_gate.php';

if (!headers_sent()) {
	header('Cache-Control: no-store, no-cache, must-revalidate, private');
	header('Pragma: no-cache');
	header('Expires: 0');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

$justFinished = !empty($_SESSION['fshop_install_success']);
if (fshop_is_installed() && !$justFinished) {
	header('Location: ../');
	exit;
}

require_once dirname(__DIR__) . '/core/Installer.php';
require_once dirname(__DIR__) . '/core/FShop.php';

$installVideoUrl = 'https://www.youtube.com/watch?v=DjJjm9zkrF4&t=12s';
$productName = FShop::NAME;
$productVersion = FShop::VERSION;
$productMajor = (int) explode('.', $productVersion)[0];
$brandTitle = $productName . ' V' . $productMajor;

function install_h($value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function install_is_ajax(): bool
{
	if (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
		return true;
	}

	return strpos(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json') !== false;
}

function install_json_exit(array $result): void
{
	while (ob_get_level() > 0) {
		ob_end_clean();
	}

	if (!headers_sent()) {
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: no-store, no-cache, must-revalidate, private');
	}

	echo json_encode($result, JSON_UNESCAPED_UNICODE);
	exit;
}

if (isset($_GET['restart']) && $_GET['restart'] === '1') {
	unset(
		$_SESSION['fshop_install'],
		$_SESSION['fshop_install_error'],
		$_SESSION['fshop_install_notice'],
		$_SESSION['fshop_install_success'],
		$_SESSION['fshop_install_result']
	);
	header('Location: ?step=1');
	exit;
}

$availableThemes = Installer::availableThemes();
$defaultTheme = in_array('theme4', $availableThemes, true) ? 'theme4' : 'default';

$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
	. '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
	. rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install')), '/');
$rewriteBase = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install')), '/') . '/';
if ($rewriteBase === '') {
	$rewriteBase = '/';
}

$defaults = [
	'db_host' => 'localhost',
	'db_name' => 'frisay',
	'db_user' => 'root',
	'db_pass' => '',
	'site_name' => 'FriSay',
	'site_url' => $siteUrl,
	'rewrite_base' => $rewriteBase,
	'admin_name' => 'Admin',
	'admin_email' => 'admin@example.com',
	'admin_password' => '',
	'theme' => $defaultTheme,
	'shop_lang' => 'tr',
	'admin_lang' => 'tr',
	'install_demo' => '1',
	'ui_lang' => 'tr',
];

if (!isset($_SESSION['fshop_install']) || !is_array($_SESSION['fshop_install'])) {
	$_SESSION['fshop_install'] = $defaults;
}

$wiz = &$_SESSION['fshop_install'];
foreach ($defaults as $key => $value) {
	if (!array_key_exists($key, $wiz)) {
		$wiz[$key] = $value;
	}
}

$fields = array_keys($defaults);
$maxStep = 5;
$requestedStep = isset($_GET['step']) ? (int) $_GET['step'] : 0;
$step = $requestedStep > 0
	? max(1, min($maxStep, $requestedStep))
	: max(1, min($maxStep, (int) ($wiz['_step'] ?? 1)));
$maxReached = max(1, (int) ($wiz['_max'] ?? 1));
$pendingInstall = !empty($wiz['_pending_install']);

// Stale step 5 (refresh / old session) must not auto-install.
if ($step === 5 && empty($_SESSION['fshop_install_success']) && !$pendingInstall) {
	$step = max(1, min(4, $maxReached >= 5 ? 4 : $maxReached));
}

if ($step > $maxReached && empty($_SESSION['fshop_install_success']) && !($step === 5 && $pendingInstall)) {
	$step = $maxReached;
}
$error = (string) ($_SESSION['fshop_install_error'] ?? '');
$notice = (string) ($_SESSION['fshop_install_notice'] ?? '');
$success = (string) ($_SESSION['fshop_install_success'] ?? '');
$resultExtra = $_SESSION['fshop_install_result'] ?? [];
unset($_SESSION['fshop_install_error'], $_SESSION['fshop_install_notice']);

if (isset($_GET['lang']) && in_array($_GET['lang'], ['tr', 'en'], true)) {
	$wiz['ui_lang'] = $_GET['lang'];
}

$ui = $wiz['ui_lang'] === 'en' ? 'en' : 'tr';

$T = [
	'tr' => [
		'window' => 'FriSay V3 Kurulum',
		'title' => 'Kurulum Sihirbazı',
		'help' => 'Yardım',
		'step_of' => 'Adım %d / %d',
		's1' => 'Karşılama',
		's2' => 'Veritabanı',
		's3' => 'Mağaza',
		's4' => 'Yönetici',
		's5' => 'Tamamlandı',
		'welcome_h' => 'FriSay V3 kurulumuna hoş geldiniz',
		'welcome_p' => 'Bu sihirbaz mağazanızı adım adım hazırlar. Devam etmeden önce sunucu gereksinimlerini kontrol edin.',
		'req_h' => 'Sistem gereksinimleri',
		'req_fail' => 'Tüm gereksinimler sağlanmadan kuruluma devam edilemez.',
		'db_h' => 'Veritabanı bağlantısı',
		'db_p' => 'MySQL bilgilerini girin. Veritabanı yoksa kurulum sırasında oluşturulur.',
		'db_host' => 'Sunucu',
		'db_name' => 'Veritabanı adı',
		'db_user' => 'Kullanıcı adı',
		'db_pass' => 'Şifre',
		'db_test' => 'Bağlantıyı dene',
		'store_h' => 'Mağaza ayarları',
		'store_p' => 'Mağaza adı, adres ve görünüm.',
		'site_name' => 'Mağaza adı',
		'site_url' => 'Mağaza adresi',
		'rewrite_base' => 'Rewrite tabanı',
		'theme' => 'Tema',
		'shop_lang' => 'Mağaza dili',
		'admin_lang' => 'Yönetim paneli dili',
		'admin_h' => 'Yönetici hesabı',
		'admin_p' => 'Yönetim paneline giriş için kullanılacak hesap.',
		'admin_name' => 'Ad soyad',
		'admin_email' => 'E-posta',
		'admin_password' => 'Şifre (en az 8 karakter)',
		'demo' => 'Demo ürün ve kategorileri yükle',
		'done_h' => 'Kurulum tamamlandı',
		'done_p' => 'Mağazanız hazır. Güvenlik için canlı ortamda /install/ klasörüne erişimi kapatın.',
		'go_admin' => 'Yönetim paneline git',
		'go_shop' => 'Mağazayı aç',
		'back' => '< Geri',
		'next' => 'İleri >',
		'install' => 'Kurulumu başlat',
		'restart' => 'Baştan başla',
		'restart_confirm' => 'Kurulum sihirbazı başa alınsın mı? Girdiğiniz bilgiler silinir.',
		'installing_h' => 'Kurulum yapılıyor',
		'installing_p' => 'Lütfen bu sayfayı kapatmayın. Tablolar, ayarlar ve yönetici hesabı oluşturuluyor.',
		'st_connect' => 'Veritabanına bağlanılıyor',
		'st_schema' => 'Tablolar oluşturuluyor',
		'st_settings' => 'Mağaza ayarları yazılıyor',
		'st_admin' => 'Yönetici hesabı hazırlanıyor',
		'st_finish' => 'Kurulum tamamlanıyor',
		'video' => 'Kurulum videosu',
		'video_watch' => 'YouTube’da izle',
		'lang_tr' => 'Türkçe',
		'lang_en' => 'English',
		'ok' => 'Tamam',
		'fail' => 'Eksik',
	],
	'en' => [
		'window' => 'FriSay V3 Setup',
		'title' => 'Setup Wizard',
		'help' => 'Help',
		'step_of' => 'Step %d of %d',
		's1' => 'Welcome',
		's2' => 'Database',
		's3' => 'Store',
		's4' => 'Administrator',
		's5' => 'Finished',
		'welcome_h' => 'Welcome to FriSay V3 setup',
		'welcome_p' => 'This wizard will set up your store step by step. Check the server requirements before continuing.',
		'req_h' => 'System requirements',
		'req_fail' => 'You cannot continue until every requirement is met.',
		'db_h' => 'Database connection',
		'db_p' => 'Enter your MySQL details. The database will be created during setup if needed.',
		'db_host' => 'Host',
		'db_name' => 'Database name',
		'db_user' => 'Username',
		'db_pass' => 'Password',
		'db_test' => 'Test connection',
		'store_h' => 'Store settings',
		'store_p' => 'Store name, URL and appearance.',
		'site_name' => 'Store name',
		'site_url' => 'Store URL',
		'rewrite_base' => 'Rewrite base',
		'theme' => 'Theme',
		'shop_lang' => 'Store language',
		'admin_lang' => 'Admin language',
		'admin_h' => 'Administrator account',
		'admin_p' => 'This account is used to sign in to the admin panel.',
		'admin_name' => 'Full name',
		'admin_email' => 'Email',
		'admin_password' => 'Password (minimum 8 characters)',
		'demo' => 'Install demo products and categories',
		'done_h' => 'Setup complete',
		'done_p' => 'Your store is ready. Disable access to /install/ in production.',
		'go_admin' => 'Open admin panel',
		'go_shop' => 'Open store',
		'back' => '< Back',
		'next' => 'Next >',
		'install' => 'Start installation',
		'restart' => 'Start over',
		'restart_confirm' => 'Reset the setup wizard? Your entered details will be cleared.',
		'installing_h' => 'Installing',
		'installing_p' => 'Please do not close this page. Tables, settings and the admin account are being created.',
		'st_connect' => 'Connecting to the database',
		'st_schema' => 'Creating tables',
		'st_settings' => 'Writing store settings',
		'st_admin' => 'Preparing the admin account',
		'st_finish' => 'Finishing setup',
		'video' => 'Setup video',
		'video_watch' => 'Watch on YouTube',
		'lang_tr' => 'Türkçe',
		'lang_en' => 'English',
		'ok' => 'OK',
		'fail' => 'Missing',
	],
];

function t(string $key) : string
{
	global $T, $ui;
	return $T[$ui][$key] ?? $key;
}

$themeLabels = [];
foreach ($availableThemes as $themeName) {
	$label = $themeName;
	$jsonPath = dirname(__DIR__) . '/templates/' . $themeName . '/theme.json';
	if (is_file($jsonPath)) {
		$meta = json_decode((string) file_get_contents($jsonPath), true);
		if (is_array($meta) && !empty($meta['name'])) {
			$label = (string) $meta['name'] . ' (' . $themeName . ')';
		}
	}
	$themeLabels[$themeName] = $label;
}

$requirements = Installer::requirements();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string) ($_POST['action'] ?? 'next');

	foreach ($fields as $field) {
		if ($field === 'install_demo') {
			continue;
		}
		if (array_key_exists($field, $_POST)) {
			$wiz[$field] = (string) $_POST[$field];
		}
	}

	if ($step === 4) {
		$wiz['install_demo'] = !empty($_POST['install_demo']) ? '1' : '0';
	}

	$redirectStep = $step;

	if ($action === 'back') {
		$wiz['_pending_install'] = 0;
		$redirectStep = max(1, $step - 1);
	} elseif ($action === 'test_db') {
		$test = Installer::testDatabase([
			'db_host' => $wiz['db_host'],
			'db_name' => $wiz['db_name'],
			'db_user' => $wiz['db_user'],
			'db_pass' => $wiz['db_pass'],
		]);
		if ($test['success']) {
			$_SESSION['fshop_install_notice'] = $test['message'];
		} else {
			$_SESSION['fshop_install_error'] = $test['message'];
		}
		$redirectStep = 2;
	} elseif ($action === 'run_install' || $action === 'install') {
		$result = Installer::install($wiz);
		$wiz['_pending_install'] = 0;
		if ($result['success']) {
			$_SESSION['fshop_install_success'] = $result['message'];
			$_SESSION['fshop_install_result'] = $result;
			$redirectStep = 5;
		} else {
			$_SESSION['fshop_install_error'] = $result['message'];
			$redirectStep = 4;
		}
		if (install_is_ajax()) {
			install_json_exit($result);
		}
	} else {
		$ok = true;
		if ($step === 1 && !$requirements['ok']) {
			$ok = false;
			$_SESSION['fshop_install_error'] = t('req_fail');
		}
		if ($step === 2) {
			$test = Installer::testDatabase([
				'db_host' => $wiz['db_host'],
				'db_name' => $wiz['db_name'],
				'db_user' => $wiz['db_user'],
				'db_pass' => $wiz['db_pass'],
			]);
			if (!$test['success']) {
				$ok = false;
				$_SESSION['fshop_install_error'] = $test['message'];
			}
		}
		if ($step === 3) {
			$site = rtrim(trim((string) $wiz['site_url']), '/') . '/';
			if (!filter_var($site, FILTER_VALIDATE_URL)) {
				$ok = false;
				$_SESSION['fshop_install_error'] = $ui === 'tr'
					? 'Geçerli bir mağaza adresi girin (https dahil).'
					: 'Enter a valid store URL including https.';
			} else {
				$wiz['site_url'] = $site;
			}
		}
		if ($step === 4) {
			if (strlen((string) $wiz['admin_password']) < 8) {
				$ok = false;
				$_SESSION['fshop_install_error'] = $ui === 'tr'
					? 'Yönetici şifresi en az 8 karakter olmalı.'
					: 'The admin password must be at least 8 characters long.';
			}
		}
		if ($ok && $step === 4) {
			$wiz['_pending_install'] = 1;
			$redirectStep = 5;
		} else {
			$wiz['_pending_install'] = 0;
			$redirectStep = $ok ? min($maxStep, $step + 1) : $step;
		}
	}

	$wiz['_step'] = $redirectStep;
	$wiz['_max'] = max((int) ($wiz['_max'] ?? 1), min($redirectStep, 4));
	if ($redirectStep === 5 && !empty($wiz['_pending_install'])) {
		$wiz['_max'] = max((int) $wiz['_max'], 4);
	}
	header('Location: ?step=' . $redirectStep);
	exit;
}

$wiz['_step'] = $step;
if ($step < 5) {
	$wiz['_pending_install'] = 0;
}
$wiz['_max'] = max($maxReached, min($step, 4));
$isInstalling = ($step === 5 && $success === '' && $pendingInstall);
$progressPct = (int) round(($step / $maxStep) * 100);
$steps = [
	1 => t('s1'),
	2 => t('s2'),
	3 => t('s3'),
	4 => t('s4'),
	5 => $isInstalling ? t('installing_h') : t('s5'),
];
$headings = [
	1 => t('welcome_h'),
	2 => t('db_h'),
	3 => t('store_h'),
	4 => t('admin_h'),
	5 => $isInstalling ? t('installing_h') : t('done_h'),
];
?>
<!DOCTYPE html>
<html lang="<?php echo install_h($ui); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo install_h($brandTitle . ' — ' . t('title')); ?></title>
	<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
	<meta http-equiv="Pragma" content="no-cache">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
	<link rel="stylesheet" href="../templates/admin/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/install.css?v=<?php echo (int) (@filemtime(__DIR__ . '/assets/install.css') ?: time()); ?>">
</head>
<body>
<div class="wiz-shell">
<form method="post" class="wiz">
	<input type="hidden" name="step" value="<?php echo (int) $step; ?>">

	<aside class="wiz__side">
		<div class="wiz__brand">
			<img class="wiz__mark" src="../img/default.jpg" width="40" height="40" alt="<?php echo install_h($productName); ?>">
			<div>
				<strong><?php echo install_h($brandTitle); ?></strong>
				<span><?php echo install_h(t('title')); ?></span>
			</div>
		</div>
		<nav class="wiz__steps" aria-label="<?php echo install_h(t('title')); ?>">
			<?php foreach ($steps as $num => $label) {
				$class = 'wiz__step';
				if ($num < $step) {
					$class .= ' is-done';
				} elseif ($num === $step) {
					$class .= ' is-active';
				} else {
					$class .= ' is-todo';
				}
			?>
			<?php if ($num <= $maxReached && $num !== $step && ($step < 5 || $success !== '')) { ?>
			<a class="<?php echo $class; ?>" href="?step=<?php echo (int) $num; ?>">
				<span class="wiz__step-num"><?php echo $num < $step ? '✓' : (int) $num; ?></span>
				<?php echo install_h($label); ?>
			</a>
			<?php } elseif ($num < $step && $step === 5 && $isInstalling) { ?>
			<a class="<?php echo $class; ?>" href="?step=<?php echo (int) $num; ?>">
				<span class="wiz__step-num">✓</span>
				<?php echo install_h($label); ?>
			</a>
			<?php } else { ?>
			<div class="<?php echo $class; ?>">
				<span class="wiz__step-num"><?php echo $num < $step ? '✓' : (int) $num; ?></span>
				<?php echo install_h($label); ?>
			</div>
			<?php } ?>
			<?php } ?>
		</nav>
		<div class="wiz__side-foot">
			<div class="wiz__langs" role="group" aria-label="<?php echo install_h(t('lang_tr') . ' / ' . t('lang_en')); ?>">
				<a href="?step=<?php echo (int) $step; ?>&amp;lang=tr" class="<?php echo $ui === 'tr' ? 'is-active' : ''; ?>">TR</a>
				<a href="?step=<?php echo (int) $step; ?>&amp;lang=en" class="<?php echo $ui === 'en' ? 'is-active' : ''; ?>">EN</a>
			</div>
			<a class="wiz__restart" href="?restart=1" onclick="return confirm(<?php echo htmlspecialchars(json_encode(t('restart_confirm'), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>);"><?php echo install_h(t('restart')); ?></a>
			<a class="wiz__help" href="<?php echo install_h($installVideoUrl); ?>" target="_blank" rel="noopener noreferrer">
				<span class="wiz__help-icon" aria-hidden="true">?</span>
				<?php echo install_h(t('help')); ?>
			</a>
			<small class="wiz__ver"><?php echo install_h($productName . ' ' . $productVersion); ?></small>
		</div>
	</aside>

	<section class="wiz__main">
		<div class="wiz__rail" aria-hidden="true"><span style="width: <?php echo $progressPct; ?>%"></span></div>
		<div class="wiz__content">
			<p class="wiz__kicker"><?php echo install_h(sprintf(t('step_of'), $step, $maxStep)); ?></p>
			<h1><?php echo install_h($headings[$step]); ?></h1>

			<?php if ($error !== '') { ?>
			<div class="alert alert-danger"><?php echo install_h($error); ?></div>
			<?php } ?>
			<?php if ($notice !== '') { ?>
			<div class="alert alert-success"><?php echo install_h($notice); ?></div>
			<?php } ?>

				<?php if ($step === 1) { ?>
				<p class="wiz__lead"><?php echo install_h(t('welcome_p')); ?></p>
				<a class="wiz-video-card" href="<?php echo install_h($installVideoUrl); ?>" target="_blank" rel="noopener noreferrer">
					<span class="wiz-play" aria-hidden="true"></span>
					<span>
						<strong><?php echo install_h(t('video')); ?></strong>
						<small><?php echo install_h(t('video_watch')); ?></small>
					</span>
				</a>
				<h2><?php echo install_h(t('req_h')); ?></h2>
				<ul class="wiz-checks">
					<?php foreach ($requirements['items'] as $item) { ?>
					<li class="<?php echo !empty($item['ok']) ? 'is-ok' : 'is-fail'; ?>">
						<span class="wiz-checks__mark"><?php echo !empty($item['ok']) ? '✓' : '✕'; ?></span>
						<div>
							<strong><?php echo install_h((string) $item['label']); ?></strong>
							<small><?php echo install_h((string) ($item['hint'] ?? '')); ?></small>
						</div>
					</li>
					<?php } ?>
				</ul>
				<?php if (!$requirements['ok']) { ?>
				<p class="text-danger mb-0"><?php echo install_h(t('req_fail')); ?></p>
				<?php } ?>
				<?php } ?>

				<?php if ($step === 2) { ?>
				<p class="wiz__lead"><?php echo install_h(t('db_p')); ?></p>
				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('db_host')); ?></label>
						<input type="text" name="db_host" class="form-control" value="<?php echo install_h($wiz['db_host']); ?>" required>
					</div>
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('db_name')); ?></label>
						<input type="text" name="db_name" class="form-control" value="<?php echo install_h($wiz['db_name']); ?>" required>
					</div>
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('db_user')); ?></label>
						<input type="text" name="db_user" class="form-control" value="<?php echo install_h($wiz['db_user']); ?>" required>
					</div>
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('db_pass')); ?></label>
						<input type="password" name="db_pass" class="form-control" value="<?php echo install_h($wiz['db_pass']); ?>">
					</div>
				</div>
				<?php } ?>

				<?php if ($step === 3) { ?>
				<p class="wiz__lead"><?php echo install_h(t('store_p')); ?></p>
				<div class="row g-3">
					<div class="col-12">
						<label class="form-label"><?php echo install_h(t('site_name')); ?></label>
						<input type="text" name="site_name" class="form-control" value="<?php echo install_h($wiz['site_name']); ?>" required>
					</div>
					<div class="col-12">
						<label class="form-label"><?php echo install_h(t('site_url')); ?></label>
						<input type="url" name="site_url" class="form-control" value="<?php echo install_h($wiz['site_url']); ?>" required>
					</div>
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('rewrite_base')); ?></label>
						<input type="text" name="rewrite_base" class="form-control" value="<?php echo install_h($wiz['rewrite_base']); ?>" required>
					</div>
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('theme')); ?></label>
						<select name="theme" class="form-select">
							<?php foreach ($themeLabels as $themeName => $label) { ?>
							<option value="<?php echo install_h($themeName); ?>"<?php echo $wiz['theme'] === $themeName ? ' selected' : ''; ?>><?php echo install_h($label); ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('shop_lang')); ?></label>
						<select name="shop_lang" class="form-select">
							<option value="tr"<?php echo $wiz['shop_lang'] === 'tr' ? ' selected' : ''; ?>>Türkçe</option>
							<option value="en"<?php echo $wiz['shop_lang'] === 'en' ? ' selected' : ''; ?>>English</option>
						</select>
					</div>
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('admin_lang')); ?></label>
						<select name="admin_lang" class="form-select">
							<option value="tr"<?php echo $wiz['admin_lang'] === 'tr' ? ' selected' : ''; ?>>Türkçe</option>
							<option value="en"<?php echo $wiz['admin_lang'] === 'en' ? ' selected' : ''; ?>>English</option>
						</select>
					</div>
				</div>
				<?php } ?>

				<?php if ($step === 4) { ?>
				<p class="wiz__lead"><?php echo install_h(t('admin_p')); ?></p>
				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('admin_name')); ?></label>
						<input type="text" name="admin_name" class="form-control" value="<?php echo install_h($wiz['admin_name']); ?>" required>
					</div>
					<div class="col-md-6">
						<label class="form-label"><?php echo install_h(t('admin_email')); ?></label>
						<input type="email" name="admin_email" class="form-control" value="<?php echo install_h($wiz['admin_email']); ?>" required>
					</div>
					<div class="col-12">
						<label class="form-label"><?php echo install_h(t('admin_password')); ?></label>
						<input type="password" name="admin_password" class="form-control" value="<?php echo install_h($wiz['admin_password']); ?>" required minlength="8">
					</div>
					<div class="col-12">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="install_demo" id="installDemo" value="1"<?php echo $wiz['install_demo'] === '1' ? ' checked' : ''; ?>>
							<label class="form-check-label" for="installDemo"><?php echo install_h(t('demo')); ?></label>
						</div>
					</div>
				</div>
				<?php } ?>

				<?php if ($step === 5 && $isInstalling) { ?>
				<p class="wiz__lead"><?php echo install_h(t('installing_p')); ?></p>
				<div class="progress wiz-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="8">
					<div class="progress-bar progress-bar-striped progress-bar-animated" id="installBar" style="width: 8%"></div>
				</div>
				<p class="wiz-progress__label"><span id="installStatus"><?php echo install_h(t('st_connect')); ?></span> <span id="installPct">8%</span></p>
				<ul class="wiz-stages" id="installStages">
					<li data-pct="12"><?php echo install_h(t('st_connect')); ?></li>
					<li data-pct="38"><?php echo install_h(t('st_schema')); ?></li>
					<li data-pct="62"><?php echo install_h(t('st_settings')); ?></li>
					<li data-pct="82"><?php echo install_h(t('st_admin')); ?></li>
					<li data-pct="94"><?php echo install_h(t('st_finish')); ?></li>
				</ul>
				<noscript>
					<button type="submit" name="action" value="run_install" class="btn btn-primary mt-3"><?php echo install_h(t('install')); ?></button>
				</noscript>
				<?php } elseif ($step === 5) { ?>
				<div class="alert alert-success"><?php echo install_h($success !== '' ? $success : t('done_h')); ?></div>
				<p class="wiz__lead"><?php echo install_h(t('done_p')); ?></p>
				<?php if (!empty($resultExtra['admin_email'])) { ?>
				<p class="mb-2"><strong><?php echo install_h(t('admin_email')); ?>:</strong> <?php echo install_h((string) $resultExtra['admin_email']); ?></p>
				<?php } ?>
				<div class="d-flex flex-wrap gap-2 mt-3">
					<a class="btn btn-primary" href="../admin/"><?php echo install_h(t('go_admin')); ?></a>
					<a class="btn btn-outline-secondary" href="../"><?php echo install_h(t('go_shop')); ?></a>
				</div>
				<?php } ?>
		</div>

		<?php if ($step < 5) { ?>
		<footer class="wiz__foot">
			<?php if ($step > 1) { ?>
			<button type="submit" name="action" value="back" class="btn btn-outline-secondary" formnovalidate><?php echo install_h(t('back')); ?></button>
			<?php } ?>
			<?php if ($step === 2) { ?>
			<button type="submit" name="action" value="test_db" class="btn btn-outline-primary"><?php echo install_h(t('db_test')); ?></button>
			<?php } ?>
			<?php if ($step === 1) { ?>
			<button type="submit" name="action" value="next" class="btn btn-primary"<?php echo $requirements['ok'] ? '' : ' disabled'; ?>><?php echo install_h(t('next')); ?></button>
			<?php } elseif ($step === 4) { ?>
			<button type="submit" name="action" value="next" class="btn btn-primary"><?php echo install_h(t('install')); ?></button>
			<?php } else { ?>
			<button type="submit" name="action" value="next" class="btn btn-primary"><?php echo install_h(t('next')); ?></button>
			<?php } ?>
		</footer>
		<?php } ?>
	</section>
</form>
</div>
<?php if (!empty($isInstalling)) { ?>
<script>
(function () {
	var bar = document.getElementById('installBar');
	var statusEl = document.getElementById('installStatus');
	var items = document.querySelectorAll('#installStages li');
	var form = document.querySelector('form.wiz');
	if (!bar) {
		return;
	}
	var index = 0;
	var finished = false;
	var pctEl = document.getElementById('installPct');
	function paint(i, pct) {
		bar.style.width = pct + '%';
		bar.parentNode.setAttribute('aria-valuenow', String(pct));
		if (pctEl) {
			pctEl.textContent = pct + '%';
		}
		items.forEach(function (el, idx) {
			el.classList.toggle('is-done', idx < i);
			el.classList.toggle('is-active', idx === i);
		});
		if (items[i]) {
			statusEl.textContent = items[i].textContent;
		}
	}
	function fallbackSubmit() {
		if (finished || !form) {
			return;
		}
		finished = true;
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'action';
		input.value = 'run_install';
		form.appendChild(input);
		form.setAttribute('action', 'index.php?step=5');
		form.submit();
	}
	paint(0, 12);
	var timer = setInterval(function () {
		if (index < items.length - 2) {
			index += 1;
			paint(index, parseInt(items[index].getAttribute('data-pct'), 10) || 80);
		}
	}, 900);
	fetch('index.php?step=5', {
		method: 'POST',
		credentials: 'same-origin',
		cache: 'no-store',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
			'Accept': 'application/json',
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
		},
		body: new URLSearchParams({ action: 'run_install', step: '5' })
	}).then(function (res) {
		return res.text().then(function (text) {
			var data = null;
			try {
				data = JSON.parse(text);
			} catch (e) {}
			return { ok: res.ok, data: data };
		});
	}).then(function (result) {
		clearInterval(timer);
		if (result.data && result.data.success) {
			finished = true;
			paint(items.length - 1, 100);
			statusEl.textContent = <?php echo json_encode(t('done_h'), JSON_UNESCAPED_UNICODE); ?>;
			window.setTimeout(function () {
				window.location.replace('index.php?step=5&_=' + Date.now());
			}, 400);
			return;
		}
		if (result.data && result.data.message) {
			finished = true;
			window.location.replace('index.php?step=4&_=' + Date.now());
			return;
		}
		fallbackSubmit();
	}).catch(function () {
		clearInterval(timer);
		fallbackSubmit();
	});
})();
</script>
<?php } ?>
</body>
</html>
