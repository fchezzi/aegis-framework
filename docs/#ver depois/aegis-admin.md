# AEGIS Framework - Pasta /admin/

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-17

[← Voltar ao índice](aegis-estrutura.md)

---

## 🔧 FERRAMENTAS ADMINISTRATIVAS (5 arquivos)

Todas requerem: `Auth::require()`

1. **cache.php** - Gerenciador de cache (limpar, stats, TTL)
2. **health.php** - Health check completo (score 0-100%)
3. **version.php** - Versionamento semântico + CHANGELOG
4. **import-csv.php** - Import CSV universal (3 etapas)
5. **deploy.php** - Gerador de ZIP para deploy (V1 - apenas código)
6. **deploy-v2.php** - Gerador completo (código + banco) *RECOMENDADO*
7. **import-sql.php** - Importador de SQL via upload

**Classificação:** 100% CORE-AEGIS

---

## 🚀 Sistema de Deploy

### Deploy V1 (`deploy.php`)
**Gera apenas código (.tar.gz)**
- Inclui: admin, core, frontend, modules, routes, api, public, storage estrutura
- Exclui: _config.php, .env, logs, cache, uploads (opcional)
- **Uso:** Deploy de atualizações de código sem mexer no banco
- **Formato:** `aegis-{ambiente}-{timestamp}.tar.gz`

### Deploy V2 (`deploy-v2.php`) ⭐ RECOMENDADO
**Gera código + banco de dados**
- **Estrutura do pacote (.zip):**
  ```
  deploy-completo-{ambiente}-{timestamp}.zip
  ├── codigo/
  │   └── aegis-{ambiente}-{timestamp}.tar.gz
  ├── database/
  │   └── database-{timestamp}.sql
  └── DEPLOY-INSTRUCOES.txt
  ```

**Processo de geração:**
1. Gera tar.gz do código (igual Deploy V1)
2. Exporta banco via mysqldump:
   - `--skip-triggers`
   - `--single-transaction`
   - Remove DEFINER via sed (evita erro SUPER privilege)
3. **IMPORTANTE:** SQL é exportado exatamente como está (sem substituições de URL)
4. Cria arquivo de instruções
5. Empacota tudo em um ZIP final

**Opções:**
- ☑️ Incluir banco de dados
- ☑️ Incluir uploads (padrão: excluído)

**Troubleshooting de URLs:**
- Deploy exporta URLs como estão no banco (ex: `http://localhost:5757/...`)
- **Após importar**, rodar UPDATE manual:
  ```sql
  UPDATE page_cards SET content = REPLACE(content, 'http://localhost:5757/futebol-energia', 'https://seudominio.com') WHERE content LIKE '%localhost%';
  ```
- **Alternativa:** Fazer upload manual de imagens/assets

### Importador SQL (`import-sql.php`)
**Upload e importação de SQL via interface web**

**Processo:**
1. Upload do arquivo .sql (máx 50MB)
2. Importação via:
   - **Método 1 (preferido):** MySQL CLI (mais rápido, confiável)
   - **Método 2 (fallback):** PDO multi-query
3. Limpeza automática de cache após importação

**Segurança:**
- Apenas arquivos .sql permitidos
- Validação de mime-type
- Requer autenticação admin
- CSRF protection

**Limitações:**
- Tamanho máximo: 50MB (configurável no PHP)
- Timeout: 300s (5 minutos)

**Pós-importação manual:**
- Ajustar `_config.php` com credenciais do servidor
- Corrigir URLs no banco (se necessário)
- Limpar cache: `rm -rf storage/cache/*`

### Histórico de Mudanças (2026-01-18)

**❌ REMOVIDO** do Deploy V2 e Import SQL:
- Sistema de replace automático `{{APP_URL}}`
- Pós-processamento de URLs
- **Motivo:** Causava problemas com caminhos relativos

**Ver:** [CHANGELOG-2026-01-18.md](CHANGELOG-2026-01-18.md)

---

## 📁 admin/api/ (2 arquivos)

**import-csv.php** - APP-FE ESPECÍFICO (100%)
- 10 tabelas hardcoded
- 10 funções de importação customizadas

**process-csv.php** - MISTO (70% CORE / 30% APP-FE)
- Upload, validação, encoding: CORE
- getRequiredHeaders(): APP-FE

---

## 📁 admin/components/ (1 arquivo)

**tables.php** - CORE-AEGIS (100%)
- Lista tabelas do banco (SHOW TABLES)
- Retorna JSON

---

## 📁 admin/controllers/ (15 controllers MVC)

Todos **100% CORE-AEGIS**:

1. AdminController - Dashboard
2. AuthController - Login/logout
3. ComponentsController - CRUD components
4. ContentController - CRUD content
5. DataSourceController - CRUD data sources
6. DocsController - Documentação markdown
7. GroupController - CRUD grupos (batch queries)
8. IncludesController - Gerenciar PHP includes (valida sintaxe)
9. MemberController - CRUD members
10. MenuController - CRUD menu (file lock)
11. ModulesController - Install/uninstall módulos
12. PageBuilderController - Visual builder (MAX 50 blocos)
13. PagesController - CRUD páginas (protege type=core)
14. ReportTemplateController - Excel reports (PhpSpreadsheet)
15. SettingsController - Settings (atualiza _config.php + SASS)

**Padrões:**
- Auth::require() + CSRF
- UUID via Core::generateUUID()
- Prepared statements
- Sanitização completa

---

## 📁 admin/views/

Templates HTML/PHP sem lógica de negócio.

**Subpastas:** admins/, components/, contents/, data-sources/, groups/, includes/, members/, menu/, modules/, page-builder/, pages/, reports/

**Classificação:** 100% CORE-AEGIS
