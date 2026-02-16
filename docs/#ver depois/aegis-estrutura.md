# AEGIS Framework - Estrutura Geral

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-18 *(Atualizado: Correção sessão APIs)*
**Responsável:** Fábio Chezzi

---

## ⚠️ AVISOS IMPORTANTES

### 🔧 Correção Aplicada (2026-01-18)
**Problema:** APIs retornavam erro 401 mesmo com usuário logado.
**Solução:** Adicionado `session_start()` em 8 arquivos API.
**Impacto:** Page Builder, uploads, cards e gráficos agora funcionam.
**Ver:** [CHANGELOG-2026-01-18.md](CHANGELOG-2026-01-18.md)

---

## 📂 ÍNDICE DA DOCUMENTAÇÃO

Este documento é o **índice geral** da documentação AEGIS. Cada pasta possui documentação detalhada separada:

### Estrutura do Framework

- **[aegis-raiz.md](aegis-raiz.md)** - Arquivos da raiz do projeto
- **[aegis-admin.md](aegis-admin.md)** - Pasta `/admin/` completa (inclui Deploy V2)
- **[aegis-api.md](aegis-api.md)** - Pasta `/api/` completa (sessão corrigida)
- **[aegis-assets.md](aegis-assets.md)** - Pasta `/assets/` completa
- **[aegis-components.md](aegis-components.md)** - Pasta `/components/` completa
- **[aegis-core.md](aegis-core.md)** - Pasta `/core/` completa
- **[aegis-modules.md](aegis-modules.md)** - Pasta `/modules/` completa
- **[aegis-routes.md](aegis-routes.md)** - Pasta `/routes/` completa
- **[aegis-frontend.md](aegis-frontend.md)** - Pasta `/frontend/` completa
- **[aegis-database.md](aegis-database.md)** - Pasta `/database/` completa
- **[aegis-storage.md](aegis-storage.md)** - Pasta `/storage/` completa

### Segurança e Auditoria

- **[SECURITY-PATTERNS.md](SECURITY-PATTERNS.md)** - Padrões de segurança do framework
- **[SECURITY-RESOLUTION.md](SECURITY-RESOLUTION.md)** - Auditoria de segurança 2026-01-18 (Score: 9.5/10)

### Histórico de Mudanças

- **[CHANGELOG-2026-01-18.md](CHANGELOG-2026-01-18.md)** - Correção crítica: Erro 401 em APIs

---

## 📂 ESTRUTURA GERAL

```
/
├── 📄 ARQUIVOS DA RAIZ → [aegis-raiz.md](aegis-raiz.md)
│   ├── index.php               # Entry point da aplicação
│   ├── config.php              # Configuration loader
│   ├── _config.php             # Configuração ativa (gerada pelo setup)
│   ├── routes.php              # Routes loader
│   ├── setup.php               # Wizard de instalação
│   ├── composer.json           # Lista de dependências PHP
│   ├── composer.lock           # Versões exatas instaladas
│   ├── composer.phar           # Executável do Composer
│   ├── .htaccess               # Regras Apache (URL rewrite)
│   ├── .gitignore              # Arquivos ignorados pelo Git
│   ├── .aegis-version          # Versão do framework
│   └── config.codekit3         # Configuração do CodeKit (dev)
│
├── 📁 admin/ → [aegis-admin.md](aegis-admin.md)
│   ├── cache.php               # Gerenciador de cache
│   ├── health.php              # Health check do sistema
│   ├── version.php             # Versionamento semântico
│   ├── import-csv.php          # Importação CSV universal
│   ├── deploy.php              # Gerador de pacote ZIP
│   ├── 📁 api/
│   │   ├── import-csv.php      # API: Executa importação no banco
│   │   └── process-csv.php     # API: Valida e retorna preview CSV
│   ├── 📁 components/
│   │   └── tables.php          # API: Lista tabelas do banco (JSON)
│   ├── 📁 controllers/
│   │   └── (15 controllers MVC)
│   └── 📁 views/
│       └── (templates HTML/PHP)
│
├── 📁 api/ → [aegis-api.md](aegis-api.md)
│   ├── chart-data.php          # API: Dados para gráficos (ApexCharts)
│   ├── metriccard-data.php     # API: Calcular métricas (SUM, AVG, etc)
│   ├── table-data.php          # API: Dados genéricos de qualquer tabela
│   ├── get-columns.php         # API: Listar colunas de uma tabela
│   ├── get-tables.php          # API: Listar tabelas do banco
│   ├── upload-image.php        # API: Upload de imagens
│   ├── list-canais.php         # API: Listar canais (DEPRECADA)
│   ├── youtube-data.php        # API: Dados YouTube (DEPRECADA)
│   ├── test-encerrado.json     # Mock data (LIXO)
│   └── 📁 controllers/
│       └── AuthApiController.php
│
├── 📁 assets/ → [aegis-assets.md](aegis-assets.md)
│   ├── 📁 css/
│   │   ├── so-main.css         # CSS compilado final (gerado pelo SASS)
│   │   └── so-main.css.map     # Source map
│   ├── 📁 fonts/
│   │   ├── inter.ttf           # Font Inter
│   │   └── roboto.ttf          # Font Roboto
│   ├── 📁 img/
│   │   ├── logo.svg            # Logo do site
│   │   ├── 📁 avatar/          # Avatars padrão
│   │   └── 📁 uploads/         # Uploads de imagens (106 arquivos)
│   ├── 📁 js/
│   │   ├── admin.js            # JavaScript do admin
│   │   ├── aegis-*.js          # Componentes AEGIS (metricards, tables)
│   │   ├── filtros-*.js        # Sistema de filtros
│   │   ├── graficos-*.js       # Gráficos (ApexCharts)
│   │   ├── 📁 components/      # Componentes JS modulares
│   │   └── 📁 core/            # JS core do framework
│   └── 📁 sass/
│       ├── so-main.sass        # Entry point SASS
│       ├── 📁 base/            # Variáveis, reset, tipografia
│       ├── 📁 components/      # Componentes (buttons, cards, etc)
│       ├── 📁 layout/          # Layout (header, footer, grid)
│       └── 📁 modules/         # Módulos específicos
│
├── 📁 components/ → [aegis-components.md](aegis-components.md)
│   ├── cards/                  # MetricCards dinâmicos
│   ├── filtros/                # Filtros (canal + data)
│   ├── graficos/               # Gráficos ApexCharts
│   ├── tabelas/                # Tabelas responsivas
│   ├── hero/                   # Banner principal
│   ├── htmllivre/              # HTML livre
│   ├── imagelink/              # Imagem + link
│   ├── spacer/                 # Espaçamento
│   ├── filtromes/              # Filtro mês/ano
│   └── ultimaatualizacao/      # Última atualização
│
├── 📁 core/ → [aegis-core.md](aegis-core.md)
│   └── (64 componentes - coração do framework)
│
├── 📁 modules/ → [aegis-modules.md](aegis-modules.md)
│   ├── blog/                # Blog de notícias (21 arquivos)
│   ├── palpites/            # Palpites esportivos (37 arquivos)
│   └── reports/             # Relatórios Excel (11 arquivos)
│
├── 📁 routes/ → [aegis-routes.md](aegis-routes.md)
│   ├── admin.php            # Rotas admin (150+ rotas)
│   ├── api.php              # API REST versionada (JWT)
│   ├── public.php           # Rotas públicas (login, home)
│   └── catchall.php         # Genéricas (última prioridade)
│
├── 📁 frontend/ → [aegis-frontend.md](aegis-frontend.md)
│   ├── 📁 includes/         # 7 partials (_head, _header, _footer, _aside, _menu-dinamico, _gtm)
│   ├── 📁 templates/        # 3 layouts (dashboard, basic, dashboard-menu-auto)
│   ├── 📁 pages/            # 25 páginas (home, dashboard, canais, programas)
│   ├── 📁 controllers/      # 1 controller (DownloadController)
│   ├── 📁 views/            # 1 view (login)
│   └── 📁 components/       # Vazio (futuro)
│
├── 📁 database/ → [aegis-database.md](aegis-database.md)
│   ├── 📁 adapters/         # 5 adapters (MySQL, Supabase, None) - 757 linhas
│   ├── 📁 schemas/          # 6 schemas (MySQL + Supabase) - 1.336 linhas
│   ├── 📁 migrations/       # 15 migrations incrementais
│   ├── 📁 utils/            # Scripts (DROP, TRUNCATE, reset)
│   ├── 📁 _archived/        # 11 migrations antigas
│   └── DEPLOY-SCHEMA-COMPLETO.sql  # Schema unificado (695 linhas)
│
└── 📁 storage/ → [aegis-storage.md](aegis-storage.md)
    ├── 📁 cache/            # Cache de arquivos (file driver)
    ├── 📁 logs/             # 29 logs diários (JSON estruturado)
    ├── 📁 uploads/          # Uploads organizados por módulo (blog, members, palpiteiros, times)
    ├── settings.json        # Configurações do site (editável via admin)
    ├── versions.json        # Histórico de versões (auto-bump)
    └── last-bump.txt        # Data do último bump
```

---

## 📊 RESUMO GERAL

### Status da Documentação

- ✅ **Raiz** - Documentado em [aegis-raiz.md](aegis-raiz.md)
- ✅ **admin/** - Documentado em [aegis-admin.md](aegis-admin.md)
- ✅ **api/** - Documentado em [aegis-api.md](aegis-api.md)
- ✅ **assets/** - Documentado em [aegis-assets.md](aegis-assets.md)
- ✅ **components/** - Documentado em [aegis-components.md](aegis-components.md)
- ✅ **core/** - Documentado em [aegis-core.md](aegis-core.md)
- ✅ **modules/** - Documentado em [aegis-modules.md](aegis-modules.md)
- ✅ **routes/** - Documentado em [aegis-routes.md](aegis-routes.md)
- ✅ **frontend/** - Documentado em [aegis-frontend.md](aegis-frontend.md)
- ✅ **database/** - Documentado em [aegis-database.md](aegis-database.md)
- ✅ **storage/** - Documentado em [aegis-storage.md](aegis-storage.md)

---

## 🎯 CLASSIFICAÇÃO GERAL

### CORE-AEGIS (Genérico - Reutilizável)
- **Raiz:** 13 arquivos (100% necessários)
- **admin/:** 5 ferramentas + 15 controllers (100% CORE)
- **api/:** 5 APIs genéricas + 1 controller (CORE)
- **components/:** 7 de 10 componentes (70% do total)

### MISTO (Parcialmente Genérico)
- **admin/api/:** process-csv.php (70% CORE / 30% APP-FE)
- **api/:** chart-data.php (80% CORE), metriccard-data.php (75% CORE)
- **components/:** Cards (70% CORE), Graficos (85% CORE)

### APP-FE ESPECÍFICO
- **admin/api/:** import-csv.php (100% específico)
- **api/:** list-canais.php, youtube-data.php (DEPRECADAS)

---

## ⚠️ REGRAS GERAIS

1. **NUNCA deletar:** index.php, config.php, _config.php, routes.php, .htaccess
2. **NUNCA commitar:** _config.php, storage/, vendor/
3. **Composer só necessário se:** Usar Reports/Excel (PhpSpreadsheet)
4. **setup.php só roda:** Na primeira instalação
5. **Ferramentas admin/** são genéricas e reutilizáveis
6. **APIs admin/api/** validam CSRF mas não usam Auth::require() (chamadas internas)

---

## 🔒 SEGURANÇA

**Status:** ✅ AUDITADO (2026-01-18)
**Score:** 9.5/10
**Vulnerabilidades:** 0 (zero)

**Documentação:**
- **[SECURITY-PATTERNS.md](SECURITY-PATTERNS.md)** - Padrões oficiais de segurança
- **[SECURITY-RESOLUTION.md](SECURITY-RESOLUTION.md)** - Relatório completo de auditoria

**Destaques:**
- ✅ UUID everywhere (não auto_increment)
- ✅ Prepared statements 100%
- ✅ CSRF em todos os forms
- ✅ Upload hardening (5 camadas)
- ✅ Rate limiting integrado
- ✅ Session regeneration
- ✅ Bcrypt cost 12 + auto-rehash

**Próxima auditoria:** 2026-07-18 (6 meses)

---

## 📝 MELHORIAS FUTURAS (BACKLOG)

**Q2 2026:**
1. Connection pooling (performance +20%)
2. Backup automático settings.json

**Q3 2026:**
3. Rotação automática logs (> 90 dias)
4. Rate limit em APIs públicas

**Q4 2026:**
5. Bcrypt cost 14 (padrão 2024+)
6. Session hardening (flags explícitos)
7. Upload re-processing (camada extra)
