<?php

class Installer
{
	public static function rootPath(): string
	{
		return dirname(__DIR__);
	}

	public static function lockPath(): string
	{
		return self::rootPath() . '/config/installed.lock';
	}

	public static function isInstalled(): bool
	{
		return is_file(self::rootPath() . '/config/env.php');
	}

	public static function requirements(): array
	{
		$checks = [
			[
				'label' => 'PHP 7.4+',
				'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
				'hint' => 'You: ' . PHP_VERSION,
			],
			[
				'label' => 'PDO MySQL',
				'ok' => extension_loaded('pdo_mysql'),
				'hint' => 'The pdo_mysql extension is required.',
			],
			[
				'label' => 'mbstring',
				'ok' => extension_loaded('mbstring'),
				'hint' => 'The mbstring extension is required.',
			],
			[
				'label' => 'GD',
				'ok' => extension_loaded('gd'),
				'hint' => 'GD is required for product image processing.',
			],
			[
				'label' => 'config/ writable',
				'ok' => is_writable(self::rootPath() . '/config'),
				'hint' => 'env.php and the installation lock must be created',
			],
			[
				'label' => 'cache/ writable',
				'ok' => is_writable(self::rootPath() . '/cache'),
				'hint' => 'Required for Smarty cache',
			],
			[
				'label' => 'img/products/ writable',
				'ok' => is_dir(self::rootPath() . '/img/products') && is_writable(self::rootPath() . '/img/products'),
				'hint' => 'Product images are required',
			],
		];

		$ok = true;

		foreach ($checks as $check) {
			if (!$check['ok']) {
				$ok = false;
				break;
			}
		}

		return ['ok' => $ok, 'items' => $checks];
	}

	public static function testDatabase(array $config): array
	{
		try {
			$pdo = self::pdo($config, false);
			$pdo->query('SELECT 1');
			$name = trim((string) ($config['db_name'] ?? ''));

			if ($name !== '' && self::databaseExists($pdo, $name)) {
				return ['success' => true, 'message' => 'Database connection successful'];
			}

			return ['success' => true, 'message' => 'Database connection successful. The database will be created during setup.'];
		} catch (Throwable $e) {
			return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
		}
	}

	public static function install(array $data): array
	{
		if (self::isInstalled()) {
			return ['success' => false, 'message' => 'The system is already installed'];
		}

		$dbHost = trim((string) ($data['db_host'] ?? 'localhost'));
		$dbName = trim((string) ($data['db_name'] ?? ''));
		$dbUser = trim((string) ($data['db_user'] ?? ''));
		$dbPass = (string) ($data['db_pass'] ?? '');
		$siteName = trim((string) ($data['site_name'] ?? 'FriSay'));
		$siteUrl = rtrim(trim((string) ($data['site_url'] ?? '')), '/') . '/';
		$rewriteBase = trim((string) ($data['rewrite_base'] ?? '/'));
		$adminName = trim((string) ($data['admin_name'] ?? 'Admin'));
		$adminEmail = trim((string) ($data['admin_email'] ?? ''));
		$adminPass = (string) ($data['admin_password'] ?? '');
		$withDemo = !empty($data['install_demo']);
		$shopLang = strtolower(trim((string) ($data['shop_lang'] ?? 'en')));
		$adminLang = strtolower(trim((string) ($data['admin_lang'] ?? 'en')));
		$theme = self::resolveThemeName((string) ($data['theme'] ?? 'default'));

		if (!in_array($shopLang, ['tr', 'en'], true)) {
			$shopLang = 'en';
		}

		if (!in_array($adminLang, ['tr', 'en'], true)) {
			$adminLang = 'en';
		}

		if ($dbName === '' || $dbUser === '') {
			return ['success' => false, 'message' => 'Database name and user are required.'];
		}

		if ($siteUrl === '/' || !filter_var($siteUrl, FILTER_VALIDATE_URL)) {
			return ['success' => false, 'message' => 'Enter a valid site address (including https)'];
		}

		if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
			return ['success' => false, 'message' => 'Enter a valid admin email address.'];
		}

		if (strlen($adminPass) < 8) {
			return ['success' => false, 'message' => 'The admin password must be at least 8 characters long.'];
		}

		if ($rewriteBase === '') {
			$rewriteBase = '/';
		}

		if ($rewriteBase[0] !== '/') {
			$rewriteBase = '/' . $rewriteBase;
		}

		if ($rewriteBase !== '/' && substr($rewriteBase, -1) !== '/') {
			$rewriteBase .= '/';
		}

		$dbConfig = [
			'db_host' => $dbHost,
			'db_name' => $dbName,
			'db_user' => $dbUser,
			'db_pass' => $dbPass,
		];

		$test = self::testDatabase($dbConfig);

		if (!$test['success']) {
			return $test;
		}

		try {
			@set_time_limit(180);
			ignore_user_abort(true);
			$pdo = self::connectAndEnsureDatabase($dbConfig);
			self::dropAllTables($pdo);
			self::runSqlFile($pdo, self::rootPath() . '/install/schema.sql');
			self::runSqlFileIfExists($pdo, self::rootPath() . '/install/patch_taxes.sql');

			if ($withDemo) {
				try {
					self::runSqlFile($pdo, self::rootPath() . '/install/seed_demo.sql');
				} catch (Throwable $demoEx) {
					// Demo is optional; leftover PK=1 rows on retry must not abort install.
				}
			}

			try {
				self::bootstrapStorefront($pdo, $withDemo);
			} catch (Throwable $bootEx) {
				// Starter modules / homepage layout must not abort install.
			}

			$shopToken = bin2hex(random_bytes(16));
			$webApiKey = bin2hex(random_bytes(32));
			$folder = $rewriteBase;

			self::setSetting($pdo, 'DOMAIN', $siteUrl);
			self::setSetting($pdo, 'FOLDER', $folder);
			self::setSetting($pdo, 'SITE_NAME', $siteName);
			self::setSetting($pdo, 'SHOP_TOKEN', $shopToken);
			self::setSetting($pdo, 'WEBAPI_ENABLED', '1');
			self::setSetting($pdo, 'WEBAPI_KEY', $webApiKey);
			self::setSetting($pdo, 'THEME', $theme);
			self::setSetting($pdo, 'DEFAULT_LANG', $shopLang);
			self::setSetting($pdo, 'SHOP_LANGUAGES', 'tr,en');
			self::setSetting($pdo, 'ADMIN_DEFAULT_LANG', $adminLang);
			self::setSetting($pdo, 'PRODUCT_LIMIT', '5000');
			self::setSetting($pdo, 'FREE_SHIPPING_MIN', '500');
			self::setSetting($pdo, 'SHIPPING_FEE', '79.90');
			self::setSetting($pdo, 'HAVALE', '3');
			self::setSetting($pdo, 'CARGO_DAY', '3');
			self::setSetting($pdo, 'CONTACT_EMAIL', $adminEmail);
			self::setSetting($pdo, 'CONTACT_PHONE', '0555 000 00 00');
			self::setSetting($pdo, 'CONTACT_PHONE_TEL', '+905550000000');
			self::setSetting($pdo, 'MAIL_DRIVER', 'php');

			$adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
			$stmt = $pdo->prepare('UPDATE admins SET full_name = ?, email = ?, password = ?, active = 1 WHERE id_admin = 1');
			$stmt->execute([$adminName, $adminEmail, $adminHash]);

			if ($stmt->rowCount() === 0) {
				$insert = $pdo->prepare('INSERT INTO admins (full_name, email, password, active) VALUES (?, ?, ?, 1)');
				$insert->execute([$adminName, $adminEmail, $adminHash]);
			}

			self::writeEnv([
				'APP_ENV' => 'production',
				'APP_DEBUG' => false,
				'DB_HOST' => $dbHost,
				'DB_NAME' => $dbName,
				'DB_USER' => $dbUser,
				'DB_PASS' => $dbPass,
				'REWRITE_BASE' => $rewriteBase,
				'ADMIN_URI' => 'admin',
			]);

			try {
				self::updateRewriteBase($rewriteBase);

				if (is_file(self::rootPath() . '/core/Admin.php')) {
					require_once self::rootPath() . '/config/app.php';
					require_once self::rootPath() . '/core/Admin.php';

					if (class_exists('App', false)) {
						App::boot();
					}

					Admin::syncHtaccessRewrite();
				}
			} catch (Throwable $e) {
				// env.php is already written; htaccess sync must not abort install.
			}

			file_put_contents(self::lockPath(), date('c') . PHP_EOL);
			self::clearCompileCache();

			if ($withDemo) {
				self::refreshDemoCurrencyPrices($dbConfig);
			}

			return [
				'success' => true,
				'message' => 'Installation complete.',
				'admin_email' => $adminEmail,
				'shop_token' => $shopToken,
				'cron_url' => rtrim($siteUrl, '/') . str_replace('//', '/', $folder . 'api/cron.php?action=currency&token=' . $shopToken),
			];
		} catch (Throwable $e) {
			return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
		}
	}

	private static function pdo(array $config, bool $useDatabase): PDO
	{
		$host = $config['db_host'] ?? 'localhost';
		$name = $config['db_name'] ?? '';
		$user = $config['db_user'] ?? '';
		$pass = $config['db_pass'] ?? '';
		$dsn = $useDatabase
			? 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4'
			: 'mysql:host=' . $host . ';charset=utf8mb4';

		$pdo = new PDO($dsn, $user, $pass, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		]);

		$pdo->exec("SET NAMES utf8mb4");

		return $pdo;
	}

	private static function dropAllTables(PDO $pdo): void
	{
		$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
		$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
		foreach ($tables as $table) {
			$name = str_replace('`', '``', (string) $table);
			$pdo->exec('DROP TABLE IF EXISTS `' . $name . '`');
		}
		$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
	}

	private static function runSqlFile(PDO $pdo, string $path): void
	{
		if (!is_file($path)) {
			throw new RuntimeException('SQL file not found: ' . $path);
		}

		self::executeSqlStatements($pdo, (string) file_get_contents($path));
	}

	private static function runSqlFileIfExists(PDO $pdo, string $path): void
	{
		if (!is_file($path)) {
			return;
		}

		self::executeSqlStatements($pdo, (string) file_get_contents($path));
	}

	private static function executeSqlStatements(PDO $pdo, string $sql): void
	{
		$sql = preg_replace('/^\s*--.*$/m', '', $sql);
		$statements = preg_split('/;\s*[\r\n]+/', $sql);

		foreach ($statements as $statement) {
			$statement = trim($statement);

			if ($statement === '') {
				continue;
			}

			$pdo->exec($statement);
		}
	}

	private static function setSetting(PDO $pdo, string $title, string $value): void
	{
		$stmt = $pdo->prepare('SELECT id FROM settings WHERE title = ? LIMIT 1');
		$stmt->execute([$title]);
		$row = $stmt->fetch();

		if ($row) {
			$update = $pdo->prepare('UPDATE settings SET value = ? WHERE title = ?');
			$update->execute([$value, $title]);

			return;
		}

		$insert = $pdo->prepare('INSERT INTO settings (title, value) VALUES (?, ?)');
		$insert->execute([$title, $value]);
	}

	private static function writeEnv(array $env): void
	{
		$path = self::rootPath() . '/config/env.php';
		$export = var_export($env, true);
		$content = "<?php\nreturn " . $export . ";\n";

		if (file_put_contents($path, $content) === false) {
			throw new RuntimeException('config/env.php could not be written');
		}

		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($path, true);
		}
	}

	private static function databaseExists(PDO $pdo, string $name): bool
	{
		$stmt = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1');
		$stmt->execute([$name]);

		return (bool) $stmt->fetchColumn();
	}

	private static function connectAndEnsureDatabase(array $config): PDO
	{
		$name = trim((string) ($config['db_name'] ?? ''));

		if ($name === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
			throw new RuntimeException('Invalid database name.');
		}

		$pdo = self::pdo($config, false);
		$safe = str_replace('`', '``', $name);
		$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $safe . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
		$pdo->exec('USE `' . $safe . '`');

		return $pdo;
	}

	private static function clearCompileCache(): void
	{
		foreach ([self::rootPath() . '/cache/force', self::rootPath() . '/cache/cache'] as $dir) {
			if (is_dir($dir)) {
				self::clearCacheDir($dir);
			}
		}
	}

	private static function clearCacheDir(string $dir): void
	{
		foreach (scandir($dir) ?: [] as $entry) {
			if ($entry === '.' || $entry === '..' || $entry === 'index.php' || $entry === '.htaccess') {
				continue;
			}

			$path = $dir . '/' . $entry;

			if (is_file($path)) {
				@unlink($path);
			} elseif (is_dir($path)) {
				self::clearCacheDir($path);
				@rmdir($path);
			}
		}
	}

	private static function updateRewriteBase(string $rewriteBase): void
	{
		$htaccess = self::rootPath() . '/.htaccess';

		if (!is_file($htaccess) || !is_writable($htaccess)) {
			return;
		}

		$content = file_get_contents($htaccess);
		$content = preg_replace(
			'/RewriteBase\s+.+$/m',
			'RewriteBase ' . $rewriteBase,
			$content,
			1,
			$count
		);

		if ($count === 0) {
			return;
		}

		file_put_contents($htaccess, $content);
	}

	/** @return list<string> */
	public static function availableThemes(): array
	{
		$dir = self::rootPath() . '/templates';
		$themes = [];

		foreach (scandir($dir) ?: [] as $entry) {
			if ($entry === '.' || $entry === '..' || $entry === 'admin') {
				continue;
			}

			$path = $dir . '/' . $entry;

			if (is_dir($path) && is_file($path . '/header.tpl')) {
				$themes[] = $entry;
			}
		}

		sort($themes);

		return $themes;
	}

	public static function resolveThemeName(string $theme): string
	{
		$theme = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($theme))) ?: 'theme4';
		$available = self::availableThemes();

		if (in_array($theme, $available, true)) {
			return $theme;
		}

		if (in_array('theme4', $available, true)) {
			return 'theme4';
		}

		if (in_array('default', $available, true)) {
			return 'default';
		}

		return $available[0] ?? 'default';
	}

	public static function ensureStorefront(?PDO $pdo = null): void
	{
		if ($pdo === null) {
			$envFile = self::rootPath() . '/config/env.php';

			if (!is_file($envFile)) {
				throw new RuntimeException('Shop is not installed.');
			}

			$env = require $envFile;
			$pdo = self::pdo([
				'db_host' => (string) ($env['DB_HOST'] ?? 'localhost'),
				'db_name' => (string) ($env['DB_NAME'] ?? ''),
				'db_user' => (string) ($env['DB_USER'] ?? ''),
				'db_pass' => (string) ($env['DB_PASS'] ?? ''),
			], true);
		}

		self::bootstrapStorefront($pdo, false);
	}

	private static function bootstrapStorefront(PDO $pdo, bool $withDemo = false): void
	{
		$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

		if ($productCount === 0 && $withDemo) {
			try {
				self::runSqlFile($pdo, self::rootPath() . '/install/seed_demo.sql');
			} catch (Throwable $e) {
				// Catalog seed is best-effort; module bootstrap still runs.
			}
		}

		self::installStarterModules($pdo);
		self::seedStarterMedia($pdo);

		if ($withDemo) {
			self::applyDemoThemePreset($pdo);
		} else {
			self::seedTheme4HomeLayout($pdo);
		}

		try {
			$pdo->exec('UPDATE categories SET show_on_home = 1, home_position = id_category WHERE id_category IN (2, 3, 4)');
		} catch (Throwable $e) {
			// ignore
		}
	}

	private static function installStarterModules(PDO $pdo): void
	{
		$starters = [
			'slider' => ['1.0.0', ['home_slider', 'home_promo_slider']],
			'main-menu' => ['1.3.0', ['main_menu', 'footer_menu']],
			'theme4' => ['2.6.0', ['home']],
		];

		foreach ($starters as $name => $meta) {
			$version = $meta[0];
			$hooks = $meta[1];
			$sqlFile = self::rootPath() . '/modules/' . $name . '/install.sql';

			if (is_file($sqlFile)) {
				self::runSqlFileIfExists($pdo, $sqlFile);
			}

			$exists = $pdo->prepare('SELECT id_module FROM modules WHERE name = ? LIMIT 1');
			$exists->execute([$name]);

			if (!$exists->fetch()) {
				$insert = $pdo->prepare('INSERT INTO modules (name, version, active, installed) VALUES (?, ?, 1, 1)');
				$insert->execute([$name, $version]);
			} else {
				$update = $pdo->prepare('UPDATE modules SET active = 1, installed = 1, version = ? WHERE name = ?');
				$update->execute([$version, $name]);
			}

			$position = 0;

			foreach ($hooks as $hook) {
				$hookStmt = $pdo->prepare(
					'INSERT IGNORE INTO module_display_hooks (module_name, hook_name, position) VALUES (?, ?, ?)'
				);
				$hookStmt->execute([$name, $hook, $position]);
				$position++;
			}
		}
	}

	private static function applyDemoThemePreset(PDO $pdo): void
	{
		$presetDir = self::rootPath() . '/install/presets';
		$jsonPath = $presetDir . '/theme4-demo.json';

		if (!is_file($jsonPath)) {
			self::seedTheme4HomeLayout($pdo);

			return;
		}

		$decoded = json_decode((string) file_get_contents($jsonPath), true);

		if (!is_array($decoded)) {
			self::seedTheme4HomeLayout($pdo);

			return;
		}

		try {
			$pdo->exec(
				"CREATE TABLE IF NOT EXISTS `theme4_settings` (
				  `id` INT(11) NOT NULL AUTO_INCREMENT,
				  `title` varchar(255) NOT NULL,
				  `value` TEXT NOT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} catch (Throwable $e) {
			return;
		}

		$settings = is_array($decoded['settings'] ?? null) ? $decoded['settings'] : [];

		foreach ($settings as $title => $value) {
			self::upsertTheme4Setting($pdo, (string) $title, (string) $value);
		}

		$copies = [
			$presetDir . '/theme4-demo-colors.css' => self::rootPath() . '/templates/theme4/css/colors.css',
			$presetDir . '/theme4-demo-custom.css' => self::rootPath() . '/templates/theme4/css/custom.css',
			$presetDir . '/theme4-demo-theme-settings.css' => self::rootPath() . '/modules/theme4/assets/css/theme-settings.css',
			$presetDir . '/theme4-demo-custom.js' => self::rootPath() . '/templates/theme4/js/custom.js',
		];

		foreach ($copies as $from => $to) {
			if (!is_file($from)) {
				continue;
			}

			$dir = dirname($to);

			if (!is_dir($dir)) {
				@mkdir($dir, 0755, true);
			}

			@copy($from, $to);
		}

		$menu = is_array($decoded['menu'] ?? null) ? $decoded['menu'] : [];

		if ($menu !== []) {
			try {
				$pdo->exec('DELETE FROM main_menu_items');
				$insert = $pdo->prepare(
					'INSERT INTO main_menu_items (label, link_type, link_value, target, position, active, show_header, show_mobile, show_footer)
					 VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)'
				);

				foreach ($menu as $item) {
					if (!is_array($item)) {
						continue;
					}

					$insert->execute([
						(string) ($item['label'] ?? ''),
						(string) ($item['link_type'] ?? 'custom'),
						(string) ($item['link_value'] ?? ''),
						'_self',
						(int) ($item['position'] ?? 0),
						(int) ($item['show_header'] ?? 1),
						(int) ($item['show_mobile'] ?? 1),
						(int) ($item['show_footer'] ?? 0),
					]);
				}
			} catch (Throwable $e) {
				// menu table may not exist
			}
		}

		$slides = is_array($decoded['slider'] ?? null) ? $decoded['slider'] : [];

		if ($slides !== []) {
			try {
				$pdo->exec('DELETE FROM slider_slides');
				$insert = $pdo->prepare(
					'INSERT INTO slider_slides (slide_group, title, subtitle, button_text, link_url, image_file, position, active)
					 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
				);

				foreach ($slides as $slide) {
					if (!is_array($slide)) {
						continue;
					}

					$insert->execute([
						(string) ($slide['slide_group'] ?? 'hero'),
						(string) ($slide['title'] ?? ''),
						(string) ($slide['subtitle'] ?? ''),
						(string) ($slide['button_text'] ?? 'Keşfet'),
						(string) ($slide['link_url'] ?? ''),
						(string) ($slide['image_file'] ?? 'slide_4.jpg'),
						(int) ($slide['position'] ?? 0),
						(int) ($slide['active'] ?? 1),
					]);
				}
			} catch (Throwable $e) {
				// slider table may not exist
			}
		}
	}

	private static function upsertTheme4Setting(PDO $pdo, string $title, string $value): void
	{
		$title = trim($title);

		if ($title === '') {
			return;
		}

		$exists = $pdo->prepare('SELECT id FROM theme4_settings WHERE title = ? LIMIT 1');
		$exists->execute([$title]);

		if ($exists->fetch()) {
			$update = $pdo->prepare('UPDATE theme4_settings SET value = ? WHERE title = ?');
			$update->execute([$value, $title]);

			return;
		}

		$insert = $pdo->prepare('INSERT INTO theme4_settings (title, value) VALUES (?, ?)');
		$insert->execute([$title, $value]);
	}

	private static function seedStarterMedia(PDO $pdo): void
	{
		$defaultImg = self::rootPath() . '/img/default.jpg';
		$productDir = self::rootPath() . '/img/products';

		if (!is_dir($productDir)) {
			@mkdir($productDir, 0755, true);
		}

		if (is_file($defaultImg)) {
			for ($i = 1; $i <= 10; $i++) {
				$dest = $productDir . '/' . $i . '.jpg';

				if (!is_file($dest)) {
					@copy($defaultImg, $dest);
				}
			}

			$slideDir = self::rootPath() . '/modules/slider/assets/img/slides';

			if (!is_dir($slideDir)) {
				@mkdir($slideDir, 0755, true);
			}

			$slideSource = $defaultImg;
			$defaultSize = (int) filesize($defaultImg);

			foreach (scandir($slideDir) ?: [] as $file) {
				if (!preg_match('/\.(jpe?g|png|webp)$/i', $file)) {
					continue;
				}

				if (in_array($file, ['welcome.jpg', 'slide_4.jpg'], true)) {
					continue;
				}

				$path = $slideDir . '/' . $file;
				$size = (int) @filesize($path);

				if ($size > 120000 && $size !== $defaultSize) {
					$slideSource = $path;
					break;
				}
			}

			foreach (['welcome.jpg', 'slide_4.jpg'] as $slideName) {
				$slideDest = $slideDir . '/' . $slideName;
				$destSize = is_file($slideDest) ? (int) filesize($slideDest) : 0;

				if ($destSize === 0 || $destSize === $defaultSize) {
					@copy($slideSource, $slideDest);
				}
			}
		}

		try {
			$slideCount = (int) $pdo->query('SELECT COUNT(*) FROM slider_slides')->fetchColumn();

			if ($slideCount === 0) {
				$pdo->exec(
					"INSERT INTO slider_slides (slide_group, title, subtitle, button_text, link_url, image_file, position, active)
					 VALUES ('hero', 'Yeni sezon', 'Seçili ürünlerde fırsatlar', 'Keşfet', '', 'welcome.jpg', 0, 1)"
				);
			} else {
				$pdo->exec(
					"UPDATE slider_slides
					 SET title = IF(title = '', 'Yeni sezon', title),
					     subtitle = IF(subtitle = '', 'Seçili ürünlerde fırsatlar', subtitle),
					     button_text = IF(button_text = '' OR button_text = 'View', 'Keşfet', button_text)
					 WHERE slide_group = 'hero'"
				);
			}
		} catch (Throwable $e) {
			// slider table may not exist yet
		}
	}

	private static function seedTheme4HomeLayout(PDO $pdo): void
	{
		try {
			$pdo->exec(
				"CREATE TABLE IF NOT EXISTS `theme4_settings` (
				  `id` INT(11) NOT NULL AUTO_INCREMENT,
				  `title` varchar(255) NOT NULL,
				  `value` TEXT NOT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} catch (Throwable $e) {
			return;
		}

		$layout = [
			'rows' => [
				[
					'id' => 'rowslider',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [[
						'id' => 'columnslider',
						'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [[
							'id' => 'w_slider',
							'type' => 'hook',
							'settings' => ['hook' => 'home_slider'],
						]],
					]],
				],
				[
					'id' => 'rowproducts',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [[
						'id' => 'columnproducts',
						'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [[
							'id' => 'w_products',
							'type' => 'category_products',
							'settings' => [
								'id_category' => 0,
								'limit' => 12,
								'title' => 'Öne çıkan ürünler',
								'show_link' => 0,
								'layout' => 'grid',
							],
						]],
					]],
				],
				[
					'id' => 'rowelectronics',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [[
						'id' => 'columnelectronics',
						'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [[
							'id' => 'w_electronics',
							'type' => 'category_products',
							'settings' => [
								'id_category' => 4,
								'limit' => 8,
								'title' => 'Elektronik',
								'show_link' => 1,
								'layout' => 'grid',
							],
						]],
					]],
				],
				[
					'id' => 'rowhomebottom',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [[
						'id' => 'columnhomebottom',
						'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
						'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
						'widgets' => [[
							'id' => 'w_home_bottom',
							'type' => 'hook',
							'settings' => ['hook' => 'home_bottom'],
						]],
					]],
				],
			],
		];

		$json = json_encode($layout, JSON_UNESCAPED_UNICODE);

		if (!is_string($json) || $json === '') {
			return;
		}

		$exists = $pdo->prepare('SELECT id, value FROM theme4_settings WHERE title = ? LIMIT 1');
		$exists->execute(['HOME_LAYOUT']);
		$row = $exists->fetch();

		if (!$row || trim((string) ($row['value'] ?? '')) === '' || trim((string) ($row['value'] ?? '')) === '{"rows":[]}') {
			if ($row) {
				$update = $pdo->prepare('UPDATE theme4_settings SET value = ? WHERE title = ?');
				$update->execute([$json, 'HOME_LAYOUT']);
			} else {
				$insert = $pdo->prepare('INSERT INTO theme4_settings (title, value) VALUES (?, ?)');
				$insert->execute(['HOME_LAYOUT', $json]);
			}
		}
	}

	private static function refreshDemoCurrencyPrices(array $dbConfig): void
	{
		require_once self::rootPath() . '/config/function.php';
		require_once self::rootPath() . '/config/database.php';
		require_once self::rootPath() . '/core/Product.php';

		global $db;
		$host = $dbConfig['db_host'] ?? 'localhost';
		$name = $dbConfig['db_name'] ?? '';
		$user = $dbConfig['db_user'] ?? '';
		$pass = $dbConfig['db_pass'] ?? '';

		try {
			$db = new PDO(
				'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
				$user,
				$pass,
				[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
			);
			Product::refreshCurrencyPrices();
		} catch (Throwable $e) {
			// Demo kurulumda kur API erişilemezse ürünler kayıt sonrası cron ile güncellenir.
		}
	}
}
