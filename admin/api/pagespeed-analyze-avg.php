<?php
/**
 * Script que faz múltiplas análises e tira a média
 * Para obter resultado mais próximo do navegador
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

$settings = Settings::all();
$api_key = $settings['pagespeed_api_key'];

echo "📊 Análise com MÉDIA de múltiplas execuções\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$test_url = 'https://drywash.com.br/';
$num_tests = 3; // Número de testes para fazer média

// Armazenar resultados
$results = [
    'mobile' => [],
    'desktop' => []
];

echo "Executando $num_tests testes para cada estratégia...\n\n";

for ($i = 1; $i <= $num_tests; $i++) {
    echo "Teste #$i:\n";

    foreach (['mobile', 'desktop'] as $strategy) {
        $api_url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed";
        $api_url .= "?url=" . urlencode($test_url);
        $api_url .= "&strategy=" . $strategy;
        $api_url .= "&category=performance";
        $api_url .= "&key=" . $api_key;

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code === 200) {
            $data = json_decode($response, true);
            $score = round($data['lighthouseResult']['categories']['performance']['score'] * 100);
            $results[$strategy][] = $score;
            echo "  $strategy: $score\n";
        } else {
            echo "  $strategy: ❌ Erro\n";
        }

        // Pequena pausa entre requisições
        if ($i < $num_tests || $strategy === 'mobile') {
            sleep(2);
        }
    }
    echo "\n";
}

echo str_repeat("=", 70) . "\n";
echo "📊 RESULTADOS:\n\n";

foreach (['mobile', 'desktop'] as $strategy) {
    if (count($results[$strategy]) > 0) {
        $min = min($results[$strategy]);
        $max = max($results[$strategy]);
        $avg = round(array_sum($results[$strategy]) / count($results[$strategy]));
        $median = $results[$strategy][floor(count($results[$strategy]) / 2)];

        echo strtoupper($strategy) . ":\n";
        echo "  Valores: " . implode(', ', $results[$strategy]) . "\n";
        echo "  Mínimo: $min\n";
        echo "  Máximo: $max\n";
        echo "  Média: $avg\n";
        echo "  Mediana: $median\n";
        echo "  Variação: " . ($max - $min) . " pontos\n";
        echo "\n";
    }
}

echo "🎯 COMPARAÇÃO COM NAVEGADOR (63/64):\n";
$avg_mobile = round(array_sum($results['mobile']) / count($results['mobile']));
$avg_desktop = round(array_sum($results['desktop']) / count($results['desktop']));

echo "  Nossa média: Mobile $avg_mobile, Desktop $avg_desktop\n";
echo "  Navegador:   Mobile 63, Desktop 64\n";
echo "  Diferença:   Mobile " . abs($avg_mobile - 63) . ", Desktop " . abs($avg_desktop - 64) . "\n";

echo "\n💡 CONCLUSÃO:\n";
echo "A variação nos scores é normal e esperada devido a:\n";
echo "- Condições de rede variáveis\n";
echo "- Carga do servidor no momento\n";
echo "- Região do servidor de teste\n";
echo "- Cache e CDN\n";
echo "\nRecomendação: Usar a MEDIANA ou MÉDIA de múltiplos testes\n";
echo "para obter resultado mais confiável.\n";