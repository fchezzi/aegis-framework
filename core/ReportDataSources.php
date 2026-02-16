<?php
/**
 * ReportDataSources
 * Fontes de dados seguras para preencher relatórios Excel
 *
 * IMPORTANTE: Todas as queries aqui são hardcoded e seguras.
 * Para adicionar nova fonte de dados, adicione uma função no array abaixo.
 */

class ReportDataSources {

	/**
	 * Obter lista de todas as fontes disponíveis (para dropdown no admin)
	 * Combina fontes hardcoded + fontes customizáveis do banco
	 */
	public static function getAvailableSources() {
		// Fontes hardcoded (mantidas por compatibilidade e exemplos)
		$hardcoded = [
			'youtube_views_janeiro' => [
				'label' => 'YouTube - Total Visualizações Janeiro 2026',
				'description' => 'Soma de todas visualizações do YouTube no mês de janeiro/2026'
			],
			'youtube_views_total' => [
				'label' => 'YouTube - Total Visualizações (Todos)',
				'description' => 'Soma de todas visualizações do YouTube (sem filtro de data)'
			],
			'youtube_videos_count' => [
				'label' => 'YouTube - Total de Vídeos',
				'description' => 'Contagem total de vídeos cadastrados'
			],
		];

		// Buscar fontes customizáveis do banco
		$custom = [];
		try {
			$db = DB::connect();
			$sources = $db->select('report_data_sources', [], 'name ASC');

			foreach ($sources as $source) {
				$key = 'custom_' . $source['id'];
				$custom[$key] = [
					'label' => $source['name'],
					'description' => $source['description'] ?? 'Fonte customizável'
				];
			}
		} catch (Exception $e) {
			// Banco ainda não tem a tabela ou erro - ignora
			error_log('Erro ao carregar fontes customizáveis: ' . $e->getMessage());
		}

		// Combinar hardcoded + custom
		return array_merge($hardcoded, $custom);
	}

	/**
	 * Executar fonte de dados e retornar valor
	 */
	public static function execute($sourceKey) {
		// Se começa com 'custom_', é uma fonte customizável
		if (strpos($sourceKey, 'custom_') === 0) {
			return self::executeCustomSource($sourceKey);
		}

		// Fontes hardcoded
		$db = DB::connect();

		switch ($sourceKey) {
			// ========================================
			// YOUTUBE
			// ========================================

			case 'youtube_views_janeiro':
				// Total de visualizações de janeiro/2026
				$result = $db->query("
					SELECT COALESCE(SUM(video_views), 0) as total
					FROM tbl_youtube
					WHERE YEAR(video_published) = 2026
					AND MONTH(video_published) = 1
				");
				return $result[0]['total'] ?? 0;

			case 'youtube_views_total':
				// Total geral de visualizações
				$result = $db->query("
					SELECT COALESCE(SUM(video_views), 0) as total
					FROM tbl_youtube
				");
				return $result[0]['total'] ?? 0;

			case 'youtube_videos_count':
				// Total de vídeos cadastrados
				$result = $db->query("
					SELECT COUNT(*) as total
					FROM tbl_youtube
				");
				return $result[0]['total'] ?? 0;

			// ========================================
			// ADICIONE NOVAS FONTES AQUI
			// ========================================

			default:
				error_log("ReportDataSources: Fonte desconhecida '{$sourceKey}'");
				return 'N/A';
		}
	}

	/**
	 * Executar fonte de dados customizável
	 */
	private static function executeCustomSource($sourceKey) {
		try {
			// Extrair ID da fonte (remove 'custom_' do início)
			$sourceId = str_replace('custom_', '', $sourceKey);

			// Buscar configuração no banco
			$db = DB::connect();
			$sources = $db->select('report_data_sources', ['id' => $sourceId]);

			if (empty($sources)) {
				throw new Exception("Fonte customizável '{$sourceId}' não encontrada");
			}

			$source = $sources[0];
			$conditions = json_decode($source['conditions'], true) ?? [];

			// Executar usando ReportQueryBuilder
			return ReportQueryBuilder::execute(
				$source['table_name'],
				$source['operation'],
				$source['column_name'],
				$conditions
			);

		} catch (Exception $e) {
			error_log("Erro ao executar fonte customizável '{$sourceKey}': " . $e->getMessage());
			return 'ERRO';
		}
	}

	/**
	 * Validar se fonte existe
	 */
	public static function isValidSource($sourceKey) {
		return array_key_exists($sourceKey, self::getAvailableSources());
	}
}
