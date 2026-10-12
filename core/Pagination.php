<?php

class Pagination
{
	public const PER_PAGE = 24;

	public static function build(int $total, int $page, int $perPage, string $baseUrl, array $query = []): array
	{
		$totalPages = max(1, (int) ceil($total / $perPage));
		$page = max(1, min($page, $totalPages));
		$offset = ($page - 1) * $perPage;

		$makeUrl = function (int $p) use ($baseUrl, $query) {
			$params = $query;

			if ($p > 1) {
				$params['page'] = $p;
			} else {
				unset($params['page']);
			}

			$params = array_filter($params, static function ($value) {
				return $value !== null && $value !== '';
			});

			$qs = http_build_query($params);

			return $baseUrl . ($qs !== '' ? '?' . $qs : '');
		};

		$pages = [];
		$start = max(1, $page - 2);
		$end = min($totalPages, $page + 2);

		for ($i = $start; $i <= $end; $i++) {
			$pages[] = [
				'num' => $i,
				'url' => $makeUrl($i),
				'current' => $i === $page,
			];
		}

		return [
			'page' => $page,
			'per_page' => $perPage,
			'total' => $total,
			'total_pages' => $totalPages,
			'offset' => $offset,
			'has_prev' => $page > 1,
			'has_next' => $page < $totalPages,
			'prev_url' => $makeUrl($page - 1),
			'next_url' => $makeUrl($page + 1),
			'pages' => $pages,
		];
	}

	public static function resolveSort(string $sort): string
	{
		$allowed = ['newest', 'price_asc', 'price_desc', 'name_asc', 'discount'];

		if (!in_array($sort, $allowed, true)) {
			$sort = 'newest';
		}

		$map = [
			'newest' => 'p.id_product DESC',
			'price_asc' => 'p.price ASC',
			'price_desc' => 'p.price DESC',
			'name_asc' => 'p.product_name ASC',
			'discount' => '(p.old_price > p.price) DESC, p.id_product DESC',
		];

		return $map[$sort];
	}

	/**
	 * Admin list column sort from ?sort=&dir= (first click DESC, second ASC).
	 *
	 * @param array<string, string> $columns map of request key => SQL expression (no ASC/DESC)
	 * @param array<string, mixed> $queryParams preserved GET params for header links
	 * @return array{
	 *   sort: string,
	 *   dir: string,
	 *   order_by: string,
	 *   query: array<string, mixed>,
	 *   links: array<string, array{url: string, active: bool, dir: string, next_dir: string}>
	 * }
	 */
	public static function adminColumnSort(
		array $columns,
		string $defaultSort,
		string $defaultDir,
		string $baseUrl,
		array $queryParams = [],
		?string $requestSort = null,
		?string $requestDir = null
	): array {
		$sort = trim((string) ($requestSort !== null ? $requestSort : Tools::getValue('sort', $defaultSort)));
		$dir = strtolower(trim((string) ($requestDir !== null ? $requestDir : Tools::getValue('dir', $defaultDir))));

		if ($sort === '' || !isset($columns[$sort])) {
			$sort = $defaultSort;
		}

		if ($dir !== 'asc' && $dir !== 'desc') {
			$dir = strtolower($defaultDir) === 'asc' ? 'asc' : 'desc';
		}

		if (!isset($columns[$sort])) {
			$keys = array_keys($columns);
			$sort = (string) ($keys[0] ?? $defaultSort);
			$dir = 'desc';
		}

		$orderBy = $columns[$sort] . ' ' . strtoupper($dir);
		$query = $queryParams;
		$query['sort'] = $sort;
		$query['dir'] = $dir;

		$links = [];

		foreach ($columns as $key => $_expr) {
			$nextDir = ($sort === $key && $dir === 'desc') ? 'asc' : 'desc';
			$linkQuery = $queryParams;
			$linkQuery['sort'] = $key;
			$linkQuery['dir'] = $nextDir;
			unset($linkQuery['page']);

			$links[$key] = [
				'url' => self::buildQueryUrl($baseUrl, $linkQuery),
				'active' => $sort === $key,
				'dir' => $sort === $key ? $dir : '',
				'next_dir' => $nextDir,
			];
		}

		return [
			'sort' => $sort,
			'dir' => $dir,
			'order_by' => $orderBy,
			'query' => $query,
			'links' => $links,
		];
	}

	/**
	 * @param array<string, mixed> $params
	 */
	public static function buildQueryUrl(string $baseUrl, array $params): string
	{
		$params = array_filter($params, static function ($value) {
			return $value !== null && $value !== '';
		});

		$qs = http_build_query($params);

		return $baseUrl . ($qs !== '' ? '?' . $qs : '');
	}

	public static function getSortOptions(): array
	{
		$options = [
			'newest' => 'Newest',
			'price_asc' => 'Price (Low to High)',
			'price_desc' => 'Price (High to Low)',
			'name_asc' => 'Name (A-Z)',
			'discount' => 'Discount Rate',
		];

		if (function_exists('translate')) {
			foreach ($options as $key => $label) {
				$options[$key] = translate($label);
			}
		}

		return $options;
	}
}
