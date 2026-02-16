-- ================================================
-- MIGRATION: Adicionar tabela youtube_extra (Supabase)
-- Data: 2025-12-12
-- Descrição: Métricas diárias de canais do YouTube
-- ================================================

-- Criar tabela youtube_extra (métricas de canais)
CREATE TABLE IF NOT EXISTS youtube_extra (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    canal_id UUID NOT NULL,
    data DATE NOT NULL,
    inscritos INTEGER DEFAULT 0,
    espectadores_unicos INTEGER DEFAULT 0,

    -- Metadados
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Key para tabela canais
    CONSTRAINT fk_youtube_extra_canal
        FOREIGN KEY (canal_id)
        REFERENCES canais(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Unique constraint (não pode ter duplicata de canal + data)
    CONSTRAINT unique_canal_data UNIQUE (canal_id, data)
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_youtube_extra_canal ON youtube_extra(canal_id);
CREATE INDEX IF NOT EXISTS idx_youtube_extra_data ON youtube_extra(data);
CREATE INDEX IF NOT EXISTS idx_youtube_extra_created ON youtube_extra(created_at);

-- Trigger para updated_at automático
CREATE OR REPLACE FUNCTION update_youtube_extra_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_youtube_extra_updated_at
BEFORE UPDATE ON youtube_extra
FOR EACH ROW
EXECUTE FUNCTION update_youtube_extra_updated_at();

-- Comentários nas colunas
COMMENT ON TABLE youtube_extra IS 'Métricas diárias de canais do YouTube';
COMMENT ON COLUMN youtube_extra.inscritos IS 'Total de inscritos do canal no dia';
COMMENT ON COLUMN youtube_extra.espectadores_unicos IS 'Espectadores únicos do dia';

-- Verificar se migration foi aplicada
SELECT 'Migration add_youtube_extra_table_supabase.sql executada com sucesso!' as status;
