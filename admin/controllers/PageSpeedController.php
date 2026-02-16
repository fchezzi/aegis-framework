<?php
/**
 * PageSpeed Controller
 * Gerencia visualização de relatórios PageSpeed Insights
 */

class PageSpeedController extends BaseController {

    /**
     * Listar relatórios
     */
    public function index() {
        // Dados do usuário para header
        $user = Auth::user();

        // Buscar configurações
        $settings = Settings::all();
        $pagespeedEnabled = $settings['pagespeed_enabled'] ?? false;

        // Filtros
        $urlFilter = $_GET['url'] ?? '';
        $strategyFilter = $_GET['strategy'] ?? '';
        $scoreFilter = $_GET['score'] ?? '';
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Construir query
        $db = $this->db();

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

        // Buscar relatórios
        $sql = "SELECT * FROM tbl_pagespeed_reports
                WHERE {$whereClause}
                ORDER BY analyzed_at DESC
                LIMIT ? OFFSET ?";

        $queryParams = array_merge($params, [$perPage, $offset]);
        $reports = $db->query($sql, $queryParams);

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM tbl_pagespeed_reports WHERE {$whereClause}";
        $countResult = $db->query($countSql, $params);
        $totalReports = $countResult[0]['total'] ?? 0;
        $totalPages = ceil($totalReports / $perPage);

        // Estatísticas gerais
        $statsResult = $db->query("
            SELECT
                COUNT(*) as total_analyses,
                AVG(performance_score) as avg_score,
                COUNT(DISTINCT url) as total_urls,
                MAX(analyzed_at) as last_analysis
            FROM tbl_pagespeed_reports
        ");
        $stats = $statsResult[0] ?? [
            'total_analyses' => 0,
            'avg_score' => 0,
            'total_urls' => 0,
            'last_analysis' => null
        ];

        // Passar dados para view
        require_once ROOT_PATH . 'admin/views/pagespeed/index.php';
    }

    /**
     * Ver relatório individual
     */
    public function report($id) {
        // Dados do usuário para header
        $user = Auth::user();

        // Buscar relatório
        $db = $this->db();
        $result = $db->query("SELECT * FROM tbl_pagespeed_reports WHERE id = ?", [$id]);
        $report = $result[0] ?? null;

        if (!$report) {
            $_SESSION['error'] = 'Relatório não encontrado';
            $this->redirect('/admin/pagespeed');
            return;
        }

        // Decodificar JSON fields (compatibilidade v1.0)
        $opportunities = $report['opportunities'] ? json_decode($report['opportunities'], true) : [];
        $diagnostics = $report['diagnostics'] ? json_decode($report['diagnostics'], true) : [];

        // Decodificar NOVOS campos (v2.0)
        $opportunitiesFull = $report['opportunities_full'] ? json_decode($report['opportunities_full'], true) : [];
        $diagnosticsFull = $report['diagnostics_full'] ? json_decode($report['diagnostics_full'], true) : [];
        $thirdPartySummary = $report['third_party_summary'] ? json_decode($report['third_party_summary'], true) : [];
        $resourceSummary = $report['resource_summary'] ? json_decode($report['resource_summary'], true) : [];
        $passedAudits = $report['passed_audits'] ? json_decode($report['passed_audits'], true) : [];
        $clsElements = $report['cls_elements'] ? json_decode($report['cls_elements'], true) : [];
        $runWarnings = $report['run_warnings'] ? json_decode($report['run_warnings'], true) : [];

        // Adicionar ao $report para a view ter acesso
        $report['opportunitiesFull'] = $opportunitiesFull;
        $report['diagnosticsFull'] = $diagnosticsFull;
        $report['thirdPartySummary'] = $thirdPartySummary;
        $report['resourceSummary'] = $resourceSummary;
        $report['passedAudits'] = $passedAudits;
        $report['clsElements'] = $clsElements;
        $report['runWarnings'] = $runWarnings;

        // Buscar análises mobile + desktop do mesmo momento
        // Busca análises feitas no mesmo minuto (até 60 segundos de diferença)
        $otherReports = $db->query("
            SELECT id, strategy, performance_score, analyzed_at
            FROM tbl_pagespeed_reports
            WHERE url = ?
            AND ABS(TIMESTAMPDIFF(SECOND, analyzed_at, ?)) <= 60
            ORDER BY strategy ASC
        ", [$report['url'], $report['analyzed_at']]);

        // Passar dados para view
        require_once ROOT_PATH . 'admin/views/pagespeed/report.php';
    }
}
