<?php
/**
 * GoogleSearchConsole - Integração com API do Google Search Console
 *
 * @version 1.0.0
 */

class GoogleSearchConsole {

	private $client;
	private $service;
	private $siteUrl;

	public function __construct() {
		$credentialsPath = ROOT_PATH . 'config/google-service-account.json';

		if (!file_exists($credentialsPath)) {
			throw new Exception('Google Service Account credentials not found. Upload JSON to: ' . $credentialsPath);
		}

		// Verificar se biblioteca Google está instalada
		if (!class_exists('Google_Client')) {
			throw new Exception('Google API Client not installed. Run: composer require google/apiclient');
		}

		$this->client = new Google_Client();
		$this->client->setAuthConfig($credentialsPath);
		$this->client->addScope(Google_Service_SearchConsole::WEBMASTERS_READONLY);

		$this->service = new Google_Service_SearchConsole($this->client);
		$this->siteUrl = APP_URL;
	}

	/**
	 * Sincronizar queries (palavras-chave) dos últimos 7 dias
	 */
	public function syncQueries($startDate = null, $endDate = null) {
		$startDate = $startDate ?? date('Y-m-d', strtotime('-7 days'));
		$endDate = $endDate ?? date('Y-m-d', strtotime('-1 day'));

		$request = new Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
		$request->setStartDate($startDate);
		$request->setEndDate($endDate);
		$request->setDimensions(['query', 'date']);
		$request->setRowLimit(1000);

		$response = $this->service->searchanalytics->query($this->siteUrl, $request);

		$synced = 0;

		foreach ($response->getRows() as $row) {
			$query = $row->getKeys()[0];
			$date = $row->getKeys()[1];
			$clicks = $row->getClicks();
			$impressions = $row->getImpressions();
			$ctr = $row->getCtr();
			$position = $row->getPosition();

			// Insert ou update (usando UNIQUE KEY)
			DB::query("
				INSERT INTO gsc_queries (id, query, date, clicks, impressions, ctr, position)
				VALUES (?, ?, ?, ?, ?, ?, ?)
				ON DUPLICATE KEY UPDATE
					clicks = VALUES(clicks),
					impressions = VALUES(impressions),
					ctr = VALUES(ctr),
					position = VALUES(position)
			", [
				Security::generateUUID(),
				$query,
				$date,
				$clicks,
				$impressions,
				$ctr,
				$position
			]);

			$synced++;
		}

		return $synced;
	}

	/**
	 * Sincronizar páginas dos últimos 7 dias
	 */
	public function syncPages($startDate = null, $endDate = null) {
		$startDate = $startDate ?? date('Y-m-d', strtotime('-7 days'));
		$endDate = $endDate ?? date('Y-m-d', strtotime('-1 day'));

		$request = new Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
		$request->setStartDate($startDate);
		$request->setEndDate($endDate);
		$request->setDimensions(['page', 'date']);
		$request->setRowLimit(1000);

		$response = $this->service->searchanalytics->query($this->siteUrl, $request);

		$synced = 0;

		foreach ($response->getRows() as $row) {
			$pageUrl = $row->getKeys()[0];
			$date = $row->getKeys()[1];
			$clicks = $row->getClicks();
			$impressions = $row->getImpressions();
			$ctr = $row->getCtr();
			$position = $row->getPosition();

			DB::query("
				INSERT INTO gsc_pages (id, page_url, date, clicks, impressions, ctr, position)
				VALUES (?, ?, ?, ?, ?, ?, ?)
				ON DUPLICATE KEY UPDATE
					clicks = VALUES(clicks),
					impressions = VALUES(impressions),
					ctr = VALUES(ctr),
					position = VALUES(position)
			", [
				Security::generateUUID(),
				$pageUrl,
				$date,
				$clicks,
				$impressions,
				$ctr,
				$position
			]);

			$synced++;
		}

		return $synced;
	}

	/**
	 * Obter estatísticas gerais (últimos 30 dias)
	 */
	public static function getStats() {
		$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

		// Total de clicks e impressions
		$totals = DB::query("
			SELECT
				SUM(clicks) as total_clicks,
				SUM(impressions) as total_impressions,
				AVG(ctr) as avg_ctr,
				AVG(position) as avg_position
			FROM gsc_queries
			WHERE date >= ?
		", [$thirtyDaysAgo]);

		// Top 10 queries
		$topQueries = DB::query("
			SELECT
				query,
				SUM(clicks) as total_clicks,
				SUM(impressions) as total_impressions,
				AVG(position) as avg_position
			FROM gsc_queries
			WHERE date >= ?
			GROUP BY query
			ORDER BY total_clicks DESC
			LIMIT 10
		", [$thirtyDaysAgo]);

		// Top 10 pages
		$topPages = DB::query("
			SELECT
				page_url,
				SUM(clicks) as total_clicks,
				SUM(impressions) as total_impressions,
				AVG(position) as avg_position
			FROM gsc_pages
			WHERE date >= ?
			GROUP BY page_url
			ORDER BY total_clicks DESC
			LIMIT 10
		", [$thirtyDaysAgo]);

		// Evolução diária (últimos 7 dias)
		$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
		$evolution = DB::query("
			SELECT
				date,
				SUM(clicks) as clicks,
				SUM(impressions) as impressions
			FROM gsc_queries
			WHERE date >= ?
			GROUP BY date
			ORDER BY date ASC
		", [$sevenDaysAgo]);

		return [
			'totals' => $totals[0] ?? [
				'total_clicks' => 0,
				'total_impressions' => 0,
				'avg_ctr' => 0,
				'avg_position' => 0
			],
			'top_queries' => $topQueries,
			'top_pages' => $topPages,
			'evolution' => $evolution
		];
	}

	/**
	 * Obter resumo para cards do topo
	 */
	public static function getSummary() {
		$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

		$summary = DB::query("
			SELECT
				COUNT(DISTINCT query) as total_keywords,
				SUM(clicks) as total_clicks,
				SUM(impressions) as total_impressions,
				AVG(position) as avg_position
			FROM gsc_queries
			WHERE date >= ?
		", [$thirtyDaysAgo]);

		$errors = DB::query("
			SELECT COUNT(*) as total_errors
			FROM gsc_errors
			WHERE resolved = 0
		");

		return [
			'total_keywords' => $summary[0]['total_keywords'] ?? 0,
			'total_clicks' => $summary[0]['total_clicks'] ?? 0,
			'total_impressions' => $summary[0]['total_impressions'] ?? 0,
			'avg_position' => round($summary[0]['avg_position'] ?? 0, 1),
			'total_errors' => $errors[0]['total_errors'] ?? 0
		];
	}
}
