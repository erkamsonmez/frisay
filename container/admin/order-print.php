<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	require_once dirname(__DIR__, 2) . '/core/Cargo.php';
	require_once dirname(__DIR__, 2) . '/core/marketplace/Code128Barcode.php';

	if (!class_exists('SiteAssets', false)) {
		require_once dirname(__DIR__, 2) . '/core/SiteAssets.php';
	}

	$ids = [];
	$bulk = trim((string) Tools::getValue('ids', ''));

	if ($bulk !== '') {
		foreach (preg_split('/[,\s|]+/', $bulk) ?: [] as $part) {
			$id = (int) $part;

			if ($id > 0 && !in_array($id, $ids, true)) {
				$ids[] = $id;
			}
		}
	}

	if ($ids === []) {
		$id = (int) Tools::getValue('id');

		if ($id > 0) {
			$ids[] = $id;
		}
	}

	/**
	 * @return array<string, mixed>|null
	 */
	$enrichLabelOrder = static function (int $idOrder): ?array {
		$order = Order::getByIdAdmin($idOrder);

		if (!$order) {
			return null;
		}

		$cargoName = trim((string) ($order['cargo_company'] ?? ''));
		$cargo = $cargoName !== '' ? Cargo::getByName($cargoName) : null;
		$order['cargo_name'] = $cargoName;
		$order['cargo_logo_url'] = Cargo::resolveLogoUrl($cargoName, $cargo ? (int) $cargo['id_cargo'] : null) ?? '';
		$order['tracking_number'] = trim((string) ($order['tracking_number'] ?? ''));

		$barcodeValue = $order['tracking_number'] !== ''
			? $order['tracking_number']
			: trim((string) ($order['reference'] ?? (string) $order['id_order']));

		$order['barcode_value'] = $barcodeValue;
		$order['barcode_svg'] = \Marketplace\Code128Barcode::toSvg($barcodeValue, 64, 1.55);

		$addressParts = array_filter([
			trim((string) ($order['address_text'] ?? '')),
			trim(
				trim((string) ($order['address_district'] ?? ''))
				. (
					trim((string) ($order['address_district'] ?? '')) !== ''
					&& trim((string) ($order['address_city'] ?? '')) !== ''
						? ' / '
						: ''
				)
				. trim((string) ($order['address_city'] ?? ''))
			),
		], static function ($v) {
			return $v !== '';
		});
		$order['label_address'] = implode(' ', $addressParts);
		$order['label_date'] = date('d.m.Y H:i', strtotime((string) ($order['date_add'] ?? 'now')) ?: time());

		foreach ($order['items'] as &$item) {
			$idProduct = (int) ($item['id_product'] ?? 0);
			$idImage = 0;

			if ($idProduct > 0) {
				$idImage = (int) (DB::getValue(
					'SELECT id_image FROM images WHERE id_product = ? AND cover = 1 LIMIT 1',
					[$idProduct]
				) ?: 0);

				if ($idImage <= 0) {
					$idImage = (int) (DB::getValue(
						'SELECT id_image FROM images WHERE id_product = ? ORDER BY id_image ASC LIMIT 1',
						[$idProduct]
					) ?: 0);
				}
			}

			$item['image_url'] = Product::getImageUrl($idImage > 0 ? $idImage : null);
			$sku = trim((string) ($item['stock_code'] ?? ''));

			if ($sku === '') {
				$sku = (string) ((int) ($item['id_product'] ?? 0) ?: '');
			}

			$item['sku'] = $sku;
			$qty = (float) ($item['qty'] ?? 1);
			$item['qty_display'] = (abs($qty - (int) $qty) < 0.001)
				? (string) (int) $qty
				: rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
		}
		unset($item);

		return $order;
	};

	$printOrders = [];

	foreach ($ids as $idOrder) {
		$enriched = $enrichLabelOrder($idOrder);

		if ($enriched) {
			$printOrders[] = $enriched;
		}
	}

	if ($printOrders === []) {
		http_response_code(404);
		AdminPage::add('404', 'Order not found', true);
		return;
	}

	$storeLogo = '';

	if (class_exists('SiteAssets', false)) {
		$storeLogo = SiteAssets::getLogoUrl('header');

		if ($storeLogo === '' || !preg_match('#^https?://#i', $storeLogo)) {
			$storeLogo = SiteAssets::resolveLogoUrl('header');
		}
	}

	$smarty->assign([
		'printOrders' => $printOrders,
		'printAuto' => Tools::getValue('auto') === '1',
		'printSiteName' => Settings::get('SITE_NAME'),
		'printStoreLogo' => $storeLogo,
		'domain' => rtrim((string) ($GLOBALS['domain'] ?? Settings::get('DOMAIN')), '/') . '/',
	]);

	$title = count($printOrders) === 1
		? (adminT('Shipping label') . ' #' . $printOrders[0]['reference'])
		: (adminT('Shipping labels') . ' (' . count($printOrders) . ')');

	AdminPage::add('order-print', $title, true);
