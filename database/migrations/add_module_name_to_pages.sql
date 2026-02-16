-- Adicionar campo module_name à tabela pages
-- Este campo identifica páginas que pertencem a módulos (vs páginas criadas manualmente)
-- Se module_name IS NULL = página manual (criada via admin)
-- Se module_name IS NOT NULL = página de módulo (criada automaticamente)

ALTER TABLE pages
ADD COLUMN module_name VARCHAR(100) NULL AFTER slug;

-- Criar índice para melhorar performance nas queries
CREATE INDEX idx_pages_module_name ON pages(module_name);

-- Atualizar páginas existentes do módulo palpites (se existirem)
UPDATE pages
SET module_name = 'palpites'
WHERE slug LIKE 'palpites/%';
