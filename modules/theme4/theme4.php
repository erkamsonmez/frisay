<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/Theme4Assets.php';

class Theme4Module extends ModuleBase
{
	public string $name = 'theme4';
	public string $title = 'Tema Edit';
	public string $version = '2.6.0';
	public string $description = 'Default temayı sitede tıklayarak düzenleme';
	public string $author = 'FShop';

	public array $hooksMeta = [
		'admin.menu' => 'Sol menüde Sistem bölümünde Tema Edit linki',
	];

	public array $displayHooks = [
		'home' => 'Theme4 homepage content (page builder)',
	];

	public array $defaultDisplayHooks = ['home'];

	public array $adminStylesheets = ['admin-builder.css'];
	public array $adminScripts = ['admin-builder.js', 'admin-visual.js'];
	public array $frontStylesheets = ['front-builder.css', 'theme-settings.css'];
	public array $frontScripts = ['front-rail.js'];

	private const LAYOUT_HOME = 'HOME_LAYOUT';
	private const LAYOUT_HEADER = 'HEADER_LAYOUT';
	private const LAYOUT_FOOTER = 'FOOTER_LAYOUT';
	private const SETTING_SITE_WIDTH = 'SITE_WIDTH';
	private const SETTING_FONT = 'FONT_FAMILY';
	private const SETTING_LOGO = 'LOGO';
	private const SETTING_FAVICON = 'FAVICON';
	private const SETTING_COPYRIGHT = 'FOOTER_COPYRIGHT';
	/** @deprecated kept for classic footer fallback only */
	private const SETTING_FOOTER_LOGO = 'FOOTER_LOGO';
	/** @deprecated kept for classic footer fallback only */
	private const SETTING_FOOTER = 'FOOTER_TEXT';
	private const SETTING_PREVIEW_TOKEN = 'PREVIEW_TOKEN';
	private const SETTING_PREVIEW_DRAFT = 'PREVIEW_DRAFT';
	private const SETTING_PREVIEW_EXPIRES = 'PREVIEW_EXPIRES';
	private const SETTING_CHROME = 'CHROME_TEXTS';

	/** @var list<int> */
	private const COL_WIDTHS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

	/** @var list<string> */
	private const WIDGETS_HOME = ['banner', 'hook', 'category_products', 'text', 'logo', 'links'];

	/** @var list<string> */
	private const WIDGETS_HEADER = ['logo', 'search', 'text', 'links', 'hook', 'header_tools'];

	/** @var list<string> */
	private const WIDGETS_FOOTER = ['logo', 'text', 'links', 'hook'];

	/** @var list<string> */
	private const WIDGET_TYPES = ['banner', 'hook', 'category_products', 'text', 'logo', 'links', 'search', 'header_tools'];

	/** @var list<string> */
	private const HOOK_CHOICES = [
		'home_slider',
		'home_promo_slider',
		'home_bottom',
		'main_menu',
		'footer',
	];

	/** @var array<string, string>|null */
	private ?array $translations = null;

	/** @var array<string, mixed>|null */
	private ?array $previewDraftCache = null;

	private bool $previewDraftResolved = false;

	public function install(): bool
	{
		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		$this->ensureDefaultThemeSettings();

		return true;
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		$this->registerAdminMenuLink('Theme Edit', 'system', 40);

		if (defined('IN_ADMIN') || !defined('IN_SCRIPT')) {
			return;
		}

		Module::registerHook('smarty.assign', function ($smarty): void {
			if (!$smarty) {
				return;
			}

			$smarty->assign($this->getFrontThemeAssigns());
		});

		Module::registerHook('head.assets', function (&$assets): void {
			if ($this->getActivePreviewDraft() === null) {
				return;
			}

			if (!is_array($assets)) {
				$assets = ['css' => [], 'js' => []];
			}

			$assets['css'][] = $this->getAssetUrl('css/preview-edit.css');
			$assets['js'][] = $this->getAssetUrl('js/preview-edit.js');
		});
	}

	/**
	 * Module-local translation (English source key).
	 * Files: modules/theme4/translations/{lang}.php — no core changes needed.
	 */
	public function l(string $text): string
	{
		$map = $this->loadTranslations();

		return array_key_exists($text, $map) ? (string) $map[$text] : $text;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		if (Tools::getValue('ajax') === '1') {
			$this->handleAdminAjax((string) $adminToken);
			exit;
		}

		$flash = '';
		$flashType = 'success';
		$activeTab = $this->normalizeTab((string) Tools::getValue('tab', 'home'));

		if (Tools::isSubmit('saveTheme4Layout')) {
			$result = $this->handleSaveLayout((string) $adminToken, self::LAYOUT_HOME, self::WIDGETS_HOME);
			$flash = $result['message'];
			$flashType = $result['type'];
			$activeTab = 'home';
		} elseif (Tools::isSubmit('saveTheme4Header')) {
			$result = $this->handleSaveLayout((string) $adminToken, self::LAYOUT_HEADER, self::WIDGETS_HEADER);
			$flash = $result['message'];
			$flashType = $result['type'];
			$activeTab = 'header';
		} elseif (Tools::isSubmit('saveTheme4Footer')) {
			$result = $this->handleSaveLayout((string) $adminToken, self::LAYOUT_FOOTER, self::WIDGETS_FOOTER);
			$flash = $result['message'];
			$flashType = $result['type'];
			$activeTab = 'footer';
		} elseif (Tools::isSubmit('saveTheme4Theme')) {
			$result = $this->handleSaveTheme((string) $adminToken);
			$flash = $result['message'];
			$flashType = $result['type'];
			$activeTab = 'theme';
		} elseif (Tools::isSubmit('saveTheme4Colors')) {
			$result = $this->handleSaveColors((string) $adminToken);
			$flash = $result['message'];
			$flashType = $result['type'];
			$activeTab = 'colors';
		} elseif (Tools::isSubmit('saveTheme4Custom')) {
			$result = $this->handleSaveCustom((string) $adminToken);
			$flash = $result['message'];
			$flashType = $result['type'];
			$activeTab = 'custom';
		} elseif (Tools::isSubmit('exportTheme4')) {
			$this->handleExportBundle((string) $adminToken);
			return;
		} elseif (Tools::isSubmit('importTheme4')) {
			$result = $this->handleImportBundle((string) $adminToken);
			$flash = $result['message'];
			$flashType = $result['type'];
			$activeTab = 'export';
		} elseif (Tools::isSubmit('applyTheme4Premium')) {
			$result = $this->handleApplyPremiumPreset((string) $adminToken);
			$flash = $result['message'];
			$flashType = $result['type'];
			$activeTab = 'export';
		}

		$publishedHome = $this->getLayoutByKey(self::LAYOUT_HOME, self::WIDGETS_HOME);
		$publishedHeader = $this->getLayoutByKey(self::LAYOUT_HEADER, self::WIDGETS_HEADER);
		$publishedFooter = $this->getLayoutByKey(self::LAYOUT_FOOTER, self::WIDGETS_FOOTER);
		$layout = $this->getAdminLayoutByKey(self::LAYOUT_HOME, self::WIDGETS_HOME);
		$headerLayout = $this->getAdminLayoutByKey(self::LAYOUT_HEADER, self::WIDGETS_HEADER);
		$footerLayout = $this->getAdminLayoutByKey(self::LAYOUT_FOOTER, self::WIDGETS_FOOTER);
		$categories = Category::getProductSelectOptions();
		$categoryJson = [];

		foreach ($categories as $cat) {
			$categoryJson[] = [
				'id' => (int) ($cat['id_category'] ?? 0),
				'name' => (string) ($cat['category_name'] ?? ''),
			];
		}

		$colors = Theme4Assets::readColors();
		$colorGroups = [];

		foreach (Theme4Assets::colorGroups() as $groupLabel => $items) {
			$fields = [];

			foreach ($items as $var => $label) {
				$value = $colors[$var] ?? Theme4Assets::DEFAULT_COLORS[$var] ?? '#000000';
				$fields[] = [
					'var' => $var,
					'label' => $this->l($label),
					'value' => $value,
					'hex' => Theme4Assets::hexForPicker($value),
				];
			}

			$colorGroups[] = [
				'label' => $this->l($groupLabel),
				'fields' => $fields,
			];
		}

		$colWidthOptions = [];

		foreach (self::COL_WIDTHS as $w) {
			$pct = (int) round(($w / 12) * 100);
			$colWidthOptions[] = [
				'value' => $w,
				'pct' => $pct,
			];
		}

		$i18n = [
			'bannerImage' => $this->l('Banner image'),
			'chooseMedia' => $this->l('Choose from media'),
			'selectMediaPlaceholder' => $this->l('Select from media'),
			'linkOptional' => $this->l('Link (optional)'),
			'altText' => $this->l('Alt text'),
			'hookHelp' => $this->l('Hook point used by modules such as sliders'),
			'category' => $this->l('Category'),
			'selectCategory' => $this->l('— select category —'),
			'title' => $this->l('Title'),
			'productCount' => $this->l('Product count'),
			'showViewAll' => $this->l('Show “View all” link'),
			'htmlText' => $this->l('HTML / text'),
			'mediaUnavailable' => $this->l('Media library could not be loaded. Refresh the page and try again.'),
			'selectAsBanner' => $this->l('Select as banner'),
			'selectLogo' => $this->l('Select logo'),
			'resetConfirm' => $this->l('Load the default layout? (Not applied until you save)'),
			'drag' => $this->l('Drag'),
			'mobile' => $this->l('Mobile'),
			'tablet' => $this->l('Tablet'),
			'desktop' => $this->l('Desktop'),
			'settings' => $this->l('Settings'),
			'rowSettings' => $this->l('Row settings'),
			'columnSettings' => $this->l('Column settings'),
			'blockId' => $this->l('ID'),
			'hideOnMobile' => $this->l('Hide on mobile'),
			'hideOnTablet' => $this->l('Hide on tablet'),
			'hideOnDesktop' => $this->l('Hide on desktop'),
			'apply' => $this->l('Apply'),
			'cancel' => $this->l('Cancel'),
			'widths' => $this->l('Widths'),
			'visibility' => $this->l('Visibility'),
			'hiddenBadge' => $this->l('Hidden'),
			'cssClass' => $this->l('CSS class'),
			'classHint' => $this->l('Space-separated class names'),
			'idHintFree' => $this->l('Letters, numbers, hyphen and underscore. Optional.'),
			'yes' => $this->l('Yes'),
			'no' => $this->l('No'),
			'logoImage' => $this->l('Logo image'),
			'captionOptional' => $this->l('Caption / text under logo'),
			'linksTitle' => $this->l('Section title'),
			'addLink' => $this->l('Add link'),
			'linkLabel' => $this->l('Label'),
			'linkUrl' => $this->l('URL'),
			'searchPlaceholder' => $this->l('Search placeholder'),
			'showAccount' => $this->l('Account'),
			'showFavorites' => $this->l('Favorites'),
			'showCart' => $this->l('Cart'),
			'showNotifications' => $this->l('Notifications'),
			'showMenuBtn' => $this->l('Mobile menu button'),
			'widgetBanner' => $this->l('Banner'),
			'widgetHook' => $this->l('Hook'),
			'widgetCategory' => $this->l('Category products'),
			'widgetText' => $this->l('Text / HTML'),
			'widgetImage' => $this->l('Image'),
			'widgetCategoryShort' => $this->l('Category'),
			'widgetTextShort' => $this->l('Text'),
			'addDiv' => $this->l('Add div'),
			'divLabel' => $this->l('Div'),
			'containerHint' => $this->l('Add side-by-side divs. Each div can be a category, image or text.'),
			'container' => $this->l('Container'),
			'widgetLogo' => $this->l('Logo'),
			'widgetLinks' => $this->l('Links'),
			'widgetSearch' => $this->l('Search bar'),
			'widgetHeaderTools' => $this->l('Header tools'),
			'linkSource' => $this->l('Link type'),
			'linkSourceCustom' => $this->l('Custom URL'),
			'linkSourcePage' => $this->l('Site page'),
			'linkSourceCms' => $this->l('CMS page'),
			'linkSourceCategory' => $this->l('Category'),
			'selectLink' => $this->l('— select —'),
			'layout' => $this->l('Layout'),
			'layoutGrid' => $this->l('Grid'),
			'layoutList' => $this->l('Carousel'),
		];

		$linkOptions = $this->buildLinkPickerOptions();
		$copyright = $this->getSetting(self::SETTING_COPYRIGHT);

		if ($copyright === '') {
			$copyright = '© {year} {site}. ' . $this->l('All rights reserved.');
		}

		if (method_exists($smarty, 'registerPlugin')) {
			try {
				$smarty->registerPlugin('modifier', 't4l', [$this, 'l']);
			} catch (Throwable $e) {
				// already registered on re-render
			}
		}

		$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

		$chrome = $this->getChromeTexts();
		$previewToken = $this->ensurePreviewToken();
		$this->savePreviewDraft([
			'home' => $publishedHome,
			'header' => $publishedHeader,
			'footer' => $publishedFooter,
			'copyright' => $copyright,
			'chrome' => $this->chromeForStorage($chrome),
		]);
		$this->writeThemeSettingsCss(
			$this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px',
			$this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif"
		);

		$storeUrl = rtrim((string) $domain, '/') . '/';
		$livePreviewUrl = $storeUrl . '?t4_preview=' . rawurlencode($previewToken);
		$previewConfig = [
			'token' => (string) $adminToken,
			'ajaxUrl' => Admin::url('module-theme4') . '?ajax=1',
			'previewUrl' => $livePreviewUrl,
			'exitUrl' => Admin::url('dashboard'),
		];

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			't4ActiveTab' => $activeTab,
			'layoutJson' => json_encode($layout, $jsonFlags),
			'headerLayoutJson' => json_encode($headerLayout, $jsonFlags),
			'footerLayoutJson' => json_encode($footerLayout, $jsonFlags),
			'defaultHomeLayoutJson' => json_encode($this->defaultLayout(), $jsonFlags),
			'defaultHeaderLayoutJson' => json_encode($this->defaultHeaderLayout(), $jsonFlags),
			'defaultFooterLayoutJson' => json_encode($this->defaultFooterLayout(), $jsonFlags),
			'categoryOptions' => $categories,
			'categoryOptionsJson' => json_encode($categoryJson, $jsonFlags),
			'hookChoices' => self::HOOK_CHOICES,
			'colWidths' => self::COL_WIDTHS,
			'colWidthOptions' => $colWidthOptions,
			'colWidthOptionsJson' => json_encode($colWidthOptions, $jsonFlags),
			'widgetsHome' => self::WIDGETS_HOME,
			'widgetsHeader' => self::WIDGETS_HEADER,
			'widgetsFooter' => self::WIDGETS_FOOTER,
			'theme4PreviewUrl' => $livePreviewUrl,
			't4LivePreviewUrl' => $livePreviewUrl,
			't4PreviewConfigJson' => json_encode($previewConfig, $jsonFlags),
			't4HeaderPublishedEmpty' => $publishedHeader['rows'] === [],
			't4FooterPublishedEmpty' => $publishedFooter['rows'] === [],
			't4HomePublishedEmpty' => $publishedHome['rows'] === [],
			'adminUseEditor' => true,
			'adminBodyClass' => 't4-visual-on',
			't4ExitUrl' => Admin::url('dashboard'),
			't4Chrome' => $chrome,
			't4Theme' => [
				'site_width' => $this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px',
				'font_family' => $this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif",
				'logo' => $this->getSetting(self::SETTING_LOGO),
				'favicon' => $this->getSetting(self::SETTING_FAVICON),
			],
			't4SiteWidthOptions' => $this->getSiteWidthOptions(),
			't4FontOptions' => $this->getFontOptions(),
			't4SiteWidthMatched' => $this->isKnownSiteWidth($this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px'),
			't4FontMatched' => $this->isKnownFont($this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif"),
			't4Copyright' => $copyright,
			't4LinkOptionsJson' => json_encode($linkOptions, $jsonFlags),
			't4ColorGroups' => $colorGroups,
			't4CustomCss' => Theme4Assets::readCustomCss(),
			't4CustomJs' => Theme4Assets::readCustomJs(),
			't4I18nJson' => json_encode($i18n, $jsonFlags),
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'home') {
			return null;
		}

		$html = trim($this->renderLayoutHtml(
			$this->getLayoutByKey(self::LAYOUT_HOME, self::WIDGETS_HOME),
			'home',
			'sn-wrap t4-builder-row'
		));

		return $html !== '' ? $html : null;
	}

	/**
	 * @param array{rows: list<array<string, mixed>>} $layout
	 */
	private function renderLayoutHtml(array $layout, string $zone, string $rowClassBase): string
	{
		$rowsHtml = [];
		$preview = $this->isPreviewEditing();

		foreach ($layout['rows'] as $row) {
			$colsHtml = [];
			$rowId = $this->safeHtmlId((string) ($row['id'] ?? ''), 'row');
			$rowClass = $this->sanitizeCssClass((string) ($row['class'] ?? ''));
			$rowHide = $this->normalizeHide($row['hide'] ?? null);

			foreach ($row['cols'] as $col) {
				$widths = $this->normalizeColWidths($col['width'] ?? 12);
				$colHide = $this->normalizeHide($col['hide'] ?? null);
				$colId = $this->safeHtmlId((string) ($col['id'] ?? ''), 'column');
				$colExtra = $this->sanitizeCssClass((string) ($col['class'] ?? ''));
				$widgetsHtml = [];

				foreach ($col['widgets'] as $widget) {
					$widgetId = $this->safeHtmlId((string) ($widget['id'] ?? ''), 'w');
					$block = $this->renderWidget($widget);

					if ($block === '' && $preview) {
						$type = (string) ($widget['type'] ?? 'text');
						$block = $this->renderFrontTemplate('widget-placeholder', [
							'label' => $this->widgetPlaceholderLabel($type),
							'hint' => $this->l('Click to edit'),
						]);
					}

					if ($block === '') {
						continue;
					}

					if ($preview) {
						$block = '<div class="t4-editable" data-t4-zone="'
							. htmlspecialchars($zone, ENT_QUOTES, 'UTF-8')
							. '" data-t4-row="'
							. htmlspecialchars($rowId, ENT_QUOTES, 'UTF-8')
							. '" data-t4-widget="'
							. htmlspecialchars($widgetId, ENT_QUOTES, 'UTF-8')
							. '">' . $block . '</div>';
					}

					$widgetsHtml[] = $block;
				}

				if ($widgetsHtml === [] && $preview) {
					$widgetsHtml[] = '<div class="t4-editable" data-t4-zone="'
						. htmlspecialchars($zone, ENT_QUOTES, 'UTF-8')
						. '" data-t4-row="'
						. htmlspecialchars($rowId, ENT_QUOTES, 'UTF-8')
						. '" data-t4-widget="">'
						. $this->renderFrontTemplate('widget-placeholder', [
							'label' => $this->l('Div'),
							'hint' => $this->l('Click to edit'),
						])
						. '</div>';
				}

				if ($widgetsHtml === [] && !$preview) {
					continue;
				}

				$colsHtml[] = $this->renderFrontTemplate('col', [
					'id' => $colId,
					'width' => $widths,
					'colClass' => $this->buildColClass($widths, $colHide, $colExtra),
					'widgetsHtml' => implode("\n", $widgetsHtml),
				]);
			}

			if ($colsHtml === [] && !$preview) {
				continue;
			}

			$rowsHtml[] = $this->renderFrontTemplate('row', [
				'id' => $rowId,
				'rowClass' => trim($rowClassBase . ' ' . $this->buildVisibilityClass($rowHide) . ' ' . $rowClass),
				'colsHtml' => implode("\n", $colsHtml),
			]);
		}

		return $this->renderFrontTemplate('layout', [
			'zone' => $zone,
			'rowsHtml' => implode("\n", $rowsHtml),
		]);
	}

	/**
	 * @param list<string> $allowedWidgets
	 * @return array{message:string,type:string}
	 */
	private function handleSaveLayout(string $adminToken, string $layoutKey, array $allowedWidgets): array
	{
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			return ['message' => $this->l('Invalid request'), 'type' => 'danger'];
		}

		$raw = isset($_POST['layout_json']) ? (string) $_POST['layout_json'] : '';
		$decoded = json_decode($raw, true);

		if (!is_array($decoded)) {
			return ['message' => $this->l('Invalid layout JSON'), 'type' => 'danger'];
		}

		$saved = $this->sanitizeLayout($decoded, $allowedWidgets);
		$this->setSetting($layoutKey, json_encode($saved, JSON_UNESCAPED_UNICODE));

		if ($layoutKey === self::LAYOUT_FOOTER) {
			$copyright = mb_substr(trim((string) Tools::getValue('copyright_text')), 0, 500);
			$this->setSetting(self::SETTING_COPYRIGHT, $copyright);
		}

		$messages = [
			self::LAYOUT_HOME => $this->l('Homepage layout saved'),
			self::LAYOUT_HEADER => $this->l('Header layout saved'),
			self::LAYOUT_FOOTER => $this->l('Footer layout saved'),
		];

		return [
			'message' => $messages[$layoutKey] ?? $this->l('Layout saved'),
			'type' => 'success',
		];
	}

	/** @return array{message:string,type:string} */
	private function handleSaveTheme(string $adminToken): array
	{
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			return ['message' => $this->l('Invalid request'), 'type' => 'danger'];
		}

		$logo = mb_substr(trim((string) Tools::getValue('logo')), 0, 500);
		$favicon = mb_substr(trim((string) Tools::getValue('favicon')), 0, 500);

		$this->setSetting(self::SETTING_LOGO, $logo);
		$this->setSetting(self::SETTING_FAVICON, $favicon);

		return ['message' => $this->l('Theme settings saved'), 'type' => 'success'];
	}

	/** @return array{message:string,type:string} */
	private function handleSaveColors(string $adminToken): array
	{
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			return ['message' => $this->l('Invalid request'), 'type' => 'danger'];
		}

		$posted = is_array($_POST['colors'] ?? null) ? $_POST['colors'] : [];
		$colors = [];

		foreach (array_keys(Theme4Assets::DEFAULT_COLORS) as $key) {
			$colors[$key] = trim((string) ($posted[$key] ?? ''));
		}

		$structural = Theme4Assets::readStructural();
		$siteWidth = $this->getSetting(self::SETTING_SITE_WIDTH);
		$font = $this->getSetting(self::SETTING_FONT);

		if ($siteWidth !== '') {
			$structural['container'] = $siteWidth;
		}

		if ($font !== '') {
			$structural['font'] = $font;
		}

		$result = Theme4Assets::writeColors($colors, $structural);

		if (!$result['success']) {
			$msg = $result['message'] === 'colors_write'
				? $this->l('Could not write colors.css')
				: $this->l('Could not write colors.css');

			return ['message' => $msg, 'type' => 'danger'];
		}

		$width = $this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px';
		$font = $this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif";
		$this->writeThemeSettingsCss($width, $font);

		return ['message' => $this->l('Colors saved to colors.css'), 'type' => 'success'];
	}

	/** @return array{message:string,type:string} */
	private function handleSaveCustom(string $adminToken): array
	{
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			return ['message' => $this->l('Invalid request'), 'type' => 'danger'];
		}

		$siteWidth = mb_substr(trim((string) Tools::getValue('site_width')), 0, 40);
		$font = mb_substr(trim((string) Tools::getValue('font_family')), 0, 200);
		$css = isset($_POST['custom_css']) ? (string) $_POST['custom_css'] : '';
		$js = isset($_POST['custom_js']) ? (string) $_POST['custom_js'] : '';

		if ($siteWidth === '') {
			$siteWidth = '1320px';
		}

		if ($font === '') {
			$font = "'Inter', system-ui, -apple-system, sans-serif";
		}

		$this->setSetting(self::SETTING_SITE_WIDTH, $siteWidth);
		$this->setSetting(self::SETTING_FONT, $font);
		$this->writeThemeSettingsCss($siteWidth, $font);
		$this->syncStructuralCss($siteWidth, $font);

		$cssResult = Theme4Assets::writeCustomCss($css);

		if (!$cssResult['success']) {
			return ['message' => $this->l('Could not write custom.css'), 'type' => 'danger'];
		}

		$jsResult = Theme4Assets::writeCustomJs($js);

		if (!$jsResult['success']) {
			return ['message' => $this->l('Could not write custom.js'), 'type' => 'danger'];
		}

		return ['message' => $this->l('Custom CSS & JS saved'), 'type' => 'success'];
	}

	/**
	 * Download a shareable Theme4 JSON bundle (layouts, theme, colors, custom CSS/JS).
	 */
	private function handleExportBundle(string $adminToken): void
	{
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			header('HTTP/1.1 403 Forbidden');
			echo $this->l('Invalid request');
			exit;
		}

		$bundle = $this->buildExportBundle();
		$json = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		if ($json === false) {
			header('HTTP/1.1 500 Internal Server Error');
			echo $this->l('Could not build export file');
			exit;
		}

		$filename = 'theme4-export-' . date('Ymd-His') . '.json';

		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Length: ' . (string) strlen($json));
		echo $json;
		exit;
	}

	/** @return array<string, mixed> */
	private function buildExportBundle(): array
	{
		return [
			'format' => 'fshop-theme4',
			'version' => 1,
			'module_version' => $this->version,
			'exported_at' => date('c'),
			'settings' => [
				'site_width' => $this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px',
				'font_family' => $this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif",
				'logo' => $this->getSetting(self::SETTING_LOGO),
				'favicon' => $this->getSetting(self::SETTING_FAVICON),
				'copyright' => $this->getSetting(self::SETTING_COPYRIGHT),
			],
			'layouts' => [
				'home' => $this->exportLayoutValue(self::LAYOUT_HOME, self::WIDGETS_HOME, $this->defaultLayout()),
				'header' => $this->exportLayoutValue(self::LAYOUT_HEADER, self::WIDGETS_HEADER, ['rows' => []]),
				'footer' => $this->exportLayoutValue(self::LAYOUT_FOOTER, self::WIDGETS_FOOTER, ['rows' => []]),
			],
			'colors' => Theme4Assets::readColors(),
			'structural' => Theme4Assets::readStructural(),
			'custom_css' => Theme4Assets::readCustomCss(),
			'custom_js' => Theme4Assets::readCustomJs(),
		];
	}

	/**
	 * @param list<string> $allowedWidgets
	 * @param array{rows: list<array<string, mixed>>} $fallback
	 * @return array{rows: list<array<string, mixed>>}
	 */
	private function exportLayoutValue(string $key, array $allowedWidgets, array $fallback): array
	{
		$raw = $this->getSetting($key);

		return $this->decodeLayoutJson($raw, $allowedWidgets, $fallback);
	}

	/**
	 * @param list<string> $allowedWidgets
	 * @param array{rows: list<array<string, mixed>>} $fallback
	 * @return array{rows: list<array<string, mixed>>}
	 */
	private function decodeLayoutJson(string $raw, array $allowedWidgets, array $fallback): array
	{
		if ($raw === '') {
			return $fallback;
		}

		$decoded = json_decode($raw, true);

		if (!is_array($decoded)) {
			return $fallback;
		}

		return $this->sanitizeLayout($decoded, $allowedWidgets);
	}

	/** @return array{message:string,type:string} */
	private function handleImportBundle(string $adminToken): array
	{
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			return ['message' => $this->l('Invalid request'), 'type' => 'danger'];
		}

		$raw = '';

		if (!empty($_FILES['import_file']['tmp_name']) && is_uploaded_file((string) $_FILES['import_file']['tmp_name'])) {
			$size = (int) ($_FILES['import_file']['size'] ?? 0);

			if ($size <= 0 || $size > 2 * 1024 * 1024) {
				return ['message' => $this->l('Import file is too large (max 2 MB)'), 'type' => 'danger'];
			}

			$raw = (string) file_get_contents((string) $_FILES['import_file']['tmp_name']);
		} else {
			$raw = isset($_POST['import_json']) ? (string) $_POST['import_json'] : '';
		}

		$raw = trim($raw);

		if ($raw === '') {
			return ['message' => $this->l('Please upload a JSON file or paste JSON'), 'type' => 'danger'];
		}

		if (strlen($raw) > 2 * 1024 * 1024) {
			return ['message' => $this->l('Import data is too large (max 2 MB)'), 'type' => 'danger'];
		}

		$decoded = json_decode($raw, true);

		if (!is_array($decoded)) {
			return ['message' => $this->l('Invalid JSON'), 'type' => 'danger'];
		}

		$format = (string) ($decoded['format'] ?? '');

		if ($format !== '' && $format !== 'fshop-theme4') {
			return ['message' => $this->l('This file is not a Theme4 export'), 'type' => 'danger'];
		}

		$doLayouts = !empty($_POST['import_layouts']);
		$doTheme = !empty($_POST['import_theme']);
		$doColors = !empty($_POST['import_colors']);
		$doCustom = !empty($_POST['import_custom']);

		if (!$doLayouts && !$doTheme && !$doColors && !$doCustom) {
			return ['message' => $this->l('Select at least one section to import'), 'type' => 'danger'];
		}

		return $this->applyBundleData($decoded, $doLayouts, $doTheme, $doColors, $doCustom);
	}

	/** @return array{message:string,type:string} */
	private function handleApplyPremiumPreset(string $adminToken): array
	{
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			return ['message' => $this->l('Invalid request'), 'type' => 'danger'];
		}

		$path = $this->getPath() . '/presets/premium.json';

		if (!is_file($path)) {
			return ['message' => $this->l('Premium preset file not found'), 'type' => 'danger'];
		}

		$decoded = json_decode((string) file_get_contents($path), true);

		if (!is_array($decoded) || (($decoded['format'] ?? '') !== 'fshop-theme4')) {
			return ['message' => $this->l('Premium preset is invalid'), 'type' => 'danger'];
		}

		$result = $this->applyBundleData($decoded, true, true, true, true);

		if ($result['type'] === 'success') {
			$result['message'] = $this->l('Premium theme applied. Refresh the storefront to see it.');
		}

		return $result;
	}

	/**
	 * @param array<string, mixed> $decoded
	 * @return array{message:string,type:string}
	 */
	private function applyBundleData(array $decoded, bool $doLayouts, bool $doTheme, bool $doColors, bool $doCustom): array
	{
		$applied = [];

		if ($doLayouts && isset($decoded['layouts']) && is_array($decoded['layouts'])) {
			$map = [
				'home' => [self::LAYOUT_HOME, self::WIDGETS_HOME],
				'header' => [self::LAYOUT_HEADER, self::WIDGETS_HEADER],
				'footer' => [self::LAYOUT_FOOTER, self::WIDGETS_FOOTER],
			];

			foreach ($map as $key => [$settingKey, $widgets]) {
				if (!isset($decoded['layouts'][$key]) || !is_array($decoded['layouts'][$key])) {
					continue;
				}

				$layout = $this->sanitizeLayout($decoded['layouts'][$key], $widgets);
				$this->setSetting($settingKey, json_encode($layout, JSON_UNESCAPED_UNICODE));
			}

			$applied[] = $this->l('Layouts');
		}

		if ($doTheme) {
			$settings = is_array($decoded['settings'] ?? null) ? $decoded['settings'] : [];

			if (isset($settings['site_width'])) {
				$siteWidth = mb_substr(trim((string) $settings['site_width']), 0, 40) ?: '1320px';
				$this->setSetting(self::SETTING_SITE_WIDTH, $siteWidth);
			}

			if (isset($settings['font_family'])) {
				$font = mb_substr(trim((string) $settings['font_family']), 0, 200)
					?: "'Inter', system-ui, -apple-system, sans-serif";
				$this->setSetting(self::SETTING_FONT, $font);
			}

			if (array_key_exists('logo', $settings)) {
				$this->setSetting(self::SETTING_LOGO, mb_substr(trim((string) $settings['logo']), 0, 500));
			}

			if (array_key_exists('favicon', $settings)) {
				$this->setSetting(self::SETTING_FAVICON, mb_substr(trim((string) $settings['favicon']), 0, 500));
			}

			if (array_key_exists('copyright', $settings)) {
				$this->setSetting(self::SETTING_COPYRIGHT, mb_substr(trim((string) $settings['copyright']), 0, 500));
			}

			$width = $this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px';
			$font = $this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif";
			$this->writeThemeSettingsCss($width, $font);
			$this->syncStructuralCss($width, $font);
			$applied[] = $this->l('Theme settings');
		}

		if ($doColors && isset($decoded['colors']) && is_array($decoded['colors'])) {
			$colors = [];

			foreach (array_keys(Theme4Assets::DEFAULT_COLORS) as $key) {
				$colors[$key] = trim((string) ($decoded['colors'][$key] ?? Theme4Assets::DEFAULT_COLORS[$key]));
			}

			$structural = Theme4Assets::readStructural();

			if (isset($decoded['structural']) && is_array($decoded['structural'])) {
				foreach (array_keys(Theme4Assets::STRUCTURAL) as $key) {
					if (isset($decoded['structural'][$key])) {
						$structural[$key] = trim((string) $decoded['structural'][$key]);
					}
				}
			}

			$siteWidth = $this->getSetting(self::SETTING_SITE_WIDTH);
			$font = $this->getSetting(self::SETTING_FONT);

			if ($siteWidth !== '') {
				$structural['container'] = $siteWidth;
			}

			if ($font !== '') {
				$structural['font'] = $font;
			}

			$result = Theme4Assets::writeColors($colors, $structural);

			if (!$result['success']) {
				return ['message' => $this->l('Could not write colors.css'), 'type' => 'danger'];
			}

			$width = $this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px';
			$font = $this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif";
			$this->writeThemeSettingsCss($width, $font);

			$applied[] = $this->l('Colors');
		}

		if ($doCustom) {
			if (array_key_exists('custom_css', $decoded)) {
				$cssResult = Theme4Assets::writeCustomCss((string) $decoded['custom_css']);

				if (!$cssResult['success']) {
					return ['message' => $this->l('Could not write custom.css'), 'type' => 'danger'];
				}
			}

			if (array_key_exists('custom_js', $decoded)) {
				$jsResult = Theme4Assets::writeCustomJs((string) $decoded['custom_js']);

				if (!$jsResult['success']) {
					return ['message' => $this->l('Could not write custom.js'), 'type' => 'danger'];
				}
			}

			$applied[] = $this->l('Custom CSS & JS');
		}

		return [
			'message' => $this->l('Import completed') . ': ' . implode(', ', $applied),
			'type' => 'success',
		];
	}

	private function ensureDefaultThemeSettings(): void
	{
		$defaults = [
			self::SETTING_SITE_WIDTH => '1320px',
			self::SETTING_FONT => "'Inter', system-ui, -apple-system, sans-serif",
			self::SETTING_LOGO => '',
			self::SETTING_FAVICON => '',
			self::SETTING_COPYRIGHT => '',
		];

		foreach ($defaults as $key => $value) {
			if ($this->getSetting($key) === '') {
				$this->setSetting($key, $value);
			}
		}

		$width = $this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px';
		$font = $this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif";
		$this->writeThemeSettingsCss($width, $font);
	}

	private function isTheme4Active(): bool
	{
		return true;
	}

	/** @return array<string, mixed> */
	private function getFrontThemeAssigns(): array
	{
		$logo = $this->resolveMediaUrl($this->getSetting(self::SETTING_LOGO));
		$footerLogo = $this->resolveMediaUrl($this->getSetting(self::SETTING_FOOTER_LOGO));
		$favicon = $this->resolveMediaUrl($this->getSetting(self::SETTING_FAVICON));
		$footerText = trim($this->getSetting(self::SETTING_FOOTER));
		$preview = $this->getActivePreviewDraft();
		$copyrightRaw = $this->getSetting(self::SETTING_COPYRIGHT);

		if (is_array($preview) && array_key_exists('copyright', $preview)) {
			$copyrightRaw = (string) $preview['copyright'];
		}

		$headerHtml = '';
		$footerHtml = '';

		try {
			$headerHtml = $this->renderLayoutHtml(
				$this->getLayoutByKey(self::LAYOUT_HEADER, self::WIDGETS_HEADER),
				'header',
				't4-builder-row t4-header-builder-row sn-wrap'
			);
			$footerHtml = $this->renderLayoutHtml(
				$this->getLayoutByKey(self::LAYOUT_FOOTER, self::WIDGETS_FOOTER),
				'footer',
				't4-builder-row t4-footer-builder-row sn-wrap'
			);
		} catch (Throwable $e) {
			$headerHtml = '';
			$footerHtml = '';
		}

		return [
			't4Logo' => $logo,
			't4FooterLogo' => $footerLogo,
			't4Favicon' => $favicon,
			't4FooterText' => $footerText,
			't4Copyright' => $this->formatCopyrightText($copyrightRaw),
			't4SiteWidth' => $this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px',
			't4FontFamily' => $this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif",
			't4FontGoogle' => $this->getFontGoogleHref($this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif"),
			't4HeaderHtml' => $headerHtml,
			't4FooterHtml' => $footerHtml,
			't4UseHeaderBuilder' => $headerHtml !== '',
			't4UseFooterBuilder' => $footerHtml !== '',
			't4PreviewMode' => $preview !== null,
			't4Chrome' => $this->getEffectiveChromeTexts(),
		];
	}

	private function formatCopyrightText(string $raw): string
	{
		$siteName = '';

		if (class_exists('Settings', false)) {
			$siteName = trim((string) Settings::get('SITE_NAME'));
		}

		if ($siteName === '') {
			$siteName = 'FShop';
		}

		$year = date('Y');

		if (trim($raw) === '') {
			$raw = '© {year} {site}. ' . $this->l('All rights reserved.');
		}

		return str_replace(
			['{year}', '{site}', '{SITE}', '{YEAR}'],
			[$year, $siteName, $siteName, $year],
			$raw
		);
	}

	/**
	 * Header info + trust/service bar copy (classic theme4 chrome).
	 *
	 * @return array<string, string>
	 */
	private function defaultChromeTexts(): array
	{
		$phone = '';

		if (class_exists('Settings', false)) {
			$phone = trim((string) Settings::get('CONTACT_PHONE'));
		}

		if ($phone === '') {
			$phone = '0 (850) 241 74 84';
		}

		return [
			'header_support_label' => '7/24 Live Support',
			'header_support_value' => $phone,
			'header_delivery_label' => 'Automatic Delivery',
			'header_delivery_value' => 'Instant Delivery',
			'header_bar_1' => '',
			'header_bar_2' => '',
			'header_bar_3' => '',
			'header_bar_bg' => '#0f172a',
			'header_bar_color' => '#ffffff',
			'trust1_title' => '%100 Secure Payment',
			'trust1_text' => '3D Secure with Secure Payment',
			'trust2_title' => 'Unlimited Support',
			'trust2_text' => 'Always with you',
			'trust3_title' => 'Fast Delivery',
			'trust3_text' => 'Delivered within 15 minutes',
			'footer_brand_text' => '',
			'footer_menu_title' => '',
			'footer_featured_title' => '',
			'footer_corporate_title' => '',
			'footer_service_title' => '',
			'footer_service_text' => '',
			'footer_news_title' => '',
			'footer_news_text' => '',
			'footer_col_service' => '',
			'footer_col_company' => '',
			'footer_col_account' => '',
			'footer_col_contact' => '',
		];
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, string>
	 */
	private function sanitizeChromeTexts(array $input): array
	{
		$defaults = $this->defaultChromeTexts();
		$out = $defaults;

		foreach ($defaults as $key => $fallback) {
			if (!array_key_exists($key, $input)) {
				continue;
			}

			if ($key === 'header_bar_bg' || $key === 'header_bar_color') {
				$out[$key] = $this->sanitizeHexColor((string) $input[$key], $fallback);
				continue;
			}

			$max = strpos($key, '_text') !== false ? 300 : 80;
			$out[$key] = mb_substr(trim(strip_tags((string) $input[$key])), 0, $max);
		}

		return $this->withChromeTel($out);
	}

	private function sanitizeHexColor(string $value, string $fallback): string
	{
		$value = trim($value);

		if (preg_match('/^#([0-9a-fA-F]{3})$/', $value, $m)) {
			$h = $m[1];

			return '#' . $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
		}

		if (preg_match('/^#([0-9a-fA-F]{6})$/', $value)) {
			return $value;
		}

		return $fallback;
	}

	/**
	 * @param array<string, string> $chrome
	 * @return array<string, string>
	 */
	private function withChromeTel(array $chrome): array
	{
		$raw = (string) ($chrome['header_support_value'] ?? '');
		$tel = preg_replace('/[^\d+]/', '', $raw);
		$chrome['header_support_tel'] = is_string($tel) && $tel !== '' ? $tel : $raw;

		return $chrome;
	}

	/**
	 * @param array<string, string> $chrome
	 * @return array<string, string>
	 */
	private function chromeForStorage(array $chrome): array
	{
		unset($chrome['header_support_tel']);

		return $chrome;
	}

	/** @return array<string, string> */
	private function getChromeTexts(): array
	{
		$defaults = $this->defaultChromeTexts();
		$decoded = json_decode($this->getSetting(self::SETTING_CHROME), true);

		if (!is_array($decoded)) {
			return $this->withChromeTel($defaults);
		}

		return $this->sanitizeChromeTexts(array_merge($defaults, $decoded));
	}

	/** @return array<string, string> */
	private function getEffectiveChromeTexts(): array
	{
		$preview = $this->getActivePreviewDraft();

		if (is_array($preview) && isset($preview['chrome']) && is_array($preview['chrome'])) {
			return $this->sanitizeChromeTexts(array_merge($this->defaultChromeTexts(), $preview['chrome']));
		}

		return $this->getChromeTexts();
	}

	/** @return array<string, string> */
	private function readPostedChrome(): array
	{
		$posted = $_POST['chrome'] ?? null;

		if (!is_array($posted)) {
			return $this->getChromeTexts();
		}

		return $this->sanitizeChromeTexts(array_merge($this->defaultChromeTexts(), $posted));
	}

	/**
	 * Options for the links widget picker (admin).
	 *
	 * @return array{pages: list<array{id:string,label:string,url:string}>,cms: list<array{id:string,label:string,url:string}>,categories: list<array{id:string,label:string,url:string}>}
	 */
	private function buildLinkPickerOptions(): array
	{
		global $domain;

		$base = rtrim((string) $domain, '/') . '/';

		$pages = [
			['id' => 'home', 'label' => $this->l('Home Page'), 'url' => $base],
			['id' => 'special', 'label' => $this->l('All Products'), 'url' => $base . 'special'],
			['id' => 'contact', 'label' => $this->l('Contact Us'), 'url' => $base . 'contact'],
			['id' => 'login', 'label' => $this->l('Login'), 'url' => $base . 'login'],
			['id' => 'cart', 'label' => $this->l('Cart'), 'url' => $base . 'cart'],
			['id' => 'favorites', 'label' => $this->l('Favorites'), 'url' => $base . 'favorites'],
		];

		$cms = [];

		if (class_exists('Cms', false)) {
			foreach (Cms::getPages() as $page) {
				$id = (int) ($page['id_cms'] ?? 0);
				$slug = trim((string) ($page['slug'] ?? ''));
				$title = trim((string) ($page['title'] ?? ''));

				if ($id <= 0 || $slug === '' || $title === '') {
					continue;
				}

				$cms[] = [
					'id' => (string) $id,
					'label' => $title,
					'url' => $base . ltrim($slug, '/'),
				];
			}
		}

		$categories = [];

		foreach (Category::getProductSelectOptions() as $cat) {
			$id = (int) ($cat['id_category'] ?? 0);

			if ($id <= 0) {
				continue;
			}

			$row = Category::getById($id);
			$url = $row ? Category::getUrl($row) : '';

			if ($url === '') {
				continue;
			}

			$categories[] = [
				'id' => (string) $id,
				'label' => (string) ($cat['category_name'] ?? ''),
				'url' => $url,
			];
		}

		return [
			'pages' => $pages,
			'cms' => $cms,
			'categories' => $categories,
		];
	}

	/**
	 * @param array<string, mixed> $item
	 * @return array{label:string,url:string}|null
	 */
	private function resolveLinkItem(array $item): ?array
	{
		global $domain;

		$source = (string) ($item['source'] ?? 'custom');
		$ref = trim((string) ($item['ref'] ?? ''));
		$label = trim(strip_tags((string) ($item['label'] ?? '')));
		$url = trim((string) ($item['url'] ?? ''));
		$base = rtrim((string) $domain, '/') . '/';

		if ($source === 'cms' && $ref !== '' && class_exists('Cms', false)) {
			$page = Cms::getById((int) $ref);

			if ($page) {
				$slug = trim((string) ($page['slug'] ?? ''));

				if ($slug !== '') {
					$url = $base . ltrim($slug, '/');
				}

				if ($label === '') {
					$label = trim((string) ($page['title'] ?? ''));
				}
			}
		} elseif ($source === 'category' && $ref !== '') {
			$cat = Category::getById((int) $ref);

			if ($cat) {
				$url = Category::getUrl($cat);

				if ($label === '') {
					$label = trim((string) ($cat['category_name'] ?? ''));
				}
			}
		} elseif ($source === 'page' && $ref !== '') {
			foreach ($this->buildLinkPickerOptions()['pages'] as $page) {
				if ($page['id'] === $ref) {
					$url = $page['url'];

					if ($label === '') {
						$label = $page['label'];
					}

					break;
				}
			}
		}

		if ($label === '' || $url === '') {
			return null;
		}

		return [
			'label' => mb_substr($label, 0, 120),
			'url' => mb_substr($url, 0, 500),
		];
	}

	private function writeThemeSettingsCss(string $siteWidth, string $font): void
	{
		$path = $this->getPath() . '/assets/css/theme-settings.css';
		$dir = dirname($path);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return;
		}

		$siteWidthCss = $this->sanitizeCssLength($siteWidth) ?: '1320px';
		$fontCss = $this->sanitizeCssFont($font) ?: "'Inter', system-ui, -apple-system, sans-serif";
		$colors = Theme4Assets::readColors();
		$colorLines = '';

		foreach ($colors as $key => $value) {
			$value = trim((string) $value);

			if ($value === '' || !Theme4Assets::isValidColorValue($value)) {
				continue;
			}

			$colorLines .= "\t--" . $key . ': ' . $value . ";\n";
		}

		$css = "/**\n * Theme4 theme settings — auto-generated. Do not edit by hand.\n */\n"
			. ":root {\n"
			. "\t--container: {$siteWidthCss};\n"
			. "\t--font: {$fontCss};\n"
			. "\t--theme-container-max: {$siteWidthCss};\n"
			. "\t--theme-font-family: {$fontCss};\n"
			. $colorLines
			. "\t--sm-primary: var(--primary);\n"
			. "\t--sm-primary-dark: var(--primary-hover);\n"
			. "\t--sm-primary-soft: var(--primary-light);\n"
			. "\t--sn-blue: var(--primary);\n"
			. "\t--sn-blue-dark: var(--primary-hover);\n"
			. "}\n\n"
			. "body,\nbody.sm-body,\nbody.sn-body,\n.prime-body {\n"
			. "\tfont-family: var(--font);\n"
			. "}\n\n"
			. ".sn-wrap,\n.sm-container,\n.custom-container,\n.page > .container,\n.page .container.custom-container,\n.t4-builder-row.container {\n"
			. "\tmax-width: var(--container);\n"
			. "\twidth: 100%;\n"
			. "\tmargin-left: auto;\n"
			. "\tmargin-right: auto;\n"
			. "}\n";

		@file_put_contents($path, $css);
	}

	/** @return list<array{value:string,label:string}> */
	private function getSiteWidthOptions(): array
	{
		return [
			['value' => '1140px', 'label' => '1140px'],
			['value' => '1200px', 'label' => '1200px'],
			['value' => '1280px', 'label' => '1280px'],
			['value' => '1320px', 'label' => '1320px (' . $this->l('default') . ')'],
			['value' => '1400px', 'label' => '1400px'],
			['value' => '1600px', 'label' => '1600px'],
			['value' => '100%', 'label' => '100% (' . $this->l('full width') . ')'],
		];
	}

	/** @return list<array{value:string,label:string,google?:string}> */
	private function getFontOptions(): array
	{
		return [
			[
				'value' => "'Inter', system-ui, -apple-system, sans-serif",
				'label' => 'Inter',
				'google' => 'Inter:wght@400;500;600;700',
			],
			[
				'value' => "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif",
				'label' => 'Plus Jakarta Sans',
				'google' => 'Plus+Jakarta+Sans:wght@400;500;600;700;800',
			],
			[
				'value' => "'Poppins', system-ui, -apple-system, sans-serif",
				'label' => 'Poppins',
				'google' => 'Poppins:wght@400;500;600;700',
			],
			[
				'value' => "'Roboto', system-ui, -apple-system, sans-serif",
				'label' => 'Roboto',
				'google' => 'Roboto:wght@400;500;700',
			],
			[
				'value' => "'Open Sans', system-ui, -apple-system, sans-serif",
				'label' => 'Open Sans',
				'google' => 'Open+Sans:wght@400;500;600;700',
			],
			[
				'value' => "'Montserrat', system-ui, -apple-system, sans-serif",
				'label' => 'Montserrat',
				'google' => 'Montserrat:wght@400;500;600;700',
			],
			[
				'value' => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
				'label' => $this->l('System font'),
				'google' => '',
			],
		];
	}

	private function isKnownSiteWidth(string $width): bool
	{
		foreach ($this->getSiteWidthOptions() as $opt) {
			if ($opt['value'] === $width) {
				return true;
			}
		}

		return false;
	}

	private function isKnownFont(string $font): bool
	{
		foreach ($this->getFontOptions() as $opt) {
			if ($opt['value'] === $font) {
				return true;
			}
		}

		return false;
	}

	private function getFontGoogleHref(string $font): string
	{
		foreach ($this->getFontOptions() as $opt) {
			if ($opt['value'] !== $font) {
				continue;
			}

			if (empty($opt['google'])) {
				return '';
			}

			return 'https://fonts.googleapis.com/css2?family=' . $opt['google'] . '&display=swap';
		}

		return 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap';
	}

	private function syncStructuralCss(string $siteWidth, string $font): void
	{
		$structural = Theme4Assets::readStructural();
		$structural['container'] = $this->sanitizeCssLength($siteWidth) ?: $structural['container'];
		$structural['font'] = $this->sanitizeCssFont($font) ?: $structural['font'];
		Theme4Assets::writeColors(Theme4Assets::readColors(), $structural);
	}

	private function sanitizeCssLength(string $value): string
	{
		$value = trim($value);

		if ($value === '') {
			return '';
		}

		if (preg_match('/^(100%|\d+(\.\d+)?(px|rem|em|%|vw))$/i', $value)) {
			return $value;
		}

		return '';
	}

	private function sanitizeCssFont(string $value): string
	{
		$value = trim($value);

		if ($value === '') {
			return '';
		}

		// Allow common font-family lists
		if (!preg_match('/^[a-zA-Z0-9\s\'",._\-]+$/u', $value)) {
			return '';
		}

		return $value;
	}

	private function normalizeTab(string $tab): string
	{
		$allowed = ['home', 'header', 'footer', 'theme', 'colors', 'custom', 'export'];

		return in_array($tab, $allowed, true) ? $tab : 'home';
	}

	/** @return array<string, string> */
	private function loadTranslations(): array
	{
		if ($this->translations !== null) {
			return $this->translations;
		}

		$lang = 'en';

		if (class_exists('AdminLang', false)) {
			$lang = AdminLang::current();
		}

		$lang = preg_replace('/[^a-z0-9_-]/i', '', strtolower($lang)) ?: 'en';
		$path = $this->getPath() . '/translations/' . $lang . '.php';

		if (!is_file($path)) {
			$path = $this->getPath() . '/translations/en.php';
		}

		$map = is_file($path) ? require $path : [];
		$this->translations = is_array($map) ? $map : [];

		return $this->translations;
	}

	/** @param array<string, mixed> $widget */
	private function renderWidget(array $widget): string
	{
		$type = (string) ($widget['type'] ?? '');
		$settings = is_array($widget['settings'] ?? null) ? $widget['settings'] : [];

		if ($type === 'banner') {
			$image = trim((string) ($settings['image'] ?? ''));

			if ($image === '') {
				return '';
			}

			return $this->renderFrontTemplate('widget-banner', [
				'image' => $this->resolveMediaUrl($image),
				'link' => trim((string) ($settings['link'] ?? '')),
				'alt' => trim((string) ($settings['alt'] ?? '')),
			]);
		}

		if ($type === 'hook') {
			$hookName = trim((string) ($settings['hook'] ?? ''));

			if ($hookName === '' || !in_array($hookName, self::HOOK_CHOICES, true)) {
				return '';
			}

			$hookHtml = Module::renderDisplayHook($hookName);

			if ($hookHtml === null || $hookHtml === '') {
				return '';
			}

			return $this->renderFrontTemplate('widget-hook', [
				'hookHtml' => $hookHtml,
				'hookName' => $hookName,
			]);
		}

		if ($type === 'category_products') {
			$idCategory = (int) ($settings['id_category'] ?? 0);
			$limit = max(1, min(48, (int) ($settings['limit'] ?? 8)));
			$title = trim((string) ($settings['title'] ?? ''));
			$showLink = !empty($settings['show_link']);
			$layout = ((string) ($settings['layout'] ?? 'grid')) === 'list' ? 'list' : 'grid';

			$fetchLimit = min(96, max($limit * 4, $limit));
			$list = $idCategory > 0
				? Product::getActiveList($idCategory, $fetchLimit)
				: Product::getActiveList(null, $fetchLimit);
			$products = array_values(array_filter(
				$list,
				static function (array $p): bool {
					return !empty($p['in_stock']);
				}
			));
			$products = array_slice($products, 0, $limit);
			$category = Category::getById($idCategory);
			$url = '';

			if ($showLink && $category) {
				$url = Category::getUrl($category);
			}

			if ($title === '' && $category) {
				$title = (string) ($category['category_name'] ?? '');
			}

			if ($title === '' && $idCategory <= 0) {
				$title = $this->l('Featured products');
			}

			return $this->renderFrontTemplate('widget-category', [
				'products' => $products,
				'title' => $title,
				'url' => $url,
				'listId' => 't4cat' . $idCategory,
				'layout' => $layout,
			]);
		}

		if ($type === 'text') {
			$html = trim((string) ($settings['html'] ?? ''));

			if ($html === '') {
				return '';
			}

			return $this->renderFrontTemplate('widget-text', [
				'html' => $html,
			]);
		}

		if ($type === 'logo') {
			return $this->renderFrontTemplate('widget-logo', [
				'image' => $this->resolveMediaUrl(trim((string) ($settings['image'] ?? ''))),
				'link' => trim((string) ($settings['link'] ?? '')),
				'alt' => trim((string) ($settings['alt'] ?? '')),
				'caption' => trim((string) ($settings['caption'] ?? '')),
			]);
		}

		if ($type === 'links') {
			$items = is_array($settings['items'] ?? null) ? $settings['items'] : [];
			$clean = [];

			foreach ($items as $item) {
				if (!is_array($item)) {
					continue;
				}

				$resolved = $this->resolveLinkItem($item);

				if ($resolved !== null) {
					$clean[] = $resolved;
				}
			}

			return $this->renderFrontTemplate('widget-links', [
				'title' => trim(strip_tags((string) ($settings['title'] ?? ''))),
				'items' => $clean,
			]);
		}

		if ($type === 'search') {
			return $this->renderFrontTemplate('widget-search', [
				'placeholder' => trim(strip_tags((string) ($settings['placeholder'] ?? ''))),
				'defaultPlaceholder' => $this->l('Search product..'),
				'searchLabel' => $this->l('Search'),
				'inputId' => 't4Search_' . bin2hex(random_bytes(3)),
			]);
		}

		if ($type === 'header_tools') {
			return $this->renderFrontTemplate('widget-header-tools', [
				'showAccount' => !empty($settings['show_account']),
				'showFavorites' => !empty($settings['show_favorites']),
				'showCart' => !empty($settings['show_cart']),
				'showNotifications' => !empty($settings['show_notifications']),
				'showMenuBtn' => !empty($settings['show_menu_btn']),
				'labelMenu' => $this->l('Menu'),
				'labelAccount' => $this->l('My Account'),
				'labelFavorites' => $this->l('Favorites'),
				'labelCart' => $this->l('Cart'),
			]);
		}

		return '';
	}

	private function resolveMediaUrl(string $image): string
	{
		$image = trim($image);

		if ($image === '') {
			return '';
		}

		if (preg_match('#^(https?:)?//#i', $image) || strpos($image, 'data:') === 0) {
			return $image;
		}

		global $domain;

		return rtrim((string) $domain, '/') . '/' . ltrim($image, '/');
	}

	/** @return array{rows: list<array<string, mixed>>} */
	public function getLayout(): array
	{
		return $this->getLayoutByKey(self::LAYOUT_HOME, self::WIDGETS_HOME);
	}

	/**
	 * @param list<string> $allowedWidgets
	 * @return array{rows: list<array<string, mixed>>}
	 */
	private function getLayoutByKey(string $key, array $allowedWidgets): array
	{
		$preview = $this->getActivePreviewDraft();

		if (is_array($preview)) {
			$map = [
				self::LAYOUT_HOME => 'home',
				self::LAYOUT_HEADER => 'header',
				self::LAYOUT_FOOTER => 'footer',
			];
			$draftKey = $map[$key] ?? '';

			if ($draftKey !== '' && isset($preview[$draftKey]) && is_array($preview[$draftKey])) {
				return $this->sanitizeLayout($preview[$draftKey], $allowedWidgets);
			}
		}

		$raw = $this->getSetting($key);
		$fallbackHome = $this->defaultLayout();

		// Empty setting → starter homepage (slider + products) instead of a blank middle
		if ($raw === '') {
			if ($key === self::LAYOUT_HEADER) {
				return ['rows' => []];
			}

			if ($key === self::LAYOUT_FOOTER) {
				return ['rows' => []];
			}

			return $fallbackHome;
		}

		$decoded = json_decode($raw, true);

		if (!is_array($decoded)) {
			if ($key === self::LAYOUT_HEADER) {
				return $this->defaultHeaderLayout();
			}

			if ($key === self::LAYOUT_FOOTER) {
				return $this->defaultFooterLayout();
			}

			return $fallbackHome;
		}

		$layout = $this->sanitizeLayout($decoded, $allowedWidgets);

		if ($key === self::LAYOUT_HOME && $layout['rows'] === []) {
			return $fallbackHome;
		}

		return $layout;
	}

	/**
	 * Layout shown in admin editor (defaults when never saved).
	 *
	 * @param list<string> $allowedWidgets
	 * @return array{rows: list<array<string, mixed>>}
	 */
	private function getAdminLayoutByKey(string $key, array $allowedWidgets): array
	{
		$layout = $this->getLayoutByKey($key, $allowedWidgets);

		if ($layout['rows'] !== []) {
			return $layout;
		}

		if ($key === self::LAYOUT_HEADER) {
			return $this->defaultHeaderLayout();
		}

		if ($key === self::LAYOUT_FOOTER) {
			return $this->defaultFooterLayout();
		}

		return $this->defaultLayout();
	}

	/** @return array{rows: list<array<string, mixed>>} */
	private function defaultLayout(): array
	{
		return [
			'rows' => [
				[
					'id' => 'rowslider',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [
						[
							'id' => 'columnslider',
							'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_slider',
									'type' => 'hook',
									'settings' => ['hook' => 'home_slider'],
								],
							],
						],
					],
				],
				[
					'id' => 'rowproducts',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [
						[
							'id' => 'columnproducts',
							'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_products',
									'type' => 'category_products',
									'settings' => [
										'id_category' => 0,
										'limit' => 12,
										'title' => 'Öne çıkan ürünler',
										'show_link' => 0,
										'layout' => 'list',
									],
								],
							],
						],
					],
				],
				[
					'id' => 'rowelectronics',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [
						[
							'id' => 'columnelectronics',
							'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_electronics',
									'type' => 'category_products',
									'settings' => [
										'id_category' => 4,
										'limit' => 8,
										'title' => 'Elektronik',
										'show_link' => 1,
										'layout' => 'list',
									],
								],
							],
						],
					],
				],
				[
					'id' => 'rowhomebottom',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [
						[
							'id' => 'columnhomebottom',
							'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_home_bottom',
									'type' => 'hook',
									'settings' => ['hook' => 'home_bottom'],
								],
							],
						],
					],
				],
			],
		];
	}

	/** @return array{rows: list<array<string, mixed>>} */
	private function defaultHeaderLayout(): array
	{
		return [
			'rows' => [
				[
					'id' => 'header_top',
					'class' => '',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [
						[
							'id' => 'header_logo',
							'class' => '',
							'width' => ['mobile' => 4, 'tablet' => 3, 'desktop' => 2],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_logo',
									'type' => 'logo',
									'settings' => ['image' => '', 'link' => '', 'alt' => '', 'caption' => ''],
								],
							],
						],
						[
							'id' => 'header_search',
							'class' => '',
							'width' => ['mobile' => 12, 'tablet' => 6, 'desktop' => 6],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_search',
									'type' => 'search',
									'settings' => ['placeholder' => ''],
								],
							],
						],
						[
							'id' => 'header_tools',
							'class' => '',
							'width' => ['mobile' => 8, 'tablet' => 3, 'desktop' => 4],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_tools',
									'type' => 'header_tools',
									'settings' => [
										'show_account' => 1,
										'show_favorites' => 1,
										'show_cart' => 1,
										'show_notifications' => 1,
										'show_menu_btn' => 1,
									],
								],
							],
						],
					],
				],
				[
					'id' => 'header_nav',
					'class' => '',
					'hide' => ['mobile' => 1, 'tablet' => 0, 'desktop' => 0],
					'cols' => [
						[
							'id' => 'header_menu',
							'class' => '',
							'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 12],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_menu',
									'type' => 'hook',
									'settings' => ['hook' => 'main_menu'],
								],
							],
						],
					],
				],
			],
		];
	}

	/** @return array{rows: list<array<string, mixed>>} */
	private function defaultFooterLayout(): array
	{
		return [
			'rows' => [
				[
					'id' => 'footer_main',
					'class' => '',
					'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
					'cols' => [
						[
							'id' => 'footer_brand',
							'class' => '',
							'width' => ['mobile' => 12, 'tablet' => 6, 'desktop' => 3],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_flogo',
									'type' => 'logo',
									'settings' => ['image' => '', 'link' => '', 'alt' => '', 'caption' => ''],
								],
								[
									'id' => 'w_ftext',
									'type' => 'text',
									'settings' => ['html' => ''],
								],
							],
						],
						[
							'id' => 'footer_links',
							'class' => '',
							'width' => ['mobile' => 6, 'tablet' => 3, 'desktop' => 3],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_flinks',
									'type' => 'links',
									'settings' => [
										'title' => 'Links',
										'items' => [
											['label' => 'Contact', 'url' => '/contact'],
										],
									],
								],
							],
						],
						[
							'id' => 'footer_hook',
							'class' => '',
							'width' => ['mobile' => 12, 'tablet' => 12, 'desktop' => 3],
							'hide' => ['mobile' => 0, 'tablet' => 0, 'desktop' => 0],
							'widgets' => [
								[
									'id' => 'w_fhook',
									'type' => 'hook',
									'settings' => ['hook' => 'footer'],
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * @param array<string, mixed> $layout
	 * @param list<string>|null $allowedWidgets
	 * @return array{rows: list<array<string, mixed>>}
	 */
	private function sanitizeLayout(array $layout, ?array $allowedWidgets = null): array
	{
		$allowed = $allowedWidgets !== null ? $allowedWidgets : self::WIDGET_TYPES;
		$rowsIn = is_array($layout['rows'] ?? null) ? $layout['rows'] : [];
		$rows = [];

		foreach (array_slice($rowsIn, 0, 40) as $row) {
			if (!is_array($row)) {
				continue;
			}

			$colsIn = is_array($row['cols'] ?? null) ? $row['cols'] : [];
			$cols = [];

			foreach (array_slice($colsIn, 0, 12) as $col) {
				if (!is_array($col)) {
					continue;
				}

				$widths = $this->normalizeColWidths($col['width'] ?? 12);
				$hide = $this->normalizeHide($col['hide'] ?? null);

				$widgetsIn = is_array($col['widgets'] ?? null) ? $col['widgets'] : [];
				$widgets = [];

				foreach (array_slice($widgetsIn, 0, 20) as $widget) {
					if (!is_array($widget)) {
						continue;
					}

					$type = (string) ($widget['type'] ?? '');

					if (!in_array($type, $allowed, true)) {
						continue;
					}

					$settings = is_array($widget['settings'] ?? null) ? $widget['settings'] : [];
					$widgets[] = [
						'id' => $this->safeId((string) ($widget['id'] ?? ''), 'w'),
						'type' => $type,
						'settings' => $this->sanitizeWidgetSettings($type, $settings),
					];
				}

				$cols[] = [
					'id' => $this->safeHtmlId((string) ($col['id'] ?? ''), 'column'),
					'class' => $this->sanitizeCssClass((string) ($col['class'] ?? '')),
					'width' => $widths,
					'hide' => $hide,
					'widgets' => $widgets,
				];
			}

			if ($cols === []) {
				continue;
			}

			$rows[] = [
				'id' => $this->safeHtmlId((string) ($row['id'] ?? ''), 'row'),
				'class' => $this->sanitizeCssClass((string) ($row['class'] ?? '')),
				'hide' => $this->normalizeHide($row['hide'] ?? null),
				'cols' => $cols,
			];
		}

		return ['rows' => $rows];
	}

	/**
	 * @param mixed $hide
	 * @return array{mobile:int,tablet:int,desktop:int}
	 */
	private function normalizeHide($hide): array
	{
		$map = is_array($hide) ? $hide : [];

		return [
			'mobile' => !empty($map['mobile']) ? 1 : 0,
			'tablet' => !empty($map['tablet']) ? 1 : 0,
			'desktop' => !empty($map['desktop']) ? 1 : 0,
		];
	}

	/**
	 * @param mixed $width
	 * @return array{mobile:int,tablet:int,desktop:int}
	 */
	private function normalizeColWidths($width): array
	{
		// Legacy: single int → col-12 + col-md-X (mobile full, md+ = value)
		if (is_int($width) || (is_string($width) && ctype_digit($width))) {
			$n = (int) $width;

			if (!in_array($n, self::COL_WIDTHS, true)) {
				$n = 12;
			}

			return [
				'mobile' => 12,
				'tablet' => $n,
				'desktop' => $n,
			];
		}

		$map = is_array($width) ? $width : [];
		$out = [
			'mobile' => 12,
			'tablet' => 6,
			'desktop' => 6,
		];

		foreach (['mobile', 'tablet', 'desktop'] as $key) {
			$n = (int) ($map[$key] ?? $out[$key]);

			if (!in_array($n, self::COL_WIDTHS, true)) {
				$n = $out[$key];
			}

			$out[$key] = $n;
		}

		return $out;
	}

	/**
	 * @param array{mobile:int,tablet:int,desktop:int} $widths
	 * @param array{mobile:int,tablet:int,desktop:int} $hide
	 */
	private function buildColClass(array $widths, array $hide = [], string $extraClass = ''): string
	{
		return trim(sprintf(
			'col-%d col-md-%d col-lg-%d t4-builder-col %s %s',
			$widths['mobile'],
			$widths['tablet'],
			$widths['desktop'],
			$this->buildVisibilityClass($hide),
			$extraClass
		));
	}

	/** @param array{mobile:int,tablet:int,desktop:int} $hide */
	private function buildVisibilityClass(array $hide): string
	{
		$classes = [];

		if (!empty($hide['mobile'])) {
			$classes[] = 't4-hide-mobile';
		}

		if (!empty($hide['tablet'])) {
			$classes[] = 't4-hide-tablet';
		}

		if (!empty($hide['desktop'])) {
			$classes[] = 't4-hide-desktop';
		}

		return implode(' ', $classes);
	}

	/** @param array<string, mixed> $settings */
	private function sanitizeWidgetSettings(string $type, array $settings): array
	{
		if ($type === 'banner') {
			return [
				'image' => mb_substr(trim((string) ($settings['image'] ?? '')), 0, 500),
				'link' => mb_substr(trim((string) ($settings['link'] ?? '')), 0, 500),
				'alt' => mb_substr(trim(strip_tags((string) ($settings['alt'] ?? ''))), 0, 120),
			];
		}

		if ($type === 'hook') {
			$hook = trim((string) ($settings['hook'] ?? ''));

			if (!in_array($hook, self::HOOK_CHOICES, true)) {
				$hook = 'home_slider';
			}

			return ['hook' => $hook];
		}

		if ($type === 'category_products') {
			return [
				'id_category' => max(0, (int) ($settings['id_category'] ?? 0)),
				'limit' => max(1, min(48, (int) ($settings['limit'] ?? 8))),
				'title' => mb_substr(trim(strip_tags((string) ($settings['title'] ?? ''))), 0, 120),
				'show_link' => !empty($settings['show_link']) ? 1 : 0,
				'layout' => ((string) ($settings['layout'] ?? 'grid')) === 'list' ? 'list' : 'grid',
			];
		}

		if ($type === 'text') {
			$html = (string) ($settings['html'] ?? '');
			$html = Security::sanitizeHtml($html);

			return ['html' => mb_substr(trim($html), 0, 20000)];
		}

		if ($type === 'logo') {
			return [
				'image' => mb_substr(trim((string) ($settings['image'] ?? '')), 0, 500),
				'link' => mb_substr(trim((string) ($settings['link'] ?? '')), 0, 500),
				'alt' => mb_substr(trim(strip_tags((string) ($settings['alt'] ?? ''))), 0, 120),
				'caption' => mb_substr(trim(strip_tags((string) ($settings['caption'] ?? ''))), 0, 500),
			];
		}

		if ($type === 'links') {
			$itemsIn = is_array($settings['items'] ?? null) ? $settings['items'] : [];
			$items = [];
			$allowedSources = ['custom', 'page', 'cms', 'category'];

			foreach (array_slice($itemsIn, 0, 30) as $item) {
				if (!is_array($item)) {
					continue;
				}

				$source = (string) ($item['source'] ?? 'custom');

				if (!in_array($source, $allowedSources, true)) {
					$source = 'custom';
				}

				$ref = mb_substr(trim(strip_tags((string) ($item['ref'] ?? ''))), 0, 64);
				$label = mb_substr(trim(strip_tags((string) ($item['label'] ?? ''))), 0, 120);
				$url = mb_substr(trim((string) ($item['url'] ?? '')), 0, 500);

				if ($source === 'custom') {
					if ($label === '' || $url === '') {
						continue;
					}

					$ref = '';
				} elseif ($ref === '') {
					continue;
				}

				$items[] = [
					'source' => $source,
					'ref' => $ref,
					'label' => $label,
					'url' => $url,
				];
			}

			return [
				'title' => mb_substr(trim(strip_tags((string) ($settings['title'] ?? ''))), 0, 120),
				'items' => $items,
			];
		}

		if ($type === 'search') {
			return [
				'placeholder' => mb_substr(trim(strip_tags((string) ($settings['placeholder'] ?? ''))), 0, 120),
			];
		}

		if ($type === 'header_tools') {
			return [
				'show_account' => !empty($settings['show_account']) ? 1 : 0,
				'show_favorites' => !empty($settings['show_favorites']) ? 1 : 0,
				'show_cart' => !empty($settings['show_cart']) ? 1 : 0,
				'show_notifications' => !empty($settings['show_notifications']) ? 1 : 0,
				'show_menu_btn' => !empty($settings['show_menu_btn']) ? 1 : 0,
			];
		}

		return [];
	}

	/**
	 * Free-form HTML id — keeps typed value; auto-generates only if empty.
	 */
	private function safeHtmlId(string $id, string $fallbackPrefix): string
	{
		$id = trim($id);
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?? '';

		if ($id === '') {
			$id = $fallbackPrefix . bin2hex(random_bytes(4));
		}

		if (preg_match('/^[0-9]/', $id)) {
			$id = 'id' . $id;
		}

		return mb_substr($id, 0, 64);
	}

	/** Space-separated CSS class names */
	private function sanitizeCssClass(string $class): string
	{
		$parts = preg_split('/\s+/', trim($class)) ?: [];
		$out = [];

		foreach ($parts as $part) {
			$part = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $part) ?? '';

			if ($part === '' || preg_match('/^[0-9]/', $part)) {
				continue;
			}

			$out[$part] = $part;

			if (count($out) >= 12) {
				break;
			}
		}

		return implode(' ', array_values($out));
	}

	private function safeId(string $id, string $prefix): string
	{
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?? '';

		if ($id === '') {
			$id = $prefix . '_' . bin2hex(random_bytes(4));
		}

		return mb_substr($id, 0, 64);
	}

	private function isPreviewEditing(): bool
	{
		return $this->getActivePreviewDraft() !== null;
	}

	private function widgetPlaceholderLabel(string $type): string
	{
		$labels = [
			'banner' => $this->l('Banner'),
			'hook' => $this->l('Hook'),
			'category_products' => $this->l('Category products'),
			'text' => $this->l('Text / HTML'),
			'logo' => $this->l('Logo'),
			'links' => $this->l('Links'),
			'search' => $this->l('Search bar'),
			'header_tools' => $this->l('Header tools'),
		];

		return $labels[$type] ?? $type;
	}

	private function handleAdminAjax(string $adminToken): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$postToken = (string) Tools::getValue('token');

		if ($postToken === '' || !hash_equals($adminToken, $postToken)) {
			http_response_code(403);
			echo json_encode(['ok' => false, 'message' => $this->l('Invalid request')]);
			exit;
		}

		$action = (string) Tools::getValue('action');

		try {
			if ($action === 'publish') {
				$home = $this->decodePostedLayout('home', self::WIDGETS_HOME);
				$header = $this->decodePostedLayout('header', self::WIDGETS_HEADER);
				$footer = $this->decodePostedLayout('footer', self::WIDGETS_FOOTER);
				$copyright = mb_substr(trim((string) Tools::getValue('copyright')), 0, 500);

				$this->setSetting(self::LAYOUT_HOME, json_encode($home, JSON_UNESCAPED_UNICODE));
				$this->setSetting(self::LAYOUT_HEADER, json_encode($header, JSON_UNESCAPED_UNICODE));
				$this->setSetting(self::LAYOUT_FOOTER, json_encode($footer, JSON_UNESCAPED_UNICODE));
				$this->setSetting(self::SETTING_COPYRIGHT, $copyright);

				$logo = mb_substr(trim((string) Tools::getValue('logo')), 0, 500);
				$favicon = mb_substr(trim((string) Tools::getValue('favicon')), 0, 500);

				if (array_key_exists('logo', $_POST)) {
					$this->setSetting(self::SETTING_LOGO, $logo);
				}

				if (array_key_exists('favicon', $_POST)) {
					$this->setSetting(self::SETTING_FAVICON, $favicon);
				}

				$chrome = $this->readPostedChrome();
				$this->setSetting(
					self::SETTING_CHROME,
					(string) json_encode($this->chromeForStorage($chrome), JSON_UNESCAPED_UNICODE)
				);

				$postedColors = is_array($_POST['colors'] ?? null) ? $_POST['colors'] : [];

				if ($postedColors !== []) {
					$colors = [];

					foreach (array_keys(Theme4Assets::DEFAULT_COLORS) as $key) {
						$colors[$key] = trim((string) ($postedColors[$key] ?? ''));
					}

					$structural = Theme4Assets::readStructural();
					$siteWidth = $this->getSetting(self::SETTING_SITE_WIDTH);
					$font = $this->getSetting(self::SETTING_FONT);

					if ($siteWidth !== '') {
						$structural['container'] = $siteWidth;
					}

					if ($font !== '') {
						$structural['font'] = $font;
					}

					Theme4Assets::writeColors($colors, $structural);
					$this->writeThemeSettingsCss(
						$siteWidth !== '' ? $siteWidth : '1320px',
						$font !== '' ? $font : "'Inter', system-ui, -apple-system, sans-serif"
					);
				}

				if (isset($_POST['site_width']) || isset($_POST['font_family']) || isset($_POST['custom_css']) || isset($_POST['custom_js'])) {
					$siteWidth = mb_substr(trim((string) Tools::getValue('site_width')), 0, 40);
					$font = mb_substr(trim((string) Tools::getValue('font_family')), 0, 200);

					if ($siteWidth === '') {
						$siteWidth = $this->getSetting(self::SETTING_SITE_WIDTH) ?: '1320px';
					}

					if ($font === '') {
						$font = $this->getSetting(self::SETTING_FONT) ?: "'Inter', system-ui, -apple-system, sans-serif";
					}

					$this->setSetting(self::SETTING_SITE_WIDTH, $siteWidth);
					$this->setSetting(self::SETTING_FONT, $font);
					$this->writeThemeSettingsCss($siteWidth, $font);
					$this->syncStructuralCss($siteWidth, $font);

					if (array_key_exists('custom_css', $_POST)) {
						Theme4Assets::writeCustomCss((string) $_POST['custom_css']);
					}

					if (array_key_exists('custom_js', $_POST)) {
						Theme4Assets::writeCustomJs((string) $_POST['custom_js']);
					}
				}

				$this->savePreviewDraft([
					'home' => $home,
					'header' => $header,
					'footer' => $footer,
					'copyright' => $copyright,
					'chrome' => $this->chromeForStorage($chrome),
				]);

				echo json_encode(['ok' => true, 'message' => $this->l('Layout saved')]);
				exit;
			}

			if ($action !== 'save_draft') {
				http_response_code(400);
				echo json_encode(['ok' => false]);
				exit;
			}

			$this->savePreviewDraft([
				'home' => $this->decodePostedLayout('home', self::WIDGETS_HOME),
				'header' => $this->decodePostedLayout('header', self::WIDGETS_HEADER),
				'footer' => $this->decodePostedLayout('footer', self::WIDGETS_FOOTER),
				'copyright' => mb_substr(trim((string) Tools::getValue('copyright')), 0, 500),
				'chrome' => $this->chromeForStorage($this->readPostedChrome()),
			]);

			echo json_encode(['ok' => true]);
			exit;
		} catch (Throwable $e) {
			http_response_code(500);
			echo json_encode(['ok' => false, 'message' => $this->l('Save failed')]);
			exit;
		}
	}

	/**
	 * @param list<string> $allowedWidgets
	 * @return array{rows: list<array<string, mixed>>}
	 */
	private function decodePostedLayout(string $field, array $allowedWidgets): array
	{
		$raw = (string) Tools::getValue($field);
		$decoded = json_decode($raw, true);

		if (!is_array($decoded)) {
			return ['rows' => []];
		}

		return $this->sanitizeLayout($decoded, $allowedWidgets);
	}

	private function ensurePreviewToken(): string
	{
		$token = $this->getSetting(self::SETTING_PREVIEW_TOKEN);
		$expires = (int) $this->getSetting(self::SETTING_PREVIEW_EXPIRES);

		if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token) || $expires < (time() + 600)) {
			$token = bin2hex(random_bytes(16));
			$this->setSetting(self::SETTING_PREVIEW_TOKEN, $token);
		}

		$this->setSetting(self::SETTING_PREVIEW_EXPIRES, (string) (time() + 28800));

		return $token;
	}

	/**
	 * @param array{home?: mixed, header?: mixed, footer?: mixed, copyright?: mixed, chrome?: mixed} $draft
	 */
	private function savePreviewDraft(array $draft): void
	{
		$this->ensurePreviewToken();
		$this->setSetting(
			self::SETTING_PREVIEW_DRAFT,
			(string) json_encode($draft, JSON_UNESCAPED_UNICODE)
		);
	}

	/** @return array<string, mixed>|null */
	private function getActivePreviewDraft(): ?array
	{
		if ($this->previewDraftResolved) {
			return $this->previewDraftCache;
		}

		$this->previewDraftResolved = true;
		$this->previewDraftCache = null;

		if (!defined('IN_SCRIPT') || defined('IN_ADMIN')) {
			return null;
		}

		$token = trim((string) Tools::getValue('t4_preview'));

		if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
			return null;
		}

		$stored = $this->getSetting(self::SETTING_PREVIEW_TOKEN);
		$expires = (int) $this->getSetting(self::SETTING_PREVIEW_EXPIRES);

		if ($stored === '' || $expires < time() || !hash_equals($stored, $token)) {
			return null;
		}

		$decoded = json_decode($this->getSetting(self::SETTING_PREVIEW_DRAFT), true);

		if (!is_array($decoded)) {
			return null;
		}

		$this->previewDraftCache = $decoded;

		return $this->previewDraftCache;
	}

	private function getSetting(string $title): string
	{
		$title = trim($title);

		if ($title === '' || !Validate::isGenericName($title)) {
			return '';
		}

		$value = DB::getValue(
			'SELECT value FROM theme4_settings WHERE title = ? LIMIT 1',
			[$title]
		);

		return $value !== false ? (string) $value : '';
	}

	private function setSetting(string $title, string $value): void
	{
		$title = trim($title);

		if ($title === '' || !Validate::isGenericName($title)) {
			return;
		}

		$exists = DB::getValue(
			'SELECT id FROM theme4_settings WHERE title = ? LIMIT 1',
			[$title]
		);

		if ($exists !== false) {
			DB::update('theme4_settings', [
				'value' => $value,
			], 'title = :where_title', ['where_title' => $title]);

			return;
		}

		DB::insert('theme4_settings', [
			'title' => $title,
			'value' => $value,
		]);
	}
}
