<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}
	$sonuc = '';
	if (Tools::isSubmit('deleteCategory'))
	{
		$formToken = md5(Tools::getValue('deleteCategory'));
		if (md5($adminToken) == $formToken)
		{
			$idCategory 	= (int)Tools::getValue('idCategory');
			$isHave		= DB::getRow('categories', 'id_category = '.(int)$idCategory.'', 'category_name');
			if ($isHave)
			{
				DB::execute('DELETE FROM categories WHERE id_category = '.(int)$idCategory.' LIMIT 1');
				$sonuc = $isHave.' kategorisi başarıyla silindi';
			}
		}
	}

	if (Tools::isSubmit('toggleHomeCategory')) {
		$postToken = (string) Tools::getValue('token');

		if (hash_equals((string) $adminToken, $postToken)) {
			$idCategory = (int) Tools::getValue('idCategory');
			$show = (int) Tools::getValue('show_on_home') === 1;

			if (Category::setShowOnHome($idCategory, $show)) {
				$sonuc = $show
					? adminT('Category added to homepage')
					: adminT('Category removed from homepage');
			}
		} else {
			$sonuc = adminT('Invalid request');
		}
	}

	$activeFilter = Tools::getIsset('active') ? (int) Tools::getValue('active') : -1;
	$queryParams = array_filter([
		'active' => $activeFilter >= 0 ? $activeFilter : null,
	], static fn($v) => $v !== null && $v !== '');

	$explicitSort = trim((string) Tools::getValue('sort', ''));
	$explicitDir = trim((string) Tools::getValue('dir', ''));

	$sortState = Pagination::adminColumnSort(
		[
			'id' => 'c.id_category',
			'name' => 'c.category_name',
			'parent' => 'p.category_name',
			'home' => 'c.show_on_home',
			'status' => 'c.active',
		],
		'id',
		'asc',
		Admin::url('categories'),
		$queryParams,
		$explicitSort !== '' ? $explicitSort : 'id',
		$explicitDir !== '' ? $explicitDir : 'asc'
	);

	if ($explicitSort === '') {
		foreach ($sortState['links'] as $key => $link) {
			$sortState['links'][$key]['active'] = false;
			$sortState['links'][$key]['dir'] = '';
		}
		$orderBy = '';
		$sortState['sort'] = '';
		$sortState['dir'] = '';
	} else {
		$orderBy = $sortState['order_by'];
	}

	$categories = Category::getAdminList($activeFilter, 500, 0, $orderBy);

	$smarty->assign([
		'categories' 	=> $categories,
		'activeFilter' 	=> $activeFilter,
		'sortLinks' 	=> $sortState['links'],
		'listSort' 		=> $sortState['sort'],
		'listDir' 		=> $sortState['dir'],
		'sonuc' 		=> $sonuc,
	]);

	AdminPage::add('categories', 'Categories');
