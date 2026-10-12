<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$flash = '';
	$flashType = 'info';
	$config = SecuritySettings::getConfig();
	$checklist = SecuritySettings::getChecklist();

	if (Tools::isSubmit('saveSecurity')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = SecuritySettings::saveConfig([
				SecuritySettings::KEY_ADMIN_IDLE => Tools::getValue(SecuritySettings::KEY_ADMIN_IDLE),
				SecuritySettings::KEY_CUSTOMER_IDLE => Tools::getValue(SecuritySettings::KEY_CUSTOMER_IDLE),
				SecuritySettings::KEY_ADMIN_LOGIN_MAX => Tools::getValue(SecuritySettings::KEY_ADMIN_LOGIN_MAX),
				SecuritySettings::KEY_ADMIN_LOGIN_WINDOW => Tools::getValue(SecuritySettings::KEY_ADMIN_LOGIN_WINDOW),
				SecuritySettings::KEY_CUSTOMER_LOGIN_MAX => Tools::getValue(SecuritySettings::KEY_CUSTOMER_LOGIN_MAX),
				SecuritySettings::KEY_CUSTOMER_LOGIN_WINDOW => Tools::getValue(SecuritySettings::KEY_CUSTOMER_LOGIN_WINDOW),
				SecuritySettings::KEY_PASSWORD_MIN => Tools::getValue(SecuritySettings::KEY_PASSWORD_MIN),
				SecuritySettings::KEY_PASSWORD_COMPLEXITY => Tools::getValue(SecuritySettings::KEY_PASSWORD_COMPLEXITY),
				SecuritySettings::KEY_FORCE_HTTPS => Tools::getValue(SecuritySettings::KEY_FORCE_HTTPS),
				SecuritySettings::KEY_HSTS => Tools::getValue(SecuritySettings::KEY_HSTS),
				SecuritySettings::KEY_X_FRAME => Tools::getValue(SecuritySettings::KEY_X_FRAME),
			]);

			$config = SecuritySettings::getConfig();
			$checklist = SecuritySettings::getChecklist();
			$flash = $result['message'];
			$flashType = $result['success'] ? 'success' : 'danger';
		}
	}

	if (Tools::isSubmit('regenerateShopToken')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} elseif (Admin::isDemoMode()) {
			$flash = adminT('Demo mode: some edits are not allowed');
			$flashType = 'danger';
		} else {
			$result = SecuritySettings::regenerateShopToken();
			$flash = $result['message'];
			$flashType = 'success';
			$checklist = SecuritySettings::getChecklist();
		}
	}

	$smarty->assign([
		'secConfig' => $config,
		'secChecklist' => $checklist,
		'secShopTokenMasked' => SecuritySettings::maskedShopToken(),
		'secShopToken' => (string) Settings::get('SHOP_TOKEN'),
		'secAdminUri' => Admin::uri(),
		'secRegenConfirm' => Security::jsString(adminT('Regenerate cron token? Scheduled jobs must be updated.')),
		'flash' => $flash,
		'flashType' => $flashType,
	]);

	AdminPage::add('security', 'Security');
