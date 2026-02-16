# Sistema de Módulos

## Estrutura

```
modules/nome/
├── module.json           # Obrigatório
├── routes.php
├── controllers/
├── views/
└── database/
    ├── mysql-schema.sql
    └── supabase-schema.sql
```

## module.json

```json
{
    "name": "nome_modulo",
    "title": "Nome Amigável",
    "description": "Descrição",
    "version": "1.0.0",
    "public": false,
    "tables": ["tabela1", "tabela2"]
}
```

## Gerenciar Módulos

```php
ModuleManager::install('nome');
ModuleManager::uninstall('nome');
ModuleManager::isInstalled('nome');
ModuleManager::getInstalled();
```

## routes.php do Módulo

```php
<?php

Router::get('/nome', function() {
    // Verificar acesso
    $metadata = json_decode(file_get_contents(__DIR__ . '/module.json'), true);
    if (!($metadata['public'] ?? false) && defined('ENABLE_MEMBERS') && ENABLE_MEMBERS) {
        MemberAuth::require();
    }

    require __DIR__ . '/controllers/NomeController.php';
    (new NomeController())->index();
});

// Rotas admin
Router::get('/admin/nome', function() {
    Auth::require();
    require __DIR__ . '/controllers/AdminNomeController.php';
    (new AdminNomeController())->index();
});
```

## Schema SQL

```sql
CREATE TABLE nome_tabela (
    id CHAR(36) PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    autor_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (autor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**IMPORTANTE:** FK para admin é `users(id)`, não `admins(id)`.

## Público/Privado

Via `/admin/modules` ou direto no `module.json`:

```json
{ "public": true }
```

---

## Exemplo Real: Módulo Artigos

### Estrutura Completa

```
modules/artigos/
├── module.json
├── routes.php
├── controllers/
│   ├── AdminArtigosController.php
│   └── PublicArtigosController.php
├── views/
│   ├── admin/
│   │   ├── index.php (lista)
│   │   └── form.php (criar/editar)
│   └── public/
│       ├── index.php (listagem)
│       └── artigo.php (individual)
└── database/
    ├── mysql-schema.sql
    ├── supabase-schema.sql
    └── rollback.sql
```

### module.json (v14)

```json
{
    "name": "artigos",
    "label": "Artigos Científicos",
    "description": "Sistema de artigos científicos com captura de leads",
    "version": "1.0.0",
    "author": "AEGIS Team",
    "public": true,
    "public_url": "/artigos",
    "homepage": "/artigos",
    "adminRoute": "/admin/artigos",
    "dependencies": {
        "core": ["DB", "Security", "Auth", "Upload", "Core", "Email", "RDStation"],
        "tables": ["tbl_artigos", "tbl_artigos_leads"],
        "requires_members": false
    },
    "installation": {
        "schemas": {
            "mysql": "database/mysql-schema.sql",
            "supabase": "database/supabase-schema.sql"
        },
        "rollback": "database/rollback.sql",
        "auto_install": true
    },
    "permissions": {
        "admin": ["read", "write", "delete"],
        "member": []
    },
    "menu": {
        "admin": [{
            "label": "Artigos",
            "route": "/admin/artigos",
            "icon": "📄"
        }]
    },
    "configuration": {
        "per_page": 10,
        "max_file_size": 10485760,
        "allowed_extensions": ["pdf", "jpg", "png"]
    }
}
```

### routes.php com Helper

```php
<?php

// Helper function para verificar acesso público/privado
if (!function_exists('checkModuleAccess')) {
    function checkModuleAccess($moduleName) {
        if (!ENABLE_MEMBERS) {
            return true;
        }

        $moduleJsonPath = ROOT_PATH . "modules/{$moduleName}/module.json";
        if (!file_exists($moduleJsonPath)) {
            http_response_code(404);
            echo "Module not found";
            exit;
        }

        $metadata = json_decode(file_get_contents($moduleJsonPath), true);
        $isPublic = ($metadata['public'] ?? false);

        if ($isPublic) {
            return true;
        }

        MemberAuth::require();
        return true;
    }
}

// Rotas públicas
Router::get('/artigos', function() {
    checkModuleAccess('artigos');
    require_once __DIR__ . '/controllers/PublicArtigosController.php';
    $controller = new PublicArtigosController();
    $controller->index();
});

Router::get('/artigos/:slug', function($slug) {
    checkModuleAccess('artigos');
    require_once __DIR__ . '/controllers/PublicArtigosController.php';
    $controller = new PublicArtigosController();
    $controller->artigo($slug);
});

Router::post('/artigos/:slug/solicitar', function($slug) {
    checkModuleAccess('artigos');
    require_once __DIR__ . '/controllers/PublicArtigosController.php';
    $controller = new PublicArtigosController();
    $controller->solicitar($slug);
});

Router::post('/artigos/buscar', function() {
    checkModuleAccess('artigos');
    require_once __DIR__ . '/controllers/PublicArtigosController.php';
    $controller = new PublicArtigosController();
    $controller->buscar();
});

// Rotas admin
Router::get('/admin/artigos', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->index();
});

Router::get('/admin/artigos/novo', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->form();
});

Router::post('/admin/artigos/salvar', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->salvar();
});

Router::get('/admin/artigos/editar/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->form($id);
});

Router::post('/admin/artigos/deletar/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->deletar($id);
});
```

### Schema com UUIDs

```sql
-- MySQL
CREATE TABLE IF NOT EXISTS tbl_artigos (
    id VARCHAR(36) PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    introducao TEXT NOT NULL,
    autor VARCHAR(255) NOT NULL,
    data_artigo DATE NOT NULL,
    imagem VARCHAR(255),
    link_externo VARCHAR(500),
    arquivo_pdf VARCHAR(255),
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_artigos_slug (slug),
    INDEX idx_artigos_data (data_artigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tbl_artigos_leads (
    id VARCHAR(36) PRIMARY KEY,
    artigo_id VARCHAR(36) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artigo_id) REFERENCES tbl_artigos(id) ON DELETE CASCADE,
    INDEX idx_leads_artigo (artigo_id),
    INDEX idx_leads_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Controller com UUID

```php
<?php

class AdminArtigosController {

    public function salvar() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        $db = DB::connect();

        // Obter dados
        $id = Security::sanitize($_POST['id'] ?? '');
        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        // ... outros campos

        if (empty($id)) {
            // CRIAR NOVO - gerar UUID
            $id = Core::generateUUID();

            $db->insert('tbl_artigos', [
                'id' => $id,
                'titulo' => $titulo,
                'slug' => $slug,
                // ... outros campos
            ]);

            $_SESSION['success'] = 'Artigo criado com sucesso!';
        } else {
            // ATUALIZAR EXISTENTE
            $db->update('tbl_artigos', [
                'titulo' => $titulo,
                // ... outros campos
            ], ['id' => $id]);

            $_SESSION['success'] = 'Artigo atualizado com sucesso!';
        }

        Core::redirect('/admin/artigos');
    }
}
```

### Integração Email + RD Station

```php
<?php

class PublicArtigosController {

    public function solicitar($slug) {
        Security::validateCSRF($_POST['csrf_token']);

        $db = DB::connect();

        // Buscar artigo
        $artigo = $db->query("SELECT * FROM tbl_artigos WHERE slug = ?", [$slug]);
        if (empty($artigo)) {
            $_SESSION['error'] = 'Artigo não encontrado';
            Core::redirect('/artigos');
            return;
        }
        $artigo = $artigo[0];

        // Validar e sanitizar
        $nome = Security::sanitize($_POST['nome'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $whatsapp = Security::sanitize($_POST['whatsapp'] ?? '');

        // Gerar UUID para lead
        $leadId = Core::generateUUID();

        // Salvar lead
        $db->insert('tbl_artigos_leads', [
            'id' => $leadId,
            'artigo_id' => $artigo['id'],
            'nome' => $nome,
            'email' => $email,
            'whatsapp' => $whatsapp
        ]);

        // Enviar email com PDF
        if (!empty($artigo['arquivo_pdf'])) {
            $pdfPath = STORAGE_PATH . $artigo['arquivo_pdf'];
            if (file_exists($pdfPath)) {
                Email::enviarArtigo($email, $nome, $artigo['titulo'], $pdfPath);
            }
        }

        // Enviar para RD Station
        RDStation::enviarLead($email, $nome, $whatsapp, $artigo['titulo'], $slug);

        $_SESSION['success'] = 'Solicitação enviada! Verifique seu email.';
        Core::redirect('/artigos/' . $slug);
    }
}
```

---

## Instalação e Uso

### Instalar Módulo

1. Via Admin UI: `/admin/modules` → Clicar em "Instalar"
2. Via código:
```php
ModuleManager::install('artigos');
```

### Desinstalar Módulo

1. Via Admin UI: `/admin/modules` → Clicar em "Desinstalar"
2. Via código:
```php
ModuleManager::uninstall('artigos');
```

### Verificar Status

```php
$instalado = ModuleManager::isInstalled('artigos'); // bool
$modulos = ModuleManager::getInstalled(); // array
```
