<?php

	if (!defined('IN_SCRIPT')) {

		header('HTTP/1.0 404 Not Found');

		header('Location: ../404');

		exit;

	}



	$cartSeo = Seo::resolvePage('cart', translate('Cart page title'), translate('Cart page description'));

	$pageTitle = $cartSeo['title'];

	$pageDesc = $cartSeo['description'];



	$css = 'pages.css';

	$cart = Cart::getSummary();



	$favoriteProducts = Favorite::getList(null, 12, 0);

	$smarty->assign([
		'cart' => $cart,
		'favoriteProducts' => $favoriteProducts,
		'breadcrumb' => [
			['name' => translate('Home Page'), 'url' => $domain],
			['name' => translate('My Cart'), 'url' => ''],
		],
	]);

	if (class_exists('Module', false)) {
		Module::refreshHook($smarty, 'cart', ['cart' => $cart]);
	}

