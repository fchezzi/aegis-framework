<?php
/**
 * Script que analisa MOBILE + DESKTOP de uma vez
 * Salva DOIS registros mas relacionados
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

// Configurações
$settings = Settings::all();
$api_key = $settings['pagespeed_api_key'];
$webhook_secret = $settings['pagespeed_webhook_secret'];

// URL para testar
$test_url = 'https://drywash.com.br';

echo "🚀 Análise COMPLETA PageSpeed (Mobile + Desktop)\n";
echo "🌐 URL: $test_url\n";
echo "⏰ Horário: " . date('H:i:s') . "\n\n";

// ID compartilhado para relacionar mobile e desktop
$group_id = uniqid('group_');

include __DIR__ . '/../../storage/n8n/pagespeed-transform-FULL.php';

// Analisar AMBOS
foreach (['mobile', 'desktop'] as $strategy) {
    echo "📱 Analisando $strategy...\n";

    // Chamar Google API
    $api_url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed";
    $api_url .= "?url=" . urlencode($test_url);
    $api_url .= "&strategy=" . $strategy;
    $api_url .= "&key=" . $api_key;

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code !== 200) {
        echo "❌ Erro na API para $strategy\n";
        continue;
    }

    // Transformar dados
    $api_data = json_decode($response, true);
    $transformed = transformPageSpeedData($api_data);
    $transformed['webhook_secret'] = $webhook_secret;
    $transformed['strategy'] = $strategy;
    $transformed['group_id'] = $group_id; // IMPORTANTE: relacionar mobile e desktop

    echo "  Score: " . $transformed['performance_score'] . "\n";

    // Salvar no banco
    $save_url = 'http://127.0.0.1:5757/aegis/admin/api/pagespeed-save.php';
    $ch = curl_init($save_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transformed));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $save_response = curl_exec($ch);
    $save_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($save_code === 200) {
        $save_data = json_decode($save_response, true);
        if ($save_data['success']) {
            echo "  ✅ Salvo! ID: " . $save_data['report_id'] . "\n";

            // Guardar ID para mostrar no final
            if ($strategy === 'mobile') {
                $mobile_id = $save_data['report_id'];
            }
        }
    }

    echo "\n";
}

echo "🎯 ANÁLISE COMPLETA!\n\n";
echo "📊 Ver relatório em:\n";
echo "http://localhost:5757/aegis/admin/pagespeed/report/" . ($mobile_id ?? $group_id) . "\n";