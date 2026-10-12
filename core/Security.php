<?php

class Security
{
	public static function sanitizeContainerSlug(string $slug): string
	{
		$slug = trim($slug);

		if ($slug === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
			return '';
		}

		return $slug;
	}

	public static function validatePassword(string $password, bool $requireComplexity = true): ?string
	{
		$password = (string) $password;
		$minLength = class_exists('SecuritySettings', false)
			? SecuritySettings::getPasswordMinLength()
			: 8;

		if (strlen($password) < $minLength) {
			$msg = 'Şifre en az %d karakter olmalı';
			if (function_exists('translate')) {
				$msg = translate('Password must be at least %d characters');
			}

			return sprintf($msg, $minLength);
		}

		$needComplexity = $requireComplexity
			&& (!class_exists('SecuritySettings', false) || SecuritySettings::requirePasswordComplexity());

		if ($needComplexity && (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password))) {
			if (function_exists('translate')) {
				return translate('Password must include at least one letter and one number');
			}

			return 'Şifre en az bir harf ve bir rakam içermeli';
		}

		return null;
	}

	/**
	 * Encode a value as JSON safe to embed inside HTML <script>
	 * (application/ld+json, application/json, or inline JS).
	 *
	 * Context rules:
	 * - HTML text / attributes: htmlspecialchars / Smarty |escape
	 * - Inline JavaScript strings: jsString() / Smarty |js  (not HTML escape)
	 * - JSON in <script>: this method (never JSON_UNESCAPED_SLASHES alone)
	 *
	 * Fail-closed: returns JSON null if a script breakout marker remains.
	 *
	 * @param mixed $data
	 */
	public static function jsonForHtmlScript($data): string
	{
		$flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

		if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
			$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
		}

		$json = json_encode($data, $flags);

		if (!is_string($json) || $json === '') {
			return 'null';
		}

		$json = str_replace(['<', '>'], ['\u003C', '\u003E'], $json);
		$json = str_replace(["\xE2\x80\xA8", "\xE2\x80\xA9"], ['\u2028', '\u2029'], $json);

		if (stripos($json, '</script') !== false || stripos($json, '<script') !== false) {
			return 'null';
		}

		return $json;
	}

	/**
	 * Encode a scalar as a JavaScript string literal, including quotes.
	 * Use inside <script>: var domain = {$domain|js nofilter};
	 *
	 * @param mixed $value
	 */
	public static function jsString($value): string
	{
		$json = self::jsonForHtmlScript((string) $value);

		if ($json === '' || ($json[0] ?? '') !== '"') {
			return '""';
		}

		return $json;
	}

	public static function isSafeOutboundUrl(string $url): bool
	{
		$url = trim($url);

		if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
			return false;
		}

		$scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?? ''));

		if (!in_array($scheme, ['http', 'https'], true)) {
			return false;
		}

		$host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));

		if ($host === '') {
			return false;
		}

		if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
			return false;
		}

		$ips = [];

		if (filter_var($host, FILTER_VALIDATE_IP)) {
			$ips[] = $host;
		} else {
			$records = @dns_get_record($host, DNS_A + DNS_AAAA);

			if (empty($records)) {
				return false;
			}

			foreach ($records as $record) {
				if (!empty($record['ip'])) {
					$ips[] = $record['ip'];
				}

				if (!empty($record['ipv6'])) {
					$ips[] = $record['ipv6'];
				}
			}
		}

		foreach ($ips as $ip) {
			if (self::isBlockedOutboundIp($ip)) {
				return false;
			}
		}

		return true;
	}

	private const HTML_ALLOW_TAGS = [
		'p' => true, 'br' => true, 'strong' => true, 'b' => true, 'em' => true, 'i' => true,
		'u' => true, 's' => true, 'ul' => true, 'ol' => true, 'li' => true, 'a' => true, 'img' => true,
		'h1' => true, 'h2' => true, 'h3' => true, 'h4' => true, 'h5' => true, 'h6' => true,
		'blockquote' => true, 'table' => true, 'thead' => true, 'tbody' => true, 'tr' => true,
		'th' => true, 'td' => true, 'span' => true, 'div' => true, 'hr' => true, 'pre' => true,
		'code' => true, 'figure' => true, 'figcaption' => true, 'sub' => true, 'sup' => true,
	];

	private const HTML_DROP_TAGS = [
		'script' => true, 'style' => true, 'iframe' => true, 'object' => true, 'embed' => true,
		'applet' => true, 'form' => true, 'input' => true, 'button' => true, 'textarea' => true,
		'select' => true, 'option' => true, 'link' => true, 'meta' => true, 'base' => true,
		'svg' => true, 'math' => true, 'noscript' => true, 'template' => true, 'video' => true,
		'audio' => true, 'source' => true, 'track' => true, 'frame' => true, 'frameset' => true,
		'noframes' => true, 'canvas' => true, 'xmp' => true, 'listing' => true, 'plaintext' => true,
		'noembed' => true, 'title' => true, 'head' => true, 'html' => true, 'body' => true,
	];

	private const HTML_ALLOW_ATTRS = [
		'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true, 'class' => true],
		'img' => ['src' => true, 'alt' => true, 'width' => true, 'height' => true, 'title' => true, 'class' => true],
		'td' => ['colspan' => true, 'rowspan' => true, 'class' => true, 'align' => true],
		'th' => ['colspan' => true, 'rowspan' => true, 'class' => true, 'align' => true],
		'blockquote' => ['cite' => true, 'class' => true],
		'p' => ['class' => true, 'align' => true],
		'div' => ['class' => true, 'align' => true],
		'h1' => ['class' => true, 'align' => true],
		'h2' => ['class' => true, 'align' => true],
		'h3' => ['class' => true, 'align' => true],
		'h4' => ['class' => true, 'align' => true],
		'h5' => ['class' => true, 'align' => true],
		'h6' => ['class' => true, 'align' => true],
		'span' => ['class' => true],
	];

	/**
	 * Allowlist HTML for product/CMS/blog editors (XSS hardening).
	 * Parses as HTML so slash-separated attributes (<img/src=x/onerror=...>) cannot skip filters.
	 */
	public static function sanitizeHtml(string $html): string
	{
		$html = trim(self::decodeOoxmlEscapes($html));

		if ($html === '') {
			return '';
		}

		$html = str_replace("\0", '', $html);

		if (!class_exists('DOMDocument')) {
			return strip_tags($html, '<' . implode('><', array_keys(self::HTML_ALLOW_TAGS)) . '>');
		}

		$previous = libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'
			. '<div id="fshop-html-root">' . $html . '</div></body></html>';
		$flags = 0;

		if (defined('LIBXML_NONET')) {
			$flags |= LIBXML_NONET;
		}

		$loaded = @$dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, $flags);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if ($loaded !== true) {
			return '';
		}

		$root = $dom->getElementById('fshop-html-root');

		if (!$root instanceof DOMElement) {
			$xpath = new DOMXPath($dom);
			$nodes = $xpath->query('//*[@id="fshop-html-root"]');
			$root = ($nodes instanceof DOMNodeList && $nodes->length > 0) ? $nodes->item(0) : null;
		}

		if (!$root instanceof DOMElement) {
			return '';
		}

		self::sanitizeDomChildren($root);
		self::removeEmptyHtmlBlocks($root);

		$out = '';

		foreach ($root->childNodes as $child) {
			$out .= $dom->saveHTML($child);
		}

		$out = self::htmlEntitiesToUtf8($out);
		$out = preg_replace('/(?:\s*<p>(?:\s|&nbsp;|\x{00A0})*<\/p>)+\s*$/u', '', $out) ?? $out;

		return trim($out);
	}

	/**
	 * Excel / OOXML shared-string escapes: _x000D_ (CR), _x000A_ (LF), …
	 */
	public static function decodeOoxmlEscapes(string $value): string
	{
		if ($value === '' || strpos($value, '_x') === false) {
			return $value;
		}

		$decoded = preg_replace_callback(
			'/_x([0-9A-Fa-f]{2,4})_/',
			static function (array $m): string {
				$code = hexdec($m[1]);

				if ($code <= 0) {
					return '';
				}

				if ($code === 0x0D || $code === 0x0A) {
					return "\n";
				}

				if ($code === 0x09) {
					return "\t";
				}

				if (function_exists('mb_chr')) {
					$ch = mb_chr($code, 'UTF-8');

					return is_string($ch) ? $ch : '';
				}

				$ch = @html_entity_decode('&#' . $code . ';', ENT_QUOTES | ENT_HTML5, 'UTF-8');

				return is_string($ch) ? $ch : '';
			},
			$value
		);

		$value = is_string($decoded) ? $decoded : $value;
		$value = str_replace(["\r\n", "\r"], "\n", $value);
		$value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;

		return $value;
	}

	/**
	 * DOMDocument::saveHTML often emits &uuml; etc.; keep UTF-8 letters without opening markup entities.
	 */
	public static function htmlEntitiesToUtf8(string $html): string
	{
		if ($html === '' || strpos($html, '&') === false) {
			return $html;
		}

		$converted = preg_replace_callback(
			'/&(#(?:x[0-9a-fA-F]+|\d+)|[a-zA-Z][a-zA-Z0-9]+);/',
			static function (array $m): string {
				$entity = '&' . $m[1] . ';';
				$decoded = html_entity_decode($entity, ENT_QUOTES | ENT_HTML5, 'UTF-8');

				if ($decoded === '' || $decoded === $entity) {
					return $m[0];
				}

				if (strpbrk($decoded, "<>&\"'") !== false) {
					return $m[0];
				}

				return $decoded;
			},
			$html
		);

		return is_string($converted) ? $converted : $html;
	}

	private static function removeEmptyHtmlBlocks(DOMElement $root): void
	{
		$xpath = new DOMXPath($root->ownerDocument);
		$nodes = $xpath->query('.//p|.//div|.//span|.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $root);

		if (!($nodes instanceof DOMNodeList)) {
			return;
		}

		$remove = [];

		foreach ($nodes as $node) {
			if (!($node instanceof DOMElement) || $node->parentNode === null) {
				continue;
			}

			$text = trim(preg_replace('/\x{00A0}|\x{200B}/u', ' ', $node->textContent ?? '') ?? '');

			if ($text !== '') {
				continue;
			}

			if ($node->getElementsByTagName('img')->length > 0
				|| $node->getElementsByTagName('br')->length > 0
				|| $node->getElementsByTagName('hr')->length > 0) {
				continue;
			}

			$remove[] = $node;
		}

		foreach ($remove as $node) {
			if ($node->parentNode) {
				$node->parentNode->removeChild($node);
			}
		}
	}

	private static function sanitizeDomChildren(DOMNode $parent): void
	{
		$snapshot = [];

		foreach ($parent->childNodes as $child) {
			$snapshot[] = $child;
		}

		foreach ($snapshot as $child) {
			if ($child instanceof DOMElement) {
				self::sanitizeDomElement($child);
			} elseif ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
				if ($child->parentNode) {
					$child->parentNode->removeChild($child);
				}
			}
		}
	}

	private static function sanitizeDomElement(DOMElement $el): void
	{
		if ($el->parentNode === null) {
			return;
		}

		$tag = strtolower($el->tagName);

		if (isset(self::HTML_DROP_TAGS[$tag])) {
			$el->parentNode->removeChild($el);

			return;
		}

		self::sanitizeDomChildren($el);

		if ($el->parentNode === null) {
			return;
		}

		if (!isset(self::HTML_ALLOW_TAGS[$tag])) {
			$parent = $el->parentNode;

			while ($el->firstChild) {
				$parent->insertBefore($el->firstChild, $el);
			}

			$parent->removeChild($el);

			return;
		}

		self::sanitizeAllowedAttributes($el, $tag);
	}

	private static function sanitizeAllowedAttributes(DOMElement $el, string $tag): void
	{
		$allowed = self::HTML_ALLOW_ATTRS[$tag] ?? ['class' => true];
		$remove = [];

		if ($el->hasAttributes()) {
			foreach ($el->attributes as $attr) {
				$name = strtolower($attr->name);

				if (strpos($name, 'on') === 0 || strpos($name, 'xmlns') === 0 || !isset($allowed[$name])) {
					$remove[] = $attr->name;
					continue;
				}

				if (($name === 'href' || $name === 'src' || $name === 'cite') && !self::isSafeHtmlAttrUrl($name, $attr->value)) {
					$remove[] = $attr->name;
					continue;
				}

				if ($name === 'target' && $attr->value !== '_blank' && $attr->value !== '_self') {
					$remove[] = $attr->name;
					continue;
				}

				if ($name === 'align') {
					$align = strtolower(trim($attr->value));

					if (!in_array($align, ['left', 'right', 'center', 'justify'], true)) {
						$remove[] = $attr->name;
					}

					continue;
				}

				if ($name === 'style') {
					$remove[] = $attr->name;
				}
			}
		}

		foreach ($remove as $name) {
			$el->removeAttribute($name);
		}

		if ($tag === 'a' && strtolower($el->getAttribute('target')) === '_blank') {
			$rel = strtolower($el->getAttribute('rel'));
			$tokens = preg_split('/\s+/', $rel, -1, PREG_SPLIT_NO_EMPTY) ?: [];

			foreach (['noopener', 'noreferrer'] as $need) {
				if (!in_array($need, $tokens, true)) {
					$tokens[] = $need;
				}
			}

			$el->setAttribute('rel', implode(' ', $tokens));
		}
	}

	private static function isSafeHtmlAttrUrl(string $attr, string $value): bool
	{
		$value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$value = str_replace("\0", '', $value);

		if ($value === '') {
			return false;
		}

		$compact = strtolower((string) preg_replace('/[\s\x00-\x1f\x7f]+/', '', $value));

		if (strpos($compact, 'javascript:') === 0 || strpos($compact, 'vbscript:') === 0
			|| strpos($compact, 'livescript:') === 0 || strpos($compact, 'mocha:') === 0) {
			return false;
		}

		if (strpos($compact, 'data:') === 0) {
			if ($attr !== 'src') {
				return false;
			}

			return (bool) preg_match('#^data:image/(gif|jpe?g|png|webp)(;|,)#i', $compact);
		}

		$scheme = strtolower((string) (parse_url($value, PHP_URL_SCHEME) ?? ''));

		if ($scheme === '') {
			return !preg_match('/^[a-z][a-z0-9+.-]*:/i', $compact);
		}

		if ($attr === 'href') {
			return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
		}

		return in_array($scheme, ['http', 'https'], true);
	}

	public static function isBlockedOutboundIp(string $ip): bool
	{
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return filter_var(
				$ip,
				FILTER_VALIDATE_IP,
				FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			) === false;
		}

		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return true;
		}

		$packed = inet_pton($ip);

		if ($packed === false) {
			return true;
		}

		if ($packed === inet_pton('::1')) {
			return true;
		}

		// IPv4-mapped IPv6 (::ffff:127.0.0.1) and IPv4-compatible (::127.0.0.1)
		if (strlen($packed) === 16) {
			$prefixMapped = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff";
			$prefixCompat = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

			if (substr($packed, 0, 12) === $prefixMapped || substr($packed, 0, 12) === $prefixCompat) {
				$v4 = inet_ntop(substr($packed, 12));

				if (is_string($v4) && $v4 !== '::' && self::isBlockedOutboundIp($v4)) {
					return true;
				}
			}
		}

		$first = ord($packed[0]);

		if (($first & 0xfe) === 0xfc) {
			return true;
		}

		if ($first === 0xfe && (ord($packed[1]) & 0xc0) === 0x80) {
			return true;
		}

		return false;
	}

	public static function sanitizeSvg(string $content): ?string
	{
		if (stripos($content, '<svg') === false) {
			return null;
		}

		$content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content) ?? '';
		$content = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $content) ?? '';
		$content = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content) ?? '';
		$content = preg_replace('/\b(href|xlink:href)\s*=\s*["\']?\s*javascript:/i', '', $content) ?? '';

		if (preg_match('/<script|javascript:|data:text\/html/i', $content)) {
			return null;
		}

		return $content;
	}

	/**
	 * Session CSRF token (front or admin).
	 */
	public static function getCsrfToken(string $scope = 'front'): string
	{
		$key = $scope === 'admin' ? 'admin_csrf_token' : 'csrf_token';
		$token = (string) ($_SESSION[$key] ?? '');

		if ($token === '') {
			$token = bin2hex(random_bytes(32));
			$_SESSION[$key] = $token;
		}

		return $token;
	}

	/**
	 * Token from POST/GET `token` / `_csrf` / legacy aliases or X-CSRF-TOKEN header.
	 */
	public static function getRequestCsrfToken(): string
	{
		$tokens = self::getRequestCsrfTokens();

		return $tokens[0] ?? '';
	}

	/**
	 * All CSRF candidates on the request (first match wins in validateCsrf).
	 *
	 * Legacy admin/front forms sometimes put the session token in alternate
	 * field names or submit-button values (deleteProduct, csf, …).
	 *
	 * @return list<string>
	 */
	public static function getRequestCsrfTokens(): array
	{
		$candidates = [];

		foreach (['token', '_csrf', 'csf', 'bulkProductToken'] as $key) {
			if (!isset($_POST[$key])) {
				continue;
			}

			$value = trim((string) $_POST[$key]);

			if ($value !== '') {
				$candidates[] = $value;
			}
		}

		// Submit buttons whose value is the session CSRF token.
		foreach ([
			'deleteProduct',
			'deleteCategory',
			'deleteBrand',
			'saveKargo',
			'testOrder',
			'addDiscount',
		] as $key) {
			if (!isset($_POST[$key])) {
				continue;
			}

			$value = trim((string) $_POST[$key]);

			if ($value !== '') {
				$candidates[] = $value;
			}
		}

		if (isset($_GET['token'])) {
			$value = trim((string) $_GET['token']);

			if ($value !== '') {
				$candidates[] = $value;
			}
		}

		if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
			$value = trim((string) $_SERVER['HTTP_X_CSRF_TOKEN']);

			if ($value !== '') {
				$candidates[] = $value;
			}
		}

		return array_values(array_unique($candidates));
	}

	/**
	 * External POSTs that cannot send our session CSRF (PSP callback, cron, API key).
	 */
	public static function isCsrfExemptRequest(): bool
	{
		$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
		$action = strtolower(trim((string) (
			$_POST['action'] ?? $_GET['action'] ?? ''
		)));

		if (strpos($script, '/install/') !== false) {
			return true;
		}

		if (preg_match('#/api/cron\.php$#', $script)) {
			return true;
		}

		if (preg_match('#/api/webapi\.php$#', $script) || preg_match('#/api/psws\.php$#', $script)) {
			return true;
		}

		// Marketplace cron uses SHOP_TOKEN, not session CSRF.
		if (preg_match('#/api/marketplace\.php$#', $script) && $action === 'cron') {
			return true;
		}

		// Payment / module webhooks (bank POST back).
		$uri = str_replace('\\', '/', (string) ($_SERVER['REQUEST_URI'] ?? ''));

		if (preg_match('#/api/module\.php(\?|$)#', $script) || preg_match('#/api/module\.php(\?|$)#', $uri)) {
			$webhookActions = ['callback', 'notify', 'webhook', 'ipn', 'return', '3d-return', '3dreturn'];
			$queryAction = strtolower(trim((string) ($_GET['action'] ?? '')));

			if (in_array($action, $webhookActions, true) || in_array($queryAction, $webhookActions, true)) {
				return true;
			}
		}

		if (preg_match('#/(iyzico-callback|paytr-callback|parampos-callback|esnekpos-callback|kuveytturk-callback|tami-callback)(/|\?|$)#i', $uri)) {
			return true;
		}

		return false;
	}

	public static function validateCsrf(string $scope = 'front'): bool
	{
		$sessionToken = self::getCsrfToken($scope);
		$providedList = self::getRequestCsrfTokens();

		// Never treat empty === empty as valid.
		if ($providedList === []) {
			return false;
		}

		$candidates = [];

		if ($sessionToken !== '') {
			$candidates[] = $sessionToken;
		}

		// Admin UI forms posting to /api/module.php use front bootstrap but send admin_csrf_token.
		if ($scope === 'front') {
			$adminToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

			if ($adminToken !== '') {
				$candidates[] = $adminToken;
			}
		}

		if ($candidates === []) {
			return false;
		}

		foreach ($providedList as $provided) {
			foreach ($candidates as $candidate) {
				if (hash_equals($candidate, $provided)) {
					return true;
				}

				// Legacy admin forms sometimes submit md5(token).
				if ($scope === 'admin' && hash_equals(md5($candidate), $provided)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Enforce CSRF on the current request. Call only for POST (or state-changing) requests.
	 */
	public static function requireCsrf(string $scope = 'front'): void
	{
		if (self::validateCsrf($scope)) {
			return;
		}

		http_response_code(403);

		$wantsJson = (
			strpos(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/api/') !== false
			|| strpos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false
			|| strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
		);

		if ($wantsJson) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => false,
				'message' => 'Geçersiz istek (CSRF)',
				'error' => 'csrf_invalid',
			], JSON_UNESCAPED_UNICODE);
			exit;
		}

		header('Content-Type: text/plain; charset=utf-8');
		echo 'Geçersiz istek (CSRF)';
		exit;
	}

	/**
	 * If this is POST and not exempt, require a valid CSRF token.
	 */
	public static function enforcePostCsrf(string $scope = 'front'): void
	{
		$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

		if ($method !== 'POST' && $method !== 'PUT' && $method !== 'PATCH' && $method !== 'DELETE') {
			return;
		}

		if (self::isCsrfExemptRequest()) {
			return;
		}

		self::requireCsrf($scope);
	}

	public static function isUnsafeZipPath(string $entry): bool
	{
		$entry = str_replace('\\', '/', $entry);

		if ($entry === '' || strpos($entry, "\0") !== false || strpos($entry, ':') !== false) {
			return true;
		}

		if ($entry[0] === '/' || strpos($entry, '//') === 0 || preg_match('#^[a-zA-Z]:#', $entry)) {
			return true;
		}

		if (strpos($entry, '../') !== false || $entry === '..' || substr($entry, -3) === '/..') {
			return true;
		}

		$parts = explode('/', $entry);
		$last = count($parts) - 1;

		foreach ($parts as $i => $part) {
			if ($part === '.' || $part === '..') {
				return true;
			}

			if ($part === '' && $i !== $last) {
				return true;
			}
		}

		return false;
	}

	public static function isZipEntrySymlink($zip, int $index): bool
	{
		if (!class_exists('ZipArchive') || !($zip instanceof ZipArchive) || !method_exists($zip, 'getExternalAttributesIndex')) {
			return false;
		}

		$opsys = 0;
		$attr = 0;

		if (!$zip->getExternalAttributesIndex($index, $opsys, $attr)) {
			return false;
		}

		$unix = defined('ZipArchive::OPSYS_UNIX') ? ZipArchive::OPSYS_UNIX : 3;

		if ((int) $opsys !== (int) $unix) {
			return false;
		}

		$mode = ($attr >> 16) & 0xFFFF;

		return ($mode & 0170000) === 0120000;
	}
}
