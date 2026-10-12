<?php

namespace Frisay;

class FrisayAdminPages
{
	public static function handlePosts(string $adminToken): string
	{
		$flash = '';

		if (\Tools::isSubmit('saveFrisay')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				\Settings::set('FRISAY_API_URL', rtrim(trim((string) \Tools::getValue('frisay_url')), '/'));
				\Settings::set('FRISAY_API_KEY', trim((string) \Tools::getValue('frisay_api_key')));
				$flash = 'FriSay uzak mağaza ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('syncFrisayOrders')) {
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

		if (\Tools::isSubmit('pullFrisayProducts')) {
			$postToken = (string) \Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = ProductSyncService::pullBatch(1, 10);
				$flash = $result['message'];
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (\Tools::isSubmit('sendFrisayProducts')) {
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
	public static function settingsVars(): array
	{
		return [
			'frisayUrl' => (string) \Settings::get('FRISAY_API_URL'),
			'frisayApiKey' => (string) \Settings::get('FRISAY_API_KEY'),
			'frisayConfigured' => ProductSyncService::isConfigured(),
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
			'frisay_remote_id' => (int) ($mapping['remote_id'] ?? 0),
			'frisay_sale_price' => (float) ($mapping['sale_price'] ?? 0),
			'frisay_linked' => ProductSyncService::isLinked($mapping),
			'settingsUrl' => \Admin::url('marketplace-settings?platform=frisay'),
		]);
	}
}
