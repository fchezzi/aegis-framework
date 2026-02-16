<?php
require_once __DIR__ . '/../_config.php';

$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
$pdo = new PDO("mysql:unix_socket=$socket;dbname=aegis", 'root', 'root');

// Pegar última análise mobile
$stmt = $pdo->query("SELECT id, opportunities_full FROM tbl_pagespeed_reports WHERE strategy = 'mobile' ORDER BY analyzed_at DESC LIMIT 1");
$report = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Análise ID: " . $report['id'] . "\n\n";

$opportunities = json_decode($report['opportunities_full'], true);

echo "Total de oportunidades: " . count($opportunities) . "\n\n";

// Procurar por render-blocking
$found = false;
foreach ($opportunities as $opp) {
    if ($opp['audit_id'] === 'render-blocking-resources') {
        $found = true;
        echo "✅ RENDER-BLOCKING ENCONTRADO!\n";
        echo "Score: " . $opp['score'] . "\n";
        echo "Title: " . $opp['title'] . "\n";
        echo "Savings: " . $opp['savings_ms'] . "ms\n";
        echo "Items: " . count($opp['items']) . "\n\n";

        foreach ($opp['items'] as $item) {
            echo "  - " . ($item['url'] ?? 'URL não disponível') . "\n";
        }
    }
}

if (!$found) {
    echo "❌ RENDER-BLOCKING NÃO ENCONTRADO nas oportunidades\n\n";
    echo "Oportunidades capturadas:\n";
    foreach ($opportunities as $opp) {
        echo "  - " . $opp['audit_id'] . " (score: " . $opp['score'] . ")\n";
    }
}

// Testar API diretamente
echo "\n\nTestando API diretamente para drywash...\n";
include __DIR__ . '/../storage/n8n/pagespeed-transform-FULL.php';

// Settings
require_once __DIR__ . '/../core/Settings.php';
$settings = Settings::all();
$api_key = $settings['pagespeed_api_key'];

$api_url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed";
$api_url .= "?url=" . urlencode('https://drywash.com.br');
$api_url .= "&strategy=mobile";
$api_url .= "&category=performance";
$api_url .= "&key=" . $api_key;

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$response = curl_exec($ch);

if ($response) {
    $data = json_decode($response, true);
    $audits = $data['lighthouseResult']['audits'] ?? [];

    if (isset($audits['render-blocking-resources'])) {
        echo "\n✅ RENDER-BLOCKING NA API:\n";
        echo "Score: " . $audits['render-blocking-resources']['score'] . "\n";
        echo "Display: " . ($audits['render-blocking-resources']['displayValue'] ?? 'N/A') . "\n";

        if ($audits['render-blocking-resources']['score'] >= 0.9) {
            echo "⚠️ Score >= 0.9, por isso não aparece nas oportunidades!\n";
        }
    } else {
        echo "❌ render-blocking-resources não encontrado na API\n";
    }
}