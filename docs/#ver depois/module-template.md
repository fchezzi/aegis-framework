# 🧩 Template: Novo Módulo AEGIS

**Tempo estimado:** 2-4h
**Complexidade:** Média-Alta

---

## ⚠️ WARNINGS CRÍTICOS - LEIA ANTES DE COMEÇAR

```
❌ NUNCA usar AUTO_INCREMENT para IDs → Use CHAR(36) + Core::generateUUID()
❌ NUNCA usar REFERENCES admins(id) → Tabela é "users", não "admins"
❌ NUNCA criar registro em tabela "pages" para módulos → module.json é fonte de verdade
❌ NUNCA usar DB::getInstance() → Use DB::connect()
❌ NUNCA usar Security::validateCSRF() sem parâmetro → Use Security::validateCSRF($_POST['csrf_token'])
```

---

## 📋 Estrutura do Módulo

```
modules/{nome}/
├── module.json              # Metadados (OBRIGATÓRIO)
├── routes.php               # Rotas admin + public (OBRIGATÓRIO)
├── controllers/
│   ├── Admin{Nome}Controller.php
│   └── Public{Nome}Controller.php
├── views/
│   ├── admin/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── edit.php
│   └── public/
│       ├── index.php
│       └── show.php
├── database/
│   ├── mysql-schema.sql     # Schema MySQL
│   ├── supabase-schema.sql  # Schema Supabase
│   └── rollback.sql         # Desinstalação
└── README.md
```

---

## 🔧 Passo 1: module.json

```json
{
  "name": "{nome}",
  "title": "{Título Amigável}",
  "label": "{Nome no Menu}",
  "description": "Descrição do módulo",
  "version": "1.0.0",
  "author": "Autor",
  "public": false,
  "public_url": "/{nome}",
  "adminRoute": "/admin/{nome}",
  "tables": ["tbl_{nome}"],
  "dependencies": {
    "core": ["DB", "Security", "Auth", "SimpleCache"],
    "requires_members": false
  }
}
```

**⚠️ IMPORTANTE:** `"public": true/false` é a fonte de verdade para acesso público.

---

## 🗄️ Passo 2: mysql-schema.sql

```sql
-- ⚠️ CRÍTICO: IDs são CHAR(36), NUNCA AUTO_INCREMENT
-- ⚠️ CRÍTICO: FK para admin é users(id), NÃO admins(id)

CREATE TABLE IF NOT EXISTS tbl_{nome} (
    id CHAR(36) PRIMARY KEY,                           -- UUID, NUNCA auto_increment
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    descricao TEXT,
    imagem VARCHAR(500),
    autor_id CHAR(36),                                 -- FK para admin
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- ⚠️ CRÍTICO: Tabela de admins = "users" (não "admins")
    FOREIGN KEY (autor_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_slug (slug),
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🗄️ Passo 3: supabase-schema.sql

```sql
-- ⚠️ CRÍTICO: Supabase usa UUID nativo
-- ⚠️ CRÍTICO: FK para admin é users(id), NÃO admins(id)

CREATE TABLE IF NOT EXISTS tbl_{nome} (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    descricao TEXT,
    imagem VARCHAR(500),
    autor_id UUID REFERENCES users(id) ON DELETE SET NULL,  -- ⚠️ users, não admins
    ativo BOOLEAN DEFAULT true,
    ordem INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_{nome}_slug ON tbl_{nome}(slug);
CREATE INDEX idx_{nome}_ativo ON tbl_{nome}(ativo);

-- RLS Policy (ajustar conforme necessidade)
ALTER TABLE tbl_{nome} ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Allow all on tbl_{nome}"
  ON tbl_{nome}
  FOR ALL
  USING (true)
  WITH CHECK (true);
```

---

## 🗑️ Passo 4: rollback.sql

```sql
-- Ordem reversa (respeitar FKs)
DROP TABLE IF EXISTS tbl_{nome} CASCADE;
```

---

## 🛤️ Passo 5: routes.php

```php
<?php
/**
 * Rotas do módulo {Nome}
 *
 * ⚠️ CRÍTICO: Rotas específicas ANTES de genéricas
 * ⚠️ CRÍTICO: Usar Router::get/post (estático), não $router->add
 */

// ========================================
// FUNÇÃO DE VERIFICAÇÃO DE ACESSO
// ========================================
// ⚠️ OBRIGATÓRIO para módulos com rotas públicas

if (!function_exists('check{Nome}ModuleAccess')) {
    function check{Nome}ModuleAccess() {
        if (!defined('ENABLE_MEMBERS') || !ENABLE_MEMBERS) {
            return true; // Sistema sem members = tudo público
        }

        $moduleJsonPath = __DIR__ . '/module.json';
        $metadata = json_decode(file_get_contents($moduleJsonPath), true);
        $isPublic = ($metadata['public'] ?? false);

        if ($isPublic) {
            return true;
        }

        // Módulo privado → exigir login
        MemberAuth::require();
        return true;
    }
}

// ========================================
// ROTAS ADMIN
// ========================================

Router::get('/admin/{nome}', function() {
    Auth::require();  // ⚠️ SEMPRE primeira linha
    require __DIR__ . '/controllers/Admin{Nome}Controller.php';
    (new Admin{Nome}Controller())->index();
});

Router::get('/admin/{nome}/create', function() {
    Auth::require();
    require __DIR__ . '/controllers/Admin{Nome}Controller.php';
    (new Admin{Nome}Controller())->create();
});

Router::post('/admin/{nome}/store', function() {
    Auth::require();
    require __DIR__ . '/controllers/Admin{Nome}Controller.php';
    (new Admin{Nome}Controller())->store();
});

Router::get('/admin/{nome}/edit/:id', function($id) {
    Auth::require();
    require __DIR__ . '/controllers/Admin{Nome}Controller.php';
    (new Admin{Nome}Controller())->edit($id);
});

Router::post('/admin/{nome}/update/:id', function($id) {
    Auth::require();
    require __DIR__ . '/controllers/Admin{Nome}Controller.php';
    (new Admin{Nome}Controller())->update($id);
});

Router::post('/admin/{nome}/delete/:id', function($id) {
    Auth::require();
    require __DIR__ . '/controllers/Admin{Nome}Controller.php';
    (new Admin{Nome}Controller())->delete($id);
});

// ========================================
// ROTAS PÚBLICAS
// ========================================
// ⚠️ CRÍTICO: Ordem importa! Específicas antes de genéricas

Router::get('/{nome}', function() {
    check{Nome}ModuleAccess();
    require __DIR__ . '/controllers/Public{Nome}Controller.php';
    (new Public{Nome}Controller())->index();
});

Router::get('/{nome}/:slug', function($slug) {
    check{Nome}ModuleAccess();
    require __DIR__ . '/controllers/Public{Nome}Controller.php';
    (new Public{Nome}Controller())->show($slug);
});
```

---

## 🎮 Passo 6: Controller Admin

```php
<?php
/**
 * Admin{Nome}Controller
 *
 * ⚠️ WARNINGS:
 * - Auth::require() SEMPRE primeira linha
 * - DB::connect() (não getInstance)
 * - Security::validateCSRF($_POST['csrf_token']) (com parâmetro!)
 * - Core::generateUUID() para IDs
 */

class Admin{Nome}Controller {

    public function index() {
        Auth::require();  // ⚠️ OBRIGATÓRIO

        $db = DB::connect();  // ⚠️ connect(), não getInstance()
        $items = $db->query("SELECT * FROM tbl_{nome} ORDER BY created_at DESC");

        require __DIR__ . '/../views/admin/index.php';
    }

    public function store() {
        Auth::require();

        // ⚠️ CRÍTICO: Com parâmetro!
        Security::validateCSRF($_POST['csrf_token']);

        $db = DB::connect();

        // Sanitizar
        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');

        // Validar
        if (empty($titulo)) {
            $_SESSION['error'] = 'Título é obrigatório';
            header('Location: ' . url('/admin/{nome}/create'));
            exit;
        }

        // Slug
        $slug = $this->generateSlug($titulo);

        // Upload (se houver)
        $imagemPath = null;
        if (!empty($_FILES['imagem']['name'])) {
            // ⚠️ Upload::image(), não new FileUpload()
            $upload = Upload::image($_FILES['imagem'], '{nome}');
            if ($upload['success']) {
                $imagemPath = $upload['path'];
            } else {
                $_SESSION['error'] = $upload['message'];  // ⚠️ 'message', não 'error'
                header('Location: ' . url('/admin/{nome}/create'));
                exit;
            }
        }

        // Insert
        // ⚠️ CRÍTICO: Core::generateUUID(), nunca AUTO_INCREMENT
        $db->insert('tbl_{nome}', [
            'id' => Core::generateUUID(),
            'titulo' => $titulo,
            'slug' => $slug,
            'descricao' => $descricao,
            'imagem' => $imagemPath,
            'autor_id' => Auth::user()['id'],
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ]);

        // Limpar cache
        SimpleCache::delete('{nome}_cache');

        $_SESSION['success'] = 'Criado com sucesso!';
        header('Location: ' . url('/admin/{nome}'));
        exit;
    }

    public function update($id) {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        // ⚠️ Validar UUID antes de usar
        if (!Security::isValidUUID($id)) {
            $_SESSION['error'] = 'ID inválido';
            header('Location: ' . url('/admin/{nome}'));
            exit;
        }

        $db = DB::connect();

        // Buscar existente
        $items = $db->select('tbl_{nome}', ['id' => $id]);
        if (empty($items)) {
            $_SESSION['error'] = 'Item não encontrado';
            header('Location: ' . url('/admin/{nome}'));
            exit;
        }
        $item = $items[0];

        // Sanitizar e validar...
        $titulo = Security::sanitize($_POST['titulo'] ?? '');

        // Manter imagem antiga ou substituir
        $imagemPath = $item['imagem'];
        if (!empty($_FILES['imagem']['name'])) {
            $upload = Upload::image($_FILES['imagem'], '{nome}');
            if ($upload['success']) {
                // Deletar antiga
                if (!empty($imagemPath)) {
                    Upload::delete($imagemPath);
                }
                $imagemPath = $upload['path'];
            }
        }

        // Update
        $db->update('tbl_{nome}', [
            'titulo' => $titulo,
            'slug' => $this->generateSlug($titulo),
            'descricao' => Security::sanitize($_POST['descricao'] ?? ''),
            'imagem' => $imagemPath,
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ], ['id' => $id]);

        // Limpar cache
        SimpleCache::delete('{nome}_cache');
        SimpleCache::delete('{nome}_' . $item['slug']);

        $_SESSION['success'] = 'Atualizado!';
        header('Location: ' . url('/admin/{nome}'));
        exit;
    }

    public function delete($id) {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        if (!Security::isValidUUID($id)) {
            $_SESSION['error'] = 'ID inválido';
            header('Location: ' . url('/admin/{nome}'));
            exit;
        }

        $db = DB::connect();
        $items = $db->select('tbl_{nome}', ['id' => $id]);

        if (!empty($items)) {
            $item = $items[0];

            // Deletar imagem
            if (!empty($item['imagem'])) {
                Upload::delete($item['imagem']);
            }

            $db->delete('tbl_{nome}', ['id' => $id]);
        }

        SimpleCache::delete('{nome}_cache');

        $_SESSION['success'] = 'Deletado!';
        header('Location: ' . url('/admin/{nome}'));
        exit;
    }

    private function generateSlug($texto) {
        $slug = mb_strtolower($texto);
        $slug = preg_replace('/[áàãâä]/u', 'a', $slug);
        $slug = preg_replace('/[éèêë]/u', 'e', $slug);
        $slug = preg_replace('/[íìîï]/u', 'i', $slug);
        $slug = preg_replace('/[óòõôö]/u', 'o', $slug);
        $slug = preg_replace('/[úùûü]/u', 'u', $slug);
        $slug = preg_replace('/[ç]/u', 'c', $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}
```

---

## ✅ Checklist Final

```
□ module.json com "public": true/false
□ mysql-schema.sql com CHAR(36) para IDs
□ mysql-schema.sql com REFERENCES users(id) (não admins)
□ supabase-schema.sql compatível
□ rollback.sql funciona
□ routes.php com checkModuleAccess()
□ routes.php: específicas ANTES de genéricas
□ Controllers: Auth::require() primeira linha
□ Controllers: DB::connect() (não getInstance)
□ Controllers: Security::validateCSRF($_POST['csrf_token'])
□ Controllers: Core::generateUUID() para IDs
□ Views: htmlspecialchars() em outputs
□ Testado instalação via /admin/modules
□ Testado desinstalação
□ Testado CRUD completo
```

---

**Versão:** 2.0.0
**Última atualização:** 2025-11-28
**Baseado em:** Padrões validados do módulo Blog
