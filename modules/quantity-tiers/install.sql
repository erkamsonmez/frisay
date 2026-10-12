CREATE TABLE IF NOT EXISTS `product_quantity_tiers` (
	`id_tier` int(11) NOT NULL AUTO_INCREMENT,
	`id_product` int(11) NOT NULL,
	`min_qty` int(11) NOT NULL DEFAULT 1,
	`unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
	PRIMARY KEY (`id_tier`),
	UNIQUE KEY `product_qty` (`id_product`, `min_qty`),
	KEY `id_product` (`id_product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
