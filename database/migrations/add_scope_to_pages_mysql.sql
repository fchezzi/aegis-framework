-- ================================================
-- Migration: Add scope column to pages table (MySQL)
-- Data: 2026-02-06
-- Descrição: Adiciona coluna scope para diferenciar páginas admin/frontend
-- ================================================

-- Adicionar coluna scope
ALTER TABLE pages
ADD COLUMN scope ENUM('admin', 'members', 'frontend') NOT NULL DEFAULT 'frontend'
AFTER type;

-- Criar índice para performance
CREATE INDEX idx_pages_scope ON pages(scope);

-- ================================================
-- Atualizar páginas existentes (opcional)
-- ================================================
-- Todas as páginas criadas até agora são frontend
-- Se houver páginas de admin no futuro, atualizar manualmente
