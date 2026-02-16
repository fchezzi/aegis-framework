<?php
/**
 * RobotsController - Gerenciamento do robots.txt
 *
 * @version 1.0.0
 */

class RobotsController extends BaseController {

    public function index() {
        Auth::require();

        $robotsPath = ROOT_PATH . 'public/robots.txt';

        $data = [
            'exists' => file_exists($robotsPath),
            'content' => file_exists($robotsPath) ? file_get_contents($robotsPath) : '',
            'path' => $robotsPath,
            'url' => url('/robots.txt')
        ];

        return $this->render('robots', $data);
    }

    public function save() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        $content = $_POST['content'] ?? '';
        $robotsPath = ROOT_PATH . 'public/robots.txt';

        try {
            file_put_contents($robotsPath, $content);
            $_SESSION['success'] = 'robots.txt salvo com sucesso!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao salvar robots.txt: ' . $e->getMessage();
        }

        header('Location: ' . url('/admin/robots'));
        exit;
    }
}
