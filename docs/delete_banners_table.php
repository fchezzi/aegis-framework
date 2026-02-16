<?php
require_once '_config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec("DROP TABLE IF EXISTS banners");
    echo '<h1 style="color: green;">✓ Tabela banners deletada com sucesso!</h1>';

} catch (PDOException $e) {
    echo '<h1 style="color: red;">✗ ERRO:</h1>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
