# AEGIS Framework - Pasta /core/ (Parte 3)

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-18
**Arquivos:** 41-57 + helpers/ + stubs/

[← Voltar ao índice](aegis-core.md)

---

## 🟢 CORE-AEGIS (1 arquivo)

### 41. RateLimiter.php (453 linhas) - 10/10

**Função:** Rate limiting multi-backend (APCu > Session > File)

**Recursos:**
- **Auto storage (linha 70-83):** Prioridade APCu > Session > File
- **Sliding window (linha 143-163):** Array de timestamps, array_filter cleanup
- **Static API (linha 94-121):** check(), increment(), retryAfter()
- **Instance API:** tooManyAttempts(), hit(), clear(), remaining()
- **File backend (linha 298-347):** JSON com expires, auto-cleanup
- **Login protection (linha 406-451):** Dual limiter (IP + user)
- **Middleware helper (linha 359-393):** registerMiddleware() para Router
- **availableIn() (linha 206-219):** Tempo até retry (segundos)

**Classificação:** 100% CORE-AEGIS

---

## 🔴 APP-SPECIFIC (2 arquivos)

### 42. ReportDataSources.php (148 linhas) - APP-FE

**Função:** Fontes de dados hardcoded para relatórios Excel

**APP-FE:**
- Hardcoded YouTube queries (linha 72-96): `tbl_youtube`
- Específico projeto Futebol Energia
- Custom sources via DB (linha 33-52)

**Classificação:** 100% APP-SPECIFIC

---

### 43. ReportQueryBuilder.php (422 linhas) - APP-FE

**Função:** Query builder seguro com whitelist de tabelas

**APP-FE:**
- Whitelist 10 tabelas (linha 19-164): tbl_youtube, tbl_facebook, tbl_instagram, tbl_x, tbl_tiktok, tbl_twitch, tbl_website, youtube_extra, tbl_x_inscritos, tbl_app
- Hardcoded column schemas (linha 20-163)
- Específico projeto Futebol Energia

**Recursos CORE:**
- Whitelisting pattern: Tabelas, operações, operadores
- Prepared statements: Segurança SQL injection
- Preview mode (linha 383-420)

**Classificação:** 80% APP-SPECIFIC / 20% CORE pattern

---

## 🟢 CORE-AEGIS (continuação)

### 44. Request.php (554 linhas) - 10/10

**Função:** Encapsulamento de requisições HTTP com sanitização automática

**Recursos:**
- **Auto-sanitize (linha 200-205):** htmlspecialchars em GET/POST, recursivo em arrays
- **Priority input() (linha 72-93):** POST > JSON > GET
- **JSON body cache (linha 132-149):** Lê `php://input` apenas 1x, verifica Content-Type
- **Headers parse (linha 273-292):** HTTP_* + Content-Type + Content-Length
- **Bearer token (linha 299-307):** Extrai de `Authorization: Bearer {token}`
- **IP detection (linha 474-497):** CF_CONNECTING_IP > X_FORWARDED_FOR > X_REAL_IP > REMOTE_ADDR
- **Method override (linha 349-358):** `_method` field ou `X-HTTP-Method-Override` header
- **Helper methods:** has(), filled(), only(), except()
- **File helpers:** file(), hasFile(), files()
- **Info methods:** uri(), url(), baseUrl(), ip(), userAgent(), referer(), segments()

**Classificação:** 100% CORE-AEGIS

---

### 45. Response.php (550 linhas) - 10/10

**Função:** Padronização de respostas HTTP com helpers

**Recursos:**
- **JSON factory (linha 73-83):** UTF-8 unescaped, auto Content-Type
- **Success/Error (linha 92-125):** Structured API responses
- **Redirect (linha 134-162):** redirect(), back(), redirectWith() (flash message)
- **Download (linha 172-197):** Auto mime detection, Content-Disposition attachment
- **File stream (linha 205-218):** inline (não attachment)
- **View render (linha 228-253):** Layout support via ob_start()
- **HTTP status helpers (linha 317-383):** ok(), created(), badRequest(), unauthorized(), forbidden(), notFound(), validationError(), tooManyRequests(), serverError()
- **Cache headers (linha 500-516):** cache($seconds), noCache()
- **CORS (linha 522-548):** cors(), corsOptions() para preflight
- **Cookie (linha 466-477):** samesite=Lax, httpOnly=true default
- **Content types:** json(), text(), html(), xml()

**Classificação:** 100% CORE-AEGIS

---

### 46. Router.php (350 linhas) - 10/10

**Função:** Router com middleware pipeline e group support

**Recursos:**
- **HTTP verbs:** get(), post(), put(), delete()
- **Middleware pipeline (linha 205-223):** Reverse order, nested closures
- **Group stack (linha 58-148):** prefix + middleware inheritance
- **Pattern params (linha 228-232):** `:id` → `([^/]+)` regex
- **Controller syntax (linha 243-276):** `UserController@index`
- **Auto-load controller (linha 249-261):** 3 paths: admin/controllers, public/controllers, core
- **Base path (linha 34-40):** Auto-detect subpasta via SCRIPT_NAME
- **Route class (linha 305-349):** Fluent middleware chaining
- **404 handling (linha 184-186):** Default fallback

**Classificação:** 100% CORE-AEGIS

---

### 47. Scheduler.php (775 linhas) - 10/10

**Função:** Cron-style task scheduler (Laravel-inspired)

**Recursos:**
- **DSL methods (linha 212-342):** everyMinute(), everyFiveMinutes(), hourly(), hourlyAt(), daily(), dailyAt(), weekly(), monthly(), yearly(), weekdays(), weekends(), mondays()-sundays()
- **Cron expression parser (linha 577-623):** Wildcards `*`, lists `1,2,3`, ranges `1-5`, steps `*/5`
- **Task types (linha 47-78):** command, callback, job, exec
- **Constraints (linha 420-464):** when(), skip(), between(), environments(), withoutOverlapping (file lock), onOneServer, runInBackground
- **Hooks (linha 470-542):** before(), after(), onSuccess(), onFailure(), pingBefore(), pingAfter(), pingOnSuccess(), pingOnFailure()
- **Execution (linha 87-123):** run() executa tarefas due, Logger integration
- **Lock file (linha 652-674):** sys_get_temp_dir() + MD5 hash

**Classificação:** 100% CORE-AEGIS

---

### 48. Security.php (368 linhas) - 10/10

**Função:** Security layers (CSRF, XSS, Password, UUID, Validation)

**Recursos:**
- **CSRF multi-source (linha 82-99):** POST field, X-CSRF-TOKEN header, X-XSRF-TOKEN (Angular)
- **hash_equals() (linha 61, 265):** Timing-safe comparison
- **Password bcrypt (linha 149-174):** cost=12, needsRehash(), verifyAndRehash()
- **Auto-rehash (linha 184-198):** Update old hashes após login bem-sucedido
- **UUID v4 (linha 138-144):** cryptographically secure via random_bytes
- **Security headers (linha 239-256):** X-Frame-Options DENY, X-Content-Type-Options, HSTS, Permissions-Policy, CSP disabled
- **Sanitize (linha 120-125):** htmlspecialchars recursivo
- **Password strength (linha 203-227):** 8+ chars, maiúscula, minúscula, número, especial
- **File upload (linha 270-284):** sanitizeFilename(), basename(), preg_replace
- **MIME validation (linha 289-304):** finfo_file()
- **Action tokens (linha 333-351):** One-time use via HMAC
- **Session destroy (linha 316-328):** Clear cookies, session_destroy()

**Classificação:** 100% CORE-AEGIS

---

### 49. ServiceProvider.php (367 linhas) - 10/10

**Função:** Service container com lazy loading e auto-wiring

**Recursos:**
- **Lazy loading (linha 116-142):** Resolve apenas quando get() chamado
- **Singleton vs transient (linha 68-87):** Configurable per-service
- **Auto-wiring (linha 176-243):** Reflection + type-hint resolution recursivo
- **Dependency resolution (linha 197-243):** Type hints, default values, nullable
- **Alias support (linha 92-94, 117-120):** Múltiplos nomes para mesmo service
- **Boot lifecycle (linha 252-274):** booting() callbacks executados em boot()
- **Instance registration (linha 84-87):** Pré-instanciado
- **Helper functions (linha 343-366):** app(), resolve()
- **Utility (linha 289-336):** forget(), flush(), reset(), getProviders(), getResolved()

**Classificação:** 100% CORE-AEGIS

---

### 50. Settings.php (161 linhas) - 10/10

**Função:** Configurações JSON com cache em memória

**Recursos:**
- **JSON storage (linha 14-18):** `storage/settings.json`
- **In-memory cache (linha 24-46):** Carrega apenas 1x por request
- **Auto-create defaults (linha 52-71):** Se arquivo não existe ou JSON inválido
- **Pretty print (linha 69, 78):** JSON_PRETTY_PRINT + UNESCAPED_UNICODE
- **API methods:** get(), set(), all(), updateMultiple(), has(), remove()

**Classificação:** 100% CORE-AEGIS

---

### 51. SimpleCache.php (172 linhas) - 9/10

**Função:** Cache session-based com TTL

**Recursos:**
- **Session storage (linha 16-55):** `$_SESSION['cache'][$key]`
- **TTL support (linha 27-30):** Auto-cleanup em get()
- **Pattern flush (linha 97-113):** `flushPattern('palpites_*')` com regex
- **Remember pattern (linha 123-134):** Laravel-style cache-or-execute
- **Stats (linha 141-171):** total_items, expired_items, valid_items, total_size

**Limitações:**
- Session-only: Não persiste entre restarts
- Single-server: Não compartilha cache

**Classificação:** 100% CORE-AEGIS

---

### 52. Upload.php (337 linhas) - 10/10

**Função:** Upload seguro com múltiplas validações

**Recursos:**
- **MIME whitelist (linha 15-32):** 11 tipos (images, docs, text)
- **finfo_file() (linha 46-48):** NÃO confia em `$_FILES['type']`
- **Validation chain (linha 150-171):** MIME → extension → size → dimensions
- **Safe naming (linha 123-134):** `timestamp_randomhex.ext` (não usa nome original)
- **Date-based dirs (linha 177-185):** `uploads/{type}/{year}/{month}`
- **Permissions (linha 194):** chmod 0644
- **Fallback MIME (linha 244-260):** finfo > mime_content_type > extension (compatibilidade)
- **image() method (linha 217-302):** Upload simplificado com customName option
- **Security:** Extension vs MIME validation (linha 65-74), size limit, dimension limit

**Classificação:** 100% CORE-AEGIS

---

### 53. Validator.php (547 linhas) - 10/10

**Função:** Validação robusta Laravel-style

**Recursos:**
- **27 regras (linha 6-27):** required, email, min, max, numeric, alpha, alphanumeric, url, date, in, regex, confirmed, unique, exists, slug, uuid, json, file, image, mimes, max_size, integer, boolean, array, between
- **Dot notation (linha 181-193):** `user.name` para arrays aninhados
- **Custom messages (linha 219-231):** field.rule specific ou global
- **DB rules (linha 363-398):** unique(table, column, exceptId), exists(table, column)
- **File rules (linha 400-450):** file, image, mimes (12 tipos), max_size
- **confirmed pattern (linha 337-340):** password + password_confirmation
- **API:** fails(), passes(), errors(), first(), all(), validated(), only(), except()
- **mb_strlen() (linha 265, 279):** Unicode support

**Classificação:** 100% CORE-AEGIS

---

### 54. Version.php (346 linhas) - 10/10

**Função:** Semantic versioning com auto-bump

**Recursos:**
- **Single source (linha 13):** `.aegis-version` (não hardcode)
- **Bump types (linha 39-54):** major, minor, patch (semver)
- **History (linha 78-97):** `storage/versions.json` estruturado
- **Auto-sync CHANGELOG.md (linha 115-148):** Gera markdown formatado
- **Auto-bump (linha 251-312):** Detecta mudanças últimas 24h, sugere bump, executa se confiança alta/média
- **detectChanges() (linha 318-345):** RecursiveIterator + mtime comparison
- **Emojis CHANGELOG (linha 160-164):** 🚨 Breaking, ✨ Features, 🐛 Fixes

**Classificação:** 100% CORE-AEGIS

---

### 55. VersionAnalyzer.php (201 linhas) - 10/10

**Função:** Análise inteligente para sugerir tipo de bump

**Recursos:**
- **Sugestão automática (linha 16-30):** analyze → calculate new version
- **classifyFiles (linha 50-81):** created, modified, deleted
- **Breaking signals (linha 99-111):** Keywords (BREAKING), schema changes, deleted files → major
- **Feature signals (linha 114-116):** core/ files, modules/, admin/ pages, created files → minor
- **Patch fallback (linha 173-179):** Apenas modified files → patch
- **Confidence levels (linha 139, 165, 176):** alta, média
- **Signals methods (linha 124-179):** signalMajor(), signalMinor(), signalPatch()

**Classificação:** 100% CORE-AEGIS

---

## 📊 RESUMO PARTE 3 (15 arquivos analisados)

| # | Arquivo | Linhas | Qualidade | Classificação |
|---|---------|--------|-----------|---------------|
| 41 | RateLimiter | 453 | 10/10 | CORE-AEGIS |
| 42 | ReportDataSources | 148 | N/A | APP-FE |
| 43 | ReportQueryBuilder | 422 | N/A | APP-FE |
| 44 | Request | 554 | 10/10 | CORE-AEGIS |
| 45 | Response | 550 | 10/10 | CORE-AEGIS |
| 46 | Router | 350 | 10/10 | CORE-AEGIS |
| 47 | Scheduler | 775 | 10/10 | CORE-AEGIS |
| 48 | Security | 368 | 10/10 | CORE-AEGIS |
| 49 | ServiceProvider | 367 | 10/10 | CORE-AEGIS |
| 50 | Settings | 161 | 10/10 | CORE-AEGIS |
| 51 | SimpleCache | 172 | 9/10 | CORE-AEGIS |
| 52 | Upload | 337 | 10/10 | CORE-AEGIS |
| 53 | Validator | 547 | 10/10 | CORE-AEGIS |
| 54 | Version | 346 | 10/10 | CORE-AEGIS |
| 55 | VersionAnalyzer | 201 | 10/10 | CORE-AEGIS |

**Total analisado:** 5.751 linhas
**CORE-AEGIS:** 13/15 arquivos (5.181 linhas) - 9.92/10 média
**APP-SPECIFIC:** 2/15 arquivos (570 linhas)

---

## 📂 SUBPASTAS

### helpers/

**Arquivo:** table_helper.php (120 linhas)

**Função:** Wrappers para componente Tabelas

**Functions:**
- `render_table($data, $options)` - Genérico (linha 14-45)
- `simple_table($data, $title)` - Sem features (linha 54-61)
- `searchable_table($data, $title)` - Com busca (linha 70-77)
- `full_table($data, $title, $perPage)` - Completo (linha 87-95)
- `db_table($query, $params, $options)` - Query SQL direta (linha 105-119)

**Classificação:** 100% CORE-AEGIS

---

### stubs/

**8 arquivos de templates** para geração de código:

1. **api-controller.stub** - API RESTful CRUD (index, store, show, update, destroy)
2. **controller.stub** - Controller MVC (index, show, create, store, edit, update, destroy)
3. **job.stub** - Job para Queue (tries, timeout, handle(), failed())
4. **middleware.stub** - Middleware pattern (handle($next))
5. **model.stub** - Model básico (table, primaryKey, fillable, timestamps, relations)
6. **notification.stub** - Notifiable class (channels, toDatabase, toMail)
7. **seeder.stub** - Seeder (run(), getData(), output())
8. **test.stub** - TestCase (setUp, tearDown, test methods)

**Placeholders:** {{CLASS_NAME}}, {{DESCRIPTION}}, {{ROUTE}}, {{TABLE}}, {{VIEW_PATH}}, {{SLUG}}

**Uso:** CLI `php aegis make:controller`, `make:model`, `make:job`, etc.

**Classificação:** 100% CORE-AEGIS

---

## 📊 RESUMO FINAL PARTE 3

**Arquivos PHP:** 15 (13 CORE + 2 APP-FE)
**Helpers:** 1 arquivo (120 linhas)
**Stubs:** 8 templates

**Total Parte 3:** 5.871 linhas documentadas
**CORE-AEGIS:** 14/16 componentes (87.5%)
**APP-SPECIFIC:** 2/16 componentes (12.5%)
