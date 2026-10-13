<?php

class Seo
{
	/** @return array<string, array{label: string, title_key: string, desc_key: string, default_title: string, default_desc: string}> */
	public static function getPageDefinitions(): array
	{
		$siteName = Settings::get('SITE_NAME') ?: 'FShop';

		return [
			'home' => [
				'label' => 'Ana Sayfa',
				'title_key' => 'SEO_HOME_TITLE',
				'desc_key' => 'SEO_HOME_DESC',
				'default_title' => $siteName,
				'default_desc' => $siteName,
			],
			'contact' => [
				'label' => 'İletişim',
				'title_key' => 'SEO_CONTACT_TITLE',
				'desc_key' => 'SEO_CONTACT_DESC',
				'default_title' => 'İletişim',
				'default_desc' => 'Bizimle iletişime geçin',
			],
			'search' => [
				'label' => 'Arama',
				'title_key' => 'SEO_SEARCH_TITLE',
				'desc_key' => 'SEO_SEARCH_DESC',
				'default_title' => 'Arama',
				'default_desc' => 'Ürün ara',
			],
			'special' => [
				'label' => 'Kampanyalar',
				'title_key' => 'SEO_SPECIAL_TITLE',
				'desc_key' => 'SEO_SPECIAL_DESC',
				'default_title' => 'Kampanyalar',
				'default_desc' => 'İndirimli ürünler ve kampanyalar',
			],
			'cart' => [
				'label' => 'Sepet',
				'title_key' => 'SEO_CART_TITLE',
				'desc_key' => 'SEO_CART_DESC',
				'default_title' => 'Sepetim',
				'default_desc' => 'Alışveriş sepetiniz',
			],
		];
	}

	/** @return array<string, array{title: string, description: string}> */
	public static function getAllPageValues(): array
	{
		$values = [];

		foreach (self::getPageDefinitions() as $pageId => $def) {
			$values[$pageId] = [
				'title' => self::get($def['title_key'], $def['default_title']),
				'description' => self::get($def['desc_key'], $def['default_desc']),
			];
		}

		return $values;
	}

	public static function get(string $key, string $default = ''): string
	{
		$value = trim((string) Settings::get($key));

		return $value !== '' ? $value : $default;
	}

	public static function savePages(array $data): array
	{
		foreach (self::getPageDefinitions() as $pageId => $def) {
			$title = mb_substr(trim(strip_tags((string) ($data[$pageId . '_title'] ?? ''))), 0, 255);
			$description = mb_substr(trim(strip_tags((string) ($data[$pageId . '_description'] ?? ''))), 0, 512);

			if (!Settings::set($def['title_key'], $title)) {
				return ['success' => false, 'message' => 'SEO ayarları kaydedilemedi'];
			}

			if (!Settings::set($def['desc_key'], $description)) {
				return ['success' => false, 'message' => 'SEO ayarları kaydedilemedi'];
			}
		}

		return ['success' => true, 'message' => 'SEO ayarları kaydedildi'];
	}

	/** @return array{title: string, description: string} */
	public static function resolvePage(string $pageId, string $fallbackTitle = '', string $fallbackDesc = ''): array
	{
		$defs = self::getPageDefinitions();
		$def = $defs[$pageId] ?? null;

		if (!$def) {
			return [
				'title' => $fallbackTitle,
				'description' => $fallbackDesc,
			];
		}

		$title = self::get($def['title_key'], '');
		$description = self::get($def['desc_key'], '');

		if ($title === '') {
			$title = $fallbackTitle !== '' ? $fallbackTitle : $def['default_title'];
		}

		if ($description === '') {
			$description = $fallbackDesc !== '' ? $fallbackDesc : $def['default_desc'];
		}

		return [
			'title' => $title,
			'description' => $description,
		];
	}

	/** @return array{title: string, description: string} */
	public static function resolveEntity(string $metaTitle, string $metaDescription, string $fallbackTitle, string $fallbackDesc): array
	{
		$title = trim($metaTitle);
		$description = trim($metaDescription);

		return [
			'title' => $title !== '' ? $title : $fallbackTitle,
			'description' => $description !== '' ? $description : $fallbackDesc,
		];
	}

	/**
	 * Short brand suffix for non-home pages (browser / OG title).
	 * Empty setting falls back to SITE_NAME for backward compatibility.
	 */
	public static function getTitleSuffix(): string
	{
		$suffix = trim((string) Settings::get('SEO_TITLE_SUFFIX'));
		if ($suffix !== '') {
			return $suffix;
		}

		$siteName = trim((string) Settings::get('SITE_NAME'));

		return $siteName !== '' ? $siteName : 'FShop';
	}

	/**
	 * Full document title for &lt;title&gt; / og:title / twitter:title.
	 * Home uses the page title as-is; other pages append " | {suffix}".
	 */
	public static function formatDocumentTitle(string $pageTitle, string $pageName = ''): string
	{
		$pageTitle = trim($pageTitle);
		$siteName = trim((string) Settings::get('SITE_NAME'));
		if ($siteName === '') {
			$siteName = 'FShop';
		}
		$suffix = self::getTitleSuffix();

		if ($pageName === 'home') {
			return $pageTitle !== '' ? $pageTitle : $siteName;
		}

		if ($pageTitle === '' || strcasecmp($pageTitle, $siteName) === 0 || strcasecmp($pageTitle, $suffix) === 0) {
			return $pageTitle !== '' ? $pageTitle : $suffix;
		}

		$tail = ' | ' . $suffix;
		if (mb_substr($pageTitle, -mb_strlen($tail)) === $tail) {
			return $pageTitle;
		}

		return $pageTitle . $tail;
	}

	public static function saveTitleSuffix(string $suffix): bool
	{
		return Settings::set('SEO_TITLE_SUFFIX', mb_substr(trim(strip_tags($suffix)), 0, 128));
	}

	/** @return array<string, string> */
	public static function getSchemaOrgFields(): array
	{
		return [
			'SCHEMA_ORG_STREET' => trim((string) Settings::get('SCHEMA_ORG_STREET')),
			'SCHEMA_ORG_CITY' => trim((string) Settings::get('SCHEMA_ORG_CITY')),
			'SCHEMA_ORG_POSTAL' => trim((string) Settings::get('SCHEMA_ORG_POSTAL')),
			'SCHEMA_ORG_LAT' => trim((string) Settings::get('SCHEMA_ORG_LAT')),
			'SCHEMA_ORG_LNG' => trim((string) Settings::get('SCHEMA_ORG_LNG')),
			'SCHEMA_FACEBOOK_URL' => trim((string) Settings::get('SCHEMA_FACEBOOK_URL')),
			'SCHEMA_INSTAGRAM_URL' => trim((string) Settings::get('SCHEMA_INSTAGRAM_URL')),
			'SCHEMA_YOUTUBE_URL' => trim((string) Settings::get('SCHEMA_YOUTUBE_URL')),
		];
	}

	public static function saveSchemaOrg(array $data): bool
	{
		$keys = array_keys(self::getSchemaOrgFields());

		foreach ($keys as $key) {
			if (!Settings::set($key, trim((string) ($data[$key] ?? '')))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Technical indexing metadata, independent from the merchant-edited title/description.
	 * Category pagination uses self-referencing canonical URLs; filtered/sorted variants
	 * are excluded from the index without blocking their links.
	 *
	 * @param array<string, mixed> $context
	 * @param array<string, mixed> $query
	 * @return array{canonical: string, robots: string, ogType: string, ogImage: string}
	 */
	public static function technicalMeta(string $pageName, array $context = [], array $query = []): array
	{
		$root = rtrim((string) Settings::get('DOMAIN'), '/');
		$requestUrl = self::requestPageUrl();
		$canonical = $requestUrl;
		$private = [
			'404', 'search', 'cart', 'checkout', 'checkout-success', 'login',
			'register', 'logout', 'forgot-password', 'reset-password', 'my-account',
			'orders', 'order', 'favorites', 'returns', 'return', 'return-request',
			'cancel-request', 'gate', 'truck', 'card-payment', 'payment', 'cancel',
		];
		$noindex = in_array($pageName, $private, true)
			|| str_contains($pageName, 'payment')
			|| str_contains($pageName, 'callback')
			|| http_response_code() === 404;

		if ($pageName === 'home') {
			$canonical = $root . '/';
		} elseif ($pageName === 'product') {
			$canonical = self::sameSiteUrl((string) ($context['productUrl'] ?? ''), $requestUrl);
		} elseif (in_array($pageName, ['category', 'brand', 'catalog', 'special'], true)) {
			$base = self::sameSiteUrl((string) ($context['catalogBaseUrl'] ?? ''), $requestUrl);
			$canonical = $base;
			$filters = $context['catalogFilter'] ?? [];
			$filtered = is_array($filters) && !empty($filters['hasActive']);
			foreach (['subcat', 'brand', 'price_min', 'price_max', 'stock', 'discount', 'var'] as $filterKey) {
				if (isset($query[$filterKey]) && $query[$filterKey] !== '') {
					$filtered = true;
				}
			}
			$sort = (string) ($query['sort'] ?? '');
			$defaultSort = $pageName === 'special' ? 'discount' : 'newest';
			$sorted = $sort !== '' && $sort !== $defaultSort;
			$noindex = $noindex || $filtered || $sorted;

			$pagination = $context['pagination'] ?? [];
			$page = is_array($pagination) ? (int) ($pagination['page'] ?? 1) : 1;
			if (!$filtered && !$sorted && $page > 1) {
				$canonical = $base . (str_contains($base, '?') ? '&' : '?') . 'page=' . $page;
			}
		}
		// Preserve only approved pagination. Tracking, session and campaign
		// parameters are always excluded from canonical URLs.
		$image = (string) ($context['imageUrl'] ?? '');
		return [
			'canonical' => $canonical,
			'robots' => $noindex ? 'noindex, follow' : 'index, follow',
			'ogType' => $pageName === 'product' ? 'product' : 'website',
			'ogImage' => $pageName === 'product' ? $image : '',
		];
	}

	/** Absolute request path resolved against the configured public DOMAIN (proxy-safe). */
	public static function requestPageUrl(): string
	{
		$root = rtrim((string) Settings::get('DOMAIN'), '/');
		$request = (string) ($_SERVER['REQUEST_URI'] ?? '/');
		$path = (string) (parse_url($request, PHP_URL_PATH) ?: '/');
		$path = '/' . ltrim(preg_replace('~/+~', '/', $path), '/');
		if ($path === '/index.php') {
			$path = '/';
		}
		return $root . $path;
	}

	/** Restrict canonical overrides to the configured shop origin. */
	private static function sameSiteUrl(string $candidate, string $fallback): string
	{
		$shop = parse_url((string) Settings::get('DOMAIN'));
		$url = parse_url($candidate);
		if (!is_array($shop) || !is_array($url)
			|| !isset($shop['host'], $url['host'])
			|| strcasecmp((string) $shop['host'], (string) $url['host']) !== 0) {
			return $fallback;
		}
		$path = (string) ($url['path'] ?? '/');
		return rtrim((string) Settings::get('DOMAIN'), '/') . '/' . ltrim($path, '/');
	}

}
