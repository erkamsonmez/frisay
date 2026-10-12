<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/QuantityTiersService.php';

class QuantityTiersModule extends ModuleBase
{
	public string $name = 'quantity-tiers';
	public string $title = 'Çok Al Az Öde';
	public string $version = '1.0.0';
	public string $description = 'Ürün bazlı miktar kademeli birim fiyat (toplu alım indirimi)';
	public string $author = 'FShop';

	public array $displayHooks = [
		'admin_product_button' => 'Ürün formunda kademe fiyatları',
	];

	public array $defaultDisplayHooks = ['admin_product_button'];

	public array $adminStylesheets = ['admin.css'];
	public array $adminScripts = ['admin.js'];

	public function install(): bool
	{
		QuantityTiersService::ensureSchema();

		return $this->runSqlFile('install.sql');
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		QuantityTiersService::ensureSchema();

		Module::registerHook('product.updated', static function ($idProduct): void {
			$idProduct = (int) $idProduct;

			if ($idProduct <= 0 || !Tools::isSubmit('saveProduct')) {
				return;
			}

			$raw = Tools::getValue('quantity_tiers');
			$rows = [];

			if (is_string($raw) && $raw !== '') {
				$decoded = json_decode($raw, true);
				if (is_array($decoded)) {
					$rows = $decoded;
				}
			} elseif (is_array($raw)) {
				$rows = $raw;
			}

			QuantityTiersService::saveTiers($idProduct, $rows);
		});
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		// Storefront “Çok Al Az Öde” comes from Cart Promotion (/admin/cart-promotion).
		return null;
	}

	public function renderAdminDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'admin_product_button') {
			return null;
		}

		$idProduct = (int) ($context['id_product'] ?? 0);
		$isNew = !empty($context['is_new']);
		$tiers = (!$isNew && $idProduct > 0) ? QuantityTiersService::getTiers($idProduct) : [];

		$html = $this->renderAdminTemplate('admin_product_button', [
			'id_product' => $idProduct,
			'is_new' => $isNew,
			'tiers' => $tiers,
			'tiersJson' => json_encode($tiers, JSON_UNESCAPED_UNICODE),
		]);

		return $html !== '' ? $html : null;
	}

	public function adminPage(): void
	{
		global $smarty;

		$smarty->assign([
			'flash' => 'Miktar kademelerini ürün düzenleme ekranından tanımlayın.',
			'flashType' => 'info',
		]);
	}
}
