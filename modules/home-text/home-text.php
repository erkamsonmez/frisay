<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class HomeTextModule extends ModuleBase
{
	public string $name = 'home-text';
	public string $title = 'Ana Sayfa Metni';
	public string $version = '1.1.0';
	public string $description = 'Ana sayfaya HTML metin bloğu ekler';
	public string $author = 'FShop';

	private const TABLE = 'home_text';
	private const LEGACY_TABLE = 'home-text';
	private const ROW_ID = 1;

	public array $displayHooks = [
		'home_text' => 'Ana sayfa metin bloğu',
		'home_bottom' => 'Ana sayfa altı (yedek konum)',
	];

	public array $defaultDisplayHooks = ['home_text'];

	public array $frontStylesheets = ['home-text.css'];

	public function install(): bool
	{
		$this->migrateLegacyTable();

		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		$this->ensureDefaultRow();

		return true;
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		if (!Module::isInstalled($this->name)) {
			return;
		}

		$this->migrateLegacyTable();
		$this->ensureDefaultRow();

		Module::registerHook('smarty.assign', function ($smarty): void {
			if (!$smarty || defined('IN_ADMIN')) {
				return;
			}

			$smarty->assign([
				'homeText' => $this->getContent(),
			]);
		});
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'home_text' && $hook !== 'home_bottom') {
			return null;
		}

		$html = $this->getContent();

		if (trim(strip_tags($html)) === '' && strpos($html, '<img') === false) {
			return null;
		}

		$rendered = $this->renderFrontTemplate('home_text', [
			'homeTextContent' => $html,
		]);

		return $rendered !== '' ? $rendered : null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'info';

		$this->migrateLegacyTable();
		$this->ensureDefaultRow();

		if (Tools::isSubmit('saveHomeText')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} elseif ($this->saveContent((string) Tools::getValue('home_text', ''))) {
				$flash = 'Ana sayfa metni kaydedildi';
				$flashType = 'success';
			} else {
				$flash = 'Metin kaydedilemedi';
				$flashType = 'danger';
			}
		}

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'homeTextContent' => $this->getContent(),
			'adminUseEditor' => true,
		]);
	}

	public function getContent(): string
	{
		$row = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

		if (!$row || !isset($row['detail'])) {
			return '';
		}

		return (string) $row['detail'];
	}

	public function saveContent(string $html): bool
	{
		$html = Security::sanitizeHtml(trim($html));
		$row = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

		if ($row) {
			return DB::update(self::TABLE, ['detail' => $html], 'id = :where_id', ['where_id' => self::ROW_ID]) !== false;
		}

		try {
			return DB::insert(self::TABLE, [
				'id' => self::ROW_ID,
				'detail' => $html,
			]) !== false;
		} catch (Throwable $e) {
			error_log('home-text save: ' . $e->getMessage());

			return false;
		}
	}

	private function ensureDefaultRow(): void
	{
		$row = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);

		if ($row) {
			return;
		}

		try {
			DB::insert(self::TABLE, [
				'id' => self::ROW_ID,
				'detail' => '',
			]);
		} catch (Throwable $e) {
			error_log('home-text default row: ' . $e->getMessage());
		}
	}

	private function migrateLegacyTable(): void
	{
		$legacy = DB::execute('SHOW TABLES LIKE \'' . self::LEGACY_TABLE . '\'');
		$current = DB::execute('SHOW TABLES LIKE \'' . self::TABLE . '\'');
		$hasLegacy = !empty($legacy);
		$hasCurrent = !empty($current);

		if ($hasLegacy && !$hasCurrent) {
			DB::execute('RENAME TABLE `' . self::LEGACY_TABLE . '` TO `' . self::TABLE . '`');

			return;
		}

		if ($hasLegacy && $hasCurrent) {
			$legacyRow = DB::execute('SELECT `detail` FROM `' . self::LEGACY_TABLE . '` WHERE id = ? LIMIT 1', [self::ROW_ID]);
			$currentRow = DB::getRowSafe(self::TABLE, 'id = ?', [self::ROW_ID]);
			$legacyDetail = is_array($legacyRow) && isset($legacyRow[0]['detail']) ? trim((string) $legacyRow[0]['detail']) : '';
			$currentDetail = $currentRow ? trim((string) ($currentRow['detail'] ?? '')) : '';

			if ($legacyDetail !== '' && $currentDetail === '') {
				if ($currentRow) {
					DB::update(self::TABLE, ['detail' => $legacyDetail], 'id = :where_id', ['where_id' => self::ROW_ID]);
				} else {
					try {
						DB::insert(self::TABLE, [
							'id' => self::ROW_ID,
							'detail' => $legacyDetail,
						]);
					} catch (Throwable $e) {
						error_log('home-text migrate insert: ' . $e->getMessage());
					}
				}
			}

			DB::execute('DROP TABLE IF EXISTS `' . self::LEGACY_TABLE . '`');
		}
	}
}
