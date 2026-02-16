<?php
/**
 * ReportTemplateController
 * Gerenciar templates de relatórios Excel
 */

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportTemplateController {

	/**
	 * Listar todos os templates
	 */
	public function index() {
		Auth::require();
		$user = Auth::user();

		$db = DB::connect();
		$templates = $db->select('report_templates', [], 'created_at DESC');

		require __DIR__ . '/../views/reports/index.php';
	}

	/**
	 * Exibir formulário de criar template
	 */
	public function create() {
		Auth::require();
		$user = Auth::user();

		// Buscar fontes de dados disponíveis
		$dataSources = ReportDataSources::getAvailableSources();

		require __DIR__ . '/../views/reports/create.php';
	}

	/**
	 * Salvar novo template
	 */
	public function store() {
		Auth::require();

		try {
			// Validar CSRF
			Security::validateCSRF($_POST['csrf_token'] ?? '');

			$name = Security::sanitize($_POST['name'] ?? '');
			$description = Security::sanitize($_POST['description'] ?? '');
			$visible = isset($_POST['visible']) ? 1 : 0;

			// Validações
			if (empty($name)) {
				throw new Exception('Nome do relatório é obrigatório');
			}

			// Upload do arquivo Excel
			if (empty($_FILES['excel_file']['name'])) {
				throw new Exception('Arquivo Excel é obrigatório');
			}

			$file = $_FILES['excel_file'];
			$fileName = $file['name'];
			$fileTmp = $file['tmp_name'];
			$fileError = $file['error'];

			if ($fileError !== UPLOAD_ERR_OK) {
				throw new Exception('Erro no upload do arquivo');
			}

			// ✅ SEGURANÇA: Validar extensão
			$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
			if (!in_array($ext, ['xlsx', 'xls'])) {
				throw new Exception('Apenas arquivos Excel (.xlsx ou .xls) são permitidos');
			}

			// ✅ SEGURANÇA: Validar MIME type (prevenir upload de executáveis/scripts)
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $fileTmp);
			finfo_close($finfo);

			$allowedMimes = [
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
				'application/vnd.ms-excel', // .xls
				'application/zip' // .xlsx é um ZIP
			];

			if (!in_array($mimeType, $allowedMimes)) {
				throw new Exception('Tipo de arquivo inválido. MIME type detectado: ' . $mimeType);
			}

			// ✅ SEGURANÇA: Validar tamanho do arquivo (máximo 10MB)
			$maxSize = 10 * 1024 * 1024; // 10MB em bytes
			if ($file['size'] > $maxSize) {
				throw new Exception('Arquivo muito grande. Tamanho máximo: 10MB');
			}

			// ✅ SEGURANÇA: Validar se é Excel válido (prevenir ZIP bomb)
			try {
				$spreadsheet = IOFactory::load($fileTmp);
			} catch (Exception $e) {
				throw new Exception('Arquivo Excel inválido: ' . $e->getMessage());
			}

			// Salvar arquivo com nome único
			$uploadsDir = ROOT_PATH . 'uploads/reports/';
			if (!is_dir($uploadsDir)) {
				mkdir($uploadsDir, 0755, true);
			}

			$uniqueName = uniqid('report_') . '_' . time() . '.' . $ext;
			$filePath = $uploadsDir . $uniqueName;

			if (!move_uploaded_file($fileTmp, $filePath)) {
				throw new Exception('Erro ao salvar arquivo');
			}

			$db = DB::connect();

			// Criar template
			$templateId = Security::generateUUID();
			$db->insert('report_templates', [
				'id' => $templateId,
				'name' => $name,
				'description' => $description,
				'file_path' => 'uploads/reports/' . $uniqueName,
				'visible' => $visible,
				'created_at' => date('Y-m-d H:i:s')
			]);

			// Processar mapeamento de células
			$cells = $_POST['cells'] ?? [];
			$dataSources = $_POST['data_sources'] ?? [];

			if (!empty($cells) && !empty($dataSources)) {
				foreach ($cells as $index => $cellRef) {
					$cellRef = strtoupper(trim($cellRef));
					$dataSourceKey = $dataSources[$index] ?? '';

					if (empty($cellRef) || empty($dataSourceKey)) {
						continue;
					}

					// Validar célula (formato básico: A1, B5, AA100, etc)
					if (!preg_match('/^[A-Z]{1,3}[0-9]{1,7}$/', $cellRef)) {
						continue; // Ignorar células inválidas
					}

					// Validar fonte de dados
					if (!ReportDataSources::isValidSource($dataSourceKey)) {
						continue;
					}

					$db->insert('report_cells', [
						'id' => Security::generateUUID(),
						'template_id' => $templateId,
						'cell_ref' => $cellRef,
						'data_source_key' => $dataSourceKey,
						'created_at' => date('Y-m-d H:i:s')
					]);
				}
			}

			$_SESSION['success'] = 'Template de relatório criado com sucesso!';
			Core::redirect('/admin/reports');

		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
			Core::redirect('/admin/reports/create');
		}
	}

	/**
	 * Exibir formulário de editar template
	 */
	public function edit($id) {
		Auth::require();
		$user = Auth::user();

		$db = DB::connect();
		$templates = $db->select('report_templates', ['id' => $id]);

		if (empty($templates)) {
			$_SESSION['error'] = 'Template não encontrado';
			Core::redirect('/admin/reports');
		}

		$template = $templates[0];

		// Buscar células configuradas
		$cells = $db->select('report_cells', ['template_id' => $id], 'created_at ASC');

		// Buscar fontes de dados disponíveis
		$dataSources = ReportDataSources::getAvailableSources();

		// Ler abas disponíveis no Excel
		$availableSheets = [];
		$filePath = ROOT_PATH . $template['file_path'];
		if (file_exists($filePath)) {
			try {
				$spreadsheet = IOFactory::load($filePath);
				foreach ($spreadsheet->getAllSheets() as $sheet) {
					$availableSheets[] = $sheet->getTitle();
				}
			} catch (Exception $e) {
				error_log('Erro ao ler abas do Excel: ' . $e->getMessage());
			}
		}

		require __DIR__ . '/../views/reports/edit.php';
	}

	/**
	 * Atualizar template
	 */
	public function update($id) {
		Auth::require();

		try {
			// Validar CSRF
			Security::validateCSRF($_POST['csrf_token'] ?? '');

			$db = DB::connect();

			// Verificar se existe
			$templates = $db->select('report_templates', ['id' => $id]);
			if (empty($templates)) {
				throw new Exception('Template não encontrado');
			}

			$template = $templates[0];

			$name = Security::sanitize($_POST['name'] ?? '');
			$description = Security::sanitize($_POST['description'] ?? '');
			$visible = isset($_POST['visible']) ? 1 : 0;

			if (empty($name)) {
				throw new Exception('Nome do relatório é obrigatório');
			}

			// Atualizar dados básicos
			$updateData = [
				'name' => $name,
				'description' => $description,
				'visible' => $visible,
				'updated_at' => date('Y-m-d H:i:s')
			];

			// Upload de novo arquivo (opcional)
			if (!empty($_FILES['excel_file']['name'])) {
				$file = $_FILES['excel_file'];
				$fileTmp = $file['tmp_name'];
				$fileError = $file['error'];

				if ($fileError === UPLOAD_ERR_OK) {
					// ✅ SEGURANÇA: Validar extensão
					$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
					if (!in_array($ext, ['xlsx', 'xls'])) {
						throw new Exception('Apenas arquivos Excel (.xlsx ou .xls) são permitidos');
					}

					// ✅ SEGURANÇA: Validar MIME type
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mimeType = finfo_file($finfo, $fileTmp);
					finfo_close($finfo);

					$allowedMimes = [
						'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
						'application/vnd.ms-excel',
						'application/zip'
					];

					if (!in_array($mimeType, $allowedMimes)) {
						throw new Exception('Tipo de arquivo inválido. MIME type detectado: ' . $mimeType);
					}

					// ✅ SEGURANÇA: Validar tamanho do arquivo (máximo 10MB)
					$maxSize = 10 * 1024 * 1024;
					if ($file['size'] > $maxSize) {
						throw new Exception('Arquivo muito grande. Tamanho máximo: 10MB');
					}

					// ✅ SEGURANÇA: Validar se é Excel válido
					try {
						$spreadsheet = IOFactory::load($fileTmp);
					} catch (Exception $e) {
						throw new Exception('Arquivo Excel inválido: ' . $e->getMessage());
					}

					// Deletar arquivo antigo
					$oldFilePath = ROOT_PATH . $template['file_path'];
					if (file_exists($oldFilePath)) {
						unlink($oldFilePath);
					}

					// Salvar novo arquivo
					$uploadsDir = ROOT_PATH . 'uploads/reports/';
					$uniqueName = uniqid('report_') . '_' . time() . '.' . $ext;
					$filePath = $uploadsDir . $uniqueName;

					if (!move_uploaded_file($fileTmp, $filePath)) {
						throw new Exception('Erro ao salvar arquivo');
					}

					$updateData['file_path'] = 'uploads/reports/' . $uniqueName;
				}
			}

			$db->update('report_templates', $updateData, ['id' => $id]);

			// Atualizar mapeamento de células (deletar antigas e inserir novas)
			$db->delete('report_cells', ['template_id' => $id]);

			$sheets = $_POST['sheets'] ?? [];
			$cells = $_POST['cells'] ?? [];
			$dataSources = $_POST['data_sources'] ?? [];

			if (!empty($cells) && !empty($dataSources)) {
				foreach ($cells as $index => $cellRef) {
					$sheetName = trim($sheets[$index] ?? '');
					$cellRef = strtoupper(trim($cellRef));
					$dataSourceKey = $dataSources[$index] ?? '';

					if (empty($cellRef) || empty($dataSourceKey)) {
						continue;
					}

					if (!preg_match('/^[A-Z]{1,3}[0-9]{1,7}$/', $cellRef)) {
						continue;
					}

					if (!ReportDataSources::isValidSource($dataSourceKey)) {
						continue;
					}

					$db->insert('report_cells', [
						'id' => Security::generateUUID(),
						'template_id' => $id,
						'sheet_name' => !empty($sheetName) ? $sheetName : null,
						'cell_ref' => $cellRef,
						'data_source_key' => $dataSourceKey,
						'created_at' => date('Y-m-d H:i:s')
					]);
				}
			}

			$_SESSION['success'] = 'Template atualizado com sucesso!';
			Core::redirect('/admin/reports');

		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
			Core::redirect('/admin/reports/' . $id . '/edit');
		}
	}

	/**
	 * Deletar template
	 */
	public function destroy($id) {
		Auth::require();

		try {
			Security::validateCSRF($_POST['csrf_token'] ?? '');

			$db = DB::connect();

			// Buscar template
			$templates = $db->select('report_templates', ['id' => $id]);
			if (empty($templates)) {
				throw new Exception('Template não encontrado');
			}

			$template = $templates[0];

			// Deletar arquivo físico
			$filePath = ROOT_PATH . $template['file_path'];
			if (file_exists($filePath)) {
				unlink($filePath);
			}

			// Deletar do banco (células são deletadas automaticamente por CASCADE)
			$db->delete('report_templates', ['id' => $id]);

			$_SESSION['success'] = 'Template deletado com sucesso!';
			Core::redirect('/admin/reports');

		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
			Core::redirect('/admin/reports');
		}
	}
}
