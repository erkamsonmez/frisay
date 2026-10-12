{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$flash|escape}</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
	<div>
		<h2 class="h5 mb-1">{'Security'|adminT}</h2>
		<p class="text-muted small mb-0">{'Session, login, password and HTTPS settings for the store and admin panel.'|adminT}</p>
	</div>
</div>

<div class="row g-3 mb-4">
	{foreach $secChecklist as $item}
	<div class="col-md-6 col-xl-4">
		<div class="admin-panel p-3 h-100">
			<div class="d-flex align-items-start gap-2">
				{if $item.level == 'ok'}
				<span class="badge text-bg-success mt-1">{'OK'|adminT}</span>
				{elseif $item.level == 'warn'}
				<span class="badge text-bg-warning mt-1">{'Check'|adminT}</span>
				{else}
				<span class="badge text-bg-secondary mt-1">{'Info'|adminT}</span>
				{/if}
				<div>
					<p class="fw-semibold mb-1">{$item.title|escape}</p>
					<p class="small text-muted mb-0">{$item.text|escape}</p>
					{if $item.href != ''}
					<a class="small" href="{$item.href|escape}">{'Open'|adminT}</a>
					{/if}
				</div>
			</div>
		</div>
	</div>
	{/foreach}
</div>

<form method="post">
	<input type="hidden" name="saveSecurity" value="1">
	<input type="hidden" name="token" value="{$adminToken}">

	<div class="row g-4">
		<div class="col-lg-6">
			<div class="admin-panel p-3 h-100">
				<h3 class="h6 mb-3">{'Session timeout'|adminT}</h3>
				<p class="text-muted small">{'Idle logout closes the session after no activity. 0 means the session stays until the browser is closed.'|adminT}</p>

				<div class="mb-3">
					<label class="form-label" for="ADMIN_IDLE_TIMEOUT">{'Admin panel'|adminT}</label>
					<select class="form-select" name="ADMIN_IDLE_TIMEOUT" id="ADMIN_IDLE_TIMEOUT">
						<option value="0"{if $secConfig.ADMIN_IDLE_TIMEOUT == '0'} selected{/if}>{'Never (0)'|adminT}</option>
						<option value="15"{if $secConfig.ADMIN_IDLE_TIMEOUT == '15'} selected{/if}>15 {'minutes'|adminT}</option>
						<option value="30"{if $secConfig.ADMIN_IDLE_TIMEOUT == '30' || $secConfig.ADMIN_IDLE_TIMEOUT == ''} selected{/if}>30 {'minutes'|adminT} ({'recommended'|adminT})</option>
						<option value="60"{if $secConfig.ADMIN_IDLE_TIMEOUT == '60'} selected{/if}>60 {'minutes'|adminT}</option>
						<option value="120"{if $secConfig.ADMIN_IDLE_TIMEOUT == '120'} selected{/if}>120 {'minutes'|adminT}</option>
					</select>
				</div>

				<div class="mb-0">
					<label class="form-label" for="CUSTOMER_IDLE_TIMEOUT">{'Customer account'|adminT}</label>
					<select class="form-select" name="CUSTOMER_IDLE_TIMEOUT" id="CUSTOMER_IDLE_TIMEOUT">
						<option value="0"{if $secConfig.CUSTOMER_IDLE_TIMEOUT == '0' || $secConfig.CUSTOMER_IDLE_TIMEOUT == ''} selected{/if}>{'Never (0)'|adminT}</option>
						<option value="15"{if $secConfig.CUSTOMER_IDLE_TIMEOUT == '15'} selected{/if}>15 {'minutes'|adminT}</option>
						<option value="30"{if $secConfig.CUSTOMER_IDLE_TIMEOUT == '30'} selected{/if}>30 {'minutes'|adminT}</option>
						<option value="60"{if $secConfig.CUSTOMER_IDLE_TIMEOUT == '60'} selected{/if}>60 {'minutes'|adminT}</option>
						<option value="120"{if $secConfig.CUSTOMER_IDLE_TIMEOUT == '120'} selected{/if}>120 {'minutes'|adminT}</option>
					</select>
					<div class="form-text">{'Default is never. Remember-me cookies are also cleared when idle logout runs.'|adminT}</div>
				</div>
			</div>
		</div>

		<div class="col-lg-6">
			<div class="admin-panel p-3 h-100">
				<h3 class="h6 mb-3">{'Login lockout'|adminT}</h3>
				<p class="text-muted small">{'Too many wrong passwords temporarily lock that account and IP.'|adminT}</p>

				<div class="row g-3">
					<div class="col-6">
						<label class="form-label" for="ADMIN_LOGIN_MAX_ATTEMPTS">{'Admin attempts'|adminT}</label>
						<input class="form-control" type="number" min="3" max="50" name="ADMIN_LOGIN_MAX_ATTEMPTS" id="ADMIN_LOGIN_MAX_ATTEMPTS" value="{$secConfig.ADMIN_LOGIN_MAX_ATTEMPTS|escape}">
					</div>
					<div class="col-6">
						<label class="form-label" for="ADMIN_LOGIN_LOCK_MINUTES">{'Admin lock (min)'|adminT}</label>
						<input class="form-control" type="number" min="1" max="1440" name="ADMIN_LOGIN_LOCK_MINUTES" id="ADMIN_LOGIN_LOCK_MINUTES" value="{$secConfig.ADMIN_LOGIN_LOCK_MINUTES|escape}">
					</div>
					<div class="col-6">
						<label class="form-label" for="CUSTOMER_LOGIN_MAX_ATTEMPTS">{'Customer attempts'|adminT}</label>
						<input class="form-control" type="number" min="3" max="50" name="CUSTOMER_LOGIN_MAX_ATTEMPTS" id="CUSTOMER_LOGIN_MAX_ATTEMPTS" value="{$secConfig.CUSTOMER_LOGIN_MAX_ATTEMPTS|escape}">
					</div>
					<div class="col-6">
						<label class="form-label" for="CUSTOMER_LOGIN_LOCK_MINUTES">{'Customer lock (min)'|adminT}</label>
						<input class="form-control" type="number" min="1" max="1440" name="CUSTOMER_LOGIN_LOCK_MINUTES" id="CUSTOMER_LOGIN_LOCK_MINUTES" value="{$secConfig.CUSTOMER_LOGIN_LOCK_MINUTES|escape}">
					</div>
				</div>
			</div>
		</div>

		<div class="col-lg-6">
			<div class="admin-panel p-3 h-100">
				<h3 class="h6 mb-3">{'Passwords'|adminT}</h3>

				<div class="mb-3">
					<label class="form-label" for="PASSWORD_MIN_LENGTH">{'Minimum length'|adminT}</label>
					<input class="form-control" type="number" min="6" max="32" name="PASSWORD_MIN_LENGTH" id="PASSWORD_MIN_LENGTH" value="{$secConfig.PASSWORD_MIN_LENGTH|escape}">
				</div>

				<div class="form-check form-switch mb-0">
					<input class="form-check-input" type="checkbox" role="switch" id="PASSWORD_REQUIRE_COMPLEXITY" name="PASSWORD_REQUIRE_COMPLEXITY" value="1"{if $secConfig.PASSWORD_REQUIRE_COMPLEXITY != '0'} checked{/if}>
					<label class="form-check-label" for="PASSWORD_REQUIRE_COMPLEXITY">
						<strong>{'Require letter and number'|adminT}</strong>
						<span class="d-block text-muted small">{'Applies to customer and admin password changes.'|adminT}</span>
					</label>
				</div>
			</div>
		</div>

		<div class="col-lg-6">
			<div class="admin-panel p-3 h-100">
				<h3 class="h6 mb-3">{'HTTPS and headers'|adminT}</h3>

				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" role="switch" id="FORCE_HTTPS" name="FORCE_HTTPS" value="1"{if $secConfig.FORCE_HTTPS == '1'} checked{/if}>
					<label class="form-check-label" for="FORCE_HTTPS">
						<strong>{'Force HTTPS'|adminT}</strong>
						<span class="d-block text-muted small">{'Redirect HTTP requests to HTTPS. Turn this on only after SSL is working.'|adminT}</span>
					</label>
				</div>

				<div class="mb-3">
					<label class="form-label" for="SECURITY_HSTS">{'HSTS'|adminT}</label>
					<select class="form-select" name="SECURITY_HSTS" id="SECURITY_HSTS">
						<option value="auto"{if $secConfig.SECURITY_HSTS == 'auto' || $secConfig.SECURITY_HSTS == ''} selected{/if}>{'Automatic (production + HTTPS)'|adminT}</option>
						<option value="on"{if $secConfig.SECURITY_HSTS == 'on'} selected{/if}>{'Always on HTTPS'|adminT}</option>
						<option value="off"{if $secConfig.SECURITY_HSTS == 'off'} selected{/if}>{'Off'|adminT}</option>
					</select>
					<div class="form-text">{'HSTS tells browsers to use HTTPS only. Do not enable Always if you still need HTTP.'|adminT}</div>
				</div>

				<div class="mb-0">
					<label class="form-label" for="SECURITY_X_FRAME">{'Clickjacking (X-Frame-Options)'|adminT}</label>
					<select class="form-select" name="SECURITY_X_FRAME" id="SECURITY_X_FRAME">
						<option value="SAMEORIGIN"{if $secConfig.SECURITY_X_FRAME != 'DENY'} selected{/if}>SAMEORIGIN</option>
						<option value="DENY"{if $secConfig.SECURITY_X_FRAME == 'DENY'} selected{/if}>DENY</option>
					</select>
					<div class="form-text">{'SAMEORIGIN allows your own site to embed pages. DENY blocks all iframes.'|adminT}</div>
				</div>
			</div>
		</div>
	</div>

	<div class="mt-4">
		<button type="submit" class="btn btn-primary">{'Save'|adminT}</button>
	</div>
</form>

<div class="admin-panel p-3 mt-4">
	<h3 class="h6 mb-3">{'Cron token'|adminT} (SHOP_TOKEN)</h3>
	<p class="text-muted small mb-3">{'Used by scheduled jobs (currency, marketplace, module cron). Regenerating breaks old cron URLs until you update them.'|adminT}</p>
	<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
		<code id="shopTokenDisplay" class="user-select-all">{$secShopTokenMasked|escape}</code>
		<input type="hidden" id="shopTokenFull" value="{$secShopToken|escape}">
		<input type="hidden" id="shopTokenMasked" value="{$secShopTokenMasked|escape}">
		{if $secShopToken|default:'' != ''}
		<button type="button" class="btn btn-sm btn-outline-secondary" id="shopTokenToggle" data-show-label="{'Show token'|adminT}" data-hide-label="{'Hide token'|adminT}">{'Show token'|adminT}</button>
		<button type="button" class="btn btn-sm btn-outline-secondary" id="shopTokenCopy">{'Copy'|adminT}</button>
		{/if}
	</div>
	<form method="post" onsubmit="return confirm({$secRegenConfirm nofilter});">
		<input type="hidden" name="regenerateShopToken" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<button type="submit" class="btn btn-outline-danger">{'Regenerate token'|adminT}</button>
	</form>
</div>
<script>
(function () {
	var display = document.getElementById('shopTokenDisplay');
	var full = document.getElementById('shopTokenFull');
	var masked = document.getElementById('shopTokenMasked');
	var toggle = document.getElementById('shopTokenToggle');
	var copyBtn = document.getElementById('shopTokenCopy');
	if (!display || !full) {
		return;
	}
	if (toggle) {
		toggle.addEventListener('click', function () {
			var showing = display.getAttribute('data-visible') === '1';
			if (showing) {
				display.textContent = masked ? masked.value : '';
				display.setAttribute('data-visible', '0');
				toggle.textContent = toggle.getAttribute('data-show-label') || toggle.textContent;
			} else {
				display.textContent = full.value;
				display.setAttribute('data-visible', '1');
				toggle.textContent = toggle.getAttribute('data-hide-label') || toggle.textContent;
			}
		});
	}
	if (copyBtn && navigator.clipboard) {
		copyBtn.addEventListener('click', function () {
			navigator.clipboard.writeText(full.value).then(function () {
				var prev = copyBtn.textContent;
				copyBtn.textContent = {'Copied'|adminT|js nofilter};
				setTimeout(function () {
					copyBtn.textContent = prev;
				}, 1600);
			});
		});
	}
})();
</script>
