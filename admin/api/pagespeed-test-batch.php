<?php
/**
 * Script de análise PageSpeed em LOTE
 * Processa todas as URLs ativas do banco
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

// Configurações
$settings = Settings::all();
$api_key = $settings['pagespeed_api_key'];
$webhook_secret = $settings['pagespeed_webhook_secret'];

// Buscar estratégias configuradas
$strategies = [];
if (!empty($settings['pagespeed_strategy_mobile'])) {
    $strategies[] = 'mobile';
}
if (!empty($settings['pagespeed_strategy_desktop'])) {
    $strategies[] = 'desktop';
}

if (empty($strategies)) {
    die("❌ Nenhuma estratégia configurada\n");
}

// Buscar URLs do banco
$db = DB::connect();
$urls = $db->query("SELECT url FROM tbl_pagespeed_urls WHERE ativo = 1 ORDER BY created_at ASC");

if (empty($urls)) {
    die("❌ Nenhuma URL ativa cadastrada\n");
}

$num_tests = 3; // 3 testes por estratégia para calcular mediana

echo "🚀 ANÁLISE EM LOTE - PageSpeed Insights\n";
echo "📊 URLs: " . count($urls) . "\n";
echo "📱 Estratégias: " . implode(', ', $strategies) . "\n";
echo "🔢 Testes por análise: $num_tests\n";
echo "⏰ Início: " . date('H:i:s') . "\n\n";

// Incluir funções de transformação
include __DIR__ . '/../../storage/n8n/pagespeed-transform-FULL.php';

/**
 * Calcula a mediana de um array
 */
function calcularMediana($arr) {
    if (empty($arr)) return 0;
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

    for ($i = 1; $i <= $num_tests; $i++) {
        echo "    Teste $i/$num_tests... ";

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

        // Esperar 2 segundos entre testes
        if ($i < $num_tests) {
            sleep(2);
        }
    }

    if (empty($resultados)) {
        return null;
    }

    // Calcular medianas
    $scores_performance = array_map(function($r) {
        return ($r['lighthouseResult']['categories']['performance']['score'] ?? 0) * 100;
    }, $resultados);

    // Usar resultado do meio
    sort($resultados, SORT_REGULAR);
    $resultado_mediana = $resultados[floor(count($resultados) / 2)];

    // Substituir score pela mediana
    $resultado_mediana['lighthouseResult']['categories']['performance']['score'] = calcularMediana($scores_performance) / 100;

    // Adicionar info de variação
    $resultado_mediana['_mediana_info'] = [
        'num_tests' => $num_tests,
        'performance_scores' => $scores_performance,
        'performance_min' => min($scores_performance),
        'performance_max' => max($scores_performance),
        'performance_median' => calcularMediana($scores_performance)
    ];

    echo "    📊 Mediana: " . round(calcularMediana($scores_performance)) . "\n";
    echo "    📉 Variação: " . round(min($scores_performance)) . "-" . round(max($scores_performance)) . "\n";

    return $resultado_mediana;
}

// Processar cada URL
foreach ($urls as $urlData) {
    $test_url = $urlData['url'];

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🌐 URL: $test_url\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    foreach ($strategies as $strategy) {
        echo "  📊 Estratégia: $strategy\n";

        // Buscar registro pending para esta URL+estratégia
        $pending = $db->query("
            SELECT id FROM tbl_pagespeed_reports
            WHERE url = ? AND strategy = ? AND status = 'pending'
            ORDER BY analyzed_at DESC LIMIT 1
        ", [$test_url, $strategy]);

        $pending_id = $pending[0]['id'] ?? null;

        // Atualizar status para processing
        if ($pending_id) {
            $db->update('tbl_pagespeed_reports',
                ['status' => 'processing'],
                ['id' => $pending_id]
            );
        }

        $resultado = analisarComMediana($test_url, $strategy, $api_key, $num_tests);

        if ($resultado) {
            // Transformar dados
            $transformed = transformPageSpeedData($resultado, $strategy, $test_url);

            // Adicionar info de mediana
            $transformed['num_tests'] = $num_tests;
            $transformed['performance_min'] = $resultado['_mediana_info']['performance_min'];
            $transformed['performance_max'] = $resultado['_mediana_info']['performance_max'];
            $transformed['performance_median'] = $resultado['_mediana_info']['performance_median'];
            $transformed['webhook_secret'] = $webhook_secret;

            // Salvar no banco
            $ch = curl_init('http://localhost:5757/aegis/admin/api/pagespeed-save.php');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transformed));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $save_response = curl_exec($ch);

            $save_data = json_decode($save_response, true);
            if ($save_data['success']) {
                echo "  ✅ Salvo! ID: " . $save_data['report_id'] . "\n";

                // Deletar registro pending (foi substituído pelo completo)
                if ($pending_id) {
                    $db->delete('tbl_pagespeed_reports', ['id' => $pending_id]);
                }
            } else {
                echo "  ❌ Erro: " . ($save_data['error'] ?? 'Unknown') . "\n";

                // Marcar como failed
                if ($pending_id) {
                    $db->update('tbl_pagespeed_reports',
                        ['status' => 'failed'],
                        ['id' => $pending_id]
                    );
                }
            }
        } else {
            // Falha na análise
            if ($pending_id) {
                $db->update('tbl_pagespeed_reports',
                    ['status' => 'failed'],
                    ['id' => $pending_id]
                );
            }
        }

        echo "\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎯 ANÁLISE COMPLETA!\n";
echo "⏰ Fim: " . date('H:i:s') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
