<div class="admin-panel p-3 mb-3">
	<div class="mp-filter-card mb-0">
		<div class="mp-filter-header">
			<div>
				<div class="mp-filter-title">
					{include file='admin/partials/filter-funnel-icon.tpl'}
					{'Filters'|adminT}
				</div>
				<div class="mp-filter-sub">{'Search name, phone or email...'|adminT}</div>
			</div>
		</div>
		<form method="get" action="{$adminUrl}customers" id="admCustomersFilterForm">
			<div class="row">
				<div class="col-md-6 mb-3">
					<label for="cst_q">{'Search'|adminT}</label>
					<input type="search" id="cst_q" name="q" class="form-control form-control-sm" placeholder="{'Search name, phone or email...'|adminT}" value="{$searchQuery|escape}">
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="admCustomersFilterForm" class="btn btn-warning">{'Search'|adminT}</button>
			</div>
			<div class="mp-filter-actions__right">
				<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCustomerModal">{'Add customer'|adminT}</button>
			</div>
		</div>
	</div>
</div>

{if $customerFlash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$customerFlashType|default:'dark'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">
					{$customerFlash|escape}
				</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

<div class="admin-panel p-0">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>{'Full name'|adminT}</th>
					<th>{'Phone'|adminT}</th>
					<th>{'Email'|adminT}</th>
					<th>{'Order'|adminT}</th>
					<th>{'Total'|adminT}</th>
					<th>{'Registered'|adminT}</th>
					<th>{'Status'|adminT}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{if $customers|@count}
				{foreach $customers as $row}
				<tr>
					<td>{$row.id_user}</td>
					<td>{$row.user_full_name|escape}</td>
					<td>{$row.phone|escape}</td>
					<td>{if $row.email}{$row.email|escape}{else}<span class="text-muted">—</span>{/if}</td>
					<td>{$row.order_count}</td>
					<td>{$row.order_total_formatted}</td>
					<td>{$row.date_formatted}</td>
					<td>{if $row.active}{'Active'|adminT}{else}<span class="text-danger">{'Inactive'|adminT}</span>{/if}</td>
					<td class="text-end"><a href="{$adminUrl}customer?id={$row.id_user}" class="btn btn-sm btn-outline-dark">{'Detail'|adminT}</a></td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="9" class="text-muted">{'No customers found.'|adminT}</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>

{include file='admin/plugin/pagination.tpl'}

<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-labelledby="createCustomerModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<form method="post" action="{$adminUrl}customers">
				<input type="hidden" name="token" value="{$adminToken|escape}">
				<input type="hidden" name="createCustomer" value="1">
				<div class="modal-header">
					<h5 class="modal-title" id="createCustomerModalLabel">{'Add customer'|adminT}</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label" for="new_customer_name">{'Full name'|adminT}</label>
						<input type="text" class="form-control" id="new_customer_name" name="user_full_name" required maxlength="128" autocomplete="off">
					</div>
					<div class="mb-3">
						<label class="form-label" for="new_customer_phone">{'Phone'|adminT}</label>
						<input type="text" class="form-control" id="new_customer_phone" name="phone" required placeholder="{'e.g. +90 532… or +1 202…'|adminT}" autocomplete="off">
					</div>
					<div class="mb-0">
						<label class="form-label" for="new_customer_email">{'Email'|adminT}</label>
						<input type="email" class="form-control" id="new_customer_email" name="email" maxlength="128" autocomplete="off">
						<div class="form-text">{'Optional. A random password is generated automatically.'|adminT}</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
					<button type="submit" class="btn btn-primary btn-sm">{'Create customer'|adminT}</button>
				</div>
			</form>
		</div>
	</div>
</div>
