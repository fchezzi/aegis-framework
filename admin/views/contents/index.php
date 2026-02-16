<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php require_once __DIR__ . '../../../includes/_admin-head.php'; ?>
	<title>Conteúdos - Admin</title>
</head>
<body>
<a href="<?= url('/admin') ?>" class="back">← Voltar ao Admin</a>
<div class="header">
<h1>Conteúdos (<?=count($contents)?>)</h1>
<a href="<?= url('/admin/contents/create') ?>" class="btn">+ Novo Conteúdo</a>
</div>
<?php if(isset($_SESSION['success'])):?><div class="success"><?=htmlspecialchars($_SESSION['success'])?></div><?php unset($_SESSION['success']);endif;?>
<table>
<thead><tr><th>Título</th><th>Tipo</th><th>Slug</th><th>Visibilidade</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach($contents as $c):?>
<tr>
<td><strong><?=htmlspecialchars($c['title'])?></strong></td>
<td><span class="badge badge-<?=$c['type']?>"><?=$c['type']?></span></td>
<td><?=htmlspecialchars($c['slug'])?></td>
<td><?=$c['is_public']?'<span class="badge badge-public">Público</span>':'Restrito'?></td>
<td>
<a href="<?= url('/admin/contents/' . $c['id'] . '/edit') ?>" class="btn btn-sm">Editar</a>
<a href="<?= url('/admin/contents/' . $c['id'] . '/preview') ?>" class="btn btn-sm" target="_blank">Preview</a>
<form method="POST" action="<?= url('/admin/contents/' . $c['id'] . '/delete') ?>" style="display:inline">
<input type="hidden" name="csrf_token" value="<?=Security::generateCSRF()?>">
<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('⚠️ ATENÇÃO!\n\nDeletar conteúdo:\n<?= htmlspecialchars($c['title']) ?>\nTipo: <?= $c['type'] ?>\n\nEsta ação NÃO pode ser desfeita!')">Deletar</button>
</form>
</td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</body>
</html>
