-- ========================================
-- Tabela de Migrations de Módulos
-- ========================================
-- Registra instalações e versões de módulos
-- ========================================

CREATE TABLE IF NOT EXISTS module_migrations (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  module_name VARCHAR(100) NOT NULL UNIQUE,
  version INTEGER NOT NULL DEFAULT 1,
  installed_at TIMESTAMP DEFAULT now(),
  updated_at TIMESTAMP DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_module_migrations_name ON module_migrations(module_name);

COMMENT ON TABLE module_migrations IS 'Controle de versão de módulos instalados';
COMMENT ON COLUMN module_migrations.version IS 'Número da última migration executada';
