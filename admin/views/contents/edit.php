<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php require_once __DIR__ . '../../../includes/_admin-head.php'; ?>
	<title>Editar Conteúdo</title>
</head>
<body>
<div class="container">
<a href="<?= url('/admin/contents') ?>" class="back">← Voltar</a>
<h1>Editar Conteúdo</h1>
<form method="POST" action="<?= url('/admin/contents/' . $content['id']) ?>">
<input type="hidden" name="csrf_token" value="<?=Security::generateCSRF()?>">
<div class="form-group"><label>Título:</label><input name="title" value="<?=htmlspecialchars($content['title'])?>" required></div>
<div class="form-group"><label>Slug:</label><input name="slug" value="<?=htmlspecialchars($content['slug'])?>" required></div>
<div class="form-group"><label>Tipo:</label>
<select name="type" required>
<option value="page" <?=$content['type']=='page'?'selected':''?>>Página</option>
<option value="link" <?=$content['type']=='link'?'selected':''?>>Link</option>
<option value="file" <?=$content['type']=='file'?'selected':''?>>Arquivo</option>
<option value="dashboard" <?=$content['type']=='dashboard'?'selected':''?>>Dashboard</option>
<option value="video" <?=$content['type']=='video'?'selected':''?>>Vídeo</option>
<option value="other" <?=$content['type']=='other'?'selected':''?>>Outro</option>
</select></div>
<div class="form-group"><label><input type="checkbox" name="is_public" value="1" <?=$content['is_public']?'checked':''?> style="width:auto"> Público</label></div>
<div class="form-group"><label><input type="checkbox" name="ativo" value="1" <?=$content['ativo']?'checked':''?> style="width:auto"> Ativo</label></div>

<?php if($content['type']=='page'):?>
<div class="form-group"><label>HTML:</label><textarea name="content_html"><?=htmlspecialchars($content['data']['html']??'')?></textarea></div>
<?php elseif($content['type']=='link'):?>
<div class="form-group"><label>URL:</label><input name="content_url" value="<?=htmlspecialchars($content['data']['url']??'')?>"></div>
<?php elseif($content['type']=='file'):?>
<div class="form-group"><label>Caminho:</label><input name="content_path" value="<?=htmlspecialchars($content['data']['path']??'')?>"></div>
<?php elseif($content['type']=='dashboard'):?>
<div class="form-group"><label>iframe URL:</label><input name="content_iframe" value="<?=htmlspecialchars($content['data']['iframe']??'')?>"></div>
<?php elseif($content['type']=='video'):?>
<div class="form-group"><label>Embed URL:</label><input name="content_embed_url" value="<?=htmlspecialchars($content['data']['embed_url']??'')?>"></div>
<?php else:?>
<div class="form-group"><label>Conteúdo:</label><textarea name="content_custom"><?=htmlspecialchars($content['data']['custom']??'')?></textarea></div>
<?php endif;?>

<button type="submit" class="btn">Salvar Alterações</button>
</form>
</div>
</body>
</html>
