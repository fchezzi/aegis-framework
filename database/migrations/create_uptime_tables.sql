-- ================================================
-- UPTIME ROBOT - TABELAS
-- ================================================

-- Monitores cadastrados
CREATE TABLE IF NOT EXISTS uptime_monitors (
    id CHAR(36) PRIMARY KEY,
    monitor_id BIGINT NOT NULL UNIQUE,
    friendly_name VARCHAR(255) NOT NULL,
    url VARCHAR(512) NOT NULL,
    type TINYINT NOT NULL,
    interval_seconds INT NOT NULL,
    status TINYINT NOT NULL COMMENT '0=pause, 2=up, 8=seems down, 9=down',
    average_response_time DECIMAL(10,3) DEFAULT 0,
    create_datetime INT NOT NULL,
    last_sync_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_monitor_id (monitor_id),
    INDEX idx_status (status),
    INDEX idx_url (url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico de tempos de resposta
CREATE TABLE IF NOT EXISTS uptime_response_times (
    id CHAR(36) PRIMARY KEY,
    monitor_id BIGINT NOT NULL,
    datetime INT NOT NULL,
    value INT NOT NULL COMMENT 'Tempo de resposta em ms',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_monitor_id (monitor_id),
    INDEX idx_datetime (datetime),
    UNIQUE KEY unique_monitor_datetime (monitor_id, datetime),
    FOREIGN KEY (monitor_id) REFERENCES uptime_monitors(monitor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico de logs (downtime, alertas)
CREATE TABLE IF NOT EXISTS uptime_logs (
    id CHAR(36) PRIMARY KEY,
    monitor_id BIGINT NOT NULL,
    type TINYINT NOT NULL COMMENT '1=down, 2=up, 98=started, 99=paused',
    datetime INT NOT NULL,
    duration INT DEFAULT 0 COMMENT 'Duração em segundos',
    reason_code INT DEFAULT 0,
    reason_detail TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_monitor_id (monitor_id),
    INDEX idx_type (type),
    INDEX idx_datetime (datetime),
    FOREIGN KEY (monitor_id) REFERENCES uptime_monitors(monitor_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
