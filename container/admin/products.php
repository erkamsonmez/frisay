<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}
	$sonuc = '';
	$sonucType = 'success';
	if (Tools::isSubmit('deleteProduct'))
	{
		$formToken = md5(Tools::getValue('deleteProduct'));
		if (md5($adminToken) == $formToken)
		{
			$idProduct 	= (int)Tools::getValue('idProduct');
			$isHave		= DB::getRow('products', 'id_product = '.(int)$idProduct.'', 'product_name');
			if ($isHave)
			{
				$result = Product::deleteById($idProduct);
				if (!empty($result['success'])) {
					$sonuc = $isHave.' ürünü başarıyla silindi';
				} else {
					$sonuc = (string) ($result['message'] ?? 'Ürün silinemedi');
					$sonucType = 'danger';
				}
			}
		}
	}

	if (Tools::isSubmit('copyProduct'))
	{
		$formToken = md5(Tools::getValue('copyProduct'));
		if (md5($adminToken) === $formToken)
		{
			$idProduct = (int) Tools::getValue('idProduct');
			$result = Product::duplicateById($idProduct);
			$sonuc = (string) ($result['message'] ?? 'Ürün kopyalanamadı');
			$sonucType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['success']) && !empty($result['id'])) {
				$_SESSION['admin_flash'] = ['message' => $sonuc, 'type' => $sonucType];
				header('Location: ' . Admin::url('product') . '?id=' . (int) $result['id']);
				exit;
			}
		}
	}

	if (Tools::isSubmit('toggleProductActive'))
	{
		$formToken = md5(Tools::getValue('toggleProductActive'));
		if (md5($adminToken) === $formToken)
		{
			$idProduct = (int) Tools::getValue('idProduct');
			$result = Product::toggleActive($idProduct);
			$sonuc = (string) ($result['message'] ?? 'Durum güncellenemedi');
			$sonucType = !empty($result['success']) ? 'success' : 'danger';
		}
	}

	if (Tools::isSubmit('bulkProductAction'))
	{
		$formToken = md5(Tools::getValue('bulkProductToken'));
		if (md5($adminToken) === $formToken)
		{
			$action = trim((string) Tools::getValue('bulkProductAction'));
			$productIds = Tools::getValue('productIds');
			$ids = is_array($productIds) ? $productIds : [];

			if ($action === 'activate') {
				$result = Product::bulkSetActive($ids, 1);
			} elseif ($action === 'deactivate') {
				$result = Product::bulkSetActive($ids, 0);
			} elseif ($action === 'delete') {
				$result = Product::bulkDelete($ids);
			} else {
				$result = ['success' => false, 'message' => 'Geçersiz toplu işlem'];
			}

			$sonuc = (string) ($result['message'] ?? '');
			$sonucType = !empty($result['success']) ? 'success' : 'danger';
		}
	}


	$currentPage 	= max(1, (int) Tools::getValue('page'));
	$query 			= trim((string) Tools::getValue('q'));
	$stockCode 		= trim((string) Tools::getValue('stock_code'));
	$idCategory 	= (int) Tools::getValue('category');
	$idBrand 		= (int) Tools::getValue('brand');
	$idSupplier 	= (int) Tools::getValue('supplier');
	$activeRaw 		= Tools::getValue('active', -1);
	$activeFilter 	= ($activeRaw === '' || $activeRaw === false || $activeRaw === null)
		? -1
		: (int) $activeRaw;
	$perPage 		= 30;
	$listFilters 	= array_filter([
		'stock_code' => $stockCode !== '' ? $stockCode : null,
	], static fn($v) => $v !== null && $v !== '');

	$total = Product::countAdmin($query, $idCategory, $idBrand, $activeFilter, $idSupplier, $listFilters);
	$queryParams = array_filter([
		'q' 			=> $query,
		'stock_code' 	=> $stockCode !== '' ? $stockCode : null,
		'category' 		=> $idCategory > 0 ? $idCategory : null,
		'brand' 		=> $idBrand > 0 ? $idBrand : null,
		'supplier' 		=> $idSupplier > 0 ? $idSupplier : null,
		'active' 		=> $activeFilter >= 0 ? $activeFilter : null,
	], static fn($v) 	=> $v !== null && $v !== '');

	$sortState = Pagination::adminColumnSort(
		[
			'id' => 'p.id_product',
			'name' => 'p.product_name',
			'category' => 'c.category_name',
			'brand' => 'b.brand_name',
			'supplier' => 's.supplier_name',
			'price' => 'p.price',
			'stock' => 'p.stock',
			'status' => 'p.active',
		],
		'id',
		'desc',
		Admin::url('products'),
		$queryParams
	);

	$paginationQuery = array_merge($queryParams, [
		'sort' => $sortState['sort'],
		'dir' => $sortState['dir'],
	]);
	$pagination = Pagination::build($total, $currentPage, $perPage, Admin::url('products'), $paginationQuery);
	$products 	= Product::getAdminList(
		$query,
		$idCategory,
		$idBrand,
		$activeFilter,
		$perPage,
		$pagination['offset'],
		$idSupplier,
		$sortState['order_by'],
		$listFilters
	);

	$flash = class_exists('ExportExcelService', false) ? ExportExcelService::consumeFlash() : null;
	if ($flash && $flash['message'] !== '') {
		$sonuc = $flash['message'];
		$sonucType = $flash['type'] === 'danger' ? 'danger' : 'success';
	}

	$smarty->assign([
		'products' 			=> $products,
		'pagination' 		=> $pagination,
		'searchQuery' 		=> $query,
		'stockCodeFilter' 	=> $stockCode,
		'categoryFilter' 	=> $idCategory,
		'brandFilter' 		=> $idBrand,
		'supplierFilter' 	=> $idSupplier,
		'activeFilter' 		=> $activeFilter,
		'categoryOptions' 	=> Category::getProductSelectOptions(),
		'brandOptions' 		=> Brand::getOptions(),
		'supplierOptions' 	=> Supplier::getOptions(),
		'sortLinks' 		=> $sortState['links'],
		'listSort' 			=> $sortState['sort'],
		'listDir' 			=> $sortState['dir'],
		'sonuc' 			=> $sonuc,
		'sonucType' 		=> $sonucType,
	]);

	AdminPage::add('products', 'Products');
