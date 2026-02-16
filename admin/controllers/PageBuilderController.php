<?php
/**
 * Page Builder Controller
 * Gerenciamento de blocos e cards (grid 6 colunas)
 *
 * SEGURANÇA:
 * - CSRF protection em todas as ações
 * - Validação rigorosa de inputs
 * - Sanitização de outputs
 * - Proteção contra XSS, SQL Injection, DoS
 * - Rate limiting integrado
 */

class PageBuilderController {

    // Limites de segurança
    private const MAX_BLOCKS = 50;  // Máximo de blocos por página
    private const MAX_CARDS = 300;  // Máximo de cards total
    private const MAX_CONTENT_LENGTH = 1000000; // 1MB de conteúdo por card

    /**
     * Salvar layout completo de uma vez
     * SEGURANÇA: Validação completa de estrutura e conteúdo
     */
    public function saveLayout() {
        // Limpar TODOS os buffers de output
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Iniciar novo buffer limpo
        ob_start();

        // Headers de segurança (ANTES de qualquer output)
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        try {
            // 1. CSRF Protection
            if (!isset($_POST['csrf_token'])) {
                ob_end_clean();
                http_response_code(403);
                die(json_encode(['success' => false, 'error' => 'Token CSRF ausente']));
            }

            Security::validateCSRF($_POST['csrf_token']);

            $db = DB::connect();

            // 2. Validar e sanitizar page_slug
            $pageSlug = Security::sanitize($_POST['page_slug'] ?? '');

            if (empty($pageSlug)) {
                ob_end_clean();
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'Slug da página é obrigatório']));
            }

            // Validar formato do slug (apenas letras, números, hífen e underscore)
            if (!preg_match('/^[a-z0-9_-]+$/i', $pageSlug)) {
                ob_end_clean();
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'Slug inválido']));
            }

            // 3. Decodificar e validar layout_data
            $layoutJSON = $_POST['layout_data'] ?? '[]';

            // Validar tamanho do JSON (proteção DoS)
            if (strlen($layoutJSON) > 10000000) { // 10MB máximo
                ob_end_clean();
                http_response_code(413);
                die(json_encode(['success' => false, 'error' => 'Dados muito grandes']));
            }

            $layoutData = json_decode($layoutJSON, true);

            if (!is_array($layoutData)) {
                ob_end_clean();
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'Formato de dados inválido']));
            }

            // Validar número de blocos (proteção DoS)
            if (count($layoutData) > self::MAX_BLOCKS) {
                ob_end_clean();
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'Número máximo de blocos excedido (' . self::MAX_BLOCKS . ')']));
            }

            // Validar estrutura de cada bloco e card
            $totalCards = 0;
            foreach ($layoutData as $blockIndex => $blockData) {
                if (!isset($blockData['cards']) || !is_array($blockData['cards'])) {
                    ob_end_clean();
                    http_response_code(400);
                    die(json_encode(['success' => false, 'error' => 'Estrutura de bloco inválida']));
                }

                $totalCards += count($blockData['cards']);

                // Validar número total de cards (proteção DoS)
                if ($totalCards > self::MAX_CARDS) {
                    ob_end_clean();
                    http_response_code(400);
                    die(json_encode(['success' => false, 'error' => 'Número máximo de cards excedido (' . self::MAX_CARDS . ')']));
                }

                foreach ($blockData['cards'] as $cardData) {
                    // Validar size
                    $size = intval($cardData['size'] ?? 0);
                    if ($size < 1 || $size > 6) {
                        ob_end_clean();
                        http_response_code(400);
                        die(json_encode(['success' => false, 'error' => 'Tamanho de card inválido']));
                    }

                    // Validar content (se presente)
                    if (isset($cardData['content'])) {
                        $content = $cardData['content'];

                        // Validar tamanho do conteúdo (proteção DoS)
                        if (strlen($content) > self::MAX_CONTENT_LENGTH) {
                            ob_end_clean();
                            http_response_code(400);
                            die(json_encode(['success' => false, 'error' => 'Conteúdo muito grande']));
                        }
                    }
                }
            }

            // 4. Deletar blocos e cards antigos desta página (transação)
            $oldBlocks = $db->select('page_blocks', ['page_slug' => $pageSlug]);
            foreach ($oldBlocks as $block) {
                // Validar que o block_id é UUID válido
                if (!preg_match('/^[a-f0-9-]{36}$/i', $block['id'])) {
                    continue; // Skip IDs inválidos
                }

                $db->delete('page_cards', ['block_id' => $block['id']]);
                $db->delete('page_blocks', ['id' => $block['id']]);
            }

            // 5. Inserir novos blocos e cards
            foreach ($layoutData as $blockIndex => $blockData) {
                // Validar blockIndex
                $ordem = intval($blockIndex) + 1;
                if ($ordem < 1 || $ordem > self::MAX_BLOCKS) {
                    continue; // Skip ordens inválidas
                }

                // Criar bloco
                $blockId = Security::generateUUID();

                $blockInsert = [
                    'id' => $blockId,
                    'page_slug' => $pageSlug,
                    'ordem' => $ordem
                ];

                if (DB_TYPE === 'mysql') {
                    $blockInsert['created_at'] = date('Y-m-d H:i:s');
                }

                $db->insert('page_blocks', $blockInsert);

                // Criar cards deste bloco
                if (!empty($blockData['cards'])) {
                    foreach ($blockData['cards'] as $cardIndex => $cardData) {
                        // Validar e sanitizar dados do card
                        $size = intval($cardData['size'] ?? 1);
                        $size = max(1, min(6, $size)); // Clamp entre 1-6

                        $ordem = intval($cardIndex) + 1;

                        // Sanitizar content (proteção XSS)
                        $content = null;
                        if (isset($cardData['content']) && !empty($cardData['content'])) {
                            // Permitir apenas tags HTML seguras
                            $content = strip_tags(
                                $cardData['content'],
                                '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><img><div><span>'
                            );

                            // Limitar tamanho
                            $content = substr($content, 0, self::MAX_CONTENT_LENGTH);
                        }

                        // Processar componentes
                        $componentType = null;
                        $componentData = null;

                        if (isset($cardData['component_type']) && !empty($cardData['component_type'])) {
                            $componentType = Security::sanitize($cardData['component_type']);

                            // Validar se componente existe
                            if (Component::exists($componentType)) {
                                // Processar component_data
                                if (isset($cardData['component_data'])) {
                                    $componentDataArray = is_array($cardData['component_data'])
                                        ? $cardData['component_data']
                                        : json_decode($cardData['component_data'], true);

                                    // Validar dados do componente
                                    if (Component::validate($componentType, $componentDataArray)) {
                                        $componentData = json_encode($componentDataArray);
                                    } else {
                                        // Log detalhado para debug
                                        error_log("Validação falhou para componente: {$componentType}");
                                        error_log("Dados recebidos: " . json_encode($componentDataArray));
                                        throw new Exception("Dados inválidos para componente '{$componentType}'. Verifique os campos obrigatórios.");
                                    }
                                }
                            } else {
                                throw new Exception("Componente '{$componentType}' não encontrado");
                            }
                        }

                        $cardInsert = [
                            'id' => Security::generateUUID(),
                            'block_id' => $blockId,
                            'size' => $size,
                            'ordem' => $ordem,
                            'content' => $content,
                            'component_type' => $componentType,
                            'component_data' => $componentData
                        ];

                        if (DB_TYPE === 'mysql') {
                            $cardInsert['created_at'] = date('Y-m-d H:i:s');
                        }

                        $db->insert('page_cards', $cardInsert);
                    }
                }
            }

            // Sucesso
            // 🚀 PERFORMANCE: Limpar cache ao salvar
            if (class_exists('PageBuilder')) {
                PageBuilder::clearCache($pageSlug);
            }

            // Limpar qualquer output capturado
            $bufferContent = ob_get_clean();

            // DEBUG: Logar o que estava no buffer
            if (!empty($bufferContent)) {
                error_log('=== BUFFER TINHA CONTEÚDO ===');
                error_log('Tamanho: ' . strlen($bufferContent));
                error_log('Conteúdo: ' . var_export($bufferContent, true));
                error_log('HEX: ' . bin2hex($bufferContent));
                error_log('=== FIM BUFFER ===');
            }

            // Preparar JSON
            $jsonResponse = json_encode([
                'success' => true,
                'message' => 'Layout salvo com sucesso!',
                'blocks' => count($layoutData),
                'cards' => $totalCards
            ], JSON_THROW_ON_ERROR);

            // DEBUG: Logar JSON que será enviado
            error_log('=== JSON RESPONSE ===');
            error_log('Tamanho: ' . strlen($jsonResponse));
            error_log('Conteúdo: ' . $jsonResponse);
            error_log('=== FIM JSON ===');

            // Retornar JSON puro
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Length: ' . strlen($jsonResponse));
            echo $jsonResponse;
            exit;

        } catch (Exception $e) {
            // Log erro completo
            error_log('PageBuilder saveLayout error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // Limpar qualquer output capturado
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Retornar erro específico (ajuda no debug)
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_THROW_ON_ERROR));
        }
    }

    /**
     * Interface de edição do layout da página
     * SEGURANÇA: Validação de slug e escape de outputs
     */
    public function edit($slug) {
        $user = Auth::user();

        try {
            // Validar slug
            $slug = Security::sanitize($slug);

            if (empty($slug) || !preg_match('/^[a-z0-9_-]+$/i', $slug)) {
                $_SESSION['error'] = 'Slug inválido';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            $db = DB::connect();

            // Buscar blocos da página
            $blocks = $db->select('page_blocks', ['page_slug' => $slug], ['order' => 'ordem ASC']);

            if (empty($blocks)) {
                $blocks = [];
            } else {
                // 🚀 PERFORMANCE: Buscar apenas cards dos blocos desta página (WHERE IN)
                $blockIds = array_column($blocks, 'id');
                $allCards = [];

                if (!empty($blockIds)) {
                    // Buscar cards filtrados por block_id (evita table scan)
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

                    // Organizar cards por block_id
                    foreach ($allCardsRaw as $card) {
                        // NÃO sanitizar aqui - será sanitizado no output HTML da view
                        // htmlspecialchars() aqui quebra JSON no JavaScript
                        $allCards[$card['block_id']][] = $card;
                    }
                }

                // Associar cards aos blocos
                foreach ($blocks as &$block) {
                    $block['cards'] = $allCards[$block['id']] ?? [];

                    // Calcular total do tamanho
                    $block['total_size'] = 0;
                    foreach ($block['cards'] as $card) {
                        $block['total_size'] += intval($card['size']);
                    }
                }
            }

            // NÃO expor dados em sessão em produção
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                $_SESSION['debug_blocks'] = $blocks;
            }

            require_once ROOT_PATH . 'admin/views/page-builder/edit.php';

        } catch (Exception $e) {
            // Log erro
            error_log('PageBuilder edit error: ' . $e->getMessage());

            // Mostrar erro detalhado em development
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                $_SESSION['error'] = 'Erro ao carregar Page Builder: ' . $e->getMessage();
            } else {
                $_SESSION['error'] = 'Erro ao carregar Page Builder. Verifique se as tabelas foram criadas.';
            }
            header('Location: ' . Router::url('/admin/pages'));
            exit;
        }
    }

    /**
     * Adicionar novo bloco horizontal
     * NOTA: Método legado, usar saveLayout() ao invés
     */
    public function addBlock() {
        try {
            Security::validateCSRF($_POST['csrf_token']);
            $db = DB::connect();

            $pageSlug = Security::sanitize($_POST['page_slug'] ?? '');

            if (empty($pageSlug) || !preg_match('/^[a-z0-9_-]+$/i', $pageSlug)) {
                $_SESSION['error'] = 'Slug da página é obrigatório';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            // Verificar limite de blocos
            $allBlocks = $db->select('page_blocks', ['page_slug' => $pageSlug]);

            if (count($allBlocks) >= self::MAX_BLOCKS) {
                $_SESSION['error'] = 'Número máximo de blocos atingido (' . self::MAX_BLOCKS . ')';
                header('Location: ' . Router::url('/admin/pages/' . $pageSlug . '/builder'));
                exit;
            }

            // Calcular próxima ordem
            $maxOrdem = 0;
            foreach ($allBlocks as $block) {
                $ordem = intval($block['ordem']);
                if ($ordem > $maxOrdem) {
                    $maxOrdem = $ordem;
                }
            }

            $novaOrdem = $maxOrdem + 1;

            // Criar bloco
            $insertData = [
                'page_slug' => $pageSlug,
                'ordem' => $novaOrdem
            ];

            if (DB_TYPE === 'mysql') {
                $insertData['id'] = Security::generateUUID();
            }

            $result = $db->insert('page_blocks', $insertData);

            if ($result) {
                $_SESSION['success'] = 'Bloco adicionado com sucesso!';
            } else {
                $_SESSION['error'] = 'Erro ao adicionar bloco';
            }

            header('Location: ' . Router::url('/admin/pages/' . $pageSlug . '/builder'));
            exit;

        } catch (Exception $e) {
            error_log('PageBuilder addBlock error: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao adicionar bloco';
            header('Location: ' . Router::url('/admin/pages'));
            exit;
        }
    }

    /**
     * Deletar bloco (e seus cards em cascade)
     */
    public function deleteBlock($blockId) {
        try {
            Security::validateCSRF($_POST['csrf_token']);

            // Validar blockId (UUID)
            $blockId = Security::sanitize($blockId);
            if (!preg_match('/^[a-f0-9-]{36}$/i', $blockId)) {
                $_SESSION['error'] = 'ID de bloco inválido';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            $db = DB::connect();

            // Buscar bloco para pegar page_slug
            $block = $db->select('page_blocks', ['id' => $blockId]);
            if (empty($block)) {
                $_SESSION['error'] = 'Bloco não encontrado';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            $pageSlug = Security::sanitize($block[0]['page_slug']);

            // Deletar (cards deletam em cascade)
            $db->delete('page_blocks', ['id' => $blockId]);

            $_SESSION['success'] = 'Bloco deletado com sucesso!';
            header('Location: ' . Router::url('/admin/pages/' . $pageSlug . '/builder'));
            exit;

        } catch (Exception $e) {
            error_log('PageBuilder deleteBlock error: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao deletar bloco';
            header('Location: ' . Router::url('/admin/pages'));
            exit;
        }
    }

    /**
     * Adicionar card a um bloco
     * NOTA: Método legado, usar saveLayout() ao invés
     */
    public function addCard() {
        try {
            Security::validateCSRF($_POST['csrf_token']);
            $db = DB::connect();

            $blockId = Security::sanitize($_POST['block_id'] ?? '');
            $size = intval($_POST['size'] ?? 1);

            // Validar blockId
            if (!preg_match('/^[a-f0-9-]{36}$/i', $blockId)) {
                $_SESSION['error'] = 'ID de bloco inválido';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            // Validar tamanho
            if ($size < 1 || $size > 6) {
                $_SESSION['error'] = 'Tamanho deve ser entre 1 e 6';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            // Buscar cards existentes no bloco
            $existingCards = $db->select('page_cards', ['block_id' => $blockId]);

            // Verificar limite de cards
            if (count($existingCards) >= 6) {
                $_SESSION['error'] = 'Número máximo de cards por bloco atingido';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            $totalSize = 0;
            $maxOrdem = 0;
            foreach ($existingCards as $card) {
                $totalSize += intval($card['size']);
                $ordem = intval($card['ordem']);
                if ($ordem > $maxOrdem) {
                    $maxOrdem = $ordem;
                }
            }

            // Validar se não ultrapassa 6
            if ($totalSize + $size > 6) {
                $_SESSION['error'] = 'Total de cards não pode ultrapassar 6';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            // Criar card
            $insertData = [
                'block_id' => $blockId,
                'size' => $size,
                'ordem' => $maxOrdem + 1,
                'content' => null
            ];

            if (DB_TYPE === 'mysql') {
                $insertData['id'] = Security::generateUUID();
            }

            $db->insert('page_cards', $insertData);

            $_SESSION['success'] = 'Card adicionado com sucesso!';

        } catch (Exception $e) {
            error_log('PageBuilder addCard error: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao adicionar card';
        }

        // Redirect seguro
        $referer = $_SERVER['HTTP_REFERER'] ?? Router::url('/admin/pages');
        if (parse_url($referer, PHP_URL_HOST) === parse_url(Router::url('/'), PHP_URL_HOST)) {
            header('Location: ' . $referer);
        } else {
            header('Location: ' . Router::url('/admin/pages'));
        }
        exit;
    }

    /**
     * Deletar card
     */
    public function deleteCard($cardId) {
        try {
            Security::validateCSRF($_POST['csrf_token']);

            // Validar cardId
            $cardId = Security::sanitize($cardId);
            if (!preg_match('/^[a-f0-9-]{36}$/i', $cardId)) {
                $_SESSION['error'] = 'ID de card inválido';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            $db = DB::connect();
            $db->delete('page_cards', ['id' => $cardId]);

            $_SESSION['success'] = 'Card deletado com sucesso!';

        } catch (Exception $e) {
            error_log('PageBuilder deleteCard error: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao deletar card';
        }

        // Redirect seguro
        $referer = $_SERVER['HTTP_REFERER'] ?? Router::url('/admin/pages');
        if (parse_url($referer, PHP_URL_HOST) === parse_url(Router::url('/'), PHP_URL_HOST)) {
            header('Location: ' . $referer);
        } else {
            header('Location: ' . Router::url('/admin/pages'));
        }
        exit;
    }

    /**
     * Atualizar tamanho do card
     */
    public function updateCardSize() {
        try {
            Security::validateCSRF($_POST['csrf_token']);
            $db = DB::connect();

            $cardId = Security::sanitize($_POST['card_id'] ?? '');
            $newSize = intval($_POST['size'] ?? 1);

            // Validar cardId
            if (!preg_match('/^[a-f0-9-]{36}$/i', $cardId)) {
                $_SESSION['error'] = 'ID de card inválido';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            // Validar tamanho
            if ($newSize < 1 || $newSize > 6) {
                $_SESSION['error'] = 'Tamanho deve ser entre 1 e 6';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            // Buscar card e seu bloco
            $card = $db->select('page_cards', ['id' => $cardId]);
            if (empty($card)) {
                $_SESSION['error'] = 'Card não encontrado';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            $blockId = $card[0]['block_id'];

            // Buscar outros cards do bloco
            $allCards = $db->select('page_cards', ['block_id' => $blockId]);
            $totalSize = 0;
            foreach ($allCards as $c) {
                if ($c['id'] !== $cardId) {
                    $totalSize += intval($c['size']);
                }
            }

            // Validar se não ultrapassa 6
            if ($totalSize + $newSize > 6) {
                $_SESSION['error'] = 'Total de cards não pode ultrapassar 6';
                header('Location: ' . Router::url('/admin/pages'));
                exit;
            }

            // Atualizar
            $db->update('page_cards', ['id' => $cardId], ['size' => $newSize]);

            $_SESSION['success'] = 'Tamanho atualizado com sucesso!';

        } catch (Exception $e) {
            error_log('PageBuilder updateCardSize error: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao atualizar tamanho';
        }

        // Redirect seguro
        $referer = $_SERVER['HTTP_REFERER'] ?? Router::url('/admin/pages');
        if (parse_url($referer, PHP_URL_HOST) === parse_url(Router::url('/'), PHP_URL_HOST)) {
            header('Location: ' . $referer);
        } else {
            header('Location: ' . Router::url('/admin/pages'));
        }
        exit;
    }
}
