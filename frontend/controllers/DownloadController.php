<?php
/**
 * DownloadController
 * Página de downloads de relatórios para usuários
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadController {

	/**
	 * Página de listagem de relatórios disponíveis
	 */
	public function index() {
		// Requer autenticação (admin ou member)
		if (!Auth::check() && !MemberAuth::check()) {
			$_SESSION['error'] = 'Você precisa estar logado para acessar relatórios';
			Core::redirect('/login');
			return;
		}

		// Buscar apenas relatórios visíveis
		$db = DB::connect();
		$reports = $db->select('report_templates', ['visible' => 1], 'name ASC');

		require ROOT_PATH . 'frontend/pages/downloads.php';
	}

	/**
	 * Gerar e baixar relatório
	 */
	public function generate($templateId) {
		// Requer autenticação (admin ou member)
		if (!Auth::check() && !MemberAuth::check()) {
			$_SESSION['error'] = 'Você precisa estar logado para baixar relatórios';
			Core::redirect('/login');
			return;
		}

		try {
			$db = DB::connect();

			// Buscar template
			$templates = $db->select('report_templates', ['id' => $templateId, 'visible' => 1]);
			if (empty($templates)) {
				$_SESSION['error'] = 'Relatório não encontrado ou não disponível';
				Core::redirect('/downloads');
				return;
			}

			$template = $templates[0];

			// Buscar mapeamento de células
			$cells = $db->select('report_cells', ['template_id' => $templateId]);

			if (empty($cells)) {
				throw new Exception('Este relatório não possui células configuradas');
			}

			// Carregar arquivo Excel
			$filePath = ROOT_PATH . $template['file_path'];
			if (!file_exists($filePath)) {
				throw new Exception('Arquivo template não encontrado');
			}

			$spreadsheet = IOFactory::load($filePath);

			// Preencher células com dados
			foreach ($cells as $cell) {
				$cellRef = $cell['cell_ref'];
				$dataSourceKey = $cell['data_source_key'];
				$sheetName = $cell['sheet_name'] ?? null;

				// Selecionar a aba correta
				if ($sheetName) {
					// Usa aba específica por nome
					try {
						$sheet = $spreadsheet->getSheetByName($sheetName);
						if (!$sheet) {
							throw new Exception("Aba '{$sheetName}' não encontrada no template");
						}
					} catch (Exception $e) {
						error_log("Erro ao acessar aba '{$sheetName}': " . $e->getMessage());
						continue; // Pula esta célula
					}
				} else {
					// Usa primeira aba (comportamento padrão)
					$sheet = $spreadsheet->getActiveSheet();
				}

				// Executar fonte de dados
				$value = ReportDataSources::execute($dataSourceKey);

				// Preencher célula
				$sheet->setCellValue($cellRef, $value);
			}

			// Gerar nome do arquivo para download
			$fileName = $this->sanitizeFileName($template['name']) . '_' . date('Y-m-d_His') . '.xlsx';

			// Headers para download
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="' . $fileName . '"');
			header('Cache-Control: max-age=0');

			// Escrever arquivo direto pro output (sem salvar no disco)
			$writer = new Xlsx($spreadsheet);
			$writer->save('php://output');

			exit;

		} catch (Exception $e) {
			error_log('Erro ao gerar relatório: ' . $e->getMessage());
			$_SESSION['error'] = 'Erro ao gerar relatório: ' . $e->getMessage();
			Core::redirect('/downloads');
		}
	}

	/**
	 * Sanitizar nome do arquivo
	 */
	private function sanitizeFileName($name) {
		// Remove caracteres especiais e espaços
		$name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
		$name = preg_replace('/_+/', '_', $name);
		return trim($name, '_');
	}
}
