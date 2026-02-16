# AEGIS Framework - Pasta /routes/

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-18

[← Voltar ao índice](aegis-estrutura.md)

---

## 📊 RESUMO

**Total:** 4 arquivos
**Padrão:** Separação por contexto (admin, api, public, catchall)
**Rotas:** ~170 rotas definidas

---

## 🏗️ ARQUITETURA DE ROTAS

### Ordem de Carregamento (routes.php)

```php
// 1. Admin routes (sempre primeiro)
require_once ROOT_PATH . 'routes/admin.php';

// 2. API routes (versionadas)
require_once ROOT_PATH . 'routes/api.php';

// 3. Public routes (login, home, pages)
require_once ROOT_PATH . 'routes/public.php';

// 4. Module routes (dinâmico - carrega módulos instalados)
ModuleManager::loadRoutes();

// 5. Catch-all (SEMPRE por último)
require_once ROOT_PATH . 'routes/catchall.php';
```

---

## 📁 ARQUIVOS

### 1. admin.php (630 linhas)

**Função:** Todas rotas administrativas (painel admin)

**Padrão de segurança:**
```php
// TODA rota admin SEMPRE requer Auth::require()
Router::get('/admin/caminho', function() {
    Auth::require();
    // controller code
});
```

**Seções (16 blocos):**

1. **ADMIN AUTH** (linhas 8-25)
   - GET `/admin/login` - Exibir formulário
   - POST `/admin/login` - Processar login (AuthController)
   - GET `/admin/logout` - Logout e redirect

2. **ADMIN DASHBOARD** (linhas 27-39)
   - GET `/admin` - Dashboard principal
   - GET `/admin/dashboard` - Alias dashboard

3. **ADMIN TOOLS** (linhas 41-73)
   - GET/POST `/admin/deploy` - Gerador ZIP
   - GET `/admin/cache` - Gerenciador cache
   - GET `/admin/health` - Health check
   - GET/POST `/admin/version` - Versionamento

4. **ADMIN DOCS** (linhas 75-83)
   - GET `/admin/docs/generate` - Gerar documentação (DocsController)

5. **ADMIN USERS** (linhas 85-123)
   - CRUD completo super admins (AdminController)
   - Pattern RESTful: index, create, store, edit, update, destroy

6. **ADMIN MEMBERS** (linhas 125-175)
   - CRUD membros (MemberController)
   - GET/POST `/admin/members/:id/permissions` - Gerenciar permissões

7. **ADMIN GROUPS** (linhas 177-239)
   - CRUD grupos (GroupController)
   - GET/POST `/:id/permissions` - Permissões do grupo
   - GET/POST `/:id/members` - Membros do grupo

8. **ADMIN PAGES** (linhas 241-279)
   - CRUD páginas (PagesController)
   - Identificador: `:slug` (não :id)

9. **ADMIN PAGE BUILDER** (linhas 281-359)
   - GET `/admin/pages/:slug/builder` - Interface visual
   - POST `/save-layout` - Salvar layout completo
   - POST `/add-block`, `/delete-block/:id` - Gerenciar blocos
   - POST `/add-card`, `/delete-card/:id` - Gerenciar cards
   - POST `/update-card-size` - Redimensionar cards

10. **ADMIN COMPONENTS** (linhas 321-352)
    - GET `/admin/components` - Listar componentes
    - GET `/metadata` - Metadados (JSON)
    - POST `/validate` - Validar config
    - POST `/preview` - Preview componente
    - GET `/tables` - Lista tabelas disponíveis

11. **ADMIN SETTINGS** (linhas 363-375)
    - GET/POST `/admin/settings` - Configurações gerais

12. **ADMIN INCLUDES** (linhas 377-421)
    - CRUD includes (header/footer customizáveis)
    - POST `/:name/restore` - Restaurar padrão

13. **ADMIN MENU** (linhas 423-468)
    - CRUD menu items
    - POST `/admin/menu/order` - **IMPORTANTE:** Rota específica ANTES da genérica `:id` (linha 451)

14. **ADMIN MODULES** (linhas 470-507)
    - GET `/admin/modules` - Listar módulos
    - POST `/update`, `/install`, `/uninstall` - Gerenciar módulos
    - GET `/uninstall-step1` - Wizard desinstalação
    - POST `/verify-uninstall` - Verificar antes de desinstalar

15. **ADMIN CSV IMPORT** (linhas 509-526)
    - GET `/admin/import-csv` - Interface importação
    - POST `/admin/api/process-csv` - Preview + validação
    - POST `/admin/api/import-csv` - Executar importação

16. **ADMIN RELATÓRIOS** (linhas 528-564)
    - CRUD templates relatórios (ReportTemplateController)
    - Pattern RESTful completo

17. **ADMIN FONTES DE DADOS** (linhas 566-629)
    - CRUD fontes customizáveis (DataSourceController)
    - GET `/duplicate/:id` - Duplicar fonte
    - GET `/get-columns` - AJAX: Listar colunas tabela
    - POST `/preview` - AJAX: Preview query

**Classificação:** 100% CORE-AEGIS

---

### 2. api.php (113 linhas)

**Função:** API REST versionada

**Arquitetura:**
```php
// Prefixo automático: /api/vX
ApiRouter::version('v1', function() {
    // Rotas públicas
    ApiRouter::get('/status', ...);

    // Rotas autenticadas (JWT)
    ApiRouter::auth(function() {
        ApiRouter::get('/auth/me', ...);
    });
});
```

**Recursos (v1):**

1. **Versões** (linhas 26-30)
   - GET `/api/v1/versions` - Listar versões disponíveis

2. **Status** (linhas 33-44)
   - GET `/api/v1/status` - Health check API

3. **Autenticação JWT** (linhas 46-57)
   - POST `/api/v1/auth/login` - Login (retorna access + refresh tokens)
   - POST `/api/v1/auth/refresh` - Renovar token
   - POST `/api/v1/auth/logout` - Invalidar token

4. **Rotas Autenticadas** (linhas 59-79)
   - GET `/api/v1/auth/me` - Usuário logado (AuthApiController)
   - Exemplos comentados:
     - `ApiRouter::resource('/users', ...)` - CRUD completo
     - `ApiRouter::apiResource('/posts', ...)` - Apenas leitura
     - Middleware role: `->middleware(Middleware::role('admin'))`

**v2 (linhas 84-98):**
- Código comentado
- Exemplo de versionamento
- Pode ter controllers diferentes (namespaced `V2\`)
- Middleware opcional em toda versão

**Deprecation Pattern (linhas 101-112):**
```php
ApiRouter::version('v0', function() {
    // rotas legacy
}, [
    'deprecated' => true,
    'sunset' => 'Sat, 31 Dec 2025 23:59:59 GMT' // RFC 7234
]);
```

**Classificação:** 100% CORE-AEGIS

---

### 3. public.php (108 linhas)

**Função:** Rotas públicas (login membros, home, páginas exemplo)

**Seções:**

1. **HOME (Lógica Complexa)** (linhas 8-39)
   ```php
   Router::get('/', function() {
       // 1. Tenta carregar /frontend/pages/home.php (prioridade)
       // 2. Sem home pública:
       //    - Sistema ESTÁTICO ou SEM MEMBERS → mensagem padrão + link /admin
       //    - Sistema COM MEMBERS → redirect /home (autenticado) ou /login
   });
   ```

2. **MEMBER AUTHENTICATION** (linhas 41-58)
   - GET `/login` - Exibir formulário (MemberAuthController)
   - POST `/login` - Processar login
   - GET `/logout` - Logout

3. **MEMBER HOME** (linhas 60-67)
   - GET `/home` - Área de membros autenticados (PageController)

4. **PÁGINAS DE EXEMPLO** (linhas 69-91)
   - `/exemplo-filtros` - Componente Filtros isolado
   - `/exemplo-filtros-completo` - Filtros + Cards + Tabelas
   - `/exemplo-integracao` - Integração completa
   - `/exemplo-multiplos-grupos` - Múltiplos grupos de filtros
   - `/exemplo-tabelas` - Componente Tabelas isolado

5. **DOWNLOADS - Relatórios** (linhas 93-107)
   - GET `/downloads` - Listagem relatórios disponíveis
   - GET `/downloads/generate/:id` - Gerar e baixar Excel (DownloadController)

**Classificação:** 100% CORE-AEGIS

---

### 4. catchall.php (82 linhas)

**Função:** Rotas genéricas (SEMPRE carregadas por último)

**IMPORTANTE:** Ordem de carregamento crítica para evitar conflitos

**Seções:**

1. **GENERIC PAGE ROUTE** (linhas 8-17)
   ```php
   Router::get('/:slug', function($slug) {
       $controller = new PageController();
       $controller->show($slug); // Verifica permissões, carrega page_blocks
   });
   ```
   - **CRÍTICO:** Deve estar no final (não intercepta /admin, /login, etc.)
   - Protegido por sistema de permissões (PageController verifica)

2. **301 REDIRECTS - Migração Blog** (linhas 19-55)
   - **Formato antigo:** `/:categoria/:post`
   - **Novo:** `/blog/:categoria/:post`
   - Verifica se módulo `blog` está instalado
   - Redirect permanente 301 (SEO-friendly)

   Rotas:
   - GET `/:categoria_slug/:post_slug` → `/blog/:categoria/:post` (301)
   - GET `/:categoria_slug/pagina/:page` → `/blog/:categoria/pagina/:page` (301)

3. **ROTA GENÉRICA DO BLOG** (linhas 57-81)
   ```php
   Router::get('/blog/:categoria_slug/:post_slug', function(...) {
       // 1. Verifica se blog está instalado
       // 2. Carrega checkModuleAccess('blog')
       // 3. PublicBlogController->postByCategory()
   });
   ```
   - Prefixo `/blog/` garante zero conflito
   - Proteção por módulo (checkModuleAccess)

**Classificação:** 90% CORE / 10% APP-SPECIFIC (rotas blog hardcoded)

---

## 🎯 PADRÕES IDENTIFICADOS

### 1. Separação de Contextos
- **admin.php:** Tudo admin (Auth::require())
- **api.php:** API REST versionada (JWT)
- **public.php:** Área pública + membros
- **catchall.php:** Genéricos (última prioridade)

### 2. Segurança
- **Admin:** Auth::require() em TODA rota
- **API:** ApiRouter::auth() para rotas protegidas
- **Public:** MemberAuth::require() quando necessário
- **Modules:** checkModuleAccess($pageSlug)

### 3. RESTful Pattern
```php
// CRUD completo
GET    /recurso            → index()
GET    /recurso/create     → create()
POST   /recurso            → store()
GET    /recurso/:id/edit   → edit($id)
POST   /recurso/:id        → update($id)
POST   /recurso/:id/delete → destroy($id)
```

### 4. Route Priority
**Ordem CRÍTICA:**
1. Rotas específicas (/admin/menu/order)
2. Rotas com parâmetros (/admin/menu/:id)
3. Catch-all (/:slug)

### 5. API Versionamento
- Prefixo automático: `/api/v1`, `/api/v2`
- Deprecation headers (RFC 7234)
- HATEOAS (`/api/v1/versions`)

### 6. Module Routes
- Carregados dinamicamente (ModuleManager::loadRoutes())
- Prefixo obrigatório (exceto blog)
- checkModuleAccess() pattern

---

## 📊 ESTATÍSTICAS

**admin.php:** 630 linhas, ~150 rotas (15 controllers)
**api.php:** 113 linhas, ~10 rotas (1 controller + exemplos)
**public.php:** 108 linhas, ~15 rotas (4 controllers)
**catchall.php:** 82 linhas, ~5 rotas (genéricas)

**Total:** 933 linhas, ~180 rotas

---

## 🔧 OPORTUNIDADES

### Pontos Fortes
✅ Separação clara de responsabilidades
✅ RESTful consistente
✅ Segurança aplicada corretamente
✅ Versionamento API RFC-compliant
✅ Zero conflito entre módulos
✅ 301 redirects para SEO

### Melhorias Identificadas

1. **Route caching:**
   - Gerar cache de rotas (Laravel-style)
   - Acelerar matching

2. **Resource routes:**
   - Helper para CRUD automático:
     ```php
     Router::resource('/admin/pages', PagesController::class);
     ```

3. **Middleware sintax:**
   - Simplificar Auth::require() repetitivo:
     ```php
     Router::group(['middleware' => 'auth'], function() {
         // rotas admin
     });
     ```

4. **Route naming:**
   - Nomear rotas para url() helper:
     ```php
     Router::get('/admin/pages', ...)->name('admin.pages.index');
     url('admin.pages.index'); // gera /admin/pages
     ```

---

## 📝 NOTA FINAL: 9.5/10

Sistema de rotas **extremamente bem organizado**, com separação clara, segurança rigorosa e zero conflitos entre módulos.
