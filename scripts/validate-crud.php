<?php
/**
 * Validador Automático de CRUD AEGIS
 *
 * Verifica se um controller Admin tem todos os elementos obrigatórios
 * de segurança, auditoria e estrutura definidos no GUIA-PRATICO.md
 *
 * Uso:
 *   php scripts/validate-crud.php BannerController
 *   php scripts/validate-crud.php CategoryController
 *
 * Retorno:
 *   Exit 0 = CRUD válido (100%)
 *   Exit 1 = CRUD incompleto (< 100%)
 */

// ================================================
// SETUP
// ================================================

if (php_sapi_name() !== 'cli') {
    die("Este script deve ser executado via CLI\n");
}

if ($argc < 2) {
    echo "Uso: php scripts/validate-crud.php ControllerName\n";
    echo "Exemplo: php scripts/validate-crud.php BannerController\n";
    exit(1);
}

$controllerName = $argv[1];

// Remove .php se foi passado
$controllerName = str_replace('.php', '', $controllerName);

// Define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

$controllerPath = ROOT_PATH . "admin/controllers/{$controllerName}.php";

// ================================================
// VERIFICAR SE ARQUIVO EXISTE
// ================================================

if (!file_exists($controllerPath)) {
    echo "❌ Controller não encontrado: {$controllerPath}\n";
    echo "\nVerifique:\n";
    echo "1. Nome correto? (case-sensitive)\n";
    echo "2. Arquivo existe em /admin/controllers/?\n";
    exit(1);
}

// ================================================
// LER CONTEÚDO
// ================================================

$content = file_get_contents($controllerPath);

if (empty($content)) {
    echo "❌ Arquivo vazio: {$controllerPath}\n";
    exit(1);
}

// ================================================
// CHECKLIST DE VALIDAÇÃO
// ================================================

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  VALIDADOR DE CRUD AEGIS\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Controller: {$controllerName}\n";
echo "Path: {$controllerPath}\n";
echo "Size: " . strlen($content) . " bytes\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$checks = [];

// ================================================
// 1. ESTRUTURA BÁSICA
// ================================================

echo "🏗️  ESTRUTURA BÁSICA\n";
echo "───────────────────────────────────────────────────────────────\n";

// 1.1 - Herança de BaseController
$checks['extends_base_controller'] = preg_match('/class\s+\w+\s+extends\s+BaseController/', $content);
echo ($checks['extends_base_controller'] ? '✅' : '❌') . " Herda de BaseController\n";

// 1.2 - 6 métodos obrigatórios
$hasIndex = preg_match('/function\s+index\s*\(/', $content);
$hasCreate = preg_match('/function\s+create\s*\(/', $content);
$hasStore = preg_match('/function\s+store\s*\(/', $content);
$hasEdit = preg_match('/function\s+edit\s*\(/', $content);
$hasUpdate = preg_match('/function\s+update\s*\(/', $content);
$hasDestroy = preg_match('/function\s+destroy\s*\(/', $content);

$checks['has_6_methods'] = ($hasIndex && $hasCreate && $hasStore && $hasEdit && $hasUpdate && $hasDestroy);
echo ($checks['has_6_methods'] ? '✅' : '❌') . " Possui 6 métodos (index, create, store, edit, update, destroy)\n";

if (!$checks['has_6_methods']) {
    echo "    Missing: ";
    if (!$hasIndex) echo "index() ";
    if (!$hasCreate) echo "create() ";
    if (!$hasStore) echo "store() ";
    if (!$hasEdit) echo "edit() ";
    if (!$hasUpdate) echo "update() ";
    if (!$hasDestroy) echo "destroy() ";
    echo "\n";
}

echo "\n";

// ================================================
// 2. SEGURANÇA
// ================================================

echo "🔒 SEGURANÇA\n";
echo "───────────────────────────────────────────────────────────────\n";

// 2.1 - CSRF Validation
$checks['has_csrf'] = preg_match('/validateCSRF\s*\(/', $content);
echo ($checks['has_csrf'] ? '✅' : '❌') . " CSRF validation (\$this->validateCSRF())\n";

// 2.2 - Rate Limiting check
$checks['has_ratelimit_check'] = preg_match('/RateLimiter::check\s*\(/', $content);
echo ($checks['has_ratelimit_check'] ? '✅' : '❌') . " Rate limiting check (RateLimiter::check())\n";

// 2.3 - Rate Limiting increment
$checks['has_ratelimit_increment'] = preg_match('/RateLimiter::increment\s*\(/', $content);
echo ($checks['has_ratelimit_increment'] ? '✅' : '❌') . " Rate limiting increment (RateLimiter::increment())\n";

// 2.4 - Prepared statements (não deve ter concatenação direta em queries)
$hasConcatenation = preg_match('/\$db\s*->\s*query\s*\(\s*["\'].*\$/', $content);
$checks['no_sql_concat'] = !$hasConcatenation;
echo ($checks['no_sql_concat'] ? '✅' : '⚠️ ') . " Prepared statements (sem concatenação SQL direta)\n";

// 2.5 - Auth verification
$checks['has_auth'] = preg_match('/(requireAuth\(\)|Auth::require\(\))/', $content);
echo ($checks['has_auth'] ? '✅' : '❌') . " Autenticação (\$this->requireAuth() ou Auth::require())\n";

echo "\n";

// ================================================
// 3. AUDITORIA
// ================================================

echo "📊 AUDITORIA\n";
echo "───────────────────────────────────────────────────────────────\n";

// 3.1 - Logger audit
$checks['has_logger'] = preg_match('/Logger::getInstance\s*\(\s*\)\s*->\s*audit\s*\(/', $content);
echo ($checks['has_logger'] ? '✅' : '❌') . " Audit logging (Logger::getInstance()->audit())\n";

// 3.2 - Logger warning (em catches)
$checks['has_logger_warning'] = preg_match('/Logger::getInstance\s*\(\s*\)\s*->\s*warning\s*\(/', $content);
echo ($checks['has_logger_warning'] ? '✅' : '❌') . " Error logging (Logger::getInstance()->warning())\n";

// 3.3 - Exception handling
$checks['has_exception_handling'] = preg_match('/catch\s*\(\s*Exception/', $content);
echo ($checks['has_exception_handling'] ? '✅' : '❌') . " Exception handling (try/catch)\n";

echo "\n";

// ================================================
// 4. VALIDAÇÃO DE DADOS
// ================================================

echo "✅ VALIDAÇÃO DE DADOS\n";
echo "───────────────────────────────────────────────────────────────\n";

// 4.1 - Security::sanitize
$checks['has_sanitize'] = preg_match('/Security::sanitize\s*\(/', $content);
echo ($checks['has_sanitize'] ? '✅' : '⚠️ ') . " Sanitização (Security::sanitize())\n";

// 4.2 - UUID validation
$checks['has_uuid_validation'] = preg_match('/Security::isValidUUID\s*\(/', $content);
echo ($checks['has_uuid_validation'] ? '✅' : '⚠️ ') . " UUID validation (Security::isValidUUID())\n";

// 4.3 - Empty checks
$checks['has_empty_checks'] = preg_match('/empty\s*\(/', $content);
echo ($checks['has_empty_checks'] ? '✅' : '⚠️ ') . " Empty checks (empty())\n";

echo "\n";

// ================================================
// 5. NOMENCLATURA
// ================================================

echo "🏷️  NOMENCLATURA\n";
echo "───────────────────────────────────────────────────────────────\n";

// 5.1 - Logger actions em maiúsculas
$checks['logger_naming'] = preg_match('/(CREATE_|UPDATE_|DELETE_)[A-Z_]+/', $content);
echo ($checks['logger_naming'] ? '✅' : '⚠️ ') . " Actions em maiúsculas (CREATE_*, UPDATE_*, DELETE_*)\n";

// 5.2 - RateLimiter keys consistentes
$checks['ratelimit_naming'] = preg_match('/[a-z_]+_(create|update|delete)/', $content);
echo ($checks['ratelimit_naming'] ? '✅' : '⚠️ ') . " RateLimiter keys consistentes (recurso_create, etc)\n";

echo "\n";

// ================================================
// 6. PERFORMANCE OBRIGATÓRIA
// ================================================

echo "⚡ PERFORMANCE OBRIGATÓRIA\n";
echo "───────────────────────────────────────────────────────────────\n";

// 6.1 - NÃO usa SELECT *
$hasSelectStar = preg_match('/SELECT\s+\*\s+FROM/i', $content);
$checks['no_select_star'] = !$hasSelectStar;
echo ($checks['no_select_star'] ? '✅' : '❌') . " Não usa SELECT * (performance crítica)\n";

// 6.2 - index() tem paginação
$hasPagination = preg_match('/LIMIT\s+\?\s+OFFSET|perPage|per_page/i', $content);
$checks['has_pagination'] = $hasPagination;
echo ($checks['has_pagination'] ? '✅' : '❌') . " Paginação no index() (LIMIT/OFFSET)\n";

// 6.3 - Otimização de imagem (se tem upload)
$hasUpload = preg_match('/_FILES\[/', $content);
if ($hasUpload) {
    $hasImageOptimization = preg_match('/imagejpeg|imagepng|imagewebp|optimizeImage/', $content);
    $checks['has_image_optimization'] = $hasImageOptimization;
    echo ($checks['has_image_optimization'] ? '✅' : '⚠️ ') . " Otimização de imagem (imagejpeg/optimizeImage)\n";
} else {
    $checks['has_image_optimization'] = true; // N/A
    echo "⊘  Otimização de imagem (N/A - sem upload)\n";
}

echo "\n";

// ================================================
// 7. SEGURANÇA AVANÇADA
// ================================================

echo "🛡️  SEGURANÇA AVANÇADA\n";
echo "───────────────────────────────────────────────────────────────\n";

// 7.1 - Path traversal protection (se tem unlink)
$hasUnlink = preg_match('/unlink\s*\(/', $content);
if ($hasUnlink) {
    $hasPathProtection = preg_match('/realpath.*storage\/uploads|strpos.*uploadBasePath/', $content);
    $checks['has_path_protection'] = $hasPathProtection;
    echo ($checks['has_path_protection'] ? '✅' : '❌') . " Path traversal protection (realpath + strpos)\n";
} else {
    $checks['has_path_protection'] = true; // N/A
    echo "⊘  Path traversal protection (N/A - sem unlink)\n";
}

// 7.2 - UUID validation em edit/update/destroy
$hasUuidInEdit = preg_match('/function\s+edit.*Security::isValidUUID/s', $content);
$hasUuidInUpdate = preg_match('/function\s+update.*Security::isValidUUID/s', $content);
$hasUuidInDestroy = preg_match('/function\s+destroy.*Security::isValidUUID/s', $content);
$checks['uuid_in_all_methods'] = ($hasUuidInEdit && $hasUuidInUpdate && $hasUuidInDestroy);
echo ($checks['uuid_in_all_methods'] ? '✅' : '⚠️ ') . " UUID validation em edit/update/destroy\n";

echo "\n";

// ================================================
// CÁLCULO DE SCORE
// ================================================

echo "═══════════════════════════════════════════════════════════════\n";

$passed = 0;
$total = count($checks);

foreach ($checks as $check => $result) {
    if ($result) {
        $passed++;
    }
}

$percentage = round(($passed / $total) * 100);

echo "SCORE: {$passed}/{$total} ({$percentage}%)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ================================================
// RESULTADO FINAL
// ================================================

if ($percentage === 100) {
    echo "✅ CRUD VÁLIDO!\n";
    echo "\n";
    echo "Seu controller passou em todos os checks obrigatórios.\n";
    echo "Está pronto para produção.\n";
    echo "\n";
    exit(0);
} elseif ($percentage >= 85) {
    echo "⚠️  CRUD QUASE COMPLETO\n";
    echo "\n";
    echo "Faltam alguns elementos não-críticos.\n";
    echo "Revise os itens marcados com ❌ acima.\n";
    echo "\n";
    exit(1);
} else {
    echo "❌ CRUD INCOMPLETO\n";
    echo "\n";
    echo "Faltam elementos CRÍTICOS de segurança ou estrutura.\n";
    echo "Revise o GUIA-PRATICO.md e corrija os itens marcados com ❌.\n";
    echo "\n";

    // Sugestões específicas
    if (!$checks['has_csrf']) {
        echo "→ Adicione \$this->validateCSRF() em store(), update(), destroy()\n";
    }
    if (!$checks['has_ratelimit_check']) {
        echo "→ Adicione RateLimiter::check() em store(), update(), destroy()\n";
    }
    if (!$checks['has_logger']) {
        echo "→ Adicione Logger::getInstance()->audit() após INSERT/UPDATE/DELETE\n";
    }
    if (!$checks['has_6_methods']) {
        echo "→ Implemente os 6 métodos obrigatórios do CRUD\n";
    }
    if (!$checks['no_select_star']) {
        echo "→ Substitua SELECT * por campos específicos (performance crítica!)\n";
    }
    if (!$checks['has_pagination']) {
        echo "→ Adicione paginação no index() com LIMIT/OFFSET\n";
    }
    if ($hasUpload && !$checks['has_image_optimization']) {
        echo "→ Adicione otimização de imagem (imagejpeg/optimizeImage)\n";
    }
    if ($hasUnlink && !$checks['has_path_protection']) {
        echo "→ Adicione path traversal protection antes de unlink()\n";
    }

    echo "\n";
    exit(1);
}

// ================================================
// MODO WATCH (VALIDAÇÃO CONTÍNUA)
// ================================================

// Adicionar no futuro: modo watch para validar durante desenvolvimento
// Uso: php scripts/validate-crud.php BannerController --watch
// Roda validação a cada 5 segundos, mostra progresso em tempo real
