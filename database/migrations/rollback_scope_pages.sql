-- ================================================
-- Rollback: Remove scope column from pages table
-- Data: 2026-02-06
-- Descrição: Remove coluna scope (MySQL e Supabase compatível)
-- ================================================

-- Remover índice (MySQL não suporta IF EXISTS em DROP INDEX)
-- Ignora erro se índice não existir
ALTER TABLE pages DROP INDEX idx_pages_scope;

-- Remover coluna
ALTER TABLE pages DROP COLUMN scope;
