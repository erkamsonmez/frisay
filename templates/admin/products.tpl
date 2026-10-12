{if $sonuc}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast dark show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">
					{$sonuc|escape}
				</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}
<div class="admin-panel p-3 mb-3">
	<div class="mp-filter-card mb-0">
		<div class="mp-filter-header">
			<div>
				<div class="mp-filter-title">
					{include file='admin/partials/filter-funnel-icon.tpl'}
					{'Filters'|adminT}
				</div>
				<div class="mp-filter-sub">{'Search...'|adminT}</div>
			</div>
		</div>
		<form method="get" action="{$adminUrl}products" id="admProductsFilterForm">
			<input type="hidden" name="sort" value="{$listSort|default:'id'|escape}">
			<input type="hidden" name="dir" value="{$listDir|default:'desc'|escape}">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label for="prd_q">{'Search'|adminT}</label>
					<input type="text" id="prd_q" name="q" class="form-control form-control-sm" placeholder="{'Search by name, SKU or barcode…'|adminT}" value="{$searchQuery|escape}">
				</div>
				<div class="col-md-4 mb-3">
					<label for="prd_stock_code">{'Stock code'|adminT}</label>
					<input type="text" id="prd_stock_code" name="stock_code" class="form-control form-control-sm" placeholder="{'Stock code'|adminT}" value="{$stockCodeFilter|default:''|escape}">
				</div>
				<div class="col-md-4 mb-3">
					<label for="prd_category">{'Category'|adminT}</label>
					<select id="prd_category" name="category" class="form-select form-select-sm">
						<option value="0">{'All categories'|adminT}</option>
						{foreach $categoryOptions as $cat}
						<option value="{$cat.id_category}"{if $categoryFilter == $cat.id_category} selected{/if}>{if isset($cat.depth) && $cat.depth > 0}{for $i=1 to $cat.depth}—{/for} {/if}{$cat.category_name|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label for="prd_brand">{'Brand'|adminT}</label>
					<select id="prd_brand" name="brand" class="form-select form-select-sm">
						<option value="0">{'All brands'|adminT}</option>
						{foreach $brandOptions as $b}
						<option value="{$b.id_brand}"{if $brandFilter == $b.id_brand} selected{/if}>{$b.brand_name|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label for="prd_supplier">{'Supplier'|adminT}</label>
					<select id="prd_supplier" name="supplier" class="form-select form-select-sm">
						<option value="0">{'All suppliers'|adminT}</option>
						{foreach $supplierOptions as $s}
						<option value="{$s.id_supplier}"{if $supplierFilter == $s.id_supplier} selected{/if}>{$s.supplier_name|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label for="prd_active">{'Status'|adminT}</label>
					<select id="prd_active" name="active" class="form-select form-select-sm">
						<option value="-1"{if $activeFilter == -1} selected{/if}>{'All statuses'|adminT}</option>
						<option value="1"{if $activeFilter == 1} selected{/if}>{'Active'|adminT}</option>
						<option value="0"{if $activeFilter == 0} selected{/if}>{'Inactive'|adminT}</option>
					</select>
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="admProductsFilterForm" class="btn btn-warning">{'Filter'|adminT}</button>
				<a href="{$adminUrl}products" class="btn btn-outline-secondary">{'Clear'|adminT}</a>
			</div>
			<div class="mp-filter-actions__right" id="exportExcelMount"></div>
		</div>
	</div>
</div>
<div class="admin-panel mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
	<div>
		<a href="{$adminUrl}product" class="btn btn-sm btn-primary">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-plus-icon lucide-circle-plus"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>
			{'New Product'|adminT}
		</a>
	</div>
	<form method="post" id="productsBulkForm" class="d-flex align-items-center flex-wrap gap-2" hidden>
		<input type="hidden" name="token" value="{$adminToken}">
		<input type="hidden" name="bulkProductToken" value="{$adminToken}">
		<span class="small text-muted" id="productsBulkCount">0 {'selected'|adminT}</span>
		<button type="submit" name="bulkProductAction" value="activate" class="btn btn-sm btn-outline-success js-bulk-product-submit">{'Active'|adminT}</button>
		<button type="submit" name="bulkProductAction" value="deactivate" class="btn btn-sm btn-outline-secondary js-bulk-product-submit">{'Deactive'|adminT}</button>
		<button type="submit" name="bulkProductAction" value="delete" class="btn btn-sm btn-outline-danger js-bulk-product-submit js-admin-confirm" data-confirm-title="{'Delete'|adminT}" data-confirm-message="{'Selected items will be permanently deleted. Do you want to continue?'|adminT}" data-confirm-require-checked=".js-product-select" data-confirm-empty-message="{'Select at least one product'|adminT}">{'Delete'|adminT}</button>
	</form>
</div>
<div class="admin-panel p-0">
	<div class="table-responsive adm-table-overflow">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th style="width:36px">
						<input type="checkbox" class="form-check-input" id="productsSelectAll" aria-label="{'Select all'|adminT}">
					</th>
					<th></th>
					{include file='admin/partials/sortable-th.tpl' col='id' label='ID' sortLinks=$sortLinks class='text-muted'}
					{include file='admin/partials/sortable-th.tpl' col='name' label='Product' sortLinks=$sortLinks}
					{include file='admin/partials/sortable-th.tpl' col='category' label='Category' sortLinks=$sortLinks}
					{include file='admin/partials/sortable-th.tpl' col='brand' label='Brand' sortLinks=$sortLinks}
					{include file='admin/partials/sortable-th.tpl' col='supplier' label='Supplier' sortLinks=$sortLinks}
					{include file='admin/partials/sortable-th.tpl' col='price' label='Price' sortLinks=$sortLinks}
					{include file='admin/partials/sortable-th.tpl' col='stock' label='Stock' sortLinks=$sortLinks}
					{include file='admin/partials/sortable-th.tpl' col='status' label='Status' sortLinks=$sortLinks}
					<th></th>
				</tr>
			</thead>
			<tbody>
				{if $products|@count}
				{foreach $products as $row}
				<tr>
					<td>
						<input type="checkbox" form="productsBulkForm" name="productIds[]" value="{$row.id_product}" class="form-check-input js-product-select" aria-label="Ürün #{$row.id_product} seç">
					</td>
					<td style="width:48px"><img src="{$row.image_url}" alt="" width="40" height="40" style="object-fit:contain"></td>
					<td class="text-muted small">{$row.id_product}</td>
					<td>
						{$row.product_name|escape|truncate:40}{if $row.product_type|default:'physical' == 'virtual'} <span class="badge bg-info">{'Virtual'|adminT}</span>{/if}
						{if $row.stock_code|default:'' != ''}
						<div class="text-muted small">{$row.stock_code|escape}</div>
						{/if}
					</td>
					<td>{$row.category_name|escape}</td>
					<td>{$row.brand_name|escape}</td>
					<td>{if $row.supplier_name|default:'' != ''}{$row.supplier_name|escape}{else}—{/if}</td>
					<td>{$row.price_formatted}</td>
					<td>{$row.stock_formatted|default:$row.stock}</td>
					<td>
						<form action="" method="POST" class="adm-status-switch mb-0">
							<input type="hidden" name="token" value="{$adminToken}">
							<input type="hidden" name="idProduct" value="{$row.id_product}">
							<input type="hidden" name="toggleProductActive" value="{$adminToken}">
							<div class="form-check form-switch mb-0">
								<input class="form-check-input" type="checkbox" role="switch" {if $row.active}checked{/if} onchange="this.form.submit()" title="{if $row.active}{'Active'|adminT}{else}{'Inactive'|adminT}{/if}" aria-label="{if $row.active}{'Active'|adminT}{else}{'Inactive'|adminT}{/if}">
							</div>
						</form>
					</td>
					<td class="text-end text-nowrap adm-actions-cell">
						<div class="adm-row-actions">
							<a href="{$adminUrl}product?id={$row.id_product}" class="adm-icon-btn" title="{'Edit'|adminT}" aria-label="{'Edit'|adminT}">
								<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
							</a>
							<div class="dropdown">
								<button type="button" class="adm-icon-btn" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="{'More actions'|adminT}" aria-label="{'More actions'|adminT}">
									<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
								</button>
								<ul class="dropdown-menu dropdown-menu-end">
									<li>
										<a class="dropdown-item" href="{$row.preview_url|escape}" target="_blank" rel="noopener">{'Preview'|adminT}</a>
									</li>
									<li>
										<form action="" method="POST" class="m-0">
											<input type="hidden" name="token" value="{$adminToken}">
											<input type="hidden" name="idProduct" value="{$row.id_product}">
											<button type="submit" name="copyProduct" value="{$adminToken}" class="dropdown-item">{'Copy'|adminT}</button>
										</form>
									</li>
									<li><hr class="dropdown-divider"></li>
									<li>
										<form action="" method="POST" class="m-0">
											<input type="hidden" name="token" value="{$adminToken}">
											<input type="hidden" name="idProduct" value="{$row.id_product}">
											<button type="submit" name="deleteProduct" value="{$adminToken}" class="dropdown-item text-danger js-admin-confirm" data-confirm-title="{'Delete product'|adminT}" data-confirm-message="{'Are you sure you want to delete this product? The product and related images will be permanently removed.'|adminT}">{'Delete'|adminT}</button>
										</form>
									</li>
								</ul>
							</div>
						</div>
					</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="11" class="text-muted">{'No records found.'|adminT}</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>

{include file='admin/plugin/pagination.tpl'}

<script>
(function () {
	'use strict';

	var selectAll = document.getElementById('productsSelectAll');
	var bulkForm = document.getElementById('productsBulkForm');
	var countEl = document.getElementById('productsBulkCount');

	function getBoxes() {
		return document.querySelectorAll('.js-product-select');
	}

	function updateBulkBar() {
		var checked = document.querySelectorAll('.js-product-select:checked');
		var total = getBoxes().length;
		var selected = checked.length;

		if (bulkForm) {
			bulkForm.hidden = selected === 0;
		}

		if (countEl) {
			countEl.textContent = selected + ' ürün seçildi';
		}

		if (!selectAll) {
			return;
		}

		selectAll.checked = total > 0 && selected === total;
		selectAll.indeterminate = selected > 0 && selected < total;
	}

	if (selectAll) {
		selectAll.addEventListener('change', function () {
			getBoxes().forEach(function (box) {
				box.checked = selectAll.checked;
			});
			updateBulkBar();
		});
	}

	document.addEventListener('change', function (event) {
		if (event.target && event.target.classList.contains('js-product-select')) {
			updateBulkBar();
		}
	});

	document.querySelectorAll('.js-bulk-product-submit').forEach(function (btn) {
		btn.addEventListener('click', function (event) {
			if (document.querySelectorAll('.js-product-select:checked').length === 0) {
				event.preventDefault();
				event.stopPropagation();
				if (window.AdminToast) {
					AdminToast.show('{'Select at least one product'|adminT|escape:'javascript'}', 'warning');
				}
			}
		});
	});
})();
</script>
