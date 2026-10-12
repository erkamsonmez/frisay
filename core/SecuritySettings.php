<?php

class SecuritySettings
{
	public const KEY_ADMIN_IDLE = 'ADMIN_IDLE_TIMEOUT';
	public const KEY_CUSTOMER_IDLE = 'CUSTOMER_IDLE_TIMEOUT';
	public const KEY_ADMIN_LOGIN_MAX = 'ADMIN_LOGIN_MAX_ATTEMPTS';
	public const KEY_ADMIN_LOGIN_WINDOW = 'ADMIN_LOGIN_LOCK_MINUTES';
	public const KEY_CUSTOMER_LOGIN_MAX = 'CUSTOMER_LOGIN_MAX_ATTEMPTS';
	public const KEY_CUSTOMER_LOGIN_WINDOW = 'CUSTOMER_LOGIN_LOCK_MINUTES';
	public const KEY_PASSWORD_MIN = 'PASSWORD_MIN_LENGTH';
	public const KEY_PASSWORD_COMPLEXITY = 'PASSWORD_REQUIRE_COMPLEXITY';
	public const KEY_FORCE_HTTPS = 'FORCE_HTTPS';
	public const KEY_HSTS = 'SECURITY_HSTS';
	public const KEY_X_FRAME = 'SECURITY_X_FRAME';

	/** @return array<string, string> */
	public static function defaults(): array
	{
		return [
			self::KEY_ADMIN_IDLE => '30',
			self::KEY_CUSTOMER_IDLE => '0',
			self::KEY_ADMIN_LOGIN_MAX => '8',
			self::KEY_ADMIN_LOGIN_WINDOW => '15',
			self::KEY_CUSTOMER_LOGIN_MAX => '8',
			self::KEY_CUSTOMER_LOGIN_WINDOW => '15',
			self::KEY_PASSWORD_MIN => '8',
			self::KEY_PASSWORD_COMPLEXITY => '1',
			self::KEY_FORCE_HTTPS => '0',
			self::KEY_HSTS => 'auto',
			self::KEY_X_FRAME => 'SAMEORIGIN',
		];
	}

	public static function ensureDefaults(): void
	{
		foreach (self::defaults() as $key => $value) {
			if (Settings::get($key) === '') {
				Settings::set($key, $value);
			}
		}
	}

	/** @return array<string, string> */
	public static function getConfig(): array
	{
		self::ensureDefaults();
		$config = [];

		foreach (self::defaults() as $key => $default) {
			$value = Settings::get($key);
			$config[$key] = $value !== '' ? $value : $default;
		}

		return $config;
	}

	/** Minutes. 0 = never expire. */
	public static function getAdminIdleMinutes(): int
	{
		return self::intSetting(self::KEY_ADMIN_IDLE, 30, 0, 1440);
	}

	public static function getAdminIdleSeconds(): int
	{
		return self::getAdminIdleMinutes() * 60;
	}

	public static function getCustomerIdleMinutes(): int
	{
		return self::intSetting(self::KEY_CUSTOMER_IDLE, 0, 0, 1440);
	}

	public static function getCustomerIdleSeconds(): int
	{
		return self::getCustomerIdleMinutes() * 60;
	}

	public static function getAdminLoginMaxAttempts(): int
	{
		return self::intSetting(self::KEY_ADMIN_LOGIN_MAX, 8, 3, 50);
	}

	public static function getAdminLoginWindowSeconds(): int
	{
		return self::intSetting(self::KEY_ADMIN_LOGIN_WINDOW, 15, 1, 1440) * 60;
	}

	public static function getCustomerLoginMaxAttempts(): int
	{
		return self::intSetting(self::KEY_CUSTOMER_LOGIN_MAX, 8, 3, 50);
	}

	public static function getCustomerLoginWindowSeconds(): int
	{
		return self::intSetting(self::KEY_CUSTOMER_LOGIN_WINDOW, 15, 1, 1440) * 60;
	}

	public static function getPasswordMinLength(): int
	{
		return self::intSetting(self::KEY_PASSWORD_MIN, 8, 6, 32);
	}

	public static function requirePasswordComplexity(): bool
	{
		$value = Settings::get(self::KEY_PASSWORD_COMPLEXITY);

		return $value === '' ? true : $value !== '0';
	}

	/** @return array{minLength:int,requireComplexity:bool,mismatch:string,tooShort:string,needComplex:string,hint:string} */
	public static function getPasswordPolicyForJs(): array
	{
		$min = self::getPasswordMinLength();
		$complex = self::requirePasswordComplexity();
		$tooShort = function_exists('translate')
			? sprintf(translate('Password must be at least %d characters'), $min)
			: sprintf('Şifre en az %d karakter olmalı', $min);
		$needComplex = function_exists('translate')
			? translate('Password must include at least one letter and one number')
			: 'Şifre en az bir harf ve bir rakam içermeli';
		$mismatch = function_exists('translate')
			? translate('Passwords do not match')
			: 'Şifreler eşleşmiyor';
		$hint = $tooShort;

		if ($complex) {
			$hint .= '. ' . $needComplex;
		}

		return [
			'minLength' => $min,
			'requireComplexity' => $complex,
			'mismatch' => $mismatch,
			'tooShort' => $tooShort,
			'needComplex' => $needComplex,
			'hint' => $hint,
		];
	}

	public static function forceHttps(): bool
	{
		return Settings::get(self::KEY_FORCE_HTTPS) === '1';
	}

	public static function xFrameOption(): string
	{
		$value = strtoupper(trim((string) Settings::get(self::KEY_X_FRAME)));

		return $value === 'DENY' ? 'DENY' : 'SAMEORIGIN';
	}

	public static function hstsEnabled(bool $https): bool
	{
		$mode = strtolower(trim((string) Settings::get(self::KEY_HSTS)));

		if ($mode === '') {
			$mode = 'auto';
		}

		if ($mode === '0' || $mode === 'off') {
			return false;
		}

		if ($mode === '1' || $mode === 'on') {
			return $https;
		}

		return $https && class_exists('App', false) && App::isProduction();
	}

	/** @param array<string, mixed> $input */
	public static function saveConfig(array $input): array
	{
		$adminIdle = self::clampIdle((int) ($input[self::KEY_ADMIN_IDLE] ?? 30));
		$customerIdle = self::clampIdle((int) ($input[self::KEY_CUSTOMER_IDLE] ?? 0));

		Settings::set(self::KEY_ADMIN_IDLE, (string) $adminIdle);
		Settings::set(self::KEY_CUSTOMER_IDLE, (string) $customerIdle);
		Settings::set(self::KEY_ADMIN_LOGIN_MAX, (string) max(3, min(50, (int) ($input[self::KEY_ADMIN_LOGIN_MAX] ?? 8))));
		Settings::set(self::KEY_ADMIN_LOGIN_WINDOW, (string) max(1, min(1440, (int) ($input[self::KEY_ADMIN_LOGIN_WINDOW] ?? 15))));
		Settings::set(self::KEY_CUSTOMER_LOGIN_MAX, (string) max(3, min(50, (int) ($input[self::KEY_CUSTOMER_LOGIN_MAX] ?? 8))));
		Settings::set(self::KEY_CUSTOMER_LOGIN_WINDOW, (string) max(1, min(1440, (int) ($input[self::KEY_CUSTOMER_LOGIN_WINDOW] ?? 15))));
		Settings::set(self::KEY_PASSWORD_MIN, (string) max(6, min(32, (int) ($input[self::KEY_PASSWORD_MIN] ?? 8))));
		Settings::set(self::KEY_PASSWORD_COMPLEXITY, !empty($input[self::KEY_PASSWORD_COMPLEXITY]) ? '1' : '0');
		Settings::set(self::KEY_FORCE_HTTPS, !empty($input[self::KEY_FORCE_HTTPS]) ? '1' : '0');

		$hsts = (string) ($input[self::KEY_HSTS] ?? 'auto');
		Settings::set(self::KEY_HSTS, in_array($hsts, ['auto', 'on', 'off'], true) ? $hsts : 'auto');

		$frame = strtoupper((string) ($input[self::KEY_X_FRAME] ?? 'SAMEORIGIN'));
		Settings::set(self::KEY_X_FRAME, $frame === 'DENY' ? 'DENY' : 'SAMEORIGIN');

		return ['success' => true, 'message' => self::t('Security settings have been saved')];
	}

	public static function regenerateShopToken(): array
	{
		$token = bin2hex(random_bytes(24));
		Settings::set('SHOP_TOKEN', $token);

		return [
			'success' => true,
			'message' => self::t('Cron token has been regenerated. Update scheduled jobs that used the old token.'),
		];
	}

	public static function enforceAdminIdleTimeout(): void
	{
		if (empty($_SESSION['id_admin'])) {
			return;
		}

		$limit = self::getAdminIdleSeconds();

		if ($limit <= 0) {
			$_SESSION['admin_last_active'] = time();

			return;
		}

		$now = time();
		$last = (int) ($_SESSION['admin_last_active'] ?? 0);

		if ($last > 0 && ($now - $last) > $limit) {
			Admin::logout();
			header('Location: ' . Admin::url('login') . '?timeout=1');
			exit;
		}

		$_SESSION['admin_last_active'] = $now;
	}

	public static function enforceCustomerIdleTimeout(): void
	{
		if (empty($_SESSION['id_user'])) {
			return;
		}

		$limit = self::getCustomerIdleSeconds();

		if ($limit <= 0) {
			$_SESSION['customer_last_active'] = time();

			return;
		}

		$now = time();
		$last = (int) ($_SESSION['customer_last_active'] ?? 0);

		if ($last > 0 && ($now - $last) > $limit) {
			Customer::logout();

			return;
		}

		$_SESSION['customer_last_active'] = $now;
	}

	public static function loginLockMessage(int $windowMinutes): string
	{
		$minutes = max(1, $windowMinutes);
		$tpl = self::t('Too many failed login attempts. Please try again in %d minutes.');

		return sprintf($tpl, $minutes);
	}

	/** @return list<array{level:string,title:string,text:string,href?:string}> */
	public static function getChecklist(): array
	{
		$items = [];
		$https = self::requestIsHttps();
		$adminUri = class_exists('Admin', false) ? Admin::uri() : 'admin';
		$debug = class_exists('App', false) && App::isDebug();
		$installDir = dirname(__DIR__) . '/install';
		$recaptchaOn = class_exists('Module', false) && Module::isEnabled('recaptcha');
		$token = (string) Settings::get('SHOP_TOKEN');
		$config = self::getConfig();

		$items[] = $https
			? self::checkItem('ok', 'HTTPS', 'The store is being served over HTTPS.')
			: self::checkItem('warn', 'HTTPS', 'The current request is HTTP. Enable HTTPS and turn on Force HTTPS on live sites.');

		$items[] = $adminUri !== 'admin'
			? self::checkItem('ok', 'Admin URL', self::t('Admin path is customized') . ': /' . $adminUri, '', false)
			: self::checkItem('warn', 'Admin URL', 'Admin path is still /admin. Change ADMIN_URI in config/env.php on a live store.');

		$items[] = !$debug
			? self::checkItem('ok', 'Debug mode', 'Error display is off.')
			: self::checkItem('warn', 'Debug mode', 'Debug is on. Turn it off from Performance on a live store.', Admin::url('performance'));

		$items[] = !is_dir($installDir)
			? self::checkItem('ok', 'Installer', 'The install folder is not present.')
			: self::checkItem('warn', 'Installer', 'The /install/ folder still exists. Remove or block it after installation.');

		$items[] = $recaptchaOn
			? self::checkItem('ok', 'reCAPTCHA', 'The reCAPTCHA module is enabled.', Admin::url('module-recaptcha'))
			: self::checkItem('info', 'reCAPTCHA', 'Optional bot protection for login and contact forms.', Admin::url('modules'));

		$items[] = $token !== ''
			? self::checkItem('ok', 'Cron token', 'SHOP_TOKEN is set. Cron and marketplace jobs use it.')
			: self::checkItem('warn', 'Cron token', 'SHOP_TOKEN is empty. Cron URLs will not be protected.');

		$adminIdle = (int) $config[self::KEY_ADMIN_IDLE];
		$items[] = $adminIdle > 0
			? self::checkItem('ok', 'Admin session', sprintf(self::t('Idle logout is %d minutes.'), $adminIdle), '', false)
			: self::checkItem('info', 'Admin session', 'Idle logout is disabled (0). The panel stays signed in until the browser is closed.');

		$items[] = $config[self::KEY_PASSWORD_COMPLEXITY] !== '0'
			? self::checkItem('ok', 'Passwords', 'Customer passwords require letters and numbers.')
			: self::checkItem('info', 'Passwords', 'Password complexity is off. Only length is enforced.');

		return $items;
	}

	public static function maskedShopToken(): string
	{
		$token = (string) Settings::get('SHOP_TOKEN');

		if ($token === '') {
			return '—';
		}

		if (strlen($token) <= 8) {
			return str_repeat('•', strlen($token));
		}

		return substr($token, 0, 4) . str_repeat('•', 12) . substr($token, -4);
	}

	private static function intSetting(string $key, int $default, int $min, int $max): int
	{
		$value = Settings::get($key);

		if ($value === '') {
			return $default;
		}

		$n = (int) $value;

		return max($min, min($max, $n));
	}

	private static function clampIdle(int $minutes): int
	{
		$allowed = [0, 15, 30, 60, 120, 240];

		if (in_array($minutes, $allowed, true)) {
			return $minutes;
		}

		return max(0, min(1440, $minutes));
	}

	private static function requestIsHttps(): bool
	{
		return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
			|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
	}

	/** @return array{level:string,title:string,text:string,href:string} */
	private static function checkItem(string $level, string $title, string $text, string $href = '', bool $translateText = true): array
	{
		return [
			'level' => $level,
			'title' => self::t($title),
			'text' => $translateText ? self::t($text) : $text,
			'href' => $href,
		];
	}

	private static function t(string $message): string
	{
		if (defined('IN_ADMIN') && function_exists('adminT')) {
			return adminT($message);
		}

		return function_exists('translate') ? translate($message) : $message;
	}
}
