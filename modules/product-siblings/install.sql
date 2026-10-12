CREATE TABLE IF NOT EXISTS `product_sibling_groups` (
	`id_group` int(11) NOT NULL AUTO_INCREMENT,
	`label` varchar(120) NOT NULL DEFAULT '',
	`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_sibling_items` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
