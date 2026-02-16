-- Migration: Adicionar suporte a componentes no Page Builder
-- Data: 2025-12-04
-- Versão: 9.0.0 → 9.1.0

-- Adicionar campos de componente à tabela page_cards
ALTER TABLE page_cards
ADD COLUMN component_type VARCHAR(50) NULL AFTER content,
ADD COLUMN component_data JSON NULL AFTER component_type;

-- Adicionar índice para melhorar performance em queries por tipo
CREATE INDEX idx_component_type ON page_cards(component_type);

-- Comentários para documentação
-- component_type: NULL = card HTML legacy | 'hero' | 'tabelas' | etc
-- component_data: JSON com configuração específica do componente
