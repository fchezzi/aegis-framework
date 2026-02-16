<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php require_once __DIR__ . '../../../includes/_admin-head.php'; ?>
	<title>Novo Conteúdo</title>
</head>
<body>
<div class="container">
<a href="<?= url('/admin/contents') ?>" class="back">← Voltar</a>
<h1>Novo Conteúdo</h1>
<?php if(isset($_SESSION['error'])):?><div class="error"><?=htmlspecialchars($_SESSION['error'])?></div><?php unset($_SESSION['error']);endif;?>
<form method="POST" action="<?= url('/admin/contents') ?>">
<input type="hidden" name="csrf_token" value="<?=Security::generateCSRF()?>">
<div class="form-group"><label>Título:</label><input name="title" required></div>
<div class="form-group"><label>Slug (URL):</label><input name="slug"><small>Deixe vazio para gerar automaticamente</small></div>
<div class="form-group"><label>Tipo:</label>
<select name="type" id="type" required>
<option value="page">Página HTML</option>
<option value="link">Link Externo</option>
<option value="file">Arquivo</option>
<option value="dashboard">Dashboard</option>
<option value="video">Vídeo</option>
<option value="other">Outro</option>
</select></div>
<div class="form-group"><label><input type="checkbox" name="is_public" value="1" style="width:auto"> Público (sem login)</label></div>

<div id="field-page" class="type-field"><div class="form-group"><label>HTML:</label><textarea name="content_html"></textarea></div></div>
<div id="field-link" class="type-field" style="display:none"><div class="form-group"><label>URL:</label><input name="content_url"></div></div>
<div id="field-file" class="type-field" style="display:none"><div class="form-group"><label>Caminho do Arquivo:</label><input name="content_path"></div></div>
<div id="field-dashboard" class="type-field" style="display:none"><div class="form-group"><label>URL do iframe:</label><input name="content_iframe"></div></div>
<div id="field-video" class="type-field" style="display:none"><div class="form-group"><label>Embed URL:</label><input name="content_embed_url"></div></div>
<div id="field-other" class="type-field" style="display:none"><div class="form-group"><label>Conteúdo:</label><textarea name="content_custom"></textarea></div></div>

<button type="submit" class="btn">Criar Conteúdo</button>
</form>
</div>
<script>
document.getElementById('type').addEventListener('change',function(){
document.querySelectorAll('.type-field').forEach(f=>f.style.display='none');
document.getElementById('field-'+this.value).style.display='block';
});
</script>
</body>
</html>
