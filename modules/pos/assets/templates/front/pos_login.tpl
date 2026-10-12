<!DOCTYPE html>
<html lang="tr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>POS Giriş — {$posSiteName|escape}</title>
	<link rel="stylesheet" href="{$posCssUrl|escape}">
</head>
<body class="pos-body pos-body--login">
<div class="pos-login">
	<div class="pos-login__card">
		<h1>POS Giriş</h1>
		<p class="pos-login__sub">Kasa ekranına erişmek için PIN girin.</p>

		{if $posError}
		<div class="pos-alert pos-alert--error">{$posError|escape}</div>
		{/if}

		{if !$posHasPin}
		<div class="pos-alert pos-alert--warn">
			PIN henüz ayarlanmamış.
			{if $posIsAdmin}
			<a href="{$posAdminConfigUrl|escape}">Modül ayarlarından</a> PIN belirleyin.
			{else}
			Yönetici PIN oluşturmalı.
			{/if}
		</div>
		{/if}

		<form method="post" class="pos-login__form" autocomplete="off">
			<input type="hidden" name="token" value="{$posToken|escape}">
			<input type="text" name="username" value="" autocomplete="username" tabindex="-1" aria-hidden="true" class="pos-autofill-decoy">
			<label for="pos_pin">PIN</label>
			<input type="text" id="pos_pin" name="pos_pin" class="pos-field pos-pin-masked" inputmode="numeric" pattern="[0-9]*" maxlength="8" autocomplete="one-time-code" required data-lpignore="true" data-1p-ignore="true" data-form-type="other">
			<button type="submit" name="posLogin" value="1" class="pos-btn pos-btn--pay pos-btn--block">Giriş yap</button>
		</form>

		<p class="pos-login__foot">
			<a href="{$posBackUrl|escape}">Geri dön</a>
		</p>
	</div>
</div>
</body>
</html>
