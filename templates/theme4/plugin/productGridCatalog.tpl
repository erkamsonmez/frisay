<div class="blue-catalog-grid t4-catalog-grid">
{foreach $products as $p}
	<article class="panel product-card t4-product-card blue-catalog-card d-flex flex-column">
		{if $p.has_discount}
		<span class="miniDiscount t4-product-card__badge">-%{Tools::getDiscount($p.old_price, $p.price)}</span>
		{elseif $p.label}
		<span class="miniDiscount t4-product-card__badge">{$p.label|escape}</span>
		{/if}
		{if !$p.in_stock}
		<span class="miniDiscount bg-danger t4-product-card__badge t4-product-card__badge--oos">{'Out of Stock'|translate}</span>
		{/if}
		<button type="button" class="t4-product-card__fav like-button toggle-favorite" data-id="{$p.id_product}" aria-label="{'Favorites'|translate}">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
		</button>
		<a class="product-image-wrapper t4-product-card__image d-flex align-items-center justify-content-center" href="{$p.url}" title="{$p.product_name|escape}">
			{if $p@first}
			<img src="{$p.image_url}" class="product-image img-fluid" fetchpriority="high" decoding="async" alt="{$p.product_name|escape}">
			{else}
			<img src="{$p.image_url}" class="product-image img-fluid" loading="lazy" decoding="async" alt="{$p.product_name|escape}">
			{/if}
		</a>
		<div class="product-info t4-product-card__body d-flex flex-column flex-grow-1 panelBody">
			<a href="{$p.url}" title="{$p.product_name|escape}" class="text-decoration-none">
				<h3 class="product-title t4-product-card__title">{$p.product_name|escape}</h3>
			</a>
			<div class="d-flex align-items-center gap-3 productDiscount t4-product-card__prices justify-content-center mt-auto">
				{if $p.has_discount}<div class="discount-badge">%{Tools::getDiscount($p.old_price, $p.price)}</div>{/if}
				<div>
					{if $p.has_discount}<div class="old-price">{$p.old_price_formatted}</div>{/if}
					<div class="current-price">{$p.price_formatted}</div>
				</div>
			</div>
			{if $p.in_stock}
			<button type="button" class="btn btn-details t4-product-card__cart w-100 addtocart mt-2" data-id="{$p.id_product}">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
				{'Add to Cart'|translate}
			</button>
			{else}
			<a href="{$p.url}" class="btn btn-details t4-product-card__cart w-100 mt-2">{'Details'|translate}</a>
			{/if}
		</div>
	</article>
{/foreach}
</div>
