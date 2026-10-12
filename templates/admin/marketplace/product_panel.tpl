{if !$configured}
<div class="trendyol-product-panel">
	<div class="alert alert-warning mb-0">
		<strong>Trendyol API yapılandırılmamış.</strong>
		<a href="{$settingsUrl|escape}" class="alert-link">Ayarlara git</a>
	</div>
</div>
{else}
<div class="trendyol-product-panel" data-id="{$id_product}" data-variant-count="{$ty_variations|default:[]|@count}" data-variant-axis="{$ty_variant_axis|default:''|escape}">
	{if isset($mapping.barcode) && $mapping.barcode != ''}
	<div class="row g-2 mb-3">
		<div class="col-md-6">
			<label class="form-label small mb-0">Trendyol satış fiyatı</label>
			<input type="number" step="0.01" min="0" class="form-control ty-sale-price-input"{if $ty_has_price} value="{$ty_sale_price|escape}"{/if} placeholder="Satış fiyatı">
		</div>
		<div class="col-md-6">
			<label class="form-label small mb-0">Trendyol liste fiyatı</label>
			<input type="number" step="0.01" min="0" class="form-control ty-list-price-input"{if $ty_has_price && $ty_list_price > 0} value="{$ty_list_price|escape}"{/if} placeholder="Liste fiyatı">
		</div>
	</div>
	<div class="d-flex flex-wrap gap-2">
		<button type="button" class="btn btn-dark ty-price-btn" data-url="{$priceUrl|escape}" data-id="{$id_product}">Fiyat / Stok Güncelle</button>
		<button type="button" class="btn btn-outline-secondary ty-refresh-btn" data-url="{$refreshUrl|escape}" data-id="{$id_product}">Bilgileri Yenile</button>
		<button type="button" class="btn btn-outline-danger ty-unlink-btn" data-url="{$unlinkUrl|escape}" data-id="{$id_product}">Bağlantıyı Sil</button>
	</div>
	{else}
	<ul class="nav nav-pills nav-fill mb-3" role="tablist">
		<li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ty-create-{$id_product}" type="button">Trendyol'a ürün ekle</button></li>
		<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ty-link-{$id_product}" type="button">Mevcut ürünle eşleştir</button></li>
	</ul>
	<div class="tab-content">
		<div class="tab-pane fade show active" id="ty-create-{$id_product}">
			<div class="alert alert-primary small">Yeni Trendyol ürünü oluşturulur ve bu mağaza ürünüyle eşleştirilir.</div>
			<div class="row g-3">
				<div class="col-lg-6"><label class="form-label small mb-0">Marka ara</label><div class="ty-picker" data-type="brand" data-key="product-brand-{$id_product}"><input type="hidden" class="ty-picker-id ty-brand-id" value="{$ty_brand_id|escape}"><input type="hidden" class="ty-picker-name" value="{$ty_brand_name|escape}"><div class="ty-picker-selected mb-1">{if $ty_brand_id}<span class="badge text-bg-success">{if $ty_brand_name}{$ty_brand_name|escape}{else}#{$ty_brand_id}{/if}</span> <span class="text-muted small">#{$ty_brand_id}</span>{else}<span class="text-muted small">Seçilmedi</span>{/if}</div><div class="input-group"><input type="text" class="form-control ty-picker-query" placeholder="Marka adı" autocomplete="off"><button type="button" class="btn btn-outline-secondary ty-picker-clear">Temizle</button></div><div class="ty-picker-results mt-1"></div></div></div>
				<div class="col-lg-6"><label class="form-label small mb-0">Kategori ara</label><div class="ty-picker" data-type="category" data-key="product-cat-{$id_product}"><input type="hidden" class="ty-picker-id ty-category-id" value="{$ty_category_id|escape}"><input type="hidden" class="ty-picker-name" value="{$ty_category_name|escape}"><div class="ty-picker-selected mb-1">{if $ty_category_id}<span class="badge text-bg-success">{if $ty_category_name}{$ty_category_name|escape}{else}#{$ty_category_id}{/if}</span> <span class="text-muted small">#{$ty_category_id}</span>{else}<span class="text-muted small">Seçilmedi</span>{/if}</div><div class="input-group"><input type="text" class="form-control ty-picker-query" placeholder="Kategori adı" autocomplete="off"><button type="button" class="btn btn-outline-secondary ty-picker-clear">Temizle</button></div><div class="ty-picker-results mt-1"></div></div></div>
				<div class="col-md-6"><label class="form-label small mb-0">Trendyol satış fiyatı</label><input type="number" step="0.01" min="0" class="form-control ty-sale-price-input" placeholder="Satış fiyatı"></div>
				<div class="col-md-6"><label class="form-label small mb-0">Trendyol liste fiyatı</label><input type="number" step="0.01" min="0" class="form-control ty-list-price-input" placeholder="Liste fiyatı"></div>
				{if $ty_has_variations|default:false}
				<div class="col-12">
					<div class="alert alert-info small mb-2 py-2">Bu ürünün {$ty_variations|@count} varyasyonu var{if $ty_variant_axis} ({$ty_variant_axis|escape}){/if}. Her satır Trendyol'a ayrı barkod / stok olarak gider. Satış fiyatını buradan veya satırdan girin.</div>
					<div class="table-responsive ty-variant-table-wrap">
						<table class="table table-sm align-middle mb-0 ty-variant-table">
							<thead>
								<tr>
									<th>Varyasyon</th>
									<th>Barkod</th>
									<th>Stok</th>
									<th>Satış fiyatı</th>
								</tr>
							</thead>
							<tbody>
								{foreach $ty_variations as $v}
								<tr class="ty-variant-row" data-id-variation="{$v.id_variation|escape}">
									<td class="small">{$v.label|escape}{if $v.sku != ''} <span class="text-muted">({$v.sku|escape})</span>{/if}</td>
									<td class="small text-muted">{$v.barcode|escape}</td>
									<td style="width:110px"><input type="number" min="0" step="1" class="form-control form-control-sm ty-variant-stock" value="{$v.stock|escape}"></td>
									<td style="width:140px"><input type="number" min="0" step="0.01" class="form-control form-control-sm ty-variant-sale" value="{if $v.price !== null}{$v.price|escape}{/if}" placeholder="Satış fiyatı"></td>
								</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</div>
				{/if}
				<div class="col-12"><label class="form-label small mb-0">Kategori özellikleri</label><div class="ty-attr-form border rounded p-2 bg-white"><div class="text-muted small">Kategori seçince zorunlu alanlar burada açılır.</div></div><textarea class="ty-attributes d-none" rows="1">{$ty_attributes_json|escape}</textarea></div>
				<div class="col-12"><button type="button" class="btn btn-primary ty-sync-btn" data-url="{$syncUrl|escape}" data-id="{$id_product}">Trendyol'a ürün ekle</button></div>
			</div>
		</div>
		<div class="tab-pane fade" id="ty-link-{$id_product}">
			<div class="alert alert-light border small">Trendyol'da zaten bulunan ürünün barkodunu yazın. Ürün doğrulanır ve yalnızca bağlantı oluşturulur; yeni ürün gönderilmez.</div>
			<label class="form-label">Trendyol barkodu</label>
			<div class="input-group"><input type="text" class="form-control ty-existing-barcode-input" value="{$product_barcode|escape}" placeholder="Trendyol barkodu"><button type="button" class="btn btn-primary ty-link-existing-btn" data-url="{$linkExistingUrl|escape}" data-id="{$id_product}">Ürünle eşleştir</button></div>
		</div>
	</div>
	{/if}
	<span class="small d-block mt-3 ty-action-msg text-muted"></span>
</div>
{/if}
