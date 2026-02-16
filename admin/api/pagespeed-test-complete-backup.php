<?php
/**
 * Script de teste completo do PageSpeed
 * Simula o que o n8n faria
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

// 1. Buscar configurações
$settings = Settings::all();
$api_key = $settings['pagespeed_api_key'];
$webhook_secret = $settings['pagespeed_webhook_secret'];

// 2. URL de teste
$test_url = 'https://drywash.com.br';

echo "🚀 Iniciando teste completo PageSpeed (MOBILE + DESKTOP)\n";
echo "🌐 URL: $test_url\n";
echo "⏰ Horário: " . date('H:i:s') . "\n\n";

// 3. Incluir arquivo de transformação
include __DIR__ . '/../../storage/n8n/pagespeed-transform-FULL.php';

// 4. IDs para retornar
$mobile_id = null;
$desktop_id = null;

// 5. Analisar MOBILE e DESKTOP
foreach (['mobile', 'desktop'] as $strategy) {
    echo "📱 Analisando $strategy...\n";

    // Chamar Google PageSpeed API
    $api_url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed";
    $api_url .= "?url=" . urlencode($test_url);
    $api_url .= "&strategy=" . $strategy;
    $api_url .= "&category=performance";
    $api_url .= "&category=accessibility";
    $api_url .= "&category=best-practices";
    $api_url .= "&category=seo";
    $api_url .= "&key=" . $api_key;

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        echo "  ❌ Erro na API: HTTP $http_code\n\n";
        continue;
    }

    echo "  ✅ API respondeu!\n";

    // Transformar dados
    $api_data = json_decode($response, true);
    $transformed = transformPageSpeedData($api_data);
    $transformed['webhook_secret'] = $webhook_secret;
    $transformed['strategy'] = $strategy;
    $transformed['url'] = $test_url;  // Garantir URL correta

    echo "  📊 Score: " . $transformed['performance_score'] . "\n";

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
            echo "  ✅ Salvo! ID: " . $save_data['report_id'] . "\n\n";

            // Guardar IDs
            if ($strategy === 'mobile') {
                $mobile_id = $save_data['report_id'];
            } else {
                $desktop_id = $save_data['report_id'];
            }
        }
    }
}

echo "🎯 ANÁLISE COMPLETA!\n\n";
if ($mobile_id) {
    echo "📊 Ver relatório em:\n";
    echo "http://localhost:5757/aegis/admin/pagespeed/report/" . $mobile_id . "\n";
}