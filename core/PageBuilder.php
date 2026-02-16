<?php
/**
 * @doc Content
 * @title Page Builder
 * @description
 * Sistema visual de montagem de páginas com blocos e cards:
 * - Blocos horizontais (rows)
 * - Cards dentro dos blocos (tamanhos 1-6)
 * - Grid responsivo baseado em colunas
 * - Cache em memória para performance
 * - Editor drag-and-drop no admin
 *
 * @example
 * // Renderizar página no template
 * <div class="l-content">
 *     <?= PageBuilder::render('home') ?>
 * </div>
 *
 * // Forçar renderização sem cache
 * echo PageBuilder::render('dashboard', true);
 */

/**
 * PageBuilder
 * Renderiza blocos e cards no frontend com cache e otimizações
 */

class PageBuilder {

    /** @var array Cache em memória (válido durante request) */
    private static $cache = [];

    /** @var int TTL do cache em segundos (5 minutos) */
    private static $cacheTTL = 300;

    /**
     * Renderizar layout completo de uma página
     *
     * @param string $pageSlug Slug da página
     * @param bool $skipCache Forçar renderização sem cache (default: false)
     * @return string HTML renderizado
     */
    public static function render($pageSlug, $skipCache = false) {
        // Verificar se existe banco de dados
        if (!defined('DB_TYPE') || DB_TYPE === 'none') {
            return ''; // Sem banco = sem page builder
        }

        // Verificar cache (se habilitado)
        if (!$skipCache && self::hasValidCache($pageSlug)) {
            return self::getFromCache($pageSlug);
        }

        try {
            $db = DB::connect();

            // Buscar blocos da página
            $blocks = $db->select('page_blocks', ['page_slug' => $pageSlug], ['order' => 'ordem ASC']);

            if (empty($blocks)) {
                return ''; // Sem blocos = nada a renderizar
            }

            // 🚀 OTIMIZAÇÃO 1: Buscar apenas cards dos blocos desta página (com WHERE IN)
            $blockIds = array_column($blocks, 'id');
            $blockIdsSet = array_flip($blockIds); // O(1) lookup ao invés de O(n) in_array

            // Buscar cards filtrados por block_id
            // Supabase: usar select() com array (WHERE IN automático)
            // MySQL: usar query() com placeholders
            if (DB_TYPE === 'supabase') {
                $allCardsRaw = $db->select('page_cards', ['block_id' => $blockIds], ['order' => 'ordem ASC']);
            } else {
                $placeholders = implode(',', array_fill(0, count($blockIds), '?'));
                $allCardsRaw = $db->query(
                    "SELECT * FROM page_cards WHERE block_id IN ($placeholders) ORDER BY ordem ASC",
                    $blockIds
                );
            }

            // 🚀 OTIMIZAÇÃO 2: Organizar cards por block_id (hash map - O(1))
            $cardsByBlock = [];
            foreach ($allCardsRaw as $card) {
                $cardsByBlock[$card['block_id']][] = $card;
            }

            // 🚀 OTIMIZAÇÃO 3: Array buffer ao invés de concatenação
            $htmlParts = [];
            $htmlParts[] = '<!-- Page Builder Start -->';

            // Para cada bloco, renderizar cards
            foreach ($blocks as $block) {
                $cards = $cardsByBlock[$block['id']] ?? [];

                if (empty($cards)) {
                    continue; // Bloco vazio
                }

                // Abrir row
                $htmlParts[] = '<div class="page-builder-row">';

                // Renderizar cada card
                foreach ($cards as $card) {
                    $size = (int)$card['size'];
                    $htmlParts[] = sprintf(
                        '<div class="page-builder-col-%d"><div class="page-builder-card">',
                        $size
                    );

                    // Conteúdo do card
                    // Se card tem component_type, renderizar via Component
                    if (!empty($card['component_type'])) {
                        try {
                            $componentData = !empty($card['component_data'])
                                ? json_decode($card['component_data'], true)
                                : [];
                            $htmlParts[] = Component::render($card['component_type'], $componentData);
                        } catch (Exception $e) {
                            $htmlParts[] = sprintf(
                                '<div class="page-builder-error">Erro ao renderizar componente: %s</div>',
                                htmlspecialchars($e->getMessage())
                            );
                        }
                    } elseif (!empty($card['content'])) {
                        // Legacy: HTML puro
                        $htmlParts[] = $card['content'];
                    } else {
                        // Placeholder vazio
                        $htmlParts[] = sprintf(
                            '<div class="page-builder-placeholder"><p>Card %d/6</p></div>',
                            $size
                        );
                    }

                    $htmlParts[] = '</div></div>';
                }

                // Fechar row
                $htmlParts[] = '</div>';
            }

            $htmlParts[] = '<!-- Page Builder End -->';

            // 🚀 OTIMIZAÇÃO 4: Juntar tudo de uma vez (muito mais rápido que +=)
            $html = implode('', $htmlParts);

            // Salvar no cache
            self::saveToCache($pageSlug, $html);

            return $html;

        } catch (Exception $e) {
            // Erro silencioso - não quebrar página
            error_log("PageBuilder error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Verificar se página tem blocos
     *
     * @param string $pageSlug
     * @return bool
     */
    public static function hasBlocks($pageSlug) {
        if (!defined('DB_TYPE') || DB_TYPE === 'none') {
            return false;
        }

        // 🚀 Cache em memória durante request
        $cacheKey = "has_blocks_{$pageSlug}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $db = DB::connect();
            $blocks = $db->select('page_blocks', ['page_slug' => $pageSlug], ['limit' => 1]);
            $hasBlocks = !empty($blocks);

            self::$cache[$cacheKey] = $hasBlocks;
            return $hasBlocks;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Contar total de cards em uma página (OTIMIZADO)
     *
     * @param string $pageSlug
     * @return int
     */
    public static function countCards($pageSlug) {
        if (!defined('DB_TYPE') || DB_TYPE === 'none') {
            return 0;
        }

        // 🚀 Cache em memória durante request
        $cacheKey = "count_cards_{$pageSlug}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $db = DB::connect();

            // 🚀 OTIMIZAÇÃO: Query única com JOIN ao invés de N queries
            $blocks = $db->select('page_blocks', ['page_slug' => $pageSlug]);

            if (empty($blocks)) {
                self::$cache[$cacheKey] = 0;
                return 0;
            }

            $blockIds = array_column($blocks, 'id');
            $placeholders = implode(',', array_fill(0, count($blockIds), '?'));
            $cards = $db->query(
                "SELECT * FROM page_cards WHERE block_id IN ($placeholders)",
                $blockIds
            );
            $totalCards = count($cards);

            self::$cache[$cacheKey] = $totalCards;
            return $totalCards;

        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Verificar se cache é válido
     *
     * @param string $pageSlug
     * @return bool
     */
    private static function hasValidCache($pageSlug) {
        // Cache em memória (durante request)
        $cacheKey = "render_{$pageSlug}";
        if (isset(self::$cache[$cacheKey])) {
            return true;
        }

        // Cache em arquivo (entre requests)
        if (defined('CACHE_ENABLED') && CACHE_ENABLED === true) {
            $cacheFile = self::getCacheFilePath($pageSlug);
            if (file_exists($cacheFile)) {
                $age = time() - filemtime($cacheFile);
                return $age < self::$cacheTTL;
            }
        }

        return false;
    }

    /**
     * Obter do cache
     *
     * @param string $pageSlug
     * @return string
     */
    private static function getFromCache($pageSlug) {
        // Cache em memória primeiro
        $cacheKey = "render_{$pageSlug}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        // Cache em arquivo
        if (defined('CACHE_ENABLED') && CACHE_ENABLED === true) {
            $cacheFile = self::getCacheFilePath($pageSlug);
            if (file_exists($cacheFile)) {
                $html = file_get_contents($cacheFile);
                self::$cache[$cacheKey] = $html; // Cachear em memória também
                return $html;
            }
        }

        return '';
    }

    /**
     * Salvar no cache
     *
     * @param string $pageSlug
     * @param string $html
     */
    private static function saveToCache($pageSlug, $html) {
        // Cache em memória
        $cacheKey = "render_{$pageSlug}";
        self::$cache[$cacheKey] = $html;

        // Cache em arquivo (se habilitado)
        if (defined('CACHE_ENABLED') && CACHE_ENABLED === true) {
            $cacheFile = self::getCacheFilePath($pageSlug);
            $cacheDir = dirname($cacheFile);

            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }

            @file_put_contents($cacheFile, $html, LOCK_EX);
        }
    }

    /**
     * Limpar cache de uma página
     *
     * @param string $pageSlug
     */
    public static function clearCache($pageSlug = null) {
        if ($pageSlug === null) {
            // Limpar todo o cache
            self::$cache = [];

            if (defined('CACHE_ENABLED') && CACHE_ENABLED === true) {
                $cacheDir = self::getCacheDir();
                if (is_dir($cacheDir)) {
                    $files = glob($cacheDir . '/*.html');
                    foreach ($files as $file) {
                        @unlink($file);
                    }
                }
            }
        } else {
            // Limpar cache de página específica
            $cacheKey = "render_{$pageSlug}";
            unset(self::$cache[$cacheKey]);

            if (defined('CACHE_ENABLED') && CACHE_ENABLED === true) {
                $cacheFile = self::getCacheFilePath($pageSlug);
                if (file_exists($cacheFile)) {
                    @unlink($cacheFile);
                }
            }
        }
    }

    /**
     * Obter caminho do diretório de cache
     *
     * @return string
     */
    private static function getCacheDir() {
        return ROOT_PATH . '/storage/cache/page-builder';
    }

    /**
     * Obter caminho do arquivo de cache
     *
     * @param string $pageSlug
     * @return string
     */
    private static function getCacheFilePath($pageSlug) {
        $safeSlug = preg_replace('/[^a-z0-9_-]/i', '', $pageSlug);
        return self::getCacheDir() . '/' . $safeSlug . '.html';
    }

    /**
     * Obter estatísticas de cache
     *
     * @return array
     */
    public static function getCacheStats() {
        $stats = [
            'memory_cache_size' => count(self::$cache),
            'file_cache_enabled' => defined('CACHE_ENABLED') && CACHE_ENABLED === true,
            'file_cache_count' => 0,
            'file_cache_size' => 0,
            'cache_ttl' => self::$cacheTTL
        ];

        if ($stats['file_cache_enabled']) {
            $cacheDir = self::getCacheDir();
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*.html');
                $stats['file_cache_count'] = count($files);

                foreach ($files as $file) {
                    $stats['file_cache_size'] += filesize($file);
                }
            }
        }

        return $stats;
    }
}
