<?php
/**
 * Script de teste PageSpeed com MEDIANA de 3 análises
 * Mais estável e confiável que uma única análise
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

// Configurações
$settings = Settings::all();
$api_key = $settings['pagespeed_api_key'];
$webhook_secret = $settings['pagespeed_webhook_secret'];

// URL de teste
$test_url = 'https://drywash.com.br';

// Número de testes para cada estratégia
$num_tests = 3;

echo "🚀 Iniciando análise PageSpeed com MEDIANA\n";
echo "🌐 URL: $test_url\n";
echo "🔢 Número de testes: $num_tests por estratégia\n";
echo "⏰ Horário: " . date('H:i:s') . "\n\n";

// Incluir arquivo de transformação
include __DIR__ . '/../../storage/n8n/pagespeed-transform-FULL.php';

/**
 * Calcula a mediana de um array
 */
function calcularMediana($arr) {
    sort($arr);
    $count = count($arr);
    $middle = floor(($count - 1) / 2);

    if ($count % 2) {
        return $arr[$middle];
    } else {
        return ($arr[$middle] + $arr[$middle + 1]) / 2;
    }
}

/**
 * Executa múltiplas análises e retorna a mediana
 */
function analisarComMediana($test_url, $strategy, $api_key, $num_tests) {
    $resultados = [];

    echo "📊 $strategy: Executando $num_tests testes...\n";

    for ($i = 1; $i <= $num_tests; $i++) {
        echo "  Teste $i/$num_tests... ";

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

        if ($http_code === 200) {
            $data = json_decode($response, true);
            $resultados[] = $data;
            $score = $data['lighthouseResult']['categories']['performance']['score'] ?? 0;
            echo "Score: " . round($score * 100) . "\n";
        } else {
            echo "❌ Erro HTTP $http_code\n";
        }

        // Esperar 2 segundos entre testes para não sobrecarregar
        if ($i < $num_tests) {
            sleep(2);
        }
    }

    if (empty($resultados)) {
        return null;
    }

    // Calcular medianas de todas as métricas
    $scores_performance = array_map(function($r) {
        return ($r['lighthouseResult']['categories']['performance']['score'] ?? 0) * 100;
    }, $resultados);

    $scores_lcp = array_map(function($r) {
        return $r['lighthouseResult']['audits']['largest-contentful-paint']['numericValue'] ?? 0;
    }, $resultados);

    $scores_fcp = array_map(function($r) {
        return $r['lighthouseResult']['audits']['first-contentful-paint']['numericValue'] ?? 0;
    }, $resultados);

    $scores_cls = array_map(function($r) {
        return $r['lighthouseResult']['audits']['cumulative-layout-shift']['numericValue'] ?? 0;
    }, $resultados);

    $scores_inp = array_map(function($r) {
        return $r['lighthouseResult']['audits']['interaction-to-next-paint']['numericValue'] ?? 0;
    }, $resultados);

    // Usar o resultado do meio (mediana) como base
    $resultado_mediana = $resultados[floor(count($resultados) / 2)];

    // Substituir scores pela mediana calculada
    $resultado_mediana['lighthouseResult']['categories']['performance']['score'] = calcularMediana($scores_performance) / 100;

    // Adicionar informações de variação
    $resultado_mediana['_mediana_info'] = [
        'num_tests' => $num_tests,
        'performance_scores' => $scores_performance,
        'performance_min' => min($scores_performance),
        'performance_max' => max($scores_performance),
        'performance_median' => calcularMediana($scores_performance),
        'lcp_median' => round(calcularMediana($scores_lcp)),
        'fcp_median' => round(calcularMediana($scores_fcp)),
        'cls_median' => round(calcularMediana($scores_cls), 3),
        'inp_median' => round(calcularMediana($scores_inp))
    ];

    echo "  📊 Mediana do Score: " . round(calcularMediana($scores_performance)) . "\n";
    echo "  📉 Min: " . round(min($scores_performance)) . " | Max: " . round(max($scores_performance)) . "\n\n";

    return $resultado_mediana;
}

// IDs para retornar
$mobile_id = null;
$desktop_id = null;

// Analisar MOBILE e DESKTOP
foreach (['mobile', 'desktop'] as $strategy) {
    echo "=" . str_repeat("=", 50) . "\n";
    echo strtoupper($strategy) . " ANALYSIS\n";
    echo "=" . str_repeat("=", 50) . "\n\n";

    $resultado = analisarComMediana($test_url, $strategy, $api_key, $num_tests);

    if ($resultado) {
        // Transformar dados usando o script existente
        $transformed = transformPageSpeedData($resultado, $strategy, $test_url);

        // Adicionar informações de mediana
        $transformed['num_tests'] = $num_tests;
        $transformed['performance_min'] = $resultado['_mediana_info']['performance_min'];
        $transformed['performance_max'] = $resultado['_mediana_info']['performance_max'];
        $transformed['performance_median'] = $resultado['_mediana_info']['performance_median'];

        // Adicionar webhook secret
        $transformed['webhook_secret'] = $webhook_secret;

        // Enviar para endpoint de salvamento
        $ch = curl_init('http://localhost:5757/aegis/admin/api/pagespeed-save.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transformed));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $save_response = curl_exec($ch);

        $save_data = json_decode($save_response, true);
        if ($save_data['success']) {
            echo "✅ Salvo! ID: " . $save_data['report_id'] . "\n\n";
            if ($strategy === 'mobile') {
                $mobile_id = $save_data['report_id'];
            } else {
                $desktop_id = $save_data['report_id'];
            }
        } else {
            echo "❌ Erro ao salvar: " . ($save_data['error'] ?? 'Unknown') . "\n\n";
        }
    }
}

echo "=" . str_repeat("=", 50) . "\n";
echo "🎯 ANÁLISE COMPLETA COM MEDIANA!\n";
echo "=" . str_repeat("=", 50) . "\n\n";

if ($mobile_id) {
    echo "📱 Mobile Report ID: $mobile_id\n";
    echo "📊 Ver relatório em:\n";
    echo "http://localhost:5757/aegis/admin/pagespeed/report/$mobile_id\n\n";
}

if ($desktop_id) {
    echo "🖥️ Desktop Report ID: $desktop_id\n";
    echo "📊 Ver relatório em:\n";
    echo "http://localhost:5757/aegis/admin/pagespeed/report/$desktop_id\n";
}