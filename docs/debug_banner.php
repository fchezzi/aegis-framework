<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Carregando _config.php...<br>";
require_once '_config.php';

echo "2. Carregando Autoloader...<br>";
require_once 'core/Autoloader.php';

echo "3. Testando se BannerController existe...<br>";
if (class_exists('BannerController')) {
    echo "✓ BannerController encontrado<br><br>";
} else {
    die("✗ BannerController NÃO encontrado<br>");
}

echo "4. Testando se BaseController existe...<br>";
if (class_exists('BaseController')) {
    echo "✓ BaseController encontrado<br><br>";
} else {
    die("✗ BaseController NÃO encontrado<br>");
}

echo "5. Tentando instanciar BannerController...<br>";
try {
    $controller = new BannerController();
    echo "✓ BannerController instanciado<br><br>";
} catch (Exception $e) {
    echo "✗ ERRO ao instanciar: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

echo "6. Testando método db()...<br>";
try {
    $db = $controller->db();
    echo "✓ db() retornou objeto<br><br>";
} catch (Exception $e) {
    echo "✗ ERRO no db(): " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

echo "7. Testando query na tabela banners...<br>";
try {
    $result = $db->query("SELECT * FROM banners");
    echo "✓ Query executada. Resultado: " . (is_array($result) ? count($result) . " registros" : "NULL") . "<br><br>";
} catch (Exception $e) {
    echo "✗ ERRO na query: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

echo "<h2 style='color: green;'>✓ TUDO FUNCIONANDO!</h2>";
echo "<p>O problema não está no controller ou no banco.</p>";
echo "<p>Testando view agora...</p>";

echo "8. Testando include da view...<br>";
try {
    $banners = is_array($result) ? $result : [];
    $viewPath = __DIR__ . '/admin/views/banners/index.php';
    echo "View path: " . $viewPath . "<br>";
    
    if (file_exists($viewPath)) {
        echo "✓ Arquivo existe<br><br>";
        echo "9. Tentando fazer require...<br>";
        require $viewPath;
    } else {
        echo "✗ Arquivo NÃO existe no caminho: " . $viewPath . "<br>";
    }
} catch (Exception $e) {
    echo "✗ ERRO no require: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
