<?php
/**
 * CheckerController - Validação de ferramentas SEO/Qualidade
 *
 * @version 1.0.0
 */

class CheckerController extends BaseController {

    public function index() {
        Auth::require();

        $checks = [
            'ssl' => $this->checkSSL(),
            'gtm' => $this->checkGTM(),
            'robots' => $this->checkRobots(),
            'sitemap' => $this->checkSitemap(),
            'phpstan' => $this->checkPHPStan(),
            'uptime' => $this->checkUptimeRobot(),
            'backup' => $this->checkBackup(),
            'search_console' => $this->checkSearchConsole()
        ];

        // Calcular progresso
        $total = count($checks);
        $completed = count(array_filter($checks, fn($c) => $c['status'] === 'success'));
        $progress = round(($completed / $total) * 100);

        $data = [
            'checks' => $checks,
            'progress' => $progress,
            'completed' => $completed,
            'total' => $total
        ];

        return $this->render('checker', $data);
    }

    /**
     * 1. Verificar SSL/HTTPS
     */
    private function checkSSL() {
        $url = defined('APP_URL') ? APP_URL : '';

        if (empty($url)) {
            return [
                'status' => 'error',
                'title' => 'HTTPS/SSL',
                'message' => 'APP_URL não configurada',
                'action' => 'Configurar APP_URL em _config.php',
                'time' => '2min'
            ];
        }

        $isHttps = strpos($url, 'https://') === 0;

        return [
            'status' => $isHttps ? 'success' : 'warning',
            'title' => 'HTTPS/SSL',
            'message' => $isHttps ? 'SSL ativo e configurado' : 'Site usando HTTP (inseguro)',
            'detail' => $url,
            'action' => $isHttps ? null : 'Instalar certificado SSL (Let\'s Encrypt)',
            'time' => '2min'
        ];
    }

    /**
     * 2. Verificar Google Tag Manager
     */
    private function checkGTM() {
        $gtmId = defined('GTM_ID') ? GTM_ID : '';

        if (empty($gtmId)) {
            return [
                'status' => 'warning',
                'title' => 'GTM/GA4',
                'message' => 'GTM_ID não configurado',
                'action' => 'Definir GTM_ID em _config.php',
                'time' => '3min'
            ];
        }

        // Verificar se GTM está no _head.php
        $headFile = ROOT_PATH . 'frontend/includes/_head.php';
        $hasGTM = false;

        if (file_exists($headFile)) {
            $content = file_get_contents($headFile);
            $hasGTM = strpos($content, 'googletagmanager.com') !== false;
        }

        return [
            'status' => $hasGTM ? 'success' : 'warning',
            'title' => 'GTM/GA4',
            'message' => $hasGTM ? "GTM configurado" : 'GTM_ID definido mas código não encontrado',
            'detail' => $gtmId,
            'action' => $hasGTM ? null : 'Adicionar código GTM em _head.php',
            'time' => '3min'
        ];
    }

    /**
     * 3. Verificar robots.txt
     */
    private function checkRobots() {
        $robotsPath = ROOT_PATH . 'public/robots.txt';
        $exists = file_exists($robotsPath);

        return [
            'status' => $exists ? 'success' : 'error',
            'title' => 'robots.txt',
            'message' => $exists ? 'Arquivo robots.txt encontrado' : 'robots.txt não existe',
            'detail' => $exists ? filesize($robotsPath) . ' bytes' : null,
            'action' => $exists ? null : 'Criar robots.txt',
            'link' => url('/admin/robots'),
            'link_text' => $exists ? 'Editar' : 'Criar',
            'time' => '5min'
        ];
    }

    /**
     * 4. Verificar sitemap.xml
     */
    private function checkSitemap() {
        $sitemapPath = ROOT_PATH . 'public/sitemap.xml';
        $exists = file_exists($sitemapPath);

        $urlCount = 0;
        if ($exists) {
            $content = file_get_contents($sitemapPath);
            preg_match_all('/<url>/', $content, $matches);
            $urlCount = count($matches[0]);
        }

        return [
            'status' => $exists ? 'success' : 'warning',
            'title' => 'Sitemap.xml',
            'message' => $exists ? "Sitemap gerado" : 'Sitemap não existe',
            'detail' => $exists ? "{$urlCount} URLs" : null,
            'action' => $exists ? null : 'Gerar sitemap.xml',
            'link' => url('/admin/sitemap'),
            'link_text' => $exists ? 'Ver/Regenerar' : 'Gerar',
            'time' => '30min'
        ];
    }

    /**
     * 5. Verificar PHPStan
     */
    private function checkPHPStan() {
        $vendorPath = ROOT_PATH . 'vendor/bin/phpstan';
        $installed = file_exists($vendorPath);

        return [
            'status' => $installed ? 'success' : 'pending',
            'title' => 'PHPStan',
            'message' => $installed ? 'PHPStan instalado' : 'PHPStan não instalado',
            'detail' => $installed ? 'Análise estática de código' : null,
            'action' => $installed ? null : 'composer require --dev phpstan/phpstan',
            'time' => '10min'
        ];
    }

    /**
     * 6. Verificar UptimeRobot
     */
    private function checkUptimeRobot() {
        return [
            'status' => 'pending',
            'title' => 'UptimeRobot',
            'message' => 'Monitoramento externo (configuração manual)',
            'action' => 'Criar monitor em UptimeRobot.com',
            'link' => 'https://uptimerobot.com',
            'link_text' => 'Abrir UptimeRobot',
            'time' => '5min'
        ];
    }

    /**
     * 7. Verificar Backup Automático
     */
    private function checkBackup() {
        return [
            'status' => 'pending',
            'title' => 'Backup Automático n8n',
            'message' => 'Workflow n8n não detectado',
            'action' => 'Criar workflow de backup diário no n8n',
            'link' => 'http://localhost:5678',
            'link_text' => 'Abrir n8n',
            'time' => '1h'
        ];
    }

    /**
     * 8. Verificar Google Search Console API
     */
    private function checkSearchConsole() {
        $credentialsPath = ROOT_PATH . 'config/google-service-account.json';
        $classPath = ROOT_PATH . 'core/GoogleSearchConsole.php';

        $hasCredentials = file_exists($credentialsPath);
        $hasClass = file_exists($classPath);

        // Verificar se tabelas existem
        $hasTables = false;
        try {
            $result = DB::query("SHOW TABLES LIKE 'gsc_queries'");
            $hasTables = count($result) > 0;
        } catch (Exception $e) {
            $hasTables = false;
        }

        $status = 'pending';
        $message = 'Não configurado';
        $detail = null;

        if ($hasCredentials && $hasClass && $hasTables) {
            $status = 'success';
            $message = 'Search Console API configurada';
            $detail = 'Credenciais, classe e tabelas OK';
        } elseif ($hasCredentials || $hasClass || $hasTables) {
            $status = 'warning';
            $missing = [];
            if (!$hasCredentials) $missing[] = 'credenciais';
            if (!$hasClass) $missing[] = 'classe PHP';
            if (!$hasTables) $missing[] = 'tabelas MySQL';
            $message = 'Configuração parcial';
            $detail = 'Falta: ' . implode(', ', $missing);
        }

        return [
            'status' => $status,
            'title' => 'Google Search Console API',
            'message' => $message,
            'detail' => $detail,
            'action' => $status === 'success' ? null : 'Configurar API e criar tabelas',
            'time' => '2h'
        ];
    }
}
