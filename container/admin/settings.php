<?php

	if (!defined('IN_ADMIN')) {

		exit;

	}

	if (Tools::getValue('ajax') === 'product-search') {

		header('Content-Type: application/json; charset=utf-8');

		$token = (string) Tools::getValue('token');

		if (!hash_equals((string) $adminToken, $token)) {

			echo json_encode(['success' => false, 'message' => adminT('Invalid request')]);

			exit;

		}

		$q = trim((string) Tools::getValue('q', ''));

		$sql = 'SELECT id_product, product_name, price FROM products WHERE active = 1';

		$params = [];

		if ($q !== '') {

			$sql .= ' AND (product_name LIKE ? OR stock_code LIKE ? OR CAST(id_product AS CHAR) = ?)';

			$like = '%' . $q . '%';

			$params = [$like, $like, $q];

		}

		$sql .= ' ORDER BY product_name ASC LIMIT 40';

		$rows = DB::execute($sql, $params) ?: [];

		$items = [];

		foreach ($rows as $row) {

			$product = Product::getById((int) $row['id_product']);

			if (!$product || empty($product['in_stock'])) {

				continue;

			}

			$items[] = [

				'id_product' => (int) $product['id_product'],

				'product_name' => (string) $product['product_name'],

				'price_formatted' => (string) ($product['price_formatted'] ?? Tools::displayPrice((float) $row['price'])),

			];

			if (count($items) >= 20) {

				break;

			}

		}

		echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);

		exit;

	}



	$flash = '';

	$flashType = 'info';

	$editableKeys = Settings::getEditableKeys();

	$settingsTab = (string) Tools::getValue('tab', 'general');

	$allowedTabs = ['general', 'contact', 'email', 'orders', 'returns', 'payment'];



	if (!in_array($settingsTab, $allowedTabs, true)) {

		$settingsTab = 'general';

	}



	$values = Settings::getAllForAdmin();

	$mailHeaderStored = trim((string) ($values['MAIL_HEADER'] ?? ''));

	$mailFooterStored = trim((string) ($values['MAIL_FOOTER'] ?? ''));

	$values['MAIL_HEADER'] = $mailHeaderStored !== '' ? $values['MAIL_HEADER'] : Mail::getDefaultEmailHeader();

	$values['MAIL_FOOTER'] = $mailFooterStored !== '' ? $values['MAIL_FOOTER'] : Mail::getDefaultEmailFooter();

	$maintenanceStored = trim((string) ($values['SHOP_MAINTENANCE_MESSAGE'] ?? ''));

	$values['SHOP_MAINTENANCE_MESSAGE'] = $maintenanceStored !== ''

		? $values['SHOP_MAINTENANCE_MESSAGE']

		: StoreStatus::getDefaultMaintenanceMessage();

	$mailConfigured = Mail::isConfigured();

	$usesSmtp = Mail::usesSmtp();



	if (Tools::isSubmit('saveSettings')) {

		$postToken = (string) Tools::getValue('token');

		$settingsTab = (string) Tools::getValue('settings_tab', $settingsTab);



		if (!in_array($settingsTab, $allowedTabs, true)) {

			$settingsTab = 'general';

		}



		if (!hash_equals($adminToken, $postToken)) {

			$flash = adminT('Invalid request');

			$flashType = 'danger';

		} else {

			$ok = true;



			$tabGroups = [

				'general' => ['general'],

				'contact' => ['contact'],

				'email' => ['mail', 'smtp', 'mail_template'],

				'orders' => ['orders'],

				'returns' => ['returns'],

				'payment' => ['payment'],

			];

			$activeGroups = $tabGroups[$settingsTab] ?? [];



			foreach ($editableKeys as $key => $keyMeta) {

				$keyGroup = (string) ($keyMeta['group'] ?? '');

				if ($activeGroups !== [] && !in_array($keyGroup, $activeGroups, true)) {

					continue;

				}

				if ($key === 'SMTP_PASS' && trim((string) Tools::getValue('SMTP_PASS')) === '') {

					continue;

				}



				$fieldType = (string) ($editableKeys[$key]['type'] ?? 'text');



				if ($fieldType === 'html') {

					$value = (string) Tools::getValue($key);

				} elseif ($fieldType === 'checkbox') {

					$value = Tools::getValue($key) ? '1' : '0';

				} else {

					$value = trim((string) Tools::getValue($key));

				}



				if ($key === 'ORDER_REF_PREFIX') {

					$value = Order::sanitizeReferencePrefix($value);

				} elseif ($key === 'ORDER_REF_SUFFIX_MODE') {

					$modes = array_keys(Order::getReferenceSuffixModes());

					if (!in_array(strtolower($value), $modes, true)) {

						$value = 'sequential';

					}

				} elseif ($key === 'ORDER_REF_PAD') {

					$value = (string) max(3, min(10, (int) $value ?: 5));

				} elseif ($key === 'GIFT_WRAP_FEE') {

					$value = (string) max(0, round((float) str_replace(',', '.', $value), 2));

				} elseif ($key === 'ORDER_SUCCESS_NOTE') {

					$value = trim(strip_tags($value));

					if (function_exists('mb_substr')) {

						$value = mb_substr($value, 0, 500);

					} else {

						$value = substr($value, 0, 500);

					}

				} elseif ($key === 'MAIL_HEADER' && $value === Mail::getDefaultEmailHeader()) {

					$value = '';

				} elseif ($key === 'MAIL_FOOTER' && $value === Mail::getDefaultEmailFooter()) {

					$value = '';

				} elseif ($key === 'SHOP_MAINTENANCE_MESSAGE' && $value === StoreStatus::getDefaultMaintenanceMessage()) {

					$value = '';

				} elseif ($key === 'DEFAULT_PAYMENT_METHOD') {

					$paymentMethods = Module::getPaymentMethods();

					$validIds = [];

					foreach ($paymentMethods as $methodRow) {

						$id = (string) ($methodRow['id'] ?? '');

						if ($id !== '') {

							$validIds[$id] = true;

						}

					}

					if ($value !== '' && $validIds !== [] && empty($validIds[$value])) {

						$value = '';

					}

				} elseif ($key === 'DEFAULT_CARGO_ID') {

					$value = (string) max(0, (int) $value);

				} elseif ($key === 'SANA_OZEL_PRODUCT_IDS') {

					$ids = [];

					foreach (preg_split('/[,\s]+/', $value) ?: [] as $id) {

						$id = (int) $id;

						if ($id > 0) {

							$ids[$id] = $id;

						}

					}

					$value = implode(',', array_slice(array_values($ids), 0, 12));

				}



				if (!Settings::set($key, $value)) {

					$ok = false;

				}

			}



			if ($ok) {

				if (class_exists('Cargo')) {

					$cargoId = (int) Settings::get('DEFAULT_CARGO_ID');

					if ($cargoId > 0) {

						Cargo::setDefaultId($cargoId);

					}

				}

				header('Location: ' . Admin::url('settings') . '?tab=' . rawurlencode($settingsTab) . '&saved=1');

				exit;

			}



			$flash = adminT('Some settings could not be saved');

			$flashType = 'danger';

			$values = Settings::getAllForAdmin();

			$mailHeaderStored = trim((string) ($values['MAIL_HEADER'] ?? ''));

			$mailFooterStored = trim((string) ($values['MAIL_FOOTER'] ?? ''));

			$values['MAIL_HEADER'] = $mailHeaderStored !== '' ? $values['MAIL_HEADER'] : Mail::getDefaultEmailHeader();

			$values['MAIL_FOOTER'] = $mailFooterStored !== '' ? $values['MAIL_FOOTER'] : Mail::getDefaultEmailFooter();

			$maintenanceStored = trim((string) ($values['SHOP_MAINTENANCE_MESSAGE'] ?? ''));

			$values['SHOP_MAINTENANCE_MESSAGE'] = $maintenanceStored !== ''

				? $values['SHOP_MAINTENANCE_MESSAGE']

				: StoreStatus::getDefaultMaintenanceMessage();

			$mailConfigured = Mail::isConfigured();

			$usesSmtp = Mail::usesSmtp();

		}

	}



	if (Tools::getValue('saved') === '1' && $flash === '') {

		$flash = adminT('Settings saved');

		$flashType = 'success';

	}



	if (Tools::isSubmit('testMail')) {

		$postToken = (string) Tools::getValue('token');



		if (!hash_equals($adminToken, $postToken)) {

			$flash = adminT('Invalid request');

			$flashType = 'danger';

		} elseif (!$mailConfigured) {

			$flash = $usesSmtp

				? adminT('Complete SMTP settings. Enter server, user and password.')

				: adminT('Enter contact or sender email for PHP mail().');

			$flashType = 'danger';

		} else {

			$testTo = trim((string) Tools::getValue('test_email'));

			if ($testTo === '') {

				$testTo = Settings::get('SMTP_FROM_EMAIL') ?: Settings::get('SMTP_USER') ?: Settings::get('CONTACT_EMAIL');

			}



			$driverLabel = $usesSmtp ? 'SMTP' : 'PHP mail()';



			if ($testTo === '' || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {

				$flash = adminT('Enter a valid email address for the test');

				$flashType = 'danger';

			} elseif (Mail::sendSample($testTo, (string) Tools::getValue('test_mail_type', 'layout'))) {

				$flash = adminT('Test email sent') . ' (' . $driverLabel . '): ' . $testTo;

				$flashType = 'success';

			} else {

				$error = Mail::getLastError();

				$flash = adminT('Test email could not be sent') . ' (' . $driverLabel . ')' . ($error !== '' ? ': ' . $error : '');

				$flashType = 'danger';

			}

		}

	}



	if (Tools::isSubmit('saveWebApi') || Tools::isSubmit('regenWebApiKey')) {

		$flash = adminT('Web API management moved: Admin → API menu');

		$flashType = 'info';

	}



	if ((int) ($values['DEFAULT_CARGO_ID'] ?? 0) <= 0 && class_exists('Cargo')) {

		$defaultCargo = Cargo::getDefault();

		if ($defaultCargo) {

			$values['DEFAULT_CARGO_ID'] = (string) (int) $defaultCargo['id_cargo'];

		}

	}

	if (trim((string) ($values['DEFAULT_PAYMENT_METHOD'] ?? '')) === '') {

		$values['DEFAULT_PAYMENT_METHOD'] = Order::resolveDefaultPaymentMethod();

	}

	$sanaOzelProducts = [];

	$sanaOzelIds = preg_split('/[,\s]+/', (string) ($values['SANA_OZEL_PRODUCT_IDS'] ?? '')) ?: [];

	foreach ($sanaOzelIds as $sanaId) {

		$sanaId = (int) $sanaId;

		if ($sanaId <= 0) {

			continue;

		}

		$product = Product::getById($sanaId);

		if ($product) {

			$sanaOzelProducts[] = [

				'id_product' => (int) $product['id_product'],

				'product_name' => (string) $product['product_name'],

				'price_formatted' => (string) ($product['price_formatted'] ?? ''),

			];

		}

	}

	$smarty->assign([

		'settingsTab' => $settingsTab,

		'settingsValues' => $values,

		'settingsKeys' => $editableKeys,

		'shopCurrencyCode' => Currency::getShopCurrency(),

		'shopCurrencyLabel' => Currency::label(),

		'orderRefPreview' => Order::previewReference(),

		'orderRefNextId' => Order::peekNextOrderId(),

		'orderRefModes' => Order::getReferenceSuffixModes(),

		'orderRefActiveMode' => Order::getReferenceSettings()['suffix_mode'],

		'flash' => $flash,

		'flashType' => $flashType,

		'mailConfigured' => $mailConfigured,

		'usesSmtp' => $usesSmtp,

		'mailLayoutPlaceholders' => Mail::getLayoutPlaceholders(),

		'mailSampleTypes' => Mail::getSampleTypes(),

		'mailTemplatePreview' => Mail::previewTemplate(

			'<h2 style="margin:0 0 12px;font-size:18px;">Örnek e-posta</h2>'
			. '<p style="margin:0 0 12px;">Merhaba <strong>Ali Yılmaz</strong>,</p>'
			. Mail::infoBox('<p style="margin:0;">Bu alan her e-postada değişen içeriktir. Sipariş bildirimleri, kampanyalar ve diğer tüm mailler buraya yerleştirilir.</p>')
			. Mail::ctaButton('#', 'Örnek buton')

		),

		'shopIsActive' => StoreStatus::isActive(),

		'currentClientIp' => StoreStatus::getClientIp(),

		'adminUseEditor' => in_array($settingsTab, ['general', 'email'], true),

		'readOnlySettings' => [

			'DOMAIN' => Settings::get('DOMAIN'),

			'FOLDER' => Settings::get('FOLDER'),

		],

		'paymentMethods' => Module::getPaymentMethods(),

		'cargoCompanies' => class_exists('Cargo') ? Cargo::getList(true) : [],

		'sanaOzelProducts' => $sanaOzelProducts,

		'productSearchUrl' => Admin::url('settings') . '?tab=payment&ajax=product-search',

	]);



	AdminPage::add('settings', 'Site settings');

