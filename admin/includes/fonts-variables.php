<?php
/**
 * CSS Variables para Fontes Selecionadas
 * Injeta variáveis CSS com as fontes escolhidas em Settings
 *
 * Uso: Incluir no <head> das páginas admin/members/frontend
 * <?php require_once ROOT_PATH . 'admin/includes/fonts-variables.php'; ?>
 *
 * Gera:
 * :root {
 *   --font-admin: 'Roboto', sans-serif;
 *   --font-members: 'Inter', sans-serif;
 *   --font-frontend-primary: 'Open Sans', sans-serif;
 *   --font-frontend-secondary: 'Lato', sans-serif;
 * }
 */

// Carregar fontes selecionadas do Settings
$adminFont = Settings::get('admin_font_family', 'system-ui');
$membersFont = Settings::get('members_font_family', 'system-ui');
$frontendPrimary = Settings::get('frontend_font_primary', 'system-ui');
$frontendSecondary = Settings::get('frontend_font_secondary', 'system-ui');

// Gerar CSS
echo '<style id="aegis-fonts-variables">' . "\n";
echo ':root {' . "\n";
echo '    --font-admin: \'' . addslashes($adminFont) . '\', sans-serif;' . "\n";
echo '    --font-members: \'' . addslashes($membersFont) . '\', sans-serif;' . "\n";
echo '    --font-frontend-primary: \'' . addslashes($frontendPrimary) . '\', sans-serif;' . "\n";
echo '    --font-frontend-secondary: \'' . addslashes($frontendSecondary) . '\', sans-serif;' . "\n";
echo '}' . "\n";
echo '</style>' . "\n";
