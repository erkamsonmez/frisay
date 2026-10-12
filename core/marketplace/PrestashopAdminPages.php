<?php

namespace Prestashop;

class PrestashopAdminPages
{
	public static function handlePosts(string $adminToken): string
	{
		$flash = '';

		if (\Tools::isSubmit('savePrestashop')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$url = rtrim(trim((string) \Tools::getValue('ps_url')), '/');
				$key = trim((string) \Tools::getValue('ps_api_key'));
				\Settings::set('PRESTASHOP_URL', $url);
				\Settings::set('PRESTASHOP_API_KEY', $key);

				$api = new PrestashopApi($key, $url, 1);

				if ($api->isConfigured()) {
					$defaults = $api->discoverDefaults();

					if ($defaults['lang_id'] > 0) {
						\Settings::set('PRESTASHOP_LANG_ID', (string) $defaults['lang_id']);
					}

					if ($defaults['category_id'] > 0) {
						\Settings::set('PRESTASHOP_DEFAULT_CATEGORY', (string) $defaults['category_id']);
					}

					if ($defaults['tax_group'] > 0) {
						\Settings::set('PRESTASHOP_TAX_GROUP', (string) $defaults['tax_group']);
					}

					if ($defaults['shop_id'] > 0) {
						\Settings::set('PRESTASHOP_SHOP_ID', (string) $defaults['shop_id']);
					}
				}

				$flash = 'PrestaShop API ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('syncPrestashopOrders')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$start = trim((string) \Tools::getValue('start_date'));
				$end = trim((string) \Tools::getValue('end_date'));
				$result = OrderService::syncOrders($start !== '' ? $start : null, $end !== '' ? $end : null);
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('testPrestashopConnection')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = ProductSyncService::testConnection();
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('pullPrestashopProducts')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$limit = (int) \Tools::getValue('ps_limit', 10);
				$result = ProductSyncService::pullBatch(0, $limit, self::pullFiltersFromRequest());
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('sendPrestashopProducts')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = ProductSyncService::sendBatch(10);
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		return $flash;
	}

	/** @return array<string, mixed> */
	public static function pullFiltersFromRequest(): array
	{
		return [
			'active_only' => \Tools::getValue('ps_active_only', '0') === '1',
			'category_id' => (int) \Tools::getValue('ps_category_id', 0),
			'manufacturer_id' => (int) \Tools::getValue('ps_manufacturer_id', 0),
			'reference' => trim((string) \Tools::getValue('ps_reference', '')),
			'name' => trim((string) \Tools::getValue('ps_name', '')),
		];
	}

	/** @return array<string, mixed> */
	public static function settingsVars(): array
	{
		$domain = rtrim((string) \Settings::get('DOMAIN'), '/') . '/';

		return [
			'psUrl' => (string) \Settings::get('PRESTASHOP_URL'),
			'psApiKey' => (string) \Settings::get('PRESTASHOP_API_KEY'),
			'psConfigured' => ProductSyncService::isConfigured(),
			'psTestUrl' => $domain . 'api/marketplace.php?action=ps-test',
			'psPullUrl' => $domain . 'api/marketplace.php?action=ps-pull',
		];
	}

	/** @return array<string, mixed> */
	public static function productPanelVars(int $idProduct): array
	{
		$product = \Product::getByIdAdmin($idProduct);
		$mapping = ProductSyncService::findMapping($idProduct) ?: [];
		$urls = \Trendyol\TrendyolAdminPages::commonUrls();

		return array_merge($urls, [
			'id_product' => $idProduct,
			'mapping' => $mapping,
			'configured' => ProductSyncService::isConfigured(),
			'product_price' => (float) ($product['price'] ?? 0),
			'product_stock' => $product ? (int) \Product::getStock($product) : 0,
			'ps_product_id' => (int) ($mapping['ps_product_id'] ?? 0),
			'ps_sale_price' => (float) ($mapping['sale_price'] ?? 0),
			'ps_linked' => ProductSyncService::isLinked($mapping),
			'settingsUrl' => \Admin::url('marketplace-settings?platform=prestashop'),
		]);
	}
}
