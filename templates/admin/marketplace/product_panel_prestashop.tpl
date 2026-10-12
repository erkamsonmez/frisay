{if !$configured}
<div class="ps-product-panel">
	<div class="alert alert-warning mb-0">
		<strong>PrestaShop API yapılandırılmamış.</strong>
		<a href="{$settingsUrl|escape}" class="alert-link">Ayarlara git</a>
	</div>
</div>
{else}
<div class="ps-product-panel" data-id="{$id_product}">
	{if $ps_linked}
	<div class="mb-2 small text-muted">
		PrestaShop ID: <code>{$ps_product_id|escape}</code>
	</div>
	<div class="row g-2 mb-3">
		<div class="col-md-6">
			<label class="form-label small mb-0">PrestaShop satış fiyatı</label>
			<input type="number" step="0.01" min="0" class="form-control ps-sale-price-input" value="{if $ps_sale_price > 0}{$ps_sale_price|escape}{/if}" placeholder="Satış fiyatı">
		</div>
	</div>
	<div class="d-flex flex-wrap gap-2">
		<button type="button" class="btn btn-dark ps-price-btn" data-url="{$priceUrl|escape}" data-id="{$id_product}" data-platform="prestashop">Fiyat / Stok Güncelle</button>
		<button type="button" class="btn btn-outline-primary ps-send-btn" data-url="{$syncUrl|escape}" data-id="{$id_product}" data-platform="prestashop">Ürünü güncelle</button>
		<button type="button" class="btn btn-outline-danger ps-unlink-btn" data-url="{$unlinkUrl|escape}" data-id="{$id_product}" data-platform="prestashop">Bağlantıyı Sil</button>
	</div>
	{else}
	<div class="alert alert-light border small">PrestaShop’taki ürün ID’sini yazın veya bu mağaza ürününü PrestaShop’a gönderin.</div>
	<label class="form-label">PrestaShop ürün ID</label>
	<div class="input-group mb-2">
		<input type="text" class="form-control ps-product-id-input" value="" placeholder="Örn. 42">
		<button type="button" class="btn btn-primary ps-link-existing-btn" data-url="{$linkExistingUrl|escape}" data-id="{$id_product}" data-platform="prestashop">Ürünle eşleştir</button>
	</div>
	<button type="button" class="btn btn-outline-dark ps-send-btn" data-url="{$syncUrl|escape}" data-id="{$id_product}" data-platform="prestashop">Ürünü PrestaShop’a gönder</button>
	{/if}
	<span class="small d-block mt-3 ps-action-msg text-muted"></span>
</div>
{/if}
