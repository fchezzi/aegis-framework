/**
 * n8n Code Node - PageSpeed Insights FULL DATA EXTRACTION
 * Extrai 100% dos dados úteis do PageSpeed para otimização
 *
 * Uso: Substituir o Code node "Transform Data" no workflow
 */

const input = $input.all();
const results = [];

for (const item of input) {
  const data = item.json;
  const lr = data.lighthouseResult;
  const audits = lr.audits;

  // ============================================
  // 1. CORE WEB VITALS (Lab Data - Sintético)
  // ============================================
  const labLCP = audits['largest-contentful-paint']?.numericValue ? (audits['largest-contentful-paint'].numericValue / 1000).toFixed(3) : null;
  const labFCP = audits['first-contentful-paint']?.numericValue ? (audits['first-contentful-paint'].numericValue / 1000).toFixed(3) : null;
  const labCLS = audits['cumulative-layout-shift']?.numericValue || null;
  const labINP = audits['interaction-to-next-paint']?.numericValue || null;
  const labSI = audits['speed-index']?.numericValue ? (audits['speed-index'].numericValue / 1000).toFixed(3) : null;
  const labTTI = audits['interactive']?.numericValue ? (audits['interactive'].numericValue / 1000).toFixed(3) : null;
  const labTBT = audits['total-blocking-time']?.numericValue || null;

  // ============================================
  // 2. FIELD DATA (Dados Reais de Usuários)
  // ============================================
  const fieldData = data.loadingExperience?.metrics || {};
  const fieldLCP = fieldData.LARGEST_CONTENTFUL_PAINT_MS?.percentile || null;
  const fieldFCP = fieldData.FIRST_CONTENTFUL_PAINT_MS?.percentile || null;
  const fieldCLS = fieldData.CUMULATIVE_LAYOUT_SHIFT_SCORE?.percentile ? (fieldData.CUMULATIVE_LAYOUT_SHIFT_SCORE.percentile / 100) : null;
  const fieldINP = fieldData.INTERACTION_TO_NEXT_PAINT?.percentile || null;
  const fieldCategory = fieldData.LARGEST_CONTENTFUL_PAINT_MS?.category || null;

  // ============================================
  // 3. OPPORTUNITIES - TODAS (não apenas TOP 5)
  // ============================================
  const opportunitiesFull = [];

  // Lista de audits de oportunidade conhecidos
  const opportunityAudits = [
    'render-blocking-resources',
    'unused-css-rules',
    'unused-javascript',
    'modern-image-formats',
    'offscreen-images',
    'minify-css',
    'minify-javascript',
    'efficient-animated-content',
    'duplicated-javascript',
    'legacy-javascript',
    'total-byte-weight',
    'uses-long-cache-ttl',
    'uses-optimized-images',
    'uses-text-compression',
    'uses-responsive-images',
    'unminified-css',
    'unminified-javascript',
    'server-response-time',
    'redirects',
    'uses-rel-preconnect',
    'uses-rel-preload',
    'font-display'
  ];

  for (const auditId of opportunityAudits) {
    const audit = audits[auditId];
    if (!audit || audit.score === null || audit.score >= 0.9) continue; // Skip se passou

    const opp = {
      audit_id: auditId,
      title: audit.title,
      description: audit.description,
      score: audit.score,
      display_value: audit.displayValue || null,
      savings_ms: audit.numericValue || 0,
      savings_bytes: null,
      items: []
    };

    // Extrair detalhes de arquivos se houver
    if (audit.details && audit.details.items) {
      opp.items = audit.details.items.slice(0, 20).map(item => ({
        url: item.url || null,
        total_bytes: item.totalBytes || item.wastedBytes || null,
        wasted_bytes: item.wastedBytes || null,
        wasted_ms: item.wastedMs || null
      }));

      // Calcular economia total
      opp.savings_bytes = opp.items.reduce((sum, i) => sum + (i.wasted_bytes || 0), 0);
    }

    // Adicionar metricSavings se disponível
    if (audit.metricSavings) {
      opp.savings_lcp = audit.metricSavings.LCP || 0;
      opp.savings_fcp = audit.metricSavings.FCP || 0;
    }

    opportunitiesFull.push(opp);
  }

  // Ordenar por impacto (savings_ms)
  opportunitiesFull.sort((a, b) => b.savings_ms - a.savings_ms);

  // ============================================
  // 4. DIAGNOSTICS - EXPANDIDOS
  // ============================================
  const diagnosticsFull = {};

  // Mainthread Work Breakdown
  if (audits['mainthread-work-breakdown']?.details?.items) {
    diagnosticsFull.mainthread_breakdown = audits['mainthread-work-breakdown'].details.items.map(item => ({
      category: item.groupLabel || item.group,
      time_ms: item.duration
    }));
  }

  // Bootup Time (JS Execution)
  if (audits['bootup-time']?.details?.items) {
    diagnosticsFull.bootup_time = audits['bootup-time'].details.items.slice(0, 10).map(item => ({
      url: item.url,
      total_ms: item.total,
      scripting_ms: item.scripting,
      script_parse_compile_ms: item.scriptParseCompile
    }));
  }

  // DOM Size
  diagnosticsFull.dom_size = {
    total_elements: audits['dom-size']?.numericValue || null,
    depth: audits['dom-size']?.details?.items?.[1]?.value || null,
    max_children: audits['dom-size']?.details?.items?.[2]?.value || null
  };

  // Critical Request Chains
  if (audits['critical-request-chains']?.details?.chains) {
    diagnosticsFull.critical_chains = Object.keys(audits['critical-request-chains'].details.chains).length;
  }

  // Long Tasks
  if (audits['long-tasks']?.details?.items) {
    diagnosticsFull.long_tasks_count = audits['long-tasks'].details.items.length;
    diagnosticsFull.long_tasks = audits['long-tasks'].details.items.slice(0, 5).map(task => ({
      url: task.url,
      duration_ms: task.duration,
      start_time_ms: task.startTime
    }));
  }

  // Layout Shift Elements
  if (audits['layout-shift-elements']?.details?.items) {
    diagnosticsFull.cls_elements = audits['layout-shift-elements'].details.items.slice(0, 5).map(elem => ({
      node: elem.node?.snippet || elem.node?.nodeLabel || 'Unknown',
      score: elem.score
    }));
  }

  // LCP Element
  if (audits['largest-contentful-paint-element']?.details?.items?.[0]) {
    diagnosticsFull.lcp_element = {
      node: audits['largest-contentful-paint-element'].details.items[0].node?.snippet ||
            audits['largest-contentful-paint-element'].details.items[0].node?.nodeLabel ||
            'Unknown'
    };
  }

  // Network Requests Summary
  if (audits['network-requests']?.details?.items) {
    const requests = audits['network-requests'].details.items;
    diagnosticsFull.network_summary = {
      total_requests: requests.length,
      total_size_kb: Math.round(requests.reduce((sum, r) => sum + (r.transferSize || 0), 0) / 1024),
      total_time_ms: Math.round(requests.reduce((sum, r) => sum + (r.endTime - r.startTime), 0))
    };
  }

  // ============================================
  // 5. THIRD-PARTY SUMMARY
  // ============================================
  const thirdPartySummary = [];
  if (audits['third-party-summary']?.details?.items) {
    thirdPartySummary.push(...audits['third-party-summary'].details.items.slice(0, 10).map(tp => ({
      entity: tp.entity,
      transfer_size_kb: Math.round((tp.transferSize || 0) / 1024),
      mainthread_time_ms: Math.round(tp.mainThreadTime || 0),
      blocking_time_ms: Math.round(tp.blockingTime || 0)
    })));
  }

  // ============================================
  // 6. RESOURCE SUMMARY (por tipo)
  // ============================================
  const resourceSummary = {
    scripts: { count: 0, size_kb: 0 },
    stylesheets: { count: 0, size_kb: 0 },
    images: { count: 0, size_kb: 0 },
    fonts: { count: 0, size_kb: 0 },
    documents: { count: 0, size_kb: 0 },
    other: { count: 0, size_kb: 0 }
  };

  if (audits['network-requests']?.details?.items) {
    for (const req of audits['network-requests'].details.items) {
      const type = req.resourceType || 'other';
      const sizeKb = Math.round((req.transferSize || 0) / 1024);

      if (type === 'Script') {
        resourceSummary.scripts.count++;
        resourceSummary.scripts.size_kb += sizeKb;
      } else if (type === 'Stylesheet') {
        resourceSummary.stylesheets.count++;
        resourceSummary.stylesheets.size_kb += sizeKb;
      } else if (type === 'Image') {
        resourceSummary.images.count++;
        resourceSummary.images.size_kb += sizeKb;
      } else if (type === 'Font') {
        resourceSummary.fonts.count++;
        resourceSummary.fonts.size_kb += sizeKb;
      } else if (type === 'Document') {
        resourceSummary.documents.count++;
        resourceSummary.documents.size_kb += sizeKb;
      } else {
        resourceSummary.other.count++;
        resourceSummary.other.size_kb += sizeKb;
      }
    }
  }

  // ============================================
  // 7. PASSED AUDITS (o que está BOM)
  // ============================================
  const passedAudits = [];
  for (const [auditId, audit] of Object.entries(audits)) {
    if (audit.score >= 0.9 && audit.scoreDisplayMode !== 'notApplicable') {
      passedAudits.push({
        id: auditId,
        title: audit.title
      });
    }
  }

  // ============================================
  // 8. MÉTRICAS ADICIONAIS
  // ============================================
  const serverResponseTime = audits['server-response-time']?.numericValue || null;
  const redirectsCount = audits['redirects']?.details?.items?.length || 0;
  const totalRequests = audits['network-requests']?.details?.items?.length || 0;
  const totalSizeKb = Math.round((audits['total-byte-weight']?.numericValue || 0) / 1024);

  const mainthreadWorkMs = Math.round(audits['mainthread-work-breakdown']?.numericValue || 0);
  const bootupTimeMs = Math.round(audits['bootup-time']?.numericValue || 0);

  // ============================================
  // 9. SCREENSHOTS (opcional - pode ser grande)
  // ============================================
  const screenshotFinal = audits['final-screenshot']?.details?.data || null; // base64
  const screenshotThumbnails = audits['screenshot-thumbnails']?.details?.items || null;

  // ============================================
  // 10. WARNINGS/ERRORS
  // ============================================
  const runWarnings = lr.runWarnings || [];
  const runtimeError = lr.runtimeError?.message || null;

  // ============================================
  // MONTAR OBJETO FINAL
  // ============================================
  results.push({
    json: {
      // Auth
      webhook_secret: $node["Get URLs from AEGIS"].json.config.webhook_secret,

      // Metadata
      url: data.id,
      strategy: lr.configSettings.emulatedFormFactor,
      analyzed_at: new Date(lr.fetchTime).toISOString().slice(0, 19).replace('T', ' '),
      lighthouse_version: lr.lighthouseVersion,
      fetch_time_ms: parseInt(lr.timing?.total || 0),

      // Score Principal
      performance_score: Math.round(lr.categories.performance.score * 100),

      // Lab Data
      lab_lcp: labLCP,
      lab_fcp: labFCP,
      lab_cls: labCLS,
      lab_inp: labINP,
      lab_si: labSI,
      lab_tti: labTTI,
      lab_tbt: labTBT,

      // Field Data
      field_lcp: fieldLCP,
      field_fcp: fieldFCP,
      field_cls: fieldCLS,
      field_inp: fieldINP,
      field_category: fieldCategory,

      // Oportunidades (TOP 5 para coluna antiga)
      opportunities: opportunitiesFull.slice(0, 5).map(o => ({
        title: o.title,
        description: o.description,
        savings_lcp: o.savings_lcp || 0,
        savings_fcp: o.savings_fcp || 0
      })),

      // NOVOS CAMPOS EXPANDIDOS
      opportunities_full: opportunitiesFull,
      diagnostics_full: diagnosticsFull,
      third_party_summary: thirdPartySummary,
      resource_summary: resourceSummary,
      passed_audits: passedAudits,

      // Screenshots (comentar se muito pesado)
      screenshot_final: null, // screenshotFinal (pode ser > 1MB),
      screenshot_thumbnails: null, // screenshotThumbnails (array de base64)

      // Elementos específicos
      lcp_element: diagnosticsFull.lcp_element?.node || null,
      cls_elements: diagnosticsFull.cls_elements || null,

      // Métricas adicionais
      server_response_time: serverResponseTime,
      redirects_count: redirectsCount,
      total_requests: totalRequests,
      total_size_kb: totalSizeKb,
      js_size_kb: resourceSummary.scripts.size_kb,
      css_size_kb: resourceSummary.stylesheets.size_kb,
      image_size_kb: resourceSummary.images.size_kb,
      font_size_kb: resourceSummary.fonts.size_kb,
      html_size_kb: resourceSummary.documents.size_kb,

      // Timing
      mainthread_work_ms: mainthreadWorkMs,
      bootup_time_ms: bootupTimeMs,

      // Warnings/Errors
      run_warnings: runWarnings.length > 0 ? runWarnings : null,
      runtime_error: runtimeError,

      // Diagnostics (coluna antiga - compatibilidade)
      diagnostics: {
        dom_size: diagnosticsFull.dom_size?.total_elements || null,
        requests_count: totalRequests,
        transfer_size: audits['total-byte-weight']?.numericValue || null
      }
    }
  });
}

return results;
