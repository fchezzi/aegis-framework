<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Páginas - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/../../includes/header.php'; ?>

  <main class="m-pagebase">

    <!-- breadcrumb e btns -->
    <div class="m-pagebase__header">
      <h1>Páginas (<?= $pagination['totalItems'] ?>)</h1>
      <a href="<?= url('/admin/pages/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto"><i data-lucide="plus"></i> Nova Página</a>
    </div>

    <!-- mensagens de status -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert--success">
        <?= htmlspecialchars($_SESSION['success']) ?>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert--error">
        <?= htmlspecialchars($_SESSION['error']) ?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- formulário filtro -->
    <form method="GET" action="<?= url('/admin/pages') ?>" class="m-pagebase__filters">

      <div class="m-pagebase__filters-group">
        <input
          type="text"
          name="search"
          placeholder="Buscar por nome..."
          value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
          class="m-pagebase__filters-input"
        >

        <select name="scope" class="m-pagebase__filters-select">
          <option value="">Todos os Scopes</option>
          <option value="frontend" <?= ($_GET['scope'] ?? '') === 'frontend' ? 'selected' : '' ?>>Frontend</option>
          <option value="members" <?= ($_GET['scope'] ?? '') === 'members' ? 'selected' : '' ?>>Members</option>
          <option value="admin" <?= ($_GET['scope'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>

        <select name="type" class="m-pagebase__filters-select">
          <option value="">Todos os Tipos</option>
          <option value="core" <?= ($_GET['type'] ?? '') === 'core' ? 'selected' : '' ?>>Core</option>
          <option value="custom" <?= ($_GET['type'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom</option>
        </select>

        <button type="submit" class="m-pagebase__filters-btn">
          <i data-lucide="search"></i> Filtrar
        </button>

        <?php if (!empty($_GET['search']) || !empty($_GET['scope']) || !empty($_GET['type'])): ?>
          <a href="<?= url('/admin/pages') ?>" class="m-pagebase__filters-clear">
            <i data-lucide="x"></i> Limpar
          </a>
        <?php endif; ?>
      </div>

    </form>

    <!-- mensagens de status -->
    <?php if (empty($pages)): ?>
      <div class="m-pagebase__empty">
        <p>Nenhuma página criada ainda.</p>
        <p>
          <a href="<?= url('/admin/pages/create') ?>" class="m-pagebase__btn"><i data-lucide="plus"></i> Criar primeira página</a>
        </p>
      </div>

      <?php else: ?>
        <?php
        // Helper para gerar URLs de ordenação
        $currentSort = $_GET['sort'] ?? 'title';
        $currentOrder = $_GET['order'] ?? 'asc';

        function getSortUrl($column, $currentSort, $currentOrder) {
            $newOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
            $params = ['sort' => $column, 'order' => $newOrder];
            if (!empty($_GET['search'])) $params['search'] = $_GET['search'];
            if (!empty($_GET['scope'])) $params['scope'] = $_GET['scope'];
            if (!empty($_GET['type'])) $params['type'] = $_GET['type'];
            return url('/admin/pages?' . http_build_query($params));
        }

        function getSortIcon($column, $currentSort, $currentOrder) {
            if ($currentSort !== $column) return '<i data-lucide="chevrons-up-down"></i>';
            return $currentOrder === 'asc' ? '<i data-lucide="chevron-up"></i>' : '<i data-lucide="chevron-down"></i>';
        }
      ?>

      <!-- tabela de dados -->
      <table class="m-pagebase__table">
        <thead>
          <tr>
            <th class="m-pagebase__table-sortable">
              <a href="<?= getSortUrl('title', $currentSort, $currentOrder) ?>">
                Nome <?= getSortIcon('title', $currentSort, $currentOrder) ?>
              </a>
            </th>
            <th class="m-pagebase__table-sortable">
              <a href="<?= getSortUrl('type', $currentSort, $currentOrder) ?>">
                Tipo <?= getSortIcon('type', $currentSort, $currentOrder) ?>
              </a>
            </th>
            <th class="m-pagebase__table-sortable">
              <a href="<?= getSortUrl('slug', $currentSort, $currentOrder) ?>">
                Slug <?= getSortIcon('slug', $currentSort, $currentOrder) ?>
              </a>
            </th>
            <th>Arquivo</th>
            <th class="m-pagebase__table-sortable">
              <a href="<?= getSortUrl('scope', $currentSort, $currentOrder) ?>">
                Scope <?= getSortIcon('scope', $currentSort, $currentOrder) ?>
              </a>
            </th>
            <th class="m-pagebase__table-sortable">
              <a href="<?= getSortUrl('ativo', $currentSort, $currentOrder) ?>">
                Status <?= getSortIcon('ativo', $currentSort, $currentOrder) ?>
              </a>
            </th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pages as $page): ?>
          <tr>
            <td>
              <?= htmlspecialchars($page['title']) ?>
            </td>
            <td>
              <?php if (($page['type'] ?? 'custom') === 'core'): ?>
                <span class="m-pagebase__badge m-pagebase__badge--core">CORE</span>
              <?php else: ?>
                <span class="m-pagebase__badge m-pagebase__badge--custom">CUSTOM</span>
              <?php endif; ?>
            </td>
            <td>
              <code class="m-pagebase__code">/<?= htmlspecialchars($page['slug']) ?></code>
            </td>
            <td>
              <code class="m-pagebase__code">frontend/pages/<?= htmlspecialchars($page['slug']) ?>.php</code>
              <?php if (!$page['file_exists']): ?>
                <span class="m-pagebase__warning">⚠️ Arquivo não encontrado</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (($page['scope'] ?? 'frontend') === 'admin'): ?>
                <span class="m-pagebase__badge m-pagebase__badge--danger">ADMIN</span>
              <?php elseif (($page['scope'] ?? 'frontend') === 'members'): ?>
                <span class="m-pagebase__badge m-pagebase__badge--custom">MEMBERS</span>
              <?php else: ?>
                <span class="m-pagebase__badge m-pagebase__badge--core">FRONTEND</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (($page['ativo'] ?? 1) == 1): ?>
                <span class="m-pagebase__badge m-pagebase__badge--success">ATIVO</span>
              <?php else: ?>
                <span class="m-pagebase__badge m-pagebase__badge--inactive">INATIVO</span>
              <?php endif; ?>
            </td>
            <td class="m-pagebase__actions">
              <a href="<?= url('/' . $page['slug']) ?>" target="_blank" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--view"><i data-lucide="eye"></i><span>Ver</span></a>
              <a href="<?= url('/admin/pages/' . $page['id'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit"><i data-lucide="pencil"></i> Editar</a>
              <a href="<?= url('/admin/pages/' . $page['slug'] . '/builder') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--builder m-pagebase__btn--widthauto"><i data-lucide="layout"></i> Builder</a>
              <form method="POST" action="<?= url('/admin/pages/' . $page['id'] . '/delete') ?>">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto" data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar a página:&#10;<?= htmlspecialchars($page['title']) ?>&#10;(/<?= htmlspecialchars($page['slug']) ?>)&#10;&#10;Esta ação NÃO pode ser desfeita!"><i data-lucide="trash-2"></i> Deletar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- pagination -->
      <?php if ($pagination['total'] > 1): ?>
      <?php
      // Manter filtros e ordenação na paginação
      $filterParams = [];
      if (!empty($_GET['search'])) $filterParams[] = 'search=' . urlencode($_GET['search']);
      if (!empty($_GET['scope'])) $filterParams[] = 'scope=' . urlencode($_GET['scope']);
      if (!empty($_GET['type'])) $filterParams[] = 'type=' . urlencode($_GET['type']);
      if (!empty($_GET['sort'])) $filterParams[] = 'sort=' . urlencode($_GET['sort']);
      if (!empty($_GET['order'])) $filterParams[] = 'order=' . urlencode($_GET['order']);
      $filterQuery = !empty($filterParams) ? '&' . implode('&', $filterParams) : '';
      ?>
      <div class="m-pagebase__pagination">
        <?php if ($pagination['current'] > 1): ?>
          <a href="<?= url('/admin/pages?page=' . ($pagination['current'] - 1) . $filterQuery) ?>" class="m-pagebase__pagination-btn">
            <i data-lucide="chevron-left"></i> Anterior
          </a>
        <?php endif; ?>

        <div class="m-pagebase__pagination-numbers">
          <?php
          $start = max(1, $pagination['current'] - 2);
          $end = min($pagination['total'], $pagination['current'] + 2);

          if ($start > 1): ?>
            <a href="<?= url('/admin/pages?page=1' . $filterQuery) ?>" class="m-pagebase__pagination-number">1</a>
            <?php if ($start > 2): ?>
              <span class="m-pagebase__pagination-ellipsis">...</span>
            <?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i == $pagination['current']): ?>
              <span class="m-pagebase__pagination-number m-pagebase__pagination-number--active"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= url('/admin/pages?page=' . $i . $filterQuery) ?>" class="m-pagebase__pagination-number"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($end < $pagination['total']): ?>
            <?php if ($end < $pagination['total'] - 1): ?>
              <span class="m-pagebase__pagination-ellipsis">...</span>
            <?php endif; ?>
            <a href="<?= url('/admin/pages?page=' . $pagination['total'] . $filterQuery) ?>" class="m-pagebase__pagination-number"><?= $pagination['total'] ?></a>
          <?php endif; ?>
        </div>

        <?php if ($pagination['current'] < $pagination['total']): ?>
          <a href="<?= url('/admin/pages?page=' . ($pagination['current'] + 1) . $filterQuery) ?>" class="m-pagebase__pagination-btn">
            Próximo <i data-lucide="chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>

      <?php endif; ?>
    <?php endif; ?>
    
  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>

</body>

</html>
