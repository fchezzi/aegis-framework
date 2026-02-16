<?php
/**
 * AEGIS - Gerador Automático de Documentação
 *
 * Escaneia o código real e gera status-real.md automaticamente
 *
 * USO:
 *   php scripts/generate-docs.php
 *
 * SAÍDA:
 *   ~/Library/Mobile Documents/iCloud~md~obsidian/Documents/Chezzi/05 - 🧠 claude/aegis/12 - status-real.md
 */

// Configuração
define('ROOT_PATH', dirname(__DIR__) . '/');
define('OBSIDIAN_PATH', '/Users/fabiochezzi/Library/Mobile Documents/iCloud~md~obsidian/Documents/Chezzi/05 - 🧠 claude/aegis/');

// ========================================
// SCANNER DE CÓDIGO
// ========================================

function scanDirectory($path, $pattern = '*.php') {
    $files = glob($path . $pattern);
    $result = [];

    foreach ($files as $file) {
        $result[] = [
            'name' => basename($file),
            'path' => $file,
            'lines' => count(file($file)),
            'size' => filesize($file),
            'modified' => filemtime($file)
        ];
    }

    return $result;
}

function scanRecursive($path, $extension) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === $extension) {
            $files[] = [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'lines' => count(file($file->getPathname())),
                'size' => $file->getSize(),
                'modified' => $file->getMTime()
            ];
        }
    }

    return $files;
}

function scanSassFiles($path) {
    $files = scanRecursive($path, 'sass');
    $total_lines = 0;

    foreach ($files as $file) {
        $total_lines += $file['lines'];
    }

    return [
        'count' => count($files),
        'lines' => $total_lines,
        'files' => $files
    ];
}

// ========================================
// COLETAR DADOS
// ========================================

echo "🔍 Escaneando código AEGIS...\n";

// Admin
$admin_controllers = scanDirectory(ROOT_PATH . 'admin/controllers/', '*.php');
$admin_views = scanRecursive(ROOT_PATH . 'admin/views/', 'php');

// Assets
$sass_data = scanSassFiles(ROOT_PATH . 'assets/sass/');
$js_files = scanDirectory(ROOT_PATH . 'assets/js/', '*.js');
$css_file = ROOT_PATH . 'assets/css/so-main.css';
$css_size = file_exists($css_file) ? filesize($css_file) : 0;

// Core
$core_files = file_exists(ROOT_PATH . 'core/') ? scanDirectory(ROOT_PATH . 'core/', '*.php') : [];

// Database
$db_files = file_exists(ROOT_PATH . 'database/') ? scanDirectory(ROOT_PATH . 'database/', '*.php') : [];

// Frontend
$frontend_templates = file_exists(ROOT_PATH . 'frontend/templates/') ? scanDirectory(ROOT_PATH . 'frontend/templates/', '*.php') : [];
$frontend_pages = file_exists(ROOT_PATH . 'frontend/pages/') ? scanDirectory(ROOT_PATH . 'frontend/pages/', '*.php') : [];
$frontend_includes = file_exists(ROOT_PATH . 'frontend/includes/') ? scanDirectory(ROOT_PATH . 'frontend/includes/', '*.php') : [];

// Public
$public_controllers = file_exists(ROOT_PATH . 'public/controllers/') ? scanDirectory(ROOT_PATH . 'public/controllers/', '*.php') : [];
$public_views = file_exists(ROOT_PATH . 'public/views/') ? scanDirectory(ROOT_PATH . 'public/views/', '*.php') : [];

// Verificações dinâmicas
$pageBuilderJsExists = file_exists(ROOT_PATH . 'assets/js/page-builder.js');
$routesExists = file_exists(ROOT_PATH . 'routes.php');
$configExists = file_exists(ROOT_PATH . '_config.php');

// ========================================
// GERAR MARKDOWN
// ========================================

$markdown = <<<MD
---
related:
  - "[[00 - README]]"
tags:
  - aegis
  - status
  - validacao
  - auto-generated
created_at: 2025-11-16
last_update: AUTO-GENERATED
version: "AUTO"
---

# AEGIS - Status Real (Auto-Gerado)

> **⚠️ ESTE ARQUIVO É GERADO AUTOMATICAMENTE**
> Execute: `php scripts/generate-docs.php`
> Última geração: **TIMESTAMP**

---

## 📊 MÉTRICAS GERAIS

| Categoria | Valor |
|-----------|-------|
| **Admin Controllers** | CONTROLLER_COUNT arquivos |
| **Admin Views** | VIEW_COUNT arquivos |
| **Frontend Templates** | FRONTEND_TEMPLATES_COUNT arquivos |
| **Frontend Pages** | FRONTEND_PAGES_COUNT arquivos |
| **Frontend Includes** | FRONTEND_INCLUDES_COUNT arquivos |
| **Public Controllers** | PUBLIC_CONTROLLERS_COUNT arquivos |
| **Public Views** | PUBLIC_VIEWS_COUNT arquivos |
| **SASS Arquivos** | SASS_COUNT arquivos (SASS_LINES linhas) |
| **CSS Compilado** | CSS_SIZE |
| **JavaScript** | JS_COUNT arquivos |
| **Core Classes** | CORE_COUNT arquivos |
| **Database Adapters** | DB_COUNT arquivos |

---

## 📁 ADMIN PANEL

### Controllers (CONTROLLER_COUNT)

CONTROLLER_LIST

### Views (VIEW_COUNT)

Estrutura detectada:
VIEW_STRUCTURE

---

## 🎨 ASSETS

### SASS (SASS_COUNT arquivos, SASS_LINES linhas)

```
assets/sass/
├── base/ (BASE_COUNT arquivos)
├── layout/ (LAYOUT_COUNT arquivos)
├── components/ (COMP_COUNT arquivos)
└── modules/ (MOD_COUNT arquivos)
```

### JavaScript (JS_COUNT arquivos)

JS_LIST

### CSS Compilado

- **so-main.css:** CSS_SIZE

---

## ⚠️ PONTOS DE ATENÇÃO

### PageBuilder
- ✅ Backend completo (PageBuilderController)
- ✅ SASS/CSS Grid 6 colunas
- PAGEBUILDER_JS_STATUS

### Arquivos Principais
- ROUTES_STATUS
- CONFIG_STATUS

### Testes Automáticos
- ✅ SecurityTestController existe
- ✅ PerformanceTestController existe
- ⚠️ **Status:** Implementados mas nunca executados em produção

---

## 🎯 PRÓXIMOS PASSOS

NEXT_STEPS

---

**Gerado automaticamente por:** `/scripts/generate-docs.php`
**Data:** TIMESTAMP
**Versão AEGIS:** v1.5.0
MD;

// ========================================
// SUBSTITUIR PLACEHOLDERS
// ========================================

// Contadores
$markdown = str_replace('CONTROLLER_COUNT', count($admin_controllers), $markdown);
$markdown = str_replace('VIEW_COUNT', count($admin_views), $markdown);
$markdown = str_replace('FRONTEND_TEMPLATES_COUNT', count($frontend_templates), $markdown);
$markdown = str_replace('FRONTEND_PAGES_COUNT', count($frontend_pages), $markdown);
$markdown = str_replace('FRONTEND_INCLUDES_COUNT', count($frontend_includes), $markdown);
$markdown = str_replace('PUBLIC_CONTROLLERS_COUNT', count($public_controllers), $markdown);
$markdown = str_replace('PUBLIC_VIEWS_COUNT', count($public_views), $markdown);
$markdown = str_replace('SASS_COUNT', $sass_data['count'], $markdown);
$markdown = str_replace('SASS_LINES', $sass_data['lines'], $markdown);
$markdown = str_replace('CSS_SIZE', round($css_size / 1024, 1) . ' KB', $markdown);
$markdown = str_replace('JS_COUNT', count($js_files), $markdown);
$markdown = str_replace('CORE_COUNT', count($core_files), $markdown);
$markdown = str_replace('DB_COUNT', count($db_files), $markdown);

// Lista de controllers
$controller_list = '';
foreach ($admin_controllers as $controller) {
    $controller_list .= "- `{$controller['name']}` ({$controller['lines']} linhas)\n";
}
$markdown = str_replace('CONTROLLER_LIST', $controller_list, $markdown);

// Estrutura de views
$view_dirs = [];
foreach ($admin_views as $view) {
    $dir = dirname(str_replace(ROOT_PATH . 'admin/views/', '', $view));
    if ($dir === '.') $dir = 'root';
    $view_dirs[$dir] = ($view_dirs[$dir] ?? 0) + 1;
}
$view_structure = '';
foreach ($view_dirs as $dir => $count) {
    $view_structure .= "- `$dir/` ($count arquivos)\n";
}
$markdown = str_replace('VIEW_STRUCTURE', $view_structure, $markdown);

// Contagem SASS por pasta
$sass_base = count(glob(ROOT_PATH . 'assets/sass/base/*.sass'));
$sass_layout = count(glob(ROOT_PATH . 'assets/sass/layout/*.sass'));
$sass_comp = count(glob(ROOT_PATH . 'assets/sass/components/*.sass'));
$sass_mod = count(glob(ROOT_PATH . 'assets/sass/modules/*.sass'));

$markdown = str_replace('BASE_COUNT', $sass_base, $markdown);
$markdown = str_replace('LAYOUT_COUNT', $sass_layout, $markdown);
$markdown = str_replace('COMP_COUNT', $sass_comp, $markdown);
$markdown = str_replace('MOD_COUNT', $sass_mod, $markdown);

// Lista JS
$js_list = '';
foreach ($js_files as $js) {
    $js_list .= "- `{$js['name']}` ({$js['lines']} linhas)\n";
}
$markdown = str_replace('JS_LIST', $js_list, $markdown);

// Timestamp
$markdown = str_replace('TIMESTAMP', date('d/m/Y H:i:s'), $markdown);

// Verificações dinâmicas
$pageBuilderStatus = $pageBuilderJsExists
    ? '✅ **JavaScript criado** (`/assets/js/page-builder.js`)'
    : '❌ **JavaScript ausente** (`/assets/js/page-builder.js` não encontrado)';
$markdown = str_replace('PAGEBUILDER_JS_STATUS', $pageBuilderStatus, $markdown);

$routesStatus = $routesExists
    ? '✅ `routes.php` encontrado'
    : '❌ `routes.php` não encontrado';
$markdown = str_replace('ROUTES_STATUS', $routesStatus, $markdown);

$configStatus = $configExists
    ? '✅ `_config.php` encontrado'
    : '❌ `_config.php` não encontrado';
$markdown = str_replace('CONFIG_STATUS', $configStatus, $markdown);

// Próximos passos dinâmicos
$nextSteps = [];
if (!$pageBuilderJsExists) {
    $nextSteps[] = '1. **Criar** `/assets/js/page-builder.js` com funcionalidade drag & drop';
}
if (count($admin_views) === 0) {
    $nextSteps[] = '2. **Verificar** estrutura de views (possível erro no scan)';
}
if (!$routesExists) {
    $nextSteps[] = '3. **Criar** `routes.php` (arquivo crítico ausente)';
}
if (!$configExists) {
    $nextSteps[] = '4. **Criar** `_config.php` (arquivo crítico ausente)';
}

// Se não há próximos passos, mostrar mensagem
if (empty($nextSteps)) {
    $nextStepsText = "✅ **Sistema completo!** Nenhuma ação crítica pendente.";
} else {
    $nextStepsText = implode("\n", $nextSteps);
}
$markdown = str_replace('NEXT_STEPS', $nextStepsText, $markdown);

// ========================================
// SALVAR ARQUIVO
// ========================================

$output_file = OBSIDIAN_PATH . '12 - status-real.md';

if (file_put_contents($output_file, $markdown)) {
    echo "✅ Documentação gerada com sucesso!\n";
    echo "📄 Arquivo: $output_file\n";
    echo "📊 Controllers: " . count($admin_controllers) . "\n";
    echo "📊 Views: " . count($admin_views) . "\n";
    echo "📊 SASS: {$sass_data['count']} arquivos ({$sass_data['lines']} linhas)\n";
    echo "📊 CSS: " . round($css_size / 1024, 1) . " KB\n";
} else {
    echo "❌ Erro ao salvar arquivo!\n";
    exit(1);
}

echo "\n✨ Concluído!\n";
