<?php

declare(strict_types=1);

/**
 * Registro de bases de datos administradas y de su propietario.
 *
 * Sin esta tabla cada usuario veria y podria modificar cualquier base de
 * datos del servidor MySQL, lo que rompe el aislamiento entre cuentas.
 */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `managed_databases` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `owner_id`   INT UNSIGNED NOT NULL,
            `name`       VARCHAR(64)  NOT NULL,
            `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_managed_databases_owner_name` (`owner_id`, `name`),
            CONSTRAINT `fk_managed_databases_owner`
                FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],
    'down' => [
        'DROP TABLE IF EXISTS `managed_databases`',
    ],
];
