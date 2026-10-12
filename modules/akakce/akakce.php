<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/AkakceFeedService.php';

class AkakceModule extends ModuleBase
{
	public string $name = 'akakce';
	public string $title = 'Akakçe XML';
	public string $version = '1.0.0';
	public string $description = 'Akakçe fiyat karşılaştırması için ürün XML feed';
	public string $author = 'FShop';

	public array $displayHooks = [];
	public array $defaultDisplayHooks = [];

	public array $adminStylesheets = ['admin.css'];

	public array $apiActions = [
		'feed' => 'api/feed.php',
		'preview' => 'api/preview.php',
		'regenerate' => 'api/regenerate.php',
	];

	public function install(): bool
	{
		Settings::set('AKAKCE_ENABLED', '1');
		Settings::set('AKAKCE_CACHE_TTL', '360');
		Settings::set('AKAKCE_INCLUDE_OUTSTOCK', '0');
		Settings::set('AKAKCE_INCLUDE_VARIATIONS', '1');
		Settings::set('AKAKCE_EXCLUDE_CATS', '');
		Settings::set('AKAKCE_BRAND_FALLBACK', Settings::get('SITE_NAME') ?: '');
		Settings::set('AKAKCE_EXPRESS_HOUR', '16');
		Settings::set('AKAKCE_DAY_OF_DELIVERY', '0');
		Settings::set('AKAKCE_USE_PRODUCT_CARGO_DAY', '0');
		Settings::set('AKAKCE_SKU_FIELD', 'stock_code');
		Settings::set('AKAKCE_CARGO_ID', '0');
		Settings::set('AKAKCE_FEED_TOKEN', bin2hex(random_bytes(16)));
		Settings::set('AKAKCE_LAST_REGEN', '');

		return true;
	}

	public function uninstall(): bool
	{
		foreach ([
			'AKAKCE_ENABLED', 'AKAKCE_CACHE_TTL', 'AKAKCE_INCLUDE_OUTSTOCK',
			'AKAKCE_INCLUDE_VARIATIONS', 'AKAKCE_EXCLUDE_CATS', 'AKAKCE_BRAND_FALLBACK',
			'AKAKCE_EXPRESS_HOUR', 'AKAKCE_DAY_OF_DELIVERY', 'AKAKCE_USE_PRODUCT_CARGO_DAY',
			'AKAKCE_SKU_FIELD', 'AKAKCE_CARGO_ID', 'AKAKCE_FEED_TOKEN', 'AKAKCE_LAST_REGEN',
		] as $key) {
			Settings::set($key, '');
		}

		@unlink(AkakceFeedService::cachePath());

		return true;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveAkakceSettings')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$hour = (int) Tools::getValue('akakce_express_hour');
				if ($hour < 0) {
					$hour = 0;
				}
				if ($hour > 23) {
					$hour = 23;
				}

				$skuField = (string) Tools::getValue('akakce_sku_field');
				if (!in_array($skuField, ['stock_code', 'id'], true)) {
					$skuField = 'stock_code';
				}

				Settings::set('AKAKCE_ENABLED', Tools::getValue('akakce_enabled') ? '1' : '0');
				Settings::set('AKAKCE_CACHE_TTL', (string) max(10, (int) Tools::getValue('akakce_cache_ttl')));
				Settings::set('AKAKCE_INCLUDE_OUTSTOCK', Tools::getValue('akakce_include_outstock') ? '1' : '0');
				Settings::set('AKAKCE_INCLUDE_VARIATIONS', Tools::getValue('akakce_include_variations') ? '1' : '0');
				Settings::set('AKAKCE_EXCLUDE_CATS', trim((string) Tools::getValue('akakce_exclude_cats')));
				Settings::set('AKAKCE_BRAND_FALLBACK', trim(strip_tags((string) Tools::getValue('akakce_brand_fallback'))));
				Settings::set('AKAKCE_EXPRESS_HOUR', (string) $hour);
				Settings::set('AKAKCE_DAY_OF_DELIVERY', (string) max(0, (int) Tools::getValue('akakce_day_of_delivery')));
				Settings::set('AKAKCE_USE_PRODUCT_CARGO_DAY', Tools::getValue('akakce_use_product_cargo_day') ? '1' : '0');
				Settings::set('AKAKCE_SKU_FIELD', $skuField);
				Settings::set('AKAKCE_CARGO_ID', (string) max(0, (int) Tools::getValue('akakce_cargo_id')));
				@unlink(AkakceFeedService::cachePath());
				$flash = 'Ayarlar kaydedildi. Feed önbelleği temizlendi.';
			}
		}

		if (Tools::isSubmit('regenToken')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				Settings::set('AKAKCE_FEED_TOKEN', bin2hex(random_bytes(16)));
				@unlink(AkakceFeedService::cachePath());
				$flash = 'Feed token yenilendi. Akakçe panelindeki XML linkini güncellemeniz gerekir.';
			}
		}

		$cargos = [];
		if (class_exists('Cargo')) {
			foreach (Cargo::getList(true) as $cargo) {
				$cargos[] = [
					'id_cargo' => (int) $cargo['id_cargo'],
					'name' => (string) $cargo['name'],
				];
			}
		}

		$cargoId = (int) Settings::get('AKAKCE_CARGO_ID');
		$sampleShip = AkakceFeedService::shipPriceForAmount(100.0, $cargoId);
		$cacheInfo = AkakceFeedService::cacheInfo();

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'panelUrl' => AkakceFeedService::PANEL_URL,
			'akEnabled' => Settings::get('AKAKCE_ENABLED'),
			'akCacheTtl' => Settings::get('AKAKCE_CACHE_TTL') ?: '360',
			'akIncludeOutstock' => Settings::get('AKAKCE_INCLUDE_OUTSTOCK'),
			'akIncludeVariations' => Settings::get('AKAKCE_INCLUDE_VARIATIONS') !== '0',
			'akExcludeCats' => Settings::get('AKAKCE_EXCLUDE_CATS'),
			'akBrandFallback' => Settings::get('AKAKCE_BRAND_FALLBACK'),
			'akExpressHour' => (string) AkakceFeedService::expressDeliveryTime(),
			'akDayOfDelivery' => Settings::get('AKAKCE_DAY_OF_DELIVERY') !== '' ? Settings::get('AKAKCE_DAY_OF_DELIVERY') : '0',
			'akUseProductCargoDay' => Settings::get('AKAKCE_USE_PRODUCT_CARGO_DAY'),
			'akSkuField' => Settings::get('AKAKCE_SKU_FIELD') ?: 'stock_code',
			'akCargoId' => $cargoId,
			'akCargos' => $cargos,
			'akSampleShip' => number_format($sampleShip, 2, ',', '.'),
			'feedUrl' => AkakceFeedService::buildFeedUrl((string) $domain),
			'lastRegen' => $cacheInfo['last_regen'] !== '' ? $cacheInfo['last_regen'] : '—',
			'cacheExists' => $cacheInfo['exists'],
			'cacheExpired' => $cacheInfo['expired'],
			'cacheAgeMin' => (int) floor($cacheInfo['age'] / 60),
			'cacheRemainingMin' => (int) ceil($cacheInfo['remaining'] / 60),
			'cronFeedsUrl' => rtrim((string) $domain, '/') . '/api/cron.php?action=feeds&token=SHOP_TOKEN',
		]);
	}
}
