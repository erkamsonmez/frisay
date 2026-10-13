<?php
// Complete catalog landing page. Previously storefront links to /catalog could 404.
if (!defined('IN_SCRIPT')) {
    exit;
}

$css = 'catalog.css';
$sort = (string) Tools::getValue('sort');
$currentPage = max(1, (int) Tools::getValue('page'));
$perPage = Pagination::PER_PAGE;
$baseUrl = $domain . 'catalog';
$productCount = Product::countActive();
$pagination = Pagination::build($productCount, $currentPage, $perPage, $baseUrl, [
    'sort' => $sort !== '' && $sort !== 'newest' ? $sort : '',
]);
$products = Product::getActiveList(null, $perPage, $pagination['offset'], $sort);

$pageTitle = 'Tüm Ürünler';
$pageDesc = '';
$smarty->assign([
    'products' => $products,
    'productCount' => $productCount,
    'listTitle' => $pageTitle,
    'pagination' => $pagination,
    'sort' => $sort !== '' ? $sort : 'newest',
    'sortOptions' => Pagination::getSortOptions(),
    'catalogBaseUrl' => $baseUrl,
    'emptyMessage' => 'Henüz ürün eklenmedi.',
    'breadcrumb' => [
        ['name' => translate('Home Page'), 'url' => $domain],
        ['name' => $pageTitle, 'url' => ''],
    ],
]);
