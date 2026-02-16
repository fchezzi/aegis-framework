<?php
/**
 * Helper: Gera URL para ordenação
 */
function getSortUrl($column, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    $params = ['sort' => $column, 'order' => $newOrder];
    return url('/admin/includes?' . http_build_query($params));
}

/**
 * Helper: Retorna ícone de ordenação
 */
function getSortIcon($column, $currentSort, $currentOrder) {
    if ($currentSort !== $column) {
        return '<i data-lucide="chevrons-up-down"></i>';
    }
    return $currentOrder === 'asc'
        ? '<i data-lucide="chevron-up"></i>'
        : '<i data-lucide="chevron-down"></i>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Includes - <?= ADMIN_NAME ?></title>
</head>

<body class="m-includesbody">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-includes">

        <div class="m-pagebase__header">
            <h1>Includes (<?= count($includes) ?>)</h1>
            <a href="<?= url('/admin/includes/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="plus"></i> Novo Include
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="m-components__alert m-components__alert--success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="m-components__alert m-components__alert--error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (empty($includes)): ?>
            <div class="m-includes__empty">
                <div class="m-components__empty-icon">📄</div>
                <p class="m-includes__empty-text">Nenhum include encontrado.</p>
                <p style="margin-top: 10px;">
                    <a href="<?= url('/admin/includes/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                        <i data-lucide="plus"></i> Criar primeiro include
                    </a>
                </p>
            </div>
        <?php else: ?>
            <table class="m-includes__table">
                <thead>
                    <tr>
                        <th class="m-pagebase__table-sortable">
                            <a href="<?= getSortUrl('name', $currentSort, $currentOrder) ?>">
                                Nome <?= getSortIcon('name', $currentSort, $currentOrder) ?>
                            </a>
                        </th>
                        <th class="m-pagebase__table-sortable">
                            <a href="<?= getSortUrl('description', $currentSort, $currentOrder) ?>">
                                Descrição <?= getSortIcon('description', $currentSort, $currentOrder) ?>
                            </a>
                        </th>
                        <th>Como Usar</th>
                        <th class="m-pagebase__table-sortable">
                            <a href="<?= getSortUrl('size', $currentSort, $currentOrder) ?>">
                                Tamanho <?= getSortIcon('size', $currentSort, $currentOrder) ?>
                            </a>
                        </th>
                        <th class="m-pagebase__table-sortable">
                            <a href="<?= getSortUrl('modified', $currentSort, $currentOrder) ?>">
                                Modificado <?= getSortIcon('modified', $currentSort, $currentOrder) ?>
                            </a>
                        </th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($includes as $include): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($include['name']) ?></code></td>
                        <td><?= htmlspecialchars($include['description']) ?></td>
                        <td>
                            <code class="m-includes__code-copy" onclick="copyToClipboard(this)" title="Clique para copiar">Core::requireInclude('frontend/includes/<?= htmlspecialchars($include['name']) ?>', true);</code>
                        </td>
                        <td><?= htmlspecialchars($include['size']) ?></td>
                        <td><?= htmlspecialchars($include['modified']) ?></td>
                        <td>
                            <?php if ($include['is_protected']): ?>
                                <span class="m-includes__badge m-includes__badge--critical">CRÍTICO</span>
                            <?php endif; ?>
                            <?php if ($include['has_backup']): ?>
                                <span class="m-includes__badge m-includes__badge--backup">BACKUP</span>
                            <?php endif; ?>
                        </td>
                        <td class="m-pagebase__actions">
                            <a href="<?= url('/admin/includes/' . $include['name'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit">
                                <i data-lucide="edit"></i> Editar
                            </a>
                            <?php if (!$include['is_protected']): ?>
                            <form method="POST" action="<?= url('/admin/includes/' . $include['name'] . '/delete') ?>" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger" data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar o include:&#10;<?= htmlspecialchars($include['name']) ?>&#10;&#10;Esta ação NÃO pode ser desfeita!">
                                    <i data-lucide="trash-2"></i> Deletar
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($pagination['total'] > 1): ?>
            <div class="m-pagebase__pagination">
                <?php
                // Helper para preservar parâmetros de ordenação
                function getPaginationUrl($page, $currentSort, $currentOrder) {
                    $params = ['page' => $page];
                    if (!empty($currentSort)) $params['sort'] = $currentSort;
                    if (!empty($currentOrder)) $params['order'] = $currentOrder;
                    return url('/admin/includes?' . http_build_query($params));
                }
                ?>

                <?php if ($pagination['current'] > 1): ?>
                    <a href="<?= getPaginationUrl($pagination['current'] - 1, $currentSort, $currentOrder) ?>" class="m-pagebase__pagination-btn">
                        <i data-lucide="chevron-left"></i> Anterior
                    </a>
                <?php endif; ?>

                <div class="m-pagebase__pagination-numbers">
                    <?php
                    $start = max(1, $pagination['current'] - 2);
                    $end = min($pagination['total'], $pagination['current'] + 2);

                    if ($start > 1): ?>
                        <a href="<?= getPaginationUrl(1, $currentSort, $currentOrder) ?>" class="m-pagebase__pagination-number">1</a>
                        <?php if ($start > 2): ?>
                            <span class="m-pagebase__pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <?php if ($i == $pagination['current']): ?>
                            <span class="m-pagebase__pagination-number m-pagebase__pagination-number--active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= getPaginationUrl($i, $currentSort, $currentOrder) ?>" class="m-pagebase__pagination-number"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end < $pagination['total']): ?>
                        <?php if ($end < $pagination['total'] - 1): ?>
                            <span class="m-pagebase__pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <a href="<?= getPaginationUrl($pagination['total'], $currentSort, $currentOrder) ?>" class="m-pagebase__pagination-number"><?= $pagination['total'] ?></a>
                    <?php endif; ?>
                </div>

                <?php if ($pagination['current'] < $pagination['total']): ?>
                    <a href="<?= getPaginationUrl($pagination['current'] + 1, $currentSort, $currentOrder) ?>" class="m-pagebase__pagination-btn">
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

    <script>
        function copyToClipboard(element) {
            const text = element.textContent;
            navigator.clipboard.writeText(text).then(() => {
                // Mostrar feedback visual
                const feedback = document.createElement('div');
                feedback.className = 'm-includes__copied-feedback';
                feedback.textContent = '✓ Código copiado!';
                document.body.appendChild(feedback);

                // Remover após 2 segundos
                setTimeout(() => {
                    feedback.remove();
                }, 2000);
            }).catch(err => {
                alert('Erro ao copiar: ' + err);
            });
        }
    </script>

</body>
</html>
