<?php

require_once dirname(__DIR__) . '/config/install_gate.php';

if (!fshop_is_installed()) {
	fshop_redirect_to_installer();
}

define('IN_SCRIPT', true);

foreach (['REDIRECT_HTTP_AUTHORIZATION', 'REDIRECT_REDIRECT_HTTP_AUTHORIZATION'] as $authEnv) {
	if (empty($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER[$authEnv])) {
		$_SERVER['HTTP_AUTHORIZATION'] = (string) $_SERVER[$authEnv];
	}
}

if (!empty($_SERVER['HTTP_AUTHORIZATION']) && empty($_SERVER['PHP_AUTH_USER'])
	&& preg_match('/^Basic\s+(.+)$/i', (string) $_SERVER['HTTP_AUTHORIZATION'], $authMatch)
) {
	$decodedAuth = base64_decode($authMatch[1], true);
	if (is_string($decodedAuth) && $decodedAuth !== '') {
		$authParts = explode(':', $decodedAuth, 2);
		$_SERVER['PHP_AUTH_USER'] = $authParts[0] ?? '';
		$_SERVER['PHP_AUTH_PW'] = $authParts[1] ?? '';
	}
}

require_once dirname(__DIR__) . '/config/settings.php';
require_once dirname(__DIR__) . '/core/PsWebService.php';

PsWebService::dispatch();
