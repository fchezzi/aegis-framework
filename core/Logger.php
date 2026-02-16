<?php
/**
 * Logger
 * PSR-3 Compatible Logger com níveis completos e rotação
 *
 * Níveis de log (RFC 5424):
 * - emergency: Sistema inutilizável
 * - alert: Ação imediata necessária
 * - critical: Condições críticas
 * - error: Erros de runtime
 * - warning: Avisos
 * - notice: Eventos normais mas significativos
 * - info: Mensagens informativas
 * - debug: Informações de debug
 *
 * @example
 * // Uso estático
 * Logger::info('Usuário logou', ['user_id' => 123]);
 * Logger::error('Falha no pagamento', ['order' => 456, 'exception' => $e]);
 *
 * // Instância
 * $logger = new Logger();
 * $logger->warning('Disco quase cheio');
 *
 * // Configuração
 * Logger::configure([
 *     'level' => Logger::WARNING,  // Apenas warning e acima
 *     'daily' => true,             // Um arquivo por dia
 *     'max_files' => 30            // Manter últimos 30 dias
 * ]);
 */

class Logger {

    // PSR-3 Log levels (RFC 5424)
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    /**
     * Prioridade dos níveis (maior = mais grave)
     */
    private static $levels = [
        self::DEBUG     => 0,
        self::INFO      => 1,
        self::NOTICE    => 2,
        self::WARNING   => 3,
        self::ERROR     => 4,
        self::CRITICAL  => 5,
        self::ALERT     => 6,
        self::EMERGENCY => 7
    ];

    /**
     * Configurações
     */
    private static $config = [
        'path' => null,
        'level' => self::DEBUG,
        'daily' => true,
        'max_files' => 30,
        'max_file_size' => 5242880, // 5MB
        'date_format' => 'Y-m-d H:i:s',
        'permission' => 0644,
        'alert_email' => null
    ];

    /**
     * Instância singleton
     */
    private static $instance = null;

    /**
     * Handlers adicionais
     */
    private $handlers = [];

    /**
     * Configurar logger
     *
     * @param array $config
     */
    public static function configure($config) {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Obter instância singleton
     *
     * @return self
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor
     */
    public function __construct() {
        $this->initPath();
    }

    /**
     * Inicializar path de logs
     */
    private function initPath() {
        if (self::$config['path'] === null) {
            self::$config['path'] = defined('ROOT_PATH')
                ? ROOT_PATH . 'storage/logs/'
                : dirname(__DIR__) . '/storage/logs/';
        }

        // Criar diretório se não existir
        if (!is_dir(self::$config['path'])) {
            @mkdir(self::$config['path'], 0755, true);
        }
    }

    /**
     * Adicionar handler customizado
     *
     * @param callable $handler function($level, $message, $context, $formatted)
     */
    public function addHandler($handler) {
        $this->handlers[] = $handler;
    }

    /**
     * Verificar se deve logar baseado no nível
     */
    private function shouldLog($level) {
        $minLevel = self::$config['level'];
        return self::$levels[$level] >= self::$levels[$minLevel];
    }

    /**
     * Interpolar placeholders {key} com valores do contexto
     */
    private function interpolate($message, $context) {
        $replace = [];

        foreach ($context as $key => $val) {
            if ($key === 'exception') {
                continue; // Exception é tratada separadamente
            }

            if (is_string($val) || is_numeric($val)) {
                $replace['{' . $key . '}'] = $val;
            } elseif (is_object($val) && method_exists($val, '__toString')) {
                $replace['{' . $key . '}'] = (string) $val;
            } elseif (is_array($val) || is_object($val)) {
                $replace['{' . $key . '}'] = json_encode($val, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($val)) {
                $replace['{' . $key . '}'] = $val ? 'true' : 'false';
            } elseif ($val === null) {
                $replace['{' . $key . '}'] = 'null';
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Formatar linha de log
     */
    private function formatLine($level, $message, $context) {
        $timestamp = date(self::$config['date_format']);
        $levelUpper = strtoupper($level);

        $line = "[{$timestamp}] [{$levelUpper}] {$message}";

        // Adicionar contexto como JSON (exceto exception)
        $extraContext = array_filter($context, function($key) {
            return $key !== 'exception';
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($extraContext)) {
            $line .= ' | ' . json_encode($extraContext, JSON_UNESCAPED_UNICODE);
        }

        // Adicionar stack trace se for exception
        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $e = $context['exception'];
            $line .= "\n  Exception: " . get_class($e) . ': ' . $e->getMessage();
            $line .= "\n  File: " . $e->getFile() . ':' . $e->getLine();
            $line .= "\n  Trace:\n  " . str_replace("\n", "\n  ", $e->getTraceAsString());
        }

        return $line . "\n";
    }

    /**
     * Escrever log
     */
    private function write($level, $message, $context = []) {
        // Verificar nível mínimo
        if (!$this->shouldLog($level)) {
            return;
        }

        // Interpolar placeholders
        $message = $this->interpolate($message, $context);

        // Formatar linha
        $formatted = $this->formatLine($level, $message, $context);

        // Escrever em arquivo
        $this->writeToFile($formatted);

        // Log crítico no error_log do PHP também
        if (in_array($level, [self::ERROR, self::CRITICAL, self::ALERT, self::EMERGENCY])) {
            error_log("[AEGIS {$level}] {$message}");
        }

        // Enviar email de alerta para níveis críticos
        if (in_array($level, [self::CRITICAL, self::ALERT, self::EMERGENCY])) {
            $this->sendAlert($level, $message, $context);
        }

        // Executar handlers adicionais
        foreach ($this->handlers as $handler) {
            try {
                $handler($level, $message, $context, $formatted);
            } catch (Exception $e) {
                // Ignorar erros em handlers
            }
        }
    }

    /**
     * Escrever em arquivo
     */
    private function writeToFile($content) {
        $this->initPath();

        $filename = self::$config['daily']
            ? 'aegis-' . date('Y-m-d') . '.log'
            : 'aegis.log';

        $filepath = self::$config['path'] . $filename;

        // Verificar rotação por tamanho (apenas para log único)
        if (!self::$config['daily']) {
            $this->rotateIfNeeded($filepath);
        }

        @file_put_contents($filepath, $content, FILE_APPEND | LOCK_EX);
        @chmod($filepath, self::$config['permission']);

        // Limpar arquivos antigos
        $this->cleanOldFiles();
    }

    /**
     * Rotacionar log por tamanho
     */
    private function rotateIfNeeded($logFile) {
        if (!file_exists($logFile)) {
            return;
        }

        if (filesize($logFile) < self::$config['max_file_size']) {
            return;
        }

        $maxFiles = self::$config['max_files'];

        // Rotacionar arquivos
        for ($i = $maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $logFile . '.' . $i;
            $newFile = $logFile . '.' . ($i + 1);

            if (file_exists($oldFile)) {
                if ($i === $maxFiles - 1) {
                    @unlink($oldFile);
                } else {
                    @rename($oldFile, $newFile);
                }
            }
        }

        @rename($logFile, $logFile . '.1');
    }

    /**
     * Limpar arquivos antigos (logs diários)
     */
    private function cleanOldFiles() {
        // Executar apenas 1% das vezes
        if (rand(1, 100) > 1) {
            return;
        }

        $maxFiles = self::$config['max_files'];
        $path = self::$config['path'];

        $files = glob($path . 'aegis-*.log');

        if (count($files) <= $maxFiles) {
            return;
        }

        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        $toDelete = count($files) - $maxFiles;
        for ($i = 0; $i < $toDelete; $i++) {
            @unlink($files[$i]);
        }
    }

    /**
     * Enviar alerta por email
     */
    private function sendAlert($level, $message, $context) {
        // Verificar se Email class está disponível
        if (!class_exists('Email')) {
            return;
        }

        // Buscar email do admin
        $email = self::$config['alert_email'];
        if (empty($email) && class_exists('Settings')) {
            $email = Settings::get('admin_email');
        }

        if (empty($email)) {
            return;
        }

        $levelEmoji = [
            self::CRITICAL => '🔴',
            self::ALERT => '🟠',
            self::EMERGENCY => '💀'
        ];

        $emoji = $levelEmoji[$level] ?? '⚠️';
        $levelUpper = strtoupper($level);
        $subject = "[AEGIS] {$emoji} {$levelUpper} - " . substr($message, 0, 50);

        // Construir corpo HTML
        $bodyHtml = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $bodyHtml .= '<div style="background: #f44336; color: white; padding: 20px; border-radius: 8px 8px 0 0;">';
        $bodyHtml .= "<h2 style=\"margin: 0;\">{$emoji} ALERTA {$levelUpper}</h2>";
        $bodyHtml .= '</div>';

        $bodyHtml .= '<div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none;">';
        $bodyHtml .= '<h3 style="margin-top: 0; color: #333;">Mensagem:</h3>';
        $bodyHtml .= '<p style="background: white; padding: 15px; border-left: 4px solid #f44336; margin: 10px 0;">';
        $bodyHtml .= htmlspecialchars($message);
        $bodyHtml .= '</p>';

        // Adicionar contexto
        if (!empty($context)) {
            $bodyHtml .= '<h3 style="color: #333; margin-top: 20px;">Contexto:</h3>';
            $bodyHtml .= '<table style="width: 100%; background: white; border-collapse: collapse;">';

            foreach ($context as $key => $val) {
                $bodyHtml .= '<tr style="border-bottom: 1px solid #eee;">';
                $bodyHtml .= '<td style="padding: 10px; font-weight: bold; width: 30%;">' . htmlspecialchars($key) . ':</td>';

                if ($key === 'exception' && $val instanceof Throwable) {
                    $bodyHtml .= '<td style="padding: 10px;">';
                    $bodyHtml .= '<strong>' . htmlspecialchars($val->getMessage()) . '</strong><br>';
                    $bodyHtml .= '<small>File: ' . htmlspecialchars($val->getFile()) . ':' . $val->getLine() . '</small>';
                    $bodyHtml .= '</td>';
                } else {
                    $bodyHtml .= '<td style="padding: 10px;">' . htmlspecialchars(is_scalar($val) ? $val : json_encode($val)) . '</td>';
                }

                $bodyHtml .= '</tr>';
            }

            $bodyHtml .= '</table>';
        }

        // Informações do servidor
        $bodyHtml .= '<h3 style="color: #333; margin-top: 20px;">Informações do Servidor:</h3>';
        $bodyHtml .= '<table style="width: 100%; background: white; border-collapse: collapse;">';
        $bodyHtml .= '<tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px; font-weight: bold; width: 30%;">Data/Hora:</td><td style="padding: 10px;">' . date('Y-m-d H:i:s') . '</td></tr>';
        $bodyHtml .= '<tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px; font-weight: bold;">Servidor:</td><td style="padding: 10px;">' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'cli') . '</td></tr>';
        $bodyHtml .= '<tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px; font-weight: bold;">URL:</td><td style="padding: 10px;">' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'n/a') . '</td></tr>';
        $bodyHtml .= '<tr><td style="padding: 10px; font-weight: bold;">IP:</td><td style="padding: 10px;">' . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'n/a') . '</td></tr>';
        $bodyHtml .= '</table>';

        $bodyHtml .= '</div>';

        $bodyHtml .= '<div style="background: #333; color: #999; text-align: center; padding: 15px; border-radius: 0 0 8px 8px; font-size: 12px;">';
        $bodyHtml .= 'AEGIS Framework - Sistema de Alertas';
        $bodyHtml .= '</div>';
        $bodyHtml .= '</div>';

        // Enviar usando Email::sendAlert() que usa SMTP de alertas
        try {
            Email::sendAlert($email, $subject, $bodyHtml);
        } catch (Exception $e) {
            // Falhou silenciosamente - não queremos quebrar o app por causa de notificação
            error_log("AEGIS Logger: Falha ao enviar email de alerta - " . $e->getMessage());
        }
    }

    // ===================
    // PSR-3 INSTANCE METHODS
    // ===================

    public function emergency($message, $context = []) {
        $this->write(self::EMERGENCY, $message, $context);
    }

    public function alert($message, $context = []) {
        $this->write(self::ALERT, $message, $context);
    }

    public function critical($message, $context = []) {
        $this->write(self::CRITICAL, $message, $context);
    }

    public function error($message, $context = []) {
        $this->write(self::ERROR, $message, $context);
    }

    public function warning($message, $context = []) {
        $this->write(self::WARNING, $message, $context);
    }

    public function notice($message, $context = []) {
        $this->write(self::NOTICE, $message, $context);
    }

    public function info($message, $context = []) {
        $this->write(self::INFO, $message, $context);
    }

    public function debug($message, $context = []) {
        // Debug apenas em dev
        if (class_exists('CoreEnvironment') && !CoreEnvironment::isDev()) {
            return;
        }
        $this->write(self::DEBUG, $message, $context);
    }

    public function log($level, $message, $context = []) {
        $this->write($level, $message, $context);
    }

    // ===================
    // STATIC API (SHORTCUTS)
    // ===================

    public static function logEmergency($message, $context = []) {
        self::getInstance()->emergency($message, $context);
    }

    public static function logAlert($message, $context = []) {
        self::getInstance()->alert($message, $context);
    }

    public static function logCritical($message, $context = []) {
        self::getInstance()->critical($message, $context);
    }

    public static function logError($message, $context = []) {
        self::getInstance()->error($message, $context);
    }

    public static function logWarning($message, $context = []) {
        self::getInstance()->warning($message, $context);
    }

    public static function logNotice($message, $context = []) {
        self::getInstance()->notice($message, $context);
    }

    public static function logInfo($message, $context = []) {
        self::getInstance()->info($message, $context);
    }

    public static function logDebug($message, $context = []) {
        self::getInstance()->debug($message, $context);
    }

    // ===================
    // UTILITY METHODS
    // ===================

    /**
     * Log de query SQL
     */
    public function query($sql, $bindings = [], $time = null) {
        $message = $sql;
        if (!empty($bindings)) {
            $message .= ' -- Bindings: ' . json_encode($bindings);
        }
        if ($time !== null) {
            $message .= " -- {$time}ms";
        }
        $this->debug($message, ['type' => 'query']);
    }

    /**
     * Log de request HTTP
     */
    public function request() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? 'n/a';
        $this->info("{$method} {$uri}", [
            'type' => 'request',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a'
        ]);
    }

    /**
     * Log de segurança
     */
    public function security($event, $context = []) {
        $this->warning("SECURITY: {$event}", array_merge([
            'type' => 'security',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a'
        ], $context));
    }

    /**
     * Log de auditoria
     */
    public function audit($action, $userId, $context = []) {
        $this->info("AUDIT: {$action}", array_merge([
            'type' => 'audit',
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a'
        ], $context));
    }

    // ===================
    // ADMIN METHODS
    // ===================

    /**
     * Ler logs (últimas N linhas)
     */
    public static function read($lines = 100, $level = null) {
        $instance = self::getInstance();
        $instance->initPath();

        $filename = self::$config['daily']
            ? 'aegis-' . date('Y-m-d') . '.log'
            : 'aegis.log';

        $logFile = self::$config['path'] . $filename;

        if (!file_exists($logFile)) {
            return [];
        }

        $file = new SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();

        $logs = [];
        $count = 0;

        for ($i = $lastLine; $i >= 0 && $count < $lines; $i--) {
            $file->seek($i);
            $line = $file->current();

            if (empty(trim($line))) {
                continue;
            }

            if ($level !== null && stripos($line, "[{$level}]") === false) {
                continue;
            }

            $logs[] = trim($line);
            $count++;
        }

        return array_reverse($logs);
    }

    /**
     * Limpar todos os logs
     */
    public static function clear() {
        $instance = self::getInstance();
        $instance->initPath();

        $files = glob(self::$config['path'] . 'aegis*.log*');

        foreach ($files as $file) {
            @unlink($file);
        }
    }

    /**
     * Obter tamanho total dos logs
     */
    public static function getSize() {
        $instance = self::getInstance();
        $instance->initPath();

        $files = glob(self::$config['path'] . 'aegis*.log*');
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);
        }

        return $totalSize;
    }

    /**
     * Obter lista de arquivos de log
     */
    public static function getFiles() {
        $instance = self::getInstance();
        $instance->initPath();

        $files = glob(self::$config['path'] . 'aegis*.log*');
        $result = [];

        foreach ($files as $file) {
            $result[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }

        // Ordenar por data (mais recente primeiro)
        usort($result, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $result;
    }
}
