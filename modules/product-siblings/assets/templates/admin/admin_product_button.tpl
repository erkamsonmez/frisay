<div id="productSiblingsAdminPanel" class="product-siblings-admin card border-0 shadow-sm mb-3" data-product-id="{$id_product|default:0}" data-search-api="{$searchApi|escape}" data-token="{$adminToken|escape}">
	<div class="card-body">
		<input type="hidden" name="sibling_items" id="siblingItemsJson" value="{$siblingItemsJson|escape}">
		<h3 class="h6 mb-1">Seçenek ürünler</h3>
		<p class="small text-muted mb-3">Aynı ürünün farklı boy/adet varyantlarını (ör. 60 kapsül, 90 kapsül) buradan bağlayın. Ürün sayfasında görselli kartlar olarak görünür.</p>

		{if $is_new}
		<div class="alert alert-info py-2 small mb-3">Önce ürünü kaydedin; ardından seçenek ekleyebilirsiniz. İlk kayıtta aşağıdaki liste de kaydedilir.</div>
		{/if}

		<div class="mb-2">
			<label class="form-label small">Ürün ara</label>
			<input type="search" id="siblingProductSearch" class="form-control form-control-sm" placeholder="Ad, stok kodu veya ID…" autocomplete="off">
			<div id="siblingSearchResults" class="product-siblings-admin__results"></div>
		</div>

		<div class="table-responsive">
			<table class="table table-sm align-middle mb-0">
				<thead>
					<tr>
						<th>Ürün</th>
						<th style="width:160px">Etiket</th>
						<th style="width:100px">Fiyat</th>
						<th style="width:50px"></th>
					</tr>
				</thead>
				<tbody id="siblingItemsBody"></tbody>
			</table>
		</div>
		<p class="small text-muted mt-2 mb-0">Bu ürün otomatik olarak gruba dahildir. En az bir ek ürün gerekir.</p>
	</div>
</div>
<link rel="stylesheet" href="{$domain}modules/product-siblings/assets/css/admin.css?v={$smarty.now}">
<script src="{$domain}modules/product-siblings/assets/js/admin.js?v={$smarty.now}"></script>
