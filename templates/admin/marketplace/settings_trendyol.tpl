<div class="admin-panel p-3" style="max-width: 720px;">
	<h2 class="h6 mb-3">Trendyol API</h2>
	{if !$tyConfigured}
	<div class="alert alert-warning py-2">Merchant ID, API Key ve Secret zorunludur.</div>
	{/if}
	<form method="post">
		<input type="hidden" name="saveTrendyol" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="mb-3">
			<label class="form-label">Merchant ID</label>
			<input type="text" name="merchant_id" class="form-control" value="{$tyMerchantId|escape}" autocomplete="off" required>
		</div>
		<div class="mb-3">
			<label class="form-label">API Key</label>
			<input type="password" name="api_key" class="form-control" value="{$tyApiKey|escape}" autocomplete="off" required>
		</div>
		<div class="mb-3">
			<label class="form-label">API Secret</label>
			<input type="password" name="api_secret" class="form-control" value="{$tyApiSecret|escape}" autocomplete="off" required>
		</div>
		<button type="submit" class="btn btn-dark mt-1">Kaydet</button>
	</form>
	<p class="text-muted small mt-3 mb-0">Marka, kategori ve fiyat bilgileri ürün panelinden (Pazaryeri → Ürünler) girilir.</p>
</div>
