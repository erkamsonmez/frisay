<div class="t4-widget t4-widget--category sm-section t4-section"{if $layout|default:'grid' == 'list'} data-t4-rail-section{/if}>
	{if $title|default:'' != '' || $url|default:'' != '' || ($layout|default:'grid' == 'list' && $products|@count > 1)}
	<div class="sm-section__head t4-section__head">
		{if $title|default:'' != ''}<h2 class="sm-section__title t4-section__title">{$title|escape}</h2>{/if}
		<div class="t4-section__tools">
			{if $url|default:'' != ''}
			<a href="{$url|escape}" class="sm-section__link t4-section__link">{'View All'|translate} →</a>
			{/if}
			{if $layout|default:'grid' == 'list' && $products|@count > 1}
			<div class="t4-rail__nav">
				<button type="button" class="t4-rail__btn" data-t4-rail-prev aria-label="{'Previous'|translate}">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
				</button>
				<button type="button" class="t4-rail__btn" data-t4-rail-next aria-label="{'Next'|translate}">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
				</button>
			</div>
			{/if}
		</div>
	</div>
	{/if}
	{if $products|@count}
		{if $layout|default:'grid' == 'list'}
		<div class="t4-rail t4-cat-carousel" data-t4-rail>
			<div class="t4-rail__track">
				{foreach $products as $p}
				<article class="panel product-card t4-product-card t4-rail__card d-flex flex-column">
					{if $p.has_discount}
						<span class="miniDiscount t4-product-card__badge">-%{Tools::getDiscount($p.old_price, $p.price)}</span>
					{elseif $p.label}
						<span class="miniDiscount t4-product-card__badge">{$p.label|escape}</span>
					{/if}
					<a class="product-image-wrapper t4-product-card__image d-flex align-items-center justify-content-center" href="{$p.url}" title="{$p.product_name|escape}">
						<img src="{$p.image_url}" class="product-image img-fluid" loading="lazy" decoding="async" alt="{$p.product_name|escape}">
					</a>
					<div class="product-info t4-product-card__body d-flex flex-column flex-grow-1">
						<a href="{$p.url}" title="{$p.product_name|escape}" class="text-decoration-none">
							<h3 class="product-title t4-product-card__title">{$p.product_name|escape}</h3>
						</a>
						<div class="d-flex align-items-center gap-3 productDiscount t4-product-card__prices justify-content-center">
							{if $p.has_discount}<div class="discount-badge">%{Tools::getDiscount($p.old_price, $p.price)}</div>{/if}
							<div>
								{if $p.has_discount}<div class="old-price">{$p.old_price_formatted}</div>{/if}
								<div class="current-price">{$p.price_formatted}</div>
							</div>
						</div>
						<button type="button" class="btn btn-details t4-product-card__cart w-100 addtocart" data-id="{$p.id_product}">
							{'Add to Cart'|translate}
						</button>
					</div>
				</article>
				{/foreach}
			</div>
		</div>
		{else}
		{include file='theme4/productGrid.tpl' products=$products}
		{/if}
	{/if}
</div>
