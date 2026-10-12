<?php

return new class {
	public string $version = '3.0.0';
	public string $title = '3.0.0 — admin arayüz, varsayılan tema, Tema Edit';

	public function up(): void
	{
		$core = dirname(__DIR__, 2) . '/core';

		foreach (['Admin', 'Order', 'Product', 'Category', 'Coupon', 'ApiKey'] as $class) {
			if (!class_exists($class, false) && is_file($core . '/' . $class . '.php')) {
				require_once $core . '/' . $class . '.php';
			}

			if (class_exists($class, false) && method_exists($class, 'ensureSchema')) {
				$class::ensureSchema();
			}
		}

		if (class_exists('Schema', false) && method_exists('Schema', 'remapStorefrontThemes')) {
			Schema::remapStorefrontThemes();
		}

		$this->ensureSetting('SEO_TITLE_SUFFIX', '');
		$this->ensureSetting('ORDER_GOAL_TARGET', '0');
	}

	private function ensureSetting(string $key, string $default): void
	{
		$exists = DB::getValue('SELECT id FROM settings WHERE title = ? LIMIT 1', [$key]);

		if ($exists === false) {
			Settings::set($key, $default);
		}
	}
};
