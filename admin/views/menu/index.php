<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Gerenciar Menu - <?= ADMIN_NAME ?></title>
</head>

<body class="m-menubody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>



	<main class="m-menu">

			<div class="m-pagebase__header">
				<div>
					<h1>Gerenciar Menu</h1>
				</div>
				<a href="<?= url('/admin/menu/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
					<i data-lucide="plus"></i> Novo Item
				</a>
			</div>	
			
			
		<div class="m-menu__container">



			<?php if (isset($_SESSION['success'])): ?>
				<div class="m-menu__alert-success">
					<?= htmlspecialchars($_SESSION['success']) ?>
				</div>
				<?php unset($_SESSION['success']); ?>
			<?php endif; ?>

			<?php if (isset($_SESSION['error'])): ?>
				<div class="m-menu__alert-error">
					<?= htmlspecialchars($_SESSION['error']) ?>
				</div>
				<?php unset($_SESSION['error']); ?>
			<?php endif; ?>

			<?php if (empty($menuTree)): ?>
				<div class="m-menu__empty">
					<p><strong>Nenhum item de menu criado ainda.</strong></p>
					<p>Comece criando o primeiro item para estruturar a navegação do seu site.</p>
					<a href="<?= url('/admin/menu/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
						<i data-lucide="plus-circle"></i> Criar Primeiro Item
					</a>
				</div>
			<?php else: ?>
				<ul class="m-menu__tree" id="menuTree">
					<?php foreach ($menuTree as $item): ?>
						<?php echo renderMenuItem($item); ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

		</div>
	</main>

	<script src="https://unpkg.com/lucide@latest"></script>
	<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
	<script>
		// Drag and drop - função para inicializar sortable
		function initSortable(element, isRoot = false) {
			return new Sortable(element, {
				animation: 150,
				handle: '.m-menu__drag-handle',
				ghostClass: 'm-menu__sortable-ghost',
				group: 'nested',
				fallbackOnBody: true,
				swapThreshold: 0.65,
				onEnd: function() {
					// Coletar árvore completa
					function collectTree(ul) {
						const items = [];
						ul.querySelectorAll(':scope > li').forEach((li) => {
							const item = {
								id: li.dataset.id,
								children: []
							};
							const childrenUl = li.querySelector(':scope > ul.children');
							if (childrenUl) {
								item.children = collectTree(childrenUl);
							}
							items.push(item);
						});
						return items;
					}

					const tree = collectTree(document.getElementById('menuTree'));

					// Enviar para servidor
					fetch('<?= url('/admin/menu/order') ?>', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
						},
						credentials: 'same-origin',
						body: JSON.stringify({
							tree: tree,
							csrf_token: '<?= Security::generateCSRF() ?>'
						})
					})
					.then(r => r.json())
					.then(data => {
						if (data.success) {
							console.log('Ordem atualizada com sucesso');
						}
					})
					.catch(err => {
						console.error('Erro ao salvar ordem:', err);
					});
				}
			});
		}

		// Inicializar sortable no menu principal
		const menuTree = document.getElementById('menuTree');
		if (menuTree) {
			initSortable(menuTree, true);

			// Inicializar sortable em todos os submenus
			document.querySelectorAll('.children').forEach(childrenUl => {
				initSortable(childrenUl, false);
			});
		}

		// Inicializar ícones Lucide
		if (typeof lucide !== 'undefined') {
			lucide.createIcons();
		}
	</script>
</body>
</html>

<?php
function renderMenuItem($item, $level = 0) {
	$typeLabels = [
		'page' => 'Página',
		'link' => 'Link',
		'category' => 'Categoria',
		'module' => 'Módulo'
	];

	$html = '<li data-id="' . htmlspecialchars($item['id']) . '">';
	$html .= '<div class="m-menu__item-header">';
	$html .= '<div class="m-menu__item-info">';
	$html .= '<span class="m-menu__drag-handle"><i data-lucide="grip-vertical"></i></span>';
	$html .= '<div style="flex: 1;">';
	$html .= '<div class="m-menu__item-title">';
	if ($item['icon']) {
		$html .= '<i data-lucide="' . htmlspecialchars($item['icon']) . '"></i>';
	}
	$html .= htmlspecialchars($item['label']);
	$html .= '</div>';

	$html .= '<div class="m-menu__item-meta">';
	$html .= '<span class="m-menu__badge m-menu__badge--' . htmlspecialchars($item['type']) . '">' . $typeLabels[$item['type']] . '</span>';

	if ($item['type'] === 'page') {
		$html .= '<span>Slug: ' . htmlspecialchars($item['page_slug']) . '</span>';
	} elseif ($item['type'] === 'link') {
		$html .= '<span>URL: ' . htmlspecialchars($item['url']) . '</span>';
	} elseif ($item['type'] === 'module') {
		$html .= '<span>Módulo: ' . htmlspecialchars($item['module_name'] ?? 'N/A') . '</span>';
		$html .= '<span>URL: ' . htmlspecialchars($item['url'] ?? 'N/A') . '</span>';
	}

	if (!$item['visible']) {
		$html .= '<span class="m-menu__badge m-menu__badge--hidden">Oculto</span>';
	}

	$html .= '<span>Permissão: ' . htmlspecialchars($item['permission_type']) . '</span>';
	$html .= '</div>';
	$html .= '</div>';
	$html .= '</div>';

	$html .= '<div class="m-menu__item-actions">';
	$html .= '<a href="' . url('/admin/menu/' . $item['id'] . '/edit') . '" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit">';
	$html .= '<i data-lucide="edit-2"></i> Editar</a>';
	$html .= '<form method="POST" action="' . url('/admin/menu/' . $item['id'] . '/delete') . '" style="display:inline;" onsubmit="return confirm(\'❌ Tem certeza que deseja deletar este item de menu?\\n\\nEsta ação não pode ser desfeita.\')">';
	$html .= '<input type="hidden" name="csrf_token" value="' . Security::generateCSRF() . '">';
	$html .= '<button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto">';
	$html .= '<i data-lucide="trash-2"></i> Deletar</button>';
	$html .= '</form>';
	$html .= '</div>';
	$html .= '</div>';

	// Renderizar filhos
	if (!empty($item['children'])) {
		$html .= '<ul class="children">';
		foreach ($item['children'] as $child) {
			$html .= renderMenuItem($child, $level + 1);
		}
		$html .= '</ul>';
	}

	$html .= '</li>';

	return $html;
}
?>
