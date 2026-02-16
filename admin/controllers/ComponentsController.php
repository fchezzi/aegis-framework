<?php
/**
 * AEGIS Framework - Components Controller
 *
 * Gerenciamento de componentes do Page Builder
 *
 * @package AEGIS
 * @version 9.1.0
 * @since 9.1.0
 */

class ComponentsController {
    /**
     * Listar todos os componentes disponíveis
     */
    public static function index() {
        Auth::require();
        $user = Auth::user();

        try {
            $components = Component::listAvailable();

            // Carregar view
            require_once __DIR__ . '/../views/components/index.php';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao listar componentes: ' . $e->getMessage();
            Core::redirect('/admin');
        }
    }

    /**
     * Obter metadata de um componente (AJAX)
     */
    public static function getMetadata() {
        Auth::require();

        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        try {
            $type = $_GET['type'] ?? '';

            if (empty($type)) {
                throw new Exception('Tipo de componente não especificado');
            }

            // Caso especial: listar todos os componentes
            if ($type === '_list_all') {
                $components = Component::listAvailable();
                echo json_encode([
                    'success' => true,
                    'components' => $components
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Caso normal: metadata de um componente específico
            $metadata = Component::getMetadata($type);

            echo json_encode([
                'success' => true,
                'metadata' => $metadata
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Validar dados de componente (AJAX)
     */
    public static function validate() {
        Auth::require();

        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        try {
            // Validar CSRF
            if (!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            $type = $_POST['type'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true);

            if (empty($type)) {
                throw new Exception('Tipo de componente não especificado');
            }

            if (!Component::exists($type)) {
                throw new Exception('Componente não encontrado');
            }

            $isValid = Component::validate($type, $data);

            echo json_encode([
                'success' => true,
                'valid' => $isValid
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Preview de componente (AJAX)
     */
    public static function preview() {
        // Limpar TODOS os buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Iniciar buffer limpo
        ob_start();

        try {
            Auth::require();

            // Validar CSRF
            if (!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            $type = $_POST['type'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true);

            if (empty($type)) {
                throw new Exception('Tipo de componente não especificado');
            }

            if (!Component::exists($type)) {
                throw new Exception('Componente não encontrado');
            }

            // Renderizar componente
            $html = Component::render($type, $data);

            // Preparar resposta
            $response = [
                'success' => true,
                'html' => $html
            ];

            // Limpar buffer
            ob_end_clean();

            // Enviar headers
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');

            // Enviar JSON
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } catch (Exception $e) {
            // Limpar buffer em caso de erro
            if (ob_get_level()) {
                ob_end_clean();
            }

            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}
