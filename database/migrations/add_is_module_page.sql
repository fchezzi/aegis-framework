-- Adicionar campo is_module_page à tabela pages
-- Este campo identifica páginas que pertencem a módulos (vs páginas criadas manualmente)
-- 0 = página manual (criada via /admin/pages/create)
-- 1 = página de módulo (criada automaticamente pelo ModuleManager)

ALTER TABLE pages
ADD COLUMN is_module_page TINYINT(1) NOT NULL DEFAULT 0 AFTER slug;

-- Criar índice para melhorar performance nas queries
CREATE INDEX idx_pages_is_module ON pages(is_module_page);

-- Marcar páginas existentes que são de módulos (se houver)
-- Exemplo: se já existe página com slug do módulo palpites
UPDATE pages
SET is_module_page = 1
WHERE slug = 'palpites/exibicao-palpites';
