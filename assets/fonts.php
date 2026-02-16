<?php
/**
 * Gerador Dinâmico de @font-face CSS
 * Carrega todas as fontes ativas do banco e gera CSS @font-face
 *
 * Uso:
 * <link rel="stylesheet" href="/assets/fonts.php">
 *
 * Cache: 1 hora (navegadores) + ETags para validação
 */

// Bootstrap AEGIS
define('ROOT_PATH', __DIR__ . '/../');
require_once ROOT_PATH . '_config.php';

// Autoloader
require_once ROOT_PATH . 'core/Autoloader.php';
Autoloader::register();

// Headers CSS
header('Content-Type: text/css; charset=utf-8');

// Cache: 1 hora (3600 segundos)
header('Cache-Control: public, max-age=3600');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// ETag para validação de cache
$fonts = Fonts::getActive();
$etag = md5(json_encode($fonts));
header('ETag: "' . $etag . '"');

// Verificar If-None-Match (validação de cache)
if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
    $clientEtag = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
    if ($clientEtag === $etag) {
        // Cache válido, retornar 304 Not Modified
        http_response_code(304);
        exit;
    }
}

// Gerar CSS
echo Fonts::generateAllFontFaces();

// Adicionar comentário com timestamp
echo "\n/* Gerado em: " . date('Y-m-d H:i:s') . " */\n";
echo "/* Total de fontes: " . count($fonts) . " */\n";
