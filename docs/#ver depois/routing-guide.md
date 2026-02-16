# Routing no Aegis - Guia Completo de Procedimento

**Criticidade:** 🔴 CRÍTICA
**Importância:** Sistema de roteamento determina segurança, performance e replicabilidade
**Última Atualização:** 2026-02-12

---

## 📋 Índice

1. [Arquitetura](#arquitetura)
2. [Ordem de Carregamento](#ordem-de-carregamento)
3. [Padrões de Routing](#padrões-de-routing)
4. [Segurança](#segurança)
5. [Adicionando Novas Rotas](#adicionando-novas-rotas)
6. [Testes e Validação](#testes-e-validação)
7. [Troubleshooting](#troubleshooting)
8. [Checklist](#checklist)

---

## Arquitetura

### Fluxo de Requisição

```
Requisição HTTP
    ↓
.htaccess (rewrite para index.php)
    ↓
index.php
    ├─ Configura segurança (session)
    ├─ Carrega _config.php
    ├─ Carrega autoloader
    ├─ Carrega helpers
    └─ Chama routes.php
        ↓
routes.php (orquestrador)
    ├─ Carrega routes/api.php
    ├─ Carrega routes/public.php
    ├─ Carrega routes/admin.php
    ├─ ModuleManager::loadAllRoutes()
    └─ Carrega routes/catchall.php
        ↓
Router::run() processa requisição
    ├─ Pega REQUEST_METHOD e REQUEST_URI
    ├─ Itera por TODAS as rotas registradas
    ├─ Converte path em regex (ex: /:id → /([^/]+))
    ├─ Testa regex contra URI
    ├─ Executa middlewares (ex: Auth::require())
    └─ Executa handler (closure ou Controller@method)
        ↓
Response (HTML, JSON, redirect, 404, 500)
```

---

## Ordem de Carregamento

### ⚠️ ORDEM É CRÍTICA - NÃO MUDAR

```php
// Em routes.php (raiz)

1. routes/api.php          // APIs (mais específicas)
2. routes/public.php       // Páginas públicas (genéricas)
3. routes/admin.php        // Admin (mais específicas)
4. ModuleManager::loadAllRoutes()  // Módulos dinâmicos
5. routes/catchall.php     // Fallback genérico (ÚLTIMO)
```

### Por Quê Essa Ordem?

```
✅ routes/api.php PRIMEIRO
   - APIs têm paths específicos (/api/table-data.php)
   - Precisam ser testadas antes de genéricas

✅ routes/public.php SEGUNDO
   - Contém home (/), login (/login), etc
   - Genéricas o suficiente

✅ routes/admin.php TERCEIRO
   - Paths específicos (/admin/reports/:id/edit)
   - Precisam ser testadas antes de catchall

✅ ModuleManager::loadAllRoutes() QUARTO
   - Módulos registram suas próprias rotas
   - Precisam vir após os core routes

❌ routes/catchall.php ÚLTIMO
   - Pega tudo que não casou (/:slug)
   - SE vier primeiro, intercepta /admin, /api, etc
   - NUNCA mexer na ordem
```

---

## Padrões de Routing

### Padrão 1: Closure (Inline)

```php
Router::get('/admin/reports', function() {
    Auth::require();  // ← OBRIGATÓRIO em rotas admin
    $controller = new ReportTemplateController();
    $controller->index();
});
```

**Uso:** Rotas simples, lógica inline
**Risco:** Pode ficar muito grande (lógica complexa vai no Controller)

### Padrão 2: Controller String

```php
Router::get('/admin/reports', 'ReportTemplateController@index');
```

**Uso:** Rotas que só precisam chamar 1 método
**Problema:** Menos seguro (não pode adicionar Auth::require() inline)

### Padrão 3: Controller Instância

```php
Router::get('/admin/reports', function() {
    Auth::require();
    $controller = new ReportTemplateController();
    $controller->index();
});
```

**Uso:** PADRÃO RECOMENDADO (usado em 99% das rotas)
**Vantagem:** Seguro, legível, permite middleware inline

---

## Segurança

### ✅ REGRA 1: SEMPRE Auth::require() em Admin

```php
// ❌ ERRADO
Router::post('/admin/reports/store', function() {
    $controller = new ReportTemplateController();
    $controller->store();
});

// ✅ CORRETO
Router::post('/admin/reports/store', function() {
    Auth::require();  // ← PRIMEIRA LINHA
    $controller = new ReportTemplateController();
    $controller->store();
});
```

**Por quê?** Qualquer pessoa sem Auth::require() pode:
- Listar dados
- Criar registros
- Modificar dados
- Deletar dados

### ✅ REGRA 2: CSRF em Todos os POSTs

```php
// Em views:
<form method="POST" action="/admin/reports/store">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
    ...
</form>

// Em controllers:
public function store() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token'] ?? '');  // ← VALIDAR
    // lógica aqui
}
```

### ✅ REGRA 3: Validação de UUIDs

```php
// ❌ ERRADO
Router::get('/admin/reports/:id/edit', function($id) {
    Auth::require();
    $controller = new ReportTemplateController();
    $controller->edit($id);  // ← Sem validação
});

// ✅ CORRETO
public function edit($id) {
    Auth::require();
    if (!Security::isValidUUID($id)) {  // ← VALIDAR
        http_response_code(404);
        return;
    }
    // lógica aqui
}
```

### ✅ REGRA 4: Sanitização de Inputs

```php
// ❌ ERRADO
$nome = $_POST['nome'];  // SQL injection, XSS

// ✅ CORRETO
$nome = Security::sanitize($_POST['nome']);
```

### ✅ REGRA 5: File Exists Before Require

```php
// ❌ ERRADO
require_once ROOT_PATH . 'modules/blog/routes.php';  // Fatal error se não existe

// ✅ CORRETO
$blogRoutesPath = ROOT_PATH . 'modules/blog/routes.php';
if (!file_exists($blogRoutesPath)) {
    http_response_code(500);
    error_log("Blog routes not found: {$blogRoutesPath}");
    return;
}
require_once $blogRoutesPath;
```

---

## Adicionando Novas Rotas

### Cenário 1: Rota de Admin Simples

```php
// Em routes/admin.php

// Comentário descritivo
Router::get('/admin/meu-recurso', function() {
    Auth::require();
    $controller = new MeuResourceController();
    $controller->index();
});

Router::get('/admin/meu-recurso/create', function() {
    Auth::require();
    $controller = new MeuResourceController();
    $controller->create();
});

Router::post('/admin/meu-recurso', function() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token'] ?? '');
    $controller = new MeuResourceController();
    $controller->store();
});

Router::get('/admin/meu-recurso/:id/edit', function($id) {
    Auth::require();
    $controller = new MeuResourceController();
    $controller->edit($id);
});

Router::post('/admin/meu-recurso/:id', function($id) {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token'] ?? '');
    $controller = new MeuResourceController();
    $controller->update($id);
});

Router::post('/admin/meu-recurso/:id/delete', function($id) {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token'] ?? '');
    $controller = new MeuResourceController();
    $controller->destroy($id);
});
```

### Cenário 2: Rota de API Pública

```php
// Em routes/public.php

Router::get('/api/dados-publicos', function() {
    $db = DB::connect();
    $dados = $db->select('tabela', ['ativo' => 1]);

    header('Content-Type: application/json');
    echo json_encode($dados);
});
```

### Cenário 3: Rota com Parâmetros Múltiplos

```php
// Formato: /categoria/subcategoria/item/:id
Router::get('/:categoria/:subcategoria/:id', function($categoria, $subcategoria, $id) {
    // $categoria = 'blog'
    // $subcategoria = 'tech'
    // $id = '123'
});
```

### Cenário 4: Rota de Módulo

```php
// Em modules/meu-modulo/routes.php

Router::get('/meu-modulo/lista', function() {
    Auth::require();
    $controller = new MeuModuloController();
    $controller->index();
});
```

---

## Testes e Validação

### Teste 1: Validar Sintaxe PHP

```bash
php -l routes/admin.php
php -l routes/public.php
php -l routes/api.php
php -l routes/catchall.php
```

**Esperado:** `No syntax errors detected`

### Teste 2: Verificar Ordem de Carregamento

```bash
# Abrir em navegador
GET /admin/dashboard

# Ver em logs se carregamento foi na ordem certa
# (não há log visual, mas check sintaxe valida ordem)
```

### Teste 3: Testar Auth em Rotas Admin

```bash
# Sem estar logado
GET /admin/reports

# Esperado: Redireciona para /admin/login

# Depois de logar
GET /admin/reports

# Esperado: Mostra a página
```

### Teste 4: Testar Parâmetros

```bash
# Teste com ID válido
GET /admin/reports/123abc-456def/edit

# Teste com ID inválido
GET /admin/reports/INVALID_UUID/edit

# Esperado: 404 ou redirect
```

### Teste 5: File Exists Check

```bash
# Deletar arquivo de módulo
rm modules/blog/routes.php

# Acessar rota que depende dele
GET /blog/categoria/post

# Esperado: 500 com mensagem clara (não Fatal error)

# Restaurar arquivo
git checkout modules/blog/routes.php
```

---

## Troubleshooting

### Problema: 404 em Rota que Deveria Existir

```
Checklist:
1. ✅ Rota está em um dos 4 arquivos? (api, public, admin, catchall)
2. ✅ Path está correto? (sem typos)
3. ✅ Ordem de carregamento está correta?
4. ✅ Se tem parâmetro, está usando :nomeparam?
5. ✅ .htaccess está reescrevendo para index.php?
```

**Debug:**
```php
// Em Router::run() adicionar temporariamente
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("Total rotas: " . count(self::$routes));
```

### Problema: Rota Errada Está Sendo Acionada

```
Causa: Ordem de rotas está errada

Exemplo:
❌ ERRADO
Router::get('/:slug', ...);  // Muito genérica
Router::get('/admin/:id', ...);  // Específica mas vem depois

✅ CORRETO
Router::get('/admin/:id', ...);  // Específica PRIMEIRO
Router::get('/:slug', ...);  // Genérica ÚLTIMO
```

### Problema: Auth::require() Retorna 401

```
Possíveis causas:
1. Sessão não iniciou (session_status() === PHP_SESSION_NONE)
2. $_SESSION['user_id'] não existe
3. Cookie de sessão foi deletado

Debug:
var_dump($_SESSION);
var_dump(session_status());
```

---

## Checklist: Adicionando Nova Rota

- [ ] Rota está no arquivo CORRETO (api, public, admin, catchall)?
- [ ] Auth::require() adicionado SE é rota admin?
- [ ] Path não conflita com rotas existentes?
- [ ] Se tem parâmetro, está validando UUID?
- [ ] Se POST, está validando CSRF?
- [ ] Se requires arquivo externo, tem file_exists()?
- [ ] Se calls controller, classe existe?
- [ ] Se calls controller, método existe?
- [ ] Comentário descritivo adicionado?
- [ ] Testou manualmente no navegador?
- [ ] Rodou `php -l` para validar sintaxe?
- [ ] Documentou em CHANGELOG?

---

## Estrutura de Arquivo Recomendada

```php
<?php
/**
 * [Nome da Seção de Rotas]
 * [Descrição]
 */

// ================================================
// SEÇÃO 1: ROTAS GET
// ================================================

Router::get('/path', function() {
    Auth::require();  // Se necessário
    // lógica
});

// ================================================
// SEÇÃO 2: ROTAS POST
// ================================================

Router::post('/path', function() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token'] ?? '');
    // lógica
});
```

---

## Performance

### Impacto de Rotas em Performance

| Métrica | Impacto |
|---------|---------|
| Quantidade de rotas | Negligenciável (regex matching é rápido) |
| Ordem de rotas | Relevante (específicas ANTES de genéricas) |
| Middlewares | ~1ms cada (Auth::require() é sessão check) |
| Require externo | ~2ms cada |

**Recomendação:** Não se preocupar com quantidade, focar em ordem

---

## Segurança: Auditoria de Rotas Existentes

### Script para Auditar

```bash
# Contar rotas com e sem auth
grep "Router::" routes/admin.php | wc -l
grep "Auth::require()" routes/admin.php | wc -l

# Encontrar rotas sem auth
grep -A 3 "Router::" routes/admin.php | grep -v "Auth::require()" | grep "Router::"
```

---

## Referência Rápida

### Sintaxe

```php
// GET
Router::get('/path', function() { ... });

// POST
Router::post('/path', function() { ... });

// PUT
Router::put('/path', function() { ... });

// DELETE
Router::delete('/path', function() { ... });
```

### Middleware

```php
// Registrar middleware
Router::middleware('auth', function($next) {
    Auth::require();
    return $next();
});

// Usar middleware
Router::get('/admin/users', function() { ... })->middleware('auth');

// Grupo com middleware
Router::group(['middleware' => 'auth', 'prefix' => '/admin'], function() {
    Router::get('/users', function() { ... });
});
```

### Parâmetros

```php
// Parâmetro obrigatório
Router::get('/user/:id', function($id) { ... });

// Múltiplos parâmetros
Router::get('/blog/:categoria/:post', function($cat, $post) { ... });

// URL base
Router::url('/admin/reports');  // Retorna /admin/reports ou /subfolder/admin/reports
```

---

## Referências

- core/Router.php - Implementação do router
- routes.php - Orquestrador
- .htaccess - URL rewrite
- docs/AUDITORIA-ROUTES-COMPLETA-2026-02-12.md - Análise completa
- docs/CHANGELOG-ROUTING-FIXES-2026-02-12.md - Histórico de fixes

---

**Última auditoria:** 2026-02-12
**Problemas encontrados:** 11 (todos corrigidos)
**Status:** ✅ SEGURO E DOCUMENTADO

🤖 Generated with [Claude Code](https://claude.com/claude-code)
