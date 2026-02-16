<?php
/**
 * Migration: Create Queue Tables
 * Tabelas para sistema de filas e notificações
 */

return new class {

    public function up() {
        // Tabela de jobs
        DB::statement("
            CREATE TABLE IF NOT EXISTS `jobs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `queue` VARCHAR(255) NOT NULL DEFAULT 'default',
                `payload` LONGTEXT NOT NULL,
                `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `reserved_at` DATETIME NULL,
                `available_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `jobs_queue_index` (`queue`),
                INDEX `jobs_available_at_index` (`available_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Tabela de jobs falhados
        DB::statement("
            CREATE TABLE IF NOT EXISTS `failed_jobs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `uuid` VARCHAR(36) NULL,
                `queue` VARCHAR(255) NOT NULL,
                `payload` LONGTEXT NOT NULL,
                `exception` LONGTEXT NOT NULL,
                `failed_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
                INDEX `failed_jobs_queue_index` (`queue`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Tabela de notificações
        DB::statement("
            CREATE TABLE IF NOT EXISTS `notifications` (
                `id` CHAR(36) NOT NULL,
                `type` VARCHAR(255) NOT NULL,
                `notifiable_type` VARCHAR(255) NOT NULL,
                `notifiable_id` BIGINT UNSIGNED NOT NULL,
                `data` JSON NOT NULL,
                `read_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `notifications_notifiable_index` (`notifiable_type`, `notifiable_id`),
                INDEX `notifications_read_at_index` (`read_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down() {
        DB::statement("DROP TABLE IF EXISTS `notifications`");
        DB::statement("DROP TABLE IF EXISTS `failed_jobs`");
        DB::statement("DROP TABLE IF EXISTS `jobs`");
    }
};
