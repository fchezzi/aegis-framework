<?php
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Fontes de Dados - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/../../includes/header.php'; ?>

  <main class="m-pagebase">

    <div class="m-pagebase__header">
      <h1>Fontes de Dados (<?= count($sources) ?>)</h1>
      <a href="<?= url('/admin/data-sources/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
        <i data-lucide="plus"></i> Nova Fonte de Dados
      </a>
    </div>

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

    <?php if (!empty($sources)): ?>
      <form method="GET" action="<?= url('/admin/data-sources') ?>" class="m-pagebase__filters">
        <div class="m-pagebase__filters-group">
          <input
            type="text"
            id="searchInput"
            placeholder="Buscar por nome, tabela, operação ou coluna..."
            class="m-pagebase__filters-input"
            autocomplete="off"
          >
        </div>
      </form>
    <?php endif; ?>

    <?php if (empty($sources)): ?>
      <div class="m-pagebase__empty">
        <p>Nenhuma fonte de dados criada ainda.</p>
        <p>
          <a href="<?= url('/admin/data-sources/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
            <i data-lucide="plus"></i> Criar Primeira Fonte
          </a>
        </p>
      </div>
    <?php else: ?>
      <table class="m-pagebase__table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Tabela</th>
            <th>Operação</th>
            <th>Coluna</th>
            <th>Condições</th>
            <th>Criada em</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sources as $source): ?>
            <?php $conditions = json_decode($source['conditions'], true) ?? []; ?>
            <tr>
              <td><strong><?= htmlspecialchars($source['name']) ?></strong></td>
              <td><span class="m-pagebase__badge m-pagebase__badge--success"><?= htmlspecialchars($source['table_name']) ?></span></td>
              <td><?= htmlspecialchars($source['operation']) ?></td>
              <td><?= htmlspecialchars($source['column_name']) ?></td>
              <td><?= count($conditions) ?> condição(ões)</td>
              <td><?= date('d/m/Y H:i', strtotime($source['created_at'])) ?></td>
              <td class="m-pagebase__actions">
                <a href="<?= url('/admin/data-sources/edit/' . $source['id']) ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit">
                  <i data-lucide="pencil"></i> Editar
                </a>
                <a href="<?= url('/admin/data-sources/duplicate/' . $source['id']) ?>" class="m-pagebase__btn m-pagebase__btn--sm">
                  <i data-lucide="copy"></i> Duplicar
                </a>
                <form method="POST" action="<?= url('/admin/data-sources/delete/' . $source['id']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                  <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger" data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar a fonte de dados:&#10;<?= htmlspecialchars($source['name']) ?>&#10;&#10;Esta ação NÃO pode ser desfeita!">
                    <i data-lucide="trash-2"></i> Deletar
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();

    // Busca client-side
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
          const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
          const table = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
          const operation = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
          const column = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

          const matchesSearch = name.includes(searchTerm) ||
                                table.includes(searchTerm) ||
                                operation.includes(searchTerm) ||
                                column.includes(searchTerm);

          if (matchesSearch) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
    }
  </script>
</body>
</html>
