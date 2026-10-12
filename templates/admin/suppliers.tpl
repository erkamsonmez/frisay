{if $sonuc}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast dark show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$sonuc|escape}</div>
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
			</div>
		</div>
		<form method="get" action="{$adminUrl}suppliers" id="admSuppliersFilterForm">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label for="sup_active">{'Status'|adminT}</label>
					<select id="sup_active" name="active" class="form-select form-select-sm">
						<option value="-1"{if $activeFilter == -1} selected{/if}>{'All'|adminT}</option>
						<option value="1"{if $activeFilter == 1} selected{/if}>{'Active'|adminT}</option>
						<option value="0"{if $activeFilter == 0} selected{/if}>{'Inactive'|adminT}</option>
					</select>
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="admSuppliersFilterForm" class="btn btn-warning">{'Filter'|adminT}</button>
				<a href="{$adminUrl}suppliers" class="btn btn-outline-secondary">{'Clear'|adminT}</a>
			</div>
			<div class="mp-filter-actions__right">
				<a href="{$adminUrl}supplier" class="btn btn-primary">{'+ New supplier'|adminT}</a>
			</div>
		</div>
	</div>
</div>

<div class="admin-panel p-0">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>{'Supplier'|adminT}</th>
					<th>{'Status'|adminT}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{if $suppliers|@count}
				{foreach $suppliers as $row}
				<tr>
					<td>{$row.id_supplier}</td>
					<td>{$row.supplier_name|escape}</td>
					<td>{if $row.active}{'Active'|adminT}{else}<span class="text-danger">{'Inactive'|adminT}</span>{/if}</td>
					<td class="text-end">
						<form action="" method="POST">
							<input type="hidden" name="token" value="{$adminToken}">
							<a href="{$adminUrl}supplier?id={$row.id_supplier}" class="btn btn-sm btn-outline-dark">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
							</a>
							<input type="hidden" name="idSupplier" value="{$row.id_supplier}">
							<button type="submit" name="deleteSupplier" value="{$adminToken}" class="btn btn-danger btn-sm js-admin-confirm" data-confirm-title="{'Delete supplier'|adminT}" data-confirm-message="{'Are you sure you want to delete this supplier?'|adminT}">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
							</button>
						</form>
					</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="4" class="text-muted">{'No records found.'|adminT}</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>
