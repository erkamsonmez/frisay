{if !$configured}
<div class="frisay-product-panel">
	<div class="alert alert-warning mb-0">
		<strong>FriSay uzak mağaza API yapılandırılmamış.</strong>
		<a href="{$settingsUrl|escape}" class="alert-link">Ayarlara git</a>
	</div>
</div>
{else}
<div class="frisay-product-panel" data-id="{$id_product}">
	{if $frisay_linked}
	<div class="mb-2 small text-muted">
		Uzak ürün ID: <code>{$frisay_remote_id|escape}</code>
	</div>
	<div class="row g-2 mb-3">
		<div class="col-md-6">
			<label class="form-label small mb-0">Uzak satış fiyatı</label>
			<input type="number" step="0.01" min="0" class="form-control frisay-sale-price-input" value="{if $frisay_sale_price > 0}{$frisay_sale_price|escape}{/if}" placeholder="Satış fiyatı">
		</div>
	</div>
	<div class="d-flex flex-wrap gap-2">
		<button type="button" class="btn btn-dark frisay-price-btn" data-url="{$priceUrl|escape}" data-id="{$id_product}" data-platform="frisay">Fiyat / Stok Güncelle</button>
		<button type="button" class="btn btn-outline-primary frisay-send-btn" data-url="{$syncUrl|escape}" data-id="{$id_product}" data-platform="frisay">Ürünü güncelle</button>
		<button type="button" class="btn btn-outline-danger frisay-unlink-btn" data-url="{$unlinkUrl|escape}" data-id="{$id_product}" data-platform="frisay">Bağlantıyı Sil</button>
	</div>
	{else}
	<div class="alert alert-light border small">Uzak FriSay mağazasındaki ürün ID’sini yazın veya bu ürünü uzak mağazaya gönderin.</div>
	<label class="form-label">Uzak ürün ID</label>
	<div class="input-group mb-2">
		<input type="text" class="form-control frisay-remote-id-input" value="" placeholder="Örn. 15">
		<button type="button" class="btn btn-primary frisay-link-existing-btn" data-url="{$linkExistingUrl|escape}" data-id="{$id_product}" data-platform="frisay">Ürünle eşleştir</button>
	</div>
	<button type="button" class="btn btn-outline-dark frisay-send-btn" data-url="{$syncUrl|escape}" data-id="{$id_product}" data-platform="frisay">Ürünü uzak mağazaya gönder</button>
	{/if}
	<span class="small d-block mt-3 frisay-action-msg text-muted"></span>
</div>
{/if}
