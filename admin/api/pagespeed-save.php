<?php
/**
 * AEGIS - PageSpeed Insights - Salvar Análise
 * Endpoint para receber dados do n8n e salvar no banco
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

    // Pegar JSON do body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('JSON inválido');
    }

    // Validar webhook secret
    $secret = $data['webhook_secret'] ?? '';
    $settings = Settings::all();

    if (empty($secret) || $secret !== ($settings['pagespeed_webhook_secret'] ?? '')) {
        Logger::getInstance()->security('Tentativa de acesso não autorizado ao webhook PageSpeed', [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'secret_received' => substr($secret, 0, 10) . '...'
        ]);
        throw new Exception('Webhook secret inválido');
    }

    // Validar campos obrigatórios
    $required = ['url', 'strategy', 'performance_score', 'analyzed_at'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Campo obrigatório ausente: {$field}");
        }
    }

    // Validar strategy
    if (!in_array($data['strategy'], ['mobile', 'desktop'])) {
        throw new Exception('Strategy deve ser "mobile" ou "desktop"');
    }

    // Validar performance_score
    if ($data['performance_score'] < 0 || $data['performance_score'] > 100) {
        throw new Exception('Performance score deve estar entre 0 e 100');
    }

    // Preparar dados para inserção
    $reportData = [
        'id' => Security::generateUUID(),
        'url' => Security::sanitize($data['url']),
        'strategy' => $data['strategy'],
        'analyzed_at' => $data['analyzed_at'],
        'lighthouse_version' => $data['lighthouse_version'] ?? null,
        'fetch_time_ms' => $data['fetch_time_ms'] ?? null,
        'performance_score' => (int) $data['performance_score'],
        'num_tests' => isset($data['num_tests']) ? (int) $data['num_tests'] : 1,
        'performance_min' => isset($data['performance_min']) ? (int) $data['performance_min'] : null,
        'performance_max' => isset($data['performance_max']) ? (int) $data['performance_max'] : null,
        'performance_median' => isset($data['performance_median']) ? (int) $data['performance_median'] : null,
        'accessibility_score' => isset($data['accessibility_score']) ? (int) $data['accessibility_score'] : null,
        'best_practices_score' => isset($data['best_practices_score']) ? (int) $data['best_practices_score'] : null,
        'seo_score' => isset($data['seo_score']) ? (int) $data['seo_score'] : null,

        // Lab Data
        'lab_lcp' => $data['lab_lcp'] ?? null,
        'lab_fcp' => $data['lab_fcp'] ?? null,
        'lab_cls' => $data['lab_cls'] ?? null,
        'lab_inp' => $data['lab_inp'] ?? null,
        'lab_si' => $data['lab_si'] ?? null,
        'lab_tti' => $data['lab_tti'] ?? null,
        'lab_tbt' => $data['lab_tbt'] ?? null,

        // Field Data (pode ser null)
        'field_lcp' => $data['field_lcp'] ?? null,
        'field_fcp' => $data['field_fcp'] ?? null,
        'field_cls' => $data['field_cls'] ?? null,
        'field_inp' => $data['field_inp'] ?? null,
        'field_category' => $data['field_category'] ?? null,

        // JSON Data (compatibilidade)
        'opportunities' => isset($data['opportunities']) ? json_encode($data['opportunities']) : null,
        'diagnostics' => isset($data['diagnostics']) ? json_encode($data['diagnostics']) : null,

        // NOVOS CAMPOS EXPANDIDOS (já vêm como JSON do transform)
        'opportunities_full' => $data['opportunities_full'] ?? null,
        'diagnostics_full' => $data['diagnostics_full'] ?? null,
        'third_party_summary' => $data['third_party_summary'] ?? null,
        'resource_summary' => $data['resource_summary'] ?? null,
        'passed_audits' => $data['passed_audits'] ?? null,

        // Screenshots (opcional)
        'screenshot_final' => $data['screenshot_final'] ?? null,
        'screenshot_thumbnails' => isset($data['screenshot_thumbnails']) ? json_encode($data['screenshot_thumbnails']) : null,

        // Elementos específicos
        'lcp_element' => $data['lcp_element'] ?? null,
        'cls_elements' => $data['cls_elements'] ?? null,

        // Métricas adicionais
        'server_response_time' => $data['server_response_time'] ?? null,
        'redirects_count' => $data['redirects_count'] ?? null,
        'total_requests' => $data['total_requests'] ?? null,
        'total_size_kb' => $data['total_size_kb'] ?? null,
        'js_size_kb' => $data['js_size_kb'] ?? null,
        'css_size_kb' => $data['css_size_kb'] ?? null,
        'image_size_kb' => $data['image_size_kb'] ?? null,
        'font_size_kb' => $data['font_size_kb'] ?? null,
        'html_size_kb' => $data['html_size_kb'] ?? null,

        // Timing
        'mainthread_work_ms' => $data['mainthread_work_ms'] ?? null,
        'bootup_time_ms' => $data['bootup_time_ms'] ?? null,

        // Warnings/Errors
        'run_warnings' => isset($data['run_warnings']) ? json_encode($data['run_warnings']) : null,
        'runtime_error' => $data['runtime_error'] ?? null
    ];

    // Inserir no banco
    $db = DB::connect();

    $columns = array_keys($reportData);
    $placeholders = array_fill(0, count($columns), '?');

    $sql = "INSERT INTO tbl_pagespeed_reports (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $placeholders) . ")";

    $db->query($sql, array_values($reportData));

    // Log sucesso
    Logger::getInstance()->info('PageSpeed report saved', [
        'url' => $reportData['url'],
        'strategy' => $reportData['strategy'],
        'score' => $reportData['performance_score']
    ]);

    // Verificar se precisa enviar alerta
    $alertThreshold = (int) ($settings['pagespeed_alert_threshold'] ?? 70);
    $alertEmail = $settings['pagespeed_alert_email'] ?? '';

    if ($reportData['performance_score'] < $alertThreshold && !empty($alertEmail)) {
        // Enviar alerta (via email ou webhook)
        $subject = "⚠️ Alerta PageSpeed: Score Baixo ({$reportData['performance_score']}/100)";
        $message = "URL: {$reportData['url']}\n";
        $message .= "Strategy: {$reportData['strategy']}\n";
        $message .= "Score: {$reportData['performance_score']}/100\n";
        $message .= "LCP: {$reportData['lab_lcp']}s\n";
        $message .= "CLS: {$reportData['lab_cls']}\n";
        $message .= "INP: {$reportData['lab_inp']}ms\n\n";
        $message .= "Acesse o painel para mais detalhes.";

        // Usar PHPMailer se configurado
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->setFrom(defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@aegis.local', 'AEGIS PageSpeed');
                $mail->addAddress($alertEmail);
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->send();
            } catch (Exception $e) {
                Logger::getInstance()->warning('Falha ao enviar alerta PageSpeed', ['error' => $e->getMessage()]);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'report_id' => $reportData['id'],
        'message' => 'Relatório salvo com sucesso'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
