{foreach $marketplaceOrders as $ord}
{assign var=cardStatus value=$ord.status_pill|default:'default'}
{assign var=thumbs value=$ord.thumb_urls|default:[]}
<article class="ps-order-card ps-order-card--{$cardStatus|escape}" data-order="{$ord.detail_json|escape}">
	<div class="ps-order-card__check">
		{if $mpOrderListMode|default:'full' != 'dashboard'}
		<input type="checkbox" class="form-check-input mp-order-check mp-order-row-check" value="{$ord.platform|escape}::{$ord.order_number|escape}::{$ord.shipment_package_id|escape}" aria-label="{'Select'|adminT}">
		{/if}
	</div>
	<div class="ps-order-thumbs">
		{if $thumbs|@count}
			{foreach $thumbs as $thumb}
			<img src="{$thumb|escape}" alt="" width="40" height="40">
			{/foreach}
		{elseif $ord.thumb_url|default:'' != ''}
			<img src="{$ord.thumb_url|escape}" alt="" width="40" height="40">
		{else}
			<img src="{$domain}templates/admin/img/icons/{$ord.platform_icon_file|escape}" alt="{$ord.platform_label|escape}" width="40" height="40" onerror="this.src='{$domain}templates/default/img/favicon.png';">
		{/if}
	</div>
	{if $mpOrderListMode|default:'full' != 'dashboard'}
	<button type="button" class="ps-order-card__ref js-mp-open-detail">{$ord.order_number|escape}</button>
	{else}
	<a class="ps-order-card__ref" href="{$adminUrl|escape}marketplace-orders">{$ord.order_number|escape}</a>
	{/if}
	<div class="ps-order-card__customer">
		<strong>{$ord.customer_name|escape}</strong>
		{if $ord.location|default:'' != ''}
		<span>{$ord.location|escape}</span>
		{elseif $ord.customer_sub|default:'' != ''}
		<span>{$ord.customer_sub|escape}</span>
		{/if}
	</div>
	<div class="ps-order-card__total">{$ord.total_formatted|default:$ord.total_price}</div>
	<div class="ps-order-card__cargo">
		{if $ord.tracking_url|default:'' != ''}
		<a href="{$ord.tracking_url|escape}" target="_blank" rel="noopener" title="{'Tracking'|adminT}">
			{if $ord.cargo_logo_url|default:'' != ''}
			<img src="{$ord.cargo_logo_url|escape}" alt="{$ord.cargo_name|default:$ord.cargo_provider|escape}">
			{elseif $ord.cargo_name|default:'' != ''}
			<span class="ps-order-card__cargo-name">{$ord.cargo_name|escape}</span>
			{else}
			<span class="ps-order-card__cargo-name">{$ord.cargo_provider|escape}</span>
			{/if}
		</a>
		{elseif $ord.cargo_logo_url|default:'' != ''}
		<img src="{$ord.cargo_logo_url|escape}" alt="{$ord.cargo_name|default:$ord.cargo_provider|escape}">
		{elseif $ord.cargo_name|default:'' != '' && $ord.cargo_name != '—'}
		<span class="ps-order-card__cargo-name">{$ord.cargo_name|escape}</span>
		{elseif $ord.cargo_provider|default:'' != '' && $ord.cargo_provider != '—'}
		<span class="ps-order-card__cargo-name">{$ord.cargo_provider|escape}</span>
		{else}
		<span class="ps-order-card__cargo-name">—</span>
		{/if}
	</div>
	<div class="ps-order-status ps-order-card__status">
		<span class="ps-order-status-face ps-status-select--{$cardStatus|escape}">{$ord.status_label|default:$ord.status|escape}</span>
	</div>
	<div class="ps-order-card__date">{$ord.date_full|default:$ord.date_list|escape}</div>
	<div class="ps-order-card__actions">
		<a href="{$ord.print_url|escape}" class="ps-action-btn" target="_blank" rel="noopener" title="{'Print'|adminT}">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
		</a>
		{if $mpOrderListMode|default:'full' != 'dashboard'}
		<button type="button" class="ps-action-btn js-mp-open-detail" title="{'View'|adminT}">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
		</button>
		{else}
		<a href="{$adminUrl|escape}marketplace-orders" class="ps-action-btn" title="{'View'|adminT}">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
		</a>
		{/if}
	</div>
</article>
{/foreach}
