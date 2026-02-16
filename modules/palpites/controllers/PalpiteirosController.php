<?php
/**
 * Palpiteiros Controller
 * Gerencia CRUD de palpiteiros
 */

class PalpiteirosController {

    /**
     * Criar novo palpiteiro
     */
    public function store() {
        error_log("=== STORE PALPITEIRO INICIADO ===");

        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            error_log("ERRO: CSRF token ausente");
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Token CSRF ausente'));
        }

        error_log("CSRF token presente, validando...");
        Security::validateCSRF($_POST['csrf_token']);
        error_log("CSRF validado com sucesso");

        // Validar dados
        if (!isset($_POST['nome']) || empty(trim($_POST['nome']))) {
            error_log("ERRO: Nome não informado");
            Core::redirect('/admin/palpites/palpiteiros/create?error=' . urlencode('Nome é obrigatório'));
        }

        $nome = Security::sanitize($_POST['nome']);
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $foto_url = null;
        error_log("Dados validados: nome=$nome, ativo=$ativo");

        // Processar upload de foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            error_log("Upload de foto detectado, processando...");
            $upload = Upload::image($_FILES['foto'], 'palpiteiros');

            if (!$upload['success']) {
                error_log("ERRO no upload: " . $upload['message']);
                Core::redirect('/admin/palpites/palpiteiros/create?error=' . urlencode($upload['message']));
            }

            $foto_url = $upload['path'];
            error_log("Upload OK: $foto_url");
        } else {
            error_log("Sem upload de foto");
        }

        try {
            error_log("Conectando ao banco...");
            $db = DB::connect();
            error_log("Conectado ao banco");

            // Obter proxima ordem
            $result = $db->query("SELECT COALESCE(MAX(ordem), 0) + 1 as proxima_ordem FROM tbl_palpiteiros");
            $proxima_ordem = $result[0]['proxima_ordem'] ?? 1;

            $palpiteiro_id = $db->insert('tbl_palpiteiros', [
                'nome' => $nome,
                'ativo' => $ativo,
                'foto_url' => $foto_url,
                'ordem' => $proxima_ordem
            ]);

            // Verificar se inseriu
            if (!$palpiteiro_id) {
                throw new Exception('Falha ao inserir palpiteiro no banco de dados');
            }

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpiteiros?success=' . urlencode('Palpiteiro criado com sucesso'));

        } catch (Exception $e) {
            // Log do erro
            error_log("ERRO ao criar palpiteiro: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Deletar foto se foi feito upload
            if ($foto_url) {
                Upload::delete($foto_url);
            }

            Core::redirect('/admin/palpites/palpiteiros/create?error=' . urlencode('Erro ao criar palpiteiro: ' . $e->getMessage()));
        }
    }

    /**
     * Atualizar palpiteiro
     */
    public function update() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('ID não informado'));
        }

        if (!isset($_POST['nome']) || empty(trim($_POST['nome']))) {
            Core::redirect('/admin/palpites/palpiteiros/' . $_POST['id'] . '/edit?error=' . urlencode('Nome é obrigatório'));
        }

        $id = Security::sanitize($_POST['id']);
        $nome = Security::sanitize($_POST['nome']);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        try {
            $db = DB::connect();

            // Preparar dados para atualizar
            $data = [
                'nome' => $nome,
                'ativo' => $ativo
            ];

            // Processar upload de nova foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                // Buscar foto antiga para deletar
                $palpiteiro = $db->select('tbl_palpiteiros', ['id' => $id]);
                if (!empty($palpiteiro) && !empty($palpiteiro[0]['foto_url'])) {
                    Upload::delete($palpiteiro[0]['foto_url']);
                }

                // Upload nova foto
                $upload = Upload::image($_FILES['foto'], 'palpiteiros');

                if (!$upload['success']) {
                    Core::redirect('/admin/palpites/palpiteiros/' . $id . '/edit?error=' . urlencode($upload['message']));
                }

                $data['foto_url'] = $upload['path'];
            }

            $updated = $db->update('tbl_palpiteiros', $data, ['id' => $id]);

            // Verificar se atualizou
            if ($updated === false) {
                throw new Exception('Falha ao atualizar palpiteiro no banco de dados');
            }

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpiteiros?success=' . urlencode('Palpiteiro atualizado com sucesso'));

        } catch (Exception $e) {
            // Log do erro
            error_log("ERRO ao atualizar palpiteiro ID={$id}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            Core::redirect('/admin/palpites/palpiteiros/' . $id . '/edit?error=' . urlencode('Erro ao atualizar: ' . $e->getMessage()));
        }
    }

    /**
     * Deletar palpiteiro
     */
    public function delete() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('ID não informado'));
        }

        $id = Security::sanitize($_POST['id']);

        try {
            $db = DB::connect();

            // Buscar foto para deletar
            $palpiteiro = $db->select('tbl_palpiteiros', ['id' => $id]);
            if (!empty($palpiteiro) && !empty($palpiteiro[0]['foto_url'])) {
                Upload::delete($palpiteiro[0]['foto_url']);
            }

            $deleted = $db->delete('tbl_palpiteiros', ['id' => $id]);

            // Verificar se deletou
            if ($deleted === false) {
                throw new Exception('Falha ao deletar palpiteiro do banco de dados');
            }

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpiteiros?success=' . urlencode('Palpiteiro deletado com sucesso'));

        } catch (Exception $e) {
            // Log do erro
            error_log("ERRO ao deletar palpiteiro ID={$id}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Erro ao deletar: ' . $e->getMessage()));
        }
    }

    /**
     * Mover palpiteiro para cima (diminuir ordem)
     */
    public function moveUp() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('ID nao informado'));
        }

        $id = Security::sanitize($_POST['id']);

        try {
            $db = DB::connect();

            // Buscar palpiteiro atual
            $current = $db->select('tbl_palpiteiros', ['id' => $id]);
            if (empty($current)) {
                Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Palpiteiro nao encontrado'));
            }

            $current = $current[0];
            $currentOrdem = $current['ordem'];

            // Buscar palpiteiro anterior (ordem menor mais proxima)
            $previous = $db->query("SELECT * FROM tbl_palpiteiros WHERE ordem < {$currentOrdem} ORDER BY ordem DESC LIMIT 1");

            if (empty($previous)) {
                // Ja esta no topo
                Core::redirect('/admin/palpites/palpiteiros');
            }

            $previous = $previous[0];
            $previousOrdem = $previous['ordem'];

            // Trocar ordens
            $db->update('tbl_palpiteiros', ['ordem' => $previousOrdem], ['id' => $id]);
            $db->update('tbl_palpiteiros', ['ordem' => $currentOrdem], ['id' => $previous['id']]);

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpiteiros?success=' . urlencode('Ordem alterada com sucesso'));

        } catch (Exception $e) {
            error_log("ERRO ao mover palpiteiro para cima ID={$id}: " . $e->getMessage());
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Erro ao alterar ordem: ' . $e->getMessage()));
        }
    }

    /**
     * Mover palpiteiro para baixo (aumentar ordem)
     */
    public function moveDown() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('ID nao informado'));
        }

        $id = Security::sanitize($_POST['id']);

        try {
            $db = DB::connect();

            // Buscar palpiteiro atual
            $current = $db->select('tbl_palpiteiros', ['id' => $id]);
            if (empty($current)) {
                Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Palpiteiro nao encontrado'));
            }

            $current = $current[0];
            $currentOrdem = $current['ordem'];

            // Buscar palpiteiro seguinte (ordem maior mais proxima)
            $next = $db->query("SELECT * FROM tbl_palpiteiros WHERE ordem > {$currentOrdem} ORDER BY ordem ASC LIMIT 1");

            if (empty($next)) {
                // Ja esta no final
                Core::redirect('/admin/palpites/palpiteiros');
            }

            $next = $next[0];
            $nextOrdem = $next['ordem'];

            // Trocar ordens
            $db->update('tbl_palpiteiros', ['ordem' => $nextOrdem], ['id' => $id]);
            $db->update('tbl_palpiteiros', ['ordem' => $currentOrdem], ['id' => $next['id']]);

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpiteiros?success=' . urlencode('Ordem alterada com sucesso'));

        } catch (Exception $e) {
            error_log("ERRO ao mover palpiteiro para baixo ID={$id}: " . $e->getMessage());
            Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Erro ao alterar ordem: ' . $e->getMessage()));
        }
    }
}
