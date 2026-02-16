<?php
/**
 * Palpites Controller
 * Gerencia cadastro de palpites (ANTES do jogo acontecer)
 */

class PalpitesController {

    /**
     * Criar novo palpite
     *
     * @api Palpites
     * @method POST /admin/palpites/palpites/store
     * @description
     * Registra um novo palpite para um jogo ativo.
     * Valida CSRF, verifica se jogo aceita palpites e previne duplicatas.
     * Invalida cache após inserção.
     *
     * @param string jogo_id UUID do jogo
     * @param string palpiteiro_id UUID do palpiteiro
     * @param int gols_mandante Gols do time mandante
     * @param int gols_visitante Gols do time visitante
     *
     * @return redirect Redireciona para lista com mensagem de sucesso/erro
     *
     * @example
     * <form method="POST" action="/admin/palpites/palpites/store">
     *   <input name="jogo_id" value="uuid-here">
     *   <input name="palpiteiro_id" value="uuid-here">
     *   <input name="gols_mandante" type="number" value="2">
     *   <input name="gols_visitante" type="number" value="1">
     *   <button type="submit">Salvar Palpite</button>
     * </form>
     */
    public function store() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpites?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        if (!isset($_POST['jogo_id']) || empty($_POST['jogo_id'])) {
            Core::redirect('/admin/palpites/palpites/create?error=' . urlencode('Jogo é obrigatório'));
        }

        if (!isset($_POST['palpiteiro_id']) || empty($_POST['palpiteiro_id'])) {
            Core::redirect('/admin/palpites/palpites/create?error=' . urlencode('Palpiteiro é obrigatório'));
        }

        if (!isset($_POST['gols_mandante']) || !isset($_POST['gols_visitante'])) {
            Core::redirect('/admin/palpites/palpites/create?error=' . urlencode('Placar é obrigatório'));
        }

        $jogo_id = Security::sanitize($_POST['jogo_id']);
        $palpiteiro_id = Security::sanitize($_POST['palpiteiro_id']);
        $gols_mandante = (int) $_POST['gols_mandante'];
        $gols_visitante = (int) $_POST['gols_visitante'];

        try {
            $db = DB::connect();

            // Verificar se jogo está ativo (aceita palpites)
            $jogo = $db->select('tbl_jogos_palpites', ['id' => $jogo_id]);
            if (empty($jogo)) {
                Core::redirect('/admin/palpites/palpites/create?error=' . urlencode('Jogo não encontrado'));
            }

            if (!$jogo[0]['ativo']) {
                Core::redirect('/admin/palpites/palpites/create?error=' . urlencode('Este jogo não está mais aceitando palpites'));
            }

            // Verificar se já existe palpite deste palpiteiro neste jogo
            // ✅ SEGURANÇA: Query parametrizada (previne SQL Injection)
            $palpite_existe = $db->select('tbl_palpites', [
                'jogo_id' => $jogo_id,
                'palpiteiro_id' => $palpiteiro_id
            ]);

            if (!empty($palpite_existe)) {
                Core::redirect('/admin/palpites/palpites/create?error=' . urlencode('Este palpiteiro já deu palpite neste jogo. Use Editar para alterar.'));
            }

            // Gerar UUID se MySQL
            $palpite_id = null;
            if (DB_TYPE === 'mysql') {
                $palpite_id = Core::generateUUID();
            }

            $data = [
                'jogo_id' => $jogo_id,
                'palpiteiro_id' => $palpiteiro_id,
                'gols_mandante' => $gols_mandante,
                'gols_visitante' => $gols_visitante
            ];

            if ($palpite_id) {
                $data['id'] = $palpite_id;
            }

            $db->insert('tbl_palpites', $data);

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpites?success=' . urlencode('Palpite cadastrado com sucesso'));

        } catch (Exception $e) {
            error_log("ERRO ao criar palpite: " . $e->getMessage());
            Core::redirect('/admin/palpites/palpites/create?error=' . urlencode('Erro ao cadastrar palpite: ' . $e->getMessage()));
        }
    }

    /**
     * Atualizar palpite
     */
    public function update() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpites?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/palpites?error=' . urlencode('ID não informado'));
        }

        if (!isset($_POST['gols_mandante']) || !isset($_POST['gols_visitante'])) {
            Core::redirect('/admin/palpites/palpites?error=' . urlencode('Placar é obrigatório'));
        }

        $id = Security::sanitize($_POST['id']);
        $gols_mandante = (int) $_POST['gols_mandante'];
        $gols_visitante = (int) $_POST['gols_visitante'];

        try {
            $db = DB::connect();

            // Buscar palpite
            $palpite = $db->select('tbl_palpites', ['id' => $id]);
            if (empty($palpite)) {
                Core::redirect('/admin/palpites/palpites?error=' . urlencode('Palpite não encontrado'));
            }

            // Verificar se jogo ainda está ativo
            $jogo = $db->select('tbl_jogos_palpites', ['id' => $palpite[0]['jogo_id']]);
            if (!empty($jogo) && !$jogo[0]['ativo']) {
                Core::redirect('/admin/palpites/palpites/' . $id . '/edit?error=' . urlencode('Jogo não está mais aceitando alterações'));
            }

            $updated = $db->update('tbl_palpites', [
                'gols_mandante' => $gols_mandante,
                'gols_visitante' => $gols_visitante
            ], ['id' => $id]);

            if ($updated === false) {
                throw new Exception('Falha ao atualizar palpite no banco de dados');
            }

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpites?success=' . urlencode('Palpite atualizado com sucesso'));

        } catch (Exception $e) {
            error_log("ERRO ao atualizar palpite ID={$id}: " . $e->getMessage());
            Core::redirect('/admin/palpites/palpites/' . $id . '/edit?error=' . urlencode('Erro ao atualizar: ' . $e->getMessage()));
        }
    }

    /**
     * Deletar palpite
     */
    public function delete() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpites?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/palpites?error=' . urlencode('ID não informado'));
        }

        $id = Security::sanitize($_POST['id']);

        try {
            $db = DB::connect();

            $deleted = $db->delete('tbl_palpites', ['id' => $id]);

            if ($deleted === false) {
                throw new Exception('Falha ao deletar palpite do banco de dados');
            }

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpites?success=' . urlencode('Palpite deletado com sucesso'));

        } catch (Exception $e) {
            error_log("ERRO ao deletar palpite ID={$id}: " . $e->getMessage());
            Core::redirect('/admin/palpites/palpites?error=' . urlencode('Erro ao deletar: ' . $e->getMessage()));
        }
    }

    /**
     * Salvar palpite ao vivo (INSERT ou UPDATE)
     */
    public function salvarAoVivo() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpites/ao-vivo?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        if (!isset($_POST['jogo_id']) || empty($_POST['jogo_id'])) {
            Core::redirect('/admin/palpites/palpites/ao-vivo?error=' . urlencode('Jogo não informado'));
        }

        if (!isset($_POST['palpiteiro_id']) || empty($_POST['palpiteiro_id'])) {
            $jogo_id = Security::sanitize($_POST['jogo_id']);
            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&error=' . urlencode('Palpiteiro não informado'));
        }

        // Validar placar
        if (!isset($_POST['gols_mandante']) || !isset($_POST['gols_visitante'])) {
            $jogo_id = Security::sanitize($_POST['jogo_id']);
            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&error=' . urlencode('Placar é obrigatório'));
        }

        // Se campos vazios, redirecionar sem erro (usuário pode ter clicado por engano)
        if ($_POST['gols_mandante'] === '' && $_POST['gols_visitante'] === '') {
            $jogo_id = Security::sanitize($_POST['jogo_id']);
            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id);
        }

        $jogo_id = Security::sanitize($_POST['jogo_id']);
        $palpiteiro_id = Security::sanitize($_POST['palpiteiro_id']);
        $gols_mandante = (int) $_POST['gols_mandante'];
        $gols_visitante = (int) $_POST['gols_visitante'];
        $palpite_id = $_POST['palpite_id'] ?? null;

        try {
            $db = DB::connect();

            // Verificar se jogo está ativo
            $jogo = $db->select('tbl_jogos_palpites', ['id' => $jogo_id]);
            if (empty($jogo) || !$jogo[0]['ativo']) {
                Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&error=' . urlencode('Este jogo não está aceitando palpites'));
            }

            if ($palpite_id) {
                // ATUALIZAR palpite existente
                $db->update('tbl_palpites', [
                    'gols_mandante' => $gols_mandante,
                    'gols_visitante' => $gols_visitante
                ], ['id' => $palpite_id]);

                $mensagem = '💾 Palpite atualizado!';
            } else {
                // INSERIR novo palpite
                $novo_id = null;
                if (DB_TYPE === 'mysql') {
                    $novo_id = Core::generateUUID();
                }

                $data = [
                    'jogo_id' => $jogo_id,
                    'palpiteiro_id' => $palpiteiro_id,
                    'gols_mandante' => $gols_mandante,
                    'gols_visitante' => $gols_visitante
                ];

                if ($novo_id) {
                    $data['id'] = $novo_id;
                }

                $db->insert('tbl_palpites', $data);

                $mensagem = '✅ Palpite cadastrado!';
            }

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&success=' . urlencode($mensagem));

        } catch (Exception $e) {
            error_log("ERRO ao salvar palpite: " . $e->getMessage());
            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&error=' . urlencode('Erro: ' . $e->getMessage()));
        }
    }

    /**
     * Adicionar palpite ao vivo (durante o programa)
     * @deprecated Use salvarAoVivo() instead
     */
    public function adicionarAoVivo() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpites/ao-vivo?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        if (!isset($_POST['jogo_id']) || empty($_POST['jogo_id'])) {
            Core::redirect('/admin/palpites/palpites/ao-vivo?error=' . urlencode('Selecione o jogo'));
        }

        if (!isset($_POST['palpiteiro_id']) || empty($_POST['palpiteiro_id'])) {
            $jogo_id = Security::sanitize($_POST['jogo_id']);
            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&error=' . urlencode('Selecione o palpiteiro'));
        }

        if (!isset($_POST['gols_mandante']) || !isset($_POST['gols_visitante'])) {
            $jogo_id = Security::sanitize($_POST['jogo_id']);
            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&error=' . urlencode('Placar é obrigatório'));
        }

        $jogo_id = Security::sanitize($_POST['jogo_id']);
        $palpiteiro_id = Security::sanitize($_POST['palpiteiro_id']);
        $gols_mandante = (int) $_POST['gols_mandante'];
        $gols_visitante = (int) $_POST['gols_visitante'];

        try {
            $db = DB::connect();

            // Verificar se jogo está ativo
            $jogo = $db->select('tbl_jogos_palpites', ['id' => $jogo_id]);
            if (empty($jogo) || !$jogo[0]['ativo']) {
                Core::redirect('/admin/palpites/palpites/ao-vivo?error=' . urlencode('Este jogo não está aceitando palpites'));
            }

            // Verificar se já existe palpite
            // ✅ SEGURANÇA: Query parametrizada (previne SQL Injection)
            $palpite_existe = $db->select('tbl_palpites', [
                'jogo_id' => $jogo_id,
                'palpiteiro_id' => $palpiteiro_id
            ]);

            if (!empty($palpite_existe)) {
                Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&error=' . urlencode('Este palpiteiro já deu palpite neste jogo'));
            }

            // Gerar UUID se MySQL
            $palpite_id = null;
            if (DB_TYPE === 'mysql') {
                $palpite_id = Core::generateUUID();
            }

            $data = [
                'jogo_id' => $jogo_id,
                'palpiteiro_id' => $palpiteiro_id,
                'gols_mandante' => $gols_mandante,
                'gols_visitante' => $gols_visitante
            ];

            if ($palpite_id) {
                $data['id'] = $palpite_id;
            }

            $db->insert('tbl_palpites', $data);

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&success=' . urlencode('✅ Palpite adicionado!'));

        } catch (Exception $e) {
            error_log("ERRO ao adicionar palpite: " . $e->getMessage());
            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&error=' . urlencode('Erro: ' . $e->getMessage()));
        }
    }

    /**
     * Deletar palpite ao vivo
     */
    public function deletarAoVivo() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/palpites/ao-vivo?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        $palpite_id = $_POST['palpite_id'] ?? '';
        $jogo_id = $_POST['jogo_id'] ?? '';

        if (empty($palpite_id)) {
            Core::redirect('/admin/palpites/palpites/ao-vivo?error=' . urlencode('ID do palpite não informado'));
        }

        try {
            $db = DB::connect();
            $db->delete('tbl_palpites', ['id' => $palpite_id]);

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&success=' . urlencode('🗑️ Palpite removido!'));

        } catch (Exception $e) {
            error_log("ERRO ao deletar palpite: " . $e->getMessage());
            Core::redirect('/admin/palpites/palpites/ao-vivo?jogo_id=' . $jogo_id . '&error=' . urlencode('Erro ao deletar'));
        }
    }
}
