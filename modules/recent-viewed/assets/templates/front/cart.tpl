<section class="rv-cart" data-rv-cart>
	<div class="rv-cart__tabs" role="tablist">
		<button type="button" class="rv-cart__tab is-active" data-rv-tab="recent" role="tab" aria-selected="true">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
			{'Recently viewed'|translate}
		</button>
		<button type="button" class="rv-cart__tab" data-rv-tab="foryou" role="tab" aria-selected="false">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
			{'For you'|translate}
		</button>
	</div>
	<div class="rv-cart__panel is-active" data-rv-panel="recent">
		{if $recentProducts|@count > 0}
		<div class="rv-cart__grid">
			{foreach $recentProducts as $product}
			<article class="rv-cart-item">
				<a href="{$product.url|escape}" class="rv-cart-item__media">
					<img src="{$product.image_url|escape}" alt="{$product.product_name|escape}" loading="lazy" width="72" height="72">
				</a>
				<div class="rv-cart-item__body">
					<a href="{$product.url|escape}" class="rv-cart-item__name">{$product.product_name|escape}</a>
					<div class="rv-cart-item__price">{$product.price_formatted|escape}</div>
				</div>
				<button type="button" class="rv-cart-item__btn addtocart" data-id="{$product.id_product}"{if !$product.in_stock} disabled{/if}>+ {'Add to Cart'|translate}</button>
			</article>
			{/foreach}
		</div>
		{else}
		<p class="rv-cart__empty">{'No recently viewed products'|translate}</p>
		{/if}
	</div>
	<div class="rv-cart__panel" data-rv-panel="foryou" hidden>
		{if $forYouProducts|@count > 0}
		<div class="rv-cart__grid">
			{foreach $forYouProducts as $product}
			<article class="rv-cart-item">
				<a href="{$product.url|escape}" class="rv-cart-item__media">
					<img src="{$product.image_url|escape}" alt="{$product.product_name|escape}" loading="lazy" width="72" height="72">
				</a>
				<div class="rv-cart-item__body">
					<a href="{$product.url|escape}" class="rv-cart-item__name">{$product.product_name|escape}</a>
					<div class="rv-cart-item__price">{$product.price_formatted|escape}</div>
				</div>
				<button type="button" class="rv-cart-item__btn addtocart" data-id="{$product.id_product}"{if !$product.in_stock} disabled{/if}>+ {'Add to Cart'|translate}</button>
			</article>
			{/foreach}
		</div>
		{else}
		<p class="rv-cart__empty">{'No for you products'|translate}</p>
		{/if}
	</div>
</section>
