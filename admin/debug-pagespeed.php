<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../_config.php';

// Conexão direta com banco
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
$dbname = 'aegis';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:unix_socket=$socket;dbname=$dbname", $username, $password);

    echo "<h2>Debug PageSpeed Controller</h2>";

    // Simular exatamente o que o controller faz
    $urlFilter = $_GET['url'] ?? '';
    $strategyFilter = $_GET['strategy'] ?? '';
    $scoreFilter = $_GET['score'] ?? '';
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    echo "<h3>Parâmetros:</h3>";
    echo "URL Filter: '$urlFilter'<br>";
    echo "Strategy Filter: '$strategyFilter'<br>";
    echo "Score Filter: '$scoreFilter'<br>";
    echo "Page: $page<br>";
    echo "Offset: $offset<br>";
    echo "Per Page: $perPage<br><br>";

    // Construir WHERE clause
    $where = ['1=1'];
    $params = [];

    if (!empty($urlFilter)) {
        $where[] = 'url LIKE ?';
        $params[] = '%' . $urlFilter . '%';
    }

    if (!empty($strategyFilter)) {
        $where[] = 'strategy = ?';
        $params[] = $strategyFilter;
    }

    if (!empty($scoreFilter)) {
        switch ($scoreFilter) {
            case 'good':
                $where[] = 'performance_score >= 90';
                break;
            case 'average':
                $where[] = 'performance_score >= 50 AND performance_score < 90';
                break;
            case 'poor':
                $where[] = 'performance_score < 50';
                break;
        }
    }

    $whereClause = implode(' AND ', $where);

    echo "<h3>Query WHERE:</h3>";
    echo "<code>$whereClause</code><br>";
    echo "Params: " . json_encode($params) . "<br><br>";

    // Query principal
    $sql = "SELECT * FROM tbl_pagespeed_reports
            WHERE {$whereClause}
            ORDER BY analyzed_at DESC
            LIMIT ? OFFSET ?";

    echo "<h3>SQL Query:</h3>";
    echo "<code>" . htmlspecialchars($sql) . "</code><br>";

    $queryParams = array_merge($params, [$perPage, $offset]);
    echo "Query Params: " . json_encode($queryParams) . "<br><br>";

    // Executar query - LIMIT e OFFSET devem ser inteiros
    $stmt = $pdo->prepare($sql);

    // Bind params de WHERE
    $i = 1;
    foreach ($params as $param) {
        $stmt->bindValue($i++, $param);
    }
    // LIMIT e OFFSET como inteiros
    $stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($i++, $offset, PDO::PARAM_INT);

    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Resultados: " . count($reports) . " relatórios</h3>";

    if (count($reports) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>URL</th><th>Strategy</th><th>Score</th><th>Data</th></tr>";
        foreach ($reports as $report) {
            echo "<tr>";
            echo "<td>" . substr($report['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($report['url']) . "</td>";
            echo "<td>" . $report['strategy'] . "</td>";
            echo "<td>" . $report['performance_score'] . "</td>";
            echo "<td>" . $report['analyzed_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>NENHUM RESULTADO ENCONTRADO!</p>";
    }

    // Contar total
    $countSql = "SELECT COUNT(*) as total FROM tbl_pagespeed_reports WHERE {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $count = $countStmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Total de registros: " . $count['total'] . "</h3>";
    echo "Total de páginas: " . ceil($count['total'] / $perPage) . "<br>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERRO:</h2>";
    echo $e->getMessage();
}