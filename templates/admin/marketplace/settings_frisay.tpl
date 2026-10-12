<div class="admin-panel p-3" style="max-width: 720px;">
	<h2 class="h6 mb-3">FriSay uzak mağaza (Web API)</h2>
	{if !$frisayConfigured}
	<div class="alert alert-warning py-2">Uzak mağaza adresi ve Web API anahtarı zorunludur.</div>
	{/if}
	<form method="post">
		<input type="hidden" name="saveFrisay" value="1">
		<input type="hidden" name="platform" value="frisay">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="mb-3">
			<label class="form-label">Mağaza URL</label>
			<input type="url" name="frisay_url" class="form-control" value="{$frisayUrl|escape}" placeholder="https://uzak-magaza.com veya http://localhost/fshop" autocomplete="off" required>
			<div class="form-text">Kök adres yeter; <code>/api/v1</code> otomatik eklenir.</div>
		</div>
		<div class="mb-3">
			<label class="form-label">Web API Key</label>
			<input type="password" name="frisay_api_key" class="form-control" value="{$frisayApiKey|escape}" autocomplete="off" required>
			<div class="form-text">Uzak mağazanın Admin → API sayfasından üretilen anahtar.</div>
		</div>
		<button type="submit" class="btn btn-dark mt-1">Kaydet</button>
	</form>
	<p class="text-muted small mt-3 mb-3">Siparişler Web API ile çekilir. Ürün eşleştirme uzak ürün ID ile yapılır; fiyat/stok hızlı güncelleme endpoint’ine gönderilir.</p>
	{if $frisayConfigured}
	<div class="d-flex flex-wrap gap-2">
		<form method="post" class="d-inline">
			<input type="hidden" name="syncFrisayOrders" value="1">
			<input type="hidden" name="platform" value="frisay">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-sm btn-outline-dark">Siparişleri çek</button>
		</form>
		<form method="post" class="d-inline">
			<input type="hidden" name="pullFrisayProducts" value="1">
			<input type="hidden" name="platform" value="frisay">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-sm btn-outline-secondary">10 ürün çek</button>
		</form>
		<form method="post" class="d-inline">
			<input type="hidden" name="sendFrisayProducts" value="1">
			<input type="hidden" name="platform" value="frisay">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-sm btn-outline-secondary">10 ürün gönder</button>
		</form>
	</div>
	{/if}
</div>
