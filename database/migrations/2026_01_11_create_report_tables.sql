-- Migration: Sistema de Relatórios com Templates Excel
-- Data: 2026-01-11

-- Tabela de templates de relatórios
CREATE TABLE IF NOT EXISTS report_templates (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de mapeamento célula -> fonte de dados
CREATE TABLE IF NOT EXISTS report_cells (
    id VARCHAR(36) PRIMARY KEY,
    template_id VARCHAR(36) NOT NULL,
    cell_ref VARCHAR(20) NOT NULL COMMENT 'Ex: B5, D12, AA100',
    data_source_key VARCHAR(100) NOT NULL COMMENT 'Chave da fonte em ReportDataSources',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE CASCADE
);

-- Índices para performance
CREATE INDEX idx_report_cells_template ON report_cells(template_id);
CREATE INDEX idx_report_templates_visible ON report_templates(visible);
