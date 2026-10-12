<div class="t4-auth" data-auth-page="register">
	<a href="{$domain}" class="t4-auth__home" title="{'Home'|translate}">
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
		<span>{'Back to store'|translate|default:'Mağazaya dön'}</span>
	</a>

	<div class="t4-auth__card t4-auth__card--register">
		<div class="t4-auth__brand">
			{if $t4Logo|default:'' != '' || $siteLogos.header|default:'' != ''}
			<a href="{$domain}" class="t4-auth__logo-link">
				<img src="{if $t4Logo|default:'' != ''}{$t4Logo|escape}{else}{$siteLogos.header|escape}{/if}?v={$minute|default:1}" alt="{$siteName|escape}" class="t4-auth__logo">
			</a>
			{else}
			<a href="{$domain}" class="t4-auth__mark" aria-label="{$siteName|escape}">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
			</a>
			{/if}
		</div>

		<h1 class="t4-auth__title">{'Sign Up'|translate}</h1>
		<p class="t4-auth__subtitle">{'Register subtitle'|translate|default:'Hesap oluşturarak siparişlerinizi ve favorilerinizi kolayca yönetin.'}</p>

		{if isset($registerSuccess) && $registerSuccess}
		<div class="t4-auth__success">
			<p class="mb-3">{$authNotice|escape}</p>
			<a href="{$domain}login" class="btn t4-auth__submit w-100">{'Sign In'|translate}</a>
		</div>
		{else}
		{if $authNotice}
		<div class="alert alert-info auth-notice">{$authNotice|escape}</div>
		{/if}
		{if $authError}
		<div class="alert alert-danger auth-notice">{$authError|escape}</div>
		{/if}

		{if $googleLoginUrl}
		<div class="t4-auth__social">
			<a href="{$googleLoginUrl|escape}" class="t4-auth__google">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
				<span>{'Sign in with Google'|translate|default:'Google ile giriş yap'}</span>
			</a>
		</div>
		<div class="t4-auth__divider"><span>{'or'|translate|default:'veya'}</span></div>
		{/if}

		<form id="registerPageForm" method="post" action="{$domain}register" class="t4-auth__form">
			<input type="hidden" name="token" value="{$token}">
			<input type="hidden" name="registerUser" value="1">

			<div class="mb-3">
				<label class="form-label" for="registerPageName">{'Full Name'|translate}</label>
				<input type="text" id="registerPageName" name="full_name" class="form-control t4-auth__input" placeholder="{'Your full name'|translate}" value="{$formData.full_name|escape}" required autocomplete="name">
			</div>

			<div class="mb-3">
				<label class="form-label" for="registerPagePhone">{'Phone'|translate}</label>
				<input type="tel" id="registerPagePhone" name="phone" class="form-control t4-auth__input phone-input" placeholder="{'Phone placeholder'|translate}" value="{$formData.phone|escape}" required autocomplete="tel">
			</div>

			<div class="mb-3">
				<label class="form-label" for="registerPageEmail">{'Email'|translate}</label>
				<input type="email" id="registerPageEmail" name="email" class="form-control t4-auth__input" placeholder="{'Email placeholder'|translate}" value="{$formData.email|escape}" required autocomplete="email">
			</div>

			<div class="mb-3">
				<label class="form-label" for="registerPagePassword">{'Password'|translate}</label>
				<div class="auth-password-wrap">
					<input type="password" id="registerPagePassword" name="password" class="form-control t4-auth__input" placeholder="{'Password placeholder'|translate}" required autocomplete="new-password" minlength="8">
					<button type="button" class="auth-password-toggle" data-target="#registerPagePassword" data-show-label="{'Show password'|translate|escape}" data-hide-label="{'Hide password'|translate|escape}" aria-label="{'Show password'|translate}">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
					</button>
				</div>
				<div class="form-text auth-password-hint">{'Password requirements hint'|translate}</div>
			</div>

			<div class="mb-4">
				<label class="form-label" for="registerPagePassword2">{'Password repeat'|translate}</label>
				<div class="auth-password-wrap">
					<input type="password" id="registerPagePassword2" name="password2" class="form-control t4-auth__input" placeholder="{'Password repeat placeholder'|translate}" required autocomplete="new-password" minlength="8">
					<button type="button" class="auth-password-toggle" data-target="#registerPagePassword2" data-show-label="{'Show password'|translate|escape}" data-hide-label="{'Hide password'|translate|escape}" aria-label="{'Show password'|translate}">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
					</button>
				</div>
			</div>

			{if $hooks.auth_register}
			{$hooks.auth_register nofilter}
			{/if}

			<button type="submit" class="btn t4-auth__submit w-100" id="registerPageSubmit">{'Create account'|translate}</button>
		</form>
		{/if}

		<p class="t4-auth__footer">
			{'Already a member'|translate|default:'Zaten üye misiniz?'}
			<a href="{$domain}login">{'Sign In'|translate}</a>
		</p>
	</div>
</div>
