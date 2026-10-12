<div id="quantityTiersAdminPanel" class="quantity-tiers-admin card border-0 shadow-sm mb-3">
	<div class="card-body">
		<input type="hidden" name="quantity_tiers" id="quantityTiersJson" value="{$tiersJson|escape}">
		<h3 class="h6 mb-1">Çok Al Az Öde</h3>
		<p class="small text-muted mb-3">Miktar kademelerine göre birim fiyat. Örn. 1 adet 500 TL, 3+ adet 450 TL, 12+ adet 400 TL.</p>

		{if $is_new}
		<div class="alert alert-info py-2 small mb-3">Önce ürünü kaydedin; kademeler ilk kayıtta da saklanır.</div>
		{/if}

		<div class="table-responsive">
			<table class="table table-sm align-middle mb-2">
				<thead>
					<tr>
						<th style="width:120px">Min. adet</th>
						<th style="width:160px">Birim fiyat</th>
						<th style="width:50px"></th>
					</tr>
				</thead>
				<tbody id="quantityTiersBody"></tbody>
			</table>
		</div>
		<button type="button" class="btn btn-sm btn-outline-dark" id="quantityTierAdd">+ Kademe ekle</button>
	</div>
</div>
<link rel="stylesheet" href="{$domain}modules/quantity-tiers/assets/css/admin.css?v={$smarty.now}">
<script src="{$domain}modules/quantity-tiers/assets/js/admin.js?v={$smarty.now}"></script>
