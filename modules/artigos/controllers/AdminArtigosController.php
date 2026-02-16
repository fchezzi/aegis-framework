<?php
/**
 * AEGIS Framework - Artigos Module
 * Admin Artigos Controller
 * Version: 1.0.0
 */

class AdminArtigosController {

    /**
     * Listagem de artigos
     */
    public function index() {
        Auth::require();

        $db = DB::connect();

        // Paginação
        $perPage = 15;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $currentPage = max(1, $currentPage);
        $offset = ($currentPage - 1) * $perPage;

        // Filtros
        $search = isset($_GET['search']) ? Security::sanitize($_GET['search']) : '';

        // Ordenação
        $allowedSortColumns = ['titulo', 'autor', 'data_artigo', 'views', 'created_at'];
        $sortColumn = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortColumns) ? $_GET['sort'] : 'data_artigo';
        $sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Construir WHERE clauses
        $whereConditions = ['1=1'];
        $params = [];

        // Filtro de busca por título ou autor
        if (!empty($search)) {
            $whereConditions[] = "(titulo LIKE ? OR autor LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Contar total de artigos
        $countQuery = "SELECT COUNT(*) as total FROM tbl_artigos WHERE {$whereClause}";
        $countResult = $db->query($countQuery, $params);
        $totalArtigos = $countResult[0]['total'] ?? 0;

        // Calcular total de páginas
        $totalPagesCount = ceil($totalArtigos / $perPage);

        // Buscar artigos com paginação e ordenação
        $selectQuery = "
            SELECT *
            FROM tbl_artigos
            WHERE {$whereClause}
            ORDER BY {$sortColumn} {$sortOrder}, created_at DESC
            LIMIT ? OFFSET ?
        ";
        $selectParams = array_merge($params, [$perPage, $offset]);
        $artigos = $db->query($selectQuery, $selectParams);

        // Dados de paginação
        $pagination = [
            'current' => $currentPage,
            'total' => $totalPagesCount,
            'perPage' => $perPage,
            'totalItems' => $totalArtigos
        ];

        require __DIR__ . '/../views/admin/index.php';
    }

    /**
     * Formulário de criação
     */
    public function novo() {
        Auth::require();

        require __DIR__ . '/../views/admin/novo.php';
    }

    /**
     * Salvar novo artigo
     */
    public function criar() {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Validações
        $errors = [];

        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        $slug = Security::sanitize($_POST['slug'] ?? '');
        $introducao = Security::sanitize($_POST['introducao'] ?? '');
        $autor = Security::sanitize($_POST['autor'] ?? '');
        $data_artigo = Security::sanitize($_POST['data_artigo'] ?? '');
        $link_externo = Security::sanitize($_POST['link_externo'] ?? '');

        // Validar campos obrigatórios
        if (empty($titulo)) $errors[] = 'Título é obrigatório';
        if (empty($slug)) $errors[] = 'Slug é obrigatório';
        if (empty($introducao)) $errors[] = 'Introdução é obrigatória';
        if (empty($autor)) $errors[] = 'Autor é obrigatório';
        if (empty($data_artigo)) $errors[] = 'Data do artigo é obrigatória';

        // Validar tamanhos
        if (strlen($titulo) > 255) $errors[] = 'Título muito longo (máx 255 caracteres)';
        if (strlen($slug) > 255) $errors[] = 'Slug muito longo (máx 255 caracteres)';
        if (strlen($autor) > 255) $errors[] = 'Autor muito longo (máx 255 caracteres)';
        if (strlen($link_externo) > 500) $errors[] = 'Link externo muito longo (máx 500 caracteres)';

        // Validar formato de data
        $dateObj = DateTime::createFromFormat('Y-m-d', $data_artigo);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $data_artigo) {
            $errors[] = 'Data inválida (formato esperado: YYYY-MM-DD)';
        }

        // Validar URL (apenas se preenchido)
        if (!empty($link_externo) && !filter_var($link_externo, FILTER_VALIDATE_URL)) {
            $errors[] = 'Link externo inválido (deve ser uma URL completa)';
        }

        // Validar slug único
        $slugExists = $db->query("SELECT id FROM tbl_artigos WHERE slug = ?", [$slug]);
        if (!empty($slugExists)) {
            $errors[] = 'Slug já existe, escolha outro';
        }

        // Upload de imagem (obrigatório)
        $imagemPath = null;
        if (empty($_FILES['imagem']['name'])) {
            $errors[] = 'Imagem é obrigatória';
        } else {
            $upload = Upload::image($_FILES['imagem'], 'artigos');

            if ($upload['success']) {
                $imagemPath = $upload['path'];
            } else {
                $errors[] = $upload['message'];
            }
        }

        // Upload de PDF (opcional)
        $pdfPath = null;
        if (!empty($_FILES['arquivo_pdf']['name'])) {
            $upload = Upload::uploadFile($_FILES['arquivo_pdf'], 'artigos', 10); // 10MB

            if ($upload['success']) {
                $pdfPath = $upload['path'];
            } else {
                $errors[] = $upload['error'];
            }
        }

        // Se tem erros, voltar ao formulário
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . url('/admin/artigos/novo'));
            exit;
        }

        // Gerar UUID para novo artigo
        $id = Core::generateUUID();

        // Inserir artigo
        try {
            $result = $db->insert('tbl_artigos', [
                'id' => $id,
                'titulo' => $titulo,
                'slug' => $slug,
                'introducao' => $introducao,
                'autor' => $autor,
                'data_artigo' => $data_artigo,
                'imagem' => $imagemPath,
                'link_externo' => $link_externo,
                'arquivo_pdf' => $pdfPath,
                'views' => 0
            ]);

            error_log("Artigo criado com sucesso: Titulo={$titulo}, Slug={$slug}");

            $_SESSION['success'] = 'Artigo criado com sucesso!';
            header('Location: ' . url('/admin/artigos'));
            exit;

        } catch (Exception $e) {
            error_log("ERRO ao criar artigo: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            $_SESSION['error'] = 'Erro ao salvar artigo: ' . $e->getMessage();
            header('Location: ' . url('/admin/artigos/novo'));
            exit;
        }
    }

    /**
     * Formulário de edição
     */
    public function editar($id) {
        Auth::require();

        $db = DB::connect();

        // Buscar artigo
        $artigo = $db->query("SELECT * FROM tbl_artigos WHERE id = ?", [$id]);
        if (empty($artigo)) {
            $_SESSION['error'] = 'Artigo não encontrado';
            header('Location: ' . url('/admin/artigos'));
            exit;
        }
        $artigo = $artigo[0];

        require __DIR__ . '/../views/admin/editar.php';
    }

    /**
     * Atualizar artigo
     */
    public function atualizar($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Verificar se artigo existe
        $artigo = $db->query("SELECT * FROM tbl_artigos WHERE id = ?", [$id]);
        if (empty($artigo)) {
            $_SESSION['error'] = 'Artigo não encontrado';
            header('Location: ' . url('/admin/artigos'));
            exit;
        }
        $artigo = $artigo[0];

        // Validações
        $errors = [];

        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        $slug = Security::sanitize($_POST['slug'] ?? '');
        $introducao = Security::sanitize($_POST['introducao'] ?? '');
        $autor = Security::sanitize($_POST['autor'] ?? '');
        $data_artigo = Security::sanitize($_POST['data_artigo'] ?? '');
        $link_externo = Security::sanitize($_POST['link_externo'] ?? '');

        // Validar campos obrigatórios
        if (empty($titulo)) $errors[] = 'Título é obrigatório';
        if (empty($slug)) $errors[] = 'Slug é obrigatório';
        if (empty($introducao)) $errors[] = 'Introdução é obrigatória';
        if (empty($autor)) $errors[] = 'Autor é obrigatório';
        if (empty($data_artigo)) $errors[] = 'Data do artigo é obrigatória';

        // Validar tamanhos
        if (strlen($titulo) > 255) $errors[] = 'Título muito longo (máx 255 caracteres)';
        if (strlen($slug) > 255) $errors[] = 'Slug muito longo (máx 255 caracteres)';
        if (strlen($autor) > 255) $errors[] = 'Autor muito longo (máx 255 caracteres)';
        if (strlen($link_externo) > 500) $errors[] = 'Link externo muito longo (máx 500 caracteres)';

        // Validar formato de data
        $dateObj = DateTime::createFromFormat('Y-m-d', $data_artigo);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $data_artigo) {
            $errors[] = 'Data inválida (formato esperado: YYYY-MM-DD)';
        }

        // Validar URL (apenas se preenchido)
        if (!empty($link_externo) && !filter_var($link_externo, FILTER_VALIDATE_URL)) {
            $errors[] = 'Link externo inválido (deve ser uma URL completa)';
        }

        // Validar slug único (exceto o próprio)
        $slugExists = $db->query("SELECT id FROM tbl_artigos WHERE slug = ? AND id != ?", [$slug, $id]);
        if (!empty($slugExists)) {
            $errors[] = 'Slug já existe, escolha outro';
        }

        // Upload de imagem (opcional na edição)
        $imagemPath = $artigo['imagem'];
        if (!empty($_FILES['imagem']['name'])) {
            $upload = Upload::image($_FILES['imagem'], 'artigos');

            if ($upload['success']) {
                // Deletar imagem antiga se existir
                if (!empty($imagemPath)) {
                    Upload::delete($imagemPath);
                }
                $imagemPath = $upload['path'];
            } else {
                $errors[] = $upload['message'];
            }
        }

        // Upload de PDF (opcional na edição)
        $pdfPath = $artigo['arquivo_pdf'];
        if (!empty($_FILES['arquivo_pdf']['name'])) {
            $upload = Upload::uploadFile($_FILES['arquivo_pdf'], 'artigos', 10); // 10MB

            if ($upload['success']) {
                // Deletar PDF antigo se existir
                if (!empty($pdfPath)) {
                    Upload::delete($pdfPath);
                }
                $pdfPath = $upload['path'];
            } else {
                $errors[] = $upload['error'];
            }
        }

        // Se tem erros, voltar ao formulário
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . url('/admin/artigos/editar/' . $id));
            exit;
        }

        // Atualizar artigo
        try {
            $result = $db->update('tbl_artigos', [
                'titulo' => $titulo,
                'slug' => $slug,
                'introducao' => $introducao,
                'autor' => $autor,
                'data_artigo' => $data_artigo,
                'imagem' => $imagemPath,
                'link_externo' => $link_externo,
                'arquivo_pdf' => $pdfPath
            ], ['id' => $id]);

            error_log("Artigo atualizado com sucesso: ID={$id}, Titulo={$titulo}");

            $_SESSION['success'] = 'Artigo atualizado com sucesso!';
            header('Location: ' . url('/admin/artigos'));
            exit;

        } catch (Exception $e) {
            error_log("ERRO ao atualizar artigo: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            $_SESSION['error'] = 'Erro ao atualizar artigo: ' . $e->getMessage();
            header('Location: ' . url('/admin/artigos/editar/' . $id));
            exit;
        }
    }

    /**
     * Deletar artigo
     */
    public function excluir($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Buscar artigo para deletar arquivos
        $artigo = $db->query("SELECT imagem, arquivo_pdf FROM tbl_artigos WHERE id = ?", [$id]);
        if (!empty($artigo)) {
            $artigo = $artigo[0];

            // Deletar imagem se existir
            if (!empty($artigo['imagem'])) {
                Upload::delete($artigo['imagem']);
            }

            // Deletar PDF se existir
            if (!empty($artigo['arquivo_pdf'])) {
                Upload::delete($artigo['arquivo_pdf']);
            }

            // Deletar artigo (CASCADE vai deletar leads também)
            $db->delete('tbl_artigos', ['id' => $id]);

            $_SESSION['success'] = 'Artigo deletado com sucesso!';
        } else {
            $_SESSION['error'] = 'Artigo não encontrado';
        }

        header('Location: ' . url('/admin/artigos'));
        exit;
    }
}
