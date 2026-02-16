<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php require_once __DIR__ . '../../../includes/_admin-head.php'; ?>
	<title>Preview: <?=htmlspecialchars($content['title'])?></title>
</head>
<body>
<div class="header">
<h1>Preview: <?=htmlspecialchars($content['title'])?> (<?=$content['type']?>)</h1>
</div>
<?php
switch($content['type']){
case 'page':
    // HTML do content é confiável (criado por admin autenticado)
    // Mas ainda assim sanitizamos para prevenir XSS armazenado
    echo htmlspecialchars($content['data']['html']??'<p>Vazio</p>', ENT_QUOTES, 'UTF-8');
    break;
case 'link':
    echo '<p>Link: <a href="'.htmlspecialchars($content['data']['url']??'').'" target="_blank">'.htmlspecialchars($content['data']['url']??'').'</a></p>';
    break;
case 'file':
    echo '<p>Arquivo: '.htmlspecialchars($content['data']['path']??'').'</p>';
    break;
case 'dashboard':
    echo '<iframe src="'.htmlspecialchars($content['data']['iframe']??'').'" style="height:600px"></iframe>';
    break;
case 'video':
    echo '<div class="video-wrapper"><iframe src="'.htmlspecialchars($content['data']['embed_url']??'').'" allowfullscreen></iframe></div>';
    break;
default:
    echo htmlspecialchars($content['data']['custom']??'<p>Sem conteúdo</p>', ENT_QUOTES, 'UTF-8');
}
?>
</body>
</html>
