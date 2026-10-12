<div class="admin-panel bulk-pricing-admin">
	<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
		<div>
			<h1 class="h3 mb-1">Toplu Fiyat Güncelleme</h1>
			<p class="text-muted mb-0">Fiyat aralığı, kategori veya marka seçip yüzde/tutar ya da formülle satış fiyatını güncelleyin. Excel ile de aktarabilirsiniz.</p>
		</div>
	</div>

	{if $flash}
	<div class="alert alert-{$flashType|default:'info'|escape}">{$flash|escape}</div>
	{/if}

	<div class="admin-panel p-4 mb-4 bulk-pricing-excel">
		<div class="mb-3">
			<h2 class="h6 mb-1">Excel ile güncelle</h2>
			<p class="small text-muted mb-0">Önce şablonu indirin, fiyatları düzenleyin, sonra .xlsx olarak yükleyin. Eşleşme: <strong>ID</strong>, yoksa <strong>Stok Kodu</strong>, yoksa <strong>Barkod</strong>.</p>
		</div>

		<form method="post" enctype="multipart/form-data" id="bulkPricingExcelImportForm" class="row g-2 align-items-end">
			<input type="hidden" name="token" value="{$adminToken|escape}">
			<div class="col-md-7">
				<label class="form-label" for="bulkPricingExcelFile">Excel dosyası (.xlsx)</label>
				<input type="file" class="form-control" id="bulkPricingExcelFile" name="bulk_pricing_excel" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
			</div>
			<div class="col-md-5">
				<button type="submit" class="btn btn-primary w-100 js-bulk-pricing-excel-import js-admin-busy" name="bulkPricingExcelImport" value="1">Excel ile güncelle</button>
			</div>
		</form>

		<ul class="small text-muted mb-0 mt-3 ps-3">
			<li>Sütunlar: <code>ID</code>, <code>Stok Kodu</code>, <code>Barkod</code>, <code>Ürün Adı</code>, <code>Satış Fiyatı</code>, <code>Alış Fiyatı</code>, <code>Eski Fiyat</code></li>
			<li>Boş bırakılan fiyat hücreleri değişmez; dolu olanlar yazılır.</li>
			<li>Ürün adı / kategori / marka sütunları bilgilendirme içindir, import’ta kullanılmaz.</li>
			<li>Şablon indirmek için aşağıdaki filtreleri ayarlayıp <strong>Excel şablonunu indir</strong>e tıklayın.</li>
		</ul>
	</div>

	<form method="post" id="bulkPricingForm" class="row g-4">
		<input type="hidden" name="token" value="{$adminToken|escape}">

		<div class="col-lg-4">
			<div class="admin-panel p-4 h-100">
				<h2 class="h6 mb-3">Filtreler</h2>

				<div class="mb-3">
					<label class="form-label" for="bpQuery">Arama</label>
					<input type="text" class="form-control" id="bpQuery" name="q" value="{$filters.query|escape}" placeholder="Ürün adı, stok kodu, barkod">
				</div>

				<div class="mb-3">
					<label class="form-label" for="bpCategory">Kategori</label>
					<select class="form-select" id="bpCategory" name="id_category">
						<option value="0"{if $filters.id_category == 0} selected{/if}>Tüm kategoriler</option>
						{foreach $categoryOptions as $cat}
						<option value="{$cat.id_category|escape}"{if $filters.id_category == $cat.id_category} selected{/if}>{$cat.category_name|escape}</option>
						{/foreach}
					</select>
				</div>

				<div class="mb-3">
					<label class="form-label" for="bpBrand">Marka</label>
					<select class="form-select" id="bpBrand" name="id_brand">
						<option value="0"{if $filters.id_brand == 0} selected{/if}>Tüm markalar</option>
						{foreach $brandOptions as $brand}
						<option value="{$brand.id_brand|escape}"{if $filters.id_brand == $brand.id_brand} selected{/if}>{$brand.brand_name|escape}</option>
						{/foreach}
					</select>
				</div>

				<div class="mb-3">
					<label class="form-label">Satış fiyatı aralığı</label>
					<div class="row g-2">
						<div class="col-6">
							<input type="text" class="form-control" id="bpPriceMin" name="price_min" value="{if isset($filters.price_min) && $filters.price_min !== null}{$filters.price_min|escape}{/if}" inputmode="decimal" placeholder="Min">
						</div>
						<div class="col-6">
							<input type="text" class="form-control" id="bpPriceMax" name="price_max" value="{if isset($filters.price_max) && $filters.price_max !== null}{$filters.price_max|escape}{/if}" inputmode="decimal" placeholder="Max">
						</div>
					</div>
					<div class="form-text">Boş bırakılan uç sınırlanmaz. Aralık satış fiyatına bakılır.</div>
				</div>

				<div class="mb-0">
					<label class="form-label" for="bpActive">Durum</label>
					<select class="form-select" id="bpActive" name="active_filter">
						<option value="-1"{if $filters.active == -1} selected{/if}>Tümü</option>
						<option value="1"{if $filters.active == 1} selected{/if}>Yalnızca aktif</option>
						<option value="0"{if $filters.active == 0} selected{/if}>Yalnızca pasif</option>
					</select>
				</div>

				<button type="submit" class="btn btn-outline-dark w-100 mt-3" name="bulkPricingExcelExport" value="1">Excel şablonunu indir</button>
				<div class="form-text">Bu filtrelere uyan ürünlerin fiyat listesini indirir.</div>
			</div>
		</div>

		<div class="col-lg-8">
			<div class="admin-panel p-4 mb-4">
				<h2 class="h6 mb-3">Fiyat değişikliği</h2>

				<div class="mb-3 js-bp-fields"{if $adjustment.mode == 'formula'} hidden{/if}>
					<label class="form-label d-block">Güncellenecek alanlar</label>
					<div class="d-flex flex-wrap gap-3">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="field_price" id="fieldPrice" value="1"{if in_array('price', $adjustment.fields)} checked{/if}>
							<label class="form-check-label" for="fieldPrice">Satış fiyatı</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="field_cost" id="fieldCost" value="1"{if in_array('cost', $adjustment.fields)} checked{/if}>
							<label class="form-check-label" for="fieldCost">Alış fiyatı</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="field_old_price" id="fieldOldPrice" value="1"{if in_array('old_price', $adjustment.fields)} checked{/if}>
							<label class="form-check-label" for="fieldOldPrice">Eski fiyat</label>
						</div>
					</div>
				</div>

				<div class="row g-3">
					<div class="col-md-4">
						<label class="form-label" for="adjustMode">İşlem türü</label>
						<select class="form-select" id="adjustMode" name="adjust_mode">
							<option value="percent"{if $adjustment.mode == 'percent'} selected{/if}>Yüzde (%)</option>
							<option value="fixed"{if $adjustment.mode == 'fixed'} selected{/if}>Sabit tutar ({$shopCurrency|upper|escape})</option>
							<option value="formula"{if $adjustment.mode == 'formula'} selected{/if}>Formül</option>
						</select>
					</div>
					<div class="col-md-4 js-bp-simple"{if $adjustment.mode == 'formula'} hidden{/if}>
						<label class="form-label" for="adjustDirection">Yön</label>
						<select class="form-select" id="adjustDirection" name="adjust_direction">
							<option value="increase"{if $adjustment.direction == 'increase'} selected{/if}>Zam / artır</option>
							<option value="decrease"{if $adjustment.direction == 'decrease'} selected{/if}>İndirim / azalt</option>
						</select>
					</div>
					<div class="col-md-4 js-bp-simple"{if $adjustment.mode == 'formula'} hidden{/if}>
						<label class="form-label" for="adjustValue">Değer</label>
						<input type="text" class="form-control" id="adjustValue" name="adjust_value" value="{$adjustment.value|string_format:'%.2f'|escape}" inputmode="decimal" placeholder="10">
						<div class="form-text" id="adjustValueHint">Örn. satış fiyatına %10 zam için 10 yazın.</div>
					</div>
				</div>

				<div class="js-bp-formula mt-3"{if $adjustment.mode != 'formula'} hidden{/if}>
					<label class="form-label" for="bpFormula">Formül</label>
					<input type="text" class="form-control font-monospace" id="bpFormula" name="formula" value="{$adjustment.formula|escape}" placeholder="(a*k)+75" autocomplete="off" spellcheck="false">
					<div class="form-text mb-2">Sonuç her zaman <strong>satış fiyatına</strong> yazılır. <code>a</code> = alış, <code>s</code> = satış, <code>k</code> = kar çarpanı. <code>a</code> varsa alış 0 olan ürünler atlanır.</div>
					<div class="d-flex flex-wrap gap-2 mb-3">
						<button type="button" class="btn btn-sm btn-outline-secondary js-bp-formula-ex" data-formula="(a*k)+75">(a*k)+75</button>
						<button type="button" class="btn btn-sm btn-outline-secondary js-bp-formula-ex" data-formula="(a+75)/0.95">(a+75)/0.95</button>
						<button type="button" class="btn btn-sm btn-outline-secondary js-bp-formula-ex" data-formula="(s+75)*1.5">(s+75)*1.5</button>
						<button type="button" class="btn btn-sm btn-outline-secondary js-bp-formula-ex" data-formula="a*k">a*k</button>
					</div>

					<div class="bp-rate-box">
						<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
							<label class="form-label mb-0">Kar oranı dilimleri</label>
							<select class="form-select form-select-sm bp-rate-base" id="bpRateBase" name="rate_base">
								<option value="cost"{if ($adjustment.margin_base|default:'cost') != 'price'} selected{/if}>Dilimi alış fiyatına göre seç</option>
								<option value="price"{if ($adjustment.margin_base|default:'cost') == 'price'} selected{/if}>Dilimi satış fiyatına göre seç</option>
							</select>
						</div>
						<p class="small text-muted mb-2"><code>k</code> yüzdelik çarpandır: <strong>%12 → k = 1.12</strong> (alış × 1.12). Boş max = üst sınır yok.</p>
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-2 bp-rate-table">
								<thead>
									<tr>
										<th>Min</th>
										<th>Max</th>
										<th>Kar %</th>
										<th></th>
									</tr>
								</thead>
								<tbody id="bpRateBody">
									{if $adjustment.margins|@count > 0}
									{foreach $adjustment.margins as $tier}
									<tr>
										<td><input type="text" class="form-control form-control-sm" name="rate_min[]" value="{$tier.min|escape}" inputmode="decimal" placeholder="0"></td>
										<td><input type="text" class="form-control form-control-sm" name="rate_max[]" value="{if $tier.max !== null}{$tier.max|escape}{/if}" inputmode="decimal" placeholder="∞"></td>
										<td><input type="text" class="form-control form-control-sm" name="rate_percent[]" value="{$tier.percent|escape}" inputmode="decimal" placeholder="12"></td>
										<td><button type="button" class="btn btn-sm btn-outline-secondary js-bp-rate-del" aria-label="Satırı sil">&times;</button></td>
									</tr>
									{/foreach}
									{else}
									<tr>
										<td><input type="text" class="form-control form-control-sm" name="rate_min[]" value="0" inputmode="decimal" placeholder="0"></td>
										<td><input type="text" class="form-control form-control-sm" name="rate_max[]" value="100" inputmode="decimal" placeholder="∞"></td>
										<td><input type="text" class="form-control form-control-sm" name="rate_percent[]" value="12" inputmode="decimal" placeholder="12"></td>
										<td><button type="button" class="btn btn-sm btn-outline-secondary js-bp-rate-del" aria-label="Satırı sil">&times;</button></td>
									</tr>
									{/if}
								</tbody>
							</table>
						</div>
						<button type="button" class="btn btn-sm btn-outline-primary" id="bpRateAdd">Dilimi ekle</button>
					</div>
				</div>

				<div class="d-flex flex-wrap gap-2 mt-4">
					<button type="submit" class="btn btn-outline-primary" name="bulkPricingPreview" value="1">Önizleme</button>
					<button type="submit" class="btn btn-primary js-bulk-pricing-apply js-admin-busy" name="bulkPricingApply" value="1">Uygula</button>
				</div>
			</div>

			{if $previewRows|@count > 0}
			<div class="admin-panel p-4">
				<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
					<h2 class="h6 mb-0">Önizleme</h2>
					{if $matchCount > 0}
					<span class="badge bg-secondary">{$matchCount|escape} ürün eşleşti</span>
					{/if}
				</div>

				{if $adjustmentLabel}
				<p class="small text-muted">Uygulanacak işlem: <strong>{$adjustmentLabel|escape}</strong></p>
				{/if}

				<div class="table-responsive">
					<table class="table table-sm align-middle mb-0">
						<thead>
							<tr>
								<th>Ürün</th>
								<th>Stok kodu</th>
								<th>Satış</th>
								<th>Alış</th>
								{if $showMarginK}
								<th>k</th>
								{/if}
								<th>Eski</th>
							</tr>
						</thead>
						<tbody>
							{foreach $previewRows as $row}
							<tr>
								<td>
									<div class="fw-semibold">{$row.product_name|escape}</div>
									<div class="small text-muted">{$row.category_name|escape} · {$row.brand_name|escape}</div>
								</td>
								<td>{$row.stock_code|escape}</td>
								<td>
									{if $row.changes.price}
									<span class="text-muted text-decoration-line-through">{$row.changes.price.before|string_format:'%.2f'|escape}</span>
									→ <strong>{$row.changes.price.after|string_format:'%.2f'|escape}</strong>
									{else}
									<span class="text-muted">{$row.price|string_format:'%.2f'|escape}</span>
									{/if}
								</td>
								<td>
									{if $row.changes.cost}
									<span class="text-muted text-decoration-line-through">{$row.changes.cost.before|string_format:'%.2f'|escape}</span>
									→ <strong>{$row.changes.cost.after|string_format:'%.2f'|escape}</strong>
									{else}
									<span class="text-muted">{$row.cost|string_format:'%.2f'|escape}</span>
									{/if}
								</td>
								{if $showMarginK}
								<td>
									{if $row.margin_k !== null}
									%{$row.margin_percent|string_format:'%.2f'|escape} → {$row.margin_k|string_format:'%.4f'|escape}
									{else}
									<span class="text-muted">dilim yok</span>
									{/if}
								</td>
								{/if}
								<td>
									{if $row.changes.old_price}
									<span class="text-muted text-decoration-line-through">{$row.changes.old_price.before|string_format:'%.2f'|escape}</span>
									→ <strong>{$row.changes.old_price.after|string_format:'%.2f'|escape}</strong>
									{else}
									<span class="text-muted">{$row.old_price|string_format:'%.2f'|escape}</span>
									{/if}
								</td>
							</tr>
							{/foreach}
						</tbody>
					</table>
				</div>

				{if $matchCount > $previewRows|@count}
				<p class="small text-muted mt-3 mb-0">İlk {$previewRows|@count} ürün gösteriliyor. Uygula dediğinizde eşleşen tüm ürünler güncellenir.</p>
				{/if}
			</div>
			{/if}
		</div>
	</form>
</div>
