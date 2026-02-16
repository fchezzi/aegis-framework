<?php
require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';

Auth::require();
$user = Auth::user();

$results = [];
$totalTests = 0;
$passedTests = 0;

// ========================================
// CORE FRAMEWORK TESTS
// ========================================

$results['core'] = [
    'name' => 'Core Framework',
    'tests' => []
];

// Test 1: Database Connection
try {
    $db = DB::connect();
    $testQuery = $db->select('users', [], 1);
    $results['core']['tests'][] = [
        'name' => 'Conexao com Banco de Dados',
        'status' => true,
        'message' => 'Conectado (' . DB_TYPE . ')'
    ];
    $totalTests++;
    $passedTests++;
} catch (Exception $e) {
    $results['core']['tests'][] = [
        'name' => 'Conexao com Banco de Dados',
        'status' => false,
        'message' => 'ERRO: ' . $e->getMessage()
    ];
    $totalTests++;
}

// Test 2: Cache System
try {
    $testKey = 'health_check_' . time();
    $testData = ['test' => true, 'timestamp' => time()];

    Cache::set($testKey, $testData, 60);
    $cached = Cache::get($testKey);
    $cacheWorks = !empty($cached) && $cached['test'] === true;
    Cache::delete($testKey);

    $results['core']['tests'][] = [
        'name' => 'Sistema de Cache',
        'status' => $cacheWorks,
        'message' => $cacheWorks ? 'Funcionando (read/write OK)' : 'ERRO: Cache nao funciona'
    ];
    $totalTests++;
    if ($cacheWorks) $passedTests++;
} catch (Exception $e) {
    $results['core']['tests'][] = [
        'name' => 'Sistema de Cache',
        'status' => false,
        'message' => 'ERRO: ' . $e->getMessage()
    ];
    $totalTests++;
}

// Test 3: Storage Permissions
$cacheWritable = is_writable(CACHE_PATH);
$uploadsWritable = is_writable(ROOT_PATH . 'storage/uploads/');
$logsWritable = is_writable(ROOT_PATH . 'storage/logs/');
$allStorageOK = $cacheWritable && $uploadsWritable && $logsWritable;

$storageMessage = $allStorageOK ? 'Todas as pastas com permissões OK' :
    (!$cacheWritable ? 'ERRO: storage/cache/ sem permissão' :
    (!$uploadsWritable ? 'ERRO: storage/uploads/ sem permissão' :
    'ERRO: storage/logs/ sem permissão'));

$results['core']['tests'][] = [
    'name' => 'Permissões de Escrita (Storage)',
    'status' => $allStorageOK,
    'message' => $storageMessage
];
$totalTests++;
if ($allStorageOK) $passedTests++;

// Test 4: Security - CSRF
try {
    $token = Security::generateCSRF();
    Security::validateCSRF($token);

    $results['core']['tests'][] = [
        'name' => 'Protecao CSRF',
        'status' => true,
        'message' => 'CSRF tokens funcionando'
    ];
    $totalTests++;
    $passedTests++;
} catch (Exception $e) {
    $results['core']['tests'][] = [
        'name' => 'Protecao CSRF',
        'status' => false,
        'message' => 'ERRO: ' . $e->getMessage()
    ];
    $totalTests++;
}

// Test 5: Rate Limiting
$testKey = 'health_ratelimit_' . uniqid();
RateLimit::reset($testKey);

// Simula 5 requests (limite)
$allowed = true;
for ($i = 0; $i < 5; $i++) {
    if (!RateLimit::check($testKey, 5, 60)) {
        $allowed = false;
        break;
    }
}

// 6ª request deve ser bloqueada
$blocked = !RateLimit::check($testKey, 5, 60);

$rateLimitWorks = $allowed && $blocked;
$results['core']['tests'][] = [
    'name' => 'Rate Limiting',
    'status' => $rateLimitWorks,
    'message' => $rateLimitWorks ? 'Bloqueio de spam funcionando (5 req OK, 6ª bloqueada)' : 'ERRO: Rate limit não funciona - verificar RateLimit::check()'
];
$totalTests++;
if ($rateLimitWorks) $passedTests++;

RateLimit::reset($testKey);

// Test 6: Session System
$testValue = 'health_' . uniqid();
$_SESSION['health_test'] = $testValue;
$sessionWorks = isset($_SESSION['health_test']) && $_SESSION['health_test'] === $testValue;
unset($_SESSION['health_test']);

$results['core']['tests'][] = [
    'name' => 'Sistema de Sessão',
    'status' => $sessionWorks,
    'message' => $sessionWorks ? 'Sessões funcionando' : 'ERRO: Sessões não funcionam - verificar session_start()'
];
$totalTests++;
if ($sessionWorks) $passedTests++;

// Test 7: Core Classes Existence
$coreClasses = ['DB', 'Auth', 'Security', 'Router', 'Cache', 'Core', 'RateLimit', 'Logger', 'Upload'];
$missingClasses = [];
foreach ($coreClasses as $class) {
    if (!class_exists($class)) {
        $missingClasses[] = $class;
    }
}

$allClassesExist = empty($missingClasses);
$results['core']['tests'][] = [
    'name' => 'Classes Core do Framework',
    'status' => $allClassesExist,
    'message' => $allClassesExist ? 'Todas as classes essenciais carregadas' : 'ERRO: Classes faltando - ' . implode(', ', $missingClasses) . ' (verificar Autoloader)'
];
$totalTests++;
if ($allClassesExist) $passedTests++;

// Test 8: URL Rewrite (.htaccess)
$htaccessExists = file_exists(ROOT_PATH . '.htaccess');
$htaccessReadable = $htaccessExists && is_readable(ROOT_PATH . '.htaccess');

// Verificar se contém regra de rewrite básica
$rewriteConfigured = false;
if ($htaccessReadable) {
    $htaccessContent = file_get_contents(ROOT_PATH . '.htaccess');
    $rewriteConfigured = strpos($htaccessContent, 'RewriteEngine') !== false;
}

$rewriteOK = $htaccessExists && $rewriteConfigured;
$results['core']['tests'][] = [
    'name' => 'URL Rewrite (.htaccess)',
    'status' => $rewriteOK,
    'message' => !$htaccessExists ? 'ERRO: .htaccess não encontrado' :
                 (!$rewriteConfigured ? 'ERRO: RewriteEngine não configurado' :
                 '.htaccess configurado corretamente')
];
$totalTests++;
if ($rewriteOK) $passedTests++;

// Test 9: PHP Version
$phpVersion = phpversion();
$phpOK = version_compare($phpVersion, '7.4.0', '>=');

$results['core']['tests'][] = [
    'name' => 'Versão do PHP',
    'status' => $phpOK,
    'message' => "PHP $phpVersion " . ($phpOK ? '(compatível com AEGIS)' : '(INCOMPATÍVEL - AEGIS requer PHP 7.4+)')
];
$totalTests++;
if ($phpOK) $passedTests++;

// Test 10: PHP Extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl', 'fileinfo', 'openssl'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

$allExtensionsLoaded = empty($missingExtensions);
$results['core']['tests'][] = [
    'name' => 'Extensões PHP Necessárias',
    'status' => $allExtensionsLoaded,
    'message' => $allExtensionsLoaded ? 'Todas as extensões instaladas' : 'ERRO: Extensões faltando - ' . implode(', ', $missingExtensions) . ' (instalar via php.ini)'
];
$totalTests++;
if ($allExtensionsLoaded) $passedTests++;

// Test 11: Email System Configuration
$emailConfigured = false;
$emailMessage = 'Email não configurado';
$missingSmtp = [];

if (class_exists('Email')) {
    // Valida todas as 4 constantes obrigatórias
    if (!defined('SMTP_HOST') || empty(SMTP_HOST)) $missingSmtp[] = 'SMTP_HOST';
    if (!defined('SMTP_PORT') || empty(SMTP_PORT)) $missingSmtp[] = 'SMTP_PORT';
    if (!defined('SMTP_USERNAME') || empty(SMTP_USERNAME)) $missingSmtp[] = 'SMTP_USERNAME';
    if (!defined('SMTP_PASSWORD') || empty(SMTP_PASSWORD)) $missingSmtp[] = 'SMTP_PASSWORD';

    $emailConfigured = empty($missingSmtp);
    $emailMessage = $emailConfigured ?
        'SMTP configurado (' . SMTP_HOST . ':' . SMTP_PORT . ') - configuração OK (não testa envio real)' :
        'ERRO: Constantes faltando - ' . implode(', ', $missingSmtp);
} else {
    $emailMessage = 'ERRO: Classe Email não encontrada';
}

$results['core']['tests'][] = [
    'name' => 'Configuração de Email',
    'status' => $emailConfigured,
    'message' => $emailMessage
];
$totalTests++;
if ($emailConfigured) $passedTests++;

// Test 12: Composer Dependencies
$composerOK = false;
$composerMessage = 'Composer não configurado';

if (file_exists(ROOT_PATH . 'composer.json')) {
    $vendorAutoload = file_exists(ROOT_PATH . 'vendor/autoload.php');
    $composerOK = $vendorAutoload;
    $composerMessage = $vendorAutoload ?
        'composer.json e vendor/ OK' :
        'ATENÇÃO: composer.json existe mas vendor/ não encontrado - rodar composer install';
} else {
    $composerMessage = 'AVISO: composer.json não encontrado (ok se não usar dependências)';
}

$results['core']['tests'][] = [
    'name' => 'Composer Dependencies',
    'status' => $composerOK,
    'message' => $composerMessage
];
$totalTests++;
if ($composerOK) $passedTests++;

// Test 13: Module Metadata Validation
$installedMods = ModuleManager::getInstalled();
$invalidModules = [];

foreach ($installedMods as $moduleName) {
    $metadataPath = ROOT_PATH . 'modules/' . $moduleName . '/metadata.json';

    if (!file_exists($metadataPath)) {
        $invalidModules[] = $moduleName . ' (sem metadata.json)';
        continue;
    }

    $metadata = json_decode(file_get_contents($metadataPath), true);
    if (!$metadata) {
        $invalidModules[] = $moduleName . ' (JSON inválido)';
        continue;
    }

    $requiredFields = ['name', 'version', 'description', 'author'];
    foreach ($requiredFields as $field) {
        if (!isset($metadata[$field])) {
            $invalidModules[] = $moduleName . " (falta campo '$field')";
            break;
        }
    }
}

$allModulesValid = empty($invalidModules);
$results['core']['tests'][] = [
    'name' => 'Metadados dos Módulos',
    'status' => $allModulesValid,
    'message' => $allModulesValid ?
        count($installedMods) . ' módulo(s) com metadata válido' :
        'ERRO: Módulos com problemas - ' . implode(', ', $invalidModules)
];
$totalTests++;
if ($allModulesValid) $passedTests++;

// ========================================
// PERFORMANCE METRICS
// ========================================

$results['performance'] = [
    'name' => 'Performance',
    'tests' => []
];

// Test 1: Query Performance
$queryStart = microtime(true);
$db->select('users', [], 1);
$queryTime = (microtime(true) - $queryStart) * 1000;

$limitQuery = DB_TYPE === 'supabase' ? 500 : 100;
$results['performance']['tests'][] = [
    'name' => 'Tempo de Query (SELECT)',
    'status' => $queryTime < $limitQuery,
    'message' => sprintf('%.2fms %s', $queryTime, $queryTime < $limitQuery ? '(rapido)' : '(LENTO)')
];
$totalTests++;
if ($queryTime < $limitQuery) $passedTests++;

// Test 2: Memory Usage
$memoryMB = memory_get_usage() / 1024 / 1024;
$results['performance']['tests'][] = [
    'name' => 'Uso de Memoria',
    'status' => $memoryMB < 50,
    'message' => sprintf('%.2f MB %s', $memoryMB, $memoryMB < 50 ? '(normal)' : '(ALTO)')
];
$totalTests++;
if ($memoryMB < 50) $passedTests++;

// Test 3: Disk Space
$freeSpace = disk_free_space(ROOT_PATH);
$totalSpace = disk_total_space(ROOT_PATH);
$freeGB = round($freeSpace / 1024 / 1024 / 1024, 2);
$totalGB = round($totalSpace / 1024 / 1024 / 1024, 2);
$usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 1);
$diskOK = $freeGB >= 1;

$results['performance']['tests'][] = [
    'name' => 'Espaço em Disco',
    'status' => $diskOK,
    'message' => "$freeGB GB livres de $totalGB GB (uso: $usedPercent%) " . ($diskOK ? '(suficiente)' : '(CRÍTICO: <1GB livre)')
];
$totalTests++;
if ($diskOK) $passedTests++;

// Test 4: Database Size
$dbSize = 0;
$dbSizeMessage = '';

if (DB_TYPE === 'mysql') {
    $sizeQuery = $db->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
        FROM information_schema.TABLES
        WHERE table_schema = '" . DB_NAME . "'
    ");
    $dbSize = $sizeQuery[0]['size_mb'] ?? 0;
    $dbSizeMessage = "$dbSize MB (MySQL)";
} else {
    // Supabase: estimativa pelo count de tabelas principais
    $dbSizeMessage = "N/A (Supabase gerenciado)";
}

$dbSizeOK = DB_TYPE === 'supabase' || $dbSize < 500;

$results['performance']['tests'][] = [
    'name' => 'Tamanho do Banco de Dados',
    'status' => $dbSizeOK,
    'message' => $dbSizeMessage . ($dbSizeOK ? '' : ' (ATENÇÃO: >500MB)')
];
$totalTests++;
if ($dbSizeOK) $passedTests++;

// ========================================
// MODULE-SPECIFIC TESTS
// ========================================

$installedModules = ModuleManager::getInstalled();

foreach ($installedModules as $moduleName) {
    $results[$moduleName] = [
        'name' => 'Modulo: ' . ucfirst($moduleName),
        'tests' => []
    ];

    // PALPITES MODULE
    if ($moduleName === 'palpites') {
        // Test 1: Ranking VIEW exists and works
        try {
            $rankingTest = $db->query("SELECT COUNT(*) as total FROM vw_ranking_palpiteiros");
            $count = $rankingTest[0]['total'] ?? 0;

            $results[$moduleName]['tests'][] = [
                'name' => 'View de Ranking (vw_ranking_palpiteiros)',
                'status' => true,
                'message' => "$count palpiteiros no ranking"
            ];
            $totalTests++;
            $passedTests++;
        } catch (Exception $e) {
            $results[$moduleName]['tests'][] = [
                'name' => 'View de Ranking (vw_ranking_palpiteiros)',
                'status' => false,
                'message' => 'ERRO: View nao existe - execute mysql-schema.sql'
            ];
            $totalTests++;
        }

        // Test 2: API response time
        try {
            $apiStart = microtime(true);

            // Simulate API logic (without HTTP request)
            $jogoAtivo = $db->query("
                SELECT id FROM tbl_jogos_palpites
                WHERE ativo = true
                ORDER BY data_jogo DESC
                LIMIT 1
            ");

            $ranking = $db->query("
                SELECT palpiteiro_id, total_pontos
                FROM vw_ranking_palpiteiros
                ORDER BY total_pontos DESC
                LIMIT 10
            ");

            $apiTime = (microtime(true) - $apiStart) * 1000;

            $results[$moduleName]['tests'][] = [
                'name' => 'Performance do Ranking',
                'status' => $apiTime < 200,
                'message' => sprintf('%.2fms %s', $apiTime, $apiTime < 200 ? '(OK)' : '(LENTO - considere otimizar)')
            ];
            $totalTests++;
            if ($apiTime < 200) $passedTests++;
        } catch (Exception $e) {
            $results[$moduleName]['tests'][] = [
                'name' => 'Performance do Ranking',
                'status' => false,
                'message' => 'ERRO: ' . $e->getMessage()
            ];
            $totalTests++;
        }

        // Test 3: Essential tables exist
        $essentialTables = [
            'tbl_palpiteiros',
            'tbl_jogos_palpites',
            'tbl_palpites',
            'tbl_resultados_reais'
        ];

        $missingTables = [];
        foreach ($essentialTables as $table) {
            try {
                $db->query("SELECT 1 FROM $table LIMIT 1");
            } catch (Exception $e) {
                $missingTables[] = $table;
            }
        }

        $results[$moduleName]['tests'][] = [
            'name' => 'Tabelas Essenciais',
            'status' => empty($missingTables),
            'message' => empty($missingTables)
                ? 'Todas as tabelas presentes'
                : 'Faltando: ' . implode(', ', $missingTables)
        ];
        $totalTests++;
        if (empty($missingTables)) $passedTests++;
    }

    // Add tests for other modules here...
}

// Calculate score
$score = $totalTests > 0 ? round(($passedTests / $totalTests) * 100) : 0;
$scoreClass = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Health Check - <?= ADMIN_NAME ?></title>
  <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
  <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/includes/header.php'; ?>

  <main class="m-pagebase">

    <div class="m-pagebase__header">
      <h1>Health Check</h1>
      <a href="<?= url('/admin/health') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
        <i data-lucide="refresh-cw"></i> Executar Novamente
      </a>
    </div>

    <!-- SCORE GERAL -->
    <div class="m-pagebase__card health-score health-score--<?= $scoreClass ?>">
      <div class="health-score__number"><?= $score ?>%</div>
      <div class="health-score__label">
        <?php if ($score >= 80): ?>
          Sistema Saudável
        <?php elseif ($score >= 50): ?>
          Sistema com Alertas
        <?php else: ?>
          Sistema Crítico
        <?php endif; ?>
      </div>
      <div class="health-score__detail">
        <i data-lucide="check-circle"></i> <?= $passedTests ?> de <?= $totalTests ?> testes passaram
      </div>
    </div>

    <!-- RESULTADOS POR CATEGORIA -->
    <?php foreach ($results as $category => $data): ?>
      <div class="m-pagebase__card">
        <h2 class="health-section__title"><?= htmlspecialchars($data['name']) ?></h2>

        <div class="health-tests">
          <?php foreach ($data['tests'] as $test): ?>
            <div class="health-test health-test--<?= $test['status'] ? 'success' : 'error' ?>">
              <div class="health-test__content">
                <div class="health-test__name">
                  <i data-lucide="<?= $test['status'] ? 'check-circle' : 'alert-circle' ?>"></i>
                  <?= htmlspecialchars($test['name']) ?>
                </div>
                <div class="health-test__message"><?= htmlspecialchars($test['message']) ?></div>
              </div>
              <span class="health-test__badge health-test__badge--<?= $test['status'] ? 'success' : 'error' ?>">
                <?= $test['status'] ? 'OK' : 'ERRO' ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>
</body>
</html>
