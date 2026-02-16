<?php
/**
 * Asset
 * Gerenciador de assets (CSS, JS, imagens) com versionamento
 *
 * Funcionalidades:
 * - Versionamento automático (cache busting)
 * - Minificação básica
 * - Concatenação de arquivos
 * - Preload/prefetch hints
 * - Inline crítico CSS
 *
 * @example
 * // CSS com versão automática
 * <link href="<?= Asset::css('app.css') ?>" rel="stylesheet">
 * // Output: /assets/css/app.css?v=1699456789
 *
 * // JS com versão
 * <script src="<?= Asset::js('app.js') ?>"></script>
 *
 * // Imagem com versão
 * <img src="<?= Asset::img('logo.png') ?>">
 *
 * // Múltiplos CSS concatenados
 * <?= Asset::styles(['reset.css', 'app.css', 'custom.css']) ?>
 *
 * // Preload
 * <?= Asset::preload('fonts/roboto.woff2', 'font') ?>
 */

class Asset {

    /**
     * Base path para assets
     */
    private static $basePath = '/assets';

    /**
     * Diretório físico dos assets
     */
    private static $directory = null;

    /**
     * Versão global (para forçar invalidação)
     */
    private static $version = null;

    /**
     * Usar hash do arquivo como versão
     */
    private static $useFileHash = true;

    /**
     * Cache de versões calculadas
     */
    private static $versionCache = [];

    /**
     * Manifest de assets (para build tools)
     */
    private static $manifest = null;

    /**
     * Assets já adicionados (para evitar duplicatas)
     */
    private static $added = [
        'css' => [],
        'js' => []
    ];

    // ===================
    // CONFIGURATION
    // ===================

    /**
     * Configurar asset manager
     */
    public static function configure(array $config) {
        if (isset($config['base_path'])) {
            self::$basePath = '/' . trim($config['base_path'], '/');
        }
        if (isset($config['directory'])) {
            self::$directory = rtrim($config['directory'], '/');
        }
        if (isset($config['version'])) {
            self::$version = $config['version'];
        }
        if (isset($config['use_file_hash'])) {
            self::$useFileHash = $config['use_file_hash'];
        }
        if (isset($config['manifest'])) {
            self::loadManifest($config['manifest']);
        }
    }

    /**
     * Carregar manifest (ex: mix-manifest.json, manifest.json)
     */
    public static function loadManifest($path) {
        if (file_exists($path)) {
            self::$manifest = json_decode(file_get_contents($path), true);
        }
    }

    /**
     * Obter diretório de assets
     */
    private static function getDirectory() {
        if (self::$directory === null) {
            self::$directory = (defined('ROOT_PATH') ? ROOT_PATH : '') . 'public/assets';
        }
        return self::$directory;
    }

    // ===================
    // URL GENERATORS
    // ===================

    /**
     * Gerar URL para asset CSS
     */
    public static function css($file) {
        return self::url('css/' . $file);
    }

    /**
     * Gerar URL para asset JS
     */
    public static function js($file) {
        return self::url('js/' . $file);
    }

    /**
     * Gerar URL para imagem
     */
    public static function img($file) {
        return self::url('img/' . $file);
    }

    /**
     * Gerar URL para fonte
     */
    public static function font($file) {
        return self::url('fonts/' . $file);
    }

    /**
     * Gerar URL genérica para asset
     */
    public static function url($path) {
        // Verificar manifest primeiro
        if (self::$manifest !== null) {
            $manifestKey = '/' . ltrim($path, '/');
            if (isset(self::$manifest[$manifestKey])) {
                return self::$basePath . self::$manifest[$manifestKey];
            }
        }

        // URL base
        $url = self::$basePath . '/' . ltrim($path, '/');

        // Adicionar versão
        $version = self::getVersion($path);
        if ($version) {
            $url .= '?v=' . $version;
        }

        return $url;
    }

    /**
     * Obter versão de um asset
     */
    private static function getVersion($path) {
        // Versão global forçada
        if (self::$version !== null) {
            return self::$version;
        }

        // Hash do arquivo
        if (self::$useFileHash) {
            if (isset(self::$versionCache[$path])) {
                return self::$versionCache[$path];
            }

            $fullPath = self::getDirectory() . '/' . $path;
            if (file_exists($fullPath)) {
                $version = substr(md5_file($fullPath), 0, 8);
                self::$versionCache[$path] = $version;
                return $version;
            }
        }

        // Fallback: timestamp da aplicação
        return defined('APP_VERSION') ? APP_VERSION : time();
    }

    // ===================
    // HTML GENERATORS
    // ===================

    /**
     * Gerar tag <link> para CSS
     */
    public static function stylesheet($file, $attributes = []) {
        $url = self::css($file);

        $attrs = array_merge([
            'rel' => 'stylesheet',
            'href' => $url
        ], $attributes);

        return '<link ' . self::buildAttributes($attrs) . '>';
    }

    /**
     * Gerar tag <script> para JS
     */
    public static function script($file, $attributes = []) {
        $url = self::js($file);

        $attrs = array_merge([
            'src' => $url
        ], $attributes);

        return '<script ' . self::buildAttributes($attrs) . '></script>';
    }

    /**
     * Gerar múltiplas tags CSS
     */
    public static function styles(array $files, $attributes = []) {
        $html = '';
        foreach ($files as $file) {
            if (!in_array($file, self::$added['css'])) {
                $html .= self::stylesheet($file, $attributes) . "\n";
                self::$added['css'][] = $file;
            }
        }
        return $html;
    }

    /**
     * Gerar múltiplas tags JS
     */
    public static function scripts(array $files, $attributes = []) {
        $html = '';
        foreach ($files as $file) {
            if (!in_array($file, self::$added['js'])) {
                $html .= self::script($file, $attributes) . "\n";
                self::$added['js'][] = $file;
            }
        }
        return $html;
    }

    // ===================
    // PRELOAD & PREFETCH
    // ===================

    /**
     * Gerar tag preload
     */
    public static function preload($file, $as = null, $type = null) {
        $url = self::url($file);

        // Detectar tipo automaticamente
        if ($as === null) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $as = self::getAsType($extension);
        }

        $attrs = [
            'rel' => 'preload',
            'href' => $url,
            'as' => $as
        ];

        if ($type) {
            $attrs['type'] = $type;
        }

        // Crossorigin para fontes
        if ($as === 'font') {
            $attrs['crossorigin'] = 'anonymous';
        }

        return '<link ' . self::buildAttributes($attrs) . '>';
    }

    /**
     * Gerar tag prefetch
     */
    public static function prefetch($file) {
        $url = self::url($file);

        return '<link rel="prefetch" href="' . $url . '">';
    }

    /**
     * Gerar tag preconnect
     */
    public static function preconnect($origin, $crossorigin = false) {
        $attrs = [
            'rel' => 'preconnect',
            'href' => $origin
        ];

        if ($crossorigin) {
            $attrs['crossorigin'] = 'anonymous';
        }

        return '<link ' . self::buildAttributes($attrs) . '>';
    }

    // ===================
    // INLINE
    // ===================

    /**
     * Inserir CSS inline (crítico)
     */
    public static function inlineCss($file) {
        $fullPath = self::getDirectory() . '/css/' . $file;

        if (!file_exists($fullPath)) {
            return '';
        }

        $content = file_get_contents($fullPath);

        // Minificar básico
        $content = self::minifyCss($content);

        return '<style>' . $content . '</style>';
    }

    /**
     * Inserir JS inline
     */
    public static function inlineJs($file) {
        $fullPath = self::getDirectory() . '/js/' . $file;

        if (!file_exists($fullPath)) {
            return '';
        }

        $content = file_get_contents($fullPath);

        return '<script>' . $content . '</script>';
    }

    // ===================
    // MINIFICATION
    // ===================

    /**
     * Minificar CSS (básico)
     */
    public static function minifyCss($css) {
        // Remover comentários
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remover espaços desnecessários
        $css = preg_replace('/\s+/', ' ', $css);
        // Remover espaços antes/depois de {, }, :, ;
        $css = preg_replace('/\s*([\{\}:;,])\s*/', '$1', $css);
        // Remover ; antes de }
        $css = str_replace(';}', '}', $css);

        return trim($css);
    }

    /**
     * Minificar JS (básico - apenas remove espaços)
     */
    public static function minifyJs($js) {
        // Remover comentários de linha única
        $js = preg_replace('/\/\/.*$/m', '', $js);
        // Remover comentários de bloco
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        // Remover múltiplos espaços
        $js = preg_replace('/\s+/', ' ', $js);

        return trim($js);
    }

    // ===================
    // HELPERS
    // ===================

    /**
     * Construir string de atributos HTML
     */
    private static function buildAttributes(array $attrs) {
        $parts = [];
        foreach ($attrs as $key => $value) {
            if ($value === true) {
                $parts[] = $key;
            } elseif ($value !== false && $value !== null) {
                $parts[] = $key . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Obter tipo 'as' baseado na extensão
     */
    private static function getAsType($extension) {
        $types = [
            'css' => 'style',
            'js' => 'script',
            'woff' => 'font',
            'woff2' => 'font',
            'ttf' => 'font',
            'otf' => 'font',
            'eot' => 'font',
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'svg' => 'image',
            'webp' => 'image'
        ];

        return $types[strtolower($extension)] ?? 'fetch';
    }

    /**
     * Resetar assets adicionados
     */
    public static function reset() {
        self::$added = ['css' => [], 'js' => []];
    }

    /**
     * Obter assets adicionados
     */
    public static function getAdded() {
        return self::$added;
    }

    /**
     * Forçar versão global
     */
    public static function setVersion($version) {
        self::$version = $version;
    }

    /**
     * Limpar cache de versões
     */
    public static function clearVersionCache() {
        self::$versionCache = [];
    }
}
