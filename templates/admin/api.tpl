{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">
					{$flash|escape}
				</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

{if $newApiKey}
<div class="alert alert-success">
	<strong>{'New API key (shown once — copy it):'|adminT}</strong><br>
	<code class="user-select-all">{$newApiKey|escape}</code>
	{if $newWsKey|default:'' != ''}
	<br><span class="d-block mt-2">PrestaShop / Prapazar (32 karakter):</span>
	<code class="user-select-all">{$newWsKey|escape}</code>
	{/if}
</div>
{/if}

<div class="row g-4">
	<div class="col-lg-5">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{'API status'|adminT}</h2>
			<p class="small text-muted mb-2">FriSay JSON API: <code>{$webApiUrl|escape}</code></p>
			<p class="small text-muted mb-2">PrestaShop uyumlu (kargo entegrasyonu): <code>{$psWsUrl|escape}</code></p>
			<form method="post">
				<input type="hidden" name="saveApiEnabled" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="form-check form-switch mb-2">
					<input class="form-check-input" type="checkbox" name="WEBAPI_ENABLED" id="apiEnabled" value="1"{if $apiEnabled} checked{/if}>
					<label class="form-check-label" for="apiEnabled">{'Web API enabled'|adminT}</label>
				</div>
				<button type="submit" class="btn btn-outline-secondary btn-sm">{'Save'|adminT}</button>
			</form>
			<ul class="small text-muted mt-3 mb-0 ps-3">
				<li>Header: <code>X-API-Key: ...</code></li>
				<li>{'or'|adminT} <code>Authorization: Bearer ...</code></li>
				<li>PrestaShop / Prapazar: 32 karakterlik anahtar + HTTP Basic (Admin listesindeki Prapazar satırı)</li>
				<li><a href="{$apiDocsPortalUrl|escape}" target="_blank" rel="noopener">{'FriSay API documentation'|adminT}</a></li>
			</ul>
		</div>

		<div class="admin-panel p-3 mt-4">
			<h2 class="h6 mb-3">{if $editKey}{'Edit API'|adminT}{else}{'Create new API'|adminT}{/if}</h2>
			<form method="post">
				{if $editKey}
				<input type="hidden" name="updateApiKey" value="1">
				<input type="hidden" name="id_api_key" value="{$editKey.id_api_key}">
				{else}
				<input type="hidden" name="createApiKey" value="1">
				{/if}
				<input type="hidden" name="token" value="{$adminToken}">

				<div class="mb-3">
					<label class="form-label">{'Partner / name'|adminT}</label>
					<input type="text" name="name" class="form-control" required maxlength="128"
						value="{$editKey.name|default:''|escape}"
						placeholder="{'e.g. Paroner, BizimHesap'|adminT}">
				</div>

				<label class="form-label">{'Permissions'|adminT}</label>
				<div class="border rounded p-2 mb-3" style="max-height:280px;overflow:auto">
					{foreach $permissionCatalog as $permKey => $permLabel}
					<div class="form-check">
						<input class="form-check-input" type="checkbox" name="permissions[]" value="{$permKey|escape}"
							id="perm_{$permKey|replace:'.':'_'|escape}"
							{if $editKey}
								{if in_array($permKey, $editKey.permissions)} checked{/if}
							{else}
								checked
							{/if}>
						<label class="form-check-label" for="perm_{$permKey|replace:'.':'_'|escape}">{$permLabel|escape}</label>
					</div>
					{/foreach}
				</div>

				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" name="active" id="keyActive" value="1"
						{if !$editKey || $editKey.active}checked{/if}>
					<label class="form-check-label" for="keyActive">{'Active'|adminT}</label>
				</div>

				<div class="d-flex flex-wrap gap-2">
					<button type="submit" class="btn btn-dark">{if $editKey}{'Update'|adminT}{else}{'Create'|adminT}{/if}</button>
					{if $editKey}
					<a href="{$adminUrl}api" class="btn btn-outline-secondary">{'Cancel'|adminT}</a>
					{/if}
				</div>
			</form>
		</div>
	</div>

	<div class="col-lg-7">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{'Defined API keys'|adminT}</h2>
			{if !$apiKeys}
			<p class="text-muted small mb-0">{'No keys yet. Add one using Create new API on the left.'|adminT}</p>
			{else}
			<div class="vstack gap-3">
				{foreach $apiKeys as $key}
				<div class="border rounded p-3">
					<div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
						<div>
							<strong>{$key.name|escape}</strong>
							{if $key.active}
							<span class="badge text-bg-success">{'Active'|adminT}</span>
							{else}
							<span class="badge text-bg-secondary">{'Inactive'|adminT}</span>
							{/if}
						</div>
						<div class="d-flex flex-wrap gap-1">
							<a href="{$adminUrl}api?edit={$key.id_api_key}" class="btn btn-sm btn-outline-primary">{'Edit'|adminT}</a>
							<form method="post" class="d-inline" data-confirm-title="{'Regenerate key'|adminT}" data-confirm-message="{'The key will be regenerated. The old key will become invalid.'|adminT}">
								<input type="hidden" name="regenApiKey" value="1">
								<input type="hidden" name="token" value="{$adminToken}">
								<input type="hidden" name="id_api_key" value="{$key.id_api_key}">
								<button type="submit" class="btn btn-sm btn-outline-warning">{'Regenerate key'|adminT}</button>
							</form>
							<form method="post" class="d-inline" data-confirm-title="{'Delete'|adminT}" data-confirm-message="{'Delete this API key?'|adminT}">
								<input type="hidden" name="deleteApiKey" value="1">
								<input type="hidden" name="token" value="{$adminToken}">
								<input type="hidden" name="id_api_key" value="{$key.id_api_key}">
								<button type="submit" class="btn btn-sm btn-outline-danger">{'Delete'|adminT}</button>
							</form>
						</div>
					</div>
					<div class="small mb-1">
						<code class="user-select-all">{$key.api_key|escape}</code>
					</div>
					{if $key.ws_key|default:'' != ''}
					<div class="small mb-1">
						<span class="text-muted">PrestaShop / Prapazar (32 karakter):</span>
						<code class="user-select-all">{$key.ws_key|escape}</code>
					</div>
					{/if}
					<div class="small text-muted mb-1">
						{foreach $key.permission_labels as $label}
						<span class="badge text-bg-light border me-1 mb-1">{$label|escape}</span>
						{/foreach}
					</div>
					<div class="small text-muted">
						{'Create'|adminT}ma: {$key.date_add|escape}
						{if $key.last_used_at}{' · Last used:'|adminT} {$key.last_used_at|escape}{/if}
					</div>
				</div>
				{/foreach}
			</div>
			{/if}
		</div>

		<div class="admin-panel p-3 mt-4">
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<h2 class="h6 mb-0">{'Documentation'|adminT}</h2>
				<a href="{$apiDocsPortalUrl|escape}" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">{'All documentation'|adminT}</a>
			</div>
			<p class="small text-muted mb-3">{'Sample PHP code and endpoint descriptions for partner integration.'|adminT}</p>

			{foreach $apiDocs as $section}
			<div class="mb-3">
				<div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.04em">{$section.group|escape}</div>
				<div class="list-group list-group-flush border rounded">
					{foreach $section.items as $doc}
					<a href="{$doc.url|escape}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-2 py-2"
						target="_blank" rel="noopener">
						<span>
							<span class="d-block fw-semibold">{$doc.title|escape}</span>
							<span class="small text-muted">{$doc.desc|escape}</span>
						</span>
						<span class="text-muted small flex-shrink-0">↗</span>
					</a>
					{/foreach}
				</div>
			</div>
			{/foreach}
		</div>

		<div class="admin-panel p-3 mt-4">
			<h2 class="h6 mb-2">{'Examples'|adminT}</h2>
			<p class="small text-muted mb-1"><strong>Paroner</strong>{' — add/edit/delete products + orders'|adminT}</p>
			<p class="small text-muted mb-0"><strong>BizimHesap</strong>{' — read/pull orders only'|adminT}</p>
		</div>
	</div>
</div>

<div class="admin-panel p-3 mt-4">
	<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
		<h2 class="h6 mb-0">Prapazar / PrestaShop hata kaydı</h2>
		<form method="post">
			<input type="hidden" name="clearPsWsLog" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-sm btn-outline-danger">Kaydı temizle</button>
		</form>
	</div>
	<p class="small text-muted mb-3">Yalnızca hatalı istekler (HTTP 4xx / 5xx) kaydedilir: method, URL, gövde, yanıt. Başarılı senkron çağrıları loglanmaz. Anahtarlar maskelenir.</p>
	{if $psWsLogs|@count == 0}
	<p class="small text-muted mb-0">Henüz hata kaydı yok.</p>
	{else}
	<div class="vstack gap-3">
		{foreach $psWsLogs as $log}
		<div class="border rounded p-3">
			<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
				<code class="small">{$log.method|default:'?'|escape}</code>
				{assign var=st value=$log.status|default:0}
				{if $st >= 200 && $st < 300}
				<span class="badge text-bg-success">{$st|escape}</span>
				{elseif $st == 401 || $st == 403}
				<span class="badge text-bg-warning">{$st|escape}</span>
				{elseif $st >= 400}
				<span class="badge text-bg-danger">{$st|escape}</span>
				{else}
				<span class="badge text-bg-secondary">{$st|escape}</span>
				{/if}
				<span class="small text-muted">{$log.at|default:''|escape}</span>
				{if $log.auth|default:'' != ''}
				<span class="badge text-bg-light border">auth: {$log.auth|escape}</span>
				{/if}
			</div>
			<div class="small mb-1"><code>{$log.uri|default:''|escape}</code></div>
			{if $log.resource|default:'' != ''}
			<div class="small text-muted mb-1">kaynak: <code>{$log.resource|escape}</code>{if $log.id|default:0 > 0} #{$log.id|escape}{/if}{if $log.sub|default:'' != ''} / {$log.sub|escape}{/if}</div>
			{/if}
			{if $log.error|default:'' != ''}
			<div class="small text-danger mb-1">{$log.error|escape}</div>
			{/if}
			{if $log.query_json|default:'' != '' && $log.query_json != '[]'}
			<div class="small text-muted mb-1">query: <code>{$log.query_json|escape}</code></div>
			{/if}
			{if $log.ua|default:'' != ''}
			<div class="small text-muted mb-1">UA: {$log.ua|escape}</div>
			{/if}
			{if $log.format|default:'' != ''}
			<div class="small text-muted mb-1">yanıt: <code>{$log.format|escape}</code></div>
			{/if}
			{if $log.body|default:'' != ''}
			<pre class="small bg-light border rounded p-2 mb-2" style="max-height:240px;overflow:auto;white-space:pre-wrap">{$log.body|escape}</pre>
			{else}
			<p class="small text-muted mb-2">İstek gövdesi yok (GET).</p>
			{/if}
			{if $log.response|default:'' != ''}
			<div class="small text-muted mb-1">Bizim yanıt (kısaltılmış):</div>
			<pre class="small bg-light border rounded p-2 mb-0" style="max-height:240px;overflow:auto;white-space:pre-wrap">{$log.response|escape}</pre>
			{/if}
		</div>
		{/foreach}
	</div>
	{/if}
</div>
