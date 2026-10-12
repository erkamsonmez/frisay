{if $flash|default:''}
<div class="alert alert-{$flashType|default:'info'} py-2">{$flash|escape}</div>
{/if}

<div class="admin-panel p-3 mb-3">
	<div class="mp-filter-card mb-0 js-filter-collapse">
		<div class="mp-filter-header">
			<button type="button" class="mp-filter-toggle js-filter-collapse-toggle" aria-expanded="false" aria-controls="admOrdersFilterBody">
				<span class="mp-filter-title">
					{include file='admin/partials/filter-funnel-icon.tpl'}
					{'Filters'|adminT}
				</span>
				<svg class="mp-filter-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
			</button>
			<div class="adm-orders-goal">
				<div class="adm-orders-goal__meta">
					<span>{$orderGoal.used|escape} / {$orderGoal.quota|escape} {'order goal'|adminT}</span>
					<strong>%{$orderGoal.pct|string_format:"%.2f"}</strong>
					<button type="button" class="adm-orders-goal__edit" data-bs-toggle="modal" data-bs-target="#orderGoalModal" title="{'Edit order goal'|adminT}" aria-label="{'Edit order goal'|adminT}">
						<i data-lucide="pencil"></i>
					</button>
				</div>
				<div class="adm-orders-goal__track">
					<span class="adm-orders-goal__fill" style="width: {$orderGoal.pct}%"></span>
				</div>
			</div>
			<div class="mp-filter-actions mp-filter-actions--header">
				<div class="mp-filter-actions__right" id="exportExcelMount"></div>
			</div>
		</div>

		<div class="mp-filter-collapse js-filter-collapse-body" id="admOrdersFilterBody" hidden>
		<form method="get" action="{$adminUrl}orders" id="admOrdersFilterForm">
			<div class="row">
				<div class="col-md-3 mb-3">
					<label for="ord_reference">{'Order no'|adminT}</label>
					<input type="text" id="ord_reference" name="reference" class="form-control form-control-sm" value="{$orderFilters.reference|escape}" placeholder="{'Order no'|adminT}">
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_status">{'Status'|adminT}</label>
					<select id="ord_status" name="status" class="form-select form-select-sm">
						<option value="0"{if $statusFilter == 0} selected{/if}>{'All'|adminT}</option>
						{foreach $statusOptions as $statusId => $statusLabel}
						<option value="{$statusId}"{if $statusFilter == $statusId} selected{/if}>{$statusLabel|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_date_from">{'Start date'|adminT}</label>
					<input type="date" id="ord_date_from" name="date_from" class="form-control form-control-sm" value="{$orderFilters.date_from|escape}">
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_date_to">{'End date'|adminT}</label>
					<input type="date" id="ord_date_to" name="date_to" class="form-control form-control-sm" value="{$orderFilters.date_to|escape}">
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_customer">{'Customer'|adminT}</label>
					<input type="text" id="ord_customer" name="customer" class="form-control form-control-sm" value="{$orderFilters.customer|escape}" placeholder="{'Full name'|adminT}">
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_payment">{'Payment type'|adminT}</label>
					<select id="ord_payment" name="payment_method" class="form-select form-select-sm">
						<option value=""{if $orderFilters.payment_method == ''} selected{/if}>{'All'|adminT}</option>
						{foreach $paymentFilterOptions as $payKey => $payLabel}
						<option value="{$payKey|escape}"{if $orderFilters.payment_method == $payKey} selected{/if}>{$payLabel|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_sku">{'Stock code'|adminT}</label>
					<input type="text" id="ord_sku" name="sku" class="form-control form-control-sm" value="{$orderFilters.sku|escape}" placeholder="SKU">
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_product">{'Product name'|adminT}</label>
					<input type="text" id="ord_product" name="product_name" class="form-control form-control-sm" value="{$orderFilters.product_name|escape}" placeholder="{'Product name'|adminT}">
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_tracking">{'Shipping barcode'|adminT}</label>
					<input type="text" id="ord_tracking" name="tracking_number" class="form-control form-control-sm" value="{$orderFilters.tracking_number|escape}">
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_channel">{'Channel'|adminT}</label>
					<select id="ord_channel" name="channel" class="form-select form-select-sm">
						<option value="all"{if $orderFilters.channel == 'all'} selected{/if}>{'All channels'|adminT}</option>
						<option value="store"{if $orderFilters.channel == 'store'} selected{/if}>{'Store'|adminT}</option>
						<option value="pos"{if $orderFilters.channel == 'pos'} selected{/if}>POS</option>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_cargo">{'Contracted cargo'|adminT}</label>
					<select id="ord_cargo" name="cargo_company" class="form-select form-select-sm">
						<option value=""{if $orderFilters.cargo_company == ''} selected{/if}>{'All'|adminT}</option>
						{foreach $cargoFilterOptions as $cargoName}
						<option value="{$cargoName|escape}"{if $orderFilters.cargo_company == $cargoName} selected{/if}>{$cargoName|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label for="ord_sort">{'Sorting'|adminT}</label>
					<select id="ord_sort" name="sort" class="form-select form-select-sm">
						<option value="date_desc"{if $orderFilters.sort == 'date_desc'} selected{/if}>{'Order date descending'|adminT}</option>
						<option value="date_asc"{if $orderFilters.sort == 'date_asc'} selected{/if}>{'Order date ascending'|adminT}</option>
						<option value="total_desc"{if $orderFilters.sort == 'total_desc'} selected{/if}>{'Total descending'|adminT}</option>
						<option value="total_asc"{if $orderFilters.sort == 'total_asc'} selected{/if}>{'Total ascending'|adminT}</option>
					</select>
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="admOrdersFilterForm" class="btn btn-warning">{'Search'|adminT}</button>
				<a href="{$adminUrl}orders" class="btn btn-outline-secondary">{'Clear'|adminT}</a>
			</div>
		</div>
		</div>
	</div>
</div>

<div class="adm-orders-bulk admin-panel mb-3">
	<div class="adm-orders-bulk__bd">
		<div class="adm-orders-bulk__left">
			<label class="adm-orders-bulk__check">
				<input type="checkbox" id="admOrdersSelectAll">
				<span>{'Apply to selected'|adminT}</span>
			</label>
			<button type="button" class="btn btn-sm btn-outline-secondary" id="admOrdersBulkPrint">
				<i data-lucide="printer" style="width:14px;height:14px"></i>
				{'Print selected'|adminT}
			</button>
		</div>
	</div>
</div>

<div class="admin-panel adm-orders-table-panel">
	<div class="adm-orders-table-panel__hd">
		<div>
			<h2 class="h6 mb-0">{'Order list'|adminT}</h2>
			<p class="text-muted small mb-0">{$ordersTotal} {'records'|adminT}</p>
		</div>
	</div>
	<div class="p-0">
		{if $orders|@count}
		<div class="ps-order-list">
			{include file='admin/partials/order-rows.tpl'}
		</div>
		{else}
		<p class="text-muted p-4 mb-0">{'No records found.'|adminT}</p>
		{/if}
	</div>
</div>

{if $pagination.total_pages > 1}
<div class="ps-pagination-wrap mt-3">
	{include file='admin/plugin/pagination.tpl'}
</div>
{/if}

<div class="modal fade" id="orderGoalModal" tabindex="-1" aria-labelledby="orderGoalModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-sm">
		<form method="post" class="modal-content">
			<input type="hidden" name="saveOrderGoal" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<div class="modal-header">
				<h5 class="modal-title" id="orderGoalModalLabel">{'Edit order goal'|adminT}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<label class="form-label" for="order_goal_target">{'Target order count'|adminT}</label>
				<input type="number" min="1" max="1000000" class="form-control" name="order_goal_target" id="order_goal_target" value="{$orderGoal.quota|escape}" required>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
				<button type="submit" class="btn btn-dark btn-sm">{'Save'|adminT}</button>
			</div>
		</form>
	</div>
</div>

<script>
(function () {
	var selectAll = document.getElementById('admOrdersSelectAll');
	var bulkPrint = document.getElementById('admOrdersBulkPrint');
	var printBase = '{$adminUrl|escape:'javascript'}order-print?auto=1&id=';

	function selectedIds() {
		return Array.prototype.map.call(document.querySelectorAll('.adm-order-select:checked'), function (el) {
			return el.value;
		});
	}

	function toggleDetail(id) {
		var row = document.querySelector('.adm-order-row[data-order-id="' + id + '"]');
		var detail = document.getElementById('adm-order-detail-' + id);
		if (!detail) return;
		var open = !detail.classList.contains('is-open');
		detail.classList.toggle('is-open', open);
		detail.hidden = !open;
		if (row) row.classList.toggle('is-open', open);
		if (window.lucide) window.lucide.createIcons();
	}

	if (selectAll) {
		selectAll.addEventListener('change', function () {
			document.querySelectorAll('.adm-order-select').forEach(function (cb) {
				cb.checked = selectAll.checked;
			});
		});
	}

	if (bulkPrint) {
		bulkPrint.addEventListener('click', function () {
			var ids = selectedIds();
			if (!ids.length) {
				if (window.AdminToast) {
					AdminToast.show('{'Select at least one order'|adminT|escape:'javascript'}', 'warning');
				}
				return;
			}
			ids.forEach(function (id, index) {
				window.setTimeout(function () {
					window.open(printBase + encodeURIComponent(id), '_blank');
				}, index * 350);
			});
		});
	}

	document.querySelectorAll('.adm-order-row').forEach(function (row) {
		row.addEventListener('click', function (e) {
			if (e.target.closest('a, button, input, select, .dropdown-menu, .adm-order-select-cell')) {
				return;
			}
			toggleDetail(row.getAttribute('data-order-id'));
		});
		row.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				toggleDetail(row.getAttribute('data-order-id'));
			}
		});
	});

	document.querySelectorAll('[data-order-expand]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			toggleDetail(btn.getAttribute('data-order-expand'));
		});
	});

	if (window.lucide) {
		window.lucide.createIcons();
	}
})();
</script>
