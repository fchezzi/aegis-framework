# ⚡ Quick Reference - AEGIS Core Classes

> **Quando usar:** Consulta rápida de APIs das classes core mais usadas

---

## 🗄️ DB (Database - Mais usado)

```php
// Connection (sempre usar)
$db = DB::connect();  // REGRAS.md #1

// Select
$users = $db->select('users', ['active' => 1]);  // WHERE active = 1
$user = $db->select('users', ['email' => $email], 1);  // LIMIT 1

// Insert (sempre usar UUID)
$id = Core::generateUUID();
$db->insert('users', [
    'id' => $id,
    'email' => $email,
    'name' => $name
]);

// Update
$db->update('users',
    ['name' => 'Novo Nome'],  // SET
    ['id' => $userId]         // WHERE
);

// Delete
$db->delete('users', ['id' => $userId]);

// Raw query (com prepared statements - REGRAS.md #4)
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$result = $stmt->execute([$email]);
```

---

## 🔐 Auth (Admin Authentication)

```php
// Require auth (OBRIGATÓRIO em controllers admin - REGRAS.md #7)
Auth::require();  // PRIMEIRA LINHA sempre

// Check
if (Auth::check()) {
    $admin = Auth::user();  // ['id', 'email', 'name']
}

// Login
$success = Auth::login($email, $password, $db);  // Rate-limited (5/5min)

// Logout
Auth::logout();
```

---

## 👥 MemberAuth (Frontend Users)

```php
// Require (páginas protegidas frontend)
MemberAuth::require();  // Redireciona para /login

// Check
if (MemberAuth::check()) {
    $member = MemberAuth::member();  // ['id', 'email', 'name', 'group_id']
}

// Login
$success = MemberAuth::login($email, $password);  // Rate-limited

// Get allowed pages
$pages = MemberAuth::getPaginasPermitidas($memberId);
```

**Ver mais:** `permissions.md`

---

## 🔑 Permission (Access Control)

```php
// Check access
if (Permission::canAccess($memberId, $pageId)) {
    // Allowed
}

// Get accessible pages
$pages = Permission::getAccessiblePages($memberId);

// Grant permissions
Permission::grantIndividual($memberId, $pageId);
Permission::grantGroup($groupId, $pageId);

// Remove
Permission::removeIndividual($memberId, $pageId);
Permission::removeGroup($groupId, $pageId);
```

**Ordem de precedência:** `is_public = 1` > individual > grupo > bloquear

**Ver mais:** `permissions.md`

---

## 🛡️ Security (CSRF + Sanitize)

```php
// Generate CSRF token (em forms)
<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

// Validate CSRF (em controllers)
Security::validateCSRF($_POST['csrf_token']);  // REGRAS.md #5

// Sanitize input
$nome = Security::sanitize($_POST['nome']);  // REGRAS.md #6

// Password hash
$hash = Security::hashPassword($password);  // bcrypt

// Verify password
if (Security::verifyPassword($password, $hash)) { }
```

---

## 📤 Upload (File handling)

```php
// Upload image
$result = Upload::image($_FILES['foto'], 'uploads/fotos');

if ($result['success']) {
    $path = $result['path'];  // Não é 'file', é 'path'
} else {
    $erro = $result['message'];  // Não é 'error', é 'message'
}

// Validações automáticas (REGRAS.md #9):
// - Tipo/extensão permitida
// - Tamanho máximo
// - Mime type real
```

**Ver mais:** `file-upload-template.md`

---

## 🎯 Core (Utilities)

```php
// UUID (OBRIGATÓRIO para IDs - REGRAS.md #3)
$id = Core::generateUUID();  // RFC 4122 v4

// URLs
Core::redirect('/admin/pages');
$url = Core::url('/admin');

// JSON response
Core::json(['success' => true, 'data' => $data], 200);

// Error pages
Core::error(404, 'Página não encontrada');

// Flash messages
Core::success('Salvo com sucesso!', '/admin/pages');

// Environment
if (Core::isDev()) { }
if (Core::isProduction()) { }

// Members system
if (Core::membersEnabled()) { }  // ENABLE_MEMBERS
```

---

## 💾 Cache (File-based)

```php
// Set (TTL em segundos)
Cache::set('key', $data, 3600);  // 1 hora

// Get
$data = Cache::get('key');  // null se não existir

// Check
if (Cache::has('key')) { }

// Delete
Cache::delete('key');

// Clear all
Cache::clear();

// Clear expired only
Cache::clearExpired();
```

---

## 🚦 RateLimit (Brute Force Protection)

```php
// Check (padrão: 5 tentativas em 5min)
$key = 'login:' . $email;

if (!RateLimit::check($key, 5, 300)) {
    // Bloqueado
    $seconds = RateLimit::getBlockedTime($key);
    die("Aguarde {$seconds}s");
}

// Reset (após sucesso)
RateLimit::reset($key);

// Block manually
RateLimit::block($key, 600);  // 10min

// Check if blocked
if (RateLimit::isBlocked($key)) { }
```

---

## 🛤️ Router (Static methods - REGRAS.md #8)

```php
// Register routes
Router::get('/blog', function() { ... });
Router::post('/contact', function() { ... });

// Com parâmetros
Router::get('/blog/:slug', function($params) {
    $slug = $params['slug'];
});

// CRÍTICO: Ordem importa (REGRAS.md #11)
Router::get('/blog', ...);           // 1. Específico
Router::get('/blog/:slug', ...);     // 2. Com param
Router::get('/:slug', ...);          // 3. Genérico ÚLTIMO

// Run router
Router::run();

// Generate URL
$url = Router::url('/admin/pages');
```

**Ver mais:** `routing.md`

---

## 📦 ModuleManager (Module System)

```php
// Get installed modules
$modules = ModuleManager::getInstalled();  // Cached

// Check if installed
if (ModuleManager::isInstalled('blog')) { }

// Install/Uninstall
$result = ModuleManager::install('blog');
$result = ModuleManager::uninstall('blog', true);

// Load all module routes (em routes.php)
ModuleManager::loadAllRoutes();

// Get menu items from modules
$menuItems = ModuleManager::getMenuItems();
```

**Fonte de verdade:** `module.json` com `"public": true/false` (REGRAS.md #10)

**Ver mais:** `module-patterns.md`

---

## 🍔 MenuBuilder (Dynamic Menus)

```php
// Render complete menu (HTML)
echo MenuBuilder::render($memberId = null);

// Get filtered menu array (sem HTML)
$items = MenuBuilder::getFilteredMenu($memberId = null);
```

**Verifica automaticamente:**
1. `is_public = 1` em pages
2. `"public": true` em modules
3. Permissões de member (se logado)

---

## 🏗️ PageBuilder (Visual Page Builder)

```php
// Render page blocks
echo PageBuilder::render($pageSlug, $skipCache = false);

// Check if has blocks
if (PageBuilder::hasBlocks($pageSlug)) { }

// Count cards
$total = PageBuilder::countCards($pageSlug);

// Clear cache
PageBuilder::clearCache($pageSlug);
PageBuilder::clearCache();  // All pages

// Stats
$stats = PageBuilder::getCacheStats();
```

---

## 🌐 Env (Environment - Não usar)

**⚠️ AEGIS NÃO usa .env - usa `_config.php`** (REGRAS.md)

Se precisar de Env:
```php
Env::load();
$value = Env::get('DB_HOST', 'localhost');
```

Mas prefira `_config.php`:
```php
define('DB_HOST', 'localhost');
```

---

## 📚 Quando Ler Outros Docs

| Preciso de... | Ler... |
|---------------|--------|
| Regras críticas, segurança | `REGRAS.md` |
| MemberAuth, grupos, permissões | `permissions.md` |
| Criar módulo | `module-patterns.md` |
| Upload de arquivos | `file-upload-template.md` |
| Rotas, 404 | `routing.md` |
| Erro aconteceu | `ERRO-PROTOCOL.md` + `known-issues.md` |

---

## 🎯 Top 5 Classes Mais Usadas

1. **DB** - Toda interação com banco
2. **Auth** - Todo controller admin
3. **Core** - UUIDs, redirects, URLs
4. **Security** - CSRF, sanitize
5. **Router** - Todas as rotas

**Memorizar:** `DB::connect()`, `Auth::require()`, `Core::generateUUID()`, `Security::validateCSRF()`, `Router::get()`

---

**Versão:** 4.0.0
**Data:** 2026-02-14
**Changelog:** Removido workflows desatualizados, focado apenas em top 12 classes core essenciais, reduzido de 1.463 → 350 linhas
