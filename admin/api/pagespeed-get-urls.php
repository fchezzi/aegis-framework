<?php
/**
 * AEGIS - PageSpeed Insights - Get URLs (n8n)
 * Endpoint público para n8n buscar URLs a serem analisadas
 * Autenticação via webhook_secret
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

header('Content-Type: application/json');

try {
    // Validar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Pegar dados do POST (form-urlencoded do n8n)
    $webhookSecret = $_POST['webhook_secret'] ?? '';

    // Buscar configurações
    $settings = Settings::all();

    // Validar webhook secret
    if (empty($webhookSecret) || $webhookSecret !== ($settings['pagespeed_webhook_secret'] ?? '')) {
        Logger::getInstance()->security('Tentativa de acesso não autorizado ao endpoint PageSpeed n8n', [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'secret_received' => substr($webhookSecret, 0, 10) . '...'
        ]);
        http_response_code(403);
        throw new Exception('Webhook secret inválido');
    }

    // Verificar se PageSpeed está habilitado
    if (empty($settings['pagespeed_enabled'])) {
        throw new Exception('PageSpeed não está habilitado');
    }

    // Verificar se API Key está configurada
    if (empty($settings['pagespeed_api_key'])) {
        throw new Exception('Google API Key não configurada');
    }

    // URLs para análise
    // TODO: Quando tbl_pages existir, descomentar query abaixo
    /*
    $db = DB::connect();
    $result = $db->query("
        SELECT DISTINCT p.url
        FROM tbl_pages p
        WHERE p.status = 'published'
        AND p.url IS NOT NULL
        AND p.url != ''
        ORDER BY p.url ASC
    ", []);
    $pages = array_column($result, 'url');
    if (empty($pages)) {
        throw new Exception('Nenhuma página publicada encontrada');
    }
    $baseUrl = rtrim(url('/'), '/');
    $urls = array_map(function($page) use ($baseUrl) {
        return $baseUrl . '/' . ltrim($page, '/');
    }, $pages);
    */

    // TEMPORÁRIO: URLs de teste
    $urls = [
        'https://google.com'
    ];

    // Determinar estratégias
    $strategies = [];
    if (!empty($settings['pagespeed_strategy_mobile'])) {
        $strategies[] = 'mobile';
    }
    if (!empty($settings['pagespeed_strategy_desktop'])) {
        $strategies[] = 'desktop';
    }

    if (empty($strategies)) {
        throw new Exception('Nenhuma estratégia de análise configurada');
    }

    // Preparar dados para retornar ao n8n
    $response = [
        'success' => true,
        'config' => [
            'api_key' => $settings['pagespeed_api_key'],
            'webhook_secret' => $settings['pagespeed_webhook_secret'],
            'strategies' => $strategies,
            'alert_threshold' => (int) ($settings['pagespeed_alert_threshold'] ?? 70),
            'alert_email' => $settings['pagespeed_alert_email'] ?? ''
        ],
        'urls' => $urls,
        'total_urls' => count($urls),
        'total_analyses' => count($urls) * count($strategies),
        'triggered_at' => date('Y-m-d H:i:s')
    ];

    // Log
    Logger::getInstance()->info('PageSpeed URLs requested by n8n', [
        'total_urls' => count($urls),
        'strategies' => implode(', ', $strategies)
    ]);

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
