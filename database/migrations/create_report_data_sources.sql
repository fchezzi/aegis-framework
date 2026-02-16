-- Migration: Sistema de Data Sources para Reports
-- Criado: 2026-02-16
-- Descrição: Tabela para gerenciar fontes de dados customizáveis de relatórios

CREATE TABLE IF NOT EXISTS `report_data_sources` (
  `id` VARCHAR(36) PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL COMMENT 'Nome da fonte de dados',
  `description` TEXT DEFAULT NULL COMMENT 'Descrição opcional',
  `table_name` VARCHAR(100) NOT NULL COMMENT 'Nome da tabela (ex: tbl_users)',
  `operation` VARCHAR(20) NOT NULL COMMENT 'Operação: COUNT, SUM, AVG, MIN, MAX',
  `column_name` VARCHAR(100) DEFAULT NULL COMMENT 'Nome da coluna para operação',
  `conditions` JSON DEFAULT NULL COMMENT 'Array de condições WHERE: [{column, operator, value}]',

  -- Timestamps
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Índices
  INDEX `idx_table` (`table_name`),
  INDEX `idx_operation` (`operation`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
