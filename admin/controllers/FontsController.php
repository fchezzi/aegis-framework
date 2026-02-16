<?php
/**
 * FontsController
 * Gerenciar fontes customizadas WOFF2
 */

class FontsController extends BaseController {

    /**
     * Listar todas as fontes
     */
    public function index() {
        $this->requireAuth();
        $user = $this->getUser();

        $fonts = Fonts::getAll();

        // Agrupar fontes por família para melhor visualização
        $fontsByFamily = [];
        foreach ($fonts as $font) {
            $family = $font['family'];
            if (!isset($fontsByFamily[$family])) {
                $fontsByFamily[$family] = [];
            }
            $fontsByFamily[$family][] = $font;
        }

        // Calcular estatísticas
        $stats = [
            'total_fonts' => count($fonts),
            'total_families' => count($fontsByFamily),
            'total_size' => array_sum(array_column($fonts, 'file_size'))
        ];

        $this->render('fonts/index', [
            'fonts' => $fonts,
            'fontsByFamily' => $fontsByFamily,
            'stats' => $stats,
            'user' => $user
        ]);
    }

    /**
     * Exibir formulário de upload de fonte
     */
    public function create() {
        $this->requireAuth();
        $user = $this->getUser();

        $this->render('fonts/create', ['user' => $user]);
    }

    /**
     * Fazer upload de nova fonte
     */
    public function store() {
        $this->requireAuth();

        try {
            $this->validateCSRF();

            // Validar se arquivo foi enviado
            if (!isset($_FILES['font_file']) || $_FILES['font_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Nenhum arquivo de fonte foi enviado');
            }

            // Nome customizado (opcional)
            $customName = !empty($_POST['custom_name']) ? $this->input('custom_name') : null;

            // Upload via Helper
            $result = Fonts::upload($_FILES['font_file'], $customName);

            if (!$result['success']) {
                throw new Exception($result['error']);
            }

            $this->success('Fonte "' . $result['name'] . '" enviada com sucesso!');
            $this->redirect('/admin/fonts');

        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->redirect('/admin/fonts');
        }
    }

    /**
     * Deletar fonte
     */
    public function destroy($id) {
        $this->requireAuth();

        try {
            $this->validateCSRF();

            // Buscar fonte antes de deletar (para pegar nome)
            $font = Fonts::find($id);

            if (!$font) {
                throw new Exception('Fonte não encontrada');
            }

            // Deletar via Helper
            $result = Fonts::delete($id);

            if (!$result['success']) {
                throw new Exception($result['error']);
            }

            $this->success('Fonte "' . $font['name'] . '" deletada com sucesso!');
            $this->redirect('/admin/fonts');

        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->redirect('/admin/fonts');
        }
    }

    /**
     * Preview de fonte (AJAX)
     * Retorna HTML com preview da fonte
     */
    public function preview() {
        $this->requireAuth();

        header('Content-Type: application/json');

        try {
            $id = $_GET['id'] ?? null;

            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
                exit;
            }

            $font = Fonts::find($id);

            if (!$font) {
                echo json_encode(['success' => false, 'error' => 'Fonte não encontrada']);
                exit;
            }

            // Gerar HTML de preview
            $previewHtml = '
                <style>
                    ' . Fonts::generateFontFace($font) . '
                    .font-preview-text {
                        font-family: \'' . addslashes($font['family']) . '\', sans-serif;
                        font-size: 24px;
                        line-height: 1.5;
                        margin: 20px 0;
                    }
                </style>
                <div class="font-preview-text">
                    <p>ABCDEFGHIJKLMNOPQRSTUVWXYZ</p>
                    <p>abcdefghijklmnopqrstuvwxyz</p>
                    <p>0123456789 !@#$%&*()</p>
                    <p>The quick brown fox jumps over the lazy dog</p>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                </div>
            ';

            echo json_encode([
                'success' => true,
                'html' => $previewHtml,
                'font' => $font
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Download de fonte
     */
    public function download($id) {
        $this->requireAuth();

        try {
            $font = Fonts::find($id);

            if (!$font) {
                throw new Exception('Fonte não encontrada');
            }

            $filePath = ROOT_PATH . 'storage/fonts/' . $font['filename'];

            if (!file_exists($filePath)) {
                throw new Exception('Arquivo de fonte não encontrado');
            }

            // Headers para download
            header('Content-Type: font/woff2');
            header('Content-Disposition: attachment; filename="' . $font['filename'] . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache');

            readfile($filePath);
            exit;

        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->redirect('/admin/fonts');
        }
    }
}
