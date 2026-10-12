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
		<form method="get" action="{$adminUrl}messages" id="admMessagesFilterForm">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label for="msg_read">{'Status'|adminT}</label>
					<select id="msg_read" name="read" class="form-select form-select-sm">
						<option value=""{if $readFilter === null} selected{/if}>{'All'|adminT}</option>
						<option value="0"{if $readFilter === 0} selected{/if}>{'Unread'|adminT}</option>
						<option value="1"{if $readFilter === 1} selected{/if}>{'Read'|adminT}</option>
					</select>
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="admMessagesFilterForm" class="btn btn-warning">{'Filter'|adminT}</button>
				<a href="{$adminUrl}messages" class="btn btn-outline-secondary">{'Clear'|adminT}</a>
			</div>
		</div>
	</div>
</div>

<div class="admin-panel p-0">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>{'Conversation'|adminT}</th>
					<th>{'Customer'|adminT}</th>
					<th>{'Order'|adminT}</th>
					<th>{'Message'|adminT}</th>
					<th>{'Status'|adminT}</th>
					<th>{'Last activity'|adminT}</th>
				</tr>
			</thead>
			<tbody>
				{if $threads|@count}
				{foreach $threads as $row}
				<tr class="{if $row.unread_count > 0}fw-semibold{/if}">
					<td>
						{if $row.is_order_thread}
						<a href="{$adminUrl}message?order={$row.id_order}">{'Order #'|adminT}{$row.order_reference|escape}</a>
						{else}
						<a href="{$adminUrl}message?id={$row.id_message}">{$row.subject|escape}</a>
						{/if}
					</td>
					<td>
						<div>{$row.full_name|escape}</div>
						<div class="small text-muted">{$row.email|escape}</div>
					</td>
					<td>
						{if $row.is_order_thread}
						<a href="{$adminUrl}order?id={$row.id_order}">#{$row.order_reference|escape}</a>
						{else}
						<span class="text-muted">{'General'|adminT}</span>
						{/if}
					</td>
					<td>
						<span class="badge bg-light text-dark border me-1">{$row.message_count}{' messages'|adminT}</span>
						{if $row.reply_count > 0}<span class="badge bg-secondary">{$row.reply_count}{' replies'|adminT}</span>{/if}
						{if $row.last_message_preview}
						<div class="small text-muted mt-1 text-truncate" style="max-width:280px;">{$row.last_message_preview|escape}</div>
						{/if}
					</td>
					<td>{$row.status_label|escape}</td>
					<td>{$row.last_date_formatted}</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="6" class="text-muted">{'No messages found.'|adminT}</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>

{include file='admin/plugin/pagination.tpl'}
