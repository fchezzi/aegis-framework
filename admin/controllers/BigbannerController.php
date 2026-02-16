<?php
/**
 * BigbannerController
 * Gerencia BigBanner
 * 
 * Gerado automaticamente pelo Sistema de CRUDs
 * Data: 2026-02-14 19:14:57
 */

class BigbannerController extends BaseController {

    /**
     * Listar todos (com paginação obrigatória)
     */
    public function index() {
        $this->requireAuth();
        $user = $this->getUser();

        // Paginação obrigatória
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 50;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $perPage;

        // Busca (opcional)
        $search = Security::sanitize($_GET['search'] ?? '');
        $whereClause = '';
        $params = [];

        if (!empty($search)) {
            $whereClause = 'WHERE ' . 'title LIKE ? OR subtitle LIKE ? OR cta LIKE ? OR cta_link LIKE ?';
            $params = array_fill(0, 4, "%{$search}%");
        }

        // Contar total
        $countQuery = "SELECT COUNT(*) as total FROM tbl_bigbanner {$whereClause}";
        $totalResult = $this->db()->query($countQuery, $params);
        $total = $totalResult[0]['total'] ?? 0;
        $totalPages = ceil($total / $perPage);

        // Buscar registros (SELECT específico, NÃO SELECT *)
        $query = "SELECT id, iamge, title, subtitle, cta, cta_link, `order`, ativo, slug, created_at, updated_at
                 FROM tbl_bigbanner
                 {$whereClause}
                 ORDER BY `order` ASC
                 LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $registros = $this->db()->query($query, $params);

        require_once ROOT_PATH . 'admin/views/bigbanner/index.php';
    }

    /**
     * Exibir formulário de criação
     */
    public function create() {
        $this->requireAuth();
        $user = $this->getUser();

        require_once ROOT_PATH . 'admin/views/bigbanner/create.php';
    }

    /**
     * Processar criação
     */
    public function store() {
        $this->requireAuth();

        try {
            // CSRF Validation
            $this->validateCSRF();

            // Rate Limiting
            if (!RateLimiter::check('bigbanner_create', Auth::id(), 5, 60)) {
                http_response_code(429);
                die('Muitas tentativas. Aguarde 1 minuto.');
            }

            // Sanitizar inputs
            $title = Security::sanitize($_POST['title'] ?? '');
            $subtitle = Security::sanitize($_POST['subtitle'] ?? '');
            $cta = Security::sanitize($_POST['cta'] ?? '');
            $cta_link = Security::sanitize($_POST['cta_link'] ?? '');

            // Validações
            if (empty($title)) {
                throw new Exception('title é obrigatório');
            }

            // Upload: iamge
            if (!isset($_FILES['iamge']) || $_FILES['iamge']['error'] === UPLOAD_ERR_NO_FILE) {
                throw new Exception('iamge é obrigatório');
            }
            if ($_FILES['iamge']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erro no upload de iamge');
            }

                // Validar tamanho (5MB máximo)
                $maxSize = 5 * 1024 * 1024;
                if ($_FILES['iamge']['size'] > $maxSize) {
                    throw new Exception('iamge: arquivo muito grande. Máximo 5MB');
                }

                // Validar MIME type
                $allowedMimes = array (
  0 => 'image/jpeg',
  1 => 'image/png',
  2 => 'image/webp',
);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_FILES['iamge']['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedMimes)) {
                    throw new Exception('iamge: tipo de arquivo não permitido');
                }

                // Validar extensão
                $extension = strtolower(pathinfo($_FILES['iamge']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];
                if (!in_array($extension, $allowedExtensions)) {
                    throw new Exception('iamge: extensão não permitida');
                }

                // Criar diretório
                $uploadDir = __DIR__ . '/../../storage/uploads/bigbanner/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Gerar nome único
                $fileName = Security::generateUUID() . '_' . time() . '.' . $extension;
                $filePath = $uploadDir . $fileName;

                // Mover arquivo
                if (!move_uploaded_file($_FILES['iamge']['tmp_name'], $filePath)) {
                    throw new Exception('Erro ao salvar iamge');
                }

                chmod($filePath, 0644);
                $iamgePath = '/storage/uploads/bigbanner/' . $fileName;

            // Gerar slug
            $slug = $this->generateSlug($title);

            // Gerar ID
            $id = Security::generateUUID();

            // INSERT
            $this->db()->query(
                "INSERT INTO tbl_bigbanner (id, iamge, title, subtitle, cta, cta_link, `order`, ativo, slug, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$id, $iamgePath, $title, $subtitle, $cta, $cta_link, (int)($_POST['order'] ?? 0), isset($_POST['ativo']) ? 1 : 0, $slug, date('Y-m-d H:i:s')]
            );

            // Audit Log
            Logger::getInstance()->audit('CREATE_BIGBANNER', Auth::id(), [
                'id' => $id,
                'table' => 'tbl_bigbanner'
            ]);

            RateLimiter::increment('bigbanner_create', Auth::id(), 60);

            $_SESSION['success'] = 'Registro criado com sucesso!';
            $this->redirect('/admin/bigbanner');

        } catch (Exception $e) {
            Logger::getInstance()->warning('CREATE_BIGBANNER_ERROR', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);

            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/bigbanner/create');
        }
    }

    /**
     * Exibir formulário de edição
     */
    public function edit($id) {
        $this->requireAuth();
        $user = $this->getUser();

        // UUID Validation (PASSO 0)
        if (!Security::isValidUUID($id)) {
            $_SESSION['error'] = 'ID inválido';
            $this->redirect('/admin/bigbanner');
        }

        // Buscar registro
        $registro = $this->db()->query(
            "SELECT * FROM tbl_bigbanner WHERE id = ?",
            [$id]
        );

        if (empty($registro)) {
            $_SESSION['error'] = 'Registro não encontrado';
            $this->redirect('/admin/bigbanner');
        }

        $registro = $registro[0];

        require_once ROOT_PATH . 'admin/views/bigbanner/edit.php';
    }

    /**
     * Processar atualização
     */
    public function update($id) {
        $this->requireAuth();

        try {
            // CSRF Validation
            $this->validateCSRF();

            // UUID Validation
            if (!Security::isValidUUID($id)) {
                throw new Exception('ID inválido');
            }

            // Rate Limiting
            if (!RateLimiter::check('bigbanner_update', Auth::id(), 10, 60)) {
                http_response_code(429);
                die('Muitas tentativas');
            }

            // Sanitizar inputs
            $title = Security::sanitize($_POST['title'] ?? '');
            $subtitle = Security::sanitize($_POST['subtitle'] ?? '');
            $cta = Security::sanitize($_POST['cta'] ?? '');
            $cta_link = Security::sanitize($_POST['cta_link'] ?? '');

            // Validações
            if (empty($title)) {
                throw new Exception('title é obrigatório');
            }

            // Upload: iamge (opcional no update)
            if (!empty($_FILES['iamge']['tmp_name'])) {
                // Buscar registro atual para deletar arquivo antigo
                $current = $this->db()->query("SELECT iamge FROM tbl_bigbanner WHERE id = ?", [$id]);
                if (!empty($current) && !empty($current[0]['iamge'])) {
                    $oldFile = __DIR__ . '/../../' . ltrim($current[0]['iamge'], '/');
                    
                    // Path traversal protection
                    $uploadBasePath = realpath(__DIR__ . '/../../storage/uploads/');
                    $oldFileRealPath = realpath($oldFile);
                    
                    if ($oldFileRealPath && strpos($oldFileRealPath, $uploadBasePath) === 0) {
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    } else {
                        Logger::getInstance()->critical('PATH_TRAVERSAL_ATTEMPT', [
                            'file' => $oldFile,
                            'admin_id' => Auth::id()
                        ]);
                    }
                }

                // Upload novo arquivo (mesma lógica do store)
                $maxSize = 5 * 1024 * 1024;
                if ($_FILES['iamge']['size'] > $maxSize) {
                    throw new Exception('iamge: arquivo muito grande');
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_FILES['iamge']['tmp_name']);
                finfo_close($finfo);

                $allowedMimes = array (
  0 => 'image/jpeg',
  1 => 'image/png',
);
                if (!in_array($mimeType, $allowedMimes)) {
                    throw new Exception('iamge: tipo não permitido');
                }

                $extension = strtolower(pathinfo($_FILES['iamge']['name'], PATHINFO_EXTENSION));
                $uploadDir = __DIR__ . '/../../storage/uploads/bigbanner/';
                $fileName = Security::generateUUID() . '_' . time() . '.' . $extension;
                $filePath = $uploadDir . $fileName;

                if (!move_uploaded_file($_FILES['iamge']['tmp_name'], $filePath)) {
                    throw new Exception('Erro ao salvar iamge');
                }

                chmod($filePath, 0644);
                $iamgePath = '/storage/uploads/bigbanner/' . $fileName;
            }

            // Atualizar slug se campo base mudou
            $slug = $this->generateSlug($title);

            // Preparar dados para UPDATE
            $data = [];
            $data['title'] = $title;
            $data['subtitle'] = $subtitle;
            $data['cta'] = $cta;
            $data['cta_link'] = $cta_link;
            $data['order'] = (int)($_POST['order'] ?? 0);
            $data['ativo'] = isset($_POST['ativo']) ? 1 : 0;
            $data['slug'] = $slug;
            if (isset($iamgePath)) {
                $data['iamge'] = $iamgePath;
            }
            $data['updated_at'] = date('Y-m-d H:i:s');

            // Montar query UPDATE
            $setClauses = [];
            $values = [];
            foreach ($data as $key => $value) {
                $setClauses[] = "`$key` = ?";
                $values[] = $value;
            }
            $values[] = $id;

            $sql = "UPDATE tbl_bigbanner SET " . implode(', ', $setClauses) . " WHERE id = ?";
            $this->db()->query($sql, $values);

            // Audit Log
            Logger::getInstance()->audit('UPDATE_BIGBANNER', Auth::id(), [
                'id' => $id,
                'fields_updated' => array_keys($data)
            ]);

            RateLimiter::increment('bigbanner_update', Auth::id(), 60);

            $_SESSION['success'] = 'Registro atualizado com sucesso!';
            $this->redirect('/admin/bigbanner');

        } catch (Exception $e) {
            Logger::getInstance()->warning('UPDATE_BIGBANNER_ERROR', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/bigbanner/' . $id . '/edit');
        }
    }

    /**
     * Deletar registro (hard delete)
     */
    public function destroy($id) {
        $this->requireAuth();

        try {
            // CSRF Validation
            $this->validateCSRF();

            // UUID Validation
            if (!Security::isValidUUID($id)) {
                throw new Exception('ID inválido');
            }

            // Rate Limiting
            if (!RateLimiter::check('bigbanner_delete', Auth::id(), 5, 60)) {
                http_response_code(429);
                die('Muitas tentativas');
            }

            // Buscar registro
            $registro = $this->db()->query(
                "SELECT * FROM tbl_bigbanner WHERE id = ?",
                [$id]
            );

            if (empty($registro)) {
                throw new Exception('Registro não encontrado');
            }

            $registro = $registro[0];

            // Deletar arquivos físicos
            if (!empty($registro['iamge'])) {
                $filePath = __DIR__ . '/../../' . ltrim($registro['iamge'], '/');
                
                // Path traversal protection
                $uploadBasePath = realpath(__DIR__ . '/../../storage/uploads/');
                $fileRealPath = realpath($filePath);
                
                if ($fileRealPath && strpos($fileRealPath, $uploadBasePath) === 0) {
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            // DELETE
            $this->db()->delete('tbl_bigbanner', ['id' => $id]);

            // Audit Log (com snapshot)
            Logger::getInstance()->audit('DELETE_BIGBANNER', Auth::id(), [
                'id' => $id,
                'snapshot' => $registro
            ]);

            RateLimiter::increment('bigbanner_delete', Auth::id(), 60);

            $_SESSION['success'] = 'Registro removido com sucesso!';
            $this->redirect('/admin/bigbanner');

        } catch (Exception $e) {
            Logger::getInstance()->warning('DELETE_BIGBANNER_ERROR', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/bigbanner');
        }
    }

    /**
     * Gerar slug único
     */
    private function generateSlug($text) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Garantir unicidade
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $exists = $this->db()->query(
                "SELECT id FROM tbl_bigbanner WHERE slug = ?",
                [$slug]
            );

            if (empty($exists)) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

}
