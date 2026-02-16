-- Migration: BigBanner
-- Gerado automaticamente em: 2026-02-14 19:14:57

CREATE TABLE IF NOT EXISTS `tbl_bigbanner` (
  `id` VARCHAR(36) PRIMARY KEY,
  `iamge` VARCHAR(500) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `cta` VARCHAR(255) DEFAULT NULL,
  `cta_link` VARCHAR(255) DEFAULT NULL,
  `order` INT DEFAULT 0,
  `ativo` TINYINT(1) DEFAULT 1,
  `slug` VARCHAR(255) UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_slug` (`slug`),
  INDEX `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;