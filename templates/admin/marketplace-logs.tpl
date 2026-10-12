{include file='admin/marketplace/_nav.tpl'}

<div class="admin-panel p-3 mb-3">
	<div class="mp-filter-card mb-3">
		<div class="mp-filter-header">
			<div>
				<div class="mp-filter-title">
					{include file='admin/partials/filter-funnel-icon.tpl'}
					Filtreler
				</div>
				<div class="mp-filter-sub">Yeni sipariş, stok ve fiyat olaylarının kronolojik kaydı.</div>
			</div>
		</div>
		<form method="get" action="{$logsUrl|escape}" id="mpLogsFilterForm">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label for="mp_log_platform">Pazaryeri</label>
					<select id="mp_log_platform" name="platform" class="form-select form-select-sm">
						<option value="all"{if $marketplaceLogPlatform == 'all'} selected{/if}>Tümü</option>
						<option value="trendyol"{if $marketplaceLogPlatform == 'trendyol'} selected{/if}>Trendyol</option>
						<option value="hepsiburada"{if $marketplaceLogPlatform == 'hepsiburada'} selected{/if}>Hepsiburada</option>
						<option value="n11"{if $marketplaceLogPlatform == 'n11'} selected{/if}>N11</option>
						<option value="prestashop"{if $marketplaceLogPlatform == 'prestashop'} selected{/if}>PrestaShop</option>
						<option value="frisay"{if $marketplaceLogPlatform == 'frisay'} selected{/if}>FriSay</option>
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label for="mp_log_event">Olay</label>
					<select id="mp_log_event" name="event_type" class="form-select form-select-sm">
						<option value="all"{if $marketplaceLogEventType == 'all'} selected{/if}>Tümü</option>
						{foreach $marketplaceLogTypes as $typeKey => $typeLabel}
						<option value="{$typeKey|escape}"{if $marketplaceLogEventType == $typeKey} selected{/if}>{$typeLabel|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label for="mp_log_start">Başlangıç tarihi</label>
					<input type="date" id="mp_log_start" name="start_date" class="form-control form-control-sm" value="{$marketplaceLogStartDate|escape}">
				</div>
				<div class="col-md-4 mb-3">
					<label for="mp_log_end">Bitiş tarihi</label>
					<input type="date" id="mp_log_end" name="end_date" class="form-control form-control-sm" value="{$marketplaceLogEndDate|escape}">
				</div>
				<div class="col-md-4 mb-3">
					<label for="mp_log_q">Ara</label>
					<input type="text" id="mp_log_q" name="q" class="form-control form-control-sm" placeholder="Sipariş / stok kodu" value="{$marketplaceLogQuery|escape}">
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="mpLogsFilterForm" class="btn btn-warning">Ara</button>
				<a href="{$logsUrl|escape}" class="btn btn-outline-secondary">Temizle</a>
			</div>
		</div>
	</div>

	{if !$marketplaceLogs|@count}
	<p class="text-muted mb-0">Henüz log kaydı yok.</p>
	{else}
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle mb-0">
			<thead>
				<tr>
					<th style="width:140px">Tarih</th>
					<th style="width:200px">Olay</th>
					<th style="width:140px">Referans</th>
					<th>Açıklama</th>
					<th style="width:110px">Platform</th>
				</tr>
			</thead>
			<tbody>
				{foreach $marketplaceLogs as $log}
				<tr>
					<td class="text-nowrap small">{$log.date_formatted|escape}</td>
					<td class="small fw-semibold">{$log.event_label|escape}</td>
					<td class="small"><code>{$log.reference|escape}</code></td>
					<td class="small">{$log.message|escape}</td>
					<td class="small text-muted">{$log.platform_label|escape}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>

	{if isset($pagination) && $pagination.total_pages > 1}
		{include file='admin/plugin/pagination.tpl'}
	{/if}
	{/if}
</div>
