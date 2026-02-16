<?php
/**
 * @doc Content
 * @title Menu Builder
 * @description
 * Sistema dinâmico de menus com hierarquia e permissões:
 * - Itens configuráveis via admin
 * - Tipos: page, link, category, module
 * - Permissões: public, authenticated, group, member
 * - Hierarquia (parent/child)
 * - Filtro automático por permissões
 * - Renderização HTML otimizada
 *
 * @example
 * // Renderizar menu no aside
 * <?php echo MenuBuilder::render($memberId); ?>
 *
 * // Obter array filtrado
 * $items = MenuBuilder::getFilteredMenu($memberId);
 */

/**
 * MenuBuilder
 * Renderiza menu hierárquico com permissões
 */

class MenuBuilder {

    /**
     * Renderizar menu completo para um member
     *
     * @param string|null $memberId ID do member logado (null = público)
     * @return string HTML do menu
     */
    public static function render($memberId = null) {
        $items = self::getFilteredMenu($memberId);
        return MenuRenderer::render($items);
    }

    /**
     * Retornar array de menu items filtrados
     *
     * @param string|null $memberId ID do member logado (null = público)
     * @return array Menu items permitidos
     */
    public static function getFilteredMenu($memberId = null) {
        $db = DB::connect();

        // Buscar todos os itens visíveis ordenados
        $allItems = $db->select('menu_items', ['visible' => 1], 'ordem ASC');

        // Filtrar por permissões
        return MenuPermissionChecker::filter($allItems, $memberId);
    }

    /**
     * Invalidar cache
     */
    public static function clearCache() {
        MenuPermissionChecker::clearCache();
    }
}
