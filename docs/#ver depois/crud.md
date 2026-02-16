# CRUD no Aegis

Padrão testado para criar CRUDs.

## Schema SQL (MySQL)

```sql
CREATE TABLE nome_tabela (
    id CHAR(36) PRIMARY KEY,                    -- UUID, NUNCA auto_increment
    nome VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Controller Admin

Localização: `admin/controllers/NomeController.php`

```php
<?php

class NomeController {

    public function index() {
        Auth::require();
        $db = DB::connect();
        $items = $db->select('nome_tabela', ['ativo' => 1]);
        require __DIR__ . '/../views/nome/index.php';
    }

    public function create() {
        Auth::require();
        require __DIR__ . '/../views/nome/create.php';
    }

    public function store() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        $db = DB::connect();
        $db->insert('nome_tabela', [
            'id' => Core::generateUUID(),
            'nome' => Security::sanitize($_POST['nome']),
            'descricao' => Security::sanitize($_POST['descricao'] ?? ''),
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ]);

        $_SESSION['success'] = 'Criado com sucesso!';
        Core::redirect('/admin/nome');
    }

    public function edit($id) {
        Auth::require();
        if (!Security::isValidUUID($id)) {
            Core::redirect('/admin/nome');
            return;
        }

        $db = DB::connect();
        $items = $db->select('nome_tabela', ['id' => $id]);
        if (empty($items)) {
            Core::redirect('/admin/nome');
            return;
        }

        $item = $items[0];
        require __DIR__ . '/../views/nome/edit.php';
    }

    public function update($id) {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        if (!Security::isValidUUID($id)) {
            Core::redirect('/admin/nome');
            return;
        }

        $db = DB::connect();
        $db->update('nome_tabela', [
            'nome' => Security::sanitize($_POST['nome']),
            'descricao' => Security::sanitize($_POST['descricao'] ?? ''),
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ], ['id' => $id]);

        $_SESSION['success'] = 'Atualizado com sucesso!';
        Core::redirect('/admin/nome');
    }

    public function destroy($id) {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        if (!Security::isValidUUID($id)) {
            Core::redirect('/admin/nome');
            return;
        }

        $db = DB::connect();
        $db->update('nome_tabela', ['ativo' => 0], ['id' => $id]); // Soft delete

        $_SESSION['success'] = 'Removido com sucesso!';
        Core::redirect('/admin/nome');
    }
}
```

## Rotas

Adicionar em `routes/admin.php`:

```php
Router::get('/admin/nome', [NomeController::class, 'index']);
Router::get('/admin/nome/create', [NomeController::class, 'create']);
Router::post('/admin/nome', [NomeController::class, 'store']);
Router::get('/admin/nome/edit/{id}', [NomeController::class, 'edit']);
Router::post('/admin/nome/update/{id}', [NomeController::class, 'update']);
Router::post('/admin/nome/delete/{id}', [NomeController::class, 'destroy']);
```

## View Index

```php
<?php $pageTitle = 'Gerenciar Nome'; ?>
<h1><?= $pageTitle ?></h1>

<?php if (isset($_SESSION['success'])): ?>
    <div class="success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<a href="<?= url('/admin/nome/create') ?>">Novo</a>

<table>
    <thead>
        <tr><th>Nome</th><th>Status</th><th>Ações</th></tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['nome']) ?></td>
            <td><?= $item['ativo'] ? 'Ativo' : 'Inativo' ?></td>
            <td>
                <a href="<?= url('/admin/nome/edit/' . $item['id']) ?>">Editar</a>
                <form method="POST" action="<?= url('/admin/nome/delete/' . $item['id']) ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                    <button onclick="return confirm('Tem certeza?')">Excluir</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

## View Create/Edit

```php
<form method="POST" action="<?= url('/admin/nome') ?>">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

    <div>
        <label>Nome *</label>
        <input type="text" name="nome" required>
    </div>

    <div>
        <label>Descrição</label>
        <textarea name="descricao"></textarea>
    </div>

    <div>
        <label><input type="checkbox" name="ativo" checked> Ativo</label>
    </div>

    <button type="submit">Salvar</button>
</form>
```

## Checklist

- [ ] IDs são UUID (`Core::generateUUID()`)
- [ ] Inputs sanitizados (`Security::sanitize()`)
- [ ] CSRF em todos os forms POST
- [ ] `Auth::require()` em todos os métodos
- [ ] Soft delete (campo `ativo`)
- [ ] Validação de UUID antes de editar/deletar
