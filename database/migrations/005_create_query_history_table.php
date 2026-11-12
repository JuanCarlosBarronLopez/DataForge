<?php

declare(strict_types=1);

/**
 * Historial de consultas ejecutadas desde la consola SQL.
 */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `query_history` (
            `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id`     INT UNSIGNED    NOT NULL,
            `statement`   TEXT            NOT NULL,
            `row_count`   INT             NOT NULL DEFAULT 0,
            `duration_ms` INT             NOT NULL DEFAULT 0,
            `executed_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_query_history_user_time` (`user_id`, `executed_at`),
            CONSTRAINT `fk_query_history_user`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],
    'down' => [
        'DROP TABLE IF EXISTS `query_history`',
    ],
];
