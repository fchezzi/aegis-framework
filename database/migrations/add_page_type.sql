-- Migration: Adicionar campo 'type' para diferenciar páginas core de custom
-- Data: 2024-12-04
-- Descrição: Permite separar páginas padrão do AEGIS (core) de páginas específicas do projeto (custom)

-- MySQL
ALTER TABLE pages
ADD COLUMN type ENUM('core', 'custom') NOT NULL DEFAULT 'custom'
AFTER slug;

-- Marcar páginas core do AEGIS
UPDATE pages SET type = 'core' WHERE slug IN ('dashboard-page', 'blank-page', 'home');

-- Supabase (PostgreSQL)
-- ALTER TABLE pages
-- ADD COLUMN type VARCHAR(10) NOT NULL DEFAULT 'custom'
-- CHECK (type IN ('core', 'custom'));
--
-- UPDATE pages SET type = 'core' WHERE slug IN ('dashboard-page', 'blank-page', 'home');
