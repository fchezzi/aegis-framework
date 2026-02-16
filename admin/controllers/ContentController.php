<?php
/**
 * ContentController
 * Gerenciar conteúdos restritos
 */

class ContentController {

    /**
     * Listar todos os conteúdos
     */
    public function index() {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();
        $contents = $db->select('contents', [], 'created_at DESC');

        // Adicionar valores padrão para compatibilidade com views
        foreach ($contents as &$content) {
            $content['type'] = 'page';
            $content['is_public'] = 0;
        }

        require __DIR__ . '/../views/contents/index.php';
    }

    /**
     * Exibir formulário de criar conteúdo
     */
    public function create() {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        require __DIR__ . '/../views/contents/create.php';
    }

    /**
     * Salvar novo conteúdo
     */
    public function store() {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $title = Security::sanitize($_POST['title'] ?? '');
            $slug = Security::sanitize($_POST['slug'] ?? '');
            $type = Security::sanitize($_POST['type'] ?? 'page');
            $isPublic = isset($_POST['is_public']) ? 1 : 0;

            if (empty($title)) {
                throw new Exception("Título é obrigatório");
            }

            // Gerar slug se vazio
            if (empty($slug)) {
                $slug = $this->generateSlug($title);
            }

            $db = DB::connect();

            // Verificar se slug já existe
            $existing = $db->select('contents', ['slug' => $slug]);
            if (!empty($existing)) {
                throw new Exception("Já existe um conteúdo com este slug");
            }

            // Processar data baseado no tipo
            $data = $this->processContentData($type, $_POST);

            $db->insert('contents', [
                'id' => Security::generateUUID(),
                'title' => $title,
                'slug' => $slug,
                'content' => json_encode($data), // Schema só tem 'content', não 'type' e 'data'
                'ativo' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $_SESSION['success'] = "Conteúdo criado com sucesso!";
            Core::redirect('/admin/contents');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/contents/create');
        }
    }

    /**
     * Exibir formulário de editar conteúdo
     */
    public function edit($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();
        $contents = $db->select('contents', ['id' => $id]);

        if (empty($contents)) {
            $_SESSION['error'] = "Conteúdo não encontrado";
            Core::redirect('/admin/contents');
        }

        $content = $contents[0];
        // Schema usa 'content', não 'data', 'type' e 'is_public'
        $content['data'] = json_decode($content['content'] ?? '{}', true);
        $content['type'] = 'page'; // Valor padrão para compatibilidade com views
        $content['is_public'] = 0; // Valor padrão (privado por padrão)

        require __DIR__ . '/../views/contents/edit.php';
    }

    /**
     * Atualizar conteúdo
     */
    public function update($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $title = Security::sanitize($_POST['title'] ?? '');
            $slug = Security::sanitize($_POST['slug'] ?? '');
            $type = Security::sanitize($_POST['type'] ?? 'page');
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            $ativo = isset($_POST['ativo']) ? (int) $_POST['ativo'] : 1;

            if (empty($title)) {
                throw new Exception("Título é obrigatório");
            }

            $db = DB::connect();

            // Verificar se slug já existe (exceto o próprio)
            $existing = $db->select('contents', ['slug' => $slug]);
            if (!empty($existing) && $existing[0]['id'] !== $id) {
                throw new Exception("Já existe um conteúdo com este slug");
            }

            // Processar data baseado no tipo
            $data = $this->processContentData($type, $_POST);

            $db->update('contents', [
                'title' => $title,
                'slug' => $slug,
                'content' => json_encode($data), // Schema só tem 'content'
                'ativo' => $ativo,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            $_SESSION['success'] = "Conteúdo atualizado com sucesso!";
            Core::redirect('/admin/contents');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/contents/edit/' . $id);
        }
    }

    /**
     * Deletar conteúdo
     */
    public function destroy($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $db = DB::connect();

            // Deletar conteúdo (cascade vai remover permissões)
            $db->delete('contents', ['id' => $id]);

            $_SESSION['success'] = "Conteúdo removido com sucesso!";
            Core::redirect('/admin/contents');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/contents');
        }
    }

    /**
     * Gerar slug a partir do título
     */
    private function generateSlug($title) {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    /**
     * Processar dados do conteúdo baseado no tipo
     */
    private function processContentData($type, $postData) {
        $data = [];

        switch ($type) {
            case 'page':
                // ✅ SEGURANÇA: Whitelist de tags HTML seguras (sem <script>, <iframe>, etc)
                $allowedTags = '<p><br><strong><em><b><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><code><pre><hr><div><span>';
                $html = $_POST['content_html'] ?? '';
                $data['html'] = strip_tags($html, $allowedTags);

                // Remover atributos perigosos (onclick, onerror, etc)
                $data['html'] = preg_replace('/(<[^>]+) on\w+\s*=\s*["\'][^"\']*["\']/i', '$1', $data['html']);
                break;

            case 'link':
                $data['url'] = Security::sanitize($_POST['content_url'] ?? '');
                $data['target'] = Security::sanitize($_POST['content_target'] ?? '_self');
                break;

            case 'file':
                // ✅ SEGURANÇA: Upload com validações completas
                if (isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
                    $result = Upload::uploadFile($_FILES['content_file'], 'documents', 10); // 10MB max

                    if ($result['success']) {
                        $data['path'] = $result['path'];
                        $data['size'] = $result['size'];
                        $data['mime'] = $result['mime'];
                        $data['original_name'] = $result['original_name'];
                    } else {
                        $_SESSION['error'] = $result['error'];
                        header('Location: /admin/content');
                        exit;
                    }
                } else {
                    // Se não há novo upload, manter valores existentes (edição)
                    $data['path'] = Security::sanitize($_POST['content_path'] ?? '');
                    $data['size'] = Security::sanitize($_POST['content_size'] ?? '');
                    $data['mime'] = Security::sanitize($_POST['content_mime'] ?? '');
                }
                break;

            case 'dashboard':
                $iframeUrl = Security::sanitize($_POST['content_iframe'] ?? '');

                // VALIDAR URL de iframe (prevenir XSS via javascript:)
                if (!empty($iframeUrl) && !filter_var($iframeUrl, FILTER_VALIDATE_URL)) {
                    throw new Exception('URL de iframe inválida');
                }
                if (!empty($iframeUrl) && !preg_match('/^https?:\/\//i', $iframeUrl)) {
                    throw new Exception('URL de iframe deve começar com http:// ou https://');
                }

                $data['iframe'] = $iframeUrl;
                $data['height'] = Security::sanitize($_POST['content_height'] ?? '600px');
                break;

            case 'video':
                $data['provider'] = Security::sanitize($_POST['content_provider'] ?? 'youtube');
                $data['video_id'] = Security::sanitize($_POST['content_video_id'] ?? '');

                $embedUrl = Security::sanitize($_POST['content_embed_url'] ?? '');

                // VALIDAR URL de embed (prevenir XSS via javascript:)
                if (!empty($embedUrl) && !filter_var($embedUrl, FILTER_VALIDATE_URL)) {
                    throw new Exception('URL de embed inválida');
                }
                if (!empty($embedUrl) && !preg_match('/^https?:\/\//i', $embedUrl)) {
                    throw new Exception('URL de embed deve começar com http:// ou https://');
                }

                $data['embed_url'] = $embedUrl;
                break;

            case 'other':
                // ✅ SEGURANÇA: Sanitizar conteúdo customizado
                $data['custom'] = Security::sanitize($_POST['content_custom'] ?? '');
                break;
        }

        return $data;
    }

    /**
     * Preview do conteúdo
     */
    public function preview($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();
        $contents = $db->select('contents', ['id' => $id]);

        if (empty($contents)) {
            $_SESSION['error'] = "Conteúdo não encontrado";
            Core::redirect('/admin/contents');
        }

        $content = $contents[0];
        // Schema usa 'content', não 'data', 'type' e 'is_public'
        $content['data'] = json_decode($content['content'] ?? '{}', true);
        $content['type'] = 'page'; // Valor padrão para compatibilidade com views
        $content['is_public'] = 0; // Valor padrão (privado por padrão)

        require __DIR__ . '/../views/contents/preview.php';
    }
}
