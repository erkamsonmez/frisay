<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/PrestashopImportService.php';

class PrestashopImportModule extends ModuleBase
{
	public string $name = 'prestashop-import';
	public string $title = 'PrestaShop Import';
	public string $version = '1.0.0';
	public string $description = 'PrestaShop tablolarından marka, kategori ve müşteri içe aktarma';
	public string $author = 'FShop';

	public array $adminStylesheets = ['admin.css'];

	public array $hooksMeta = [
		'admin.menu' => 'Sol menüde Catalog bölümünde PrestaShop Import linki',
	];

	public function install(): bool
	{
		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		PrestashopImportService::ensureDefaultSettings();

		return true;
	}

	public function uninstall(): bool
	{
		Settings::set(PrestashopImportService::SETTING_PREFIX, '');
		Settings::set(PrestashopImportService::SETTING_LANG_ID, '');
		Settings::set(PrestashopImportService::SETTING_COOKIE_KEY, '');
		Settings::set(PrestashopImportService::SETTING_SKIP_ROOT, '');
		Settings::set(PrestashopImportService::SETTING_SKIP_GUESTS, '');

		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		PrestashopImportService::ensureSchema();
		PrestashopImportService::ensureDefaultSettings();
		$this->registerAdminMenuLink('PrestaShop Import', 'catalog', 86);
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'success';
		$stats = [];
		$settings = PrestashopImportService::getSettings();

		if (Tools::isSubmit('savePsImportSettings')) {
			$token = (string) Tools::getValue('token');

			if (!hash_equals((string) $adminToken, $token)) {
				$flash = adminT('Invalid request');
				$flashType = 'danger';
			} else {
				PrestashopImportService::saveSettings([
					'prefix' => Tools::getValue('prefix'),
					'lang_id' => Tools::getValue('lang_id'),
					'cookie_key' => Tools::getValue('cookie_key'),
					'skip_root' => Tools::getValue('skip_root'),
					'skip_guests' => Tools::getValue('skip_guests'),
				]);
				$settings = PrestashopImportService::getSettings();
				$flash = adminT('Settings saved');
			}
		} elseif (Tools::isSubmit('runPsImport')) {
			$token = (string) Tools::getValue('token');

			if (!hash_equals((string) $adminToken, $token)) {
				$flash = adminT('Invalid request');
				$flashType = 'danger';
			} elseif (Admin::isDemoMode()) {
				$flash = adminT('Demo mode: some edits are not allowed');
				$flashType = 'warning';
			} else {
				PrestashopImportService::saveSettings([
					'prefix' => Tools::getValue('prefix', $settings['prefix']),
					'lang_id' => Tools::getValue('lang_id', $settings['lang_id']),
					'cookie_key' => Tools::getValue('cookie_key', $settings['cookie_key']),
					'skip_root' => Tools::getValue('skip_root'),
					'skip_guests' => Tools::getValue('skip_guests'),
				]);
				$settings = PrestashopImportService::getSettings();

				$target = (string) Tools::getValue('import_target', 'all');

				if ($target === 'brands') {
					$result = PrestashopImportService::importBrands();
				} elseif ($target === 'categories') {
					$result = PrestashopImportService::importCategories();
				} elseif ($target === 'customers') {
					$result = PrestashopImportService::importCustomers();
				} else {
					$result = PrestashopImportService::importAll();
				}

				$flash = (string) ($result['message'] ?? '');
				$flashType = !empty($result['success']) ? 'success' : 'danger';
				$rawStats = is_array($result['stats'] ?? null) ? $result['stats'] : null;

				if (is_array($rawStats)) {
					$labels = [
						'brands' => 'Brands',
						'categories' => 'Categories',
						'customers' => 'Customers',
					];

					if (isset($rawStats['brands']) || isset($rawStats['categories']) || isset($rawStats['customers'])) {
						foreach ($labels as $key => $label) {
							if (isset($rawStats[$key]) && is_array($rawStats[$key])) {
								$stats[] = [
									'label' => $label,
									'stats' => $rawStats[$key],
								];
							}
						}
					} else {
						$stats[] = [
							'label' => '',
							'stats' => $rawStats,
						];
					}
				}
			}
		}

		$smarty->assign([
			'pageTitle' => $this->title,
			'flash' => $flash,
			'flashType' => $flashType,
			'psSettings' => $settings,
			'psSourceStatus' => PrestashopImportService::getSourceStatus(),
			'psImportStats' => $stats,
			'psDemoMode' => Admin::isDemoMode(),
			'psModuleUrl' => Admin::url($this->getAdminSlug()),
		]);
	}
}
