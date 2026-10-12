<?php
	if (!defined('IN_SCRIPT')) {
		exit;
	}

	$css = 'pages.css';
	$idOrder = (int) Tools::getValue('id');
	if ($idOrder <= 0) {
		$idOrder = (int) Tools::getValue('id_order');
	}
	if ($idOrder <= 0) {
		$idOrder = (int) Tools::getValue('order');
	}

	$reference = trim((string) Tools::getValue('ref'));

	// PayTR / KuveytTürk gibi ödemelerde önce callback siparişi oluşturur; ref ile kısa süre bekleyebiliriz
	if ($idOrder <= 0 && $reference !== '') {
		for ($attempt = 0; $attempt < 8; $attempt++) {
			$idOrder = (int) DB::getValue('SELECT id_order FROM orders WHERE reference = ? LIMIT 1', [$reference]);

			if ($idOrder > 0) {
				break;
			}

			usleep(500000);
		}
	}

	// Payment return: grant only when this browser session started the checkout.
	// Knowing a public order reference is not enough (IDOR).
	if ($idOrder > 0) {
		$orderRef = (string) DB::getValue('SELECT reference FROM orders WHERE id_order = ? LIMIT 1', [$idOrder]);
		$pending = $_SESSION['pending_order_data'] ?? null;
		$allowGrant = false;

		if (is_array($pending) && $orderRef !== '') {
			foreach ($pending as $key => $value) {
				if (!is_scalar($value)) {
					continue;
				}

				$keyName = strtolower((string) $key);

				if ($keyName !== 'reference' && substr($keyName, -10) !== '_reference') {
					continue;
				}

				$candidate = trim((string) $value);

				if ($candidate !== '' && hash_equals($orderRef, $candidate)) {
					$allowGrant = true;
					break;
				}
			}
		}

		if ($allowGrant) {
			Order::grantGuestOrderAccess($idOrder);
		}
	}

	$order = Order::getByIdForViewer($idOrder);

	if ($order) {
		Order::clearPendingPayment();
		Cart::clear();
	}

	if (!$order) {
		http_response_code(404);
		$skipPageRender = true;
		$page->add('404', translate('Page Not Found'));
		return;
	}

	$pageTitle = translate('Checkout success title');
	$pageDesc = translate('Checkout success description');

	$guestRegisterError = '';
	$guestRegisterNotice = '';
	$guestFieldError = '';
	$isGuestOrder = empty($isLoggedIn) && (int) ($order['id_user'] ?? 0) === 0;
	$guestForm = [
		'full_name' => (string) ($order['customer_name'] ?? ''),
		'email' => (string) ($order['customer_email'] ?? ''),
		'phone' => (string) ($order['customer_phone'] ?? ''),
		'address_city' => (string) ($order['address_city'] ?? ''),
		'address_district' => (string) ($order['address_district'] ?? ''),
		'address_text' => (string) ($order['address_text'] ?? ''),
	];

	if ($isGuestOrder && Tools::isSubmit('registerFromOrder')) {
		$guestForm['full_name'] = trim((string) Tools::getValue('full_name', $guestForm['full_name']));
		$guestForm['email'] = trim((string) Tools::getValue('email', $guestForm['email']));
		$guestForm['phone'] = (string) Tools::getValue('phone', $guestForm['phone']);
		$guestForm['address_city'] = trim((string) Tools::getValue('address_city', $guestForm['address_city']));
		$guestForm['address_district'] = trim((string) Tools::getValue('address_district', $guestForm['address_district']));
		$guestForm['address_text'] = trim((string) Tools::getValue('address_text', $guestForm['address_text']));

		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($token, $postToken)) {
			$guestRegisterError = translate('Invalid request, please refresh and try again');
		} else {
			$password = (string) Tools::getValue('password');
			$password2 = (string) Tools::getValue('password2');

			if ($password !== $password2) {
				$guestRegisterError = translate('Passwords do not match');
			} else {
				$result = Customer::register(
					$guestForm['full_name'],
					$guestForm['phone'],
					$password,
					$guestForm['email']
				);

				if (!empty($result['success'])) {
					$idUser = (int) ($result['id_user'] ?? 0);

					Order::updateGuestContact((int) $order['id_order'], [
						'customer_name' => $guestForm['full_name'],
						'customer_phone' => $guestForm['phone'],
						'customer_email' => $guestForm['email'],
						'address_city' => $guestForm['address_city'],
						'address_district' => $guestForm['address_district'],
						'address_text' => $guestForm['address_text'],
					]);

					if ($idUser > 0) {
						Order::attachGuestOrdersToUser($idUser);

						if (class_exists('Address')) {
							Address::save($idUser, [
								'full_name' => $guestForm['full_name'],
								'phone' => $guestForm['phone'],
								'city' => $guestForm['address_city'],
								'district' => $guestForm['address_district'],
								'address_text' => $guestForm['address_text'],
								'company_name' => (string) ($order['company_name'] ?? ''),
								'tax_office' => (string) ($order['tax_office'] ?? ''),
								'tax_number' => (string) ($order['tax_number'] ?? ''),
								'label' => translate('Address'),
								'is_default' => 1,
							]);
						}
					}

					if (Customer::isLoggedIn()) {
						header('Location: ' . rtrim((string) $domain, '/') . '/my-account?order=' . (int) $order['id_order']);
						exit;
					}

					$guestRegisterNotice = $result['message'];
					$isGuestOrder = false;
				} else {
					$guestRegisterError = (string) ($result['message'] ?? translate('An error occurred'));
				}
			}
		}

		if ($guestRegisterError === translate('This phone is already registered')) {
			$guestFieldError = 'phone';
		} elseif ($guestRegisterError === translate('This email is already registered')) {
			$guestFieldError = 'email';
		}
	}

	$passwordPolicy = SecuritySettings::getPasswordPolicyForJs();

	$smarty->assign([
		'order' => $order,
		'isGuestOrder' => $isGuestOrder,
		'guestForm' => $guestForm,
		'guestRegisterError' => $guestRegisterError,
		'guestRegisterNotice' => $guestRegisterNotice,
		'guestFieldError' => $guestFieldError,
		'guestRegisterCanLogin' => $guestFieldError !== '',
		'passwordPolicy' => $passwordPolicy,
		'passwordPolicyJson' => Security::jsonForHtmlScript($passwordPolicy),
		'orderSuccessNote' => trim((string) Settings::get('ORDER_SUCCESS_NOTE')),
	]);

	if (class_exists('Module', false)) {
		Module::refreshHook($smarty, 'order_confirmation', ['order' => $order]);
	}
