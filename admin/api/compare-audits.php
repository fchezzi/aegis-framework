<?php
/**
 * Comparar audits capturados com todos disponíveis na API
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Settings.php';

$settings = Settings::all();
$api_key = $settings['pagespeed_api_key'];

// Analisar drywash
$test_url = 'https://drywash.com.br';

$api_url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed";
$api_url .= "?url=" . urlencode($test_url);
$api_url .= "&strategy=mobile";
$api_url .= "&category=performance";
$api_url .= "&key=" . $api_key;

echo "Analisando: $test_url\n";
echo "=" . str_repeat("=", 50) . "\n\n";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    die("Erro na API: HTTP $httpCode\n");
}

$data = json_decode($response, true);
$audits = $data['lighthouseResult']['audits'] ?? [];

// Lista de audits que DEVERÍAMOS estar capturando
$expected_opportunities = [
    'render-blocking-resources',
    'render-blocking-insight',
    'unused-css-rules',
    'unused-javascript',
    'modern-image-formats',
    'uses-optimized-images',
    'offscreen-images',
    'uses-responsive-images',
    'efficient-animated-content',
    'duplicated-javascript',
    'legacy-javascript',
    'preload-lcp-image',
    'uses-long-cache-ttl',
    'uses-rel-preconnect',
    'server-response-time',
    'redirects',
    'uses-text-compression',
    'uses-rel-preload',
    'unminified-css',
    'unminified-javascript',
    'font-display',
    'third-party-summary'
];

// Audits de diagnóstico importantes
$diagnostic_audits = [
    'mainthread-work-breakdown',
    'bootup-time',
    'dom-size',
    'long-tasks',
    'diagnostics',
    'network-requests',
    'network-rtt',
    'network-server-latency',
    'total-byte-weight',
    'uses-passive-event-listeners',
    'uses-http2',
    'critical-request-chains',
    'user-timings',
    'resource-summary'
];

echo "OPORTUNIDADES DISPONÍVEIS NA API:\n";
echo "-" . str_repeat("-", 49) . "\n";

$found_opportunities = [];
foreach ($audits as $id => $audit) {
    // Filtrar apenas audits com score < 1 (potenciais oportunidades)
    if (isset($audit['score']) && $audit['score'] !== null && $audit['score'] < 1) {
        $found_opportunities[$id] = [
            'score' => $audit['score'],
            'title' => $audit['title'] ?? 'N/A',
            'displayValue' => $audit['displayValue'] ?? '',
            'hasDetails' => isset($audit['details']['items']) && count($audit['details']['items']) > 0
        ];
    }
}

// Ordenar por score (pior primeiro)
uasort($found_opportunities, function($a, $b) {
    return $a['score'] <=> $b['score'];
});

foreach ($found_opportunities as $id => $info) {
    $in_list = in_array($id, $expected_opportunities) ? '✅' : '❌';
    echo sprintf("%s %-40s | Score: %.2f | %s\n",
        $in_list,
        substr($id, 0, 40),
        $info['score'],
        $info['displayValue']
    );
}

echo "\n";
echo "DIAGNÓSTICOS DISPONÍVEIS NA API:\n";
echo "-" . str_repeat("-", 49) . "\n";

foreach ($diagnostic_audits as $id) {
    if (isset($audits[$id])) {
        $audit = $audits[$id];
        $hasData = isset($audit['details']['items']) && count($audit['details']['items']) > 0;
        echo sprintf("✅ %-40s | %s\n",
            substr($id, 0, 40),
            $hasData ? 'Com dados' : 'Sem dados'
        );
    } else {
        echo sprintf("❌ %-40s | NÃO ENCONTRADO\n", substr($id, 0, 40));
    }
}

echo "\n";
echo "AUDITS NÃO CAPTURADOS (score < 0.9):\n";
echo "-" . str_repeat("-", 49) . "\n";

foreach ($found_opportunities as $id => $info) {
    if (!in_array($id, $expected_opportunities) && $info['score'] < 0.9) {
        echo "⚠️  $id (score: {$info['score']})\n";
        echo "    Título: {$info['title']}\n";
    }
}

echo "\n";
echo "RESUMO:\n";
echo "=" . str_repeat("=", 49) . "\n";
echo "Total de oportunidades na API: " . count($found_opportunities) . "\n";
echo "Capturadas em nossa lista: " . count(array_filter($found_opportunities, function($id) use ($expected_opportunities) {
    return in_array($id, $expected_opportunities);
}, ARRAY_FILTER_USE_KEY)) . "\n";