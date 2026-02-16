<?php
/**
 * API: Retornar dados de vídeos do YouTube com filtros
 * Usado pelo componente Tabelas na página /youtube
 */

require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

// Requer autenticação
if (!Auth::check() && !MemberAuth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Autenticação necessária'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Headers
header('Content-Type: application/json; charset=utf-8');

try {
    $db = DB::connect(); // ✅ REGRA #1

    // ========================================
    // 1. RECEBER E SANITIZAR FILTROS
    // ========================================

    $canalFiltro = isset($_GET['select']) ? Security::sanitize($_GET['select']) : null;
    $dataInicio = isset($_GET['date_start']) ? Security::sanitize($_GET['date_start']) : null;
    $dataFim = isset($_GET['date_end']) ? Security::sanitize($_GET['date_end']) : null;

    // ========================================
    // 2. MONTAR QUERY COM FILTROS
    // ========================================

    $query = "SELECT titulo, canal, data_publicacao, visualizacoes, url
              FROM tbl_youtube
              WHERE 1=1";

    $params = [];

    // Filtro de canal
    if ($canalFiltro && $canalFiltro !== '') {
        $query .= " AND canal = ?";
        $params[] = $canalFiltro;
    }

    // Filtro de data início
    if ($dataInicio && $dataInicio !== '') {
        $query .= " AND data_publicacao >= ?";
        $params[] = $dataInicio;
    }

    // Filtro de data fim
    if ($dataFim && $dataFim !== '') {
        $query .= " AND data_publicacao <= ?";
        $params[] = $dataFim;
    }

    // Ordenação (mais recentes primeiro)
    $query .= " ORDER BY data_publicacao DESC";

    // Limite de segurança (máximo 1000 registros)
    $query .= " LIMIT 1000";

    // ========================================
    // 3. EXECUTAR QUERY
    // ========================================

    $results = $db->query($query, $params);

    // ========================================
    // 4. FORMATAR PARA O COMPONENTE TABELAS
    // ========================================

    // O componente espera: array de arrays
    // Exemplo: [["titulo1", "canal1", "2025-12-01"], ["titulo2", "canal2", "2025-12-02"]]

    $rows = [];
    foreach ($results as $row) {
        // Formatar data: remover hora (00:00:00)
        $dataFormatada = '';
        if (!empty($row['data_publicacao'])) {
            $dataFormatada = substr($row['data_publicacao'], 0, 10); // Pega apenas YYYY-MM-DD
        }

        $rows[] = [
            $row['titulo'] ?? '',
            $row['canal'] ?? '',
            $dataFormatada,
            number_format($row['visualizacoes'] ?? 0, 0, ',', '.'), // Formatar visualizações
            $row['url'] ?? ''
        ];
    }

    // ========================================
    // 5. RETORNAR JSON
    // ========================================

    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // Log do erro
    error_log('youtube-data.php error: ' . $e->getMessage());

    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar dados',
        'message' => $e->getMessage()
    ]);
}
