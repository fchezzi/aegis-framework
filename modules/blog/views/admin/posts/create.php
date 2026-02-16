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
    <title>Novo Post - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-blogbody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Novo Post</h1>
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
            <form method="POST" action="<?= url('/admin/blog/posts/store') ?>" enctype="multipart/form-data" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label for="titulo" class="m-pagebase__form-label">Título *</label>
                    <input type="text" id="titulo" name="titulo" required maxlength="255" class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="slug" class="m-pagebase__form-label">Slug (URL) *</label>
                    <input type="text" id="slug" name="slug" required maxlength="255" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">Gerado automaticamente a partir do título</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="categoria_id" class="m-pagebase__form-label">Categoria *</label>
                    <select id="categoria_id" name="categoria_id" required class="m-pagebase__form-select">
                        <option value="">Selecione</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="introducao" class="m-pagebase__form-label">Introdução *</label>
                    <textarea id="introducao" name="introducao" required maxlength="350" rows="3" class="m-pagebase__form-textarea"></textarea>
                    <div class="m-pagebase__form-help">Breve resumo do post (máximo 350 caracteres)</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="conteudo" class="m-pagebase__form-label">Conteúdo *</label>
                    <textarea id="conteudo" name="conteudo" maxlength="10000" rows="15" class="m-pagebase__form-textarea"></textarea>
                    <div class="m-pagebase__form-help">Conteúdo completo do post (máximo 10.000 caracteres)</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="imagem" class="m-pagebase__form-label">Imagem</label>
                    <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/jpg,image/png,image/webp" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">JPG, PNG ou WEBP. Máximo 5MB</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-checkbox">
                        <input type="checkbox" name="ativo" value="1" checked>
                        Post ativo
                    </label>
                    <div class="m-pagebase__form-help">Desmarque para salvar como rascunho</div>
                </div>

                <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                    <i data-lucide="save"></i> Salvar Post
                </button>
            </form>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
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

        // Auto-slug
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
