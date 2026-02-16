-- Migration: Adicionar campo 'context' para separar páginas public/members/admin
-- Data: 2026-02-05
-- Descrição: Permite carregar CSS específico para cada contexto (frontend.css, members.css, admin.css)
--            Preparação para futura separação física de pastas (Fase 2)

-- ============================================
-- MySQL
-- ============================================

-- Adicionar coluna context
ALTER TABLE pages
ADD COLUMN context VARCHAR(20) NOT NULL DEFAULT 'public'
COMMENT 'Contexto da página: public, members ou admin'
AFTER slug;

-- Criar índice para melhorar performance
CREATE INDEX idx_pages_context ON pages(context);

-- Marcar páginas de dashboard como 'members'
UPDATE pages SET context = 'members'
WHERE slug IN (
    'dashboard',
    'profile',
    'cards',
    'charts',
    'filtros',
    'tabelas'
);

-- Marcar páginas públicas existentes (garantir que fiquem 'public')
-- UPDATE pages SET context = 'public' WHERE slug IN ('home', 'sobre', 'contato');

-- ============================================
-- Supabase (PostgreSQL)
-- ============================================
-- ALTER TABLE pages
-- ADD COLUMN context VARCHAR(20) NOT NULL DEFAULT 'public'
-- CHECK (context IN ('public', 'members', 'admin'));
--
-- CREATE INDEX idx_pages_context ON pages(context);
--
-- UPDATE pages SET context = 'members'
-- WHERE slug IN ('dashboard', 'profile', 'cards', 'charts', 'filtros', 'tabelas');

-- ============================================
-- ROLLBACK (se necessário)
-- ============================================
-- DROP INDEX idx_pages_context ON pages;
-- ALTER TABLE pages DROP COLUMN context;
