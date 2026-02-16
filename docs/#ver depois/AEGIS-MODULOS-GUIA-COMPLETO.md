# Guia Completo: Criação de Módulos no AEGIS Framework v14

## 📋 Índice

1. [Visão Geral](#1-visão-geral-do-sistema-de-módulos)
2. [Estrutura de Pastas](#2-estrutura-de-pastas-obrigatória)
3. [Configuração: module.json](#3-configuração-modulejson)
4. [Database Schemas](#4-database-schemas)
5. [Sistema de Rotas](#5-sistema-de-rotas-routesphp)
6. [Controllers](#6-controllers)
7. [Views](#7-views)
8. [Padrão de Acesso Público/Privado](#8-padrão-de-acesso-públicoprivado)
9. [Processo de Instalação](#9-processo-de-instalação)
10. [Processo de Desinstalação](#10-processo-de-desinstalação)
11. [Checklist Completo](#11-checklist-completo)
12. [Exemplo Prático Completo](#12-exemplo-prático-criar-módulo-cursos)
13. [Troubleshooting](#13-troubleshooting)
14. [Boas Práticas](#14-boas-práticas)

---

## 1. Visão Geral do Sistema de Módulos

### O que é um Módulo no AEGIS?

Um módulo é uma **funcionalidade independente e autocontida** que pode ser instalada/desinstalada sem afetar o core do framework. Funciona como um plugin.

### Características de um Módulo:

- ✅ **Autocontido**: Possui sua própria estrutura de pastas, rotas, controllers, views
- ✅ **Banco de dados próprio**: Define suas tabelas/views no `database/`
- ✅ **Instalação automatizada**: Schema SQL executado automaticamente
- ✅ **Desinstalação limpa**: Remove tabelas e configurações sem deixar rastros
- ✅ **Multi-database**: Suporta MySQL e Supabase com schemas separados
- ✅ **Manifesto declarativo**: Tudo configurado em `module.json`
- ✅ **Controle de acesso**: Público/Privado via campo `"public"` no manifesto
- ✅ **Roteamento independente**: `routes.php` próprio carregado automaticamente
- ✅ **Menu automático**: Itens de menu criados na instalação

### Módulos Instalados no Projeto:

| Módulo | Versão | Tipo | Descrição |
|--------|--------|------|-----------|
| `blog` | 1.1.0 | Público | Sistema de blog com posts e categorias |
| `palpites` | 1.0.0 | Privado | Sistema de palpites esportivos com ranking |
| `artigos` | 1.0.0 | Público | Artigos científicos com captura de leads |

---

## 2. Estrutura de Pastas Obrigatória

```
modules/
└── nome_modulo/
    ├── module.json                    ← OBRIGATÓRIO: Manifesto do módulo
    ├── routes.php                     ← OBRIGATÓRIO: Definição de rotas
    ├── README.md                      ← Recomendado: Documentação
    │
    ├── controllers/                   ← OBRIGATÓRIO
    │   ├── AdminNomeController.php    ← Controller para admin
    │   └── PublicNomeController.php   ← Controller para rotas públicas
    │
    ├── views/                         ← OBRIGATÓRIO
    │   ├── admin/                     ← Views do admin
    │   │   ├── index.php
    │   │   ├── novo.php
    │   │   └── editar.php
    │   └── public/                    ← Views públicas
    │       ├── index.php
    │       └── detalhes.php
    │
    ├── database/                      ← OBRIGATÓRIO (se usar DB)
    │   ├── mysql-schema.sql           ← Schema para MySQL
    │   ├── supabase-schema.sql        ← Schema para Supabase/PostgreSQL
    │   └── rollback.sql               ← Script de desinstalação
    │
    └── assets/                        ← OPCIONAL
        ├── css/
        ├── js/
        └── images/
```

### Observações:

- **Nome do módulo**: Sempre em lowercase, sem espaços (ex: `artigos`, `palpites`, `blog`)
- **Convenção de nomenclatura**: Use `snake_case` para nomes de pastas/arquivos
- **Controllers**: Prefixo `Admin` ou `Public` para distinguir contexto
- **Database**: Sempre fornecer ambos schemas (MySQL + Supabase) para compatibilidade

---

## 3. Configuração: module.json

### Estrutura Completa (Todos os Campos Possíveis)

```json
{
    "name": "nome_modulo",
    "label": "Nome Legível do Módulo",
    "title": "Título do Módulo",
    "description": "Descrição detalhada do módulo",
    "version": "1.0.0",
    "author": "Nome do Autor",

    "public": false,
    "public_url": "/nome_modulo/rota-principal",
    "homepage": "/nome_modulo",
    "adminRoute": "/admin/nome_modulo",

    "dependencies": {
        "core": [
            "DB",
            "Security",
            "Auth",
            "Upload",
            "Core"
        ],
        "tables": [
            "tbl_nome_principal",
            "tbl_nome_relacionada"
        ],
        "views": [
            "vw_nome_view"
        ],
        "requires_members": false
    },

    "features": [
        "Feature 1 do módulo",
        "Feature 2 do módulo",
        "Feature 3 do módulo"
    ],

    "permissions": {
        "admin": true,
        "public": true,
        "members_only": false
    },

    "installation": {
        "schemas": {
            "mysql": "database/mysql-schema.sql",
            "supabase": "database/supabase-schema.sql"
        },
        "rollback": "database/rollback.sql",
        "auto_install": true
    },

    "menu": {
        "admin": [
            {
                "label": "Item Admin 1",
                "route": "/admin/nome_modulo/rota",
                "icon": "📄"
            }
        ],
        "public": [
            {
                "label": "Item Público",
                "route": "/nome_modulo",
                "icon": "📚"
            }
        ]
    },

    "configuration": {
        "items_per_page": 10,
        "max_file_size": 5242880,
        "allowed_file_types": ["jpg", "jpeg", "png", "webp"]
    }
}
```

### Campos Obrigatórios (Mínimo)

```json
{
    "name": "nome_modulo",
    "label": "Nome do Módulo",
    "version": "1.0.0",
    "public": false,
    "public_url": "/nome_modulo",
    "dependencies": {
        "tables": []
    }
}
```

### Descrição dos Campos Principais

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `name` | string | ✅ | Identificador único (slug) do módulo |
| `label` | string | ✅ | Nome legível exibido no menu |
| `version` | string | ✅ | Versão semântica (X.Y.Z) |
| `public` | boolean | ✅ | Define se módulo é acessível sem login |
| `public_url` | string | ✅ | URL principal do módulo |
| `dependencies.tables` | array | ✅ | Lista de tabelas criadas pelo módulo |
| `dependencies.views` | array | ❌ | Lista de views SQL do módulo |
| `dependencies.requires_members` | boolean | ❌ | Exige sistema de membros habilitado |
| `installation.auto_install` | boolean | ❌ | Se `true`, executa schema automaticamente |
| `menu.admin` | array | ❌ | Itens de menu para área admin |
| `menu.public` | array | ❌ | Itens de menu para área pública |

---

## 4. Database Schemas

### 4.1. MySQL Schema (`database/mysql-schema.sql`)

```sql
-- =====================================================
-- AEGIS Framework - Nome do Módulo
-- MySQL Schema
-- Version: 1.0.0
-- Compatível: AEGIS v14+
-- =====================================================

-- Tabela principal
CREATE TABLE IF NOT EXISTS tbl_nome_principal (
    id VARCHAR(36) PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_nome_slug (slug),
    INDEX idx_nome_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela relacionada (se necessário)
CREATE TABLE IF NOT EXISTS tbl_nome_relacionada (
    id VARCHAR(36) PRIMARY KEY,
    principal_id VARCHAR(36) NOT NULL,
    conteudo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (principal_id) REFERENCES tbl_nome_principal(id) ON DELETE CASCADE,
    INDEX idx_relacionada_principal (principal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Regras MySQL:**
- ✅ UUID como `VARCHAR(36)`
- ✅ Sempre usar `IF NOT EXISTS`
- ✅ Engine `InnoDB` + charset `utf8mb4_unicode_ci`
- ✅ Índices em campos de busca/FK
- ✅ `ON DELETE CASCADE` para relacionamentos

### 4.2. Supabase Schema (`database/supabase-schema.sql`)

```sql
-- =====================================================
-- AEGIS Framework - Nome do Módulo
-- Supabase/PostgreSQL Schema
-- Version: 1.0.0
-- Compatível: AEGIS v14+
-- =====================================================

-- Tabela principal
CREATE TABLE IF NOT EXISTS tbl_nome_principal (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    descricao TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_nome_slug ON tbl_nome_principal(slug);
CREATE INDEX IF NOT EXISTS idx_nome_created ON tbl_nome_principal(created_at);

-- Trigger para updated_at automático
CREATE OR REPLACE FUNCTION update_nome_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_nome_updated_at
    BEFORE UPDATE ON tbl_nome_principal
    FOR EACH ROW
    EXECUTE FUNCTION update_nome_updated_at();

-- Tabela relacionada
CREATE TABLE IF NOT EXISTS tbl_nome_relacionada (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    principal_id UUID NOT NULL,
    conteudo TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    FOREIGN KEY (principal_id) REFERENCES tbl_nome_principal(id) ON DELETE CASCADE
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_relacionada_principal ON tbl_nome_relacionada(principal_id);
```

**Regras Supabase:**
- ✅ UUID nativo com `gen_random_uuid()`
- ✅ `TIMESTAMP WITH TIME ZONE`
- ✅ Trigger para `updated_at` (não existe `ON UPDATE` no PostgreSQL)
- ✅ Índices criados separadamente (não inline)
- ✅ Sempre usar `IF NOT EXISTS`

### 4.3. Rollback Schema (`database/rollback.sql`)

```sql
-- =====================================================
-- AEGIS Framework - Nome do Módulo
-- Rollback/Uninstall Script
-- Version: 1.0.0
-- =====================================================

-- Remover tabelas na ordem correta (dependências primeiro)
DROP TABLE IF EXISTS tbl_nome_relacionada;
DROP TABLE IF EXISTS tbl_nome_principal;

-- Remover views (se existirem)
DROP VIEW IF EXISTS vw_nome_view;
```

**Regras Rollback:**
- ✅ Ordem reversa: tabelas dependentes primeiro
- ✅ Sempre usar `IF EXISTS`
- ✅ Remover views antes de tabelas

---

## 5. Sistema de Rotas (routes.php)

### Estrutura Completa

```php
<?php
/**
 * AEGIS Framework - Nome do Módulo Routes
 * Version: 1.0.0
 */

// =========================================
// HELPER: Verificar se módulo é público
// =========================================
if (!function_exists('checkModuleAccess')) {
    function checkModuleAccess($moduleName) {
        // Se MEMBERS desabilitado, libera acesso
        if (!ENABLE_MEMBERS) {
            return true;
        }

        // Ler module.json do módulo
        $moduleJsonPath = ROOT_PATH . "modules/{$moduleName}/module.json";

        if (!file_exists($moduleJsonPath)) {
            http_response_code(404);
            echo "<!DOCTYPE html>";
            echo "<html lang='pt-BR'><head><meta charset='UTF-8'>";
            echo "<title>Módulo Não Encontrado</title>";
            echo "<style>body{font-family:sans-serif;text-align:center;padding:50px;}</style>";
            echo "</head><body>";
            echo "<h1>404</h1><p>Módulo não encontrado.</p>";
            echo "<a href='" . url('/') . "'>Voltar</a>";
            echo "</body></html>";
            exit;
        }

        $json = file_get_contents($moduleJsonPath);
        $metadata = json_decode($json, true);

        if (!$metadata) {
            http_response_code(500);
            exit('Erro ao ler configuração do módulo');
        }

        $isPublic = ($metadata['public'] ?? false);

        if ($isPublic) {
            // Módulo público: libera acesso sem login
            return true;
        }

        // Módulo privado: exige autenticação
        MemberAuth::require();
        return true;
    }
}

// =====================================================
// ADMIN ROUTES (Authenticated)
// =====================================================

// Listagem
Router::get('/admin/nome_modulo', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminNomeController.php';
    $controller = new AdminNomeController();
    $controller->index();
});

// Novo (formulário)
Router::get('/admin/nome_modulo/novo', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminNomeController.php';
    $controller = new AdminNomeController();
    $controller->novo();
});

// Criar (processar formulário)
Router::post('/admin/nome_modulo/criar', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminNomeController.php';
    $controller = new AdminNomeController();
    $controller->criar();
});

// Editar (formulário)
Router::get('/admin/nome_modulo/editar/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminNomeController.php';
    $controller = new AdminNomeController();
    $controller->editar($id);
});

// Atualizar (processar formulário)
Router::post('/admin/nome_modulo/atualizar/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminNomeController.php';
    $controller = new AdminNomeController();
    $controller->atualizar($id);
});

// Excluir
Router::post('/admin/nome_modulo/excluir/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminNomeController.php';
    $controller = new AdminNomeController();
    $controller->excluir($id);
});

// =====================================================
// PUBLIC ROUTES (Open to everyone if module.public=true)
// =====================================================

// Listagem pública
Router::get('/nome_modulo', function() {
    checkModuleAccess('nome_modulo');
    require_once __DIR__ . '/controllers/PublicNomeController.php';
    $controller = new PublicNomeController();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $controller->index($page);
});

// Detalhes (DEVE vir por último para não capturar rotas acima)
Router::get('/nome_modulo/:slug', function($slug) {
    checkModuleAccess('nome_modulo');
    require_once __DIR__ . '/controllers/PublicNomeController.php';
    $controller = new PublicNomeController();
    $controller->detalhes($slug);
});
```

### ⚠️ REGRAS CRÍTICAS SOBRE ROTAS

#### 1. Prefixo Obrigatório em Rotas Públicas

**TODOS os módulos (exceto Blog) DEVEM usar prefixo `/{nome_modulo}/` em TODAS as rotas públicas.**

✅ **Correto:**
- `/palpites/exibicao-palpites`
- `/cursos/aula-01`
- `/artigos/titulo-do-artigo`

❌ **ERRADO:**
- `/exibicao-palpites` (sem prefixo)
- `/aula-01` (sem prefixo)

**Exceção:** Apenas o módulo `blog` pode ter rotas sem prefixo (devido a SEO).

#### 2. Ordem das Rotas com Parâmetros

Rotas com `:slug` ou `:id` **DEVEM vir por último** para não capturar rotas específicas:

```php
// ✅ CORRETO
Router::get('/artigos/buscar', ...);        // Específica primeiro
Router::get('/artigos/:slug', ...);         // Genérica por último

// ❌ ERRADO
Router::get('/artigos/:slug', ...);         // Captura tudo, inclusive /buscar
Router::get('/artigos/buscar', ...);        // Nunca será executada
```

#### 3. Helper `checkModuleAccess()`

- ✅ Sempre incluir no início do `routes.php`
- ✅ Chamar em TODAS as rotas públicas
- ✅ Passar o **nome do módulo** (ex: `'artigos'`)
- ❌ NÃO passar slug de página (ex: ~~`'artigos/titulo'`~~)

---

## 6. Controllers

### 6.1. Admin Controller

```php
<?php
/**
 * AEGIS Framework - Nome do Módulo
 * Admin Controller
 * Version: 1.0.0
 */

class AdminNomeController {

    /**
     * Listagem de itens (com paginação)
     */
    public function index() {
        $db = DB::connect();

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Buscar itens
        $itens = $db->query("
            SELECT *
            FROM tbl_nome_principal
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        // Contar total
        $totalResult = $db->query("SELECT COUNT(*) as total FROM tbl_nome_principal");
        $total = $totalResult[0]['total'] ?? 0;
        $totalPages = ceil($total / $perPage);

        require __DIR__ . '/../views/admin/index.php';
    }

    /**
     * Formulário de novo item
     */
    public function novo() {
        require __DIR__ . '/../views/admin/novo.php';
    }

    /**
     * Criar novo item (processar formulário)
     */
    public function criar() {
        // Validar CSRF
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        $db = DB::connect();

        // Sanitizar dados
        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');

        // Validações
        if (empty($titulo)) {
            Session::set('error', 'Título é obrigatório');
            redirect('/admin/nome_modulo/novo');
            return;
        }

        // Gerar slug
        $slug = Core::generateSlug($titulo);

        // Verificar slug único
        $existente = $db->select('tbl_nome_principal', ['slug' => $slug]);
        if (!empty($existente)) {
            Session::set('error', 'Já existe um item com este título');
            redirect('/admin/nome_modulo/novo');
            return;
        }

        // Gerar UUID
        $id = Core::generateUUID();

        // Inserir
        $db->insert('tbl_nome_principal', [
            'id' => $id,
            'titulo' => $titulo,
            'slug' => $slug,
            'descricao' => $descricao
        ]);

        Session::set('success', 'Item criado com sucesso!');
        redirect('/admin/nome_modulo');
    }

    /**
     * Formulário de edição
     */
    public function editar($id) {
        $db = DB::connect();

        $item = $db->select('tbl_nome_principal', ['id' => $id]);

        if (empty($item)) {
            Session::set('error', 'Item não encontrado');
            redirect('/admin/nome_modulo');
            return;
        }

        $item = $item[0];
        require __DIR__ . '/../views/admin/editar.php';
    }

    /**
     * Atualizar item (processar formulário)
     */
    public function atualizar($id) {
        // Validar CSRF
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        $db = DB::connect();

        // Verificar se existe
        $item = $db->select('tbl_nome_principal', ['id' => $id]);
        if (empty($item)) {
            Session::set('error', 'Item não encontrado');
            redirect('/admin/nome_modulo');
            return;
        }

        // Sanitizar dados
        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');

        // Validações
        if (empty($titulo)) {
            Session::set('error', 'Título é obrigatório');
            redirect('/admin/nome_modulo/editar/' . $id);
            return;
        }

        // Gerar slug
        $slug = Core::generateSlug($titulo);

        // Verificar slug único (exceto o próprio)
        $existente = $db->query(
            "SELECT id FROM tbl_nome_principal WHERE slug = ? AND id != ?",
            [$slug, $id]
        );
        if (!empty($existente)) {
            Session::set('error', 'Já existe um item com este título');
            redirect('/admin/nome_modulo/editar/' . $id);
            return;
        }

        // Atualizar
        $db->update('tbl_nome_principal', [
            'titulo' => $titulo,
            'slug' => $slug,
            'descricao' => $descricao
        ], ['id' => $id]);

        Session::set('success', 'Item atualizado com sucesso!');
        redirect('/admin/nome_modulo');
    }

    /**
     * Excluir item
     */
    public function excluir($id) {
        // Validar CSRF
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        $db = DB::connect();

        // Verificar se existe
        $item = $db->select('tbl_nome_principal', ['id' => $id]);
        if (empty($item)) {
            Session::set('error', 'Item não encontrado');
            redirect('/admin/nome_modulo');
            return;
        }

        // Deletar
        $db->delete('tbl_nome_principal', ['id' => $id]);

        Session::set('success', 'Item excluído com sucesso!');
        redirect('/admin/nome_modulo');
    }
}
```

### 6.2. Public Controller

```php
<?php
/**
 * AEGIS Framework - Nome do Módulo
 * Public Controller
 * Version: 1.0.0
 */

class PublicNomeController {

    /**
     * Listagem pública (com paginação)
     */
    public function index($page = 1) {
        $db = DB::connect();

        $page = max(1, (int)$page);
        $perPage = 9;
        $offset = ($page - 1) * $perPage;

        // Buscar itens
        $itens = $db->query("
            SELECT *
            FROM tbl_nome_principal
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        // Contar total
        $totalResult = $db->query("SELECT COUNT(*) as total FROM tbl_nome_principal");
        $total = $totalResult[0]['total'] ?? 0;
        $totalPages = ceil($total / $perPage);

        require __DIR__ . '/../views/public/index.php';
    }

    /**
     * Página de detalhes
     */
    public function detalhes($slug) {
        $db = DB::connect();

        // Buscar item
        $item = $db->query("SELECT * FROM tbl_nome_principal WHERE slug = ?", [$slug]);

        if (empty($item)) {
            http_response_code(404);
            echo "Item não encontrado";
            exit;
        }
        $item = $item[0];

        require __DIR__ . '/../views/public/detalhes.php';
    }
}
```

---

## 7. Views

### 7.1. Admin View - Listagem

```php
<?php
/**
 * View: Admin - Listagem de Itens
 */

// Cabeçalho Admin
require ROOT_PATH . 'admin/views/layouts/header.php';
?>

<div class="content-wrapper">
    <div class="page-header">
        <h2>Gerenciar Itens</h2>
        <a href="<?= url('/admin/nome_modulo/novo') ?>" class="btn btn-primary">
            Novo Item
        </a>
    </div>

    <?php if (Session::has('success')): ?>
        <div class="alert alert-success">
            <?= Session::get('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Session::has('error')): ?>
        <div class="alert alert-error">
            <?= Session::get('error') ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Slug</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center">
                            Nenhum item encontrado
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['titulo']) ?></td>
                            <td><?= htmlspecialchars($item['slug']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                            <td>
                                <a href="<?= url('/admin/nome_modulo/editar/' . $item['id']) ?>"
                                   class="btn btn-sm btn-secondary">
                                    Editar
                                </a>
                                <form method="POST"
                                      action="<?= url('/admin/nome_modulo/excluir/' . $item['id']) ?>"
                                      style="display:inline"
                                      onsubmit="return confirm('Tem certeza?')">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= Security::generateCSRF() ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= url('/admin/nome_modulo?page=' . $i) ?>"
                   class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Rodapé Admin
require ROOT_PATH . 'admin/views/layouts/footer.php';
?>
```

### 7.2. Public View - Listagem

```php
<?php
/**
 * View: Public - Listagem de Itens
 */

// Cabeçalho Público
require ROOT_PATH . 'frontend/layouts/header.php';
?>

<div class="container">
    <h1>Itens</h1>

    <div class="items-grid">
        <?php if (empty($itens)): ?>
            <p>Nenhum item encontrado.</p>
        <?php else: ?>
            <?php foreach ($itens as $item): ?>
                <div class="item-card">
                    <h3>
                        <a href="<?= url('/nome_modulo/' . $item['slug']) ?>">
                            <?= htmlspecialchars($item['titulo']) ?>
                        </a>
                    </h3>
                    <p><?= nl2br(htmlspecialchars($item['descricao'])) ?></p>
                    <small><?= date('d/m/Y', strtotime($item['created_at'])) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= url('/nome_modulo?page=' . $i) ?>"
                   class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Rodapé Público
require ROOT_PATH . 'frontend/layouts/footer.php';
?>
```

---

## 8. Padrão de Acesso Público/Privado

### Fonte de Verdade: `module.json`

O controle de acesso de um módulo é definido **exclusivamente** no campo `"public"` do `module.json`.

```json
{
    "public": false  // Módulo privado (exige login)
}
```

```json
{
    "public": true   // Módulo público (acesso livre)
}
```

### Comportamento

#### Quando `ENABLE_MEMBERS = false` (sem sistema de membros)
- **Todas as rotas públicas são liberadas automaticamente**
- Não verifica permissões
- Acesso totalmente público

#### Quando `ENABLE_MEMBERS = true` e `"public": true`
- **Acesso liberado sem login**
- Qualquer pessoa pode acessar
- Menu aparece para todos
- Ideal para conteúdo público (blog, landing pages)

#### Quando `ENABLE_MEMBERS = true` e `"public": false`
- **Exige login (MemberAuth::require())**
- Menu só aparece para usuários autenticados
- Acesso controlado por autenticação

### Helper `checkModuleAccess()`

Este helper é **OBRIGATÓRIO** em todas as rotas públicas:

```php
Router::get('/nome_modulo', function() {
    checkModuleAccess('nome_modulo');  // ← OBRIGATÓRIO
    // ... resto da rota
});
```

**O que o helper faz:**
1. Verifica se `ENABLE_MEMBERS` está desabilitado → libera acesso
2. Lê o `module.json` do módulo
3. Verifica campo `"public"`
4. Se `public: true` → libera acesso
5. Se `public: false` → chama `MemberAuth::require()` (exige login)

### ⚠️ NÃO Criar Páginas na Tabela `pages`

**MÓDULOS ≠ PÁGINAS**

- ✅ Módulos: controlados por `module.json`
- ✅ Páginas estáticas: controladas por tabela `pages`
- ❌ NUNCA misturar os dois conceitos
- ❌ NÃO criar registros na tabela `pages` para módulos

---

## 9. Processo de Instalação

### Como o ModuleInstaller Funciona

Quando você clica em "Instalar" no `/admin/modules`, o sistema executa:

```
1. Validar módulo
   └─ Verifica se pasta modules/{nome_modulo}/ existe
   └─ Valida module.json

2. Ler metadados
   └─ Parse do module.json
   └─ Extrair configurações

3. Validar requisitos
   └─ Verificar database disponível (se necessário)
   └─ Verificar ENABLE_MEMBERS (se requires_members: true)

4. Executar schema SQL
   └─ Detectar DB_TYPE (mysql/supabase)
   └─ Carregar database/{DB_TYPE}-schema.sql
   └─ Executar queries separadas por ponto-e-vírgula
   └─ Criar tabelas/views/triggers

5. Registrar módulo
   └─ INSERT/UPDATE na tabela modules
   └─ Gravar is_public, version, label

6. Criar menu item
   └─ INSERT na tabela menu_items
   └─ Tipo 'module' (diferente de tipo 'page')
   └─ page_slug = null (módulos não usam pages)

7. Adicionar à lista de instalados
   └─ Atualizar INSTALLED_MODULES em .env ou _config.php
   └─ Formato: 'blog,palpites,artigos'

8. Invalidar cache
   └─ Limpar cache de módulos instalados
   └─ Limpar cache de permissões

9. Auto-bump version
   └─ Incrementar versão do AEGIS automaticamente
```

### Arquivos Modificados na Instalação

1. **`.env`** ou **`_config.php`**
   ```
   INSTALLED_MODULES=blog,palpites,artigos,novo_modulo
   ```

2. **Tabela `modules`**
   ```sql
   INSERT INTO modules (name, label, version, is_public, is_active)
   VALUES ('novo_modulo', 'Novo Módulo', '1.0.0', 0, 1);
   ```

3. **Tabela `menu_items`**
   ```sql
   INSERT INTO menu_items (id, label, type, module_name, url, icon, ...)
   VALUES (UUID(), 'Novo Módulo', 'module', 'novo_modulo', '/novo_modulo', 'box', ...);
   ```

4. **Tabelas do módulo** (via schema SQL)

### Erros Comuns na Instalação

| Erro | Causa | Solução |
|------|-------|---------|
| "module.json inválido" | JSON mal formatado | Validar JSON em jsonlint.com |
| "Schema para mysql não encontrado" | Arquivo ausente | Criar `database/mysql-schema.sql` |
| "Tabelas não foram criadas" | Erro SQL | Verificar syntax do schema |
| "Este módulo requer banco" | DB_TYPE=none | Configurar database em _config.php |
| "Módulo já instalado" | Já existe em INSTALLED_MODULES | Desinstalar antes de reinstalar |

---

## 10. Processo de Desinstalação

### Como o ModuleUninstaller Funciona

Quando você clica em "Desinstalar" no `/admin/modules`:

```
1. Confirmação obrigatória
   └─ Checkbox + modal de confirmação
   └─ Evita desinstalação acidental

2. Ler metadados
   └─ Carregar module.json
   └─ Obter lista de tabelas/views

3. Remover views SQL (se existirem)
   └─ DROP VIEW IF EXISTS vw_nome

4. Remover tabelas
   └─ Ordem reversa (dependências primeiro)
   └─ DROP TABLE IF EXISTS tbl_nome
   └─ Usar transação se MySQL
   └─ SET FOREIGN_KEY_CHECKS = 0 (temporariamente)

5. Verificar deleção
   └─ SELECT 1 FROM tabela (deve dar erro)
   └─ Se tabela ainda existe → ERRO e abortar

6. Remover menu item
   └─ DELETE FROM menu_items WHERE module_name = 'nome'

7. Remover da lista de instalados
   └─ Retirar de INSTALLED_MODULES
   └─ Atualizar .env ou _config.php

8. Invalidar cache
   └─ Limpar todos os caches relacionados

9. Auto-bump version
   └─ Incrementar versão do AEGIS
```

### Casos Especiais: Supabase

Para Supabase (PostgreSQL), o sistema usa **verificação assíncrona**:

1. Usuário clica em "Desinstalar"
2. Sistema executa DROP TABLE
3. **Importante**: No Supabase, pode haver delay na deleção
4. Sistema verifica se tabelas foram deletadas
5. Se ainda existirem → mostra mensagem:
   ```
   "Aguardando confirmação do Supabase.
   Tabelas restantes: tbl_nome_1, tbl_nome_2
   Clique em 'Verificar Novamente' em 10 segundos"
   ```
6. Usuário clica em "Verificar Novamente"
7. Sistema refaz SELECT nas tabelas
8. Se não existirem → finaliza desinstalação

### Rollback Manual (Se Necessário)

Se a desinstalação falhar:

```bash
# 1. Executar rollback SQL manualmente
mysql -u root -p database_name < modules/nome_modulo/database/rollback.sql

# 2. Remover de INSTALLED_MODULES manualmente
# Editar .env ou _config.php e remover 'nome_modulo' da lista

# 3. Limpar menu_items
DELETE FROM menu_items WHERE module_name = 'nome_modulo';

# 4. Limpar modules
DELETE FROM modules WHERE name = 'nome_modulo';
```

---

## 11. Checklist Completo

### ✅ Antes de Começar

- [ ] Definir nome do módulo (lowercase, sem espaços)
- [ ] Definir se será público ou privado
- [ ] Listar todas as tabelas necessárias
- [ ] Desenhar relacionamentos (ER Diagram)
- [ ] Definir rotas principais (admin + public)

### ✅ Estrutura de Pastas

- [ ] Criar pasta `modules/nome_modulo/`
- [ ] Criar `module.json` completo
- [ ] Criar `README.md` com documentação
- [ ] Criar `routes.php`
- [ ] Criar `controllers/AdminNomeController.php`
- [ ] Criar `controllers/PublicNomeController.php`
- [ ] Criar `views/admin/` (index, novo, editar)
- [ ] Criar `views/public/` (index, detalhes)
- [ ] Criar `database/mysql-schema.sql`
- [ ] Criar `database/supabase-schema.sql`
- [ ] Criar `database/rollback.sql`
- [ ] (Opcional) Criar `assets/` (css, js, images)

### ✅ module.json

- [ ] Campo `name` correto
- [ ] Campo `label` legível
- [ ] Campo `version` semântico (X.Y.Z)
- [ ] Campo `public` definido (true/false)
- [ ] Campo `public_url` com prefixo `/{nome_modulo}/`
- [ ] Campo `dependencies.tables` listando TODAS as tabelas
- [ ] Campo `dependencies.views` (se houver views SQL)
- [ ] Campo `menu.admin` com itens de menu
- [ ] Campo `menu.public` (se módulo público)
- [ ] Validar JSON em jsonlint.com

### ✅ Database Schemas

**MySQL:**
- [ ] UUID como `VARCHAR(36)`
- [ ] `IF NOT EXISTS` em todos CREATE
- [ ] Engine `InnoDB`
- [ ] Charset `utf8mb4_unicode_ci`
- [ ] Índices em campos de busca
- [ ] `ON DELETE CASCADE` em FKs

**Supabase:**
- [ ] UUID nativo `gen_random_uuid()`
- [ ] `TIMESTAMP WITH TIME ZONE`
- [ ] Trigger para `updated_at`
- [ ] Índices criados separadamente
- [ ] `IF NOT EXISTS` em todos CREATE

**Rollback:**
- [ ] Ordem reversa (dependências primeiro)
- [ ] `IF EXISTS` em todos DROP
- [ ] Views removidas antes de tabelas

### ✅ Rotas (routes.php)

- [ ] Helper `checkModuleAccess()` incluído
- [ ] Rotas admin com `Auth::require()`
- [ ] Rotas públicas com `checkModuleAccess('nome_modulo')`
- [ ] Prefixo `/{nome_modulo}/` em TODAS rotas públicas
- [ ] Rotas com `:slug` por último (ordem importa!)
- [ ] CSRF protection em todos POSTs

### ✅ Controllers

**AdminController:**
- [ ] Método `index()` com paginação
- [ ] Método `novo()` (formulário)
- [ ] Método `criar()` com validações + CSRF
- [ ] Método `editar($id)` (formulário)
- [ ] Método `atualizar($id)` com validações + CSRF
- [ ] Método `excluir($id)` com CSRF
- [ ] Todas inputs sanitizadas via `Security::sanitize()`
- [ ] UUIDs gerados via `Core::generateUUID()`
- [ ] Slugs gerados via `Core::generateSlug()`

**PublicController:**
- [ ] Método `index($page)` com paginação
- [ ] Método `detalhes($slug)`
- [ ] Validações de entrada
- [ ] Tratamento de 404

### ✅ Views

**Admin:**
- [ ] Layout com `admin/views/layouts/header.php`
- [ ] Listagem com tabela + paginação
- [ ] Formulário novo com CSRF token
- [ ] Formulário editar com CSRF token
- [ ] Confirmação em exclusões
- [ ] Mensagens de sucesso/erro via Session

**Public:**
- [ ] Layout com `frontend/layouts/header.php`
- [ ] Listagem responsiva
- [ ] Página de detalhes
- [ ] Tratamento de casos vazios

### ✅ Testes

- [ ] Testar instalação do módulo
- [ ] Verificar criação de tabelas no banco
- [ ] Testar CRUD completo no admin
- [ ] Testar rotas públicas (autenticado e não autenticado)
- [ ] Testar com `ENABLE_MEMBERS = false`
- [ ] Testar com `ENABLE_MEMBERS = true` e `public: false`
- [ ] Testar com `ENABLE_MEMBERS = true` e `public: true`
- [ ] Testar paginação (criar 20+ itens)
- [ ] Testar validações (campos vazios, duplicados)
- [ ] Testar desinstalação
- [ ] Verificar remoção de tabelas após desinstalar

### ✅ Segurança

- [ ] CSRF protection em todos formulários
- [ ] Sanitização de inputs (`Security::sanitize()`)
- [ ] Validação de UUIDs em URLs
- [ ] Escape de outputs (`htmlspecialchars()`)
- [ ] Autenticação em rotas admin (`Auth::require()`)
- [ ] Verificação de acesso em rotas públicas (`checkModuleAccess()`)

### ✅ Documentação

- [ ] README.md no módulo
- [ ] Comentários em controllers
- [ ] Comentários em views complexas
- [ ] Atualizar docs/aegis-modules.md (adicionar novo módulo)

---

## 12. Exemplo Prático: Criar Módulo "Cursos"

Vamos criar um módulo completo de Cursos passo-a-passo.

### Requisitos:

- Listagem de cursos
- Página individual de cada curso
- Admin para criar/editar/excluir cursos
- Campos: título, slug, descrição, instrutor, carga horária, imagem
- Módulo **público** (acessível sem login)

### Passo 1: Criar Estrutura de Pastas

```bash
mkdir -p modules/cursos/controllers
mkdir -p modules/cursos/views/admin
mkdir -p modules/cursos/views/public
mkdir -p modules/cursos/database
```

### Passo 2: Criar `module.json`

```json
{
    "name": "cursos",
    "label": "Cursos Online",
    "title": "Cursos Online",
    "description": "Sistema de cursos online com listagem e detalhes",
    "version": "1.0.0",
    "author": "AEGIS Framework",
    "public": true,
    "public_url": "/cursos",
    "homepage": "/cursos",
    "adminRoute": "/admin/cursos",
    "dependencies": {
        "core": [
            "DB",
            "Security",
            "Auth",
            "Upload",
            "Core"
        ],
        "tables": [
            "tbl_cursos"
        ],
        "requires_members": false
    },
    "features": [
        "CRUD completo de cursos",
        "Upload de imagem destacada",
        "Slug SEO-friendly",
        "Paginação automática",
        "Listagem pública"
    ],
    "permissions": {
        "admin": true,
        "public": true,
        "members_only": false
    },
    "installation": {
        "schemas": {
            "mysql": "database/mysql-schema.sql",
            "supabase": "database/supabase-schema.sql"
        },
        "rollback": "database/rollback.sql",
        "auto_install": true
    },
    "menu": {
        "admin": [
            {
                "label": "Cursos",
                "route": "/admin/cursos",
                "icon": "🎓"
            }
        ],
        "public": [
            {
                "label": "Cursos",
                "route": "/cursos",
                "icon": "📚"
            }
        ]
    },
    "configuration": {
        "cursos_per_page": 9,
        "max_image_size": 5242880,
        "allowed_image_types": [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ]
    }
}
```

### Passo 3: Criar Schema MySQL

**`database/mysql-schema.sql`:**

```sql
-- =====================================================
-- AEGIS Framework - Cursos Module
-- MySQL Schema
-- Version: 1.0.0
-- =====================================================

CREATE TABLE IF NOT EXISTS tbl_cursos (
    id VARCHAR(36) PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    descricao TEXT NOT NULL,
    instrutor VARCHAR(255) NOT NULL,
    carga_horaria INT NOT NULL COMMENT 'Em horas',
    imagem VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cursos_slug (slug),
    INDEX idx_cursos_ativo (ativo),
    INDEX idx_cursos_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Passo 4: Criar Schema Supabase

**`database/supabase-schema.sql`:**

```sql
-- =====================================================
-- AEGIS Framework - Cursos Module
-- Supabase/PostgreSQL Schema
-- Version: 1.0.0
-- =====================================================

CREATE TABLE IF NOT EXISTS tbl_cursos (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    descricao TEXT NOT NULL,
    instrutor VARCHAR(255) NOT NULL,
    carga_horaria INT NOT NULL,
    imagem VARCHAR(255),
    ativo BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_cursos_slug ON tbl_cursos(slug);
CREATE INDEX IF NOT EXISTS idx_cursos_ativo ON tbl_cursos(ativo);
CREATE INDEX IF NOT EXISTS idx_cursos_created ON tbl_cursos(created_at);

CREATE OR REPLACE FUNCTION update_cursos_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_cursos_updated_at
    BEFORE UPDATE ON tbl_cursos
    FOR EACH ROW
    EXECUTE FUNCTION update_cursos_updated_at();
```

### Passo 5: Criar Rollback SQL

**`database/rollback.sql`:**

```sql
-- =====================================================
-- AEGIS Framework - Cursos Module
-- Rollback/Uninstall Script
-- Version: 1.0.0
-- =====================================================

DROP TABLE IF EXISTS tbl_cursos;
```

### Passo 6: Criar Routes

**`routes.php`:**

```php
<?php
/**
 * AEGIS Framework - Cursos Module Routes
 * Version: 1.0.0
 */

// Helper de acesso
if (!function_exists('checkModuleAccess')) {
    function checkModuleAccess($moduleName) {
        if (!ENABLE_MEMBERS) {
            return true;
        }

        $moduleJsonPath = ROOT_PATH . "modules/{$moduleName}/module.json";
        if (!file_exists($moduleJsonPath)) {
            http_response_code(404);
            exit('Módulo não encontrado');
        }

        $json = file_get_contents($moduleJsonPath);
        $metadata = json_decode($json, true);

        if (!$metadata) {
            http_response_code(500);
            exit('Erro ao ler configuração do módulo');
        }

        $isPublic = ($metadata['public'] ?? false);

        if ($isPublic) {
            return true;
        }

        MemberAuth::require();
        return true;
    }
}

// =====================================================
// ADMIN ROUTES
// =====================================================

Router::get('/admin/cursos', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCursosController.php';
    $controller = new AdminCursosController();
    $controller->index();
});

Router::get('/admin/cursos/novo', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCursosController.php';
    $controller = new AdminCursosController();
    $controller->novo();
});

Router::post('/admin/cursos/criar', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCursosController.php';
    $controller = new AdminCursosController();
    $controller->criar();
});

Router::get('/admin/cursos/editar/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCursosController.php';
    $controller = new AdminCursosController();
    $controller->editar($id);
});

Router::post('/admin/cursos/atualizar/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCursosController.php';
    $controller = new AdminCursosController();
    $controller->atualizar($id);
});

Router::post('/admin/cursos/excluir/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCursosController.php';
    $controller = new AdminCursosController();
    $controller->excluir($id);
});

// =====================================================
// PUBLIC ROUTES
// =====================================================

Router::get('/cursos', function() {
    checkModuleAccess('cursos');
    require_once __DIR__ . '/controllers/PublicCursosController.php';
    $controller = new PublicCursosController();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $controller->index($page);
});

Router::get('/cursos/:slug', function($slug) {
    checkModuleAccess('cursos');
    require_once __DIR__ . '/controllers/PublicCursosController.php';
    $controller = new PublicCursosController();
    $controller->detalhes($slug);
});
```

### Passo 7: Criar AdminCursosController

**`controllers/AdminCursosController.php`:**

```php
<?php
class AdminCursosController {

    public function index() {
        $db = DB::connect();
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $cursos = $db->query("
            SELECT * FROM tbl_cursos
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        $totalResult = $db->query("SELECT COUNT(*) as total FROM tbl_cursos");
        $total = $totalResult[0]['total'] ?? 0;
        $totalPages = ceil($total / $perPage);

        require __DIR__ . '/../views/admin/index.php';
    }

    public function novo() {
        require __DIR__ . '/../views/admin/novo.php';
    }

    public function criar() {
        Security::validateCSRF($_POST['csrf_token'] ?? '');
        $db = DB::connect();

        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        $instrutor = Security::sanitize($_POST['instrutor'] ?? '');
        $cargaHoraria = (int)($_POST['carga_horaria'] ?? 0);

        if (empty($titulo) || empty($descricao) || empty($instrutor) || $cargaHoraria <= 0) {
            Session::set('error', 'Preencha todos os campos obrigatórios');
            redirect('/admin/cursos/novo');
            return;
        }

        $slug = Core::generateSlug($titulo);
        $existente = $db->select('tbl_cursos', ['slug' => $slug]);
        if (!empty($existente)) {
            Session::set('error', 'Já existe um curso com este título');
            redirect('/admin/cursos/novo');
            return;
        }

        // Upload de imagem (se houver)
        $imagem = null;
        if (!empty($_FILES['imagem']['name'])) {
            $uploadResult = Upload::image($_FILES['imagem'], 'cursos', 5242880);
            if ($uploadResult['success']) {
                $imagem = $uploadResult['file_path'];
            }
        }

        $id = Core::generateUUID();
        $db->insert('tbl_cursos', [
            'id' => $id,
            'titulo' => $titulo,
            'slug' => $slug,
            'descricao' => $descricao,
            'instrutor' => $instrutor,
            'carga_horaria' => $cargaHoraria,
            'imagem' => $imagem,
            'ativo' => 1
        ]);

        Session::set('success', 'Curso criado com sucesso!');
        redirect('/admin/cursos');
    }

    public function editar($id) {
        $db = DB::connect();
        $curso = $db->select('tbl_cursos', ['id' => $id]);

        if (empty($curso)) {
            Session::set('error', 'Curso não encontrado');
            redirect('/admin/cursos');
            return;
        }

        $curso = $curso[0];
        require __DIR__ . '/../views/admin/editar.php';
    }

    public function atualizar($id) {
        Security::validateCSRF($_POST['csrf_token'] ?? '');
        $db = DB::connect();

        $curso = $db->select('tbl_cursos', ['id' => $id]);
        if (empty($curso)) {
            Session::set('error', 'Curso não encontrado');
            redirect('/admin/cursos');
            return;
        }

        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        $instrutor = Security::sanitize($_POST['instrutor'] ?? '');
        $cargaHoraria = (int)($_POST['carga_horaria'] ?? 0);

        if (empty($titulo) || empty($descricao) || empty($instrutor) || $cargaHoraria <= 0) {
            Session::set('error', 'Preencha todos os campos obrigatórios');
            redirect('/admin/cursos/editar/' . $id);
            return;
        }

        $slug = Core::generateSlug($titulo);
        $existente = $db->query("SELECT id FROM tbl_cursos WHERE slug = ? AND id != ?", [$slug, $id]);
        if (!empty($existente)) {
            Session::set('error', 'Já existe um curso com este título');
            redirect('/admin/cursos/editar/' . $id);
            return;
        }

        $dados = [
            'titulo' => $titulo,
            'slug' => $slug,
            'descricao' => $descricao,
            'instrutor' => $instrutor,
            'carga_horaria' => $cargaHoraria
        ];

        // Upload de nova imagem (se houver)
        if (!empty($_FILES['imagem']['name'])) {
            $uploadResult = Upload::image($_FILES['imagem'], 'cursos', 5242880);
            if ($uploadResult['success']) {
                $dados['imagem'] = $uploadResult['file_path'];
            }
        }

        $db->update('tbl_cursos', $dados, ['id' => $id]);

        Session::set('success', 'Curso atualizado com sucesso!');
        redirect('/admin/cursos');
    }

    public function excluir($id) {
        Security::validateCSRF($_POST['csrf_token'] ?? '');
        $db = DB::connect();

        $curso = $db->select('tbl_cursos', ['id' => $id]);
        if (empty($curso)) {
            Session::set('error', 'Curso não encontrado');
            redirect('/admin/cursos');
            return;
        }

        $db->delete('tbl_cursos', ['id' => $id]);

        Session::set('success', 'Curso excluído com sucesso!');
        redirect('/admin/cursos');
    }
}
```

### Passo 8: Criar PublicCursosController

**`controllers/PublicCursosController.php`:**

```php
<?php
class PublicCursosController {

    public function index($page = 1) {
        $db = DB::connect();
        $page = max(1, (int)$page);
        $perPage = 9;
        $offset = ($page - 1) * $perPage;

        $cursos = $db->query("
            SELECT * FROM tbl_cursos
            WHERE ativo = 1
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        $totalResult = $db->query("SELECT COUNT(*) as total FROM tbl_cursos WHERE ativo = 1");
        $total = $totalResult[0]['total'] ?? 0;
        $totalPages = ceil($total / $perPage);

        require __DIR__ . '/../views/public/index.php';
    }

    public function detalhes($slug) {
        $db = DB::connect();

        $curso = $db->query("SELECT * FROM tbl_cursos WHERE slug = ? AND ativo = 1", [$slug]);

        if (empty($curso)) {
            http_response_code(404);
            echo "Curso não encontrado";
            exit;
        }
        $curso = $curso[0];

        require __DIR__ . '/../views/public/detalhes.php';
    }
}
```

### Passo 9: Criar Views Admin

**`views/admin/index.php`** (listagem) - similar ao exemplo da seção 7.1

**`views/admin/novo.php`** e **`views/admin/editar.php`** - formulários com campos: titulo, descricao, instrutor, carga_horaria, imagem (upload)

### Passo 10: Criar Views Public

**`views/public/index.php`** (grid de cursos) e **`views/public/detalhes.php`** (página individual)

### Passo 11: Instalar o Módulo

1. Acessar `/admin/modules`
2. Localizar "Cursos Online" na lista
3. Clicar em "Instalar"
4. Aguardar confirmação
5. Verificar que:
   - Tabela `tbl_cursos` foi criada
   - Menu "Cursos" aparece no admin
   - Menu "Cursos" aparece na área pública
   - Rotas `/cursos` e `/admin/cursos` funcionam

### Passo 12: Testar

- ✅ Criar 10 cursos via admin
- ✅ Editar um curso
- ✅ Fazer upload de imagem
- ✅ Acessar `/cursos` (deve listar)
- ✅ Acessar `/cursos/nome-do-curso` (deve exibir detalhes)
- ✅ Testar paginação
- ✅ Desinstalar módulo (deve remover tabela)

---

## 13. Troubleshooting

### Problema: "module.json inválido"

**Causa:** JSON mal formatado

**Solução:**
1. Validar JSON em https://jsonlint.com
2. Verificar vírgulas faltando/sobrando
3. Verificar aspas duplas (não usar aspas simples)
4. Verificar colchetes/chaves fechadas

### Problema: "Schema para mysql não encontrado"

**Causa:** Arquivo SQL ausente ou caminho errado

**Solução:**
1. Verificar que arquivo existe em `modules/nome_modulo/database/mysql-schema.sql`
2. Verificar permissões do arquivo (deve ser legível)
3. Verificar campo `installation.schemas.mysql` no module.json

### Problema: "Erro ao executar schema: Syntax error"

**Causa:** SQL inválido

**Solução:**
1. Testar SQL manualmente no MySQL/Supabase
2. Remover comentários complexos
3. Verificar ponto-e-vírgula separando queries
4. Verificar compatibilidade MySQL vs PostgreSQL

### Problema: Módulo instalado mas rotas não funcionam (404)

**Causa:** Rotas não carregadas

**Solução:**
1. Verificar que `INSTALLED_MODULES` contém o módulo
2. Limpar cache: `Cache::delete('installed_modules')`
3. Verificar que `routes.php` existe no módulo
4. Verificar sintaxe do `routes.php`
5. Reiniciar servidor web

### Problema: Menu não aparece após instalação

**Causa:** Menu item não foi criado

**Solução:**
1. Verificar campo `menu` no module.json
2. Verificar campo `label` e `public_url` existem
3. Verificar tabela `menu_items`:
   ```sql
   SELECT * FROM menu_items WHERE module_name = 'nome_modulo';
   ```
4. Se não existir, criar manualmente:
   ```sql
   INSERT INTO menu_items (id, label, type, module_name, url, icon, visible, ordem)
   VALUES (UUID(), 'Nome', 'module', 'nome_modulo', '/nome_modulo', 'box', 1, 10);
   ```

### Problema: Acesso negado mesmo com módulo público

**Causa:** `checkModuleAccess()` não configurado corretamente

**Solução:**
1. Verificar campo `"public": true` no module.json
2. Verificar que `checkModuleAccess()` está sendo chamado nas rotas
3. Verificar que helper está definido no início do routes.php
4. Testar com `ENABLE_MEMBERS = false` (deve funcionar)

### Problema: Desinstalação não remove tabelas

**Causa:** Tabelas com FK ou views dependentes

**Solução:**
1. Executar rollback.sql manualmente
2. Verificar ordem de DROP (dependências primeiro)
3. Usar `SET FOREIGN_KEY_CHECKS = 0` temporariamente (MySQL)
4. Para Supabase: aguardar alguns segundos e tentar novamente

### Problema: Upload de imagem não funciona

**Causa:** Pasta de destino não existe ou sem permissões

**Solução:**
1. Criar pasta: `mkdir -p storage/uploads/nome_modulo`
2. Dar permissões: `chmod 755 storage/uploads/nome_modulo`
3. Verificar que `Upload::image()` está sendo usado corretamente
4. Verificar tamanho máximo em `php.ini` (upload_max_filesize)

---

## 14. Boas Práticas

### Nomenclatura

- ✅ Nome do módulo: lowercase, sem espaços (ex: `artigos`, `cursos_online`)
- ✅ Tabelas: prefixo `tbl_` (ex: `tbl_cursos`)
- ✅ Views SQL: prefixo `vw_` (ex: `vw_ranking_cursos`)
- ✅ Controllers: sufixo `Controller` (ex: `AdminCursosController`)
- ✅ Métodos: camelCase (ex: `criarCurso()`)
- ✅ Arquivos: kebab-case (ex: `criar-curso.php`)

### Segurança

- ✅ **SEMPRE** validar CSRF em POSTs
- ✅ **SEMPRE** sanitizar inputs com `Security::sanitize()`
- ✅ **SEMPRE** escapar outputs com `htmlspecialchars()`
- ✅ **SEMPRE** usar prepared statements (automático via `DB::query()`)
- ✅ **NUNCA** confiar em dados de `$_GET`, `$_POST`, `$_FILES`
- ✅ **NUNCA** concatenar SQL diretamente (usar placeholders)

### Performance

- ✅ Usar paginação em listagens
- ✅ Criar índices em campos de busca
- ✅ Usar `LIMIT` em queries
- ✅ Cachear metadados do módulo (já feito automaticamente)
- ✅ Otimizar imagens antes de upload
- ❌ Evitar `SELECT *` (especificar colunas necessárias)

### Manutenibilidade

- ✅ Documentar métodos complexos
- ✅ Separar lógica de negócio de apresentação
- ✅ Reutilizar helpers do core (`Core::`, `Security::`, etc)
- ✅ Seguir padrão MVC (Model-View-Controller)
- ✅ Manter controllers enxutos (max 200 linhas)
- ✅ Criar README.md no módulo

### Versionamento

- ✅ Seguir Semantic Versioning (MAJOR.MINOR.PATCH)
  - MAJOR: mudanças incompatíveis
  - MINOR: novas features compatíveis
  - PATCH: correções de bugs
- ✅ Documentar mudanças em CHANGELOG.md
- ✅ Testar migração entre versões

### Compatibilidade

- ✅ Suportar MySQL E Supabase (schemas separados)
- ✅ Funcionar com e sem sistema de membros
- ✅ Compatível com PHP 7.4+ (sem union types, sem match())
- ✅ Testar em diferentes ambientes (local, staging, produção)

### Testes

Antes de considerar o módulo pronto:

- ✅ Testar instalação limpa
- ✅ Testar CRUD completo
- ✅ Testar validações (campos vazios, duplicados, etc)
- ✅ Testar upload de arquivos (se houver)
- ✅ Testar paginação (criar 50+ registros)
- ✅ Testar desinstalação (verificar limpeza completa)
- ✅ Testar com outro módulo instalado (evitar conflitos)
- ✅ Testar em navegadores diferentes
- ✅ Testar responsividade mobile

### Documentação

Criar README.md no módulo com:

- ✅ Descrição do módulo
- ✅ Features
- ✅ Requisitos (PHP, database, extensions)
- ✅ Instruções de instalação
- ✅ Configurações disponíveis
- ✅ Screenshots (se possível)
- ✅ Troubleshooting específico do módulo
- ✅ Créditos/licença

---

## 🎯 Resumo Executivo

### O que você precisa para criar um módulo:

1. **Pasta** `modules/nome_modulo/`
2. **Manifesto** `module.json` completo
3. **Rotas** `routes.php` com helper de acesso
4. **Controllers** (Admin + Public)
5. **Views** (Admin + Public)
6. **Schemas SQL** (MySQL + Supabase + Rollback)
7. **Testar** instalação e funcionalidades

### Tempo estimado:

- Módulo simples (CRUD básico): **2-4 horas**
- Módulo médio (com uploads, validações): **4-8 horas**
- Módulo complexo (com integrações, APIs): **8-16 horas**

### Próximos Passos:

1. Definir requisitos do seu módulo
2. Seguir o [Checklist Completo](#11-checklist-completo)
3. Usar o [Exemplo Prático](#12-exemplo-prático-criar-módulo-cursos) como referência
4. Testar exaustivamente antes de deploy
5. Documentar para facilitar manutenção futura

---

**Versão do Guia:** 1.0.0
**Data:** 05/02/2026
**AEGIS Framework:** v14.0.7
**Autor:** Claude Code + Fábio Chezzi
