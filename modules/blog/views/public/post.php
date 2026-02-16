<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['titulo']) ?></title>
        <!-- include - head -->
    <?php Core::requireInclude('frontend/includes/_head.php', true); ?>
</head>
<body>
    <?php if (!empty($categorias)): ?>
    <div class="">
        <h3>Categorias</h3>
        <ul>
            <?php foreach ($categorias as $cat): ?>
                <li><a href="<?= url('/blog/' . $cat['slug']) ?>" <?= $cat['id'] === $post['categoria_id'] ? 'style="font-weight: bold; color: #007bff;"' : '' ?>><?= htmlspecialchars($cat['nome']) ?> (<?= $cat['total_posts'] ?>)</a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <article class="m-blog-article">
        <div class="">
            <?php if ($post['categoria_nome']): ?>
                <a href="<?= url('/blog/' . $post['categoria_slug']) ?>" class=""><?= htmlspecialchars($post['categoria_nome']) ?></a>
            <?php endif; ?>

            <h1><?= htmlspecialchars($post['titulo']) ?></h1>

            <div class="">
                <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                | <?= $post['visualizacoes'] ?> visualizações
                <?php if ($post['autor_nome']): ?>
                    | Por: <?= htmlspecialchars($post['autor_nome']) ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($post['imagem']): ?>
            <img src="<?= url('storage' . $post['imagem']) ?>" style="max-width: 100%; height: auto; margin-bottom: 20px;">
        <?php endif; ?>

        <div class="">
            <strong><?= htmlspecialchars($post['introducao']) ?></strong>
        </div>

        <div class="">
            <?= $post['conteudo'] ?>
        </div>

        <?php if (!empty($postsRelacionados)): ?>
            <div class="">
                <h3>Posts Relacionados</h3>
                <?php foreach ($postsRelacionados as $rel): ?>
                    <div class="">
                        <a href="<?= url('/blog/' . $rel['categoria_slug'] . '/' . $rel['slug']) ?>"><?= htmlspecialchars($rel['titulo']) ?></a>
                        <p style="color: #666; margin: 10px 0 0 0; font-size: 14px;">
                            <?= htmlspecialchars(substr($rel['introducao'], 0, 100)) ?>...
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="<?= url('/blog') ?>" class="">← Voltar ao Blog</a>
    </article>
</body>
</html>
