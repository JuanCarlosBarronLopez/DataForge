<?php

declare(strict_types=1);

/**
 * Crea la tabla de usuarios de la base de datos de sistema.
 */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `users` (
            `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `username`        VARCHAR(30)  NOT NULL,
            `email`           VARCHAR(190) NOT NULL,
            `password_hash`   VARCHAR(255) NOT NULL,
            `theme`           VARCHAR(30)  NOT NULL DEFAULT 'neutral',
            `color_mode`      VARCHAR(10)  NOT NULL DEFAULT 'dark',
            `profile_pic`     VARCHAR(255) NULL,
            `onboarding_done` TINYINT(1)   NOT NULL DEFAULT 0,
            `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `last_login`      TIMESTAMP    NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_users_email` (`email`),
            UNIQUE KEY `uk_users_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],
    'down' => [
        'DROP TABLE IF EXISTS `users`',
    ],
];
