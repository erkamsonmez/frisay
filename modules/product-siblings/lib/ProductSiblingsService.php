<?php

class ProductSiblingsService
{
	public static function ensureSchema(): void
	{
		$groups = DB::execute("SHOW TABLES LIKE 'product_sibling_groups'");

		if (empty($groups)) {
			DB::execute(
				"CREATE TABLE IF NOT EXISTS `product_sibling_groups` (
					`id_group` int(11) NOT NULL AUTO_INCREMENT,
					`label` varchar(120) NOT NULL DEFAULT '',
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_group`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		$items = DB::execute("SHOW TABLES LIKE 'product_sibling_items'");

		if (empty($items)) {
			DB::execute(
				"CREATE TABLE IF NOT EXISTS `product_sibling_items` (
					`id_item` int(11) NOT NULL AUTO_INCREMENT,
					`id_group` int(11) NOT NULL,
					`id_product` int(11) NOT NULL,
					`label` varchar(120) NOT NULL DEFAULT '',
					`position` int(11) NOT NULL DEFAULT 0,
					PRIMARY KEY (`id_item`),
					UNIQUE KEY `group_product` (`id_group`, `id_product`),
					UNIQUE KEY `product_once` (`id_product`),
					KEY `id_group` (`id_group`),
					KEY `id_product` (`id_product`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}
	}

	public static function getGroupIdForProduct(int $idProduct): int
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return 0;
		}

		$id = DB::getValue(
			'SELECT id_group FROM product_sibling_items WHERE id_product = ? LIMIT 1',
			[$idProduct]
		);

		return $id !== false ? (int) $id : 0;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function getSiblingsForProduct(int $idProduct): array
	{
		$idGroup = self::getGroupIdForProduct($idProduct);

		if ($idGroup <= 0) {
			return [];
		}

		$rows = DB::execute(
			'SELECT i.id_product, i.label, i.position, p.product_name, p.price, p.active,
				p.product_link, c.category_link, img.id_image
			FROM product_sibling_items i
			INNER JOIN products p ON p.id_product = i.id_product
			LEFT JOIN categories c ON c.id_category = p.id_category
			LEFT JOIN images img ON img.id_product = p.id_product AND img.cover = 1
			WHERE i.id_group = ? AND p.active = 1
			ORDER BY i.position ASC, i.id_item ASC',
			[$idGroup]
		) ?: [];

		$out = [];

		foreach ($rows as $row) {
			$id = (int) $row['id_product'];
			$product = Product::getById($id);

			if (!$product) {
				continue;
			}

			$label = trim((string) ($row['label'] ?? ''));

			if ($label === '') {
				$label = (string) ($product['product_name'] ?? '');
			}

			$out[] = [
				'id_product' => $id,
				'label' => $label,
				'product_name' => (string) ($product['product_name'] ?? ''),
				'price' => (float) ($product['price'] ?? 0),
				'price_formatted' => Tools::displayPrice((float) ($product['price'] ?? 0)),
				'url' => (string) ($product['url'] ?? ''),
				'image_url' => (string) ($product['image_url'] ?? ''),
				'current' => $id === $idProduct,
			];
		}

		return $out;
	}

	/**
	 * @return list<array{id_product:int,product_name:string,label:string,price:float,price_formatted:string}>
	 */
	public static function getAdminItems(int $idProduct): array
	{
		$idGroup = self::getGroupIdForProduct($idProduct);

		if ($idGroup <= 0) {
			return [];
		}

		$rows = DB::execute(
			'SELECT i.id_product, i.label, p.product_name, p.price
			FROM product_sibling_items i
			INNER JOIN products p ON p.id_product = i.id_product
			WHERE i.id_group = ?
			ORDER BY i.position ASC, i.id_item ASC',
			[$idGroup]
		) ?: [];

		$out = [];

		foreach ($rows as $row) {
			$out[] = [
				'id_product' => (int) $row['id_product'],
				'product_name' => (string) $row['product_name'],
				'label' => (string) ($row['label'] ?? ''),
				'price' => (float) $row['price'],
				'price_formatted' => Tools::displayPrice((float) $row['price']),
			];
		}

		return $out;
	}

	/**
	 * Replace the sibling group for $idProduct with the given product list (must include self).
	 *
	 * @param list<array{id_product?:int,label?:string}|int> $items
	 */
	public static function saveForProduct(int $idProduct, array $items): void
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return;
		}

		$normalized = [];
		$seen = [];

		foreach ($items as $item) {
			$id = 0;
			$label = '';

			if (is_array($item)) {
				$id = (int) ($item['id_product'] ?? 0);
				$label = mb_substr(trim((string) ($item['label'] ?? '')), 0, 120);
			} else {
				$id = (int) $item;
			}

			if ($id <= 0 || isset($seen[$id])) {
				continue;
			}

			$exists = DB::getValue('SELECT id_product FROM products WHERE id_product = ? LIMIT 1', [$id]);

			if ($exists === false) {
				continue;
			}

			$seen[$id] = true;
			$normalized[] = ['id_product' => $id, 'label' => $label];
		}

		if (!isset($seen[$idProduct])) {
			array_unshift($normalized, ['id_product' => $idProduct, 'label' => '']);
		}

		$oldGroup = self::getGroupIdForProduct($idProduct);

		if (count($normalized) < 2) {
			if ($oldGroup > 0) {
				DB::execute('DELETE FROM product_sibling_items WHERE id_group = ?', [$oldGroup]);
				DB::execute('DELETE FROM product_sibling_groups WHERE id_group = ?', [$oldGroup]);
			}

			return;
		}

		// Detach products that will join this group from other groups
		$ids = array_column($normalized, 'id_product');
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$otherGroups = DB::execute(
			"SELECT DISTINCT id_group FROM product_sibling_items WHERE id_product IN ($placeholders)",
			$ids
		) ?: [];

		foreach ($otherGroups as $row) {
			$gid = (int) $row['id_group'];
			DB::execute(
				"DELETE FROM product_sibling_items WHERE id_group = ? AND id_product IN ($placeholders)",
				array_merge([$gid], $ids)
			);
			$left = (int) DB::getValue('SELECT COUNT(*) FROM product_sibling_items WHERE id_group = ?', [$gid]);

			if ($left < 2) {
				DB::execute('DELETE FROM product_sibling_items WHERE id_group = ?', [$gid]);
				DB::execute('DELETE FROM product_sibling_groups WHERE id_group = ?', [$gid]);
			}
		}

		$idGroup = $oldGroup;

		if ($idGroup <= 0 || (int) DB::getValue('SELECT id_group FROM product_sibling_groups WHERE id_group = ?', [$idGroup]) <= 0) {
			DB::execute("INSERT INTO product_sibling_groups (label) VALUES ('')");
			$idGroup = (int) DB::getValue('SELECT LAST_INSERT_ID()');
		} else {
			DB::execute('DELETE FROM product_sibling_items WHERE id_group = ?', [$idGroup]);
		}

		$pos = 0;

		foreach ($normalized as $row) {
			DB::execute(
				'INSERT INTO product_sibling_items (id_group, id_product, label, position) VALUES (?, ?, ?, ?)',
				[$idGroup, $row['id_product'], $row['label'], $pos]
			);
			$pos++;
		}
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function searchProducts(string $query, int $excludeId = 0, int $limit = 20): array
	{
		$query = trim($query);
		$limit = max(1, min(50, $limit));

		$sql = 'SELECT id_product, product_name, price, stock
			FROM products
			WHERE active = 1';
		$params = [];

		if ($excludeId > 0) {
			$sql .= ' AND id_product != ?';
			$params[] = $excludeId;
		}

		if ($query !== '') {
			$sql .= ' AND (product_name LIKE ? OR stock_code LIKE ? OR CAST(id_product AS CHAR) = ?)';
			$like = '%' . $query . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $query;
		}

		$sql .= ' ORDER BY product_name ASC LIMIT ' . (int) $limit;
		$rows = DB::execute($sql, $params) ?: [];
		$out = [];

		foreach ($rows as $row) {
			$out[] = [
				'id_product' => (int) $row['id_product'],
				'product_name' => (string) $row['product_name'],
				'price' => (float) $row['price'],
				'price_formatted' => Tools::displayPrice((float) $row['price']),
				'stock' => (int) $row['stock'],
			];
		}

		return $out;
	}
}
