# 🔧 Template: Novo CRUD

**Tempo estimado:** 30-60 min
**Complexidade:** Baixa-Média

---

## 📋 Checklist Obrigatório

Antes de começar, responda:

- [ ] Qual o nome da entidade? (ex: `produto`, `cliente`, `pedido`)
- [ ] Vai em módulo ou admin? (ex: `modules/estoque/` ou `admin/`)
- [ ] Precisa permissões? (admin sempre tem, módulo depende)
- [ ] Campos da tabela? (listar todos com tipo)

---

## 🗂️ Passo 1: Criar Tabela no Banco

**Localização:** `database/schemas/` ou no SQL do módulo

**Template SQL:**

```sql
CREATE TABLE IF NOT EXISTS `tbl_{NOME_PLURAL}` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` TEXT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ativo` (`ativo`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Checklist Segurança:**
- ✅ Campo `id` com UNSIGNED AUTO_INCREMENT
- ✅ Campos NOT NULL quando obrigatórios
- ✅ DEFAULT values apropriados
- ✅ Índices para campos de busca/filtro
- ✅ Timestamps (created_at, updated_at)
- ✅ ENGINE=InnoDB (para transactions)
- ✅ CHARSET=utf8mb4 (para emojis/unicode)

---

## 🎮 Passo 2: Criar Controller

**Localização:**
- Admin: `admin/controllers/{Nome}Controller.php`
- Módulo: `modules/{modulo}/controllers/{Nome}Controller.php`

**Template Controller:**

```php
<?php
/**
 * @doc {Nome}Controller
 *
 * CRUD completo para gerenciar {entidade_plural}
 *
 * @security CSRF protected, Admin auth required
 * @database tbl_{nome_plural}
 */

require_once __DIR__ . '/../../_config.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/DB.php';
require_once BASE_PATH . '/core/Security.php';

Auth::require();

class {Nome}Controller {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance();
    }

    /**
     * @api GET /admin/{nome_plural}
     * Lista todos os registros (com paginação)
     */
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Buscar registros
        $items = $this->db->query(
            "SELECT * FROM tbl_{nome_plural}
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );

        // Contar total (para paginação)
        $total = $this->db->query(
            "SELECT COUNT(*) as total FROM tbl_{nome_plural}"
        )[0]['total'];

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * @api GET /admin/{nome_plural}/:id
     * Busca registro por ID
     */
    public function show($id) {
        // ✅ SEGURANÇA: Validar ID
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            return ['error' => 'ID inválido'];
        }

        $item = $this->db->select('tbl_{nome_plural}', ['id' => $id]);

        if (empty($item)) {
            http_response_code(404);
            return ['error' => 'Registro não encontrado'];
        }

        return $item[0];
    }

    /**
     * @api POST /admin/{nome_plural}
     * Cria novo registro
     */
    public function store() {
        // ✅ SEGURANÇA: CSRF
        if (!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            return ['error' => 'Token CSRF inválido'];
        }

        // ✅ SEGURANÇA: Validar inputs
        $nome = Security::sanitize($_POST['nome'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (empty($nome)) {
            http_response_code(400);
            return ['error' => 'Nome é obrigatório'];
        }

        // Inserir
        $id = $this->db->insert('tbl_{nome_plural}', [
            'nome' => $nome,
            'descricao' => $descricao,
            'ativo' => $ativo
        ]);

        if ($id) {
            return ['success' => true, 'id' => $id];
        }

        http_response_code(500);
        return ['error' => 'Erro ao criar registro'];
    }

    /**
     * @api PUT /admin/{nome_plural}/:id
     * Atualiza registro existente
     */
    public function update($id) {
        // ✅ SEGURANÇA: CSRF
        if (!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            return ['error' => 'Token CSRF inválido'];
        }

        // ✅ SEGURANÇA: Validar ID
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            return ['error' => 'ID inválido'];
        }

        // Verificar se existe
        $exists = $this->db->select('tbl_{nome_plural}', ['id' => $id]);
        if (empty($exists)) {
            http_response_code(404);
            return ['error' => 'Registro não encontrado'];
        }

        // ✅ SEGURANÇA: Validar inputs
        $nome = Security::sanitize($_POST['nome'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (empty($nome)) {
            http_response_code(400);
            return ['error' => 'Nome é obrigatório'];
        }

        // Atualizar
        $success = $this->db->update('tbl_{nome_plural}',
            [
                'nome' => $nome,
                'descricao' => $descricao,
                'ativo' => $ativo
            ],
            ['id' => $id]
        );

        if ($success) {
            return ['success' => true];
        }

        http_response_code(500);
        return ['error' => 'Erro ao atualizar registro'];
    }

    /**
     * @api DELETE /admin/{nome_plural}/:id
     * Deleta registro (soft delete se tiver campo 'ativo')
     */
    public function destroy($id) {
        // ✅ SEGURANÇA: CSRF
        if (!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            return ['error' => 'Token CSRF inválido'];
        }

        // ✅ SEGURANÇA: Validar ID
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            return ['error' => 'ID inválido'];
        }

        // SOFT DELETE (recomendado)
        $success = $this->db->update('tbl_{nome_plural}',
            ['ativo' => 0],
            ['id' => $id]
        );

        // OU HARD DELETE (use com cuidado!)
        // $success = $this->db->delete('tbl_{nome_plural}', ['id' => $id]);

        if ($success) {
            return ['success' => true];
        }

        http_response_code(500);
        return ['error' => 'Erro ao deletar registro'];
    }
}

// Roteamento simples
$controller = new {Nome}Controller();
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

switch ($action) {
    case 'show':
        echo json_encode($controller->show($id));
        break;
    case 'store':
        echo json_encode($controller->store());
        break;
    case 'update':
        echo json_encode($controller->update($id));
        break;
    case 'destroy':
        echo json_encode($controller->destroy($id));
        break;
    default:
        echo json_encode($controller->index());
}
```

---

## 🎨 Passo 3: Criar View (Admin)

**Localização:** `admin/views/{nome_plural}.php`

**Template View:**

```php
<?php
require_once __DIR__ . '/../_config.php';
require_once BASE_PATH . '/core/Auth.php';
Auth::require();

$pageTitle = '{Nome_Plural}';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{Nome_Plural}</h1>
        <button class="btn btn-primary" onclick="showCreateModal()">
            <i class="fas fa-plus"></i> Novo
        </button>
    </div>

    <!-- Tabela -->
    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="table-{nome_plural}">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Preenchido via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Criar/Editar -->
<div class="modal fade" id="modal-{nome}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo {Nome}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-{nome}">
                    <input type="hidden" name="id" id="input-id">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" name="nome" id="input-nome" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" id="input-descricao" rows="3"></textarea>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="ativo" id="input-ativo" checked>
                        <label class="form-check-label">Ativo</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="save()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Carregar dados
async function loadData() {
    const response = await fetch('/admin/controllers/{Nome}Controller.php');
    const data = await response.json();

    const tbody = document.querySelector('#table-{nome_plural} tbody');
    tbody.innerHTML = '';

    data.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.id}</td>
            <td>${item.nome}</td>
            <td><span class="badge bg-${item.ativo ? 'success' : 'secondary'}">${item.ativo ? 'Ativo' : 'Inativo'}</span></td>
            <td>${new Date(item.created_at).toLocaleDateString('pt-BR')}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="edit(${item.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="destroy(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Mostrar modal criar
function showCreateModal() {
    document.getElementById('form-{nome}').reset();
    document.getElementById('input-id').value = '';
    document.querySelector('#modal-{nome} .modal-title').textContent = 'Novo {Nome}';
    new bootstrap.Modal(document.getElementById('modal-{nome}')).show();
}

// Editar
async function edit(id) {
    const response = await fetch(`/admin/controllers/{Nome}Controller.php?action=show&id=${id}`);
    const item = await response.json();

    document.getElementById('input-id').value = item.id;
    document.getElementById('input-nome').value = item.nome;
    document.getElementById('input-descricao').value = item.descricao || '';
    document.getElementById('input-ativo').checked = item.ativo == 1;

    document.querySelector('#modal-{nome} .modal-title').textContent = 'Editar {Nome}';
    new bootstrap.Modal(document.getElementById('modal-{nome}')).show();
}

// Salvar
async function save() {
    const form = document.getElementById('form-{nome}');
    const formData = new FormData(form);
    const id = formData.get('id');

    const url = id
        ? `/admin/controllers/{Nome}Controller.php?action=update&id=${id}`
        : `/admin/controllers/{Nome}Controller.php?action=store`;

    const response = await fetch(url, {
        method: 'POST',
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        bootstrap.Modal.getInstance(document.getElementById('modal-{nome}')).hide();
        loadData();
        alert('Salvo com sucesso!');
    } else {
        alert(result.error || 'Erro ao salvar');
    }
}

// Deletar
async function destroy(id) {
    if (!confirm('Tem certeza que deseja deletar?')) return;

    const formData = new FormData();
    formData.append('csrf_token', '<?= Security::generateCSRF() ?>');

    const response = await fetch(`/admin/controllers/{Nome}Controller.php?action=destroy&id=${id}`, {
        method: 'POST',
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        loadData();
        alert('Deletado com sucesso!');
    } else {
        alert(result.error || 'Erro ao deletar');
    }
}

// Carregar ao iniciar
loadData();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
```

---

## 🔒 Passo 4: Checklist de Segurança

Antes de finalizar, verificar:

- [ ] **SQL Injection:** Todas as queries usam parametrização ou `$db->select/insert/update/delete`
- [ ] **XSS:** Todos os outputs usam `htmlspecialchars()` ou `Security::sanitize()`
- [ ] **CSRF:** Todos os formulários POST/PUT/DELETE validam token CSRF
- [ ] **Auth:** Controller verifica `Auth::require()` no topo
- [ ] **Validation:** Todos os inputs são validados (tipo, tamanho, formato)
- [ ] **IDs:** IDs são convertidos para int e validados (> 0)
- [ ] **Errors:** Mensagens de erro não expõem informação sensível

---

## ⚡ Passo 5: Checklist de Performance

- [ ] **Índices:** Tabela tem índices em campos de busca/filtro
- [ ] **Paginação:** Lista usa LIMIT/OFFSET (não carrega tudo)
- [ ] **N+1:** Não faz queries dentro de loops
- [ ] **Cache:** Se lista raramente muda, considere cache
- [ ] **SELECT:** Não usa `SELECT *` desnecessariamente

---

## 📝 Passo 6: Atualizar Documentação

Após criar CRUD, atualizar:

```bash
✅ .claude/memory/index.json → adicionar componente
✅ .claude/memory/changelog.json → entry tipo "feature"
✅ .claude/memory/codebase-map.json → dependências
✅ .claude/memory/sessions.json → registrar sessão
```

---

## 🎯 Exemplo Completo

**Criar CRUD de "Produtos":**

1. **Tabela:** `tbl_produtos` (id, nome, descricao, preco, ativo, created_at, updated_at)
2. **Controller:** `admin/controllers/ProdutosController.php`
3. **View:** `admin/views/produtos.php`
4. **Tempo:** ~45 minutos
5. **Linhas:** ~400 linhas (150 controller + 150 view + 100 SQL/docs)

---

**Versão:** 1.0.0
**Criado em:** 2025-01-20
**Uso:** Copiar e substituir placeholders `{Nome}`, `{nome}`, `{nome_plural}`
