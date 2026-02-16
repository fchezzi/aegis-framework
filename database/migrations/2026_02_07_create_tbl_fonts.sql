-- ================================================
-- Migration: Criar tabela tbl_fonts
-- Data: 2026-02-07
-- Descrição: Sistema de gerenciamento de fontes customizáveis
-- ================================================

CREATE TABLE IF NOT EXISTS tbl_fonts (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    family VARCHAR(100) NOT NULL,
    weight VARCHAR(10) DEFAULT 'normal',
    style VARCHAR(10) DEFAULT 'normal',
    filename VARCHAR(255) NOT NULL UNIQUE,
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) DEFAULT 1,
    INDEX idx_family (family),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
