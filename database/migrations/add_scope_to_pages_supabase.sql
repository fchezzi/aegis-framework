-- ================================================
-- Migration: Add scope column to pages table (Supabase)
-- Data: 2026-02-06
-- Descrição: Adiciona coluna scope para diferenciar páginas admin/frontend
-- ================================================

-- Adicionar coluna scope
ALTER TABLE pages
ADD COLUMN scope VARCHAR(10) NOT NULL DEFAULT 'frontend'
CHECK (scope IN ('admin', 'members', 'frontend'));

-- Criar índice para performance
CREATE INDEX IF NOT EXISTS idx_pages_scope ON pages(scope);

-- ================================================
-- Atualizar páginas existentes (opcional)
-- ================================================
-- Todas as páginas criadas até agora são frontend
-- Se houver páginas de admin no futuro, atualizar manualmente
