<?php

declare(strict_types=1);

/**
 * Historial de intentos de inicio de sesion por cuenta y por direccion IP.
 *
 * Permite bloquear una cuenta concreta aunque el atacante reparta los
 * intentos entre varias direcciones IP.
 */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `identifier`   VARCHAR(190) NOT NULL,
            `ip`           VARCHAR(45)  NOT NULL,
            `succeeded`    TINYINT(1)   NOT NULL DEFAULT 0,
            `attempted_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_login_attempts_identifier_time` (`identifier`, `attempted_at`),
            KEY `idx_login_attempts_ip_time` (`ip`, `attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],
    'down' => [
        'DROP TABLE IF EXISTS `login_attempts`',
    ],
];
