-- =====================================================
-- AEGIS Framework - Migração de Banco de Dados
-- Tabela: tbl_website
-- Descrição: Armazena número de visitantes por website/canal por data
-- =====================================================

-- Criar tabela tbl_website
CREATE TABLE IF NOT EXISTS `tbl_website` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `data` DATE NOT NULL COMMENT 'Data da métrica',
  `website_id` INT(11) NOT NULL COMMENT 'ID do canal/website (FK para canais.id)',
  `visitantes` INT(11) NOT NULL DEFAULT 0 COMMENT 'Número de visitantes no dia',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação do registro',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização do registro',

  PRIMARY KEY (`id`),

  -- Índice único: um registro por data + website
  UNIQUE KEY `idx_data_website` (`data`, `website_id`),

  -- Índice para buscar por website
  KEY `idx_website_id` (`website_id`),

  -- Índice para buscar por data
  KEY `idx_data` (`data`),

  -- Foreign Key: relacionamento com tabela canais
  CONSTRAINT `fk_website_canal`
    FOREIGN KEY (`website_id`)
    REFERENCES `canais` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Métricas de visitantes por website/canal';

-- =====================================================
-- Comentários sobre a estrutura
-- =====================================================

-- id: Chave primária auto incremento
-- data: Data da métrica (formato YYYY-MM-DD)
-- website_id: FK para canais.id (identifica qual canal/website)
-- visitantes: Contador de visitantes do dia
-- created_at: Timestamp de criação automático
-- updated_at: Timestamp de atualização automático

-- UNIQUE KEY (data, website_id): Garante que só existe um registro por data + website
-- ON DELETE CASCADE: Se o canal for deletado, deleta também os registros de website
-- ON UPDATE CASCADE: Se o ID do canal mudar, atualiza automaticamente aqui

-- =====================================================
-- Exemplo de uso
-- =====================================================

-- Inserir dados de exemplo (descomente para testar):
-- INSERT INTO tbl_website (data, website_id, visitantes) VALUES
-- ('2024-01-15', 1, 15000),
-- ('2024-01-16', 1, 16500),
-- ('2024-01-15', 2, 8000);

-- Consultar visitantes de um canal específico:
-- SELECT * FROM tbl_website WHERE website_id = 1 ORDER BY data DESC;

-- Consultar total de visitantes por canal:
-- SELECT c.nome, SUM(w.visitantes) as total_visitantes
-- FROM tbl_website w
-- INNER JOIN canais c ON w.website_id = c.id
-- GROUP BY c.id, c.nome;
