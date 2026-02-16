# 🚀 Padrões de Desenvolvimento - Módulos AEGIS

> **Quando usar:** Ao criar qualquer módulo novo. Ler ANTES de começar (obrigatório).

---

## ✅ CHECKLIST PRÉ-DESENVOLVIMENTO

**Antes de escrever qualquer código:**

- [ ] 🚨 **Prefixo obrigatório** = Rotas públicas DEVEM usar `/{modulo}/` (exceto blog)
- [ ] 🚨 **Padrão de acesso** = Implementar `checkModuleAccess()` em rotas públicas
- [ ] 🚨 **Router order** = Rotas específicas ANTES de genéricas (problema #1)
- [ ] Tabela de admins = `users` (não `admins`)
- [ ] Database = `DB::connect()` (não `getInstance`)
- [ ] Router = `Router::get()` static (não `$router->add`)
- [ ] CSRF = `Security::validateCSRF($_POST['csrf_token'])` (com parâmetro)
- [ ] Upload = `Upload::image($file, 'dest')` (não `new FileUpload`)
- [ ] Views = Self-contained HTML completo (não `require header.php`)
- [ ] Rota raiz = `/admin/[modulo]` que redireciona para principal
- [ ] **module.json** = Incluir `"label"`, `"public": false`, `"public_url"`

**Se todos ✅ → pode começar!**

---

## ⚠️ ERROS CRÍTICOS - NÃO COMETA

### 1. Database - Foreign Keys

```sql
-- ❌ ERRADO
FOREIGN KEY (autor_id) REFERENCES admins(id)
LEFT JOIN admins a ON a.id = p.autor_id

-- ✅ CERTO (tabela de admins se chama 'users')
FOREIGN KEY (autor_id) REFERENCES users(id)
LEFT JOIN users a ON a.id = p.autor_id
```

### 2. Database Connection

```php
// ❌ ERRADO
$db = DB::getInstance();

// ✅ CERTO
$db = DB::connect();
```

### 3. Router (Métodos Estáticos)

```php
// ❌ ERRADO
$router->add('/path', ...);

// ✅ CERTO
Router::get('/path', ...);
Router::post('/path', ...);
```

### 4. CSRF Token

```php
// ❌ ERRADO - generateCSRF() retorna APENAS o token, não o HTML
<?= Security::generateCSRF() ?>

// ✅ CERTO - input completo
<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

// ❌ ERRADO - validateCSRF sem parâmetro
Security::validateCSRF();

// ✅ CERTO - com parâmetro
Security::validateCSRF($_POST['csrf_token']);
```

### 5. Views Self-Contained

```php
// ❌ ERRADO - views não usam includes
require ROOT_PATH . 'admin/includes/header.php';
require 'frontend/templates/header.php';

// ✅ CERTO - HTML completo
<!DOCTYPE html>
<html lang="pt-BR">
<head>...</head>
<body>...</body>
</html>
```

**Mensagens:**
- Controller usa `$_SESSION['success']`
- View lê e faz `unset`

### 6. Upload de Arquivos

```php
// ❌ ERRADO
$result = new FileUpload();
$erro = $result['error'];
unlink(UPLOAD_PATH . $path);

// ✅ CERTO
$result = Upload::image($_FILES['foto'], 'destino');
$erro = $result['message'];  // não 'error'
Upload::delete($path);  // não unlink
```

### 7. Router Order (PROBLEMA #1 QUE QUEBRA MÓDULOS)

⚠️ **Ordem de registro de rotas é FUNDAMENTAL**

**No routes.php principal:**
```php
// ❌ ERRADO - /:slug captura tudo antes dos módulos
Router::get('/:slug', function($slug) { ... });
ModuleManager::loadAllRoutes(); // módulos nunca executam!

// ✅ CERTO - módulos primeiro
ModuleManager::loadAllRoutes(); // /blog funciona!
Router::get('/:slug', function($slug) { ... }); // genérico por último
```

**No routes.php do módulo:**
```php
// ✅ Ordem correta (mais específica → menos específica)
Router::get('/blog', ...);                          // 1. Fixo
Router::get('/blog/pagina/:page', ...);            // 2. Com param
Router::get('/:categoria_slug/pagina/:page', ...); // 3. Genérico 2 params
Router::get('/:categoria_slug/:post_slug', ...);   // 4. Genérico 2 params
Router::get('/:categoria_slug', ...);              // 5. Genérico 1 param (ÚLTIMO!)
```

⚠️ **Se `/blog` der 404, problema é ordem de rotas!**

---

## 🗄️ Database Patterns

### Tabela Principal (MySQL)

```sql
CREATE TABLE IF NOT EXISTS tbl_[modulo]_[entidade] (
    id VARCHAR(36) PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    conteudo TEXT,
    imagem VARCHAR(500),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela Principal (Supabase)

```sql
CREATE TABLE IF NOT EXISTS tbl_[modulo]_[entidade] (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    conteudo TEXT,
    imagem VARCHAR(500),
    ativo BOOLEAN DEFAULT true,
    ordem INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_slug ON tbl_[modulo]_[entidade](slug);
CREATE INDEX idx_ativo ON tbl_[modulo]_[entidade](ativo);

-- Trigger updated_at
CREATE OR REPLACE FUNCTION update_[tabela]_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_updated_at
BEFORE UPDATE ON tbl_[modulo]_[entidade]
FOR EACH ROW
EXECUTE FUNCTION update_[tabela]_updated_at();
```

### Foreign Keys

```sql
-- RESTRICT: não deixa deletar pai se tem filhos
FOREIGN KEY (categoria_id) REFERENCES tbl_categorias(id)
    ON DELETE RESTRICT ON UPDATE CASCADE

-- CASCADE: deleta filhos quando deleta pai
FOREIGN KEY (post_id) REFERENCES tbl_posts(id)
    ON DELETE CASCADE ON UPDATE CASCADE

-- SET NULL: seta NULL nos filhos quando deleta pai
-- ⚠️ CRÍTICO: Tabela de admins se chama 'users' (não 'admins')
FOREIGN KEY (autor_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
```

---

## 🎮 Controller Pattern (Admin CRUD)

```php
<?php
class Admin[Entidade]Controller {

    public function index() {
        Auth::require();
        $db = DB::connect(); // ⚠️ CORRETO: DB::connect() (não getInstance)
        $items = $db->query("SELECT * FROM tbl_[modulo] ORDER BY created_at DESC");
        require __DIR__ . '/../views/admin/index.php';
    }

    public function store() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']); // ⚠️ COM parâmetro

        $db = DB::connect();
        $errors = [];

        // Sanitizar
        $campo = Security::sanitize($_POST['campo'] ?? '');

        // Validar
        if (empty($campo)) $errors[] = 'Campo obrigatório';
        if (strlen($campo) > 255) $errors[] = 'Campo muito longo';

        // Unique
        $exists = $db->query("SELECT id FROM tbl WHERE campo = ?", [$campo]);
        if (!empty($exists)) $errors[] = 'Já existe';

        // Upload (opcional)
        $imagemPath = null;
        if (!empty($_FILES['imagem']['name'])) {
            $upload = Upload::image($_FILES['imagem'], '[modulo]');
            if ($upload['success']) {
                $imagemPath = $upload['path'];
            } else {
                $errors[] = $upload['message']; // ⚠️ 'message' não 'error'
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . url('/admin/[modulo]/create'));
            exit;
        }

        // Insert
        $db->insert('tbl_[modulo]', [
            'id' => Core::generateUUID(), // ⚠️ Core::generateUUID()
            'campo' => $campo,
            'imagem' => $imagemPath,
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ]);

        Cache::delete('[modulo]_cache');

        $_SESSION['success'] = 'Criado com sucesso!';
        header('Location: ' . url('/admin/[modulo]'));
        exit;
    }

    public function update($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Buscar existente
        $item = $db->query("SELECT * FROM tbl WHERE id = ?", [$id])[0];

        // Validar unique (exceto próprio)
        $exists = $db->query("SELECT id FROM tbl WHERE campo = ? AND id != ?", [$campo, $id]);

        // Manter imagem antiga ou substituir
        $imagemPath = $item['imagem'];
        if (!empty($_FILES['imagem']['name'])) {
            $upload = Upload::image($_FILES['imagem'], '[modulo]');
            if ($upload['success']) {
                // Deletar antiga
                if (!empty($imagemPath)) {
                    Upload::delete($imagemPath); // ⚠️ Upload::delete (não unlink)
                }
                $imagemPath = $upload['path'];
            }
        }

        // Update
        $db->update('tbl', [...], ['id' => $id]);

        Cache::delete('[modulo]_cache');
        Cache::delete('[modulo]_' . $item['slug']);

        $_SESSION['success'] = 'Atualizado!';
        header('Location: ' . url('/admin/[modulo]'));
        exit;
    }

    public function delete($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();
        $item = $db->query("SELECT * FROM tbl WHERE id = ?", [$id])[0];

        // Deletar arquivo
        if (!empty($item['imagem'])) {
            Upload::delete($item['imagem']);
        }

        $db->delete('tbl', ['id' => $id]);

        Cache::delete('[modulo]_cache');

        $_SESSION['success'] = 'Deletado!';
        header('Location: ' . url('/admin/[modulo]'));
        exit;
    }
}
```

---

## 🎨 View Patterns (Self-Contained)

### Admin List (index.php)

```php
<?php
Auth::require();
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
if ($success) unset($_SESSION['success']);
if ($error) unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>[Módulo]</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        .btn { padding: 8px 12px; color: white; text-decoration: none; border-radius: 4px; }
        .btn-primary { background-color: #007bff; }
        .btn-danger { background-color: #dc3545; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>📝 [Entidades]</h1>
    <a href="<?= url('/admin/[modulo]/create') ?>" class="btn btn-primary">+ Novo</a>

    <?php if ($success): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr><th>Campo</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['campo']) ?></td>
                    <td><?= $item['ativo'] ? '✅' : '❌' ?></td>
                    <td>
                        <a href="<?= url('/admin/[modulo]/' . $item['id'] . '/edit') ?>" class="btn btn-primary">✏️</a>
                        <form method="POST" action="<?= url('/admin/[modulo]/' . $item['id'] . '/delete') ?>"
                              style="display:inline" onsubmit="return confirm('Deletar?')">
                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                            <button type="submit" class="btn btn-danger">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
```

### Admin Form (create.php)

```php
<?php
Auth::require();
$error = $_SESSION['error'] ?? '';
if ($error) unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Novo [Entidade]</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 15px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Novo [Entidade]</h1>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/[modulo]/store') ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

        <div class="form-group">
            <label for="titulo">Título *</label>
            <input type="text" id="titulo" name="titulo" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="slug">Slug *</label>
            <input type="text" id="slug" name="slug" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="imagem">Imagem</label>
            <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/jpg,image/png,image/webp">
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="ativo" value="1" checked> Ativo</label>
        </div>

        <button type="submit" class="btn btn-primary">💾 Salvar</button>
        <a href="<?= url('/admin/[modulo]') ?>" class="btn btn-secondary">Cancelar</a>
    </form>

    <script>
    // Auto-slug
    document.getElementById('titulo').addEventListener('input', function(e) {
        const slug = e.target.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-')
            .replace(/-+/g, '-').replace(/^-|-$/g, '');
        document.getElementById('slug').value = slug;
    });
    </script>
</body>
</html>
```

---

## 📱 Public Controller Pattern

```php
<?php
class Public[Modulo]Controller {

    public function index($page = 1) {
        $cacheKey = "[modulo]_list_p{$page}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            extract($cached);
        } else {
            $db = DB::connect();
            $page = max(1, (int)$page);
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            $items = $db->query("SELECT * FROM tbl WHERE ativo = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
                [1, $perPage, $offset]);

            $total = $db->query("SELECT COUNT(*) as total FROM tbl WHERE ativo = ?", [1])[0]['total'];
            $totalPages = ceil($total / $perPage);

            Cache::set($cacheKey, compact('items', 'total', 'totalPages'), 300);
        }

        require __DIR__ . '/../views/public/index.php';
    }

    public function show($slug) {
        $cacheKey = "[modulo]_{$slug}";
        $item = Cache::get($cacheKey);

        if ($item === null) {
            $db = DB::connect();
            $result = $db->query("SELECT * FROM tbl WHERE slug = ? AND ativo = ?", [$slug, 1]);
            $item = $result[0] ?? null;

            if (!$item) {
                http_response_code(404);
                echo "Não encontrado";
                exit;
            }

            Cache::set($cacheKey, $item, 600);
        }

        require __DIR__ . '/../views/public/show.php';
    }
}
```

---

## 🔐 Public Access Pattern (OBRIGATÓRIO)

### Regras:
1. **Prefixo obrigatório:** Todas rotas públicas DEVEM usar `/{modulo}/` (exceto blog)
2. **Implementar `checkModuleAccess()`** no início do `routes.php`
3. **Adicionar ao module.json:** `"label"`, `"public": false`, `"public_url"`

### Implementação em routes.php:

```php
// Verificação de acesso (copiar no topo do routes.php)
function checkModuleAccess($moduleName) {
    if (!ENABLE_MEMBERS) return true;

    $moduleJsonPath = ROOT_PATH . "modules/{$moduleName}/module.json";
    if (!file_exists($moduleJsonPath)) return true;

    $metadata = json_decode(file_get_contents($moduleJsonPath), true);
    $isPublic = ($metadata['public'] ?? false);

    if ($isPublic) return true;

    MemberAuth::require(); // Módulo privado
    return true;
}

// Aplicar em TODAS as rotas públicas
Router::get('/cursos', function() {
    checkModuleAccess('cursos');
    // controller...
});

Router::get('/cursos/:slug', function($params) {
    checkModuleAccess('cursos');
    // controller...
});
```

### module.json:

```json
{
  "name": "cursos",
  "label": "Cursos Online",
  "public": false,
  "public_url": "/cursos"
}
```

---

## 🎨 TinyMCE Integration Pattern

### Config em _config.php:

```php
define('TINYMCE_API_KEY', 'sua-chave-aqui');
```

### View com Editor:

```php
<textarea id="conteudo" name="conteudo"></textarea>

<script src="https://cdn.tiny.cloud/1/<?= TINYMCE_API_KEY ?>/tinymce/6/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#conteudo',
    height: 500,
    language: 'pt_BR',
    plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'media', 'table', 'code'],
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image media',

    // Upload de imagens
    images_upload_url: '<?= url('/admin/[modulo]/upload-image') ?>',
    automatic_uploads: true,

    // YouTube embed
    media_live_embeds: true
});
</script>
```

### Controller Upload:

```php
public function uploadImage() {
    Auth::require();
    header('Content-Type: application/json');

    $upload = Upload::image($_FILES['file'], 'modulo');

    if ($upload['success']) {
        echo json_encode(['location' => url('/storage/uploads/' . $upload['path'])]);
    } else {
        echo json_encode(['error' => $upload['message']]);
    }
    exit;
}
```

### Route:

```php
Router::post('/admin/[modulo]/upload-image', function() {
    $controller = new Admin[Modulo]Controller();
    $controller->uploadImage();
});
```

### Database (conteúdo rico):

```sql
-- Para conteúdo com imagens/vídeos
conteudo MEDIUMTEXT NOT NULL  -- 16MB (não TEXT 64KB)
```

---

## 📄 module.json Template

```json
{
  "name": "[modulo]",
  "title": "[Título]",
  "label": "[Label no Menu]",
  "description": "Descrição do módulo",
  "version": "1.0.0",
  "author": "Autor",
  "homepage": "/[modulo]",
  "adminRoute": "/admin/[modulo]",
  "public": false,
  "public_url": "/[modulo]",
  "dependencies": {
    "core": ["DB", "Security", "Auth", "Cache"],
    "tables": ["tbl_[modulo]"],
    "requires_members": false
  },
  "installation": {
    "schemas": {
      "mysql": "database/mysql-schema.sql",
      "supabase": "database/supabase-schema.sql"
    },
    "rollback": "database/rollback.sql",
    "auto_install": true
  }
}
```

---

## 🎯 Validação Final

Antes de considerar módulo pronto:

```
✅ module.json completo (name, label, public, public_url)
✅ Schemas MySQL + Supabase + rollback.sql
✅ routes.php com checkModuleAccess() nas rotas públicas
✅ Controllers com Auth::require() e validateCSRF($_POST['csrf_token'])
✅ Views self-contained (HTML completo, sem includes)
✅ Security::sanitize() em TODOS inputs
✅ Upload::image() e Upload::delete() (não unlink)
✅ Cache em listagens públicas
✅ htmlspecialchars() em TODOS outputs
✅ Router order correto (específicas ANTES de genéricas)
✅ Teste: instalar via /admin/modules
✅ Teste: CRUD completo funciona
✅ Teste: frontend público funciona
```

---

**Versão:** 5.0.0
**Data:** 2026-02-14
**Changelog:** Removidas seções enfeite (workflow, métricas blog, checklist duplicado, JS utils). Focado em erros críticos, patterns práticos, router order. Reduzido de 702 → 488 linhas.
