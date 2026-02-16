<?php
/**
 * SearchConsoleController - Dashboard Google Search Console
 *
 * @version 1.0.0
 */

class SearchConsoleController extends BaseController {

	public function index() {
		Auth::require();

		$stats = GoogleSearchConsole::getStats();
		$summary = GoogleSearchConsole::getSummary();

		// Verificar se credenciais existem
		$credentialsPath = ROOT_PATH . 'config/google-service-account.json';
		$hasCredentials = file_exists($credentialsPath);

		// Verificar se tem dados
		$hasData = $summary['total_clicks'] > 0 || $summary['total_impressions'] > 0;

		$data = [
			'stats' => $stats,
			'summary' => $summary,
			'has_credentials' => $hasCredentials,
			'has_data' => $hasData
		];

		return $this->render('search-console', $data);
	}

	public function sync() {
		Auth::require();
		Security::validateCSRF($_POST['csrf_token']);

		try {
			$gsc = new GoogleSearchConsole();

			$queriesCount = $gsc->syncQueries();
			$pagesCount = $gsc->syncPages();

			$_SESSION['success'] = sprintf(
				'Sincronizado: %d queries, %d páginas',
				$queriesCount,
				$pagesCount
			);
		} catch (Exception $e) {
			$_SESSION['error'] = 'Erro ao sincronizar: ' . $e->getMessage();
		}

		header('Location: ' . url('/admin/search-console'));
		exit;
	}
}
