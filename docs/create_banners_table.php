<?php
require_once '_config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
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
    echo '<h1 style="color: green;">✓ Tabela banners criada com sucesso!</h1>';
    echo '<h2>Estrutura:</h2>';
    echo '<ul>';
    echo '<li>id (CHAR 36 UUID) - Primary Key</li>';
    echo '<li>image (VARCHAR 255) - Caminho da imagem</li>';
    echo '<li>title (VARCHAR 255) - Título principal</li>';
    echo '<li>subtitle (VARCHAR 255 NULL) - Subtítulo opcional</li>';
    echo '<li>button_text (VARCHAR 255) - Texto do botão CTA</li>';
    echo '<li>button_url (VARCHAR 255) - URL do botão CTA</li>';
    echo '<li>order (INT DEFAULT 0) - Ordem de exibição</li>';
    echo '<li>ativo (TINYINT 1/0 DEFAULT 1) - Status ativo/inativo</li>';
    echo '<li>created_at (TIMESTAMP) - Data de criação</li>';
    echo '<li>updated_at (TIMESTAMP) - Data de atualização</li>';
    echo '</ul>';

} catch (PDOException $e) {
    echo '<h1 style="color: red;">✗ ERRO:</h1>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
