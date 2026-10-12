CREATE TABLE IF NOT EXISTS `prestashop_import_map` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`entity` varchar(16) NOT NULL,
	`ps_id` int(11) NOT NULL,
	`local_id` int(11) NOT NULL,
	`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `entity_ps` (`entity`, `ps_id`),
	KEY `entity_local` (`entity`, `local_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
