# 📤 Guia Completo: Upload de Arquivos

> **Quando usar:** Qualquer implementação de upload (CRUD, TinyMCE, standalone). Upload é vetor de ataque #1 - seguir TODOS os passos de segurança.

---

## 🎯 Cenários de Upload

| Cenário | Uso | Referência |
|---------|-----|------------|
| **CRUD Admin** | Foto de produto, imagem destaque, banner | Seção 1 |
| **TinyMCE** | Upload inline no editor rico | Seção 2 |
| **Standalone** | Múltiplos arquivos, CSV, galeria | Seção 3 |

---

## 📋 Antes de Começar

**Decisões obrigatórias:**
- [ ] Que tipo de arquivo? (imagem, PDF, CSV, etc)
- [ ] Tamanho máximo? (5MB imagem, 10MB docs)
- [ ] Onde armazenar? (`storage/uploads/{tipo}/`)
- [ ] Público ou privado?

---

## 1️⃣ CENÁRIO 1: Upload em CRUD Admin

**Exemplo:** Produto com foto, Post com imagem destaque

### Controller Pattern:

```php
public function store() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);

    $db = DB::connect();
    $errors = [];

    // Campos normais
    $nome = Security::sanitize($_POST['nome'] ?? '');
    if (empty($nome)) $errors[] = 'Nome obrigatório';

    // ✅ UPLOAD DE IMAGEM
    $imagemPath = null;
    if (!empty($_FILES['imagem']['tmp_name'])) {
        $upload = Upload::image($_FILES['imagem'], 'produtos');

        if ($upload['success']) {
            $imagemPath = $upload['path'];
        } else {
            $errors[] = $upload['message']; // ⚠️ 'message' não 'error'
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        Core::redirect('/admin/produtos/create');
    }

    // Insert
    $db->insert('produtos', [
        'id' => Core::generateUUID(),
        'nome' => $nome,
        'imagem' => $imagemPath,
        'ativo' => isset($_POST['ativo']) ? 1 : 0
    ]);

    $_SESSION['success'] = 'Criado!';
    Core::redirect('/admin/produtos');
}
```

### Update com Troca de Imagem:

```php
public function update($id) {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);

    $db = DB::connect();

    // Buscar item atual
    $item = $db->query("SELECT * FROM produtos WHERE id = ?", [$id])[0];

    // ✅ MANTER IMAGEM ATUAL OU TROCAR
    $imagemPath = $item['imagem']; // Manter atual

    if (!empty($_FILES['imagem']['tmp_name'])) {
        $upload = Upload::image($_FILES['imagem'], 'produtos');

        if ($upload['success']) {
            // Deletar antiga
            if (!empty($imagemPath)) {
                Upload::delete($imagemPath); // ⚠️ Upload::delete (não unlink)
            }

            $imagemPath = $upload['path'];
        } else {
            $_SESSION['error'] = $upload['message'];
            Core::redirect('/admin/produtos/edit/' . $id);
        }
    }

    // Update
    $db->update('produtos', [
        'nome' => Security::sanitize($_POST['nome']),
        'imagem' => $imagemPath
    ], ['id' => $id]);

    $_SESSION['success'] = 'Atualizado!';
    Core::redirect('/admin/produtos');
}
```

### Delete com Remoção de Arquivo:

```php
public function delete($id) {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);

    $db = DB::connect();
    $item = $db->query("SELECT * FROM produtos WHERE id = ?", [$id])[0];

    // ✅ DELETAR ARQUIVO
    if (!empty($item['imagem'])) {
        Upload::delete($item['imagem']);
    }

    $db->delete('produtos', ['id' => $id]);

    $_SESSION['success'] = 'Deletado!';
    Core::redirect('/admin/produtos');
}
```

### View (Form):

```php
<form method="POST" action="<?= url('/admin/produtos/store') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

    <div class="form-group">
        <label for="nome">Nome *</label>
        <input type="text" id="nome" name="nome" required>
    </div>

    <div class="form-group">
        <label for="imagem">Imagem</label>

        <!-- Preview imagem atual (edit) -->
        <?php if (!empty($produto['imagem'])): ?>
            <div class="current-image">
                <img src="<?= Upload::url($produto['imagem']) ?>"
                     alt="Atual"
                     style="max-width: 200px;">
                <p><small>Imagem atual</small></p>
            </div>
        <?php endif; ?>

        <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp">
        <small>JPG, PNG ou WEBP (máx 5MB)</small>
    </div>

    <button type="submit" class="btn btn-primary">Salvar</button>
</form>
```

---

## 2️⃣ CENÁRIO 2: Upload no TinyMCE (Editor)

**Uso:** Upload de imagens inline no conteúdo rico

### Config em _config.php:

```php
define('TINYMCE_API_KEY', 'sua-chave-aqui');
```

### View com TinyMCE:

```php
<textarea id="conteudo" name="conteudo"></textarea>

<script src="https://cdn.tiny.cloud/1/<?= TINYMCE_API_KEY ?>/tinymce/6/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#conteudo',
    height: 500,
    language: 'pt_BR',
    plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'media', 'table', 'code'],
    toolbar: 'undo redo | blocks | bold italic | link image media',

    // ✅ Upload de imagens
    images_upload_url: '<?= url('/admin/blog/upload-image') ?>',
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

    $upload = Upload::image($_FILES['file'], 'blog');

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

### Route:

```php
Router::post('/admin/blog/upload-image', function() {
    $controller = new AdminBlogController();
    $controller->uploadImage();
});
```

### Database (conteúdo rico):

```sql
-- Para conteúdo com imagens/vídeos embedados
conteudo MEDIUMTEXT NOT NULL  -- 16MB (não TEXT 64KB)
```

---

## 3️⃣ CENÁRIO 3: Upload Standalone (Avançado)

**Uso:** Múltiplos arquivos, importação CSV, galeria de imagens

### Múltiplos Arquivos:

```php
public function uploadMultiple() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);

    $db = DB::connect();
    $uploadedPaths = [];
    $errors = [];

    // $_FILES['imagens'] é array quando input tem multiple
    $files = $_FILES['imagens'];
    $totalFiles = count($files['name']);

    for ($i = 0; $i < $totalFiles; $i++) {
        // Montar array compatível com Upload::image()
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];

        // Upload individual
        $upload = Upload::image($file, 'galeria');

        if ($upload['success']) {
            $uploadedPaths[] = $upload['path'];

            // Salvar no banco (opcional)
            $db->insert('galeria_imagens', [
                'id' => Core::generateUUID(),
                'path' => $upload['path'],
                'ordem' => $i
            ]);
        } else {
            $errors[] = "Arquivo {$file['name']}: {$upload['message']}";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    } else {
        $_SESSION['success'] = count($uploadedPaths) . ' imagens enviadas!';
    }

    Core::redirect('/admin/galeria');
}
```

### Form Múltiplos Arquivos:

```php
<form method="POST" action="<?= url('/admin/galeria/upload') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

    <div class="form-group">
        <label for="imagens">Selecionar Imagens</label>
        <input type="file"
               id="imagens"
               name="imagens[]"
               multiple
               accept="image/*"
               required>
        <small>Selecione múltiplas imagens (Ctrl/Cmd + clique)</small>
    </div>

    <button type="submit" class="btn btn-primary">Upload</button>
</form>
```

### Importação de CSV:

```php
public function importCSV() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);

    if (empty($_FILES['csv']['tmp_name'])) {
        $_SESSION['error'] = 'Nenhum arquivo enviado';
        Core::redirect('/admin/produtos/import');
    }

    $file = $_FILES['csv'];

    // ✅ Validar tipo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ['text/plain', 'text/csv', 'application/csv'])) {
        $_SESSION['error'] = 'Arquivo deve ser CSV';
        Core::redirect('/admin/produtos/import');
    }

    // ✅ Processar CSV
    $handle = fopen($file['tmp_name'], 'r');
    $header = fgetcsv($handle); // Primeira linha = cabeçalho
    $imported = 0;
    $db = DB::connect();

    while (($row = fgetcsv($handle)) !== false) {
        // Mapear colunas
        $nome = $row[0] ?? '';
        $preco = $row[1] ?? 0;

        if (empty($nome)) continue;

        $db->insert('produtos', [
            'id' => Core::generateUUID(),
            'nome' => Security::sanitize($nome),
            'preco' => floatval($preco),
            'ativo' => 1
        ]);

        $imported++;
    }

    fclose($handle);

    $_SESSION['success'] = "{$imported} produtos importados!";
    Core::redirect('/admin/produtos');
}
```

---

## 🛡️ Upload Class - API Completa

### Upload::image()

```php
$result = Upload::image($_FILES['foto'], 'produtos', [
    'maxSize' => 5242880,  // 5MB (opcional, padrão 5MB)
    'allowedTypes' => ['image/jpeg', 'image/png', 'image/webp']  // Opcional
]);

if ($result['success']) {
    $path = $result['path'];  // ⚠️ 'path' não 'file'
} else {
    $erro = $result['message'];  // ⚠️ 'message' não 'error'
}
```

**Validações automáticas:**
1. MIME type real (finfo, não extensão)
2. Extensão permitida baseada no MIME
3. Tamanho máximo
4. Dimensões (para imagens)
5. Nome sanitizado (gerado aleatoriamente)
6. Path traversal bloqueado
7. Permissões corretas (0644)

### Upload::delete()

```php
Upload::delete($path);  // ⚠️ NÃO usar unlink() diretamente
```

### Upload::url()

```php
<img src="<?= Upload::url($produto['imagem']) ?>" alt="Produto">
```

Retorna: `/storage/uploads/produtos/2025/02/1739557234_a3f4d5e6.jpg`

---

## 🔐 Checklist de Segurança OBRIGATÓRIO

```
✅ MIME validation (finfo, não $_FILES['type'])
✅ Extensão validada baseada no MIME
✅ Tamanho máximo definido
✅ Nome sanitizado (aleatório, nunca original)
✅ CSRF token validado
✅ Auth::require() no controller
✅ Upload::delete() ao remover (não unlink)
✅ Storage fora do webroot OU .htaccess bloqueando execução
```

### .htaccess no Storage (CRÍTICO):

Criar `storage/uploads/.htaccess`:

```apache
# ✅ BLOQUEAR EXECUÇÃO DE SCRIPTS
<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|html|shtml|sh|cgi)$">
    Require all denied
</FilesMatch>

# ✅ PERMITIR APENAS ARQUIVOS ESPECÍFICOS
<FilesMatch "\.(jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx|txt|csv)$">
    Require all granted
</FilesMatch>
```

---

## ⚠️ Erros Comuns

### ❌ ERRADO:

```php
// 1. Confundir keys do resultado
$path = $result['file'];  // ❌ Não existe
$erro = $result['error'];  // ❌ Não existe

// 2. Usar unlink direto
unlink(UPLOAD_PATH . $path);  // ❌ Path incorreto, sem validação

// 3. Confiar na extensão
if ($_FILES['file']['type'] == 'image/jpeg') {  // ❌ Pode ser forjado

// 4. Nome original
$filename = $_FILES['file']['name'];  // ❌ Risco de path traversal
move_uploaded_file($tmp, "uploads/" . $filename);
```

### ✅ CERTO:

```php
// 1. Keys corretas
$path = $result['path'];
$erro = $result['message'];

// 2. Upload::delete()
Upload::delete($path);

// 3. MIME real
$upload = Upload::image($_FILES['file'], 'tipo');  // Valida MIME interno

// 4. Nome sanitizado
// Upload::image() gera nome aleatório automaticamente
```

---

## 📚 Referências

- **CRUD com upload:** module-patterns.md (Controller Pattern)
- **Validação de inputs:** REGRAS.md #6
- **CSRF obrigatório:** REGRAS.md #5
- **Upload validações:** REGRAS.md #9

---

**Versão:** 2.0.0
**Data:** 2026-02-14
**Changelog:** Refatorado de implementação → guia de uso. Focado em 3 cenários (CRUD, TinyMCE, standalone). Reduzido de 531 → 318 linhas.
