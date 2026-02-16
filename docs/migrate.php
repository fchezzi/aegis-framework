<!DOCTYPE html>
<html>
<head>
    <title>Migration - Banners</title>
    <style>
        body { font-family: monospace; padding: 40px; background: #1a1a2e; color: #0f3; }
        h1 { color: #0f3; }
        .success { color: #0f3; font-size: 18px; font-weight: bold; }
        .error { color: #f00; font-size: 18px; font-weight: bold; }
        pre { background: #000; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🚀 Criando Tabela Banners</h1>
<?php
require_once '_config.php';

try {
    $pdo = new PDO(
        'mysql:unix_socket=' . DB_SOCKET . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
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

    echo '<p class="success">✓ Tabela banners criada com sucesso!</p>';
    echo '<pre>';
    echo 'Estrutura da tabela banners:' . "\n\n";
    echo '- id (CHAR 36 UUID)' . "\n";
    echo '- image (VARCHAR 255)' . "\n";
    echo '- title (VARCHAR 255)' . "\n";
    echo '- subtitle (VARCHAR 255 NULL)' . "\n";
    echo '- button_text (VARCHAR 255)' . "\n";
    echo '- button_url (VARCHAR 255)' . "\n";
    echo '- order (INT DEFAULT 0)' . "\n";
    echo '- ativo (TINYINT 1/0 DEFAULT 1)' . "\n";
    echo '- created_at (TIMESTAMP)' . "\n";
    echo '- updated_at (TIMESTAMP)' . "\n";
    echo '</pre>';

    // Deletar arquivo após sucesso
    @unlink(__FILE__);
    echo '<p style="color: #ff0;">⚠️ Este arquivo foi auto-deletado por segurança.</p>';

} catch (PDOException $e) {
    echo '<p class="error">✗ ERRO:</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
?>
</body>
</html>
