# 📋 Changelog - AEGIS Framework

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

---



## [17.3.8] - 2026-02-16

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- database/migrations/create_uptime_tables.sql
- database/migrations/create_search_console_tables.sql
- composer.lock
- composer.phar
- core/GoogleSearchConsole.php
- core/UptimeRobot.php
- CHANGELOG.md
- phpstan.neon
- config.codekit3
- README-phpstan.html


## [17.3.7] - 2026-02-15

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- database/migrations/create_tbl_cruds.sql
- database/migrations/create_tbl_produtos.sql
- database/migrations/create_tbl_bigbanner.sql
- database/create_tbl_ftp_sync_log.sql
- .DS_Store
- core/CrudGenerator.php
- frontend/controllers/FrontendBigbannerController.php
- frontend/views/partials/bigbanner.php
- frontend/pages/home.php
- config.codekit3


## [17.3.6] - 2026-02-14

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- test_banners.php
- migrations/003_create_banner_hero_table.sql
- migrations/002_create_banners_table.sql
- test_require.php
- .DS_Store
- frontend/controllers/FrontendBannerController.php
- frontend/views/partials/banner-hero.php
- frontend/pages/home.php
- delete_banners_table.php
- debug_banner.php


## [17.3.5] - 2026-02-13

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- database/adapters/MySQLAdapter.php
- migrations/002_create_banners_table.sql
- .DS_Store
- RELATORIO_TESTES_PRATICOS.md
- core/DB.php
- index.php
- frontend/controllers/FrontendBannerController.php
- frontend/views/partials/banner-hero.php
- frontend/pages/home.php
- config.codekit3


## [17.3.4] - 2026-02-12

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- CHANGELOG.html
- CHANGELOG.md
- config.codekit3
- admin/check-render-blocking.php
- admin/debug-render-blocking.php
- admin/debug-third-party.php
- admin/test-list.php
- admin/check-render-blocking-detail.php
- admin/debug-pagespeed.php
- admin/api/pagespeed-save.php


## [17.3.3] - 2026-02-11

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3


## [17.3.2] - 2026-02-10

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3


## [17.3.1] - 2026-02-09

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- _config.php.backup.20260208_112358
- _config.php.backup.20260208_193747
- database/adapters/MySQLAdapter.php
- _config.php.backup.20260208_132756
- composer.lock
- _config.php.backup.20260208_112154
- _config.php.backup.20260208_201421
- _config.php.backup.20260208_133538
- .DS_Store
- core/SEO.php


## [17.3.0] - 2026-02-08

### ✨ New Features

Credenciais FTP configuráveis para deploy automatizado

- admin/views/settings.php
- admin/controllers/SettingsController.php
- assets/js/admin-settings.js
- routes/admin.php
- .aegis-version
- CHANGELOG.md
- docs/_state.md
- storage/versions.json


## [17.2.0] - 2026-02-08

### ✨ New Features

Google Tag Manager - Integração completa via painel admin

- frontend/includes/_gtm-head.php
- frontend/includes/_gtm-body.php
- admin/views/settings.php
- admin/controllers/SettingsController.php
- .aegis-version
- CHANGELOG.md
- docs/_state.md
- storage/versions.json


## [17.1.0] - 2026-02-08

### ✨ New Features

Sistema de favicons customizáveis (3 contextos separados) + Refatoração admin

- admin/includes/_admin-head.php (NOVO)
- frontend/includes/_members-head.php (NOVO)
- storage/uploads/favicon/.htaccess (NOVO)
- admin/views/settings.php
- admin/controllers/SettingsController.php
- frontend/includes/_head.php
- frontend/templates/dashboard.php
- frontend/templates/basic.php
- 40 views admin refatoradas (dashboard, settings, login, pages, admins, menu, etc)
- .aegis-version
- CHANGELOG.md
- docs/_state.md


## [17.0.0] - 2026-02-08

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/migrations/2026_02_07_create_tbl_fonts.sql
- database/schemas/mysql-schema.sql
- _config.php.backup.20260207_180842
- core/Email.php
- core/Logger.php
- CHANGELOG.html
- deploys/deploy-completo-producao-20260207-161524.zip
- frontend/includes/_aside.php
- frontend/includes/_aside.php.backup
- frontend/includes/_header.php


## [16.0.0] - 2026-02-07

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/migrations/add_scope_to_pages_mysql.sql
- database/migrations/rollback_scope_pages.sql
- database/migrations/add_scope_to_pages_supabase.sql
- database/schemas/supabase-schema-minimal.sql
- database/schemas/mysql-schema-minimal.sql
- database/schemas/supabase-schema.sql
- database/schemas/mysql-schema.sql
- core/MemberAuth.php
- core/CoreConfig.php
- CHANGELOG.html


## [15.2.4] - 2026-02-06

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- database/migrations/2026_02_05_add_context_to_pages.sql
- core/CoreConfig.php
- CHANGELOG.html
- frontend/includes/_head.php
- CHANGELOG.md
- config.codekit3
- admin/views/login.php
- .claude/future-modules.md
- _config.php
- docs/AEGIS-MODULOS-GUIA-COMPLETO.md


## [15.2.3] - 2026-02-05

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3
- docs/AEGIS-MODULOS-GUIA-COMPLETO.md


## [15.2.2] - 2026-01-31

### 🐛 Bug Fixes

CRITICAL FIX: Upload de imagens (ModSecurity + Auth hardening)

- api/.htaccess
- api/upload-image.php
- .aegis-version
- storage/versions.json
- CHANGELOG.md
- docs/UPLOAD-FIX-2026-01-31.md


## [15.2.1] - 2026-01-31

### 🐛 Bug Fixes

Reorganização UX em /admin/settings + campos de integrações opcionais + valores padrão vazios

- admin/views/settings.php
- .aegis-version
- storage/versions.json
- CHANGELOG.md
- docs/_state.md


## [15.2.0] - 2026-01-31

### ✨ New Features

Upload de logo editável + cores customizáveis (light/dark) + SVG inline com currentColor

- admin/views/settings.php
- admin/controllers/SettingsController.php
- frontend/includes/_dash-header.php
- core/Upload.php
- assets/sass/modules/_m-header.sass
- storage/.htaccess (NOVO)
- frontend/pages/*.php (9 arquivos)
- frontend/templates/*.php
- modules/artigos/views/*.php (2 arquivos)
- .aegis-version
- storage/versions.json
- CHANGELOG.md
- docs/_state.md


## [15.1.0] - 2026-01-31

### ✨ New Features

Dashboard Colors editáveis via /admin/settings

- admin/views/settings.php
- admin/controllers/SettingsController.php
- .aegis-version
- storage/versions.json


## [15.0.1] - 2026-01-31

### 🐛 Bug Fixes

Bugfix: Validação de foreign keys em GroupController

- admin/controllers/GroupController.php
- .aegis-version
- storage/versions.json


## [15.0.0] - 2026-01-31

### 🚨 Breaking Changes

Páginas CORE pré-instaladas no setup + coluna type em todos os schemas

- database/schemas/mysql-schema.sql
- database/schemas/mysql-schema-minimal.sql
- database/schemas/supabase-schema.sql
- database/schemas/supabase-schema-minimal.sql
- .aegis-version
- storage/versions.json


## [14.0.16] - 2026-01-31

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3


## [14.0.15] - 2026-01-29

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3


## [14.0.14] - 2026-01-27

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- .DS_Store
- CHANGELOG.html
- CHANGELOG.md
- config.codekit3
- .claude/.DS_Store
- .claude/INDEX.md
- _config.php
- docs/_state.md
- storage/last-bump.txt
- storage/versions.json


## [14.0.13] - 2026-01-26

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- .DS_Store
- config.codekit3


## [14.0.12] - 2026-01-24

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- CHANGELOG.html
- CHANGELOG.md
- config.codekit3
- storage/last-bump.txt
- storage/versions.json
- .aegis-version


## [14.0.11] - 2026-01-23

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3


## [14.0.10] - 2026-01-21

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3


## [14.0.9] - 2026-01-20

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3


## [14.0.8] - 2026-01-19

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- .DS_Store
- core/Component.php
- core/Version.php
- core/MemberAuth.php
- deploys/deploy-completo-producao-20260118-195635.zip
- deploys/deploy-completo-producao-20260118-185342.zip
- deploys/deploy-completo-producao-20260118-181455.zip
- deploys/deploy-completo-producao-20260118-185337.zip
- deploys/deploy-completo-producao-20260118-205809.zip
- deploys/deploy-completo-producao-20260118-181423.zip


## [14.0.7] - 2026-01-18

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- .DS_Store
- config.codekit3
- admin/.DS_Store
- admin/views/.DS_Store
- docs/aegis-core-01.html
- docs/aegis-routes.md
- docs/aegis-database.md
- docs/aegis-frontend.md
- docs/.DS_Store
- docs/SECURITY-RESOLUTION.md


## [14.0.6] - 2026-01-16

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- .DS_Store
- deploys/.DS_Store
- config.codekit3
- admin/.DS_Store
- admin/deploy.php
- admin/api/import-csv.php
- admin/api/process-csv.php
- uploads/.DS_Store
- docs/.TEMP-analise-pasta-admin.md
- docs/aegis/README.md


## [14.0.5] - 2026-01-15

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- CHANGELOG.html
- CHANGELOG.md
- config.codekit3
- storage/last-bump.txt
- storage/versions.json
- .aegis-version


## [14.0.4] - 2026-01-14

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- core/ReportQueryBuilder.php
- config.codekit3
- uploads/reports/report_6966c069548ed_1768341609.xlsx


## [14.0.3] - 2026-01-13

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- database/DEPLOY-SCHEMA-COMPLETO.sql
- .DS_Store
- LIMPAR-ANTES-DEPLOY.sh
- CHANGELOG.html
- deploys/aegis-producao-20260112-204313.tar.gz
- deploys/aegis-producao-20260112-210035.tar.gz
- CHANGELOG.md
- DEPLOY-AGORA.md
- config.codekit3
- uploads/reports/report_696584a314851_1768260771.xlsx


## [14.0.2] - 2026-01-12

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- database/DEPLOY-SCHEMA-COMPLETO.sql
- LIMPAR-ANTES-DEPLOY.sh
- DEPLOY-AGORA.md
- config.codekit3
- DEPLOY-RAPIDO.md


## [14.0.1] - 2026-01-11

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- database/create_tbl_x_inscritos.sql
- database/create_tbl_instagram.sql
- database/create_tbl_x.sql
- database/create_tbl_tiktok.sql
- CHANGELOG.html
- frontend/pages/instagram.php
- frontend/pages/x.php
- frontend/pages/home.php
- frontend/pages/tik-tok.php
- CHANGELOG.md


## [14.0.0] - 2026-01-10

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/schemas/supabase-schema.sql
- database/schemas/mysql-schema.sql
- .DS_Store
- core/MemberAuth.php
- CHANGELOG.html
- frontend/includes/_header.php
- frontend/pages/dashboard.php
- frontend/pages/home.php
- frontend/pages/profile.php
- CHANGELOG.md


## [13.0.5] - 2026-01-09

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- .DS_Store
- config.codekit3


## [13.0.4] - 2025-12-21

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- database/database.db
- CHANGELOG.html
- frontend/includes/_head.php
- frontend/pages/charts.php
- frontend/pages/cards.php
- CHANGELOG.md
- config.codekit3
- storage/last-bump.txt
- storage/versions.json
- components/filtros/Filtros.php


## [13.0.3] - 2025-12-20

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- CHANGELOG.html
- frontend/pages/facebook.php
- CHANGELOG.md
- config.codekit3
- admin/api/import-csv.php
- admin/api/process-csv.php
- storage/last-bump.txt
- storage/versions.json
- components/cards/Cards.php
- .aegis-version


## [13.0.2] - 2025-12-19

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- tbl_facebook_import.csv
- config.codekit3
- admin/import-csv.php
- components/tabelas/Tabelas.php
- assets/js/tabela-filtros.js
- assets/js/tabela-filtros-min.js


## [13.0.1] - 2025-12-18

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- frontend/includes/_footer.php
- components/filtros/Filtros.php
- components/filtromes/Filtromes.php
- assets/js/filtros-fix.js
- assets/js/filtros-autoload.js
- assets/js/filter-mesano.js
- assets/js/cards-filtros.js


## [13.0.0] - 2025-12-17

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/schemas/mysql-schema.sql
- .DS_Store
- frontend/.DS_Store
- frontend/pages/morde-e-assopra.php
- frontend/pages/.DS_Store
- frontend/pages/website.php
- frontend/pages/damas-em-campo.php
- frontend/pages/energia-em-campo.php
- frontend/pages/estadio-97.php
- frontend/pages/tempo-de-jogo.php


## [12.0.0] - 2025-12-16

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/migrations/create_tbl_website.sql
- database/schemas/mysql-schema.sql
- database/samples/tbl_website_exemplo.csv
- .DS_Store
- frontend/pages/website.php
- config.codekit3
- admin/import-csv.php
- admin/api/import-csv.php
- admin/api/process-csv.php
- _config.php


## [11.0.2] - 2025-12-15

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- frontend/pages/cila.php
- frontend/pages/youtube.php
- CHANGELOG.md
- config.codekit3
- admin/import-csv.php
- admin/views/dashboard.php
- admin/views/page-builder/edit.php
- docs/API.md
- docs/COMPONENTES_ARQUITETURA.md
- docs/README.md


## [11.0.1] - 2025-12-14

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3
- docs/API.md
- docs/COMPONENTES_ARQUITETURA.md
- docs/README.md
- docs/ESTRUTURA.md
- docs/_index.md
- docs/DATABASE.md


## [11.0.0] - 2025-12-12

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/MySQLAdapter.php
- database/mysql-schema.sql
- teste-debug-tabelas.php
- CHANGELOG.html
- frontend/pages/youtube.php
- CHANGELOG.md
- config.codekit3
- .claude/youtube-page-docs.md
- .claude/REGRAS.html
- .claude/REGRAS.md


## [10.0.0] - 2025-12-11

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/MySQLAdapter.php
- database/mysql-schema.sql
- frontend/pages/youtube.php
- config.codekit3
- .claude/youtube-page-docs.md
- .claude/REGRAS.html
- .claude/REGRAS.md
- _config.php
- _archived_scripts/README.md
- components/metriccard/component.json


## [9.0.4] - 2025-12-10

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- check-youtube-table.php
- config.codekit3


## [9.0.3] - 2025-12-09

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- database/create-page-builder-table.sql
- MODO-DYNAMIC-CORRETO.php
- teste-filtro.php
- CHANGELOG.html
- CHANGELOG.md
- verificar-posicao.php
- debug-filtro-valores.php
- config.codekit3
- SOLUCAO-QUE-FUNCIONA.php
- storage/last-bump.txt


## [9.0.2] - 2025-12-08

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- CHANGELOG.html
- CHANGELOG.md
- config.codekit3
- admin/check-youtube-page.php
- admin/components/tables.php
- admin/views/page-builder/edit.php
- storage/last-bump.txt
- storage/versions.json
- components/filtros/component.json
- .aegis-version


## [9.0.1] - 2025-12-07

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3
- admin/components/tables.php
- routes/admin.php


## [9.0.0] - 2025-12-04

### 🚨 Breaking Changes

Sistema de páginas Core vs Custom + Sistema de visibilidade em menus

- database/migrations/add_page_type.sql
- database/mysql-schema.sql
- database/supabase-schema.sql
- admin/controllers/PagesController.php
- admin/views/pages/create.php
- admin/views/pages/edit.php
- admin/views/pages/index.php
- admin/views/menu/create.php
- admin/views/menu/edit.php
- assets/sass/modules/_m-sidebar.sass


## [8.0.3] - 2025-12-04

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- CHANGELOG.html
- frontend/pages/dashboard-page.php
- frontend/pages/blank-page.php
- CHANGELOG.md
- config.codekit3
- storage/last-bump.txt
- storage/versions.json
- .aegis-version


## [8.0.2] - 2025-12-03

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3


## [8.0.1] - 2025-12-01

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- config.codekit3


## [8.0.0] - 2025-11-27

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- DEPLOY_COMPLETO.md
- database/migrations/2024_01_01_000003_create_queue_tables.php
- database/mysql-schema.sql
- .DS_Store
- core/stubs/seeder.stub
- core/stubs/model.stub
- core/stubs/test.stub
- core/stubs/controller.stub
- core/stubs/api-controller.stub
- core/stubs/middleware.stub


## [7.0.0] - 2025-11-26

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- DEPLOY_COMPLETO.md
- database/migrations/2024_01_01_000003_create_queue_tables.php
- database/mysql-schema.sql
- .DS_Store
- core/stubs/seeder.stub
- core/stubs/model.stub
- core/stubs/test.stub
- core/stubs/controller.stub
- core/stubs/api-controller.stub
- core/stubs/middleware.stub


## [6.0.0] - 2025-11-25

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/migrations/add_module_permissions.sql
- database/supabase-schema.sql
- database/mysql-schema.sql
- sync-menu-permissions.php
- test-palpites-route.php
- .pending-issues.md
- core/ModuleManager.php
- core/MenuBuilder.php
- core/MemberAuth.php
- core/Permission.php


## [5.0.1] - 2025-11-24

### 🐛 Bug Fixes

Auto-bump: Apenas modificações em arquivos existentes

- .pending-issues.md
- core/ModuleManager.php
- frontend/pages/remo.php
- frontend/pages/teste.php
- config.codekit3
- admin/controllers/MenuController.php
- admin/controllers/PagesController.php
- .claude/memory/module-patterns.html
- .claude/memory/module-patterns.md
- _config.php


## [5.0.0] - 2025-11-23

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- DEPLOY_COMPLETO.md
- database/aegis.db
- database/migrations/add_is_public_to_pages.sql
- database/migrations/add_module_name_to_pages.sql
- database/migrations/add_is_module_page.sql
- database/supabase-schema-minimal.sql
- database/mysql-schema-minimal.sql
- database/LIMPAR_BANCO.sql
- database/supabase-schema.sql
- database/mysql-schema.sql


## [4.0.0] - 2025-11-22

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/supabase-schema-minimal.sql
- database/MySQLAdapter.php
- database/mysql-schema-minimal.sql
- database/supabase-schema.sql
- database/mysql-schema.sql
- install.php
- core/ModuleManager.php
- core/Upload.php
- core/Core.php
- deploys/aegis-producao-20251121-230307.tar.gz


## [3.0.0] - 2025-11-21

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/SupabaseAdapter.php
- core/ModuleManager.php
- core/Version.php
- core/_deprecated_ModuleMigration.php
- CHANGELOG.html
- CHANGELOG.md
- config.codekit3
- admin/test-exec-sql.php
- admin/controllers/ModulesController.php
- admin/controllers/DocsController.php


## [2.0.0] - 2025-11-20

### 🚨 Breaking Changes

Auto-bump: Mudanças no schema do banco

- database/SupabaseAdapter.php
- core/ModuleManager.php
- core/ModuleMigration.php
- core/Version.php
- config.codekit3
- admin/test-exec-sql.php
- admin/migrations.php
- admin/controllers/ModulesController.php
- admin/controllers/DocsController.php
- admin/views/dashboard.php


## [1.6.0] - 2025-11-18

### ✨ New Features

Adicionado sistema de versionamento automatizado e health check unificado

- Criado sistema de versionamento automatizado (Version.php)
- Interface admin para gerenciar versões
- Health check unificado (framework + módulos)
- Análise automática de mudanças
- Dashboard reorganizado com seção Sistema e Deploy
- Gerenciamento visual de migrations, deploy e cache
- Slash command /aegis turbinado com sugestão automática
- CHANGELOG.md sincronizado automaticamente


## [1.5.0] - 2025-01-16

### ✨ New Features

Primeira versao publica do AEGIS Framework

- Sistema de Seguranca: Autenticacao Dupla, CSRF, Rate Limiting
- Sistema de Banco de Dados: MySQL, Supabase, None
- Sistema de Conteudo: Page Builder, Menu Builder, Includes
- Sistema de Membros: Grupos e Permissoes Granulares
- Performance: Cache em Memoria e Arquivo
- Modularidade: Modo Estatico, Factory Pattern
- Sistema de Documentacao: Auto-geracao a partir do codigo
- Instalacao e Deploy: Wizard interativo, Multi-ambiente

