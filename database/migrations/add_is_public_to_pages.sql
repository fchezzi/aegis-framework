-- Adicionar campo is_public à tabela pages
-- Este campo controla se a página de módulo é pública (acessível sem login)
-- 0 = privado (exige login e permissão)
-- 1 = público (acessível sem login)

ALTER TABLE pages
ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER is_module_page;

-- Criar índice para melhorar performance
CREATE INDEX idx_pages_is_public ON pages(is_public);

-- IMPORTANTE: Para sistemas SEM members (ENABLE_MEMBERS = false)
-- Tornar todas as páginas existentes públicas automaticamente
-- Descomente a linha abaixo se você NÃO usa sistema de members:
-- UPDATE pages SET is_public = 1 WHERE 1=1;
