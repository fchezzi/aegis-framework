# Guia Rápido: Criar Módulos AEGIS

> Checklist + erros críticos + código mínimo. Ler ANTES de criar qualquer módulo.

---

## ✅ CHECKLIST PRÉ-DESENVOLVIMENTO

**Antes de escrever qualquer código:**

```
□ Prefixo obrigatório: Rotas públicas usam /{modulo}/ (exceto blog)
□ checkModuleAccess() implementado em rotas públicas
□ Router order: específicas ANTES de genéricas
□ REFERENCES users(id) (não admins)
□ DB::connect() (não getInstance)
□ Router::get() estático (não $router->add)
□ Security::validateCSRF($_POST['csrf_token'])
□ Upload::image() e Upload::delete()
□ Views self-contained (HTML completo, sem includes)
□ module.json completo (name, label, public, public_url)
```

---

## ⚠️ 7 ERROS CRÍTICOS - NÃO COMETA

### 1. Foreign Keys

```sql
-- ❌ ERRADO
FOREIGN KEY (autor_id) REFERENCES admins(id)

-- ✅ CERTO (tabela de admins se chama 'users')
FOREIGN KEY (autor_id) REFERENCES users(id)
```

### 2. Database Connection

```php
// ❌ ERRADO
$db = DB::getInstance();

// ✅ CERTO
$db = DB::connect();
```

### 3. Router Order (PROBLEMA #1)

```php
// ❌ ERRADO - /:slug captura tudo
Router::get('/:slug', ...);
ModuleManager::loadAllRoutes();

// ✅ CERTO - módulos primeiro
ModuleManager::loadAllRoutes();
Router::get('/:slug', ...);  // Por último
```

### 4. CSRF Token

```php
// ❌ ERRADO
<?= Security::generateCSRF() ?>  // Só o token

// ✅ CERTO
<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

// ❌ ERRADO
Security::validateCSRF();  // Sem parâmetro

// ✅ CERTO
Security::validateCSRF($_POST['csrf_token']);
```

### 5. Views

```php
// ❌ ERRADO - Não usar includes
require ROOT_PATH . 'admin/includes/header.php';

// ✅ CERTO - HTML completo self-contained
<!DOCTYPE html>
<html lang="pt-BR">
<head>...</head>
<body>...</body>
</html>
```

### 6. Upload

```php
// ❌ ERRADO
$erro = $result['error'];  // Não existe
unlink(UPLOAD_PATH . $path);

// ✅ CERTO
$erro = $result['message'];
Upload::delete($path);
```

### 7. Router Order no Módulo

```php
// ✅ CERTO - Específica → Genérica
Router::get('/blog', ...);                          // 1. Fixo
Router::get('/blog/pagina/:page', ...);            // 2. Com param
Router::get('/:categoria_slug/:post_slug', ...);   // 3. Genérico 2 params
Router::get('/:categoria_slug', ...);              // 4. Genérico 1 param (ÚLTIMO!)
```

---

## 📦 Estrutura Mínima de Módulo

```
modules/[nome]/
├── module.json           # Metadados
├── routes.php            # Rotas admin + public
├── controllers/
│   ├── Admin[Nome]Controller.php
│   └── Public[Nome]Controller.php
├── views/
│   ├── admin/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── edit.php
│   └── public/
│       ├── index.php
│       └── show.php
└── database/
    ├── mysql-schema.sql
    ├── supabase-schema.sql
    └── rollback.sql
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

## 🗄️ Database Schema Template

### MySQL

```sql
CREATE TABLE IF NOT EXISTS tbl_[modulo]_[entidade] (
    id VARCHAR(36) PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    conteudo TEXT,
    imagem VARCHAR(500),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Supabase

```sql
CREATE TABLE IF NOT EXISTS tbl_[modulo]_[entidade] (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    conteudo TEXT,
    imagem VARCHAR(500),
    ativo BOOLEAN DEFAULT true,
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

---

## 🔐 Public Access Pattern (checkModuleAccess)

**Implementar no topo do routes.php:**

```php
function checkModuleAccess($moduleName) {
    if (!ENABLE_MEMBERS) return true;

    $moduleJsonPath = ROOT_PATH . "modules/{$moduleName}/module.json";
    if (!file_exists($moduleJsonPath)) return true;

    $metadata = json_decode(file_get_contents($moduleJsonPath), true);
    $isPublic = ($metadata['public'] ?? false);

    if ($isPublic) return true;

    MemberAuth::require();  // Módulo privado
    return true;
}

// Aplicar em TODAS as rotas públicas
Router::get('/[modulo]', function() {
    checkModuleAccess('[modulo]');
    // controller...
});
```

---

## 🎨 TinyMCE Integration (Opcional)

### Config _config.php

```php
define('TINYMCE_API_KEY', 'sua-chave-aqui');
```

### View

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
    images_upload_url: '<?= url('/admin/[modulo]/upload-image') ?>',
    automatic_uploads: true,
    media_live_embeds: true
});
</script>
```

### Controller

```php
public function uploadImage() {
    Auth::require();
    header('Content-Type: application/json');

    $upload = Upload::image($_FILES['file'], '[modulo]');

    if ($upload['success']) {
        echo json_encode(['location' => url('/storage/uploads/' . $upload['path'])]);
    } else {
        echo json_encode(['error' => $upload['message']]);
    }
    exit;
}
```

### Route

```php
Router::post('/admin/[modulo]/upload-image', function() {
    $controller = new Admin[Modulo]Controller();
    $controller->uploadImage();
});
```

### Database

```sql
conteudo MEDIUMTEXT NOT NULL  -- 16MB para imagens/vídeos embed
```

---

## ✅ Checklist Final (Antes de Considerar Pronto)

```
✅ module.json completo (name, label, public, public_url)
✅ Schemas MySQL + Supabase + rollback.sql
✅ routes.php com checkModuleAccess() nas rotas públicas
✅ Controllers com Auth::require() e validateCSRF($_POST['csrf_token'])
✅ Views self-contained (HTML completo)
✅ Security::sanitize() em TODOS inputs
✅ Upload::image() e Upload::delete()
✅ htmlspecialchars() em TODOS outputs
✅ Router order correto (específicas ANTES)
✅ Teste: instalar via /admin/modules
✅ Teste: CRUD completo funciona
✅ Teste: frontend público funciona
```

---

**Versão:** 1.0.0
**Data:** 2026-02-14
**Linhas:** 270 (checklist + código mínimo)
