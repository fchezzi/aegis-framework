# AEGIS Framework - Padrões de Segurança

**Versão:** 1.0
**Data:** 2026-01-18
**Framework:** AEGIS v14.0.6

---

## 📋 ÍNDICE

1. [Autenticação](#autenticação)
2. [Autorização](#autorização)
3. [CSRF Protection](#csrf-protection)
4. [SQL Injection Prevention](#sql-injection-prevention)
5. [Upload Security](#upload-security)
6. [Rate Limiting](#rate-limiting)
7. [Session Management](#session-management)
8. [Password Hashing](#password-hashing)
9. [Input Validation](#input-validation)
10. [Error Handling](#error-handling)

---

## 🔐 AUTENTICAÇÃO

### Auth vs MemberAuth

**Auth.php** - Administradores
```php
// Proteger rota admin
Router::get('/admin/users', function() {
    Auth::require(); // ← OBRIGATÓRIO em rotas
    // ou no controller
});

// Controller
class AdminController extends BaseController {
    public function index() {
        Auth::require(); // ← PADRÃO AEGIS (autenticação no controller)
        // ...
    }
}
```

**MemberAuth.php** - Usuários do site
```php
// Proteger página de membros
Router::get('/profile', function() {
    MemberAuth::require(); // ← OBRIGATÓRIO
    // ou no controller
});

// Verificar permissão específica
if (MemberAuth::hasPermission('page_slug')) {
    // Permitir acesso
}
```

### Padrão de Autenticação

**✅ CORRETO (padrão AEGIS):**
```php
// Autenticação NO CONTROLLER
class RecursoController {
    public function index() {
        Auth::require(); // ← Aqui
        $data = DB::select(...);
        require 'views/index.php';
    }
}
```

**❌ ERRADO (duplicação):**
```php
// NÃO fazer autenticação na rota E no controller
Router::get('/admin/recurso', function() {
    Auth::require(); // ← Duplicado
    $controller = new RecursoController();
    $controller->index(); // ← Controller também tem
});
```

**Por quê?**
- DRY principle (não duplicar)
- Flexibilidade (controller pode ter lógica condicional)
- Testabilidade (testar controller isoladamente)

---

## 🛡️ AUTORIZAÇÃO

### Permissões (Members)

**Sistema de 3 níveis:**

1. **Permissões de grupo:**
```php
// Grupo tem acesso à página?
$hasAccess = Permission::groupHasPageAccess($groupId, $pageSlug);
```

2. **Permissões individuais:**
```php
// Member tem acesso direto?
$hasAccess = Permission::memberHasPageAccess($memberId, $pageSlug);
```

3. **Páginas públicas:**
```php
// Verificar no banco
SELECT is_public FROM pages WHERE slug = ?;
// is_public = 1 → Acesso liberado
```

### Ordem de verificação

```php
// 1. Página pública?
if ($page['is_public'] == 1) return true;

// 2. Member tem permissão individual?
if (Permission::memberHasPageAccess($memberId, $slug)) return true;

// 3. Grupos do member têm permissão?
$groups = MemberGroups::getGroupsByMember($memberId);
foreach ($groups as $group) {
    if (Permission::groupHasPageAccess($group['id'], $slug)) return true;
}

// 4. Negar acesso
return false;
```

---

## 🔒 CSRF PROTECTION

### Gerar Token

**Em TODOS os formulários:**
```php
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
    <!-- campos -->
</form>
```

### Validar Token

**Automático em rotas POST:**
```php
// Middleware valida automaticamente
Router::post('/admin/save', function() {
    // CSRF já foi validado aqui
});
```

**Manual (se necessário):**
```php
Security::validateCSRF(); // Lança exception se inválido
```

### Multi-source Validation

**Token aceito em 3 locais:**
1. `$_POST['csrf_token']`
2. `$_SERVER['HTTP_X_CSRF_TOKEN']` (AJAX)
3. `$_SERVER['HTTP_X_XSRF_TOKEN']` (Angular/frameworks)

---

## 💉 SQL INJECTION PREVENTION

### SEMPRE usar Prepared Statements

**✅ CORRETO:**
```php
// Via DB class
$users = DB::select('users', ['email' => $email]);

// Via QueryBuilder
$users = QueryBuilder::table('users')
    ->where('email', '=', $email)
    ->get();

// Query customizada
$results = DB::query('SELECT * FROM users WHERE email = ?', [$email]);
```

**❌ ERRADO:**
```php
// NUNCA concatenar SQL
$sql = "SELECT * FROM users WHERE email = '" . $email . "'"; // ← VULNERÁVEL
```

### Sanitização de Table Names

**Automático no DB class:**
```php
// MySQLAdapter.php linha 74
$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
```

**Manual (se necessário):**
```php
// Whitelist de tabelas
$allowedTables = ['users', 'members', 'pages'];
if (!in_array($table, $allowedTables)) {
    throw new Exception('Tabela inválida');
}
```

---

## 📤 UPLOAD SECURITY

### 5 Camadas de Proteção

**1. PHP Execution OFF**
```apache
# storage/uploads/.htaccess
php_flag engine off
```

**2. Deny All (padrão)**
```apache
Order Deny,Allow
Deny from all
```

**3. Whitelist de Extensões**
```apache
<FilesMatch "\.(jpg|jpeg|png|gif|webp|pdf)$">
    Allow from all
</FilesMatch>
```

**4. Bloqueio Dupla Extensão**
```apache
<FilesMatch "\.(php|phtml|exe)\.">
    Deny from all
</FilesMatch>
```

**5. MIME Validation**
```php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $tmpPath);

if (!in_array($mime, $allowedMimes)) {
    throw new Exception('MIME inválido');
}
```

### Naming Anti-collision

**Pattern:** `{hash}_{timestamp}.{ext}`
```php
$filename = uniqid('', true) . '_' . time() . '.' . $ext;
```

---

## ⏱️ RATE LIMITING

### Login Protection

**Automático em Auth::login() e MemberAuth::login():**
```php
// 60 tentativas por minuto (por IP)
RateLimiter::check('auth_login_' . $ip, 60, 60);
```

### Custom Rate Limits

**Para APIs ou ações sensíveis:**
```php
// 100 requisições por minuto
RateLimiter::check('api-endpoint', 100, 60);

// 10 requisições por hora
RateLimiter::check('password-reset', 10, 3600);
```

### Drivers

**Session (padrão):**
```php
$_SESSION['rate_limit'][$key] = [
    'count' => 1,
    'reset_at' => time() + $window
];
```

**File (se session desabilitada):**
```php
// storage/cache/rate-limit-{key}.cache
```

**APCu (se disponível):**
```php
apcu_inc('rate_limit_' . $key);
```

---

## 🔑 SESSION MANAGEMENT

### Regeneração Obrigatória

**Após login (previne session fixation):**
```php
// Auth.php e MemberAuth.php
session_regenerate_id(true);
```

### Session Security

**Configurações recomendadas (php.ini ou runtime):**
```php
ini_set('session.cookie_httponly', 1); // Bloqueia JS
ini_set('session.cookie_secure', 1);   // HTTPS only
ini_set('session.use_strict_mode', 1); // Strict IDs
```

### Audit Log

**Tracking de sessões:**
```php
Logger::info('AUDIT: Admin login', [
    'type' => 'audit',
    'user_id' => $userId,
    'ip' => $_SERVER['REMOTE_ADDR'],
    'email' => $email
]);
```

---

## 🔐 PASSWORD HASHING

### Bcrypt (current)

**Hash:**
```php
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
```

**Verify:**
```php
if (password_verify($inputPassword, $storedHash)) {
    // Login OK
}
```

### Auto-rehash

**Se cost aumentar no futuro:**
```php
// Auth.php
if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 14])) {
    $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 14]);
    DB::update('users', ['password' => $newHash], ['id' => $userId]);
}
```

---

## ✅ INPUT VALIDATION

### Validator Class

**27 regras disponíveis:**
```php
$validator = new Validator($_POST, [
    'email' => 'required|email|max:100',
    'password' => 'required|min:8|confirmed',
    'age' => 'numeric|between:18,100'
]);

if (!$validator->validate()) {
    $errors = $validator->getErrors();
}
```

### Sanitização Automática

**Request class:**
```php
// Auto-sanitize (strip_tags + trim)
$name = Request::input('name'); // Sanitizado

// Raw (sem sanitização)
$html = Request::raw('content'); // Não sanitizado
```

### Output Escaping

**SEMPRE em views:**
```php
// ✅ CORRETO
<p><?= htmlspecialchars($user['name']) ?></p>

// ❌ ERRADO (XSS)
<p><?= $user['name'] ?></p>
```

---

## 🚨 ERROR HANDLING

### Logs Estruturados

**Formato JSON:**
```
[YYYY-MM-DD HH:MM:SS] [LEVEL] MESSAGE | {"context":"json"}
```

**Exemplo:**
```php
Logger::info('AUDIT: Admin login', [
    'type' => 'audit',
    'user_id' => $userId,
    'ip' => '::1'
]);
```

**Resultado:**
```
[2026-01-18 10:00:00] [INFO] AUDIT: Admin login | {"type":"audit","user_id":"uuid","ip":"::1"}
```

### Níveis de Log

- **INFO:** Ações normais (login, logout)
- **WARNING:** Alertas (CSRF fail, rate limit)
- **ERROR:** Erros críticos (exceptions, DB errors)
- **DEBUG:** Debug (apenas dev)

### Dados Sensíveis

**❌ NUNCA logar:**
- Senhas (plain text)
- CSRF tokens
- Session IDs
- API keys

**✅ OK logar:**
- User IDs (UUID)
- IPs
- Emails
- Ações realizadas

---

## 📊 CHECKLIST DE SEGURANÇA

### Para Novos Recursos

**Controllers:**
- [ ] Auth::require() ou MemberAuth::require()?
- [ ] Inputs validados (Validator)?
- [ ] Outputs escapados (htmlspecialchars)?

**Forms:**
- [ ] CSRF token presente?
- [ ] Method POST para mutations?
- [ ] Validação server-side?

**Database:**
- [ ] Prepared statements?
- [ ] Zero concatenação de SQL?
- [ ] Table names sanitizados?

**Upload:**
- [ ] Whitelist de extensões?
- [ ] MIME validation?
- [ ] Unique filename?
- [ ] Pasta protegida (.htaccess)?

**APIs:**
- [ ] Rate limiting configurado?
- [ ] Autenticação (JWT)?
- [ ] Input validation?
- [ ] CORS configurado?

---

## 🎯 SCORE DE SEGURANÇA ATUAL

| Categoria | Score | Status |
|-----------|-------|--------|
| Autenticação | 10/10 | ✅ |
| Autorização | 10/10 | ✅ |
| CSRF | 10/10 | ✅ |
| SQL Injection | 10/10 | ✅ |
| Upload | 9/10 | ✅ |
| Rate Limiting | 8/10 | ✅ |
| Session | 9/10 | ✅ |
| Password | 9/10 | ✅ |
| Validation | 10/10 | ✅ |
| Error Handling | 10/10 | ✅ |

**Média:** 9.5/10

---

**Próxima revisão:** 2026-07-18
