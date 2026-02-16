<?php
/**
 * Resultados Controller
 * Gerencia cadastro de resultados reais (DEPOIS do jogo acontecer)
 */

class ResultadosController {

    /**
     * Cadastrar resultado real do jogo
     *
     * @api Resultados
     * @method POST /admin/palpites/resultados/cadastrar
     * @description
     * Registra o resultado real de um jogo e recalcula automaticamente o ranking.
     * Atualiza tbl_jogos_palpites, cria/atualiza tbl_resultados_reais e dispara
     * refresh_ranking() para atualizar cache de pontuação.
     * Marca o jogo como finalizado (ativo=false).
     *
     * @param string jogo_id UUID do jogo
     * @param int gols_mandante Gols marcados pelo time mandante
     * @param int gols_visitante Gols marcados pelo time visitante
     *
     * @return redirect Redireciona para tela de conferência com pontuação calculada
     *
     * @example
     * <form method="POST" action="/admin/palpites/resultados/cadastrar">
     *   <input name="jogo_id" value="abc-123">
     *   <input name="gols_mandante" type="number" value="3">
     *   <input name="gols_visitante" type="number" value="1">
     *   <button>Salvar Resultado</button>
     * </form>
     */
    public function cadastrar() {
        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/palpites/resultados?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar jogo_id
        if (!isset($_POST['jogo_id']) || empty($_POST['jogo_id'])) {
            Core::redirect('/admin/palpites/resultados?error=' . urlencode('ID do jogo não informado'));
        }

        $jogo_id = Security::sanitize($_POST['jogo_id']);

        // Validar resultado real
        if (!isset($_POST['gols_mandante']) || !isset($_POST['gols_visitante'])) {
            Core::redirect('/admin/palpites/resultados/' . $jogo_id . '?error=' . urlencode('Resultado real é obrigatório'));
        }

        $gols_mandante = (int) $_POST['gols_mandante'];
        $gols_visitante = (int) $_POST['gols_visitante'];

        try {
            $db = DB::connect();

            // 1. Atualizar jogo com resultado real
            $db->update('tbl_jogos_palpites', [
                'gols_mandante_real' => $gols_mandante,
                'gols_visitante_real' => $gols_visitante,
                'exibir_resultado' => true,
                'ativo' => false  // Jogo finalizado, não aceita mais palpites
            ], ['id' => $jogo_id]);

            // 2. Inserir/atualizar em tbl_resultados_reais
            $resultado_existe = $db->select('tbl_resultados_reais', ['jogo_id' => $jogo_id]);

            if (!empty($resultado_existe)) {
                // Atualizar
                $db->update('tbl_resultados_reais', [
                    'gols_mandante' => $gols_mandante,
                    'gols_visitante' => $gols_visitante
                ], ['jogo_id' => $jogo_id]);
            } else {
                // Inserir
                $resultado_id = null;
                if (DB_TYPE === 'mysql') {
                    $resultado_id = Core::generateUUID();
                }

                $data = [
                    'jogo_id' => $jogo_id,
                    'gols_mandante' => $gols_mandante,
                    'gols_visitante' => $gols_visitante
                ];

                if ($resultado_id) {
                    $data['id'] = $resultado_id;
                }

                $db->insert('tbl_resultados_reais', $data);
            }

            // 3. ⚡ ATUALIZAR RANKING (Materialized Views)
            try {
                if (DB_TYPE === 'supabase') {
                    $db->query("SELECT refresh_ranking()");
                }
            } catch (Exception $e) {
                // Falha silenciosa: ranking será atualizado na próxima vez
                error_log("Aviso: Falha ao atualizar ranking: " . $e->getMessage());
            }

            // 4. Invalidar cache
            SimpleCache::flushPattern('palpites_*');

            Core::redirect('/admin/palpites/resultados?success=' . urlencode('Resultado cadastrado com sucesso'));

        } catch (Exception $e) {
            Core::redirect('/admin/palpites/resultados/' . $jogo_id . '?error=' . urlencode('Erro: ' . $e->getMessage()));
        }
    }
}
