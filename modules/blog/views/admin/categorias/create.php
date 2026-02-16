<?php
Auth::require();
$error = $_SESSION['error'] ?? '';
if ($error) unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Nova Categoria</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 600px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; margin-right: 5px; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Nova Categoria</h1>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/blog/categorias/store') ?>">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" required maxlength="100">
        </div>

        <div class="form-group">
            <label for="slug">Slug (URL) *</label>
            <input type="text" id="slug" name="slug" required maxlength="100">
        </div>

        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label for="ordem">Ordem</label>
            <input type="number" id="ordem" name="ordem" value="0" min="0">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="ativo" value="1" checked> Categoria ativa
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Categoria</button>
        <a href="<?= url('/admin/blog/categorias') ?>" class="btn btn-secondary">Cancelar</a>
    </form>

    <script>
    document.getElementById('nome').addEventListener('input', function(e) {
        const slug = e.target.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        document.getElementById('slug').value = slug;
    });
    </script>
</body>
</html>
