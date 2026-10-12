<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/ProductSiblingsService.php';

class ProductSiblingsModule extends ModuleBase
{
	public string $name = 'product-siblings';
	public string $title = 'Ürün Seçenekleri';
	public string $version = '1.0.0';
	public string $description = 'Ayrı ürünleri (60/90 kapsül vb.) ürün sayfasında görselli seçenek kartları olarak bağlar';
	public string $author = 'FShop';

	public array $displayHooks = [
		'product_inf' => 'Ürün sayfasında seçenek kartları',
		'admin_product_button' => 'Ürün formunda seçenek bağlama',
	];

	public array $defaultDisplayHooks = ['product_inf', 'admin_product_button'];

	public array $frontStylesheets = ['front.css'];
	public array $adminStylesheets = ['admin.css'];
	public array $adminScripts = ['admin.js'];

	public array $apiActions = [
		'search' => 'api/search.php',
	];

	public function install(): bool
	{
		ProductSiblingsService::ensureSchema();

		return $this->runSqlFile('install.sql');
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		ProductSiblingsService::ensureSchema();

		Module::registerHook('product.updated', static function ($idProduct): void {
			$idProduct = (int) $idProduct;

			if ($idProduct <= 0 || !Tools::isSubmit('saveProduct')) {
				return;
			}

			$raw = Tools::getValue('sibling_items');
			$items = [];

			if (is_string($raw) && $raw !== '') {
				$decoded = json_decode($raw, true);
				if (is_array($decoded)) {
					$items = $decoded;
				}
			} elseif (is_array($raw)) {
				$items = $raw;
			}

			ProductSiblingsService::saveForProduct($idProduct, $items);
		});
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'product_inf') {
			return null;
		}

		$idProduct = (int) ($context['id_product'] ?? 0);

		if ($idProduct <= 0) {
			return null;
		}

		$siblings = ProductSiblingsService::getSiblingsForProduct($idProduct);

		if (count($siblings) < 2) {
			return null;
		}

		$html = $this->renderFrontTemplate('product_detail', [
			'siblings' => $siblings,
		]);

		return $html !== '' ? $html : null;
	}

	public function renderAdminDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'admin_product_button') {
			return null;
		}

		$idProduct = (int) ($context['id_product'] ?? 0);
		$isNew = !empty($context['is_new']);
		$items = (!$isNew && $idProduct > 0) ? ProductSiblingsService::getAdminItems($idProduct) : [];

		global $domain, $adminToken;

		$html = $this->renderAdminTemplate('admin_product_button', [
			'id_product' => $idProduct,
			'is_new' => $isNew,
			'siblingItems' => $items,
			'siblingItemsJson' => json_encode(array_map(static function (array $row): array {
				return [
					'id_product' => (int) $row['id_product'],
					'product_name' => (string) $row['product_name'],
					'label' => (string) ($row['label'] ?? ''),
					'price' => (float) $row['price'],
					'price_formatted' => (string) $row['price_formatted'],
				];
			}, $items), JSON_UNESCAPED_UNICODE),
			'searchApi' => rtrim((string) $domain, '/') . '/api/module.php?m=product-siblings&action=search',
			'adminToken' => $adminToken,
		]);

		return $html !== '' ? $html : null;
	}

	public function adminPage(): void
	{
		global $smarty;

		$smarty->assign([
			'flash' => 'Seçenek ürünlerini ürün düzenleme ekranından bağlayın.',
			'flashType' => 'info',
		]);
	}
}
