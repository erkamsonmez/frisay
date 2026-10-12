{include file='admin/marketplace/_nav.tpl'}

<div class="admin-panel p-3">
	<div class="mp-filter-card mb-3">
		<div class="mp-filter-header">
			<div>
				<div class="mp-filter-title">
					{include file='admin/partials/filter-funnel-icon.tpl'}
					{'Filters'|adminT}
				</div>
				<div class="mp-filter-sub">{'Product name or barcode'|adminT}</div>
			</div>
		</div>
		<form method="get" action="{$adminUrl}marketplace-products" id="mpProductsFilterForm">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label for="mp_prd_q">{'Search'|adminT}</label>
					<input type="text" id="mp_prd_q" name="q" class="form-control form-control-sm" placeholder="{'Product name or barcode'|adminT}" value="{$searchQuery|escape}">
				</div>
				<div class="col-md-4 mb-3">
					<label for="mp_prd_filter">{'Status'|adminT}</label>
					<select id="mp_prd_filter" name="filter" class="form-select form-select-sm">
						<option value="all"{if $linkFilter == 'all'} selected{/if}>{'All'|adminT}</option>
						<option value="linked"{if $linkFilter == 'linked'} selected{/if}>{'Marketplace Products'|adminT}</option>
						<option value="unlinked"{if $linkFilter == 'unlinked'} selected{/if}>{'Store Products'|adminT}</option>
					</select>
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="mpProductsFilterForm" class="btn btn-warning">{'Filter'|adminT}</button>
				<a href="{$productsUrl|escape}" class="btn btn-outline-secondary">{'Clean'|adminT}</a>
			</div>
			<div class="mp-filter-actions__right">
				<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#trendyolProductImportModal">{'Import Product'|adminT}</button>
			</div>
		</div>
	</div>

	{if !$catalogProducts|@count}
	<p class="text-muted mb-0">{'Not found'|adminT}</p>
	{else}
	<div class="vstack gap-3 marketplace-catalog ty-marketplace-catalog">
		{foreach $catalogProducts as $item}
		{assign var=row value=$item.row}
		<article class="mp-product-card" id="mp-item-{$row.id_product}" data-product-id="{$row.id_product}">
			<div class="mp-product-card-main">
				<div class="mp-product-media">
					<img src="{$row.image_url|escape}" alt="" width="64" height="64">
					<span class="mp-stock-badge">{$row.stock_formatted|default:$row.stock}</span>
				</div>
				<div class="mp-product-info">
					<div class="mp-product-title-row">
						<a class="mp-product-title" href="{$row.edit_url|escape}">{$row.product_name|escape}</a>
						{if $row.has_variations}<span class="badge text-bg-info">Varyasyon</span>{/if}
					</div>
					<div class="mp-product-sub">
						<span>[{$row.brand_name|escape}]</span>
						{if $row.stock_code|default:'' != ''}
						<span class="dot">·</span>
						<span>{$row.stock_code|escape}</span>
						{/if}
					</div>
					<div class="mp-product-cat">
						{$row.category_name|escape}
						{if $row.barcode|default:'' != ''}
						<span class="dot">·</span>
						{$row.barcode|escape}
						{/if}
					</div>
					{if $row.variation_chips|@count}
					<div class="mp-variant-chips">
						{foreach $row.variation_chips as $chip}
						<span class="mp-variant-chip">{$chip.label|escape} · {$chip.stock|escape}</span>
						{/foreach}
					</div>
					{/if}
				</div>
				<div class="mp-product-side">
					<div class="mp-product-metrics">
						<span class="mp-metric-chip" title="Mağaza fiyatı">
							<span class="mp-price-dot"></span>
							<strong>{$row.price_formatted}</strong>
							<small>KDV %{$row.vat|string_format:"%.0f"}</small>
						</span>
						<button type="button" class="mp-metric-chip quick-stock-btn" data-id="{$row.id_product}" data-name="{$row.product_name|escape}" data-stock="{$row.stock}" title="Stok güncelle">
							<strong class="stock-display-val">{$row.stock_formatted|default:$row.stock}</strong>
							<small>Adt</small>
						</button>
					</div>
					<div class="mp-channel-shortcuts">
						{foreach $marketplacePlatforms as $platform}
						{if $platform.active && !$platform.settings_only}
						{assign var=isOn value=0}
						{if $platform.key == 'trendyol' && $row.ty_linked}{assign var=isOn value=1}{/if}
						{if $platform.key == 'hepsiburada' && $row.hb_linked}{assign var=isOn value=1}{/if}
						{if $platform.key == 'n11' && $row.n11_linked}{assign var=isOn value=1}{/if}
						{if $platform.key == 'prestashop' && $row.ps_linked}{assign var=isOn value=1}{/if}
						{if $platform.key == 'frisay' && $row.frisay_linked}{assign var=isOn value=1}{/if}
						{assign var=modalTarget value='mp-modal-'|cat:$row.id_product}
						{if $platform.key == 'hepsiburada'}{assign var=modalTarget value='mp-modal-hb-'|cat:$row.id_product}{/if}
						{if $platform.key == 'n11'}{assign var=modalTarget value='mp-modal-n11-'|cat:$row.id_product}{/if}
						{if $platform.key == 'prestashop'}{assign var=modalTarget value='mp-modal-ps-'|cat:$row.id_product}{/if}
						{if $platform.key == 'frisay'}{assign var=modalTarget value='mp-modal-frisay-'|cat:$row.id_product}{/if}
						<button type="button" class="mp-channel-shortcut{if $isOn} on{else} off{/if}" data-bs-toggle="modal" data-bs-target="#{$modalTarget}" title="{$platform.label|escape}">
							<img src="{$domain}templates/admin/img/{$platform.key|escape}.png" alt="{$platform.label|escape}" width="28" height="28" onerror="var b=this.closest('button'); if(b) b.style.display='none';">
						</button>
						{/if}
						{/foreach}
					</div>
				</div>
				<div class="dropdown mp-product-main-menu">
					<button type="button" class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false" title="Ürün menüsü">⋯</button>
					<ul class="dropdown-menu dropdown-menu-end">
						<li><a class="dropdown-item" href="{$row.edit_url|escape}">Düzenle</a></li>
					</ul>
				</div>
			</div>

			<div class="mp-product-listings">
			{if $row.listings|@count}
				{foreach $row.listings as $listing}
				<div class="mp-listing-card status-{$listing.status|escape}" data-marketplace="{$listing.platform|escape}" data-product-id="{$row.id_product}" data-status="{$listing.status|escape}">
					<div class="mp-listing-row1">
						<img class="mp-listing-mp-img" src="{$domain}templates/admin/img/{$listing.platform|escape}.png" alt="" width="22" height="22" onerror="this.style.display='none'">
						{if $listing.status == 'sale'}
						<span class="mp-sale-dot" title="Satışta"></span>
						{elseif $listing.status == 'pending'}
						<span class="mp-sale-dot pending" title="Onay bekliyor"></span>
						{elseif $listing.status == 'paused'}
						<span class="mp-sale-dot paused" title="Duraklatıldı"></span>
						{else}
						<span class="mp-sale-dot off" title="Bağlı"></span>
						{/if}
						<button type="button" class="mp-listing-price" data-bs-toggle="modal" data-bs-target="#{$listing.modal_id}">{$listing.price_formatted|escape}</button>
						<div class="dropdown ms-auto">
							<button type="button" class="btn btn-sm btn-light py-0 px-2" data-bs-toggle="dropdown" aria-expanded="false">⋯</button>
							<ul class="dropdown-menu dropdown-menu-end mp-listing-menu">
								<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#{$listing.modal_id}">Düzenle</button></li>
								{if $listing.platform == 'trendyol'}
								{if $listing.status == 'paused'}
								<li><button type="button" class="dropdown-item js-listing-sale-toggle" data-paused="0">Satışa başlat</button></li>
								{else}
								<li><button type="button" class="dropdown-item js-listing-sale-toggle" data-paused="1">Satışa durdur</button></li>
								{/if}
								<li><button type="button" class="dropdown-item js-listing-stock"{if $row.has_variations} data-variants="1"{/if}>{if $row.has_variations}Varyasyon stoklarını gönder{else}Stok güncelle{/if}</button></li>
								<li><button type="button" class="dropdown-item js-listing-refresh">Güncel durum al</button></li>
								{if $listing.product_url}
								<li><a class="dropdown-item" href="{$listing.product_url|escape}" target="_blank" rel="noopener">Pazaryerinde aç</a></li>
								{else}
								<li><button type="button" class="dropdown-item js-listing-open">Pazaryerinde aç</button></li>
								{/if}
								<li><hr class="dropdown-divider"></li>
								<li><button type="button" class="dropdown-item text-danger js-listing-unlink">Bağlantıyı sil</button></li>
								{else}
								<li><button type="button" class="dropdown-item js-listing-stock">Stok güncelle</button></li>
								<li><hr class="dropdown-divider"></li>
								<li><button type="button" class="dropdown-item text-danger js-listing-unlink">Bağlantıyı sil</button></li>
								{/if}
							</ul>
						</div>
					</div>
					<div class="mp-listing-row2">
						<span class="mp-listing-store">{$listing.label|escape}</span>
						{if $listing.profit_pct !== null}
						<span class="mp-listing-profit{if $listing.profit_pct < 0} is-neg{else} is-pos{/if}">%{$listing.profit_pct|escape} kârlılık</span>
						{/if}
					</div>
				</div>
				{/foreach}
			{else}
				<div class="mp-listing-empty">Henüz pazaryeri bağlantısı yok. Sağdaki kanal ikonlarından gönder / bağla.</div>
			{/if}
			</div>
		</article>
		<div class="modal fade" id="mp-modal-{$row.id_product}" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header"><div><h5 class="modal-title">{'Trendyol Connect'|adminT}</h5><div class="small text-muted">{$row.product_name|escape}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button></div>
					<div class="modal-body">{$item.panel_html nofilter}</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="mp-modal-hb-{$row.id_product}" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header"><div><h5 class="modal-title">Hepsiburada Bağlantı</h5><div class="small text-muted">{$row.product_name|escape}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button></div>
					<div class="modal-body">{$item.panel_html_hb nofilter}</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="mp-modal-n11-{$row.id_product}" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header"><div><h5 class="modal-title">N11 Bağlantı</h5><div class="small text-muted">{$row.product_name|escape}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button></div>
					<div class="modal-body">{$item.panel_html_n11 nofilter}</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="mp-modal-ps-{$row.id_product}" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header"><div><h5 class="modal-title">PrestaShop Bağlantı</h5><div class="small text-muted">{$row.product_name|escape}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button></div>
					<div class="modal-body">{$item.panel_html_ps nofilter}</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="mp-modal-frisay-{$row.id_product}" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header"><div><h5 class="modal-title">FriSay Bağlantı</h5><div class="small text-muted">{$row.product_name|escape}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button></div>
					<div class="modal-body">{$item.panel_html_frisay nofilter}</div>
				</div>
			</div>
		</div>
		{/foreach}
	</div>
	{/if}
</div>

<div class="modal fade" id="quickStockModal" tabindex="-1">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content border-0 shadow">
			<form id="quickStockForm">
				<div class="modal-header">
					<div>
						<h5 class="modal-title mb-0">{'Edit Stock Quantity'|adminT}</h5>
						<small class="text-muted quick-stock-product-name"></small>
					</div>
					<button class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<input type="hidden" name="id_product" id="qs_id_product">
					<div class="row align-items-center mb-4">
						<label class="col-4 col-form-label fw-semibold">
							{'Stock Quantity'|adminT}
							<span class="text-danger">*</span>
						</label>
						<div class="col-8">
							<input id="qs_stock" name="stock" type="number" min="0" class="form-control" required>
						</div>
					</div>
					<div class="form-check form-switch fs-6">
						<input class="form-check-input" type="checkbox" id="qs_sync" name="sync_marketplaces" value="1" checked>
						<label class="form-check-label" for="qs_sync">{'Update marketplace stocks after saving'|adminT}</label>
					</div>
					<div class="qs-msg mt-3 small"></div>
				</div>
				<div class="modal-footer">
					<button class="btn btn-light" data-bs-dismiss="modal" type="button">{'Cancel'|adminT}</button>
					<button class="btn btn-primary id-qs-submit-btn" type="submit">
						<span class="spinner-border spinner-border-sm me-2 d-none qs-spinner"></span>
						{'Save'|adminT}
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

{if $pagination.total_pages > 1}
<div class="mt-3">
	{include file='admin/plugin/pagination.tpl'}
</div>
{/if}

{include file='admin/marketplace/import_modal.tpl'}

<script>
window.trendyolBrandsApiUrl = {$brandsUrl|@json_encode nofilter};
window.trendyolCategoriesApiUrl = {$categoriesUrl|@json_encode nofilter};
window.trendyolAttributesApiUrl = {$attributesUrl|@json_encode nofilter};
window.trendyolUpdateStockApiUrl = {$updateStockUrl|@json_encode nofilter};
window.trendyolSalePauseApiUrl = {$salePauseUrl|@json_encode nofilter};
window.trendyolUnlinkApiUrl = {$unlinkUrl|@json_encode nofilter};
window.trendyolRefreshApiUrl = {$refreshUrl|@json_encode nofilter};
window.trendyolPriceApiUrl = {$priceUrl|@json_encode nofilter};
window.__adminCsrfToken = {$adminToken|@json_encode nofilter};
</script>
<script src="{$importJsUrl|escape}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	if (window.TrendyolAdmin && typeof window.TrendyolAdmin.bindProductActions === 'function') {
		window.TrendyolAdmin.bindProductActions();
	}
});
</script>

{include file='admin/marketplace/_close.tpl'}
