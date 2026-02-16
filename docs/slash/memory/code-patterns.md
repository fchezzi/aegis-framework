# Padrões de Código AEGIS - Copiar/Colar

> Código pronto e testado. Copiar direto, adaptar valores entre [colchetes].

---

## 🎮 Controller CRUD Admin

### Store (Criar)

```php
public function store() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);

    $db = DB::connect();
    $errors = [];

    // Sanitizar
    $nome = Security::sanitize($_POST['nome'] ?? '');

    // Validar
    if (empty($nome)) $errors[] = 'Nome obrigatório';

    // Upload (opcional)
    $imagemPath = null;
    if (!empty($_FILES['imagem']['tmp_name'])) {
        $upload = Upload::image($_FILES['imagem'], '[pasta]');
        if ($upload['success']) {
            $imagemPath = $upload['path'];
        } else {
            $errors[] = $upload['message'];
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        Core::redirect('/admin/[rota]/create');
    }

    // Insert
    $db->insert('[tabela]', [
        'id' => Core::generateUUID(),
        'nome' => $nome,
        'imagem' => $imagemPath,
        'ativo' => isset($_POST['ativo']) ? 1 : 0
    ]);

    $_SESSION['success'] = 'Criado!';
    Core::redirect('/admin/[rota]');
}
```

### Update (Atualizar)

```php
public function update($id) {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);

    $db = DB::connect();

    // Buscar item atual
    $item = $db->query("SELECT * FROM [tabela] WHERE id = ?", [$id])[0];

    // Manter imagem atual ou trocar
    $imagemPath = $item['imagem'];

    if (!empty($_FILES['imagem']['tmp_name'])) {
        $upload = Upload::image($_FILES['imagem'], '[pasta]');
        if ($upload['success']) {
            if (!empty($imagemPath)) {
                Upload::delete($imagemPath);
            }
            $imagemPath = $upload['path'];
        } else {
            $_SESSION['error'] = $upload['message'];
            Core::redirect('/admin/[rota]/edit/' . $id);
        }
    }

    // Update
    $db->update('[tabela]', [
        'nome' => Security::sanitize($_POST['nome']),
        'imagem' => $imagemPath
    ], ['id' => $id]);

    $_SESSION['success'] = 'Atualizado!';
    Core::redirect('/admin/[rota]');
}
```

### Delete (Deletar)

```php
public function delete($id) {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);

    $db = DB::connect();
    $item = $db->query("SELECT * FROM [tabela] WHERE id = ?", [$id])[0];

    // Deletar arquivo
    if (!empty($item['imagem'])) {
        Upload::delete($item['imagem']);
    }

    $db->delete('[tabela]', ['id' => $id]);

    $_SESSION['success'] = 'Deletado!';
    Core::redirect('/admin/[rota]');
}
```

---

## 📤 Upload de Arquivos

### Upload Simples

```php
$upload = Upload::image($_FILES['foto'], '[pasta]');

if ($upload['success']) {
    $path = $upload['path'];  // ⚠️ 'path' não 'file'
} else {
    $erro = $upload['message'];  // ⚠️ 'message' não 'error'
}
```

### Upload com Opções

```php
$upload = Upload::image($_FILES['foto'], '[pasta]', [
    'maxSize' => 5242880,  // 5MB
    'allowedTypes' => ['image/jpeg', 'image/png', 'image/webp']
]);
```

### Upload para TinyMCE

```php
public function uploadImage() {
    Auth::require();
    header('Content-Type: application/json');

    $upload = Upload::image($_FILES['file'], '[pasta]');

    if ($upload['success']) {
        echo json_encode([
            'location' => url('/storage/uploads/' . $upload['path'])
        ]);
    } else {
        echo json_encode([
            'error' => $upload['message']
        ]);
    }
    exit;
}
```

### Upload Múltiplos Arquivos

```php
public function uploadMultiple() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);

    $db = DB::connect();
    $files = $_FILES['imagens'];
    $totalFiles = count($files['name']);

    for ($i = 0; $i < $totalFiles; $i++) {
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];

        $upload = Upload::image($file, '[pasta]');

        if ($upload['success']) {
            $db->insert('[tabela]', [
                'id' => Core::generateUUID(),
                'path' => $upload['path'],
                'ordem' => $i
            ]);
        }
    }

    $_SESSION['success'] = $totalFiles . ' arquivos enviados!';
    Core::redirect('/admin/[rota]');
}
```

---

## 🎨 SASS (Sempre .sass, 2 tabs)

### Estrutura Básica

```sass
// ✅ CERTO - .sass com 2 tabs
.container
  display: flex
  padding: 20px
  background: #fff

  .item
    color: #333
    font-size: 16px

    &:hover
      color: #000

    &.active
      font-weight: bold
```

### Variáveis e Mixins

```sass
// Variáveis
$primary: #007bff
$secondary: #6c757d
$spacing: 20px

// Mixin
=flex-center
  display: flex
  align-items: center
  justify-content: center

// Uso
.header
  +flex-center
  padding: $spacing
  background: $primary
```

### Media Queries

```sass
.container
  padding: 20px

  @media (max-width: 768px)
    padding: 10px

  @media (max-width: 480px)
    padding: 5px
```

---

## 🛤️ Router (Ordem Crítica)

### Ordem Correta

```php
// ✅ CERTO: Específicas ANTES de genéricas

// 1. Rotas fixas primeiro
Router::get('/blog', function() { ... });
Router::get('/blog/categoria', function() { ... });

// 2. Rotas com params
Router::get('/blog/:categoria', function($params) { ... });
Router::get('/blog/:categoria/:slug', function($params) { ... });

// 3. Genéricas POR ÚLTIMO
Router::get('/:slug', function($params) { ... });
```

### Rotas de Módulos

```php
// routes.php principal
ModuleManager::loadAllRoutes();  // ⚠️ ANTES das rotas genéricas
Router::get('/:slug', function($params) { ... });  // Por último
```

### Rotas Admin

```php
// CRUD completo
Router::get('/admin/[entidade]', '[Controller]@index');
Router::get('/admin/[entidade]/create', '[Controller]@create');
Router::post('/admin/[entidade]', '[Controller]@store');
Router::get('/admin/[entidade]/edit/:id', '[Controller]@edit');
Router::post('/admin/[entidade]/update/:id', '[Controller]@update');
Router::post('/admin/[entidade]/delete/:id', '[Controller]@destroy');
```

---

## 👥 MemberAuth (Frontend Users)

### Página Protegida

```php
<?php
MemberAuth::require();  // Redireciona para /login se não logado

$member = MemberAuth::member();  // ['id', 'email', 'name', 'group_id']
?>
<h1>Bem-vindo, <?= htmlspecialchars($member['name']) ?>!</h1>
```

### Controller Protegido

```php
public function edit() {
    MemberAuth::require();

    $member = MemberAuth::member();
    $memberId = $member['id'];

    // código...
}
```

### Check Condicional

```php
<?php if (MemberAuth::check()): ?>
    <p>Logado como: <?= htmlspecialchars(MemberAuth::member()['name']) ?></p>
    <a href="/logout">Sair</a>
<?php else: ?>
    <a href="/login">Entrar</a>
<?php endif; ?>
```

### Verificar Permissão

```php
$memberId = MemberAuth::member()['id'];
$pageId = '...';

if (Permission::canAccess($memberId, $pageId)) {
    // Permitido
} else {
    http_response_code(403);
    die('Acesso negado');
}
```

---

## 🗄️ Database Queries

### Select

```php
$db = DB::connect();

// Todos
$items = $db->select('[tabela]');

// Com filtro
$items = $db->select('[tabela]', ['ativo' => 1]);

// Limit 1
$item = $db->select('[tabela]', ['id' => $id], 1);

// Com ordenação
$items = $db->select('[tabela]', ['ativo' => 1], 'nome ASC');
```

### Insert

```php
$db->insert('[tabela]', [
    'id' => Core::generateUUID(),
    'nome' => $nome,
    'ativo' => 1,
    'created_at' => date('Y-m-d H:i:s')
]);
```

### Update

```php
$db->update('[tabela]',
    ['nome' => $nome, 'ativo' => 1],  // SET
    ['id' => $id]                      // WHERE
);
```

### Delete

```php
$db->delete('[tabela]', ['id' => $id]);
```

### Query Avançada (Prepared)

```php
$stmt = $db->prepare("
    SELECT p.*, c.nome as categoria_nome
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE p.ativo = ? AND p.preco > ?
    ORDER BY p.created_at DESC
");

$result = $stmt->execute([1, 100]);
```

---

## 📝 Forms HTML

### Form Completo

```php
<form method="POST" action="<?= url('/admin/[rota]/store') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

    <div class="form-group">
        <label for="nome">Nome *</label>
        <input type="text" id="nome" name="nome" required>
    </div>

    <div class="form-group">
        <label for="imagem">Imagem</label>
        <input type="file" id="imagem" name="imagem" accept="image/*">
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="ativo" value="1" checked>
            Ativo
        </label>
    </div>

    <button type="submit" class="btn btn-primary">Salvar</button>
</form>
```

### Form de Delete

```php
<form method="POST"
      action="<?= url('/admin/[rota]/delete/' . $item['id']) ?>"
      style="display: inline"
      onsubmit="return confirm('Tem certeza?')">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
    <button type="submit" class="btn btn-danger">Deletar</button>
</form>
```

---

## ✅ Mensagens de Sessão

### Controller (Definir)

```php
$_SESSION['success'] = 'Operação realizada!';
$_SESSION['error'] = 'Algo deu errado';
Core::redirect('/admin/[rota]');
```

### View (Exibir)

```php
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <?php unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>
```

---

**Versão:** 1.0.0
**Data:** 2026-02-14
**Linhas:** 380 (código pronto para copiar/colar)
