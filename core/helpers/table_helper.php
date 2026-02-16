<?php
/**
 * Table Helper
 * Funções auxiliares para renderizar tabelas facilmente
 */

/**
 * Renderizar tabela a partir de array de dados
 *
 * @param array $data Array associativo com linhas
 * @param array $options Opções da tabela
 * @return string HTML da tabela
 */
function render_table(array $data, array $options = []): string {
    if (empty($data)) {
        return '<p>Nenhum dado disponível.</p>';
    }

    // Extrair colunas (chaves do primeiro item)
    $firstRow = reset($data);
    $columns = array_keys($firstRow);

    // Converter dados para formato do componente
    $rows = [];
    foreach ($data as $item) {
        $rows[] = array_values($item);
    }

    // Configuração padrão
    $defaults = [
        'title' => '',
        'style' => 'default',
        'header_color' => 'primary',
        'sortable' => 'yes',
        'searchable' => 'yes',
        'pagination' => 'yes',
        'rows_per_page' => 10
    ];

    $config = array_merge($defaults, $options);
    $config['columns'] = json_encode($columns);
    $config['rows'] = json_encode($rows);

    return Component::render('tabelas', $config);
}

/**
 * Renderizar tabela simples (sem features avançadas)
 *
 * @param array $data Array associativo com linhas
 * @param string $title Título da tabela
 * @return string HTML da tabela
 */
function simple_table(array $data, string $title = ''): string {
    return render_table($data, [
        'title' => $title,
        'sortable' => 'no',
        'searchable' => 'no',
        'pagination' => 'no'
    ]);
}

/**
 * Renderizar tabela com busca e ordenação
 *
 * @param array $data Array associativo com linhas
 * @param string $title Título da tabela
 * @return string HTML da tabela
 */
function searchable_table(array $data, string $title = ''): string {
    return render_table($data, [
        'title' => $title,
        'sortable' => 'yes',
        'searchable' => 'yes',
        'pagination' => 'no'
    ]);
}

/**
 * Renderizar tabela completa (todos os recursos)
 *
 * @param array $data Array associativo com linhas
 * @param string $title Título da tabela
 * @param int $perPage Linhas por página
 * @return string HTML da tabela
 */
function full_table(array $data, string $title = '', int $perPage = 10): string {
    return render_table($data, [
        'title' => $title,
        'sortable' => 'yes',
        'searchable' => 'yes',
        'pagination' => 'yes',
        'rows_per_page' => $perPage
    ]);
}

/**
 * Renderizar tabela de banco de dados
 *
 * @param string $query SQL query
 * @param array $params Parâmetros para prepared statement
 * @param array $options Opções da tabela
 * @return string HTML da tabela
 */
function db_table(string $query, array $params = [], array $options = []): string {
    try {
        $db = DB::connect();
        $results = $db->query($query, $params);

        if (empty($results)) {
            return '<p>Nenhum resultado encontrado.</p>';
        }

        return render_table($results, $options);
    } catch (Exception $e) {
        return '<div class="aegis-table-error">Erro ao buscar dados: ' .
               htmlspecialchars($e->getMessage()) . '</div>';
    }
}
