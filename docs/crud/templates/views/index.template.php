<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '/../../../includes/_admin-head.php';
	?>
	<title>{{RESOURCE_PLURAL}} - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<div class="m-pagebase">

		<div class="m-pagebase__header">
			<h1>{{RESOURCE_PLURAL}} (<?= count(${{resource_var_plural}}) ?>)</h1>
			<a href="<?= url('/admin/{{resource_slug}}/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">+ Novo {{RESOURCE_SINGULAR}}</a>
		</div>

		<?php if (isset($_SESSION['success'])): ?>
			<div class="alert alert--success"><?= htmlspecialchars($_SESSION['success']) ?></div>
			<?php unset($_SESSION['success']); ?>
		<?php endif; ?>

		<?php if (isset($_SESSION['error'])): ?>
			<div class="alert alert--error"><?= htmlspecialchars($_SESSION['error']) ?></div>
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

		<?php if (!empty(${{resource_var_plural}})): ?>
		<table class="m-pagebase__table">
			<thead>
				<tr>
					<!-- PERSONALIZAR COLUNAS AQUI -->
					<th>Título</th>
					<th>Status</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach (${{resource_var_plural}} as ${{resource_var_singular}}): ?>
				<tr>
					<!-- PERSONALIZAR DADOS AQUI -->
					<td><strong><?= htmlspecialchars(${{resource_var_singular}}['title']) ?></strong></td>
					<td>
						<?php if (${{resource_var_singular}}['ativo']): ?>
							<span class="m-pagebase__badge m-pagebase__badge--success">ATIVO</span>
						<?php else: ?>
							<span class="m-pagebase__badge m-pagebase__badge--inactive">INATIVO</span>
						<?php endif; ?>
					</td>
					<td class="m-pagebase__actions">
						<a href="<?= url('/admin/{{resource_slug}}/' . htmlspecialchars(${{resource_var_singular}}['id']) . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--widthauto m-pagebase__btn--edit"><i data-lucide="pencil"></i> Editar</a>
						<form method="POST" action="<?= url('/admin/{{resource_slug}}/' . htmlspecialchars(${{resource_var_singular}}['id']) . '/delete') ?>">
							<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
							<button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--widthauto m-pagebase__btn--danger" onclick="return confirm('Tem certeza que deseja deletar este registro?')"><i data-lucide="trash-2"></i> Deletar</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php else: ?>
			<p class="m-pagebase__empty">Nenhum registro cadastrado. <a href="<?= url('/admin/{{resource_slug}}/create') ?>">Criar o primeiro?</a></p>
		<?php endif; ?>

	</div>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>

</body>
</html>

<!--
INSTRUÇÕES DE USO:
==================

1. Substituir placeholders:
   {{RESOURCE_PLURAL}} = Nome plural do recurso (ex: "Banners Hero", "Categorias")
   {{RESOURCE_SINGULAR}} = Nome singular (ex: "Banner", "Categoria")
   {{resource_slug}} = Slug da rota (ex: "banners", "categories")
   {{resource_var_plural}} = Variável PHP plural (ex: $banners, $categories)
   {{resource_var_singular}} = Variável PHP singular (ex: $banner, $category)

2. Personalizar colunas da tabela:
   - Ajustar <thead> com nomes das colunas
   - Ajustar <tbody> com os dados correspondentes
   - Adicionar mais <th> e <td> conforme necessário

3. Adicionar colunas extras (exemplos):
   - Imagem: <td><img src="<?= url($item['image']) ?>" class="m-pagebase__thumb"></td>
   - Data: <td><?= date('d/m/Y', strtotime($item['created_at'])) ?></td>
   - Ordem: <td><?= $item['order'] ?></td>

4. Badges de status (opções):
   - m-pagebase__badge--success (verde)
   - m-pagebase__badge--inactive (cinza)
   - m-pagebase__badge--danger (vermelho)
   - m-pagebase__badge--core (roxo)

5. Botões extras (exemplos):
   - Visualizar: m-pagebase__btn--view + <i data-lucide="eye"></i>
   - Builder: m-pagebase__btn--builder + <i data-lucide="layout"></i>

PADRÃO AEGIS:
=============
- SEMPRE usar classes m-pagebase__*
- SEMPRE usar ícones Lucide nos botões de ação
- SEMPRE usar m-pagebase__btn--widthauto nos botões
- NUNCA usar CSS inline (exceto casos específicos)
- SEMPRE incluir header.php do admin
- SEMPRE usar alerts para mensagens
-->
