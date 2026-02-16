<?php
/**
 * AEGIS - PageSpeed Insights - Disparar Análise
 * Endpoint para disparar análise manual das URLs configuradas
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    // Validar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Validar CSRF token
    if (!isset($_POST['csrf_token'])) {
        throw new Exception('Token CSRF não fornecido');
    }

    Security::validateCSRF($_POST['csrf_token'], true); // throwException = true

    // Verificar se está autenticado
    if (!Auth::check()) {
        throw new Exception('Usuário não autenticado');
    }

    // Buscar configurações
    $settings = Settings::all();

    // Verificar se PageSpeed está habilitado
    if (empty($settings['pagespeed_enabled'])) {
        throw new Exception('PageSpeed não está habilitado');
    }

    // Verificar se API Key está configurada
    if (empty($settings['pagespeed_api_key'])) {
        throw new Exception('Google API Key não configurada');
    }

    // Buscar URLs do banco
    $db = DB::connect();
    $result = $db->query("
        SELECT url
        FROM tbl_pagespeed_urls
        WHERE ativo = 1
        ORDER BY created_at ASC
    ");

    if (empty($result)) {
        throw new Exception('Nenhuma URL ativa cadastrada. Acesse /admin/pagespeed/urls para adicionar.');
    }

    $urls = array_column($result, 'url');

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
            'webhook_url' => url('/admin/api/pagespeed-save.php'),
            'webhook_secret' => $settings['pagespeed_webhook_secret'],
            'strategies' => $strategies,
            'alert_threshold' => (int) ($settings['pagespeed_alert_threshold'] ?? 70),
            'alert_email' => $settings['pagespeed_alert_email'] ?? ''
        ],
        'urls' => $urls,
        'total_urls' => count($urls),
        'total_analyses' => count($urls) * count($strategies),
        'triggered_at' => date('Y-m-d H:i:s'),
        'triggered_by' => Auth::user()['name'] ?? 'Admin'
    ];

    // Log da ação
    Logger::getInstance()->info('PageSpeed analysis triggered manually', [
        'user_id' => Auth::id(),
        'total_urls' => count($urls),
        'strategies' => implode(', ', $strategies)
    ]);

    // ============================================
    // CRIAR REGISTROS PENDING NA FILA
    // ============================================

    $pending_ids = [];
    foreach ($urls as $url) {
        foreach ($strategies as $strategy) {
            $id = Core::generateUUID();
            $db->insert('tbl_pagespeed_reports', [
                'id' => $id,
                'url' => $url,
                'strategy' => $strategy,
                'status' => 'pending',
                'analyzed_at' => date('Y-m-d H:i:s')
            ]);
            $pending_ids[] = $id;
        }
    }

    // ============================================
    // EXECUTAR ANÁLISE DIRETAMENTE (sem n8n)
    // ============================================

    // Executar análise diretamente via PHP
    $scriptPath = ROOT_PATH . 'admin/api/pagespeed-test-batch.php';
    $phpBin = '/Applications/MAMP/bin/php/php8.2.0/bin/php';

    // Executar em background com nohup (garante execução mesmo se conexão cair)
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen("start /B php \"$scriptPath\" > NUL 2>&1", "r"));
    } else {
        $cmd = sprintf(
            'nohup %s %s > /dev/null 2>&1 & echo $!',
            escapeshellarg($phpBin),
            escapeshellarg($scriptPath)
        );
        $pid = exec($cmd);

        // Log do PID para debug
        error_log("PageSpeed processo iniciado com PID: $pid");
    }

    // Log que análise foi disparada
    Logger::getInstance()->info('PageSpeed análise disparada diretamente', [
        'url' => $urls[0],
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
