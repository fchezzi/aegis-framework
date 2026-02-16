<?php
/**
 * MenuRenderer
 * Renderiza menu hierárquico como HTML
 */

class MenuRenderer {

    /**
     * Renderizar menu completo
     */
    public static function render($items) {
        $tree = self::buildTree($items);
        return self::renderTree($tree);
    }

    /**
     * Construir árvore hierárquica
     */
    private static function buildTree($items, $parentId = null) {
        $tree = [];

        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $item['children'] = self::buildTree($items, $item['id']);
                $tree[] = $item;
            }
        }

        return $tree;
    }

    /**
     * Renderizar árvore
     */
    private static function renderTree($tree, $level = 0) {
        if (empty($tree)) {
            return '';
        }

        $html = '';
        foreach ($tree as $item) {
            $html .= self::renderItem($item, $level);
        }

        return $html;
    }

    /**
     * Renderizar item individual
     */
    private static function renderItem($item, $level = 0) {
        // Validar página
        if ($item['type'] === 'page') {
            $pageFile = ROOT_PATH . 'frontend/pages/' . $item['page_slug'] . '.php';
            if (!file_exists($pageFile)) {
                return ''; // Página não existe
            }
        }

        $hasChildren = !empty($item['children']);
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

        if ($hasChildren) {
            return self::renderParentItem($item, $currentPath, $level);
        }

        return ($level === 0)
            ? self::renderRootItem($item, $currentPath)
            : self::renderChildItem($item, $currentPath);
    }

    /**
     * Renderizar item pai (com filhos)
     */
    private static function renderParentItem($item, $currentPath, $level) {
        $hasActiveDescendant = self::hasActiveChild($item, $currentPath);
        $activeClass = $hasActiveDescendant ? ' active' : '';

        $html = '<li class="menu-item menu-text' . $activeClass . '">';
        $html .= '<div class="menu-item__link" data-submenu="' . htmlspecialchars($item['id']) . '">';

        // Ícone
        if ($item['icon']) {
            $html .= '<i data-lucide="' . htmlspecialchars($item['icon']) . '"></i>';
        }

        $html .= '<div class="menu-item__content">';
        $html .= '<span class="menu-item__text">' . htmlspecialchars($item['label']) . '</span>';
        $html .= '<i data-lucide="chevron-right" class="menu-item__arrow"></i>';
        $html .= '</div>';
        $html .= '</div>';

        // Submenu
        $html .= '<ul class="submenu">';
        foreach ($item['children'] as $child) {
            $html .= self::renderItem($child, $level + 1);
        }
        $html .= '</ul>';

        $html .= '</li>';

        return $html;
    }

    /**
     * Renderizar item raiz (sem filhos)
     */
    private static function renderRootItem($item, $currentPath) {
        $itemUrl = self::getItemUrl($item);
        $itemPath = parse_url($itemUrl, PHP_URL_PATH);
        $isActive = ($itemPath && $currentPath === $itemPath);
        $activeClass = $isActive ? ' menu-item--active' : '';

        $html = '<li class="menu-item menu-text' . $activeClass . '">';

        if ($item['type'] === 'category') {
            // Categoria sem link
            $html .= '<div class="menu-item__link">';
            if ($item['icon']) {
                $html .= '<i data-lucide="' . htmlspecialchars($item['icon']) . '"></i>';
            }
            $html .= '<div class="menu-item__content">';
            $html .= '<span class="menu-item__text">' . htmlspecialchars($item['label']) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
        } else {
            // Link normal
            $html .= '<a href="' . htmlspecialchars($itemUrl) . '" class="menu-item__link">';
            if ($item['icon']) {
                $html .= '<i data-lucide="' . htmlspecialchars($item['icon']) . '"></i>';
            }
            $html .= '<div class="menu-item__content">';
            $html .= '<span class="menu-item__text">' . htmlspecialchars($item['label']) . '</span>';
            $html .= '</div>';
            $html .= '</a>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Renderizar sub-item
     */
    private static function renderChildItem($item, $currentPath) {
        $itemUrl = self::getItemUrl($item);
        $itemPath = parse_url($itemUrl, PHP_URL_PATH);
        $isActive = ($itemPath && $currentPath === $itemPath);

        $html = '<li class="submenu__item">';

        if ($item['type'] === 'category') {
            $html .= '<span>';
            if ($item['icon']) {
                $html .= '<i data-lucide="' . htmlspecialchars($item['icon']) . '" class="submenu__icon"></i> ';
            }
            $html .= htmlspecialchars($item['label']) . '</span>';
        } else {
            $target = ($item['type'] === 'link' && strpos($item['url'], 'http') === 0) ? ' target="_blank"' : '';
            $activeClass = $isActive ? ' active' : '';
            $html .= '<a href="' . htmlspecialchars($itemUrl) . '"' . $target . ' class="' . trim($activeClass) . '">';
            if ($item['icon']) {
                $html .= '<i data-lucide="' . htmlspecialchars($item['icon']) . '" class="submenu__icon"></i> ';
            }
            $html .= htmlspecialchars($item['label']);
            $html .= '</a>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Obter URL do item
     */
    private static function getItemUrl($item) {
        switch ($item['type']) {
            case 'page':
                return url('/' . $item['page_slug']);

            case 'link':
                return $item['url'];

            case 'module':
                return url($item['url'] ?? '#');

            default:
                return '#';
        }
    }

    /**
     * Verificar se item ou filhos estão ativos
     */
    private static function hasActiveChild($item, $currentPath) {
        $itemUrl = self::getItemUrl($item);
        $itemPath = parse_url($itemUrl, PHP_URL_PATH);

        if ($itemPath === $currentPath) {
            return true;
        }

        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if (self::hasActiveChild($child, $currentPath)) {
                    return true;
                }
            }
        }

        return false;
    }
}
