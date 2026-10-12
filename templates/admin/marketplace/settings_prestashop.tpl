<div class="admin-panel p-3" style="max-width: 720px;">
	<h2 class="h6 mb-3">PrestaShop WebService</h2>
	{if !$psConfigured}
	<div class="alert alert-warning py-2">Mağaza URL ve WebService API anahtarı zorunludur.</div>
	{/if}
	<form method="post">
		<input type="hidden" name="savePrestashop" value="1">
		<input type="hidden" name="platform" value="prestashop">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="mb-3">
			<label class="form-label">Mağaza URL</label>
			<input type="url" name="ps_url" class="form-control" value="{$psUrl|escape}" placeholder="https://ornek-magaza.com" autocomplete="off" required>
		</div>
		<div class="mb-3">
			<label class="form-label">WebService API Key</label>
			<input type="password" name="ps_api_key" class="form-control" value="{$psApiKey|escape}" autocomplete="off" required>
		</div>
		<button type="submit" class="btn btn-dark mt-1">Kaydet</button>
	</form>
	<p class="text-muted small mt-3 mb-3">Ürün eşleştirme PrestaShop ürün ID ile yapılır. Fiyat/stok ürün panelinden güncellenir.</p>
	{if $psConfigured}
	<div class="d-flex flex-wrap gap-2">
		<form method="post" class="d-inline">
			<input type="hidden" name="syncPrestashopOrders" value="1">
			<input type="hidden" name="platform" value="prestashop">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-sm btn-outline-dark">Siparişleri çek</button>
		</form>
		<form method="post" class="d-inline">
			<input type="hidden" name="pullPrestashopProducts" value="1">
			<input type="hidden" name="platform" value="prestashop">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-sm btn-outline-secondary">10 ürün çek</button>
		</form>
		<form method="post" class="d-inline">
			<input type="hidden" name="sendPrestashopProducts" value="1">
			<input type="hidden" name="platform" value="prestashop">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-sm btn-outline-secondary">10 ürün gönder</button>
		</form>
	</div>
	{/if}
</div>
