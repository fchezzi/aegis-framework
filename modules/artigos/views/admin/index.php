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
    <title>Artigos - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Gerenciar Artigos (<?= $pagination['totalItems'] ?>)</h1>
            <a href="<?= url('/admin/artigos/novo') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="plus"></i> Novo Artigo
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert--success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="<?= url('/admin/artigos') ?>" class="m-pagebase__filters">
            <div class="m-pagebase__filters-group">
                <input
                    type="text"
                    name="search"
                    placeholder="Buscar por título ou autor..."
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    class="m-pagebase__filters-input"
                />
                <button type="submit" class="m-pagebase__filters-btn">
                    <i data-lucide="search"></i> Filtrar
                </button>
                <?php if (!empty($_GET['search'])): ?>
                    <a href="<?= url('/admin/artigos') ?>" class="m-pagebase__filters-btn-clear">
                        <i data-lucide="x"></i> Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($artigos)): ?>
            <div class="m-pagebase__empty">
                <i data-lucide="file-text"></i>
                <?php if (!empty($_GET['search'])): ?>
                    <p>Nenhum artigo encontrado com os filtros aplicados.</p>
                    <p>
                        <a href="<?= url('/admin/artigos') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                            <i data-lucide="x"></i> Limpar filtros
                        </a>
                    </p>
                <?php else: ?>
                    <p>Nenhum artigo criado ainda.</p>
                    <p>
                        <a href="<?= url('/admin/artigos/novo') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                            <i data-lucide="plus"></i> Criar primeiro artigo
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php
            // Helper para gerar URLs de ordenação
            function sortUrl($column) {
                $currentSort = $_GET['sort'] ?? 'data_artigo';
                $currentOrder = $_GET['order'] ?? 'DESC';
                $newOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';

                $params = ['sort' => $column, 'order' => $newOrder];
                if (!empty($_GET['search'])) $params['search'] = $_GET['search'];

                return url('/admin/artigos?' . http_build_query($params));
            }

            function sortIcon($column) {
                $currentSort = $_GET['sort'] ?? 'data_artigo';
                $currentOrder = $_GET['order'] ?? 'DESC';

                if ($currentSort !== $column) return 'chevrons-up-down';
                return $currentOrder === 'ASC' ? 'chevron-up' : 'chevron-down';
            }
            ?>
            <table class="m-pagebase__table">
                <thead>
                    <tr>
                        <th>Imagem</th>
                        <th class="m-pagebase__table-sortable">
                            <a href="<?= sortUrl('titulo') ?>">
                                Título <i data-lucide="<?= sortIcon('titulo') ?>"></i>
                            </a>
                        </th>
                        <th class="m-pagebase__table-sortable">
                            <a href="<?= sortUrl('autor') ?>">
                                Autor <i data-lucide="<?= sortIcon('autor') ?>"></i>
                            </a>
                        </th>
                        <th class="m-pagebase__table-sortable">
                            <a href="<?= sortUrl('data_artigo') ?>">
                                Data Artigo <i data-lucide="<?= sortIcon('data_artigo') ?>"></i>
                            </a>
                        </th>
                        <th class="m-pagebase__table-sortable">
                            <a href="<?= sortUrl('views') ?>">
                                Visualizações <i data-lucide="<?= sortIcon('views') ?>"></i>
                            </a>
                        </th>
                        <th class="m-pagebase__table-sortable">
                            <a href="<?= sortUrl('created_at') ?>">
                                Criado em <i data-lucide="<?= sortIcon('created_at') ?>"></i>
                            </a>
                        </th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artigos as $artigo): ?>
                        <tr>
                            <td>
                                <?php if ($artigo['imagem']): ?>
                                    <img src="<?= url('/storage/uploads/' . $artigo['imagem']) ?>" alt="<?= htmlspecialchars($artigo['titulo']) ?>" class="m-pagebase__thumb">
                                <?php else: ?>
                                    <span class="m-pagebase__meta">Sem imagem</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($artigo['titulo']) ?></td>
                            <td><?= htmlspecialchars($artigo['autor']) ?></td>
                            <td><?= date('d/m/Y', strtotime($artigo['data_artigo'])) ?></td>
                            <td><?= $artigo['views'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($artigo['created_at'])) ?></td>
                            <td class="m-pagebase__actions">
                                <a href="<?= url('/artigos/' . $artigo['slug']) ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--builder m-pagebase__btn--widthauto" target="_blank">
                                    <i data-lucide="eye"></i> Ver
                                </a>
                                <a href="<?= url('/admin/artigos/editar/' . $artigo['id']) ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit m-pagebase__btn--widthauto">
                                    <i data-lucide="pencil"></i> Editar
                                </a>
                                <form method="POST" action="<?= url('/admin/artigos/excluir/' . $artigo['id']) ?>" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto" data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar o artigo:&#10;<?= htmlspecialchars($artigo['titulo']) ?>&#10;&#10;Esta ação NÃO pode ser desfeita!">
                                        <i data-lucide="trash-2"></i> Deletar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

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
                <a href="<?= url('/admin/artigos?page=' . ($pagination['current'] - 1) . $filterQuery) ?>" class="m-pagebase__pagination-btn">
                    <i data-lucide="chevron-left"></i> Anterior
                </a>
            <?php endif; ?>

            <div class="m-pagebase__pagination-numbers">
                <?php
                $start = max(1, $pagination['current'] - 2);
                $end = min($pagination['total'], $pagination['current'] + 2);

                if ($start > 1): ?>
                    <a href="<?= url('/admin/artigos?page=1' . $filterQuery) ?>" class="m-pagebase__pagination-number">1</a>
                    <?php if ($start > 2): ?>
                        <span class="m-pagebase__pagination-ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <?php if ($i == $pagination['current']): ?>
                        <span class="m-pagebase__pagination-number m-pagebase__pagination-number--active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= url('/admin/artigos?page=' . $i . $filterQuery) ?>" class="m-pagebase__pagination-number"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end < $pagination['total']): ?>
                    <?php if ($end < $pagination['total'] - 1): ?>
                        <span class="m-pagebase__pagination-ellipsis">...</span>
                    <?php endif; ?>
                    <a href="<?= url('/admin/artigos?page=' . $pagination['total'] . $filterQuery) ?>" class="m-pagebase__pagination-number"><?= $pagination['total'] ?></a>
                <?php endif; ?>
            </div>

            <?php if ($pagination['current'] < $pagination['total']): ?>
                <a href="<?= url('/admin/artigos?page=' . ($pagination['current'] + 1) . $filterQuery) ?>" class="m-pagebase__pagination-btn">
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
