# 💡 Ideias de Módulos Futuros - AEGIS Framework

> Registro de ideias, melhorias e módulos potenciais para implementação futura

**Última atualização:** 2026-02-05

---

## 🚀 Módulos Propostos

### 1. 📊 PageSpeed Monitor (Google PageSpeed Insights Integration)

**Status:** 💭 Ideia
**Prioridade:** Média
**Complexidade:** 6/10 (Média)
**Tempo estimado:** 3-4 horas

#### 📖 Descrição
Integração com a API pública do Google PageSpeed Insights para monitoramento automatizado de performance de páginas do site. Dashboard completo com histórico, gráficos de evolução e sugestões priorizadas de otimização.

#### 🎯 Funcionalidades

**Admin: `/admin/pagespeed`**
- ✅ Adicionar URLs para monitorar
- ✅ Análise sob demanda (botão "Analisar agora")
- ✅ Agendamento automático (diário/semanal/manual)
- ✅ Dashboard com scores e métricas
- ✅ Histórico de análises com gráficos de evolução
- ✅ Lista de sugestões priorizadas do Google
- ✅ Comparação Mobile vs Desktop
- ✅ Alertas quando score cair abaixo de threshold
- ✅ Export de relatórios (PDF/CSV)

**Métricas Capturadas:**
- Score de Performance (0-100)
- Score de Acessibilidade (0-100)
- Score de SEO (0-100)
- Score de Best Practices (0-100)
- Core Web Vitals: LCP, FID/INP, CLS
- Outras métricas: FCP, TTI, Speed Index, TBT

#### 🗄️ Estrutura de Banco

```sql
-- URLs monitoradas
tbl_pagespeed_urls (
    id UUID PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT true,
    frequencia ENUM('manual','daily','weekly') DEFAULT 'manual',
    threshold_alert INT DEFAULT 50,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

-- Histórico de relatórios
tbl_pagespeed_reports (
    id UUID PRIMARY KEY,
    url_id UUID REFERENCES tbl_pagespeed_urls(id) ON DELETE CASCADE,
    strategy ENUM('mobile','desktop') NOT NULL,
    score_performance INT,
    score_accessibility INT,
    score_seo INT,
    score_best_practices INT,
    lcp DECIMAL(10,2),
    fid DECIMAL(10,2),
    cls DECIMAL(10,3),
    fcp DECIMAL(10,2),
    tti DECIMAL(10,2),
    speed_index DECIMAL(10,2),
    tbt DECIMAL(10,2),
    suggestions JSON,
    has_crux_data BOOLEAN DEFAULT false,
    created_at TIMESTAMP
)
```

#### 🛣️ Roteiro de Implementação

**Fase 1: Planejamento (30min)**
- [x] Definir estrutura de banco de dados
- [x] Definir fluxo de análise (síncrono com AJAX)
- [x] Escolher biblioteca de gráficos (Chart.js via CDN)

**Fase 2: Estrutura do Módulo (30min)**
```
modules/pagespeed/
├── module.json
├── routes.php
├── controllers/
│   ├── AdminPageSpeedController.php (CRUD + dashboard)
│   └── PageSpeedService.php (wrapper API Google)
├── views/admin/
│   ├── index.php (dashboard geral)
│   ├── urls.php (gerenciar URLs)
│   └── report.php (detalhes de 1 análise)
└── database/
    ├── mysql-schema.sql
    ├── supabase-schema.sql
    └── rollback.sql
```

**Fase 3: API Integration (1h)**
- Criar `PageSpeedService.php`
- Integrar com API: `https://www.googleapis.com/pagespeedonline/v5/runPagespeed`
- Parse do JSON (extrair scores, métricas, sugestões)
- Tratamento de erros e timeouts
- Salvar resultados no banco

**Fase 4: Controllers Admin (1h)**
- `index()` - Dashboard com cards e gráficos
- `urls()` - Lista de URLs monitoradas
- `addUrl()` / `editUrl()` / `deleteUrl()` - CRUD
- `analyze($urlId)` - Análise sob demanda (AJAX)
- `report($id)` - Detalhes de 1 análise específica
- `history($urlId)` - Histórico filtrado por URL

**Fase 5: Views + UI (1h)**
- Dashboard com cards de resumo
- Gráficos de evolução com Chart.js
- Tabela de URLs com status e último score
- Badges coloridos (verde >90, amarelo 50-89, vermelho <50)
- Lista de sugestões priorizadas
- Loading spinner para análises (30-60s)

**Fase 6: Scheduler Integration (30min)**
- Usar `Scheduler::job()` do AEGIS para análises automáticas
- Cron diário/semanal baseado em `frequencia`
- Queue opcional para múltiplas URLs

**Fase 7: Testes (30min)**
- Testar com URL real
- Validar timeout (análise ~30-60s)
- Testar site sem dados CrUX
- Verificar rate limit (1 req/s)

#### 🔧 API do Google PageSpeed Insights

**Endpoint:**
```
GET https://www.googleapis.com/pagespeedonline/v5/runPagespeed
```

**Parâmetros:**
- `url` - URL completa a ser analisada
- `strategy` - `mobile` ou `desktop`
- `category` - `performance`, `accessibility`, `seo`, `best-practices`

**Rate Limits:**
- ✅ API gratuita (sem API Key para uso básico)
- ✅ 25.000 requisições/dia (com API Key)
- ⚠️ Recomendado: 1 request/segundo
- ⏱️ Tempo de resposta: 30-60 segundos

**Resposta:**
- JSON com ~50kb de dados
- Scores, métricas, screenshots, sugestões
- Dados reais (CrUX) se disponível
- Dados de laboratório (Lighthouse) sempre

#### ⚠️ Complexidades & Soluções

| Complexidade | Risco | Solução |
|--------------|-------|---------|
| **Timeout (30-60s)** | ⚠️ Médio | AJAX com loading spinner |
| **Rate Limit (1/s)** | ⚠️ Médio | Queue se múltiplas URLs |
| **JSON gigante (50kb+)** | ⚠️ Médio | Salvar apenas campos importantes |
| **Sem dados CrUX** | 🔴 Alto | Mostrar apenas Lighthouse data |
| **API pode falhar** | 🔴 Alto | Try/catch + retry logic + logs |

#### 📦 Dependências

- ✅ **Zero dependências externas** (cURL nativo PHP)
- ✅ Chart.js (via CDN)
- ✅ AEGIS 15.2.2+ (compatível)
- ✅ PHP `allow_url_fopen = On` OU `curl` habilitado
- ✅ Scheduler do AEGIS (opcional, para análises automáticas)

#### 💡 Versões

**MVP (2h):**
- Análise manual de 1 URL
- Mostrar score + sugestões básicas
- Sem histórico (análise em tempo real)

**Full (4h):**
- Gerenciar múltiplas URLs
- Histórico completo + gráficos
- Análises automáticas (cron)
- Comparação mobile/desktop
- Alertas e exports

#### 🎨 UI/UX

**Dashboard:**
```
┌─────────────────────────────────────────┐
│ 📊 PageSpeed Monitor                    │
├─────────────────────────────────────────┤
│                                          │
│  [Score Médio: 85] [URLs: 5] [Análises: 23] │
│                                          │
│  📈 Evolução (30 dias)                  │
│  [Gráfico Chart.js]                     │
│                                          │
│  🔗 URLs Monitoradas                    │
│  ┌──────────────────────────────────┐  │
│  │ example.com/page    🟢 92  [▶]   │  │
│  │ example.com/blog    🟡 78  [▶]   │  │
│  │ example.com/produto 🔴 45  [▶]   │  │
│  └──────────────────────────────────┘  │
│                                          │
│  💡 Top Sugestões                       │
│  • Comprimir imagens (-500kb)           │
│  • Minificar CSS (-120kb)               │
│  • Lazy load offscreen images           │
└─────────────────────────────────────────┘
```

**Badge de Score:**
- 🟢 Verde: 90-100 (Bom)
- 🟡 Amarelo: 50-89 (Precisa melhorar)
- 🔴 Vermelho: 0-49 (Ruim)

#### 📚 Referências

- [PageSpeed Insights API](https://developers.google.com/speed/docs/insights/v5/get-started)
- [Lighthouse Scoring](https://developer.chrome.com/docs/lighthouse/performance/performance-scoring/)
- [Core Web Vitals](https://web.dev/vitals/)
- [CrUX Dashboard](https://developer.chrome.com/docs/crux/)

#### ✅ Próximos Passos (quando implementar)

1. Criar issue/tarefa no backlog
2. Validar viabilidade técnica com API real
3. Definir prioridade vs outros módulos
4. Alocar tempo de desenvolvimento
5. Implementar seguindo roteiro acima
6. Documentar em `.claude/modules.md`

---

## 📝 Como Adicionar Novas Ideias

Para adicionar uma nova ideia de módulo:

1. Copiar template de seção acima
2. Preencher: Status, Prioridade, Complexidade, Tempo
3. Descrever funcionalidades principais
4. Definir estrutura de banco (se aplicável)
5. Esboçar roteiro de implementação
6. Listar dependências e riscos
7. Adicionar referências úteis

**Formato de Status:**
- 💭 Ideia (sem validação)
- 📋 Planejado (validado, aguardando implementação)
- 🚧 Em desenvolvimento
- ✅ Implementado (mover para CHANGELOG)
- ❌ Descartado (explicar motivo)

---

**Mantido por:** Claude Code + Fábio Chezzi
**Versão:** 1.0.0
