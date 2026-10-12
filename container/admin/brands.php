<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}
	$sonuc = '';
	if (Tools::isSubmit('deleteBrand'))
	{
		$formToken = md5(Tools::getValue('deleteBrand'));
		if (md5($adminToken) == $formToken)
		{
			$idBrand 	= (int)Tools::getValue('idBrand');
			$isHave		= DB::getRow('brands', 'id_brand = '.(int)$idBrand.'', 'brand_name');
			if ($isHave)
			{
				DB::execute('DELETE FROM brands WHERE id_brand = '.(int)$idBrand.' LIMIT 1');
				$sonuc = $isHave.' markası başarıyla silindi';
			}
		}
	}
	$activeFilter = Tools::getIsset('active') ? (int) Tools::getValue('active') : -1;
	$queryParams = array_filter([
		'active' => $activeFilter >= 0 ? $activeFilter : null,
	], static fn($v) => $v !== null && $v !== '');

	$sortState = Pagination::adminColumnSort(
		[
			'id' => 'id_brand',
			'name' => 'brand_name',
			'status' => 'active',
		],
		'name',
		'asc',
		Admin::url('brands'),
		$queryParams
	);

	$brands = Brand::getAdminList($activeFilter, 500, 0, $sortState['order_by']);

	$smarty->assign([
		'brands' 		=> $brands,
		'activeFilter' 	=> $activeFilter,
		'sortLinks' 	=> $sortState['links'],
		'listSort' 		=> $sortState['sort'],
		'listDir' 		=> $sortState['dir'],
		'sonuc' 		=> $sonuc,
	]);

	AdminPage::add('brands', 'Brands');
