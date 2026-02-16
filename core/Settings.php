<?php
/**
 * Settings Helper
 * Gerencia configurações do sistema via JSON
 */

class Settings {
    private static $file = null;
    private static $cache = null;

    /**
     * Define o caminho do arquivo de settings
     */
    private static function getFile() {
        if (self::$file === null) {
            self::$file = ROOT_PATH . 'storage/settings.json';
        }
        return self::$file;
    }

    /**
     * Carrega settings do arquivo
     */
    private static function load() {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $file = self::getFile();

        // Se arquivo não existe, criar com defaults
        if (!file_exists($file)) {
            self::createDefault();
        }

        $content = file_get_contents($file);
        self::$cache = json_decode($content, true);

        if (self::$cache === null) {
            // JSON inválido, recriar
            self::createDefault();
            $content = file_get_contents($file);
            self::$cache = json_decode($content, true);
        }

        return self::$cache;
    }

    /**
     * Criar arquivo com configurações padrão
     */
    private static function createDefault() {
        $defaults = [
            'admin_email' => '',
            'site_name' => 'AEGIS Framework',
            'maintenance_mode' => false,
            'timezone' => 'America/Sao_Paulo',
            'tinymce_api_key' => defined('TINYMCE_API_KEY') ? TINYMCE_API_KEY : 'no-api-key'
        ];

        $file = self::getFile();

        // Criar diretório se não existir
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($file, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        self::$cache = $defaults;
    }

    /**
     * Salvar settings no arquivo
     */
    private static function save($data) {
        $file = self::getFile();
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        self::$cache = $data;
    }

    /**
     * Obter valor de uma configuração
     *
     * @param string $key Chave da configuração
     * @param mixed $default Valor padrão se não existir
     * @return mixed
     */
    public static function get($key, $default = null) {
        $settings = self::load();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Definir valor de uma configuração
     *
     * @param string $key Chave da configuração
     * @param mixed $value Valor
     */
    public static function set($key, $value) {
        $settings = self::load();
        $settings[$key] = $value;
        self::save($settings);
    }

    /**
     * Obter todas as configurações
     *
     * @return array
     */
    public static function all() {
        return self::load();
    }

    /**
     * Atualizar múltiplas configurações de uma vez
     *
     * @param array $data Array associativo com as configurações
     */
    public static function updateMultiple($data) {
        $settings = self::load();

        foreach ($data as $key => $value) {
            $settings[$key] = $value;
        }

        self::save($settings);
    }

    /**
     * Verificar se configuração existe
     *
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        $settings = self::load();
        return isset($settings[$key]);
    }

    /**
     * Remover configuração
     *
     * @param string $key
     */
    public static function remove($key) {
        $settings = self::load();

        if (isset($settings[$key])) {
            unset($settings[$key]);
            self::save($settings);
        }
    }

    /**
     * Limpar cache (útil para testes)
     */
    public static function clearCache() {
        self::$cache = null;
    }
}
