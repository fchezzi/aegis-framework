<?php
Auth::require();
$user = Auth::user();
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-blogbody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Categorias (<?= $pagination['totalItems'] ?>)</h1>
            <div style="display: flex; gap: 10px;">
                <a href="<?= url('/admin/blog/posts') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--builder">
                    <i data-lucide="file-text"></i> Posts
                </a>
                <a href="<?= url('/admin/blog/categorias/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                    <i data-lucide="plus"></i> Nova Categoria
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" action="<?= url('/admin/blog/categorias') ?>" class="m-pagebase__filters">
            <div class="m-pagebase__filters-group">
                <input
                    type="text"
                    name="search"
                    placeholder="Buscar por nome..."
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    class="m-pagebase__filters-input"
                >

                <button type="submit" class="m-pagebase__filters-btn">
                    <i data-lucide="search"></i> Filtrar
                </button>

                <?php if (!empty($_GET['search'])): ?>
                    <a href="<?= url('/admin/blog/categorias') ?>" class="m-pagebase__filters-clear">
                        <i data-lucide="x"></i> Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php
        // Helper para gerar URLs de ordenação
        $currentSort = $_GET['sort'] ?? 'ordem';
        $currentOrder = $_GET['order'] ?? 'asc';

        function getSortUrlCat($column, $currentSort, $currentOrder) {
            $newOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
            $params = ['sort' => $column, 'order' => $newOrder];
            if (!empty($_GET['search'])) $params['search'] = $_GET['search'];
            return url('/admin/blog/categorias?' . http_build_query($params));
        }

        function getSortIconCat($column, $currentSort, $currentOrder) {
            if ($currentSort !== $column) return '<i data-lucide="chevrons-up-down"></i>';
            return $currentOrder === 'asc' ? '<i data-lucide="chevron-up"></i>' : '<i data-lucide="chevron-down"></i>';
        }
        ?>

        <table class="m-blog__table">
            <thead>
                <tr>
                    <th class="m-pagebase__table-sortable">
                        <a href="<?= getSortUrlCat('nome', $currentSort, $currentOrder) ?>">
                            Nome <?= getSortIconCat('nome', $currentSort, $currentOrder) ?>
                        </a>
                    </th>
                    <th class="m-pagebase__table-sortable">
                        <a href="<?= getSortUrlCat('slug', $currentSort, $currentOrder) ?>">
                            Slug <?= getSortIconCat('slug', $currentSort, $currentOrder) ?>
                        </a>
                    </th>
                    <th>Posts</th>
                    <th class="m-pagebase__table-sortable">
                        <a href="<?= getSortUrlCat('ativo', $currentSort, $currentOrder) ?>">
                            Status <?= getSortIconCat('ativo', $currentSort, $currentOrder) ?>
                        </a>
                    </th>
                    <th class="m-pagebase__table-sortable">
                        <a href="<?= getSortUrlCat('ordem', $currentSort, $currentOrder) ?>">
                            Ordem <?= getSortIconCat('ordem', $currentSort, $currentOrder) ?>
                        </a>
                    </th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categorias)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Nenhuma categoria encontrada</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['nome']) ?></td>
                            <td><?= htmlspecialchars($cat['slug']) ?></td>
                            <td><?= $cat['total_posts'] ?? 0 ?></td>
                            <td>
                                <?php if ($cat['ativo']): ?>
                                    <span class="m-pagebase__badge m-pagebase__badge--success">ATIVO</span>
                                <?php else: ?>
                                    <span class="m-pagebase__badge m-pagebase__badge--inactive">INATIVO</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $cat['ordem'] ?></td>
                            <td class="m-pagebase__actions">
                                <a href="<?= url('/admin/blog/categorias/' . $cat['id'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit">
                                    <i data-lucide="pencil"></i> Editar
                                </a>
                                <form method="POST" action="<?= url('/admin/blog/categorias/' . $cat['id'] . '/delete') ?>" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto" data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar a categoria:&#10;<?= htmlspecialchars($cat['nome']) ?>&#10;&#10;Esta ação NÃO pode ser desfeita!">
                                        <i data-lucide="trash-2"></i> Deletar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($pagination['total'] > 1): ?>
        <?php
        // Manter filtros e ordenação na paginação
        $filterParams = [];
        if (!empty($_GET['search'])) $filterParams[] = 'search=' . urlencode($_GET['search']);
        if (!empty($_GET['sort'])) $filterParams[] = 'sort=' . urlencode($_GET['sort']);
        if (!empty($_GET['order'])) $filterParams[] = 'order=' . urlencode($_GET['order']);
        $filterQuery = !empty($filterParams) ? '&' . implode('&', $filterParams) : '';
        ?>
        <div class="m-pagebase__pagination">
            <?php if ($pagination['current'] > 1): ?>
                <a href="<?= url('/admin/blog/categorias?page=' . ($pagination['current'] - 1) . $filterQuery) ?>" class="m-pagebase__pagination-btn">
                    <i data-lucide="chevron-left"></i> Anterior
                </a>
            <?php endif; ?>

            <div class="m-pagebase__pagination-numbers">
                <?php
                $start = max(1, $pagination['current'] - 2);
                $end = min($pagination['total'], $pagination['current'] + 2);

                if ($start > 1): ?>
                    <a href="<?= url('/admin/blog/categorias?page=1' . $filterQuery) ?>" class="m-pagebase__pagination-number">1</a>
                    <?php if ($start > 2): ?>
                        <span class="m-pagebase__pagination-ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <?php if ($i == $pagination['current']): ?>
                        <span class="m-pagebase__pagination-number m-pagebase__pagination-number--active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= url('/admin/blog/categorias?page=' . $i . $filterQuery) ?>" class="m-pagebase__pagination-number"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end < $pagination['total']): ?>
                    <?php if ($end < $pagination['total'] - 1): ?>
                        <span class="m-pagebase__pagination-ellipsis">...</span>
                    <?php endif; ?>
                    <a href="<?= url('/admin/blog/categorias?page=' . $pagination['total'] . $filterQuery) ?>" class="m-pagebase__pagination-number"><?= $pagination['total'] ?></a>
                <?php endif; ?>
            </div>

            <?php if ($pagination['current'] < $pagination['total']): ?>
                <a href="<?= url('/admin/blog/categorias?page=' . ($pagination['current'] + 1) . $filterQuery) ?>" class="m-pagebase__pagination-btn">
                    Próximo <i data-lucide="chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
