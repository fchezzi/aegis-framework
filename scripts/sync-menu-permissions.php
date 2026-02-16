<?php
/**
 * SYNC: Sincronizar permissões do menu com a página do módulo
 */

require_once __DIR__ . '/_config.php';

// Autoloader simples
spl_autoload_register(function($class) {
    $paths = [
        ROOT_PATH . 'core/' . $class . '.php',
        ROOT_PATH . 'database/' . $class . '.php'
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

header('Content-Type: text/plain; charset=utf-8');

echo "=== SYNC MENU PERMISSIONS COM PÁGINAS ===\n\n";

try {
    $db = DB::connect();

    // Buscar todos os menu items do tipo module
    $moduleMenus = $db->select('menu_items', ['type' => 'module']);

    if (empty($moduleMenus)) {
        echo "❌ Nenhum menu item de módulo encontrado!\n";
        exit;
    }

    echo "Encontrados " . count($moduleMenus) . " menu(s) de módulo.\n\n";

    $updated = 0;

    foreach ($moduleMenus as $menu) {
        echo "──────────────────────────────────────────\n";
        echo "Menu: {$menu['label']}\n";
        echo "  Módulo: {$menu['module_name']}\n";
        echo "  Permission atual: {$menu['permission_type']}\n";

        // Buscar página do módulo
        $page = $db->select('pages', ['module_name' => $menu['module_name']]);

        if (empty($page)) {
            echo "  ⚠️ Página do módulo não encontrada\n\n";
            continue;
        }

        $isPublic = ($page[0]['is_public'] ?? 0) == 1;

        echo "  Página is_public: " . ($isPublic ? 'SIM' : 'NÃO') . "\n";

        // Determinar permission_type correto
        $correctPermission = $isPublic ? 'public' : 'authenticated';

        if ($menu['permission_type'] === $correctPermission) {
            echo "  ✅ Já está sincronizado!\n\n";
            continue;
        }

        // Atualizar menu item
        $db->update('menu_items',
            ['permission_type' => $correctPermission],
            ['id' => $menu['id']]
        );

        echo "  🔄 Atualizado: {$menu['permission_type']} → $correctPermission\n\n";
        $updated++;
    }

    echo "──────────────────────────────────────────\n";
    echo "\n✅ Sincronização concluída!\n";
    echo "   $updated menu(s) atualizado(s)\n\n";

    if ($updated > 0) {
        echo "🔄 Acesse o site e veja o menu agora!\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
