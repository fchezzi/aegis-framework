<?php
/**
 * AEGIS Framework - Artigos Module
 * Public Artigos Controller
 * Version: 1.0.0
 */

class PublicArtigosController {

    /**
     * Listagem de artigos (paginada)
     */
    public function index($page = 1) {
        $db = DB::connect();

        $page = max(1, (int)$page);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Buscar artigos
        $artigos = $db->query("
            SELECT *
            FROM tbl_artigos
            ORDER BY data_artigo DESC, created_at DESC
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        // Contar total
        $totalResult = $db->query("SELECT COUNT(*) as total FROM tbl_artigos");
        $total = $totalResult[0]['total'] ?? 0;
        $totalPages = ceil($total / $perPage);

        require __DIR__ . '/../views/public/index.php';
    }

    /**
     * Página individual do artigo com formulário de captura
     */
    public function artigo($slug) {
        $db = DB::connect();

        // Buscar artigo
        $artigo = $db->query("SELECT * FROM tbl_artigos WHERE slug = ?", [$slug]);

        if (empty($artigo)) {
            http_response_code(404);
            echo "Artigo não encontrado";
            exit;
        }
        $artigo = $artigo[0];

        // Incrementar visualizações
        $db->query("UPDATE tbl_artigos SET views = views + 1 WHERE id = ?", [$artigo['id']]);

        require __DIR__ . '/../views/public/artigo.php';
    }

    /**
     * Busca AJAX de artigos
     * Endpoint: POST /artigos/buscar
     * Suporta busca por texto + filtro por ano
     */
    public function buscar() {
        // Validação CSRF
        Security::validateCSRF($_POST["csrf_token"] ?? '');

        $db = DB::connect();

        // Sanitizar query
        $query = Security::sanitize($_POST['query'] ?? '');
        $year = isset($_POST['year']) ? (int)$_POST['year'] : null;

        // Validar ano (se fornecido)
        if ($year !== null && ($year < 2020 || $year > 2030)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Ano inválido',
                'artigos' => []
            ]);
            exit;
        }

        // Validações de query (apenas se houver query)
        if (!empty($query)) {
            if (strlen($query) < 2) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Digite pelo menos 2 caracteres',
                    'artigos' => []
                ]);
                exit;
            }

            if (strlen($query) > 100) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Busca muito longa',
                    'artigos' => []
                ]);
                exit;
            }
        }

        try {
            // Construir query dinamicamente
            $sql = "
                SELECT
                    id,
                    titulo,
                    slug,
                    introducao,
                    autor,
                    data_artigo,
                    imagem,
                    views
                FROM tbl_artigos
                WHERE 1=1
            ";

            $params = [];

            // Filtro por ano
            if ($year !== null) {
                $sql .= " AND YEAR(data_artigo) = ?";
                $params[] = $year;
            }

            // Filtro por texto (se fornecido)
            if (!empty($query)) {
                $searchTerm = "%{$query}%";
                $sql .= " AND (titulo LIKE ? OR autor LIKE ? OR introducao LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $sql .= " ORDER BY data_artigo DESC, created_at DESC LIMIT 20";

            $artigos = $db->query($sql, $params);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'count' => count($artigos),
                'year' => $year,
                'artigos' => $artigos
            ]);

        } catch (Exception $e) {
            error_log("ERRO na busca de artigos: " . $e->getMessage());

            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar artigos',
                'artigos' => []
            ]);
        }

        exit;
    }

    /**
     * Processar formulário de solicitação (captura de lead)
     */
    public function solicitar($slug) {
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Buscar artigo
        $artigo = $db->query("SELECT id, titulo, link_externo, arquivo_pdf FROM tbl_artigos WHERE slug = ?", [$slug]);

        if (empty($artigo)) {
            $_SESSION['error'] = 'Artigo não encontrado';
            header('Location: ' . url('/artigos'));
            exit;
        }
        $artigo = $artigo[0];

        // Validações
        $errors = [];

        $nome = Security::sanitize($_POST['nome'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $whatsapp = Security::sanitize($_POST['whatsapp'] ?? '');

        if (empty($nome)) $errors[] = 'Nome é obrigatório';
        if (empty($email)) $errors[] = 'Email é obrigatório';
        if (empty($whatsapp)) $errors[] = 'WhatsApp é obrigatório';

        // Validar email
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }

        // Validar tamanhos
        if (strlen($nome) > 255) $errors[] = 'Nome muito longo';
        if (strlen($email) > 255) $errors[] = 'Email muito longo';
        if (strlen($whatsapp) > 20) $errors[] = 'WhatsApp muito longo';

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . url('/artigos/' . $slug));
            exit;
        }

        // Gerar UUID para novo lead
        $leadId = Core::generateUUID();

        // Salvar lead
        try {
            $db->insert('tbl_artigos_leads', [
                'id' => $leadId,
                'artigo_id' => $artigo['id'],
                'nome' => $nome,
                'email' => $email,
                'whatsapp' => $whatsapp
            ]);

            error_log("Lead capturado: Artigo={$artigo['id']}, Email={$email}");

            // Enviar email com PDF (se houver arquivo)
            if (!empty($artigo['arquivo_pdf'])) {
                $pdfFullPath = STORAGE_PATH . $artigo['arquivo_pdf'];

                if (file_exists($pdfFullPath)) {
                    $emailSent = Email::enviarArtigo(
                        $email,
                        $nome,
                        $artigo['titulo'],
                        $pdfFullPath
                    );

                    if ($emailSent) {
                        error_log("Email enviado com sucesso para: {$email}");
                    } else {
                        error_log("AVISO: Falha ao enviar email para: {$email}");
                        // Não interrompe o fluxo, lead já foi salvo
                    }
                } else {
                    error_log("AVISO: PDF não encontrado em: {$pdfFullPath}");
                }
            } else {
                error_log("AVISO: Artigo sem PDF anexado, email não enviado");
            }

            // Enviar lead para RD Station
            $rdSent = RDStation::enviarLead(
                $email,
                $nome,
                $whatsapp,
                $artigo['titulo'],
                $slug
            );

            if (!$rdSent) {
                error_log("AVISO: Falha ao enviar lead para RD Station - Email: {$email}");
                // Não interrompe o fluxo, lead já foi salvo e email enviado
            }

            $_SESSION['success'] = 'Solicitação enviada com sucesso! Verifique seu email.';

            // Redirecionar para o link externo ou página de agradecimento
            header('Location: ' . url('/artigos/' . $slug . '?lead=success'));
            exit;

        } catch (Exception $e) {
            error_log("ERRO ao salvar lead: " . $e->getMessage());

            $_SESSION['error'] = 'Erro ao processar solicitação. Tente novamente.';
            header('Location: ' . url('/artigos/' . $slug));
            exit;
        }
    }
}
