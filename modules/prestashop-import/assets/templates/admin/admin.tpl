{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$flash|escape}</div>
			</div>
			<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

{if $psDemoMode}
<div class="alert alert-warning mb-4" role="alert">
	<strong>{'Demo mode'|adminT}</strong>
	{'Demo mode: some edits are not allowed'|adminT}
</div>
{/if}

<div class="row g-4">
	<div class="col-lg-7">
		<form method="post" class="admin-panel p-3 mb-4"{if $psDemoMode} onsubmit="return false;"{/if}>
			<input type="hidden" name="token" value="{$adminToken}">

			<h2 class="h6 mb-3">{'PrestaShop Import'|adminT}</h2>
			<p class="text-muted small mb-3">
				{'Import brands, categories and customers from PrestaShop tables already added to this database. Existing records are left unchanged.'|adminT}
			</p>

			<div class="mb-3">
				<label class="form-label" for="psPrefix">{'Table prefix'|adminT}</label>
				<input type="text" name="prefix" id="psPrefix" class="form-control form-control-sm" value="{$psSettings.prefix|escape}" maxlength="16"{if $psDemoMode} disabled{/if}>
				<p class="form-text mb-0">{'PrestaShop table prefix (e.g. ps_)'|adminT}</p>
			</div>

			<div class="mb-3">
				<label class="form-label" for="psLangId">{'PrestaShop language ID'|adminT}</label>
				<input type="number" min="1" name="lang_id" id="psLangId" class="form-control form-control-sm" value="{$psSettings.lang_id|escape}"{if $psDemoMode} disabled{/if}>
				<p class="form-text mb-0">{'Used for category names when ps_lang cannot be mapped.'|adminT}</p>
			</div>

			<div class="mb-3">
				<label class="form-label" for="psCookieKey">{'Cookie key (_COOKIE_KEY_)'|adminT}</label>
				<input type="text" name="cookie_key" id="psCookieKey" class="form-control form-control-sm" value="{$psSettings.cookie_key|escape}" autocomplete="off"{if $psDemoMode} disabled{/if}>
				<p class="form-text mb-0">{'Needed only for PrestaShop 1.6 password hashes. Leave empty for 1.7+.'|adminT}</p>
			</div>

			<div class="form-check mb-2">
				<input class="form-check-input" type="checkbox" name="skip_root" id="psSkipRoot" value="1"{if $psSettings.skip_root} checked{/if}{if $psDemoMode} disabled{/if}>
				<label class="form-check-label" for="psSkipRoot">{'Skip root / Home category'|adminT}</label>
			</div>

			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="skip_guests" id="psSkipGuests" value="1"{if $psSettings.skip_guests} checked{/if}{if $psDemoMode} disabled{/if}>
				<label class="form-check-label" for="psSkipGuests">{'Skip guest customers'|adminT}</label>
			</div>

			<div class="d-flex flex-wrap gap-2">
				<button type="submit" name="savePsImportSettings" value="1" class="btn btn-outline-dark btn-sm"{if $psDemoMode} disabled{/if}>
					{'Save settings'|adminT}
				</button>
				<button type="submit" name="runPsImport" value="1" class="btn btn-outline-primary btn-sm" onclick="this.form.import_target.value='brands'"{if $psDemoMode} disabled{/if}>
					{'Import brands'|adminT}
				</button>
				<button type="submit" name="runPsImport" value="1" class="btn btn-outline-primary btn-sm" onclick="this.form.import_target.value='categories'"{if $psDemoMode} disabled{/if}>
					{'Import categories'|adminT}
				</button>
				<button type="submit" name="runPsImport" value="1" class="btn btn-outline-primary btn-sm" onclick="this.form.import_target.value='customers'"{if $psDemoMode} disabled{/if}>
					{'Import customers'|adminT}
				</button>
				<button type="submit" name="runPsImport" value="1" class="btn btn-primary btn-sm" onclick="this.form.import_target.value='all'"{if $psDemoMode} disabled{/if}>
					{'Import all'|adminT}
				</button>
			</div>
			<input type="hidden" name="import_target" value="all">
		</form>

		{if $psImportStats|@count}
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{'Last import summary'|adminT}</h2>
			{foreach $psImportStats as $group}
				{if $group.label}
				<h3 class="small text-muted text-uppercase mb-2{if !$group@first} mt-3{/if}">{$group.label|adminT}</h3>
				{/if}
				<div class="row g-2 mb-2">
					<div class="col-6 col-md-3">
						<div class="border rounded-3 p-2 text-center">
							<div class="small text-muted">{'Total'|adminT}</div>
							<strong>{$group.stats.total|default:0}</strong>
						</div>
					</div>
					<div class="col-6 col-md-3">
						<div class="border rounded-3 p-2 text-center">
							<div class="small text-muted">{'Created'|adminT}</div>
							<strong class="text-success">{$group.stats.created|default:0}</strong>
						</div>
					</div>
					<div class="col-6 col-md-3">
						<div class="border rounded-3 p-2 text-center">
							<div class="small text-muted">{'Skipped'|adminT}</div>
							<strong class="text-primary">{$group.stats.skipped|default:0}</strong>
						</div>
					</div>
					<div class="col-6 col-md-3">
						<div class="border rounded-3 p-2 text-center">
							<div class="small text-muted">{'Errors'|adminT}</div>
							<strong class="text-danger">{$group.stats.errors|default:0}</strong>
						</div>
					</div>
				</div>
				{if isset($group.stats.error_messages) && $group.stats.error_messages|@count}
				<ul class="small text-danger mb-0 ps-3">
					{foreach $group.stats.error_messages as $err}
					<li>{$err|escape}</li>
					{/foreach}
				</ul>
				{/if}
			{/foreach}
		</div>
		{/if}
	</div>

	<div class="col-lg-5">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{'Source tables'|adminT}</h2>
			<p class="text-muted small mb-3">
				{'These PrestaShop tables must exist in the current store database.'|adminT}
			</p>
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>{'Table'|adminT}</th>
							<th>{'Status'|adminT}</th>
							<th class="text-end">{'Rows'|adminT}</th>
						</tr>
					</thead>
					<tbody>
						{foreach $psSourceStatus as $src}
						<tr>
							<td><code>{$src.table|escape}</code></td>
							<td>
								{if $src.exists}
									<span class="badge text-bg-success">{'Found'|adminT}</span>
								{elseif $src.required}
									<span class="badge text-bg-danger">{'Missing'|adminT}</span>
								{else}
									<span class="badge text-bg-secondary">{'Optional'|adminT}</span>
								{/if}
							</td>
							<td class="text-end">{if $src.exists}{$src.count}{else}—{/if}</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			<p class="form-text mt-3 mb-0">
				{'Customers without a phone in ps_address get a unique placeholder. They can sign in with e-mail. Guest and deleted customers are skipped when enabled.'|adminT}
			</p>
		</div>
	</div>
</div>
