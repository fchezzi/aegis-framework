<?php
// Script temporário para criar tabela banners

$host = 'localhost';
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
$dbname = 'aegis';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO(
        "mysql:unix_socket={$socket};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = "CREATE TABLE IF NOT EXISTS banners (
        id CHAR(36) PRIMARY KEY,
        image VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(255) DEFAULT NULL,
        button_text VARCHAR(255) NOT NULL,
        button_url VARCHAR(255) NOT NULL,
        `order` INT NOT NULL DEFAULT 0,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_order (`order`),
        INDEX idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✓ Tabela banners criada!\n";

} catch (PDOException $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
