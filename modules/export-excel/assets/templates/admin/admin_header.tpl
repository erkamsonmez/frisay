{if $mode == 'products'}
<div class="export-excel-toolbar" id="exportExcelToolbar" data-filter-form="admProductsFilterForm">
	<div class="export-excel-group">
		<span class="export-excel-group__label">{'Product upload / update'|adminT}</span>
		<form method="post" action="{$exportUrl|escape}" class="d-inline js-export-excel-form" id="exportExcelExportForm">
			<input type="hidden" name="token" value="{$adminToken|escape}">
			<input type="hidden" name="q" value="{$filterQ|escape}">
			<input type="hidden" name="reference" value="">
			<input type="hidden" name="category_q" value="">
			<input type="hidden" name="category" value="{$filterCategory|escape}">
			<input type="hidden" name="brand" value="{$filterBrand|escape}">
			<input type="hidden" name="active" value="{$filterActive|escape}">
			<input type="hidden" name="id_min" value="">
			<input type="hidden" name="id_max" value="">
			<input type="hidden" name="price_excl_min" value="">
			<input type="hidden" name="price_excl_max" value="">
			<input type="hidden" name="price_incl_min" value="">
			<input type="hidden" name="price_incl_max" value="">
			<input type="hidden" name="stock_min" value="">
			<input type="hidden" name="stock_max" value="">
			<button type="submit" class="btn btn-success" title="{'Export'|adminT}">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
				{'Export'|adminT}
			</button>
		</form>
		<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportExcelImportModal" title="{'Import'|adminT}">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m17 8-5-5-5 5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
			{'Import'|adminT}
		</button>
	</div>
	<div class="export-excel-group">
		<span class="export-excel-group__label">{'Price & stock'|adminT}</span>
		<form method="post" action="{$exportPriceStockUrl|escape}" class="d-inline js-export-excel-form" id="exportExcelPriceStockForm">
			<input type="hidden" name="token" value="{$adminToken|escape}">
			<input type="hidden" name="q" value="{$filterQ|escape}">
			<input type="hidden" name="reference" value="">
			<input type="hidden" name="category_q" value="">
			<input type="hidden" name="category" value="{$filterCategory|escape}">
			<input type="hidden" name="brand" value="{$filterBrand|escape}">
			<input type="hidden" name="active" value="{$filterActive|escape}">
			<input type="hidden" name="id_min" value="">
			<input type="hidden" name="id_max" value="">
			<input type="hidden" name="price_excl_min" value="">
			<input type="hidden" name="price_excl_max" value="">
			<input type="hidden" name="price_incl_min" value="">
			<input type="hidden" name="price_incl_max" value="">
			<input type="hidden" name="stock_min" value="">
			<input type="hidden" name="stock_max" value="">
			<button type="submit" class="btn btn-success" title="{'Export prices and stock'|adminT}">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
				{'Export'|adminT}
			</button>
		</form>
		<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportExcelPriceStockModal" title="{'Import prices and stock'|adminT}">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m17 8-5-5-5 5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
			{'Import'|adminT}
		</button>
	</div>
</div>

<div class="modal fade" id="exportExcelImportModal" tabindex="-1" aria-labelledby="exportExcelImportModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exportExcelImportModalLabel">{'Import products from Excel'|adminT}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
			</div>
			<form method="post" action="{$importAction|escape}" enctype="multipart/form-data">
				<div class="modal-body">
					<input type="hidden" name="exportExcelImportProducts" value="1">
					<input type="hidden" name="token" value="{$adminToken|escape}">
					<input type="hidden" name="return_url" value="{$returnUrl|escape}">
					<div class="alert alert-info mb-3">
						<strong>{'Information'|adminT}</strong>
						<ul class="mb-0 mt-2 small">
							<li>{'Only <strong>.xlsx</strong> files are allowed.'|adminT nofilter}</li>
							<li>{'Download the current product list with <strong>Export</strong> first to get a sample template.'|adminT nofilter}</li>
							<li>{'If <strong>SKU</strong> matches an existing product, the record is updated; otherwise a new product is created.'|adminT nofilter}</li>
							<li>{'Variation rows are listed under each product. Existing columns stay the same.'|adminT}</li>
							<li>{'For variations fill Parent Stock Code and Variation (e.g. Color: Red, Size: M).'|adminT}</li>
							<li>{'If <strong>category</strong> or <strong>brand</strong> in Excel does not exist, it is created automatically.'|adminT nofilter}</li>
							<li>{'In the <strong>Images</strong> column, enter image URLs separated by <strong>;</strong>. On import, existing product images are replaced with the URLs in Excel.'|adminT nofilter}</li>
						</ul>
					</div>
					<div class="mb-3">
						<p class="fw-semibold mb-2">{'Expected columns'|adminT}</p>
						<div class="small text-muted">
							Product Name, Barcode, Stock Code, {'Desi'|adminT}, Price, Old Price, Vat, Stock,
							short Description, Description, Meta Title, Meta Description, Slug,
							Category Name, Brand Name, Images, Active,
							Product ID, Parent Stock Code, Variation, Type, Variation ID
						</div>
					</div>
					<div class="mb-0">
						<label for="exportExcelFile" class="form-label">{'Excel file'|adminT}</label>
						<input type="file" id="exportExcelFile" name="excelFile" class="form-control" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
					<button type="submit" class="btn btn-success">{'Upload and import'|adminT}</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="exportExcelPriceStockModal" tabindex="-1" aria-labelledby="exportExcelPriceStockModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exportExcelPriceStockModalLabel">{'Update prices and stock from Excel'|adminT}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
			</div>
			<form method="post" action="{$importAction|escape}" enctype="multipart/form-data">
				<div class="modal-body">
					<input type="hidden" name="exportExcelImportPriceStock" value="1">
					<input type="hidden" name="token" value="{$adminToken|escape}">
					<input type="hidden" name="return_url" value="{$returnUrl|escape}">
					<div class="alert alert-info mb-3">
						<strong>{'Information'|adminT}</strong>
						<ul class="mb-0 mt-2 small">
							<li>{'Only <strong>.xlsx</strong> files are allowed.'|adminT nofilter}</li>
							<li>{'This file only updates price, cost and stock. New products are not created.'|adminT}</li>
							<li>{'Each variation is a separate row. Match by Variation ID, SKU or barcode.'|adminT}</li>
							<li>{'Empty cells are left unchanged.'|adminT}</li>
							<li>{'Parent product stock is the sum of variations and is not updated from the parent row.'|adminT}</li>
							<li>{'Cost is updated from the parent product row. Cost on variation rows is ignored.'|adminT}</li>
						</ul>
					</div>
					<div class="mb-3">
						<p class="fw-semibold mb-2">{'Expected columns'|adminT}</p>
						<div class="small text-muted">
							Product ID, Variation ID, Type, Product Name, Variation, Stock Code, Barcode, Price, Cost, Old Price, Stock
						</div>
					</div>
					<div class="mb-0">
						<label for="exportExcelPriceStockFile" class="form-label">{'Excel file'|adminT}</label>
						<input type="file" id="exportExcelPriceStockFile" name="excelPriceStockFile" class="form-control" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
					<button type="submit" class="btn btn-success">{'Upload and import'|adminT}</button>
				</div>
			</form>
		</div>
	</div>
</div>

{else}
<div class="export-excel-toolbar" id="exportExcelToolbar" data-filter-form="admOrdersFilterForm">
	<form method="post" action="{$exportUrl|escape}" class="d-inline js-export-excel-form" id="exportExcelExportForm">
		<input type="hidden" name="token" value="{$adminToken|escape}">
		<input type="hidden" name="status" value="{$filterStatus|escape}">
		<input type="hidden" name="reference" value="{$filterReference|escape}">
		<input type="hidden" name="customer" value="{$filterCustomer|escape}">
		<input type="hidden" name="date_from" value="{$filterDateFrom|escape}">
		<input type="hidden" name="date_to" value="{$filterDateTo|escape}">
		<input type="hidden" name="payment_method" value="{$filterPayment|escape}">
		<input type="hidden" name="sku" value="{$filterSku|escape}">
		<input type="hidden" name="id_min" value="">
		<input type="hidden" name="id_max" value="">
		<input type="hidden" name="total_min" value="">
		<input type="hidden" name="total_max" value="">
		<input type="hidden" name="product_name" value="{$filterProductName|escape}">
		<input type="hidden" name="tracking_number" value="{$filterTracking|escape}">
		<input type="hidden" name="cargo_company" value="{$filterCargo|escape}">
		<input type="hidden" name="channel" value="{$filterChannel|escape}">
		<input type="hidden" name="sort" value="{$filterSort|escape}">
		<button type="submit" class="btn btn-success" title="{'Export'|adminT}">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
			{'Export'|adminT}
		</button>
	</form>
</div>
{/if}

<link rel="stylesheet" href="{$domain}modules/export-excel/assets/css/admin.css?v={$smarty.now}">
<script src="{$domain}modules/export-excel/assets/js/admin.js?v={$smarty.now}"></script>
