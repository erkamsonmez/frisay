<?php

class QuantityTiersService
{
	public static function ensureSchema(): void
	{
		$table = DB::execute("SHOW TABLES LIKE 'product_quantity_tiers'");

		if (!empty($table)) {
			return;
		}

		DB::execute(
			"CREATE TABLE IF NOT EXISTS `product_quantity_tiers` (
				`id_tier` int(11) NOT NULL AUTO_INCREMENT,
				`id_product` int(11) NOT NULL,
				`min_qty` int(11) NOT NULL DEFAULT 1,
				`unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
				PRIMARY KEY (`id_tier`),
				UNIQUE KEY `product_qty` (`id_product`, `min_qty`),
				KEY `id_product` (`id_product`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * @return list<array{min_qty:int,unit_price:float}>
	 */
	public static function getTiers(int $idProduct): array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return [];
		}

		$rows = DB::execute(
			'SELECT min_qty, unit_price FROM product_quantity_tiers
			WHERE id_product = ? ORDER BY min_qty ASC',
			[$idProduct]
		) ?: [];

		$out = [];

		foreach ($rows as $row) {
			$out[] = [
				'min_qty' => max(1, (int) $row['min_qty']),
				'unit_price' => max(0.0, (float) $row['unit_price']),
			];
		}

		return $out;
	}

	/**
	 * Highest min_qty <= $qty wins; otherwise $fallbackPrice.
	 */
	public static function unitPriceForQty(int $idProduct, float $qty, float $fallbackPrice): float
	{
		$tiers = self::getTiers($idProduct);

		if ($tiers === []) {
			return max(0.0, $fallbackPrice);
		}

		$qty = max(0.0, $qty);
		$matched = null;

		foreach ($tiers as $tier) {
			if ($qty + 0.00001 >= (float) $tier['min_qty']) {
				$matched = $tier;
			}
		}

		if ($matched === null) {
			return max(0.0, $fallbackPrice);
		}

		return max(0.0, (float) $matched['unit_price']);
	}

	/**
	 * Storefront cards with discount badge vs base (lowest qty tier or product price).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function getStorefrontTiers(int $idProduct, float $basePrice): array
	{
		$tiers = self::getTiers($idProduct);

		if ($tiers === []) {
			return [];
		}

		$base = $basePrice;

		foreach ($tiers as $tier) {
			if ((int) $tier['min_qty'] === 1) {
				$base = (float) $tier['unit_price'];
				break;
			}
		}

		if ($base <= 0) {
			$base = $basePrice > 0 ? $basePrice : 1.0;
		}

		$out = [];

		foreach ($tiers as $tier) {
			$minQty = (int) $tier['min_qty'];
			$unit = (float) $tier['unit_price'];
			$total = $unit * $minQty;
			$discountPct = 0;

			if ($base > 0 && $unit < $base) {
				$discountPct = (int) round((1 - ($unit / $base)) * 100);
			}

			$out[] = [
				'min_qty' => $minQty,
				'unit_price' => $unit,
				'unit_price_formatted' => Tools::displayPrice($unit),
				'total' => $total,
				'total_formatted' => Tools::displayPrice($total),
				'discount_pct' => max(0, $discountPct),
				'label' => $minQty . ' x ' . Tools::displayPrice($unit),
			];
		}

		return $out;
	}

	/**
	 * @param list<array{min_qty?:int|string,unit_price?:float|string}|mixed> $rows
	 */
	public static function saveTiers(int $idProduct, array $rows): void
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return;
		}

		DB::execute('DELETE FROM product_quantity_tiers WHERE id_product = ?', [$idProduct]);

		$seen = [];

		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}

			$minQty = max(1, (int) ($row['min_qty'] ?? 0));
			$unitPrice = max(0.0, (float) str_replace(',', '.', (string) ($row['unit_price'] ?? 0)));

			if (isset($seen[$minQty])) {
				continue;
			}

			$seen[$minQty] = true;

			DB::execute(
				'INSERT INTO product_quantity_tiers (id_product, min_qty, unit_price) VALUES (?, ?, ?)',
				[$idProduct, $minQty, $unitPrice]
			);
		}
	}
}
