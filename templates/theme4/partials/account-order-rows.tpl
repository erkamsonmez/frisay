<div class="sn-order-list">
	{foreach $orders as $o}
	{assign var=thumbs value=$o.thumb_urls|default:[]}
	<article class="sn-order-card sn-order-card--{$o.status_class|escape}">
		<div class="sn-order-card__thumbs">
			{if $thumbs|@count}
				{foreach $thumbs as $thumb}
				<img src="{$thumb|escape}" alt="{$o.thumb_product|escape}" width="64" height="64">
				{/foreach}
			{else}
				<img src="{$o.thumb_url|escape}" alt="{$o.thumb_product|escape}" width="64" height="64">
			{/if}
		</div>

		<div class="sn-order-card__info">
			<a class="sn-order-card__ref" href="{$domain}my-account?order={$o.id_order}">#{$o.reference|escape}</a>
			<div class="sn-order-card__sub">
				<span>{$o.date_full|default:$o.date_formatted|escape}</span>
				{if $o.thumb_product}<span>{$o.thumb_product|escape}</span>{/if}
			</div>
			<div class="sn-order-card__cargo">
				{if $o.tracking_url}
				<a href="{$o.tracking_url|escape}" target="_blank" rel="noopener">
					{if $o.cargo_logo_url}
					<img src="{$o.cargo_logo_url|escape}" alt="{$o.cargo_name|escape}">
					{elseif $o.cargo_name}
					{$o.cargo_name|escape}
					{else}{'Cargo'|translate}{/if}
				</a>
				{elseif $o.cargo_logo_url}
				<img src="{$o.cargo_logo_url|escape}" alt="{$o.cargo_name|escape}">
				{elseif $o.cargo_name}
				<span>{$o.cargo_name|escape}</span>
				{/if}
			</div>
		</div>

		<div class="sn-order-card__total">
			<strong>{$o.total_formatted}</strong>
			<small>{$o.shipping_formatted} {'Cargo'|translate}</small>
		</div>

		<div class="sn-order-card__status">
			<span class="sn-order-status sn-order-status--{$o.status_class|escape}">{$o.status_label|escape}</span>
		</div>

		<div class="sn-order-card__actions">
			{if $o.has_invoice|default:false && $o.invoice_view_url|default:''}
			<a href="{$o.invoice_view_url|escape}" class="sn-order-action" target="_blank" rel="noopener" title="{'Invoice'|translate}">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
				{'Invoice'|translate}
			</a>
			{/if}
			<a href="{$domain}my-account?order={$o.id_order}" class="sn-order-action sn-order-action--primary">
				{'Order Detail'|translate}
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
			</a>
		</div>
	</article>
	{/foreach}
</div>
