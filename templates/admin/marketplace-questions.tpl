{include file='admin/marketplace/_nav.tpl'}

{literal}
<style>
.mp-q-page{background:#f7f8fa;border-radius:16px;padding:16px}
.mp-q-list{display:flex;flex-direction:column;gap:12px}
.mp-q-card{background:#fff;border:1px solid #e8ebf0;border-radius:14px;padding:14px 16px}
.mp-q-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px}
.mp-q-left{display:flex;align-items:flex-start;gap:12px;min-width:0}
.mp-q-icon{width:36px;height:36px;border-radius:10px;object-fit:contain;background:#f8fafc;border:1px solid #eef1f5;padding:4px;flex:0 0 auto}
.mp-q-icon-fallback{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:#fff;text-transform:uppercase;flex:0 0 auto}
.mp-q-icon-fallback.trendyol{background:#f27a1a}
.mp-q-icon-fallback.hepsiburada{background:#ff6000}
.mp-q-icon-fallback.n11{background:#7b2cbf}
.mp-q-product{font-weight:700;color:#0f172a;line-height:1.3}
.mp-q-meta{font-size:.78rem;color:#8b93a7;margin-top:2px}
.mp-q-status{display:inline-flex;align-items:center;border-radius:999px;padding:7px 12px;font-size:.78rem;font-weight:700;white-space:nowrap}
.mp-q-status.pending{background:#dbeafe;color:#1d4ed8}
.mp-q-status.done{background:#bbf7d0;color:#166534}
.mp-q-text{font-size:.92rem;color:#334155;margin:0 0 10px;line-height:1.45}
.mp-q-answer{background:#f0fdf4;border:1px solid #dcfce7;border-radius:10px;padding:10px 12px;font-size:.88rem;color:#166534}
.mp-q-empty{background:#fff;border:1px dashed #e5e7eb;border-radius:12px;padding:28px;text-align:center;color:#94a3b8}
@media (max-width:767px){
	.mp-q-top{flex-direction:column}
}
</style>
{/literal}

<div class="admin-panel p-3 mb-3 mp-q-page">
	<div class="mp-filter-card mb-3">
		<div class="mp-filter-header">
			<div>
				<div class="mp-filter-title">
					{include file='admin/partials/filter-funnel-icon.tpl'}
					Filtreler
				</div>
				<div class="mp-filter-sub">Sorular cron ile gelir; tüm pazaryerleri tek listede görünür.</div>
			</div>
			<a href="{$helpUrl|escape}" class="btn btn-outline-secondary">Cron / Yardım</a>
		</div>
		<form method="get" action="{$questionsUrl|escape}" id="mpQuestionsFilterForm">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label for="mp_q_platform">Pazaryeri</label>
					<select id="mp_q_platform" name="marketplace_platform" class="form-select form-select-sm">
						<option value="all"{if $marketplaceQuestionPlatform == 'all'} selected{/if}>Tümü</option>
						<option value="trendyol"{if $marketplaceQuestionPlatform == 'trendyol'} selected{/if}>Trendyol</option>
						<option value="hepsiburada"{if $marketplaceQuestionPlatform == 'hepsiburada'} selected{/if}>Hepsiburada</option>
						<option value="n11"{if $marketplaceQuestionPlatform == 'n11'} selected{/if}>N11</option>
					</select>
				</div>
				<div class="col-md-4 mb-3">
					<label for="mp_q_status">Durum</label>
					<select id="mp_q_status" name="question_status" class="form-select form-select-sm">
						<option value="all"{if $marketplaceQuestionStatus == 'all'} selected{/if}>Tümü</option>
						<option value="waiting"{if $marketplaceQuestionStatus == 'waiting'} selected{/if}>Cevap bekleniyor</option>
						<option value="answered"{if $marketplaceQuestionStatus == 'answered'} selected{/if}>Cevaplandı</option>
					</select>
				</div>
			</div>
		</form>
		<div class="mp-filter-actions">
			<div class="mp-filter-actions__btns">
				<button type="submit" form="mpQuestionsFilterForm" class="btn btn-warning">Ara</button>
			</div>
		</div>
	</div>

	{if $marketplaceQuestions|@count}
	<div class="mp-q-list">
		{foreach $marketplaceQuestions as $q}
		<div class="mp-q-card">
			<div class="mp-q-top">
				<div class="mp-q-left">
					<img
						src="{$domain}templates/admin/img/icons/{$q.platform_icon_file|escape}"
						alt="{$q.platform_label|escape}"
						class="mp-q-icon"
						onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
					<span class="mp-q-icon-fallback {$q.platform|escape}" style="display:none;">{$q.platform_icon|escape}</span>
					<div>
						<div class="mp-q-product">{$q.product_name|escape}</div>
						<div class="mp-q-meta">
							{$q.platform_label|escape}
							· #{$q.question_id|escape}
							· {$q.date_day|escape}{if $q.date_time} {$q.date_time|escape}{/if}
						</div>
					</div>
				</div>
				<span class="mp-q-status {$q.status_tone|escape}">{$q.status|escape}</span>
			</div>

			<p class="mp-q-text">{$q.question_text|escape}</p>

			{if $q.answered}
				<div class="mp-q-answer"><strong>Cevap:</strong> {$q.answer_text|escape}</div>
			{else}
				<form method="post" action="{$questionsUrl|escape}">
					<input type="hidden" name="{$q.answer_action|escape}" value="1">
					<input type="hidden" name="token" value="{$adminToken}">
					<input type="hidden" name="question_id" value="{$q.question_id|escape}">
					<textarea name="answer_text" class="form-control form-control-sm mb-2" rows="2" placeholder="Cevabınız…" required></textarea>
					<button type="submit" class="btn btn-sm btn-primary">Cevapla</button>
				</form>
			{/if}
		</div>
		{/foreach}
	</div>
	{else}
	<div class="mp-q-empty">Henüz soru yok. Cron çalışınca buraya düşer.</div>
	{/if}

	{if isset($pagination) && $pagination.total_pages > 1}
		{include file='admin/plugin/pagination.tpl'}
	{/if}
</div>

{include file='admin/marketplace/_close.tpl'}
