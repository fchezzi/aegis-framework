<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Blog</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 1000px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .posts { display: grid; gap: 20px; margin-bottom: 30px; }
        .post { border: 1px solid #ddd; padding: 20px; border-radius: 4px; }
        .post h2 { margin-top: 0; }
        .post a { color: #007bff; text-decoration: none; }
        .post a:hover { text-decoration: underline; }
        .post-meta { color: #666; font-size: 14px; margin: 10px 0; }
        .post-category { background: #007bff; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px; text-decoration: none; }
        .sidebar { float: right; width: 250px; margin-left: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 4px; }
        .sidebar h3 { margin-top: 0; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar li { margin-bottom: 8px; }
        .sidebar a { color: #333; text-decoration: none; }
        .sidebar a:hover { color: #007bff; }
        .pagination { text-align: center; margin: 30px 0; }
        .pagination a { padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 0 5px; }
    </style>
</head>
<body>
    <?php if (!empty($categorias)): ?>
    <div class="sidebar">
        <h3>Categorias</h3>
        <ul>
            <?php foreach ($categorias as $cat): ?>
                <li><a href="<?= url('/blog/' . $cat['slug']) ?>"><?= htmlspecialchars($cat['nome']) ?> (<?= $cat['total_posts'] ?>)</a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="header">
        <h1>Blog</h1>
    </div>

    <div class="posts">
        <?php if (empty($posts)): ?>
            <p>Nenhum post publicado.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <?php if ($post['categoria_nome']): ?>
                        <a href="<?= url('/blog/' . $post['categoria_slug']) ?>" class="post-category"><?= htmlspecialchars($post['categoria_nome']) ?></a>
                    <?php endif; ?>

                    <h2><a href="<?= url('/blog/' . $post['categoria_slug'] . '/' . $post['slug']) ?>"><?= htmlspecialchars($post['titulo']) ?></a></h2>

                    <?php if ($post['imagem']): ?>
                        <img src="<?= url('storage' . $post['imagem']) ?>" style="max-width: 100%; height: auto; margin: 10px 0;">
                    <?php endif; ?>

                    <p><?= htmlspecialchars($post['introducao']) ?></p>

                    <div class="post-meta">
                        <?= date('d/m/Y', strtotime($post['created_at'])) ?> | <?= $post['visualizacoes'] ?> visualizações
                    </div>

                    <a href="<?= url('/blog/' . $post['categoria_slug'] . '/' . $post['slug']) ?>">Ler mais →</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= url('/blog/pagina/' . ($page - 1)) ?>">← Anterior</a>
            <?php endif; ?>

            <span>Página <?= $page ?> de <?= $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="<?= url('/blog/pagina/' . ($page + 1)) ?>">Próxima →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>
