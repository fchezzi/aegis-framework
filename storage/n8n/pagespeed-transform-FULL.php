<?php
/**
 * Função de transformação PageSpeed FULL
 * Extrai 100% dos dados úteis da API
 */

function transformPageSpeedData($apiData, $strategy = 'mobile', $url = null) {
    $audits = $apiData['lighthouseResult']['audits'] ?? [];
    $metrics = $apiData['loadingExperience']['metrics'] ?? [];
    $categories = $apiData['lighthouseResult']['categories'] ?? [];

    // Todos os Scores
    $performanceScore = isset($categories['performance']['score'])
        ? round($categories['performance']['score'] * 100)
        : null;

    $accessibilityScore = isset($categories['accessibility']['score'])
        ? round($categories['accessibility']['score'] * 100)
        : null;

    $bestPracticesScore = isset($categories['best-practices']['score'])
        ? round($categories['best-practices']['score'] * 100)
        : null;

    $seoScore = isset($categories['seo']['score'])
        ? round($categories['seo']['score'] * 100)
        : null;

    // Lab Data
    $labMetrics = [
        'lab_lcp' => $audits['largest-contentful-paint']['numericValue'] ?? null,
        'lab_fcp' => $audits['first-contentful-paint']['numericValue'] ?? null,
        'lab_cls' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
        'lab_inp' => $audits['interaction-to-next-paint']['numericValue'] ?? null,
        'lab_si' => $audits['speed-index']['numericValue'] ?? null,
        'lab_tti' => $audits['interactive']['numericValue'] ?? null,
        'lab_tbt' => $audits['total-blocking-time']['numericValue'] ?? null,
    ];

    // Field Data
    $fieldMetrics = [
        'field_lcp' => $metrics['LARGEST_CONTENTFUL_PAINT_MS']['percentile'] ?? null,
        'field_fcp' => $metrics['FIRST_CONTENTFUL_PAINT_MS']['percentile'] ?? null,
        'field_cls' => isset($metrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['percentile'])
            ? $metrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['percentile'] / 100
            : null,
        'field_inp' => $metrics['INTERACTION_TO_NEXT_PAINT']['percentile'] ?? null,
    ];

    // Field Category
    $fieldCategory = 'FAST';
    if (isset($metrics['LARGEST_CONTENTFUL_PAINT_MS']['category'])) {
        $fieldCategory = $metrics['LARGEST_CONTENTFUL_PAINT_MS']['category'];
    }

    // Opportunities FULL
    $opportunitiesFull = [];
    $opportunityAudits = [
        // Audits tradicionais
        'render-blocking-resources',
        'unused-css-rules',
        'unused-javascript',
        'modern-image-formats',
        'uses-optimized-images',
        'offscreen-images',
        'uses-responsive-images',
        'efficient-animated-content',
        'duplicated-javascript',
        'legacy-javascript',
        'preload-lcp-image',
        'uses-long-cache-ttl',
        'uses-rel-preconnect',
        'server-response-time',
        'redirects',
        'uses-text-compression',
        'uses-rel-preload',
        'unminified-css',
        'unminified-javascript',
        'font-display',
        'third-party-summary',
        // Novos audits "-insight" do PageSpeed
        'render-blocking-insight',
        'cls-culprits-insight',
        'image-delivery-insight',
        'font-display-insight',
        'cache-insight',
        'lcp-discovery-insight',
        'network-dependency-tree-insight',
        // Métricas importantes
        'largest-contentful-paint',
        'layout-shifts',
        'interactive',
        'unsized-images',
        'total-byte-weight',
        'speed-index',
        'total-blocking-time',
        'max-potential-fid',
        'cumulative-layout-shift',
        'first-contentful-paint'
    ];

    foreach ($opportunityAudits as $auditId) {
        if (!isset($audits[$auditId])) continue;

        $audit = $audits[$auditId];

        // Incluir audits importantes mesmo com score alto
        $alwaysInclude = [
            'render-blocking-resources',
            'render-blocking-insight',
            'cls-culprits-insight',
            'image-delivery-insight',
            'font-display-insight',
            'cache-insight',
            'lcp-discovery-insight',
            'network-dependency-tree-insight',
            'layout-shifts',
            'unsized-images',
            'total-byte-weight'
        ];

        $includeHighScore = in_array($auditId, $alwaysInclude);

        if (!$includeHighScore) {
            if (!isset($audit['score']) || $audit['score'] === null || $audit['score'] >= 0.9) continue;
        }

        $opp = [
            'audit_id' => $auditId,
            'title' => $audit['title'] ?? '',
            'description' => $audit['description'] ?? '',
            'score' => $audit['score'],
            'display_value' => $audit['displayValue'] ?? null,
            'savings_ms' => $audit['numericValue'] ?? 0,
            'savings_bytes' => null,
            'items' => []
        ];

        // Extrair savings
        if (isset($audit['details']['overallSavingsMs'])) {
            $opp['savings_ms'] = $audit['details']['overallSavingsMs'];
        }
        if (isset($audit['details']['overallSavingsBytes'])) {
            $opp['savings_bytes'] = $audit['details']['overallSavingsBytes'];
        }

        // Para render-blocking-insight, extrair do displayValue se não tiver overallSavingsMs
        if ($auditId === 'render-blocking-insight' && $opp['savings_ms'] === 0 && !empty($audit['displayValue'])) {
            // Extrair número de "Est savings of X ms"
            if (preg_match('/(\d+)\s*ms/', $audit['displayValue'], $matches)) {
                $opp['savings_ms'] = (int) $matches[1];
            }
        }

        // Extrair items detalhados
        if (isset($audit['details']['items']) && is_array($audit['details']['items'])) {
            foreach (array_slice($audit['details']['items'], 0, 20) as $item) {
                $opp['items'][] = [
                    'url' => $item['url'] ?? null,
                    'total_bytes' => $item['totalBytes'] ?? $item['wastedBytes'] ?? null,
                    'wasted_bytes' => $item['wastedBytes'] ?? null,
                    'wasted_ms' => $item['wastedMs'] ?? null
                ];
            }
        }

        $opportunitiesFull[] = $opp;
    }

    // Diagnostics FULL
    $diagnosticsFull = [
        'mainthread_breakdown' => [],
        'bootup_time' => [],
        'dom_size' => null,
        'long_tasks_count' => null,
        'lcp_element' => null,
        'cls_elements' => [],
        'network_summary' => null
    ];

    // Mainthread breakdown
    if (isset($audits['mainthread-work-breakdown']['details']['items'])) {
        foreach ($audits['mainthread-work-breakdown']['details']['items'] as $item) {
            $diagnosticsFull['mainthread_breakdown'][] = [
                'category' => $item['group'] ?? $item['groupLabel'] ?? 'Other',
                'time_ms' => $item['duration'] ?? 0
            ];
        }
    }

    // Bootup time
    if (isset($audits['bootup-time']['details']['items'])) {
        foreach (array_slice($audits['bootup-time']['details']['items'], 0, 10) as $item) {
            $diagnosticsFull['bootup_time'][] = [
                'url' => $item['url'] ?? '',
                'total_ms' => $item['total'] ?? 0,
                'scripting_ms' => $item['scripting'] ?? 0,
                'script_parse_compile_ms' => $item['scriptParseCompile'] ?? 0
            ];
        }
    }

    // DOM size
    if (isset($audits['dom-size']['details']['items'][0])) {
        $domItem = $audits['dom-size']['details']['items'][0];
        $diagnosticsFull['dom_size'] = [
            'total_elements' => $domItem['value'] ?? 0,
            'depth' => $audits['dom-size']['details']['items'][1]['value'] ?? 0,
            'max_children' => $audits['dom-size']['details']['items'][2]['value'] ?? 0
        ];
    }

    // Long tasks
    if (isset($audits['long-tasks']['details']['items'])) {
        $diagnosticsFull['long_tasks_count'] = count($audits['long-tasks']['details']['items']);
    }

    // Network summary
    if (isset($audits['diagnostics']['details']['items'][0])) {
        $diag = $audits['diagnostics']['details']['items'][0];
        $diagnosticsFull['network_summary'] = [
            'total_requests' => $diag['numRequests'] ?? 0,
            'total_size_kb' => isset($diag['totalByteWeight']) ? round($diag['totalByteWeight'] / 1024) : 0,
            'total_time_ms' => $diag['totalTaskTime'] ?? 0
        ];
    }

    // Third-party summary
    $thirdPartySummary = [];
    if (isset($audits['third-party-summary']['details']['items'])) {
        foreach (array_slice($audits['third-party-summary']['details']['items'], 0, 10) as $item) {
            $thirdPartySummary[] = [
                'entity' => $item['entity'] ?? 'Unknown',
                'transfer_size_kb' => isset($item['transferSize']) ? round($item['transferSize'] / 1024) : 0,
                'mainthread_time_ms' => $item['mainThreadTime'] ?? 0,
                'blocking_time_ms' => $item['blockingTime'] ?? 0
            ];
        }
    }

    // Resource summary
    $resourceSummary = [
        'scripts' => ['count' => 0, 'size_kb' => 0],
        'stylesheets' => ['count' => 0, 'size_kb' => 0],
        'images' => ['count' => 0, 'size_kb' => 0],
        'fonts' => ['count' => 0, 'size_kb' => 0],
        'documents' => ['count' => 0, 'size_kb' => 0],
        'other' => ['count' => 0, 'size_kb' => 0]
    ];

    if (isset($audits['resource-summary']['details']['items'])) {
        foreach ($audits['resource-summary']['details']['items'] as $item) {
            $type = strtolower($item['resourceType'] ?? 'other');
            $key = str_replace(' ', '', $type);
            if (isset($resourceSummary[$key])) {
                $resourceSummary[$key] = [
                    'count' => $item['requestCount'] ?? 0,
                    'size_kb' => isset($item['transferSize']) ? round($item['transferSize'] / 1024) : 0
                ];
            }
        }
    }

    // Passed audits
    $passedAudits = [];
    foreach ($audits as $auditId => $audit) {
        if (isset($audit['score']) && $audit['score'] >= 0.9) {
            $passedAudits[] = [
                'id' => $auditId,
                'title' => $audit['title'] ?? ''
            ];
        }
    }

    // LCP Element
    $lcpElement = null;
    if (isset($audits['largest-contentful-paint-element']['details']['items'][0])) {
        $lcpItem = $audits['largest-contentful-paint-element']['details']['items'][0];
        $lcpElement = $lcpItem['node']['snippet'] ?? null;
    }

    // CLS Elements
    $clsElements = [];
    if (isset($audits['layout-shift-elements']['details']['items'])) {
        foreach (array_slice($audits['layout-shift-elements']['details']['items'], 0, 5) as $item) {
            $clsElements[] = [
                'node' => $item['node']['snippet'] ?? '',
                'score' => $item['score'] ?? 0
            ];
        }
    }

    // Montar resultado final
    return [
        'url' => $apiData['id'] ?? '',
        'strategy' => $strategy, // mobile ou desktop
        'analyzed_at' => date('Y-m-d H:i:s'),
        'lighthouse_version' => $apiData['lighthouseResult']['lighthouseVersion'] ?? '',
        'fetch_time_ms' => isset($apiData['lighthouseResult']['fetchTime'])
            ? (int)$apiData['lighthouseResult']['fetchTime']
            : 3000,
        'performance_score' => $performanceScore,
        'accessibility_score' => $accessibilityScore,
        'best_practices_score' => $bestPracticesScore,
        'seo_score' => $seoScore,

        // Lab metrics
        'lab_lcp' => $labMetrics['lab_lcp'],
        'lab_fcp' => $labMetrics['lab_fcp'],
        'lab_cls' => $labMetrics['lab_cls'],
        'lab_inp' => $labMetrics['lab_inp'],
        'lab_si' => $labMetrics['lab_si'],
        'lab_tti' => $labMetrics['lab_tti'],
        'lab_tbt' => $labMetrics['lab_tbt'],

        // Field metrics
        'field_lcp' => $fieldMetrics['field_lcp'],
        'field_fcp' => $fieldMetrics['field_fcp'],
        'field_cls' => $fieldMetrics['field_cls'],
        'field_inp' => $fieldMetrics['field_inp'],
        'field_category' => $fieldCategory,

        // JSON fields
        'opportunities_full' => json_encode($opportunitiesFull),
        'diagnostics_full' => json_encode($diagnosticsFull),
        'third_party_summary' => json_encode($thirdPartySummary),
        'resource_summary' => json_encode($resourceSummary),
        'passed_audits' => json_encode($passedAudits),

        // Elementos críticos
        'lcp_element' => $lcpElement,
        'cls_elements' => json_encode($clsElements),

        // Métricas adicionais
        'server_response_time' => $audits['server-response-time']['numericValue'] ?? null,
        'redirects_count' => isset($audits['redirects']['details']['items'])
            ? count($audits['redirects']['details']['items'])
            : 0,
        'total_requests' => $diagnosticsFull['network_summary']['total_requests'] ?? 0,
        'total_size_kb' => $diagnosticsFull['network_summary']['total_size_kb'] ?? 0,
        'js_size_kb' => $resourceSummary['scripts']['size_kb'],
        'css_size_kb' => $resourceSummary['stylesheets']['size_kb'],
        'image_size_kb' => $resourceSummary['images']['size_kb'],
        'font_size_kb' => $resourceSummary['fonts']['size_kb'],
        'html_size_kb' => $resourceSummary['documents']['size_kb'],
        'mainthread_work_ms' => $audits['mainthread-work-breakdown']['numericValue'] ?? null,
        'bootup_time_ms' => $audits['bootup-time']['numericValue'] ?? null,

        // Warnings
        'run_warnings' => isset($apiData['lighthouseResult']['runWarnings'])
            ? json_encode($apiData['lighthouseResult']['runWarnings'])
            : null
    ];
}