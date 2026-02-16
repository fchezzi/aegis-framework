<?php
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
$pdo = new PDO("mysql:unix_socket=$socket;dbname=aegis", 'root', 'root');

$stmt = $pdo->query("SELECT opportunities_full FROM tbl_pagespeed_reports ORDER BY analyzed_at DESC LIMIT 1");
$report = $stmt->fetch(PDO::FETCH_ASSOC);

$opportunities = json_decode($report['opportunities_full'], true);

echo "Verificando render-blocking na última análise...\n\n";

$found = false;
foreach ($opportunities as $opp) {
    if (strpos($opp['audit_id'], 'render') !== false || strpos($opp['audit_id'], 'blocking') !== false) {
        echo "✅ ENCONTRADO: " . $opp['audit_id'] . "\n";
        echo "   Score: " . $opp['score'] . "\n";
        echo "   Title: " . $opp['title'] . "\n";
        if (!empty($opp['items'])) {
            echo "   Items: " . count($opp['items']) . "\n";
        }
        echo "\n";
        $found = true;
    }
}

if (!$found) {
    echo "❌ Nenhum render-blocking encontrado nas oportunidades\n\n";
    echo "Oportunidades capturadas:\n";
    foreach ($opportunities as $opp) {
        echo "  - " . $opp['audit_id'] . " (score: " . $opp['score'] . ")\n";
    }
}