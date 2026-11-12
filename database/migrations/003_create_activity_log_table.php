<?php

declare(strict_types=1);

/**
 * Bitacora de auditoria: cada operacion relevante queda registrada.
 */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `activity_log` (
            `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id`     INT UNSIGNED    NOT NULL,
            `action`      VARCHAR(60)     NOT NULL,
            `target_type` VARCHAR(40)     NOT NULL,
            `target_name` VARCHAR(190)    NULL,
            `db_context`  VARCHAR(190)    NULL,
            `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_activity_log_user_created` (`user_id`, `created_at`),
            CONSTRAINT `fk_activity_log_user`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],
    'down' => [
        'DROP TABLE IF EXISTS `activity_log`',
    ],
];
