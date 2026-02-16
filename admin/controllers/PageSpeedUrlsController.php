<?php
/**
 * PageSpeed URLs Controller
 * Gerenciar URLs a serem analisadas no PageSpeed
 */

class PageSpeedUrlsController extends BaseController {

    /**
     * Listar URLs
     */
    public function index() {
        Auth::require();

        $user = Auth::user();
        $db = $this->db();

        // Buscar URLs
        $urls = $db->query("SELECT * FROM tbl_pagespeed_urls ORDER BY created_at DESC");

        require_once ROOT_PATH . 'admin/views/pagespeed/urls.php';
    }

    /**
     * Adicionar URL
     */
    public function store() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token'], true);

        $db = $this->db();
        $errors = [];

        // Sanitizar
        $url = Security::sanitize($_POST['url'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Validar
        if (empty($url)) {
            $errors[] = 'URL é obrigatória';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'URL inválida';
        }

        // Verificar se já existe
        $exists = $db->query("SELECT id FROM tbl_pagespeed_urls WHERE url = ?", [$url]);
        if (!empty($exists)) {
            $errors[] = 'URL já cadastrada';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $this->redirect('/admin/pagespeed/urls');
            return;
        }

        // Inserir
        $db->insert('tbl_pagespeed_urls', [
            'id' => Core::generateUUID(),
            'url' => $url,
            'ativo' => $ativo
        ]);

        $_SESSION['success'] = 'URL adicionada com sucesso!';
        $this->redirect('/admin/pagespeed/urls');
    }

    /**
     * Toggle ativo/inativo
     */
    public function toggle($id) {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token'], true);

        $db = $this->db();

        // Buscar URL
        $result = $db->query("SELECT * FROM tbl_pagespeed_urls WHERE id = ?", [$id]);
        $urlData = $result[0] ?? null;

        if (!$urlData) {
            $_SESSION['error'] = 'URL não encontrada';
            $this->redirect('/admin/pagespeed/urls');
            return;
        }

        // Toggle
        $novoStatus = $urlData['ativo'] == 1 ? 0 : 1;
        $db->update('tbl_pagespeed_urls', ['ativo' => $novoStatus], ['id' => $id]);

        $_SESSION['success'] = 'Status atualizado!';
        $this->redirect('/admin/pagespeed/urls');
    }

    /**
     * Deletar URL
     */
    public function delete($id) {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token'], true);

        $db = $this->db();

        // Verificar se existe
        $result = $db->query("SELECT id FROM tbl_pagespeed_urls WHERE id = ?", [$id]);
        if (empty($result)) {
            $_SESSION['error'] = 'URL não encontrada';
            $this->redirect('/admin/pagespeed/urls');
            return;
        }

        // Deletar
        $db->delete('tbl_pagespeed_urls', ['id' => $id]);

        $_SESSION['success'] = 'URL removida com sucesso!';
        $this->redirect('/admin/pagespeed/urls');
    }
}
