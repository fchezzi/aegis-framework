-- =========================================
-- Migração: Adicionar tabela module_permissions
-- Data: 2025-11-24
-- Descrição: Permite controle de permissões de módulos por grupo
-- =========================================

-- MySQL
CREATE TABLE IF NOT EXISTS module_permissions (
    group_id VARCHAR(36) NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, module_name),
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    INDEX idx_group_id (group_id),
    INDEX idx_module_name (module_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Para Supabase, execute:
-- CREATE TABLE IF NOT EXISTS module_permissions (
--     id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
--     group_id UUID NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
--     module_name VARCHAR(100) NOT NULL,
--     created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
--     UNIQUE(group_id, module_name)
-- );
-- CREATE INDEX IF NOT EXISTS idx_module_perms_group ON module_permissions(group_id);
-- CREATE INDEX IF NOT EXISTS idx_module_perms_module ON module_permissions(module_name);
-- ALTER TABLE module_permissions ENABLE ROW LEVEL SECURITY;
-- DROP POLICY IF EXISTS "Allow all operations on module_permissions" ON module_permissions;
-- CREATE POLICY "Allow all operations on module_permissions" ON module_permissions FOR ALL USING (true) WITH CHECK (true);
