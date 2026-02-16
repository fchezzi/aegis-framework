-- Migration: Produtos
-- Gerado automaticamente em: 2026-02-14 14:25:22

CREATE TABLE IF NOT EXISTS `tbl_produtos` (
  `id` VARCHAR(36) PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  `preco` DECIMAL(10,2) NOT NULL,
  `imagem` VARCHAR(500) DEFAULT NULL,
  `order` INT DEFAULT 0,
  `ativo` TINYINT(1) DEFAULT 1,
  `slug` VARCHAR(255) UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_slug` (`slug`),
  INDEX `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;