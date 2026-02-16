-- Migration: Sistema de CRUDs
-- Criado: 2026-02-14
-- Descrição: Tabela para gerenciar configurações de CRUDs gerados automaticamente

CREATE TABLE IF NOT EXISTS `tbl_cruds` (
  `id` VARCHAR(36) PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL COMMENT 'Nome humanizado (ex: Banner Hero)',
  `table_name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nome da tabela (ex: tbl_banner_hero)',
  `controller_name` VARCHAR(100) NOT NULL COMMENT 'Nome do controller (ex: BannerHeroController)',
  `route` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Rota base (ex: banner-hero)',

  -- Configuração de campos (JSON)
  `fields` JSON NOT NULL COMMENT 'Array de campos: [{name, type, required, max_length, mime_types, fk_table, fk_column}]',

  -- Features opcionais
  `has_ordering` TINYINT(1) DEFAULT 0 COMMENT 'Tem campo order para ordenação manual',
  `has_status` TINYINT(1) DEFAULT 1 COMMENT 'Tem campo ativo (status)',
  `has_slug` TINYINT(1) DEFAULT 0 COMMENT 'Gera slug automaticamente',
  `slug_source` VARCHAR(50) DEFAULT NULL COMMENT 'Campo base para slug (ex: titulo)',
  `has_frontend` TINYINT(1) DEFAULT 0 COMMENT 'Tem display frontend',
  `frontend_format` ENUM('grid', 'carousel', 'list', 'table') DEFAULT 'grid' COMMENT 'Formato de exibição frontend',
  `has_upload` TINYINT(1) DEFAULT 0 COMMENT 'Tem upload de arquivos',

  -- Configuração de upload (JSON)
  `upload_config` JSON DEFAULT NULL COMMENT 'Config upload: {max_size, allowed_mimes, optimize_images}',

  -- Configuração de relacionamentos (JSON)
  `relationships` JSON DEFAULT NULL COMMENT 'Array de FKs: [{field, table, column, display_field}]',

  -- Status do CRUD gerado
  `status` ENUM('draft', 'generated', 'active', 'inactive') DEFAULT 'draft' COMMENT 'Status: draft=criando, generated=arquivos gerados, active=funcionando',
  `generated_at` TIMESTAMP NULL COMMENT 'Quando foi gerado',

  -- Arquivos gerados (para controle)
  `generated_files` JSON DEFAULT NULL COMMENT 'Lista de arquivos criados',

  -- Timestamps
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Índices
  INDEX `idx_status` (`status`),
  INDEX `idx_table` (`table_name`),
  INDEX `idx_route` (`route`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
