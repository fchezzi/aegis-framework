-- Criar tabela para fontes de dados customizáveis
CREATE TABLE IF NOT EXISTS report_data_sources (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    table_name VARCHAR(100) NOT NULL,
    operation VARCHAR(20) NOT NULL, -- SUM, COUNT, AVG, MIN, MAX
    column_name VARCHAR(100) NOT NULL,
    conditions JSON, -- Array de condições WHERE
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices
CREATE INDEX idx_data_sources_name ON report_data_sources(name);
