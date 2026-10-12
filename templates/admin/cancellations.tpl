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
		<form method="get" action="{$adminUrl}cancellations" id="admCancelFilterForm">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label for="cn_status">{'Status'|adminT}</label>
					<select id="cn_status" name="status" class="form-select form-select-sm">
						<option value="0"{if $statusFilter == 0} selected{/if}>{'All'|adminT}</option>
						{foreach $statusOptions as $statusId => $statusLabel}
						<option value="{$statusId}"{if $statusFilter == $statusId} selected{/if}>{$statusLabel|escape}</option>
						{/foreach}
					</select>
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="admCancelFilterForm" class="btn btn-warning">{'Filter'|adminT}</button>
				<a href="{$adminUrl}cancellations" class="btn btn-outline-secondary">{'Clear'|adminT}</a>
			</div>
		</div>
	</div>
</div>

<div class="ps-panel">
	<div class="ps-panel__head ps-panel__head--split">
		<h2>{'Cancellations'|adminT} {if $cancellationsTotal > 0}<span class="ps-panel__count">({$cancellationsTotal})</span>{/if}</h2>
	</div>
	<div class="ps-panel__body p-0">
		{if $cancellations|@count}
		<div class="table-responsive">
			<table class="table table-hover mb-0">
				<thead>
					<tr>
						<th>#</th>
						<th>{'Order'|adminT}</th>
						<th>{'Customer'|adminT}</th>
						<th>{'Date'|adminT}</th>
						<th>{'Status'|adminT}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					{foreach $cancellations as $c}
					<tr>
						<td>{$c.id_cancel}</td>
						<td>
							<a href="{$adminUrl}order?id={$c.id_order}">#{$c.reference|escape}</a>
							<div class="small text-muted">{$c.total_formatted}</div>
						</td>
						<td>
							<div>{$c.customer_name|escape}</div>
							<div class="small text-muted">{$c.customer_phone|escape}</div>
						</td>
						<td>{$c.date_formatted}</td>
						<td><span class="badge {$c.status_badge}">{$c.status_label|escape}</span></td>
						<td class="text-end">
							<a href="{$adminUrl}cancel?id={$c.id_cancel}" class="btn btn-sm btn-outline-dark">{'Detail'|adminT}</a>
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
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
