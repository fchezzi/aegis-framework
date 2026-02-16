<?php
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
$pdo = new PDO("mysql:unix_socket=$socket;dbname=aegis", 'root', 'root');

$stmt = $pdo->query("SELECT opportunities_full FROM tbl_pagespeed_reports ORDER BY analyzed_at DESC LIMIT 1");
$report = $stmt->fetch(PDO::FETCH_ASSOC);

$opportunities = json_decode($report['opportunities_full'], true);

echo "=== RENDER-BLOCKING DETALHADO ===\n\n";

foreach ($opportunities as $opp) {
    if (strpos($opp['audit_id'], 'render') !== false || strpos($opp['audit_id'], 'blocking') !== false) {
        echo "Audit ID: " . $opp['audit_id'] . "\n";
        echo "Score: " . $opp['score'] . "\n";
        echo "Title: " . $opp['title'] . "\n";
        echo "Description: " . ($opp['description'] ?? 'N/A') . "\n";
        echo "Display Value: " . ($opp['display_value'] ?? 'N/A') . "\n";
        echo "Savings MS: " . ($opp['savings_ms'] ?? 0) . "ms\n";
        echo "\n";

        if (!empty($opp['items'])) {
            echo "ITEMS BLOQUEANTES (" . count($opp['items']) . "):\n";
            foreach ($opp['items'] as $idx => $item) {
                echo ($idx + 1) . ". URL: " . ($item['url'] ?? 'N/A') . "\n";
                echo "   Total Bytes: " . ($item['total_bytes'] ?? 'N/A') . "\n";
                echo "   Wasted Bytes: " . ($item['wasted_bytes'] ?? 'N/A') . "\n";
                echo "   Wasted MS: " . ($item['wasted_ms'] ?? 'N/A') . "\n";
                echo "\n";
            }
        } else {
            echo "Nenhum item específico encontrado.\n";
        }
    }
}