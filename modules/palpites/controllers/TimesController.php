<?php
/**
 * Times Controller
 * Gerencia CRUD de times
 */

class TimesController {

    /**
     * Criar novo time
     */
    public function store() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/times?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        if (!isset($_POST['nome']) || empty(trim($_POST['nome']))) {
            Core::redirect('/admin/palpites/times/create?error=' . urlencode('Nome é obrigatório'));
        }

        if (!isset($_POST['sigla']) || empty(trim($_POST['sigla']))) {
            Core::redirect('/admin/palpites/times/create?error=' . urlencode('Sigla é obrigatória'));
        }

        $nome = Security::sanitize($_POST['nome']);
        $sigla = Security::sanitize($_POST['sigla']);
        $escudo_url = null;

        // Processar upload de escudo
        if (isset($_FILES['escudo']) && $_FILES['escudo']['error'] === UPLOAD_ERR_OK) {
            // Usar sigla como nome do arquivo
            $upload = Upload::image($_FILES['escudo'], 'times', [
                'customName' => strtolower($sigla)
            ]);

            if (!$upload['success']) {
                Core::redirect('/admin/palpites/times/create?error=' . urlencode($upload['message']));
            }

            $escudo_url = $upload['path'];
        }

        try {
            $db = DB::connect();

            $time_id = $db->insert('tbl_times', [
                'nome' => $nome,
                'sigla' => $sigla,
                'escudo_url' => $escudo_url
            ]);

            if (!$time_id) {
                throw new Exception('Falha ao inserir time no banco de dados');
            }

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/times?success=' . urlencode('Time criado com sucesso'));

        } catch (Exception $e) {
            error_log("ERRO ao criar time: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Deletar escudo se foi feito upload
            if ($escudo_url) {
                Upload::delete($escudo_url);
            }

            Core::redirect('/admin/palpites/times/create?error=' . urlencode('Erro ao criar time: ' . $e->getMessage()));
        }
    }

    /**
     * Atualizar time
     */
    public function update() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/times?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/times?error=' . urlencode('ID não informado'));
        }

        if (!isset($_POST['nome']) || empty(trim($_POST['nome']))) {
            Core::redirect('/admin/palpites/times/' . $_POST['id'] . '/edit?error=' . urlencode('Nome é obrigatório'));
        }

        if (!isset($_POST['sigla']) || empty(trim($_POST['sigla']))) {
            Core::redirect('/admin/palpites/times/' . $_POST['id'] . '/edit?error=' . urlencode('Sigla é obrigatória'));
        }

        $id = Security::sanitize($_POST['id']);
        $nome = Security::sanitize($_POST['nome']);
        $sigla = Security::sanitize($_POST['sigla']);

        try {
            $db = DB::connect();

            // Preparar dados para atualizar
            $data = [
                'nome' => $nome,
                'sigla' => $sigla
            ];

            // Processar upload de novo escudo
            if (isset($_FILES['escudo']) && $_FILES['escudo']['error'] === UPLOAD_ERR_OK) {
                // Buscar escudo antigo para deletar
                $time = $db->select('tbl_times', ['id' => $id]);
                if (!empty($time) && !empty($time[0]['escudo_url'])) {
                    Upload::delete($time[0]['escudo_url']);
                }

                // Upload novo escudo usando sigla como nome
                $upload = Upload::image($_FILES['escudo'], 'times', [
                    'customName' => strtolower($sigla)
                ]);

                if (!$upload['success']) {
                    Core::redirect('/admin/palpites/times/' . $id . '/edit?error=' . urlencode($upload['message']));
                }

                $data['escudo_url'] = $upload['path'];
            }

            $updated = $db->update('tbl_times', $data, ['id' => $id]);

            if ($updated === false) {
                throw new Exception('Falha ao atualizar time no banco de dados');
            }

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/times?success=' . urlencode('Time atualizado com sucesso'));

        } catch (Exception $e) {
            error_log("ERRO ao atualizar time ID={$id}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            Core::redirect('/admin/palpites/times/' . $id . '/edit?error=' . urlencode('Erro ao atualizar: ' . $e->getMessage()));
        }
    }

    /**
     * Deletar time
     */
    public function delete() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/times?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            Core::redirect('/admin/palpites/times?error=' . urlencode('ID não informado'));
        }

        $id = Security::sanitize($_POST['id']);

        try {
            $db = DB::connect();

            // Buscar escudo para deletar
            $time = $db->select('tbl_times', ['id' => $id]);
            if (!empty($time) && !empty($time[0]['escudo_url'])) {
                Upload::delete($time[0]['escudo_url']);
            }

            $deleted = $db->delete('tbl_times', ['id' => $id]);

            if ($deleted === false) {
                throw new Exception('Falha ao deletar time do banco de dados');
            }

            // Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/times?success=' . urlencode('Time deletado com sucesso'));

        } catch (Exception $e) {
            error_log("ERRO ao deletar time ID={$id}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            Core::redirect('/admin/palpites/times?error=' . urlencode('Erro ao deletar: ' . $e->getMessage()));
        }
    }
}
