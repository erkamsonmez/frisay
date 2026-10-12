<div class="admin-panel p-3" style="max-width: 720px;">
	<h2 class="h6 mb-3">FiyatTrend</h2>
	{if !$ftConfigured}
	<div class="alert alert-warning py-2">Toplu fiyat güncelleme için FiyatTrend API token’ı gerekir.</div>
	{/if}
	<p class="text-muted small">Barkod ile fiyat çekmek için FiyatTrend API token’ınızı girin. Token ürün içe aktarma ekranında kullanılır.</p>
	<form method="post">
		<input type="hidden" name="saveFiyattrend" value="1">
		<input type="hidden" name="platform" value="fiyattrend">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="mb-3">
			<label class="form-label">FiyatTrend API Token</label>
			<input type="text" name="fiyattrend_token" class="form-control" value="{$tyFiyattrendToken|escape}" autocomplete="off">
		</div>
		<button type="submit" class="btn btn-dark">Kaydet</button>
	</form>
</div>
