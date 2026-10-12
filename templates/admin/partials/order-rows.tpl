{foreach $orders as $row}
{assign var=thumbs value=$row.thumb_urls|default:[]}
<article class="ps-order-card ps-order-card--{$row.status_class|escape}">
	<div class="ps-order-card__check">
		{if $orderListMode|default:'full' != 'dashboard'}
		<input type="checkbox" class="adm-order-select form-check-input" value="{$row.id_order}" aria-label="{'Select'|adminT}">
		{/if}
	</div>
	<div class="ps-order-thumbs">
		{if $thumbs|@count}
			{foreach $thumbs as $thumb}
			<img src="{$thumb|escape}" alt="{$row.thumb_product|escape}" width="40" height="40">
			{/foreach}
		{else}
			<img src="{$row.thumb_url|escape}" alt="{$row.thumb_product|escape}" width="40" height="40">
		{/if}
	</div>
	<a class="ps-order-card__ref" href="{$adminUrl}order?id={$row.id_order}">{$row.reference|escape}</a>
	<div class="ps-order-card__customer">
		<strong>{$row.customer_name|escape}</strong>
		{if $row.location}<span>{$row.location|escape}</span>{/if}
	</div>
	<div class="ps-order-card__total">{$row.total_formatted}</div>
	<div class="ps-order-card__cargo">
		{if $row.tracking_url}
		<a href="{$row.tracking_url|escape}" target="_blank" rel="noopener" title="{'Tracking'|adminT}">
			{if $row.cargo_logo_url}
			<img src="{$row.cargo_logo_url|escape}" alt="{$row.cargo_name|escape}">
			{elseif $row.cargo_name}
			<span class="ps-order-card__cargo-name">{$row.cargo_name|escape}</span>
			{else}—{/if}
		</a>
		{elseif $row.cargo_logo_url}
		<img src="{$row.cargo_logo_url|escape}" alt="{$row.cargo_name|escape}">
		{elseif $row.cargo_name}
		<span class="ps-order-card__cargo-name">{$row.cargo_name|escape}</span>
		{else}
		<span class="ps-order-card__cargo-name">—</span>
		{/if}
	</div>
	<div class="ps-order-status">
		{if isset($statusOptions) && $statusOptions|@count}
		<select class="ps-order-status-select ps-status-select--{$row.status_class}"
			data-order-id="{$row.id_order}"
			data-current="{$row.status}"
			onclick="event.stopPropagation();"
			aria-label="{'Order status'|adminT}">
			{foreach $statusOptions as $statusId => $statusLabel}
			<option value="{$statusId}"{if $statusId == $row.status} selected{/if}>{$statusLabel|escape}</option>
			{/foreach}
		</select>
		{else}
		<span class="ps-order-status-face ps-status-select--{$row.status_class}">{$row.status_label|escape}</span>
		{/if}
	</div>
	<div class="ps-order-card__date">{$row.date_full|escape}</div>
	<div class="ps-order-card__actions">
		<a href="{$adminUrl}order-print?id={$row.id_order}&amp;auto=1" class="ps-action-btn" target="_blank" rel="noopener" title="{'Print'|adminT}">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
		</a>
		<a href="{$adminUrl}order?id={$row.id_order}" class="ps-action-btn" title="{'View'|adminT}">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
		</a>
	</div>
</article>
{/foreach}
