{if $flash}
<div class="alert alert-{$flashType|default:'info'} alert-dismissible fade show" role="alert">
	{$flash|escape}
	<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

<div class="row g-4">

	<div class="col-12">
		<div class="card border-0 shadow-sm ak-hero">
			<div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
				<div>
					<div class="ak-badge mb-2">Akakçe Entegrasyonu</div>
					<h2 class="h5 mb-1">Ürünlerinizi Akakçe karşılaştırmasına açın</h2>
					<p class="text-muted small mb-0">Akakçe XML formatında ürün feed’i üretir. Kargo ücreti mağaza kargo dilimlerinden otomatik hesaplanır; son kargo saati aşağıdan ayarlanır.</p>
				</div>
				<a href="{$panelUrl|escape}" target="_blank" rel="noopener noreferrer" class="btn btn-ak">
					Akakçe
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
				</a>
			</div>
		</div>
	</div>

	<div class="col-12">
		<div class="card border-0 shadow-sm">
			<div class="card-header bg-white d-flex align-items-center gap-2">
				<span class="badge text-bg-warning text-dark">XML FEED URL</span>
				<span class="fw-semibold">Bu linki Akakçe mağaza başvurusuna ekleyin</span>
			</div>
			<div class="card-body">
				<div class="input-group">
					<input type="text" class="form-control font-monospace" id="akFeedUrlInput" value="{$feedUrl|escape}" readonly>
					<button class="btn btn-outline-secondary" type="button" id="akCopyFeedUrl">Kopyala</button>
					<a href="{$feedUrl|escape}" target="_blank" class="btn btn-outline-primary">Önizle</a>
				</div>
				<div class="d-flex gap-4 mt-2 text-muted small flex-wrap">
					<span>Son yenileme: <strong id="akLastRegen">{$lastRegen|escape}</strong></span>
					<span>Önbellek: <strong>{if !$cacheExists}Yok{elseif $cacheExpired}Süresi doldu ({$cacheAgeMin} dk önce){else}Geçerli — {$cacheRemainingMin} dk kaldı{/if}</strong></span>
					<span>Örnek kargo (100 ₺ sepet): <strong>{$akSampleShip|escape} ₺</strong></span>
				</div>
				<p class="small text-muted mb-0 mt-2">XML, süre dolunca kendiliğinden üretilmez. Feed URL’si istenince veya aşağıdaki cron çalışınca yenilenir. Akakçe tarafında eski HTTP önbelleği kalmış olabilir.</p>
				<p class="small font-monospace text-muted mb-0">{$cronFeedsUrl|escape}</p>
				<div class="mt-3 d-flex flex-wrap gap-2">
					<button class="btn btn-dark btn-sm" id="akBtnRegen" type="button">
						<span id="akRegenSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
						Feed'i Yenile
					</button>
					<button class="btn btn-outline-secondary btn-sm" id="akBtnPreview" type="button">Önizleme (İlk 5 Ürün)</button>
				</div>
			</div>
		</div>
	</div>

	<div class="col-12 d-none" id="akPreviewSection">
		<div class="card border-0 shadow-sm">
			<div class="card-header bg-white">
				<span class="fw-semibold">Feed Önizleme</span>
				<span class="badge bg-secondary ms-2" id="akPreviewTotal"></span>
			</div>
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="table table-sm table-hover mb-0" id="akPreviewTable">
						<thead class="table-light">
							<tr>
								<th>SKU</th>
								<th>Ad</th>
								<th>Fiyat</th>
								<th>Kargo</th>
								<th>Stok</th>
								<th>Son saat</th>
								<th>Barkod</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-5">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-header bg-white fw-semibold">Kurulum</div>
			<div class="card-body">
				<ol class="mb-0 small text-muted ak-steps">
					<li>Aşağıdaki kargo ve son kargo saati ayarlarını kaydedin.</li>
					<li>Yukarıdaki <strong>XML Feed URL</strong>’yi kopyalayın.</li>
					<li>Akakçe satıcı başvurusunda XML adresini bildirin.</li>
					<li><code>shipPrice</code> aktif kargo firmasının fiyat diliminden (ücretsiz kargo eşiği dahil) ürün fiyatına göre hesaplanır.</li>
					<li><code>expressDeliveryTime</code> siparişin en geç kaçta kargoya verileceğini gösterir (ör. 16 → 16:00).</li>
				</ol>
			</div>
		</div>
	</div>

	<div class="col-lg-7">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-header bg-white fw-semibold">Feed Ayarları</div>
			<div class="card-body">
				<form method="post">
					<input type="hidden" name="saveAkakceSettings" value="1">
					<input type="hidden" name="token" value="{$adminToken}">

					<div class="row g-3">
						<div class="col-md-6">
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" name="akakce_enabled" id="akEnabled" value="1"{if $akEnabled} checked{/if}>
								<label class="form-check-label fw-semibold" for="akEnabled">Feed aktif</label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" name="akakce_include_outstock" id="akIncludeOutstock" value="1"{if $akIncludeOutstock} checked{/if}>
								<label class="form-check-label" for="akIncludeOutstock">Stok dışı ürünleri dahil et</label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" name="akakce_include_variations" id="akIncludeVariations" value="1"{if $akIncludeVariations} checked{/if}>
								<label class="form-check-label" for="akIncludeVariations">Varyasyonları ayrı ürün olarak gönder</label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" name="akakce_use_product_cargo_day" id="akUseProductCargoDay" value="1"{if $akUseProductCargoDay} checked{/if}>
								<label class="form-check-label" for="akUseProductCargoDay">Teslim gününü üründeki kargo gününden al</label>
							</div>
						</div>
						<div class="col-md-4">
							<label class="form-label" for="akExpressHour">Son kargo saati</label>
							<input type="number" name="akakce_express_hour" id="akExpressHour" class="form-control" value="{$akExpressHour|escape}" min="0" max="23">
							<div class="form-text">expressDeliveryTime · 16 = 16:00</div>
						</div>
						<div class="col-md-4">
							<label class="form-label" for="akDayOfDelivery">Kargoya verilme günü</label>
							<input type="number" name="akakce_day_of_delivery" id="akDayOfDelivery" class="form-control" value="{$akDayOfDelivery|escape}" min="0" max="30">
							<div class="form-text">dayOfDelivery · 0 = bugün</div>
						</div>
						<div class="col-md-4">
							<label class="form-label" for="akCacheTtl">Önbellek (dakika)</label>
							<input type="number" name="akakce_cache_ttl" id="akCacheTtl" class="form-control" value="{$akCacheTtl|escape}" min="10" max="1440">
						</div>
						<div class="col-md-6">
							<label class="form-label" for="akCargoId">Kargo firması (shipPrice)</label>
							<select name="akakce_cargo_id" id="akCargoId" class="form-select">
								<option value="0"{if $akCargoId == 0} selected{/if}>Varsayılan kargo</option>
								{foreach $akCargos as $cargo}
								<option value="{$cargo.id_cargo}"{if $akCargoId == $cargo.id_cargo} selected{/if}>{$cargo.name|escape}</option>
								{/foreach}
							</select>
							<div class="form-text">Ücret, seçilen firmanın sepet tutarı diliminden gelir.</div>
						</div>
						<div class="col-md-6">
							<label class="form-label" for="akSkuField">SKU alanı</label>
							<select name="akakce_sku_field" id="akSkuField" class="form-select">
								<option value="stock_code"{if $akSkuField == 'stock_code'} selected{/if}>Stok kodu (yoksa ürün ID)</option>
								<option value="id"{if $akSkuField == 'id'} selected{/if}>Ürün ID</option>
							</select>
						</div>
						<div class="col-md-6">
							<label class="form-label" for="akBrandFallback">Varsayılan marka</label>
							<input type="text" name="akakce_brand_fallback" id="akBrandFallback" class="form-control" value="{$akBrandFallback|escape}" placeholder="Markası olmayan ürünler">
						</div>
						<div class="col-md-6">
							<label class="form-label" for="akExcludeCats">Hariç kategori ID</label>
							<input type="text" name="akakce_exclude_cats" id="akExcludeCats" class="form-control" value="{$akExcludeCats|escape}" placeholder="Örn: 5,12,38">
						</div>
						<div class="col-12">
							<button type="submit" class="btn btn-dark">Ayarları Kaydet</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>

	<div class="col-12">
		<div class="card border-0 shadow-sm border-warning">
			<div class="card-header bg-white fw-semibold text-warning">Token güvenliği</div>
			<div class="card-body">
				<p class="text-muted small mb-3">Feed URL’sindeki token yetkisiz erişimi engeller. Yenilerseniz Akakçe’deki XML linkini de güncelleyin.</p>
				<form method="post" data-confirm-message="Token yenilenirse mevcut feed URL geçersiz olur. Devam edilsin mi?">
					<input type="hidden" name="regenToken" value="1">
					<input type="hidden" name="token" value="{$adminToken}">
					<button type="submit" class="btn btn-outline-warning btn-sm">Token'ı Yenile</button>
				</form>
			</div>
		</div>
	</div>

</div>

<script>
var akApiBase = '{$domain|escape}api/module.php?m=akakce&action=';
var akCsrf = '{$adminToken|escape:"javascript"}';

document.getElementById('akCopyFeedUrl').addEventListener('click', function () {
	var inp = document.getElementById('akFeedUrlInput');
	inp.select();
	document.execCommand('copy');
	this.textContent = 'Kopyalandı!';
	var self = this;
	setTimeout(function () { self.textContent = 'Kopyala'; }, 2000);
});

document.getElementById('akBtnRegen').addEventListener('click', function () {
	var spinner = document.getElementById('akRegenSpinner');
	var btn = this;
	spinner.classList.remove('d-none');
	btn.disabled = true;

	fetch(akApiBase + 'regenerate', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			'X-CSRF-Token': akCsrf
		},
		body: 'token=' + encodeURIComponent(akCsrf)
	})
	.then(function (r) { return r.json(); })
	.then(function (data) {
		spinner.classList.add('d-none');
		btn.disabled = false;
		if (data.success) {
			document.getElementById('akLastRegen').textContent = data.generated_at || '—';
			alert(data.message);
		} else {
			alert(data.message || 'Hata');
		}
	})
	.catch(function () {
		spinner.classList.add('d-none');
		btn.disabled = false;
		alert('Bağlantı hatası');
	});
});

document.getElementById('akBtnPreview').addEventListener('click', function () {
	var btn = this;
	btn.disabled = true;
	btn.textContent = 'Yükleniyor…';

	fetch(akApiBase + 'preview', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			'X-CSRF-Token': akCsrf
		},
		body: 'token=' + encodeURIComponent(akCsrf)
	})
	.then(function (r) { return r.json(); })
	.then(function (data) {
		btn.disabled = false;
		btn.textContent = 'Önizleme (İlk 5 Ürün)';
		if (!data.success) {
			alert(data.message || 'Hata');
			return;
		}

		document.getElementById('akPreviewTotal').textContent = 'Toplam: ' + data.total + ' ürün';
		var tbody = document.querySelector('#akPreviewTable tbody');
		tbody.innerHTML = '';

		data.preview.forEach(function (p) {
			var tr = document.createElement('tr');
			tr.innerHTML =
				'<td class="font-monospace small"></td>' +
				'<td></td>' +
				'<td class="text-nowrap"></td>' +
				'<td class="text-nowrap"></td>' +
				'<td></td>' +
				'<td></td>' +
				'<td class="font-monospace small"></td>';
			tr.cells[0].textContent = p.sku || '';
			tr.cells[1].textContent = (p.name || '').substring(0, 60);
			tr.cells[2].textContent = p.price || '';
			tr.cells[3].textContent = p.shipPrice || '';
			tr.cells[4].textContent = p.quantity || '';
			tr.cells[5].textContent = p.expressDeliveryTime || '';
			tr.cells[6].textContent = p.barcode || '';
			tbody.appendChild(tr);
		});

		document.getElementById('akPreviewSection').classList.remove('d-none');
	})
	.catch(function () {
		btn.disabled = false;
		btn.textContent = 'Önizleme (İlk 5 Ürün)';
		alert('Bağlantı hatası');
	});
});
</script>
