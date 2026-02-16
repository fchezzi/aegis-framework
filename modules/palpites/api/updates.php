<?php
/**
 * API de Updates para Tela de Exibição
 * Retorna apenas dados que mudaram (leve e rápido)
 *
 * @api Palpites
 * @method GET /modules/palpites/api/updates.php
 * @description
 * Retorna atualizações em tempo real para a tela de exibição.
 * Sistema de cache inteligente de 5 segundos para reduzir carga no banco.
 * Busca jogo ativo, ranking top 10 e palpites do jogo atual.
 *
 * @return object JSON com {success, timestamp, ranking, palpites, jogo_id}
 *
 * @example
 * fetch('/modules/palpites/api/updates.php')
 *   .then(r => r.json())
 *   .then(data => {
 *     console.log('Ranking:', data.ranking);
 *     console.log('Palpites:', data.palpites);
 *   });
 */

// Headers JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // OBS pode estar em iframe

// Carregar framework
require_once __DIR__ . '/../../../_config.php';
require_once __DIR__ . '/../../../core/Autoloader.php';

try {
    $db = DB::connect();

    // Cache de 5 segundos (evitar sobrecarga)
    $cacheKey = 'palpites_api_updates_' . floor(time() / 5);
    $cached = Cache::get($cacheKey);

    if ($cached) {
        echo $cached;
        exit;
    }

    // Buscar jogo ativo
    $jogo_ativo = $db->query("
        SELECT id
        FROM tbl_jogos_palpites
        WHERE ativo = true
        ORDER BY data_jogo DESC
        LIMIT 1
    ");

    $jogo_id = !empty($jogo_ativo) ? $jogo_ativo[0]['id'] : null;

    // Buscar ranking do cache (MUITO mais rápido que view)
    $ranking = $db->query("
        SELECT
            palpiteiro_id,
            total_pontos
        FROM cache_ranking_palpiteiros
        ORDER BY total_pontos DESC, placares_exatos DESC
        LIMIT 10
    ");

    // Fallback para view se cache não existir
    if (empty($ranking)) {
        $ranking = $db->query("
            SELECT
                palpiteiro_id,
                total_pontos
            FROM vw_ranking_palpiteiros
            ORDER BY total_pontos DESC, placares_exatos DESC
            LIMIT 10
        ");
    }

    // Buscar palpites do jogo ativo (se houver)
    $palpites = [];
    if ($jogo_id) {
        $palpites = $db->query("
            SELECT
                p.palpiteiro_id,
                p.gols_mandante,
                p.gols_visitante
            FROM tbl_palpites p
            JOIN tbl_palpiteiros pa ON p.palpiteiro_id = pa.id
            WHERE p.jogo_id = ? AND pa.ativo = true
            ORDER BY pa.ordem ASC
        ", [$jogo_id]);
    }

    // Resposta JSON
    $response = [
        'success' => true,
        'timestamp' => time(),
        'ranking' => $ranking ?? [],
        'palpites' => $palpites,
        'jogo_id' => $jogo_id
    ];

    $json = json_encode($response);

    // Salvar no cache por 5 segundos
    Cache::set($cacheKey, $json, 5);

    echo $json;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
