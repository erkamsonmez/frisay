{assign var=t4Discount value=0}
{if $oldPrice > 0}
{assign var=t4Discount value=Tools::getDiscount($oldPrice, $price)}
{/if}

<div class="t4-product">
	<div class="t4-product__toolbar">
		<div class="t4-product__crumb">
			{include file='./partials/breadcrumb.tpl'}
		</div>
		<nav class="t4-product__pager" aria-label="{'Product navigation'|translate}">
			{if $relatedProducts|@count > 1}
			<a href="{$relatedProducts[1].url|escape}" class="t4-product__pager-btn" title="{'Previous'|translate}" aria-label="{'Previous'|translate}">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
			</a>
			{else}
			<span class="t4-product__pager-btn is-disabled" aria-hidden="true">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
			</span>
			{/if}
			<a href="{$domain}{$product.category_link|escape}" class="t4-product__pager-btn" title="{'Categories'|translate}" aria-label="{'Categories'|translate}">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
			</a>
			{if $relatedProducts|@count > 0}
			<a href="{$relatedProducts[0].url|escape}" class="t4-product__pager-btn" title="{'Next'|translate}" aria-label="{'Next'|translate}">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
			</a>
			{else}
			<span class="t4-product__pager-btn is-disabled" aria-hidden="true">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
			</span>
			{/if}
		</nav>
	</div>

	<div class="product-showcase t4-product__showcase">
		<div class="product-showcase__inner t4-product__inner">
			<div class="row g-4 g-xl-5">
				<div class="col-lg-5">
					<div class="product-media-card t4-product__media">
						<div class="product-gallery" data-image-count="{$images|@count}">
							<div class="product-gallery__main t4-product__gallery-main">
								{if $images|@count > 1}
								<button type="button" class="product-gallery__nav product-gallery__nav--prev" aria-label="{'Previous image'|translate}">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
								</button>
								{/if}
								<img id="main-display" fetchpriority="high" decoding="async" data-bs-toggle="modal" data-bs-target="#imageModal" class="img-responsives cursorPointer t4-product__image" src="{$imageUrl|escape}" alt="{$productName|escape}" />
								{if $images|@count > 1}
								<button type="button" class="product-gallery__nav product-gallery__nav--next" aria-label="{'Next image'|translate}">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
								</button>
								{/if}
								<button type="button" class="t4-product__alert-fab priceAllertButton alertButton" data-bs-toggle="modal" data-bs-target="#priceModal" data-id="{$product.id_product}" data-price="{$product.price}" aria-label="{'Price Alert'|translate|default:'Fiyat Alarmı'}" title="{'Price Alert'|translate|default:'Fiyat Alarmı'}">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
								</button>
								<button type="button" class="t4-product__expand" data-bs-toggle="modal" data-bs-target="#imageModal" aria-label="{'Zoom'|translate|default:'Büyüt'}">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="m21 3-7 7"/><path d="M9 21H3v-6"/><path d="m3 21 7-7"/></svg>
								</button>
							</div>
							{if $images|@count > 1}
							<div class="product-gallery__thumbs">
								{foreach $images as $img name=productGallery}
								<button type="button" class="product-gallery__thumb thumb-img{if $smarty.foreach.productGallery.first} active{/if}" data-image="{$img.url|escape}" data-index="{$smarty.foreach.productGallery.index}" aria-label="{$productName|escape}">
									<img loading="lazy" src="{$img.url|escape}" alt="{$productName|escape}">
								</button>
								{/foreach}
							</div>
							{/if}
						</div>
					</div>
				</div>

				<div class="col-lg-7">
					<div class="product-purchase-card t4-product__buybox">
						<div class="product-heading t4-product__heading">
							<h1>{$productName|escape}</h1>
							<div class="t4-product__reviews">
								<span class="t4-product__stars{if $product.review_count|default:0 == 0} is-empty{/if}" aria-hidden="true">★★★★★</span>
								<span class="t4-product__reviews-label">{'Reviews'|translate|default:'Yorum'} ({$product.review_count|default:0})</span>
							</div>
							{if $hooks.product_inf}{$hooks.product_inf nofilter}{/if}
						</div>

						<div class="product-price-block t4-product__prices">
							{if $oldPrice > 0}<div class="old-price" id="productOldPrice">{Tools::displayPrice($oldPrice)}</div>{/if}
							<div class="current-price" id="productCurrentPrice" data-base-price="{$price}">{Tools::displayPrice($price)}</div>
						</div>

						<ul class="t4-product__specs">
							{if $havale|default:0 > 0}
							<li>
								<span class="t4-product__specs-label">{'Bank Transfer'|translate|default:'Havale Fiyatı'}</span>
								<span class="t4-product__specs-value"><strong>{Tools::displayPrice($price * (1 - $havale / 100))}</strong> <em>(%{$havale|string_format:"%g"} {'discount'|translate})</em></span>
							</li>
							{/if}
							{if $brandName}
							<li>
								<span class="t4-product__specs-label">{'Brand'|translate}</span>
								<span class="t4-product__specs-value"><a href="{$brandUrl|escape}">{$brandName|escape}</a></span>
							</li>
							{/if}
							{if $stockCode}
							<li>
								<span class="t4-product__specs-label">{'Stock Code'|translate}</span>
								<span class="t4-product__specs-value">{$stockCode|escape}</span>
							</li>
							{/if}
							<li>
								<span class="t4-product__specs-label">{'Cargo'|translate}</span>
								<span class="t4-product__specs-value">{if $price >= $freeCargo}{'Free Shipping'|translate|default:'Ücretsiz Kargo'}{else}{Tools::displayPrice($cargoPrice)}{/if}</span>
							</li>
						</ul>

						{if $shortDescription}
						<p class="t4-product__excerpt">{$shortDescription|escape}</p>
						{/if}

						<div class="productCenter t4-product__center">
							{if $hasVariations}
							<div class="product-variations mb-3" id="productVariations" data-required-groups="{$variationGroups|@count}" data-select-hint="{'Select product options'|translate|escape}" data-out-hint="{'Out Of Stock'|translate|escape}">
								{foreach from=$variationGroups item=group name=varGroup}
								<div class="product-variation-group mb-3" role="radiogroup" aria-labelledby="variationLabel{$smarty.foreach.varGroup.index}">
									<div class="product-variation-label" id="variationLabel{$smarty.foreach.varGroup.index}">
										<span class="product-variation-name">{$group.name|escape}</span>
										<span class="product-variation-selected" data-group-label="{$group.name|escape}"></span>
									</div>
									<div class="product-variation-options">
										{foreach $group.values as $val}
										<button type="button" role="radio" aria-checked="false" class="product-variation-option" data-group="{$group.name|escape}" data-value="{$val|escape}">{$val|escape}</button>
										{/foreach}
									</div>
								</div>
								{/foreach}
								<p class="product-variation-summary small text-muted mb-1 d-none" id="variationSummary"></p>
								<p class="small text-muted mb-0" id="variationHint">{'Select product options'|translate}</p>
								<input type="hidden" id="selectedVariationId" value="0">
							</div>
							<script type="application/json" id="variationItemsData">{$variationItemsJson nofilter}</script>
							{/if}

							{include file='./partials/cart-promo.tpl'}

							{if $inStock}
							<div class="product-buy-row t4-product__actions">
								<div class="qty-picker" id="qtyPicker">
									<button type="button" class="qty-btn" onclick="updateQty(-1)" aria-label="-">−</button>
									<input type="text" value="1" id="qty-input" class="qty-input" readonly data-max="{$stock}" aria-label="{'Quantity'|translate}">
									<button type="button" class="qty-btn" onclick="updateQty(1)" aria-label="+">+</button>
								</div>
								<button type="button" class="btn btn-details cart-button addtocart t4-product__cart{if $hasVariations} requires-variation{/if}" data-id="{$product.id_product}" data-variation="0"{if $hasVariations} disabled{/if}>
									{'Add To Cart'|translate}
								</button>
								<button type="button" class="btn t4-product__buynow addtocart{if $hasVariations} requires-variation{/if}" data-id="{$product.id_product}" data-variation="0" data-buynow="1"{if $hasVariations} disabled{/if}>
									{'Buy Now'|translate|default:'Hemen Satın Al'}
								</button>
								<button type="button" class="t4-product__fav-btn like-button toggle-favorite{if $isFavorite} is-active active{/if}" data-id="{$product.id_product}" aria-label="{'Add to Favorites'|translate|default:'Favorilere Ekle'}" title="{'Add to Favorites'|translate|default:'Favorilere Ekle'}">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
								</button>
							</div>
							{else}
							<div class="product-buy-row t4-product__actions t4-product__actions--oos">
								<button type="button" class="btn btn-secondary" disabled>{'Out Of Stock'|translate}</button>
								<button type="button" class="t4-product__fav-btn like-button toggle-favorite{if $isFavorite} is-active active{/if}" data-id="{$product.id_product}" aria-label="{'Add to Favorites'|translate|default:'Favorilere Ekle'}" title="{'Add to Favorites'|translate|default:'Favorilere Ekle'}">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
								</button>
							</div>
							<p class="t4-product__oos-hint small text-muted mt-2 mb-0">{'This product is currently unavailable. Leave your email below to get notified when it is back.'|translate|default:'Ürün şu an stokta yok. Tekrar gelince haber almak için e-posta bırakabilirsiniz.'}</p>
							{/if}

							{if $hooks.product_detail}
							<div class="product-detail-hook mt-3">
								{$hooks.product_detail nofilter}
							</div>
							{/if}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="product-content-tabs t4-product__tabs">
		<ul class="nav nav-tabs" id="productTabs">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" data-bs-toggle="tab" data-bs-target="#description" type="button">{'Description'|translate}</button>
			</li>
			{if $productVideoEmbed}
			<li class="nav-item" role="presentation">
				<button class="nav-link" data-bs-toggle="tab" data-bs-target="#video" type="button">{'Video'|translate}</button>
			</li>
			{/if}
			{if $hooks.product_tab}{$hooks.product_tab nofilter}{/if}
		</ul>
		<div class="tab-content product-tab-content">
			<div class="tab-pane fade show active" id="description">
				{if $description}{$description nofilter}{/if}
			</div>
			<div class="tab-pane" id="video">
				{if $productVideoEmbed}
				<div class="product-video ratio ratio-16x9 mt-3">
					<iframe src="{$productVideoEmbed|escape}" title="{$productName|escape}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>
				</div>
				{/if}
			</div>
			{if $hooks.product_tab_content}{$hooks.product_tab_content nofilter}{/if}
		</div>
	</div>

	{if $hooks.product}{$hooks.product nofilter}{/if}

	{if $brandProducts|default:[]|@count > 0}
	<section class="t4-product-rail" data-t4-rail-section>
		<div class="t4-product-rail__head">
			<h2 class="t4-product-rail__title">
				{if $brandName}
					{$brandName|escape} {'brand products'|translate|default:'markasına ait diğer ürünler'}
				{else}
					{'Related Products'|translate|default:'İlgili Ürünler'}
				{/if}
			</h2>
			{if $brandProducts|@count > 1}
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
		<div class="t4-rail" data-t4-rail>
			<div class="t4-rail__track">
				{foreach $brandProducts as $p}
				<article class="panel product-card t4-product-card t4-rail__card d-flex flex-column">
					{if $p.has_discount}
						<span class="miniDiscount t4-product-card__badge">-%{Tools::getDiscount($p.old_price, $p.price)}</span>
					{/if}
					<a class="product-image-wrapper t4-product-card__image d-flex align-items-center justify-content-center" href="{$p.url}" title="{$p.product_name|escape}">
						<img src="{$p.image_url}" class="product-image img-fluid" loading="lazy" decoding="async" alt="{$p.product_name|escape}">
					</a>
					<div class="product-info t4-product-card__body d-flex flex-column flex-grow-1">
						<a href="{$p.url}" title="{$p.product_name|escape}" class="text-decoration-none">
							<h3 class="product-title t4-product-card__title">{$p.product_name|escape}</h3>
						</a>
						<div class="d-flex align-items-center gap-2 productDiscount t4-product-card__prices justify-content-center">
							{if $p.has_discount}<div class="old-price">{$p.old_price_formatted}</div>{/if}
							<div class="current-price">{$p.price_formatted}</div>
						</div>
						{if $p.in_stock}
						<button type="button" class="btn btn-details t4-product-card__cart w-100 addtocart" data-id="{$p.id_product}">{'Add to Cart'|translate}</button>
						{else}
						<a href="{$p.url}" class="btn btn-details t4-product-card__cart w-100">{'View'|translate}</a>
						{/if}
					</div>
				</article>
				{/foreach}
			</div>
		</div>
	</section>
	{/if}
</div>

{if $inStock}
<div class="t4-sticky-buy" id="t4StickyBuy" aria-hidden="true">
	<div class="t4-sticky-buy__inner sm-container">
		<div class="t4-sticky-buy__info">
			<img src="{$imageUrl|escape}" alt="{$productName|escape}" class="t4-sticky-buy__img" id="t4StickyBuyImg" width="48" height="48">
			<div class="t4-sticky-buy__meta">
				<strong class="t4-sticky-buy__name">{$productName|escape}</strong>
				{if $brandName}<span class="t4-sticky-buy__brand">{$brandName|escape}</span>{/if}
			</div>
		</div>
		<div class="t4-sticky-buy__actions">
			<div class="t4-sticky-buy__price" id="t4StickyBuyPrice">{Tools::displayPrice($price)}</div>
			<button type="button" class="btn t4-sticky-buy__cart addtocart{if $hasVariations} requires-variation{/if}" data-id="{$product.id_product}" data-variation="0"{if $hasVariations} disabled{/if}>
				{'Add To Cart'|translate}
			</button>
		</div>
	</div>
</div>
{/if}

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-600 modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title fs-5" id="imageModalLabel">{$productName|escape}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|translate}"></button>
			</div>
			<div class="modal-body product-lightbox">
				{if $images|@count > 1}
				<button type="button" class="product-lightbox__nav product-lightbox__nav--prev" id="imageModalPrev" aria-label="{'Previous image'|translate}">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
				</button>
				{/if}
				<img id="modal-display" src="{$imageUrl|escape}" class="imageFull" alt="{$productName|escape}" />
				{if $images|@count > 1}
				<button type="button" class="product-lightbox__nav product-lightbox__nav--next" id="imageModalNext" aria-label="{'Next image'|translate}">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
				</button>
				<div class="product-lightbox__counter"><span id="imageModalCounter">1</span> / {$images|@count}</div>
				{/if}
			</div>
		</div>
	</div>
</div>
