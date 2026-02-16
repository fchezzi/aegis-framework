<?php
/**
 * AEGIS Setup Wizard
 * Instalação completa em 1 arquivo
 */

// Configurar sessão ANTES de iniciar
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // 0 para desenvolvimento
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 1800);

// Iniciar sessão
session_start();

// =====================================
// AJAX: TESTAR CONEXÃO (ANTES DE QUALQUER OUTPUT)
// =====================================
if (isset($_GET['action']) && $_GET['action'] === 'test_connection') {
    // Autoloader
    require_once __DIR__ . '/core/Autoloader.php';
    Autoloader::register();

    header('Content-Type: application/json');

    try {
        $dbType = $_POST['db_type'] ?? '';

        if ($dbType === 'mysql') {
            $host = $_POST['db_host'] ?? 'localhost';
            $database = $_POST['db_name'] ?? '';
            $username = $_POST['db_user'] ?? '';
            $password = $_POST['db_pass'] ?? '';

            // Testar conexão SEM especificar banco (para permitir criação)
            $dsn = "mysql:host={$host};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Criar banco se não existir
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Testar se consegue usar o banco
            $pdo->exec("USE `{$database}`");

            // Importar schema (criar tabelas)
            $enableMembers = isset($_POST['enable_members']) && $_POST['enable_members'] == '1';
            $schemaFile = $enableMembers ? 'database/schemas/mysql-schema.sql' : 'database/schemas/mysql-schema-minimal.sql';

            if (file_exists(__DIR__ . '/' . $schemaFile)) {
                $sql = file_get_contents(__DIR__ . '/' . $schemaFile);

                // Remover comentários SQL
                $sql = preg_replace('/--.*$/m', '', $sql);
                $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

                // Dividir por ; e executar query por query
                $queries = array_filter(array_map('trim', explode(';', $sql)));

                foreach ($queries as $query) {
                    if (!empty($query)) {
                        try {
                            $pdo->exec($query);
                        } catch (PDOException $e) {
                            // Ignorar erros de "table already exists"
                            if (strpos($e->getMessage(), '1050') === false) {
                                throw $e;
                            }
                        }
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => '✅ Conexão MySQL bem-sucedida! Banco criado e tabelas importadas.'
            ]);

        } elseif ($dbType === 'supabase') {
            $url = $_POST['supabase_url'] ?? '';
            $key = $_POST['supabase_key'] ?? '';
            $enableMembers = isset($_POST['enable_members']) && $_POST['enable_members'] == '1';

            // Testar requisição à API
            $ch = curl_init($url . '/rest/v1/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apikey: ' . $key,
                'Authorization: Bearer ' . $key
            ]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception('Falha ao conectar. HTTP Code: ' . $httpCode);
            }

            // Conexão OK, agora validar tabelas
            // Configurar temporariamente para validação
            define('DB_TYPE', 'supabase');
            define('SUPABASE_URL', $url);
            define('SUPABASE_KEY', $key);

            $db = DB::connect();

            // Validar tabelas obrigatórias
            $requiredTables = ['users', 'security_tests', 'performance_tests', 'menu_items', 'pages', 'page_blocks', 'page_cards'];
            $missingTables = [];

            foreach ($requiredTables as $table) {
                if (!$db->tableExists($table)) {
                    $missingTables[] = $table;
                }
            }

            // Se membros habilitado, validar tabelas de membros
            if ($enableMembers) {
                $memberTables = ['members', 'groups', 'member_groups', 'page_permissions', 'member_page_permissions'];
                foreach ($memberTables as $table) {
                    if (!$db->tableExists($table)) {
                        $missingTables[] = $table;
                    }
                }
            }

            // Se faltarem tabelas, retornar SQL necessário
            if (!empty($missingTables)) {
                $sqlFile = __DIR__ . '/database/schemas/supabase-schema' . ($enableMembers ? '' : '-minimal') . '.sql';
                $sqlContent = file_get_contents($sqlFile);

                echo json_encode([
                    'success' => false,
                    'missing_tables' => true,
                    'tables' => $missingTables,
                    'sql' => $sqlContent,
                    'message' => 'Tabelas não encontradas: ' . implode(', ', $missingTables)
                ]);
            } else {
                // Tudo OK!
                echo json_encode([
                    'success' => true,
                    'message' => '✅ Conexão Supabase OK! Todas as tabelas encontradas.'
                ]);
            }

        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Modo sem banco - nada a testar'
            ]);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Falha: ' . $e->getMessage()
        ]);
    }

    exit;
}

// =====================================
// INICIALIZAÇÃO NORMAL (após AJAX)
// =====================================

// Autoloader
require_once __DIR__ . '/core/Autoloader.php';
Autoloader::register();

// Configurar ambiente
Core::configure();

// Rate limiting por IP (DESABILITADO TEMPORARIAMENTE PARA TESTES)
$setupKey = 'setup_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

// TEMPORÁRIO: Resetar rate limit para testes
if (isset($_GET['reset_limit'])) {
    unset($_SESSION['rate_limit'][$setupKey]);
    die(json_encode(['success' => true, 'message' => 'Rate limit resetado']));
}

// COMENTADO PARA TESTES - DESCOMENTAR EM PRODUÇÃO
// RateLimit::middleware($setupKey, 50, 600);

// Verificar se já está instalado
if (Core::isInstalled()) {
    Core::redirect('/');
}

// Processar form
$step = $_GET['step'] ?? '1';
$error = '';
$success = '';

// =====================================
// PROCESSAR INSTALAÇÃO
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar CSRF
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        $dbType = Security::sanitize($_POST['db_type'] ?? 'none');
        $enableMembers = isset($_POST['enable_members']) && $_POST['enable_members'] === '1';
        $adminName = Security::sanitize($_POST['admin_name'] ?? '');
        $adminEmail = Security::sanitize($_POST['admin_email'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';

        // Validações (apenas se não for "none")
        if ($dbType !== 'none') {
            if (empty($adminName) || empty($adminEmail) || empty($adminPassword)) {
                throw new Exception("Todos os campos são obrigatórios");
            }

            if (!Security::validateEmail($adminEmail)) {
                throw new Exception("Email inválido");
            }

            if ($adminPassword !== $adminPasswordConfirm) {
                throw new Exception("Senhas não conferem");
            }

            $passwordErrors = Security::validatePasswordStrength($adminPassword);
            if (!empty($passwordErrors)) {
                throw new Exception(implode(', ', $passwordErrors));
            }
        }

        // 1. Gerar _config.php
        $configData = [
            'DB_TYPE' => $dbType,
            'DB_HOST' => Security::sanitize($_POST['db_host'] ?? ''),
            'DB_NAME' => Security::sanitize($_POST['db_name'] ?? ''),
            'DB_USER' => Security::sanitize($_POST['db_user'] ?? ''),
            'DB_PASS' => $_POST['db_pass'] ?? '', // Não sanitizar senha
            'SUPABASE_URL' => Security::sanitize($_POST['supabase_url'] ?? ''),
            'SUPABASE_KEY' => $_POST['supabase_key'] ?? '', // Não sanitizar chave
            'APP_URL' => Security::sanitize($_POST['app_url'] ?? ''),
            'TINYMCE_API_KEY' => Security::sanitize($_POST['tinymce_api_key'] ?? 'no-api-key'),
            'ENVIRONMENT' => 'development',
            'ENABLE_MEMBERS' => $enableMembers ? 'true' : 'false' // Será substituído sem aspas no template
        ];

        // 1. Gerar arquivo de configuração (_config.php ou .env)
        if (!Core::generateConfig($configData)) {
            throw new Exception("Erro ao criar arquivo de configuração");
        }

        // 2. Recarregar _config.php para definir constantes
        if (file_exists(__DIR__ . '/_config.php')) {
            require __DIR__ . '/_config.php';
        }

        // 3. Conectar ao banco (agora as constantes estão definidas!)
        if ($dbType !== 'none') {
            try {
                $db = DB::connect();
            } catch (Exception $e) {
                throw new Exception("Erro ao conectar: " . $e->getMessage());
            }

            // 3. Tabelas já foram criadas no "Testar Conexão" via schema SQL
            // Não precisamos recriar

            // 4. Criar usuário admin
            /* BLOCO COMENTADO - Tabelas já criadas no teste de conexão
            if ($dbType === 'mysql') {
                // Tabela admins (users) - SEMPRE criada
                $sql = "CREATE TABLE IF NOT EXISTS users (
                    id CHAR(36) PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    ativo BOOLEAN DEFAULT true,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $db->execute($sql);

                // Tabela security_tests - SEMPRE criada
                $sql = "CREATE TABLE IF NOT EXISTS security_tests (
                    id CHAR(36) PRIMARY KEY,
                    score DECIMAL(5,2) NOT NULL,
                    status VARCHAR(20) NOT NULL,
                    message TEXT,
                    details JSON,
                    tested_at TIMESTAMP NOT NULL,
                    INDEX idx_tested_at (tested_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $db->execute($sql);

                // Tabela performance_tests - SEMPRE criada
                $sql = "CREATE TABLE IF NOT EXISTS performance_tests (
                    id CHAR(36) PRIMARY KEY,
                    score DECIMAL(5,2) NOT NULL,
                    status VARCHAR(20) NOT NULL,
                    message TEXT,
                    details JSON,
                    avg_response_time DECIMAL(8,2),
                    tested_at TIMESTAMP NOT NULL,
                    INDEX idx_tested_at (tested_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $db->execute($sql);

                // Sistema de membros (OPCIONAL)
                if ($enableMembers) {
                    // Tabela members (usuários do site)
                    $sql = "CREATE TABLE IF NOT EXISTS members (
                        id CHAR(36) PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        email VARCHAR(100) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        ativo BOOLEAN DEFAULT true,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_email (email),
                        INDEX idx_ativo (ativo)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    $db->execute($sql);

                    // Tabela groups
                    $sql = "CREATE TABLE IF NOT EXISTS groups (
                        id CHAR(36) PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    $db->execute($sql);

                    // Tabela member_groups
                    $sql = "CREATE TABLE IF NOT EXISTS member_groups (
                        id CHAR(36) PRIMARY KEY,
                        member_id CHAR(36) NOT NULL,
                        group_id CHAR(36) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
                        FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_member_group (member_id, group_id),
                        INDEX idx_member_id (member_id),
                        INDEX idx_group_id (group_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    $db->execute($sql);

                    // Tabela contents
                    $sql = "CREATE TABLE IF NOT EXISTS contents (
                        id CHAR(36) PRIMARY KEY,
                        title VARCHAR(200) NOT NULL,
                        slug VARCHAR(200) NOT NULL UNIQUE,
                        type ENUM('page', 'link', 'file', 'dashboard', 'video', 'other') DEFAULT 'page',
                        data LONGTEXT,
                        is_public BOOLEAN DEFAULT false,
                        ativo BOOLEAN DEFAULT true,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_slug (slug),
                        INDEX idx_type (type),
                        INDEX idx_is_public (is_public),
                        INDEX idx_ativo (ativo)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    $db->execute($sql);

                    // Tabela group_permissions
                    $sql = "CREATE TABLE IF NOT EXISTS group_permissions (
                        id CHAR(36) PRIMARY KEY,
                        group_id CHAR(36) NOT NULL,
                        content_id CHAR(36) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
                        FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_group_content (group_id, content_id),
                        INDEX idx_group_id (group_id),
                        INDEX idx_content_id (content_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    $db->execute($sql);

                    // Tabela member_permissions
                    $sql = "CREATE TABLE IF NOT EXISTS member_permissions (
                        id CHAR(36) PRIMARY KEY,
                        member_id CHAR(36) NOT NULL,
                        content_id CHAR(36) NOT NULL,
                        allow BOOLEAN DEFAULT true,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
                        FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_member_content (member_id, content_id),
                        INDEX idx_member_id (member_id),
                        INDEX idx_content_id (content_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    $db->execute($sql);
                }

                // Tabela menu_items - SEMPRE criada
                // Se members habilitado: com group_id e member_id
                // Se members desabilitado: sem essas colunas
                if ($enableMembers) {
                    // COM sistema de membros
                    $sql = "CREATE TABLE IF NOT EXISTS menu_items (
                        id VARCHAR(36) PRIMARY KEY,
                        label VARCHAR(255) NOT NULL,
                        type ENUM('page', 'link', 'category') NOT NULL DEFAULT 'page',
                        url VARCHAR(500) NULL,
                        page_slug VARCHAR(255) NULL,
                        icon VARCHAR(50) NULL,
                        parent_id VARCHAR(36) NULL,
                        ordem INT NOT NULL DEFAULT 0,
                        visible TINYINT(1) NOT NULL DEFAULT 1,
                        permission_type ENUM('public', 'authenticated', 'group', 'member') NOT NULL DEFAULT 'authenticated',
                        group_id VARCHAR(36) NULL,
                        member_id VARCHAR(36) NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
                        FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL,
                        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
                        INDEX idx_parent_id (parent_id),
                        INDEX idx_ordem (ordem),
                        INDEX idx_visible (visible),
                        INDEX idx_permission_type (permission_type)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                } else {
                    // SEM sistema de membros (minimal)
                    $sql = "CREATE TABLE IF NOT EXISTS menu_items (
                        id VARCHAR(36) PRIMARY KEY,
                        label VARCHAR(255) NOT NULL,
                        type ENUM('page', 'link', 'category') NOT NULL DEFAULT 'page',
                        url VARCHAR(500) NULL,
                        page_slug VARCHAR(255) NULL,
                        icon VARCHAR(50) NULL,
                        parent_id VARCHAR(36) NULL,
                        ordem INT NOT NULL DEFAULT 0,
                        visible TINYINT(1) NOT NULL DEFAULT 1,
                        permission_type ENUM('public', 'authenticated') NOT NULL DEFAULT 'public',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
                        INDEX idx_parent_id (parent_id),
                        INDEX idx_ordem (ordem),
                        INDEX idx_visible (visible),
                        INDEX idx_permission_type (permission_type)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                }
                $db->execute($sql);

                // Tabela page_blocks - SEMPRE criada (Page Builder)
                $sql = "CREATE TABLE IF NOT EXISTS page_blocks (
                    id VARCHAR(36) PRIMARY KEY,
                    page_slug VARCHAR(255) NOT NULL,
                    ordem INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_page_slug (page_slug),
                    INDEX idx_ordem (ordem)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $db->execute($sql);

                // Tabela page_cards - SEMPRE criada (Page Builder)
                $sql = "CREATE TABLE IF NOT EXISTS page_cards (
                    id VARCHAR(36) PRIMARY KEY,
                    block_id VARCHAR(36) NOT NULL,
                    size INT NOT NULL DEFAULT 1,
                    ordem INT NOT NULL DEFAULT 0,
                    content LONGTEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (block_id) REFERENCES page_blocks(id) ON DELETE CASCADE,
                    INDEX idx_block_id (block_id),
                    INDEX idx_ordem (ordem)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $db->execute($sql);

            }
            FIM DO BLOCO COMENTADO */

            // Tabela tbl_fonts - Sistema de fontes customizáveis (SEMPRE criada)
            if ($dbType === 'mysql') {
                $sql = "CREATE TABLE IF NOT EXISTS tbl_fonts (
                    id VARCHAR(36) PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    family VARCHAR(100) NOT NULL,
                    weight VARCHAR(10) DEFAULT 'normal',
                    style VARCHAR(10) DEFAULT 'normal',
                    filename VARCHAR(255) NOT NULL UNIQUE,
                    file_size INT,
                    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    active TINYINT(1) DEFAULT 1,
                    INDEX idx_family (family),
                    INDEX idx_active (active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $db->execute($sql);

                // Tabela tbl_cruds - Sistema de gerenciamento de CRUDs (SEMPRE criada)
                $sql = "CREATE TABLE IF NOT EXISTS tbl_cruds (
                    id VARCHAR(36) PRIMARY KEY,
                    name VARCHAR(255) NOT NULL COMMENT 'Nome humanizado',
                    table_name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nome da tabela',
                    controller_name VARCHAR(100) NOT NULL COMMENT 'Nome do controller',
                    route VARCHAR(100) NOT NULL UNIQUE COMMENT 'Rota base',
                    fields JSON NOT NULL COMMENT 'Array de campos',
                    has_ordering TINYINT(1) DEFAULT 0,
                    has_status TINYINT(1) DEFAULT 1,
                    has_slug TINYINT(1) DEFAULT 0,
                    slug_source VARCHAR(50) DEFAULT NULL,
                    has_frontend TINYINT(1) DEFAULT 0,
                    has_upload TINYINT(1) DEFAULT 0,
                    upload_config JSON DEFAULT NULL,
                    relationships JSON DEFAULT NULL,
                    status ENUM('draft', 'generated', 'active', 'inactive') DEFAULT 'draft',
                    generated_at TIMESTAMP NULL,
                    generated_files JSON DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_status (status),
                    INDEX idx_table (table_name),
                    INDEX idx_route (route)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $db->execute($sql);
            }

            // Criar usuário admin
            Auth::createUser($adminEmail, $adminPassword, $adminName, $db);
        }

        // 5. Sucesso
        $_SESSION['install_success'] = true;
        $_SESSION['installed_db_type'] = $dbType;
        session_regenerate_id(true);

        $step = 'success';
        $success = 'Instalação concluída com sucesso!';

    } catch (Exception $e) {
        $error = $e->getMessage();
        $step = '4'; // Voltar para formulário
    }
}

// CSRF Token para o form
$csrfToken = Security::generateCSRF();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AEGIS - Setup Wizard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .wizard-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }

        .wizard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .wizard-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .wizard-body {
            padding: 40px;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #f0f0f0;
            margin: 0 5px;
            border-radius: 6px;
            font-size: 12px;
            color: #666;
        }

        .step.active {
            background: #667eea;
            color: white;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .radio-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .radio-option input[type="radio"] {
            width: auto;
            margin-right: 8px;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }

        .success {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
        }

        .hidden {
            display: none;
        }

        .success-icon {
            font-size: 64px;
            text-align: center;
            margin: 30px 0;
        }

        .text-center {
            text-align: center;
        }

        small {
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-header">
            <h1>🛡️ AEGIS Framework</h1>
            <p>Setup Wizard</p>
        </div>

        <div class="wizard-body">
            <?php if ($error): ?>
                <div class="error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($step === 'success'): ?>
                <div class="success-icon">✅</div>
                <h2 class="text-center">Instalação Concluída!</h2>
                <p class="text-center" style="margin: 20px 0;">
                    AEGIS foi instalado com sucesso.<br>
                    Ambiente detectado: <strong><?= Core::environment() ?></strong>
                </p>
                <?php
                $installedDbType = $_SESSION['installed_db_type'] ?? 'mysql';
                if ($installedDbType === 'none'):
                ?>
                    <a href="<?= Router::url('/') ?>" class="btn">Acessar Site</a>
                <?php else: ?>
                    <a href="<?= Router::url('/admin/login') ?>" class="btn">Fazer Login</a>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST" action="?step=install">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <!-- STEP 1: Database Type -->
                    <div class="form-group">
                        <label>1. Escolha o tipo de banco de dados:</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="db_type" value="mysql" required>
                                <strong>MySQL</strong><br>
                                <small>Banco tradicional</small>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="db_type" value="supabase">
                                <strong>Supabase</strong><br>
                                <small>PostgreSQL cloud</small>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="db_type" value="none">
                                <strong>Nenhum</strong><br>
                                <small>Site estático</small>
                            </label>
                        </div>
                    </div>

                    <!-- STEP 1.5: Enable Members System -->
                    <div id="members-option" class="form-group hidden">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 15px; border: 2px solid #e0e0e0; border-radius: 6px;">
                            <input type="checkbox" name="enable_members" value="1" style="width: auto; margin: 0;">
                            <div>
                                <strong>Habilitar sistema de membros e permissões</strong><br>
                                <small>Login de usuários, grupos, permissões hierárquicas (OPCIONAL)</small>
                            </div>
                        </label>
                    </div>

                    <!-- STEP 2: MySQL Config -->
                    <div id="mysql-config" class="hidden">
                        <h3>2. Configuração MySQL</h3>
                        <div class="form-group">
                            <label>Host:</label>
                            <input type="text" name="db_host" value="localhost">
                        </div>
                        <div class="form-group">
                            <label>Database:</label>
                            <input type="text" name="db_name">
                        </div>
                        <div class="form-group">
                            <label>Usuário:</label>
                            <input type="text" name="db_user">
                        </div>
                        <div class="form-group">
                            <label>Senha:</label>
                            <input type="password" name="db_pass">
                        </div>
                        <button type="button" class="btn" onclick="testConnection()" style="background: #27ae60; margin-top: 10px;">
                            🔌 Testar Conexão MySQL
                        </button>
                        <div id="test-result-mysql" style="margin-top: 10px;"></div>
                    </div>

                    <!-- STEP 2: Supabase Config -->
                    <div id="supabase-config" class="hidden">
                        <h3>2. Configuração Supabase</h3>

                        <div class="form-group">
                            <label>Project URL:</label>
                            <input type="url" name="supabase_url" placeholder="https://xxxxx.supabase.co">
                        </div>
                        <div class="form-group">
                            <label>API Key (service_role):</label>
                            <input type="text" name="supabase_key" placeholder="eyJhbGc...">
                            <small style="color: #666;">Use a service_role key, não a anon key</small>
                        </div>
                        <button type="button" class="btn" onclick="testConnection()" style="background: #27ae60; margin-top: 10px;">
                            🔌 Testar Conexão Supabase
                        </button>
                        <div id="test-result-supabase" style="margin-top: 10px;"></div>
                    </div>

                    <!-- STEP 3: Admin User (Hidden for none) -->
                    <div id="admin-config" class="hidden">
                        <h3>3. Criar Usuário Administrador</h3>
                        <div class="form-group">
                            <label>Nome:</label>
                            <input type="text" name="admin_name" id="admin_name">
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="admin_email" id="admin_email">
                        </div>
                        <div class="form-group">
                            <label>Senha:</label>
                            <input type="password" name="admin_password" id="admin_password">
                            <small>Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 especial</small>
                        </div>
                        <div class="form-group">
                            <label>Confirmar Senha:</label>
                            <input type="password" name="admin_password_confirm" id="admin_password_confirm">
                        </div>
                    </div>

                    <!-- STEP 4: App URL -->
                    <div class="form-group">
                        <label>4. URL da Aplicação:</label>
                        <?php
                        // Detectar URL completa com subpasta
                        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'];
                        $path = dirname($_SERVER['REQUEST_URI']);
                        // Remover /setup.php se existir
                        $path = str_replace('/setup.php', '', $path);
                        $fullUrl = $protocol . '://' . $host . $path;
                        ?>
                        <input type="url" name="app_url" value="<?= $fullUrl ?>" required>
                        <small>Esta URL será usada para redirects. Verifique se está correta.</small>
                    </div>

                    <button type="submit" class="btn">Instalar AEGIS</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle config sections based on database type
        document.querySelectorAll('input[name="db_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('mysql-config').classList.add('hidden');
                document.getElementById('supabase-config').classList.add('hidden');
                document.getElementById('members-option').classList.add('hidden');
                document.getElementById('admin-config').classList.add('hidden');

                // Limpar resultados de teste
                document.getElementById('test-result-mysql').innerHTML = '';
                document.getElementById('test-result-supabase').innerHTML = '';

                if (this.value === 'mysql') {
                    document.getElementById('mysql-config').classList.remove('hidden');
                    document.getElementById('members-option').classList.remove('hidden');
                    // MySQL: admin fica escondido até testar conexão (igual Supabase)
                    document.querySelectorAll('#mysql-config input').forEach(i => i.required = true);
                    document.querySelectorAll('#supabase-config input').forEach(i => i.required = false);
                    document.querySelectorAll('#admin-config input').forEach(i => i.required = false);
                } else if (this.value === 'supabase') {
                    document.getElementById('supabase-config').classList.remove('hidden');
                    document.getElementById('members-option').classList.remove('hidden');
                    // Supabase: admin fica escondido até testar conexão
                    document.querySelectorAll('#supabase-config input').forEach(i => i.required = true);
                    document.querySelectorAll('#mysql-config input').forEach(i => i.required = false);
                    document.querySelectorAll('#admin-config input').forEach(i => i.required = false);
                } else {
                    // Sem banco = sem admin e sem membros
                    document.querySelectorAll('#mysql-config input').forEach(i => i.required = false);
                    document.querySelectorAll('#supabase-config input').forEach(i => i.required = false);
                    document.querySelectorAll('#admin-config input').forEach(i => i.required = false);
                }
            });
        });

        // Testar conexão via AJAX
        function testConnection() {
            const form = document.querySelector('form');
            const formData = new FormData(form);
            const dbType = formData.get('db_type');
            const resultDiv = dbType === 'mysql' ? 'test-result-mysql' : 'test-result-supabase';

            // Loading
            document.getElementById(resultDiv).innerHTML = '<div style="color: #3498db;">⏳ Testando conexão...</div>';

            // AJAX
            fetch('?action=test_connection', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(resultDiv).innerHTML =
                        '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; border: 1px solid #c3e6cb;">' +
                        data.message + '</div>';

                    // Mostrar seção de admin após conexão bem-sucedida
                    document.getElementById('admin-config').classList.remove('hidden');
                    document.querySelectorAll('#admin-config input').forEach(i => i.required = true);
                } else if (data.missing_tables) {
                    // Tabelas faltando - mostrar SQL
                    document.getElementById(resultDiv).innerHTML =
                        '<div style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px;">' +
                        '<h3 style="margin-top: 0;">⚠️ Tabelas não encontradas</h3>' +
                        '<p><strong>Faltando:</strong> ' + data.tables.join(', ') + '</p>' +
                        '<p><strong>Execute o SQL abaixo no Supabase SQL Editor:</strong></p>' +
                        '<ol style="margin: 15px 0; padding-left: 20px;">' +
                        '<li>Copie o SQL (botão Copiar)</li>' +
                        '<li>Acesse: <a href="https://supabase.com/dashboard" target="_blank">Supabase Dashboard</a></li>' +
                        '<li>Menu: <strong>SQL Editor</strong></li>' +
                        '<li>Cole e execute (RUN)</li>' +
                        '<li>Volte aqui e clique em "Testar Conexão" novamente</li>' +
                        '</ol>' +
                        '<textarea id="supabase-sql-inline" readonly style="width: 100%; height: 250px; font-family: monospace; font-size: 11px; padding: 10px; border: 2px solid #ddd; border-radius: 6px; background: #f8f9fa; margin: 10px 0;">' +
                        data.sql +
                        '</textarea>' +
                        '<button type="button" onclick="copySQLInline()" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">📋 Copiar SQL</button>' +
                        '</div>';

                    // NÃO mostrar admin
                    document.getElementById('admin-config').classList.add('hidden');
                } else {
                    document.getElementById(resultDiv).innerHTML =
                        '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; border: 1px solid #f5c6cb;">' +
                        data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById(resultDiv).innerHTML =
                    '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; border: 1px solid #f5c6cb;">' +
                    '❌ Erro ao testar: ' + error.message + '</div>';
            });
        }

        // Copiar SQL inline (quando mostrado no teste de conexão)
        function copySQLInline() {
            const textarea = document.getElementById('supabase-sql-inline');
            if (textarea) {
                textarea.select();
                document.execCommand('copy');
                alert('✅ SQL copiado! Cole no Supabase SQL Editor e execute.');
            }
        }
    </script>
</body>
</html>
