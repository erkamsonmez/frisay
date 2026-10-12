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
			</div>
		</div>
		<form method="get" action="{$adminUrl}brands" id="admBrandsFilterForm">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label for="br_active">{'Status'|adminT}</label>
					<select id="br_active" name="active" class="form-select form-select-sm">
						<option value="-1"{if $activeFilter == -1} selected{/if}>{'All'|adminT}</option>
						<option value="1"{if $activeFilter == 1} selected{/if}>{'Active'|adminT}</option>
						<option value="0"{if $activeFilter == 0} selected{/if}>{'Inactive'|adminT}</option>
					</select>
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="admBrandsFilterForm" class="btn btn-warning">{'Filter'|adminT}</button>
				<a href="{$adminUrl}brands" class="btn btn-outline-secondary">{'Clear'|adminT}</a>
			</div>
			<div class="mp-filter-actions__right">
				<a href="{$adminUrl}brand" class="btn btn-primary">{'+ New brand'|adminT}</a>
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
					<th>{'Brand'|adminT}</th>
					<th>URL</th>
					<th>{'Status'|adminT}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{if $brands|@count}
				{foreach $brands as $row}
				<tr>
					<td>{$row.id_brand}</td>
					<td>{$row.brand_name|escape}</td>
					<td>{$row.brand_link|escape}</td>
					<td>{if $row.active}{'Active'|adminT}{else}<span class="text-danger">{'Inactive'|adminT}</span>{/if}</td>
					<td class="text-end text-nowrap">
						<form action="" method="POST" class="adm-row-actions">
							<input type="hidden" name="token" value="{$adminToken}">
							<input type="hidden" name="idBrand" value="{$row.id_brand}">
							<a href="{$adminUrl}brand?id={$row.id_brand}" class="adm-icon-btn" title="{'Edit'|adminT}" aria-label="{'Edit'|adminT}">
								<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
							</a>
							<button type="submit" name="deleteBrand" value="{$adminToken}" class="adm-icon-btn adm-icon-btn--danger js-admin-confirm" title="{'Delete'|adminT}" aria-label="{'Delete'|adminT}" data-confirm-title="{'Delete brand'|adminT}" data-confirm-message="{'Are you sure you want to delete this brand? The brand record will be permanently removed.'|adminT}">
								<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
							</button>
						</form>
					</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="5" class="text-muted">{'No records found.'|adminT}</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>
