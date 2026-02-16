<?php
Auth::require();
$user = Auth::user();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Post - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-blogbody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Editar Post</h1>
            <a href="<?= url('/admin/blog/posts') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="m-pagebase__form-container">
            <form method="POST" action="<?= url('/admin/blog/posts/' . $post['id'] . '/update') ?>" enctype="multipart/form-data" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label for="titulo" class="m-pagebase__form-label">Título *</label>
                    <input type="text" id="titulo" name="titulo" required maxlength="255" value="<?= htmlspecialchars($post['titulo']) ?>" class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="slug" class="m-pagebase__form-label">Slug (URL) *</label>
                    <input type="text" id="slug" name="slug" required maxlength="255" value="<?= htmlspecialchars($post['slug']) ?>" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">Gerado automaticamente a partir do título</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="categoria_id" class="m-pagebase__form-label">Categoria *</label>
                    <select id="categoria_id" name="categoria_id" required class="m-pagebase__form-select">
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['id']) ?>" <?= $cat['id'] === $post['categoria_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="introducao" class="m-pagebase__form-label">Introdução *</label>
                    <textarea id="introducao" name="introducao" required maxlength="350" rows="3" class="m-pagebase__form-textarea"><?= htmlspecialchars($post['introducao']) ?></textarea>
                    <div class="m-pagebase__form-help">Breve resumo do post (máximo 350 caracteres)</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="conteudo" class="m-pagebase__form-label">Conteúdo *</label>
                    <textarea id="conteudo" name="conteudo" maxlength="10000" rows="15" class="m-pagebase__form-textarea"><?= htmlspecialchars($post['conteudo']) ?></textarea>
                    <div class="m-pagebase__form-help">Conteúdo completo do post (máximo 10.000 caracteres)</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="imagem" class="m-pagebase__form-label">Imagem</label>
                    <?php if ($post['imagem']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?= url($post['imagem']) ?>" alt="Imagem atual" style="max-width: 300px; display: block; border-radius: 4px; border: 2px solid #e0e0e0;">
                            <small style="color: #666; display: block; margin-top: 5px;">Imagem atual</small>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/jpg,image/png,image/webp" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">JPG, PNG ou WEBP. Máximo 5MB. Deixe vazio para manter a imagem atual.</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-checkbox">
                        <input type="checkbox" name="ativo" value="1" <?= $post['ativo'] ? 'checked' : '' ?>>
                        Post ativo
                    </label>
                    <div class="m-pagebase__form-help">Desmarque para ocultar o post do site</div>
                </div>

                <!-- Posts Relacionados -->
                <div class="m-pagebase__info">
                    <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 16px;">Posts Relacionados</h3>

                    <div style="margin-bottom: 25px;">
                        <strong style="display: block; margin-bottom: 8px;">Adicionar Post Relacionado:</strong>
                        <div style="position: relative;">
                            <input
                                type="text"
                                id="search-relacionados"
                                placeholder="Buscar posts por título..."
                                class="m-pagebase__form-input"
                                autocomplete="off"
                                style="margin-bottom: 10px;"
                            >
                            <div id="search-results" style="display: none; position: absolute; width: 100%; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                        </div>
                    </div>

                    <div>
                        <strong style="display: block; margin-bottom: 8px;">Posts Relacionados (<span id="count-manuais"><?= count($relacionadosManuais) ?></span>):</strong>
                        <div id="lista-relacionados-manuais">
                            <?php if (empty($relacionadosManuais)): ?>
                                <p style="color: #999; margin: 0; font-size: 14px;" id="msg-vazio">Nenhum post relacionado selecionado</p>
                            <?php else: ?>
                                <?php foreach ($relacionadosManuais as $rel): ?>
                                    <div class="post-relacionado-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #f9f9f9; border-radius: 4px; margin-bottom: 5px;">
                                        <span><?= htmlspecialchars($rel['titulo']) ?></span>
                                        <button type="button" class="btn-remove-relacionado m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger" data-rel-id="<?= $rel['id'] ?>">
                                            <i data-lucide="x"></i> Remover
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                    <i data-lucide="save"></i> Salvar Alterações
                </button>
            </form>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // Posts Relacionados - Busca e Gerenciamento
        (function() {
            const postId = '<?= $post['id'] ?>';
            const searchInput = document.getElementById('search-relacionados');
            const searchResults = document.getElementById('search-results');
            const listaManuais = document.getElementById('lista-relacionados-manuais');
            const countManuais = document.getElementById('count-manuais');
            let searchTimeout;

            // Buscar posts ao digitar
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch('<?= url('/admin/blog/posts/' . $post['id'] . '/relacionados/search') ?>?q=' + encodeURIComponent(query))
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.posts.length > 0) {
                                searchResults.innerHTML = data.posts.map(post => `
                                    <div class="search-result-item" data-post-id="${post.id}" style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;">
                                        <strong>${post.titulo}</strong>
                                        <small style="display: block; color: #999;">${post.categoria_nome}</small>
                                    </div>
                                `).join('');
                                searchResults.style.display = 'block';

                                // Adicionar eventos de clique
                                document.querySelectorAll('.search-result-item').forEach(item => {
                                    item.addEventListener('click', function() {
                                        adicionarRelacionado(this.dataset.postId);
                                    });
                                });
                            } else {
                                searchResults.innerHTML = '<div style="padding: 10px; color: #999;">Nenhum post encontrado</div>';
                                searchResults.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao buscar posts:', error);
                        });
                }, 300);
            });

            // Fechar resultados ao clicar fora
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });

            // Adicionar post relacionado
            function adicionarRelacionado(postRelacionadoId) {
                fetch('<?= url('/admin/blog/posts/' . $post['id'] . '/relacionados/add') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'post_relacionado_id=' + postRelacionadoId + '&ordem=0&csrf_token=<?= Security::generateCSRF() ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recarregar página para atualizar lista
                        location.reload();
                    } else {
                        alert('Erro: ' + (data.error || 'Não foi possível adicionar o post relacionado'));
                    }
                })
                .catch(error => {
                    console.error('Erro ao adicionar relacionado:', error);
                    alert('Erro ao adicionar post relacionado');
                });

                searchInput.value = '';
                searchResults.style.display = 'none';
            }

            // Remover post relacionado
            document.addEventListener('click', function(e) {
                if (e.target.closest('.btn-remove-relacionado')) {
                    const btn = e.target.closest('.btn-remove-relacionado');
                    const relacionadoId = btn.dataset.relId;

                    if (!confirm('Remover este post relacionado?')) return;

                    fetch('<?= url('/admin/blog/posts/' . $post['id'] . '/relacionados/remove') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'relacionado_id=' + relacionadoId + '&csrf_token=<?= Security::generateCSRF() ?>'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Recarregar página para atualizar lista
                            location.reload();
                        } else {
                            alert('Erro ao remover post relacionado');
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao remover relacionado:', error);
                        alert('Erro ao remover post relacionado');
                    });
                }
            });
        })();
    </script>

    <!-- TinyMCE -->
    <?php
    $tinymceKey = Settings::get('tinymce_api_key', defined('TINYMCE_API_KEY') ? TINYMCE_API_KEY : 'no-api-key');
    ?>
    <script src="https://cdn.tiny.cloud/1/<?= htmlspecialchars($tinymceKey) ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    // Esperar DOM estar pronto
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM carregado');

        // Auto-slug ao editar título
        document.getElementById('titulo').addEventListener('input', function(e) {
            const slug = e.target.value
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            document.getElementById('slug').value = slug;
        });

        // Forçar sincronização do TinyMCE ao submeter
        var form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Submit event disparado!');

                // Sincronizar conteúdo do TinyMCE para o textarea
                if (typeof tinymce !== 'undefined' && tinymce.get('conteudo')) {
                    tinymce.get('conteudo').save();
                    console.log('TinyMCE sincronizado');

                    // Validar se conteúdo foi preenchido
                    var conteudo = tinymce.get('conteudo').getContent();
                    console.log('Conteúdo length:', conteudo.length);

                    if (!conteudo || conteudo.trim().length === 0) {
                        e.preventDefault();
                        alert('Por favor, preencha o conteúdo do post.');
                        console.log('❌ Conteúdo vazio, submit bloqueado');
                        return false;
                    }
                } else {
                    console.warn('TinyMCE não encontrado');
                }

                console.log('✅ Submit liberado, enviando formulário...');
                return true;
            });
            console.log('Event listener de submit adicionado');
        } else {
            console.error('Formulário não encontrado!');
        }
    });

    // TinyMCE
    tinymce.init({
        selector: '#conteudo',
        height: 500,
        language: 'pt_BR',
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | ' +
                 'alignleft aligncenter alignright alignjustify | ' +
                 'bullist numlist outdent indent | link image media | ' +
                 'forecolor backcolor | removeformat | code fullscreen',
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',

        // Upload de imagens
        images_upload_url: '<?= url('/admin/blog/upload-image') ?>',
        automatic_uploads: true,
        images_reuse_filename: true,

        // Permitir vídeos do YouTube
        media_live_embeds: true,
        media_url_resolver: function (data, resolve) {
            if (data.url.indexOf('youtube.com') !== -1 || data.url.indexOf('youtu.be') !== -1) {
                resolve({
                    html: '<iframe width="560" height="315" src="' +
                          data.url.replace('watch?v=', 'embed/').replace('youtu.be/', 'youtube.com/embed/') +
                          '" frameborder="0" allowfullscreen></iframe>'
                });
            } else {
                resolve({html: ''});
            }
        },

        // Configurações adicionais
        file_picker_types: 'image',
        paste_data_images: true,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,

        // Setup do editor
        setup: function(editor) {
            editor.on('init', function() {
                console.log('TinyMCE inicializado');
            });
        }
    });
    </script>
</body>
</html>
