<?php
/**
 * DEBUG: Verificar PHP_BINARY no ambiente web
 */

echo "<h1>DEBUG: PHP_BINARY</h1>";
echo "<pre>";

echo "PHP_BINARY definido: " . (defined('PHP_BINARY') ? 'SIM' : 'NÃO') . "\n";
echo "PHP_BINARY valor: '" . (defined('PHP_BINARY') ? PHP_BINARY : 'INDEFINIDO') . "'\n";
echo "PHP_BINARY vazio: " . (defined('PHP_BINARY') && empty(PHP_BINARY) ? 'SIM' : 'NÃO') . "\n";
echo "\n";

echo "PHP_VERSION: " . PHP_VERSION . "\n";
echo "php_sapi_name(): " . php_sapi_name() . "\n";
echo "\n";

// Testar comando exec
$testFile = tempnam(sys_get_temp_dir(), 'test_');
file_put_contents($testFile, "<?php echo 'ok';");

echo "--- TESTE 1: Usando PHP_BINARY ---\n";
if (defined('PHP_BINARY') && !empty(PHP_BINARY)) {
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($testFile) . ' 2>&1';
    echo "Comando: $cmd\n";
    exec($cmd, $output1, $returnCode1);
    echo "Return code: $returnCode1\n";
    echo "Output: " . implode("\n", $output1) . "\n\n";
} else {
    echo "PHP_BINARY está vazio ou indefinido!\n\n";
}

echo "--- TESTE 2: Usando 'php' direto ---\n";
$cmd2 = 'php -l ' . escapeshellarg($testFile) . ' 2>&1';
echo "Comando: $cmd2\n";
exec($cmd2, $output2, $returnCode2);
echo "Return code: $returnCode2\n";
echo "Output: " . implode("\n", $output2) . "\n\n";

echo "--- TESTE 3: Tentando encontrar PHP via which ---\n";
exec('which php 2>&1', $output3, $returnCode3);
echo "Return code: $returnCode3\n";
echo "PHP encontrado em: " . implode("\n", $output3) . "\n\n";

unlink($testFile);

echo "--- TESTE 4: Usando caminho MAMP (se existir) ---\n";
$mampPaths = [
    '/Applications/MAMP/bin/php/php8.2.0/bin/php',
    '/Applications/MAMP/bin/php/php8.1.0/bin/php',
    '/Applications/MAMP/bin/php/php8.0.0/bin/php',
    '/Applications/MAMP/bin/php/php7.4.33/bin/php',
];

foreach ($mampPaths as $mampPhp) {
    if (file_exists($mampPhp)) {
        echo "ENCONTRADO: $mampPhp\n";
        $testFile2 = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($testFile2, "<?php echo 'ok';");
        $cmd3 = escapeshellarg($mampPhp) . ' -l ' . escapeshellarg($testFile2) . ' 2>&1';
        exec($cmd3, $output4, $returnCode4);
        echo "Return code: $returnCode4\n";
        echo "Output: " . implode("\n", $output4) . "\n";
        unlink($testFile2);
        break;
    }
}

echo "</pre>";
