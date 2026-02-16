-- ================================================
-- MIGRATION: Adicionar tabela youtube_extra
-- Data: 2025-12-12
-- Descrição: Métricas diárias de canais do YouTube
-- ================================================

-- Criar tabela youtube_extra (métricas de canais)
CREATE TABLE IF NOT EXISTS youtube_extra (
    id VARCHAR(36) PRIMARY KEY,
    canal_id VARCHAR(36) NOT NULL,
    data DATE NOT NULL,
    inscritos INT DEFAULT 0 COMMENT 'Total de inscritos do canal',
    espectadores_unicos INT DEFAULT 0 COMMENT 'Espectadores únicos do dia',

    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Key para tabela canais
    FOREIGN KEY (canal_id) REFERENCES canais(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Índices para performance
    UNIQUE KEY unique_canal_data (canal_id, data),
    INDEX idx_canal_id (canal_id),
    INDEX idx_data (data),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar se migration foi aplicada
SELECT 'Migration add_youtube_extra_table.sql executada com sucesso!' as status;
