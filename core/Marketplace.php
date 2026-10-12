<?php

require_once __DIR__ . '/marketplace/bootstrap.php';

class Marketplace
{
	public const PLATFORMS = [
		'trendyol' => [
			'label' => 'Trendyol',
			'active' => true,
		],
		'hepsiburada' => [
			'label' => 'Hepsiburada',
			'active' => true,
		],
		'n11' => [
			'label' => 'N11',
			'active' => true,
		],
		'prestashop' => [
			'label' => 'PrestaShop',
			'active' => true,
		],
		'frisay' => [
			'label' => 'FriSay',
			'active' => true,
		],
		'fiyattrend' => [
			'label' => 'FiyatTrend',
			'active' => true,
			'settings_only' => true,
		],
	];

	/** send=0 ile kapatılır: pazaryerine stok/fiyat push yok, yalnızca FShop stok düşer */
	private static bool $allowMarketplaceStockPush = true;

	public static function setAllowMarketplaceStockPush(bool $allow): void
	{
		self::$allowMarketplaceStockPush = $allow;
	}

	public static function allowMarketplaceStockPush(): bool
	{
		return self::$allowMarketplaceStockPush;
	}

	public static function ensureSchema(): void
	{
		MarketplaceTables::ensureSchema();
		Trendyol\ProductSyncService::ensureSchema();
		Hepsiburada\ProductSyncService::ensureSchema();
		N11\ProductSyncService::ensureSchema();
		Prestashop\ProductSyncService::ensureSchema();
		Frisay\ProductSyncService::ensureSchema();
	}

	public static function isEnabled(): bool
	{
		return true;
	}

	public static function platforms(): array
	{
		return self::PLATFORMS;
	}

	/**
	 * Sipariş/ürün kataloğu olan aktif platformlar (FiyatTrend hariç).
	 *
	 * @return array<int, string>
	 */
	public static function orderPlatformKeys(): array
	{
		$keys = [];

		foreach (self::PLATFORMS as $key => $platform) {
			if (!empty($platform['active']) && empty($platform['settings_only'])) {
				$keys[] = $key;
			}
		}

		return $keys;
	}

	public static function isOrderPlatform(string $key): bool
	{
		return in_array($key, self::orderPlatformKeys(), true);
	}

	public static function isValidOrderPlatformFilter(string $platform): bool
	{
		return $platform === 'all' || self::isOrderPlatform($platform);
	}

	public static function isSettingsOnly(string $key): bool
	{
		return !empty(self::PLATFORMS[$key]['settings_only']);
	}

	public static function platformList(): array
	{
		$list = [];

		foreach (self::PLATFORMS as $key => $platform) {
			$list[] = [
				'key' => $key,
				'label' => $platform['label'],
				'active' => !empty($platform['active']),
				'settings_only' => !empty($platform['settings_only']),
				'configured' => self::isPlatformConfigured($key),
				'settings_url' => self::settingsUrl($key),
			];
		}

		return $list;
	}

	public static function isPlatformActive(string $key): bool
	{
		return !empty(self::PLATFORMS[$key]['active']);
	}

	public static function isPlatformConfigured(string $key): bool
	{
		if ($key === 'trendyol') {
			return Trendyol\ProductSyncService::isConfigured();
		}

		if ($key === 'hepsiburada') {
			return Hepsiburada\ProductSyncService::isConfigured();
		}

		if ($key === 'n11') {
			return N11\ProductSyncService::isConfigured();
		}

		if ($key === 'prestashop') {
			return Prestashop\ProductSyncService::isConfigured();
		}

		if ($key === 'frisay') {
			return Frisay\ProductSyncService::isConfigured();
		}

		if ($key === 'fiyattrend') {
			return trim((string) Settings::get('TRENDYOL_FIYATTREND_TOKEN')) !== '';
		}

		return false;
	}

	/**
	 * @return array{ok: bool, message: string, count?: int, stock_updates?: int, orders?: array<int, array<string, mixed>>}
	 */
	public static function syncOrdersForPlatform(string $platform, ?string $start = null, ?string $end = null): array
	{
		if ($platform === 'hepsiburada') {
			return Hepsiburada\OrderService::syncOrders($start, $end);
		}

		if ($platform === 'n11') {
			return N11\OrderService::syncOrders();
		}

		if ($platform === 'prestashop') {
			return Prestashop\OrderService::syncOrders($start, $end);
		}

		if ($platform === 'frisay') {
			return Frisay\OrderService::syncOrders($start, $end);
		}

		return Trendyol\OrderService::syncOrders($start, $end);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getRecentOrders(string $platform, int $limit = 50): array
	{
		if ($platform === 'hepsiburada') {
			return Hepsiburada\OrderService::getRecent($limit);
		}

		if ($platform === 'n11') {
			return N11\OrderService::getRecent($limit);
		}

		if ($platform === 'prestashop') {
			return Prestashop\OrderService::getRecent($limit);
		}

		if ($platform === 'frisay') {
			return Frisay\OrderService::getRecent($limit);
		}

		return Trendyol\OrderService::getRecent($limit);
	}

	public static function handleAdminPosts(string $adminToken): string
	{
		if (\Tools::isSubmit('syncMarketplaceOrders')) {
			$postToken = (string) \Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				return 'Geçersiz istek';
			}

			$platform = trim((string) \Tools::getValue('marketplace_platform', 'trendyol'));
			$start = trim((string) \Tools::getValue('start_date'));
			$end = trim((string) \Tools::getValue('end_date'));
			$syncStart = $start !== '' ? $start : null;
			$syncEnd = $end !== '' ? $end : null;

			if ($platform === 'all') {
				$messages = [];

				foreach (self::orderPlatformKeys() as $key) {
					if (!self::isPlatformConfigured($key)) {
						continue;
					}

					$result = self::syncOrdersForPlatform($key, $syncStart, $syncEnd);
					$messages[] = (string) ($result['message'] ?? (self::PLATFORMS[$key]['label'] . ' siparişleri senkronize edildi'));
				}

				return $messages === []
					? 'Yapılandırılmış pazaryeri yok'
					: implode(' · ', $messages);
			}

			if (!self::isOrderPlatform($platform)) {
				$platform = 'trendyol';
			}

			$result = self::syncOrdersForPlatform($platform, $syncStart, $syncEnd);

			return (string) ($result['message'] ?? 'Siparişler senkronize edildi');
		}

		$flash = Trendyol\TrendyolAdminPages::handlePosts($adminToken);

		if ($flash === '') {
			$flash = Hepsiburada\HepsiburadaAdminPages::handlePosts($adminToken);
		}

		if ($flash === '') {
			$flash = N11\N11AdminPages::handlePosts($adminToken);
		}

		if ($flash === '') {
			$flash = Prestashop\PrestashopAdminPages::handlePosts($adminToken);
		}

		if ($flash === '') {
			$flash = Frisay\FrisayAdminPages::handlePosts($adminToken);
		}

		return $flash;
	}

	public static function isTrendyolConfigured(): bool
	{
		return Trendyol\ProductSyncService::isConfigured();
	}

	public static function urls(): array
	{
		$urls = Trendyol\TrendyolAdminPages::commonUrls();
		$domain = rtrim((string) Settings::get('DOMAIN'), '/') . '/';
		$api = rtrim($domain, '/') . '/api/marketplace.php';
		$token = urlencode((string) Settings::get('SHOP_TOKEN'));

		$urls['cronOrdersUrlHb'] = $api . '?action=cron&type=orders&platform=hepsiburada&token=' . $token;
		$urls['cronQuestionsUrlHb'] = $api . '?action=cron&type=questions&platform=hepsiburada&token=' . $token;
		$urls['cronOrdersUrlN11'] = $api . '?action=cron&type=orders&platform=n11&token=' . $token;
		$urls['cronQuestionsUrlN11'] = $api . '?action=cron&type=questions&platform=n11&token=' . $token;
		$urls['cronOrdersUrlPs'] = $api . '?action=cron&type=orders&platform=prestashop&token=' . $token;
		$urls['cronOrdersUrlFrisay'] = $api . '?action=cron&type=orders&platform=frisay&token=' . $token;

		$urls['exportOrdersUrl'] = $api . '?action=export-orders';
		$urls['orderActionUrl'] = $api . '?action=order-action';

		return $urls;
	}

	public static function settingsUrl(string $platform = 'trendyol'): string
	{
		$path = 'marketplace-settings';

		if ($platform !== '') {
			$path .= '?platform=' . rawurlencode($platform);
		}

		return Admin::url($path);
	}

	public static function adminAssets(): array
	{
		global $adminCssDir, $domain;

		$jsBase = rtrim((string) ($adminCssDir ?? $domain . 'templates/admin/css/'), '/');
		$jsBase = preg_replace('#/css/?$#', '/js', $jsBase) ?: $domain . 'templates/admin/js/';

		return [
			'css' => [],
			'js' => [
				$domain . 'templates/admin/js/marketplace-admin.js?v=' . filemtime(__DIR__ . '/../templates/admin/js/marketplace-admin.js'),
			],
		];
	}

	public static function renderProductPanelHtml(int $idProduct, string $platform = 'trendyol'): string
	{
		global $smarty;

		if ($idProduct <= 0) {
			return '';
		}

		if ($platform === 'hepsiburada') {
			$vars = Hepsiburada\HepsiburadaAdminPages::productPanelVars($idProduct);
			$tpl = 'admin/marketplace/product_panel_hepsiburada.tpl';
		} elseif ($platform === 'n11') {
			$vars = N11\N11AdminPages::productPanelVars($idProduct);
			$tpl = 'admin/marketplace/product_panel_n11.tpl';
		} elseif ($platform === 'prestashop') {
			$vars = Prestashop\PrestashopAdminPages::productPanelVars($idProduct);
			$tpl = 'admin/marketplace/product_panel_prestashop.tpl';
		} elseif ($platform === 'frisay') {
			$vars = Frisay\FrisayAdminPages::productPanelVars($idProduct);
			$tpl = 'admin/marketplace/product_panel_frisay.tpl';
		} else {
			$vars = Trendyol\TrendyolAdminPages::productPanelVars($idProduct);
			$tpl = 'admin/marketplace/product_panel.tpl';
		}

		$previous = [];

		foreach ($vars as $key => $value) {
			$previous[$key] = $smarty->getTemplateVars($key);
			$smarty->assign($key, $value);
		}

		$html = $smarty->fetch($tpl) ?: '';

		foreach ($previous as $key => $value) {
			if ($value === null) {
				$smarty->clearAssign($key);
			} else {
				$smarty->assign($key, $value);
			}
		}

		return $html;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	public static function enrichCatalogRow(array $row): array
	{
		$idProduct = (int) ($row['id_product'] ?? 0);
		$row['ty_linked'] = !empty($row['ty_linked']) || trim((string) ($row['ty_barcode'] ?? '')) !== '';
		$row['ty_paused'] = strtolower(trim((string) ($row['ty_last_status'] ?? ''))) === 'paused';
		$row['edit_url'] = Admin::url('product?id=' . $idProduct);
		$cost = (float) ($row['cost'] ?? 0);

		$hb = $idProduct > 0 ? Hepsiburada\ProductSyncService::findMapping($idProduct) : null;
		$row['hb_linked'] = Hepsiburada\ProductSyncService::isLinked($hb);
		$row['hb_sale_price'] = (float) ($hb['sale_price'] ?? 0);
		$row['hb_merchant_sku'] = (string) ($hb['merchant_sku'] ?? '');

		$n11 = $idProduct > 0 ? N11\ProductSyncService::findMapping($idProduct) : null;
		$row['n11_linked'] = N11\ProductSyncService::isLinked($n11);
		$row['n11_sale_price'] = (float) ($n11['sale_price'] ?? 0);
		$row['n11_stock_code'] = (string) ($n11['stock_code'] ?? '');

		$ps = $idProduct > 0 ? Prestashop\ProductSyncService::findMapping($idProduct) : null;
		$row['ps_linked'] = Prestashop\ProductSyncService::isLinked($ps);
		$row['ps_sale_price'] = (float) ($ps['sale_price'] ?? 0);
		$row['ps_product_id'] = (int) ($ps['ps_product_id'] ?? 0);

		$frisay = $idProduct > 0 ? Frisay\ProductSyncService::findMapping($idProduct) : null;
		$row['frisay_linked'] = Frisay\ProductSyncService::isLinked($frisay);
		$row['frisay_sale_price'] = (float) ($frisay['sale_price'] ?? 0);
		$row['frisay_remote_id'] = (int) ($frisay['remote_id'] ?? 0);

		$row['stock'] = round((float) ($row['stock'] ?? 0), 2);
		$row['stock_formatted'] = Tools::displayStock($row['stock']);
		$row['price_formatted'] = $row['price_formatted'] ?? Tools::displayPrice((float) ($row['price'] ?? 0));
		$row['vat'] = (float) ($row['vat'] ?? 20);
		$row['has_variations'] = class_exists('ProductVariation') && $idProduct > 0
			? ProductVariation::hasVariations($idProduct)
			: false;
		$row['variation_chips'] = [];

		if ($row['has_variations']) {
			foreach (ProductVariation::getByProduct($idProduct, true) as $variation) {
				$row['variation_chips'][] = [
					'label' => ProductVariation::formatLabel($variation),
					'stock' => (int) ($variation['stock'] ?? 0),
				];
			}
		}

		$row['listings'] = [];
		$channels = [
			['key' => 'trendyol', 'linked' => !empty($row['ty_linked']), 'price' => (float) ($row['ty_sale_price'] ?? 0), 'url' => (string) ($row['ty_product_url'] ?? ''), 'paused' => !empty($row['ty_paused']), 'status' => (string) ($row['ty_last_status'] ?? '')],
			['key' => 'hepsiburada', 'linked' => !empty($row['hb_linked']), 'price' => (float) $row['hb_sale_price'], 'url' => '', 'paused' => false, 'status' => ''],
			['key' => 'n11', 'linked' => !empty($row['n11_linked']), 'price' => (float) $row['n11_sale_price'], 'url' => '', 'paused' => false, 'status' => ''],
			['key' => 'prestashop', 'linked' => !empty($row['ps_linked']), 'price' => (float) $row['ps_sale_price'], 'url' => '', 'paused' => false, 'status' => ''],
			['key' => 'frisay', 'linked' => !empty($row['frisay_linked']), 'price' => (float) $row['frisay_sale_price'], 'url' => '', 'paused' => false, 'status' => ''],
		];

		foreach ($channels as $ch) {
			if (empty($ch['linked'])) {
				continue;
			}

			$price = (float) $ch['price'];
			$profitPct = null;

			if ($price > 0) {
				$profitPct = round((($price - $cost) / $price) * 100, 0);
			}

			$status = 'sale';

			if (!empty($ch['paused']) || $ch['status'] === 'paused') {
				$status = 'paused';
			} elseif (in_array($ch['status'], ['pending', 'failed'], true)) {
				$status = $ch['status'] === 'failed' ? 'closed' : 'pending';
			}

			$row['listings'][] = [
				'platform' => $ch['key'],
				'label' => (string) (self::PLATFORMS[$ch['key']]['label'] ?? $ch['key']),
				'price' => $price,
				'price_formatted' => $price > 0 ? number_format($price, 2, ',', '.') . ' ₺' : '—',
				'profit_pct' => $profitPct,
				'status' => $status,
				'product_url' => $ch['url'],
				'modal_id' => self::listingModalId($ch['key'], $idProduct),
			];
		}

		return $row;
	}

	private static function listingModalId(string $platform, int $idProduct): string
	{
		if ($platform === 'trendyol') {
			return 'mp-modal-' . $idProduct;
		}

		if ($platform === 'hepsiburada') {
			return 'mp-modal-hb-' . $idProduct;
		}

		if ($platform === 'n11') {
			return 'mp-modal-n11-' . $idProduct;
		}

		if ($platform === 'prestashop') {
			return 'mp-modal-ps-' . $idProduct;
		}

		return 'mp-modal-frisay-' . $idProduct;
	}

	/**
	 * @param int $idProduct FShop Product ID
	 * @param string|null $excludePlatform Platform key to skip
	 * @return array<string, array{ok: bool, message: string}>
	 */
	public static function syncProductStockAcrossPlatforms(int $idProduct, ?string $excludePlatform = null): array
	{
		$results = [];

		if ($idProduct <= 0 || !self::$allowMarketplaceStockPush) {
			return $results;
		}

		if ($excludePlatform !== 'trendyol' && self::isPlatformActive('trendyol') && self::isTrendyolConfigured()) {
			$mapping = Trendyol\ProductSyncService::findMapping($idProduct);
			if ($mapping && Trendyol\ProductSyncService::isLinked($mapping)) {
				$results['trendyol'] = Trendyol\ProductSyncService::updatePriceStock($idProduct);
			}
		}

		if ($excludePlatform !== 'hepsiburada' && self::isPlatformActive('hepsiburada') && self::isPlatformConfigured('hepsiburada')) {
			$mapping = Hepsiburada\ProductSyncService::findMapping($idProduct);
			if ($mapping && Hepsiburada\ProductSyncService::isLinked($mapping)) {
				$results['hepsiburada'] = Hepsiburada\ProductSyncService::updatePriceStock($idProduct);
			}
		}

		if ($excludePlatform !== 'n11' && self::isPlatformActive('n11') && self::isPlatformConfigured('n11')) {
			$mapping = N11\ProductSyncService::findMapping($idProduct);
			if ($mapping && N11\ProductSyncService::isLinked($mapping)) {
				$results['n11'] = N11\ProductSyncService::updatePriceStock($idProduct);
			}
		}

		if ($excludePlatform !== 'prestashop' && self::isPlatformActive('prestashop') && self::isPlatformConfigured('prestashop')) {
			$mapping = Prestashop\ProductSyncService::findMapping($idProduct);
			if ($mapping && Prestashop\ProductSyncService::isLinked($mapping)) {
				$results['prestashop'] = Prestashop\ProductSyncService::updatePriceStock($idProduct);
			}
		}

		if ($excludePlatform !== 'frisay' && self::isPlatformActive('frisay') && self::isPlatformConfigured('frisay')) {
			$mapping = Frisay\ProductSyncService::findMapping($idProduct);
			if ($mapping && Frisay\ProductSyncService::isLinked($mapping)) {
				$results['frisay'] = Frisay\ProductSyncService::updatePriceStock($idProduct);
			}
		}

		return $results;
	}
}
