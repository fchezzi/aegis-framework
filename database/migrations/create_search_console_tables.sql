-- ==================================================
-- AEGIS Framework - Google Search Console Tables
-- ==================================================

-- Queries (palavras-chave)
CREATE TABLE IF NOT EXISTS gsc_queries (
    id CHAR(36) PRIMARY KEY,
    query VARCHAR(512) NOT NULL,
    date DATE NOT NULL,
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_query (query(191)),
    INDEX idx_position (position),
    INDEX idx_clicks (clicks DESC),
    UNIQUE KEY unique_query_date (query(191), date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pages (URLs do site)
CREATE TABLE IF NOT EXISTS gsc_pages (
    id CHAR(36) PRIMARY KEY,
    page_url VARCHAR(512) NOT NULL,
    date DATE NOT NULL,
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_url (page_url(191)),
    INDEX idx_position (position),
    INDEX idx_clicks (clicks DESC),
    UNIQUE KEY unique_page_date (page_url(191), date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Core Web Vitals
CREATE TABLE IF NOT EXISTS gsc_vitals (
    id CHAR(36) PRIMARY KEY,
    page_url VARCHAR(512) NOT NULL,
    metric_type ENUM('LCP', 'FID', 'CLS', 'INP') NOT NULL,
    good_percent DECIMAL(5,2) DEFAULT 0,
    needs_improvement_percent DECIMAL(5,2) DEFAULT 0,
    poor_percent DECIMAL(5,2) DEFAULT 0,
    device ENUM('DESKTOP', 'MOBILE') NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_url (page_url(191)),
    INDEX idx_metric (metric_type),
    INDEX idx_device (device),
    UNIQUE KEY unique_vital (page_url(191), metric_type, device, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Errors (404s, erros de crawl, etc)
CREATE TABLE IF NOT EXISTS gsc_errors (
    id CHAR(36) PRIMARY KEY,
    page_url VARCHAR(512) NOT NULL,
    error_type VARCHAR(100) NOT NULL COMMENT 'notFound, serverError, redirectError, etc',
    severity ENUM('ERROR', 'WARNING') NOT NULL,
    detected_at DATE NOT NULL,
    resolved BOOLEAN DEFAULT 0,
    resolved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_detected (detected_at),
    INDEX idx_resolved (resolved),
    INDEX idx_url (page_url(191)),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
