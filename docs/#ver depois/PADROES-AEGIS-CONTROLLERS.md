# PADRÕES AEGIS - Controllers Admin (Análise Real)

**Data:** 2026-02-12
**Versão:** 1.0.0
**Análise:** 10 controllers (AdminController, AuthController, ComponentsController, ContentController, DataSourceController, GroupController, IncludesController, MemberController, MenuController, ModulesController)

---

## 🎯 PADRÃO UNIVERSAL: CRUD ADMIN

### Estrutura Exata (Não é genérico - É REAL)

```php
<?php
class [ResourceName]Controller {

    /**
     * 1️⃣ INDEX - Listar
     */
    public function index() {
        Auth::require();                    // ← LINHA 1 SEMPRE
        
        if (!Core::membersEnabled()) {      // ← Se aplicável
            Core::redirect('/admin');
        }

        $db = DB::connect();
        $items = $db->select('tabela', [], 'campo DESC');

        require __DIR__ . '/../views/pasta/index.php';
    }

    /**
     * 2️⃣ CREATE - Formulário novo
     */
    public function create() {
        Auth::require();
        
        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        require __DIR__ . '/../views/pasta/create.php';
    }

    /**
     * 3️⃣ STORE - Processar formulário (POST)
     */
    public function store() {
        Auth::require();
        
        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            // ✅ VALIDAR CSRF PRIMEIRO
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            // ✅ SANITIZAR INPUTS
            $campo1 = Security::sanitize($_POST['campo1'] ?? '');
            $campo2 = Security::sanitize($_POST['campo2'] ?? '');

            // ✅ VALIDAÇÕES
            if (empty($campo1)) {
                throw new Exception('Campo é obrigatório');
            }

            $db = DB::connect();

            // ✅ VERIFICAR DUPLICATAS (se aplicável)
            $existing = $db->select('tabela', ['campo_unico' => $campo1]);
            if (!empty($existing)) {
                throw new Exception('Já existe com este valor');
            }

            // ✅ GERAR UUID
            $id = Security::generateUUID();

            // ✅ INSERIR
            $db->insert('tabela', [
                'id' => $id,
                'campo1' => $campo1,
                'campo2' => $campo2
            ]);

            $_SESSION['success'] = 'Criado com sucesso!';
            Core::redirect('/admin/recurso');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/recurso/create');
        }
    }

    /**
     * 4️⃣ EDIT - Formulário editar
     */
    public function edit($id) {
        Auth::require();
        
        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();
        $items = $db->select('tabela', ['id' => $id]);

        if (empty($items)) {
            $_SESSION['error'] = 'Recurso não encontrado';
            Core::redirect('/admin/recurso');
            return;
        }

        $item = $items[0];
        require __DIR__ . '/../views/pasta/edit.php';
    }

    /**
     * 5️⃣ UPDATE - Processar atualização (POST)
     */
    public function update($id) {
        Auth::require();
        
        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $campo1 = Security::sanitize($_POST['campo1'] ?? '');
            $campo2 = Security::sanitize($_POST['campo2'] ?? '');

            if (empty($campo1)) {
                throw new Exception('Campo é obrigatório');
            }

            $db = DB::connect();

            // ✅ VERIFICAR SE EXISTE
            $existing = $db->select('tabela', ['id' => $id]);
            if (empty($existing)) {
                throw new Exception('Recurso não encontrado');
            }

            // ✅ VERIFICAR DUPLICATAS (exceto o próprio)
            $duplicata = $db->query(
                "SELECT id FROM tabela WHERE campo_unico = ? AND id != ?",
                [$campo1, $id]
            );
            if (!empty($duplicata)) {
                throw new Exception('Já existe com este valor');
            }

            // ✅ ATUALIZAR
            $db->update('tabela', [
                'campo1' => $campo1,
                'campo2' => $campo2
            ], ['id' => $id]);

            $_SESSION['success'] = 'Atualizado com sucesso!';
            Core::redirect('/admin/recurso');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/recurso/edit/' . $id);
        }
    }

    /**
     * 6️⃣ DESTROY - Deletar (POST)
     */
    public function destroy($id) {
        Auth::require();
        
        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $db = DB::connect();

            // ✅ VERIFICAR SE EXISTE
            $item = $db->select('tabela', ['id' => $id]);
            if (empty($item)) {
                throw new Exception('Recurso não encontrado');
            }

            // ✅ PROTEÇÕES ESPECÍFICAS (se houver)
            // Ex: Não deletar o único admin ativo
            // Ex: Verificar cascata

            // ✅ DELETAR
            $db->delete('tabela', ['id' => $id]);

            $_SESSION['success'] = 'Deletado com sucesso!';
            Core::redirect('/admin/recurso');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/recurso');
        }
    }
}
```

---

## 📋 CHECKLIST POR MÉTODO

### `index()`
- [ ] `Auth::require()` na linha 1
- [ ] `if (!Core::membersEnabled()) redirect()`
- [ ] `$db = DB::connect()`
- [ ] `$db->select('tabela', [], 'order')`
- [ ] `require` a view (nunca `echo`)

### `create()`
- [ ] `Auth::require()`
- [ ] `if (!Core::membersEnabled()) redirect()`
- [ ] `require` a view com CSRF token

### `store()` / `update()`
- [ ] `Auth::require()`
- [ ] `if (!Core::membersEnabled()) redirect()`
- [ ] `Security::validateCSRF()` PRIMEIRA coisa no try
- [ ] `Security::sanitize()` TODOS inputs
- [ ] `throw new Exception()` para erros (não `$_SESSION` direto)
- [ ] Try/catch com `$_SESSION['error']` e `redirect()`
- [ ] `Security::generateUUID()` para IDs
- [ ] `Core::redirect()` ou `$_SESSION` + `redirect()`

### `edit()`
- [ ] `Auth::require()`
- [ ] Buscar item
- [ ] Check empty + redirect com error
- [ ] `require` view

### `destroy()`
- [ ] `Auth::require()`
- [ ] `Security::validateCSRF()`
- [ ] Check empty + throw
- [ ] **Proteções específicas** (anti-deleção críticos)
- [ ] `$db->delete()`

---

## 🔴 ERROS COMUNS ENCONTRADOS

### ❌ Esqueceu de `Auth::require()` na primeira linha
```php
// ❌ ERRADO
public function index() {
    $db = DB::connect();  // Auth missing!
}

// ✅ CORRETO
public function index() {
    Auth::require();      // Sempre primeira
    $db = DB::connect();
}
```

### ❌ CSRF validation fora do try
```php
// ❌ ERRADO
Security::validateCSRF($_POST['csrf_token'] ?? '');
$data = Security::sanitize($_POST['name'] ?? '');  // If CSRF fails, never reaches here

// ✅ CORRETO
try {
    Security::validateCSRF($_POST['csrf_token'] ?? '');
    $data = Security::sanitize($_POST['name'] ?? '');
}
```

### ❌ Não sanitizar todos inputs
```php
// ❌ ERRADO
$name = $_POST['name'];  // Raw input!

// ✅ CORRETO
$name = Security::sanitize($_POST['name'] ?? '');
```

### ❌ UUID duplicação
```php
// ❌ ERRADO
$id = uniqid();  // Pode gerar duplicatas

// ✅ CORRETO
$id = Security::generateUUID();  // UUID v4 garantido
```

---

## ⚙️ OTIMIZAÇÕES ENCONTRADAS

### Batch Queries (GroupController:index)
```php
// ✅ PADRÃO: Em vez de N+1 queries
// Buscar IDs de todos os grupos
$groupIds = array_column($groups, 'id');

// 1 query para contar membros de TODOS
$memberCounts = $db->query(
    "SELECT group_id, COUNT(*) as count 
     FROM member_groups 
     WHERE group_id IN (" . implode(',', array_fill(0, count($groupIds), '?')) . ") 
     GROUP BY group_id",
    $groupIds
);

// Associar resultados
foreach ($groups as &$group) {
    $group['member_count'] = $memberCounts[$group['id']] ?? 0;
}
```

### Cache Estático (GroupController:getInstalledModules)
```php
// ✅ PADRÃO: Guardar em static var
private function getInstalledModules() {
    static $cachedModules = null;
    
    if ($cachedModules !== null) {
        return $cachedModules;  // Return cached
    }
    
    // ... processar módulos ...
    
    $cachedModules = $modules;
    return $modules;
}
```

### Validação de UUID em Array (MemberController:store)
```php
// ✅ PADRÃO: Validar cada ID do array
$groupIds = is_array($_POST['groups'] ?? []) ? $_POST['groups'] : [];

// Filtrar apenas UUIDs válidos
$groupIds = array_filter($groupIds, function($id) {
    return Security::isValidUUID($id);
});
```

### File Locking (MenuController:store)
```php
// ✅ PADRÃO: Evitar race conditions em inserts
$lockFile = sys_get_temp_dir() . '/aegis_menu_insert.lock';
$fp = fopen($lockFile, 'c');

if (!flock($fp, LOCK_EX | LOCK_NB)) {
    fclose($fp);
    throw new Exception('Outra inserção em andamento');
}

try {
    // ... fazer insert ...
    flock($fp, LOCK_UN);
} finally {
    fclose($fp);
}
```

### Validação Dupla em Update (MenuController:update)
```php
// ✅ PADRÃO: Checklist de validações
- Check if item exists
- Check for duplicate names (except self)
- Validate enum values (type, permission_type)
- Validate FK references
- Sanitize all inputs
- Only then update
```

---

## 🛡️ SEGURANÇA PATTERNS

### Proteção de Deleção (AdminController:destroy)
```php
// ✅ PADRÃO: Proteger deleção crítica
$activeAdmins = $this->db()->select('users', ['ativo' => 1]);
if (count($activeAdmins) <= 1 && $admins[0]['ativo'] == 1) {
    throw new Exception('Não é possível deletar o único admin ativo');
}

// Não permitir deletar a si mesmo
if ($currentUser['id'] == $id) {
    throw new Exception('Você não pode deletar a si mesmo');
}
```

### Rate Limiting (AuthController:login)
```php
// ✅ PADRÃO: Proteção brute force
$rateLimit = RateLimiter::loginAttempt($email, 5, 300);

if (!$rateLimit['allowed']) {
    throw new Exception("Muitas tentativas. Aguarde " . $rateLimit['retry_after'] . " segundos.");
}

// Se falhar, registra tentativa
RateLimiter::loginFailed($email);
```

### Cache Invalidation (GroupController:updatePermissions)
```php
// ✅ PADRÃO: Limpar cache após mudança
MenuBuilder::clearCache();
```

---

## 📊 PADRÃO DE RESPOSTA

### Sempre usar:
```php
// ✅ Session + Redirect (nunca echo json em admin)
$_SESSION['success'] = 'Mensagem';
Core::redirect('/admin/recurso');

// ❌ Nunca
echo json_encode(['success' => true]);  // Exception!
```

### Para AJAX endpoints (API):
```php
// ✅ Limpar buffer + JSON
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true]);
exit;
```

---

## 🎯 PRÓXIMO PASSO QUANDO CRIAR UM CRUD

1. Copy this template
2. Replace `[ResourceName]` com seu recurso
3. Replace `tabela` com sua tabela
4. Replace `campo1`, `campo2` com seus campos
5. Adicionar proteções específicas no `destroy()`
6. Testar CRUD completo

**Tempo:** ~15 min para implementar base

---

**Registrado por:** Claude Code + Fábio Chezzi
**Data:** 2026-02-12
