<?php
require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();
require_once __DIR__ . '/../../core/Database.php';

echo "🔧 Adicionando colunas de scores no banco...\n\n";

try {
    $db = Database::getInstance();

    // Verificar se colunas já existem
    $result = $db->query("SHOW COLUMNS FROM tbl_pagespeed_reports LIKE 'accessibility_score'");

    if (!empty($result)) {
        echo "⚠️  Colunas já existem!\n";
        exit;
    }

    // Adicionar colunas
    $sql = "ALTER TABLE tbl_pagespeed_reports
            ADD COLUMN accessibility_score INT DEFAULT NULL AFTER performance_score,
            ADD COLUMN best_practices_score INT DEFAULT NULL AFTER accessibility_score,
            ADD COLUMN seo_score INT DEFAULT NULL AFTER best_practices_score";

    $db->execute($sql);

    echo "✅ Colunas adicionadas com sucesso!\n";
    echo "   - accessibility_score\n";
    echo "   - best_practices_score\n";
    echo "   - seo_score\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}