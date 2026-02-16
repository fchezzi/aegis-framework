<?php
/**
 * Jogos Controller
 * Gerencia CRUD de jogos
 */

class JogosController {

    /**
     * Criar novo jogo
     */
    public function store() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/jogos?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        $required = ['time_mandante_id', 'time_visitante_id', 'data_jogo', 'campeonato'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                Core::redirect('/admin/palpites/jogos/create?error=' . urlencode(ucfirst($field) . ' é obrigatório'));
            }
        }

        $time_mandante_id = Security::sanitize($_POST['time_mandante_id']);
        $time_visitante_id = Security::sanitize($_POST['time_visitante_id']);
        $data_jogo = Security::sanitize($_POST['data_jogo']);
        $campeonato = Security::sanitize($_POST['campeonato']);
        $rodada = isset($_POST['rodada']) ? Security::sanitize($_POST['rodada']) : null;
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Validar times diferentes
        if ($time_mandante_id === $time_visitante_id) {
            Core::redirect('/admin/palpites/jogos/create?error=' . urlencode('Times devem ser diferentes'));
        }

        try {
            $db = DB::connect();

            // Gerar UUID se MySQL
            $id = null;
            if (DB_TYPE === 'mysql') {
                $id = Core::generateUUID();
            }

            $data = [
                'time_mandante_id' => $time_mandante_id,
                'time_visitante_id' => $time_visitante_id,
                'data_jogo' => $data_jogo,
                'campeonato' => $campeonato,
                'rodada' => $rodada,
                'ativo' => $ativo
            ];

            if ($id) {
                $data['id'] = $id;
            }

            $db->insert('tbl_jogos_palpites', $data);

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/jogos?success=' . urlencode('Jogo criado com sucesso'));

        } catch (Exception $e) {
            Core::redirect('/admin/palpites/jogos/create?error=' . urlencode('Erro: ' . $e->getMessage()));
        }
    }

    /**
     * Atualizar jogo
     */
    public function update() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/jogos?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/jogos?error=' . urlencode('ID não informado'));
        }

        $id = Security::sanitize($_POST['id']);

        // Validar dados
        $required = ['time_mandante_id', 'time_visitante_id', 'data_jogo', 'campeonato'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                Core::redirect('/admin/palpites/jogos/' . $id . '/edit?error=' . urlencode(ucfirst($field) . ' é obrigatório'));
            }
        }

        $time_mandante_id = Security::sanitize($_POST['time_mandante_id']);
        $time_visitante_id = Security::sanitize($_POST['time_visitante_id']);
        $data_jogo = Security::sanitize($_POST['data_jogo']);
        $campeonato = Security::sanitize($_POST['campeonato']);
        $rodada = isset($_POST['rodada']) ? Security::sanitize($_POST['rodada']) : null;
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Validar times diferentes
        if ($time_mandante_id === $time_visitante_id) {
            Core::redirect('/admin/palpites/jogos/' . $id . '/edit?error=' . urlencode('Times devem ser diferentes'));
        }

        try {
            $db = DB::connect();

            $db->update('tbl_jogos_palpites', [
                'time_mandante_id' => $time_mandante_id,
                'time_visitante_id' => $time_visitante_id,
                'data_jogo' => $data_jogo,
                'campeonato' => $campeonato,
                'rodada' => $rodada,
                'ativo' => $ativo
            ], ['id' => $id]);

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/jogos?success=' . urlencode('Jogo atualizado com sucesso'));

        } catch (Exception $e) {
            Core::redirect('/admin/palpites/jogos/' . $id . '/edit?error=' . urlencode('Erro: ' . $e->getMessage()));
        }
    }

    /**
     * Deletar jogo
     */
    public function delete() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/jogos?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/jogos?error=' . urlencode('ID não informado'));
        }

        $id = Security::sanitize($_POST['id']);

        try {
            $db = DB::connect();
            $db->delete('tbl_jogos_palpites', ['id' => $id]);

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/jogos?success=' . urlencode('Jogo deletado com sucesso'));

        } catch (Exception $e) {
            Core::redirect('/admin/palpites/jogos?error=' . urlencode('Erro: ' . $e->getMessage()));
        }
    }
}
