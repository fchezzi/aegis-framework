-- ================================================
-- ROLLBACK: Remover tabela youtube_extra
-- Data: 2025-12-12
-- ================================================

-- ATENÇÃO: Este rollback irá DELETAR todos os dados da tabela youtube_extra!
-- Certifique-se de fazer backup antes de executar.

-- MySQL / Supabase (compatível com ambos)
DROP TABLE IF EXISTS youtube_extra;

-- Supabase: Remover trigger e function
DROP TRIGGER IF EXISTS trg_youtube_extra_updated_at ON youtube_extra;
DROP FUNCTION IF EXISTS update_youtube_extra_updated_at();

-- Verificar se rollback foi aplicado
SELECT 'Rollback youtube_extra executado. Tabela removida.' as status;
