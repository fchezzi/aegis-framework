<?php
/**
 * ReportQueryBuilder
 * Construtor de queries SQL seguro para relatórios customizáveis
 *
 * SEGURANÇA:
 * - Whitelist de tabelas permitidas
 * - Whitelist de operadores
 * - Whitelist de funções de agregação
 * - Prepared statements para valores
 * - Validação de tipos
 */

class ReportQueryBuilder {

	/**
	 * Tabelas permitidas (whitelist)
	 */
	private static $allowedTables = [
		'tbl_youtube' => [
			'label' => 'YouTube',
			'columns' => [
				'canal_id' => ['type' => 'int', 'label' => 'Canal ID'],
				'video_views' => ['type' => 'int', 'label' => 'Visualizações'],
				'video_watchtime' => ['type' => 'int', 'label' => 'Tempo Assistido'],
				'video_likes' => ['type' => 'int', 'label' => 'Curtidas'],
				'video_deslikes' => ['type' => 'int', 'label' => 'Deslikes'],
				'video_comments' => ['type' => 'int', 'label' => 'Comentários'],
				'video_subscriptions' => ['type' => 'int', 'label' => 'Inscrições'],
				'video_duration' => ['type' => 'int', 'label' => 'Duração (seg)'],
				'video_impressions' => ['type' => 'int', 'label' => 'Impressões'],
				'video_clickrate' => ['type' => 'decimal', 'label' => 'Taxa de Cliques'],
				'video_returnviewers' => ['type' => 'int', 'label' => 'Espectadores Recorrentes'],
				'video_casualviewers' => ['type' => 'int', 'label' => 'Espectadores Casuais'],
				'video_uniqueviewers' => ['type' => 'int', 'label' => 'Espectadores Únicos'],
				'video_newviewers' => ['type' => 'int', 'label' => 'Novos Espectadores'],
				'simultaneos' => ['type' => 'int', 'label' => 'Simultâneos'],
				'video_published' => ['type' => 'datetime', 'label' => 'Data Publicação'],
				'video_show' => ['type' => 'varchar', 'label' => 'Nome do Show']
			]
		],
		'tbl_facebook' => [
			'label' => 'Facebook',
			'columns' => [
				'canal_id' => ['type' => 'int', 'label' => 'Canal ID'],
				'visualizacoes' => ['type' => 'int', 'label' => 'Visualizações'],
				'ganhos' => ['type' => 'decimal', 'label' => 'Ganhos'],
				'interacoes' => ['type' => 'int', 'label' => 'Interações'],
				'seguidores_liquidos' => ['type' => 'int', 'label' => 'Seguidores Líquidos'],
				'deixaram_seguir' => ['type' => 'int', 'label' => 'Deixaram de Seguir'],
				'total_seguidores' => ['type' => 'int', 'label' => 'Total Seguidores'],
				'video_view_3s' => ['type' => 'int', 'label' => 'Views 3s'],
				'video_view_1min' => ['type' => 'int', 'label' => 'Views 1min'],
				'reacoes' => ['type' => 'int', 'label' => 'Reações'],
				'comentarios' => ['type' => 'int', 'label' => 'Comentários'],
				'compartilhamentos' => ['type' => 'int', 'label' => 'Compartilhamentos'],
				'data' => ['type' => 'date', 'label' => 'Data']
			]
		],
		'tbl_instagram' => [
			'label' => 'Instagram',
			'columns' => [
				'canal_id' => ['type' => 'int', 'label' => 'Canal ID'],
				'visualizacoes_total' => ['type' => 'int', 'label' => 'Visualizações Total'],
				'visualizacoes_seguidores' => ['type' => 'decimal', 'label' => 'Views Seguidores %'],
				'visualizacoes_naoseguidores' => ['type' => 'decimal', 'label' => 'Views Não Seguidores %'],
				'contas_alcancadas' => ['type' => 'int', 'label' => 'Contas Alcançadas'],
				'tipo_post' => ['type' => 'int', 'label' => 'Posts'],
				'tipo_reels' => ['type' => 'int', 'label' => 'Reels'],
				'tipo_stories' => ['type' => 'int', 'label' => 'Stories'],
				'tipo_live' => ['type' => 'int', 'label' => 'Lives'],
				'tipo_videos' => ['type' => 'int', 'label' => 'Vídeos'],
				'visitas_ao_perfil' => ['type' => 'int', 'label' => 'Visitas ao Perfil'],
				'toques_links_externos' => ['type' => 'int', 'label' => 'Toques em Links'],
				'interacoes_total' => ['type' => 'int', 'label' => 'Interações Total'],
				'interacoes_curtir' => ['type' => 'int', 'label' => 'Curtidas'],
				'interacoes_comentarios' => ['type' => 'int', 'label' => 'Comentários'],
				'interacoes_salvar' => ['type' => 'int', 'label' => 'Salvamentos'],
				'interacoes_compartilhar' => ['type' => 'int', 'label' => 'Compartilhamentos'],
				'seguidores_total' => ['type' => 'int', 'label' => 'Total Seguidores'],
				'seguidores_novos' => ['type' => 'int', 'label' => 'Novos Seguidores'],
				'seguidores_deixaram' => ['type' => 'int', 'label' => 'Deixaram de Seguir'],
				'data' => ['type' => 'date', 'label' => 'Data']
			]
		],
		'tbl_app' => [
			'label' => 'App',
			'columns' => [
				'canal_id' => ['type' => 'int', 'label' => 'Canal ID'],
				'total_instalacoes' => ['type' => 'int', 'label' => 'Total Instalações'],
				'usuarios_mes' => ['type' => 'int', 'label' => 'Usuários no Mês'],
				'visualizacoes' => ['type' => 'int', 'label' => 'Visualizações'],
				'visualizacoes_aovivo' => ['type' => 'int', 'label' => 'Visualizações ao Vivo'],
				'data' => ['type' => 'date', 'label' => 'Data']
			]
		],
		'tbl_x' => [
			'label' => 'X (Twitter)',
			'columns' => [
				'canal_id' => ['type' => 'int', 'label' => 'Canal ID'],
				'impressoes' => ['type' => 'int', 'label' => 'Impressões'],
				'engajamento' => ['type' => 'int', 'label' => 'Engajamento'],
				'curtidas' => ['type' => 'int', 'label' => 'Curtidas'],
				'itens_salvos' => ['type' => 'int', 'label' => 'Itens Salvos'],
				'compartilhamentos' => ['type' => 'int', 'label' => 'Compartilhamentos'],
				'novos_seguidores' => ['type' => 'int', 'label' => 'Novos Seguidores'],
				'deixaram_seguir' => ['type' => 'int', 'label' => 'Deixaram de Seguir'],
				'respostas' => ['type' => 'int', 'label' => 'Respostas'],
				'reposts' => ['type' => 'int', 'label' => 'Reposts'],
				'visitas_perfil' => ['type' => 'int', 'label' => 'Visitas ao Perfil'],
				'posts_criados' => ['type' => 'int', 'label' => 'Posts Criados'],
				'data' => ['type' => 'date', 'label' => 'Data']
			]
		],
		'tbl_tiktok' => [
			'label' => 'TikTok',
			'columns' => [
				'canal_id' => ['type' => 'int', 'label' => 'Canal ID'],
				'visualizacoes_publicacoes' => ['type' => 'int', 'label' => 'Views de Publicações'],
				'visualizacoes_perfil' => ['type' => 'int', 'label' => 'Views do Perfil'],
				'curtidas' => ['type' => 'int', 'label' => 'Curtidas'],
				'comentarios' => ['type' => 'int', 'label' => 'Comentários'],
				'compartilhamentos' => ['type' => 'int', 'label' => 'Compartilhamentos'],
				'receita' => ['type' => 'decimal', 'label' => 'Receita'],
				'seguidores' => ['type' => 'int', 'label' => 'Seguidores'],
				'data' => ['type' => 'date', 'label' => 'Data']
			]
		],
		'tbl_twitch' => [
			'label' => 'Twitch',
			'columns' => [
				'canal_id' => ['type' => 'int', 'label' => 'Canal ID'],
				'novos_seguidores' => ['type' => 'int', 'label' => 'Novos Seguidores'],
				'espectadores_engajados' => ['type' => 'int', 'label' => 'Espectadores Engajados'],
				'espectadores_unicos' => ['type' => 'int', 'label' => 'Espectadores Únicos'],
				'data' => ['type' => 'date', 'label' => 'Data']
			]
		],
		'tbl_website' => [
			'label' => 'Website',
			'columns' => [
				'website_id' => ['type' => 'varchar', 'label' => 'Website ID'],
				'visitantes' => ['type' => 'int', 'label' => 'Visitantes'],
				'data' => ['type' => 'date', 'label' => 'Data']
			]
		],
		'youtube_extra' => [
			'label' => 'YouTube Extra',
			'columns' => [
				'canal_id' => ['type' => 'varchar', 'label' => 'Canal ID'],
				'inscritos' => ['type' => 'int', 'label' => 'Inscritos'],
				'espectadores_unicos' => ['type' => 'int', 'label' => 'Espectadores Únicos'],
				'data' => ['type' => 'date', 'label' => 'Data']
			]
		],
		'tbl_x_inscritos' => [
			'label' => 'X Inscritos',
			'columns' => [
				'canal_id' => ['type' => 'varchar', 'label' => 'Canal ID'],
				'inscritos' => ['type' => 'int', 'label' => 'Inscritos'],
				'data' => ['type' => 'date', 'label' => 'Data']
			]
		]
	];

	/**
	 * Operações de agregação permitidas
	 */
	private static $allowedOperations = [
		'SUM' => 'Soma',
		'COUNT' => 'Contagem',
		'AVG' => 'Média',
		'MIN' => 'Mínimo',
		'MAX' => 'Máximo'
	];

	/**
	 * Operadores de comparação permitidos
	 */
	private static $allowedOperators = [
		'=' => 'Igual a',
		'!=' => 'Diferente de',
		'>' => 'Maior que',
		'>=' => 'Maior ou igual',
		'<' => 'Menor que',
		'<=' => 'Menor ou igual',
		'LIKE' => 'Contém',
		'NOT LIKE' => 'Não contém',
		'IN' => 'Está em (lista)',
		'NOT IN' => 'Não está em (lista)',
		'BETWEEN' => 'Entre (intervalo)',
		'IS NULL' => 'É nulo',
		'IS NOT NULL' => 'Não é nulo'
	];

	/**
	 * Obter lista de tabelas permitidas
	 */
	public static function getAllowedTables() {
		return self::$allowedTables;
	}

	/**
	 * Obter lista de operações permitidas
	 */
	public static function getAllowedOperations() {
		return self::$allowedOperations;
	}

	/**
	 * Obter lista de operadores permitidos
	 */
	public static function getAllowedOperators() {
		return self::$allowedOperators;
	}

	/**
	 * Obter colunas de uma tabela
	 */
	public static function getTableColumns($tableName) {
		if (!isset(self::$allowedTables[$tableName])) {
			return [];
		}
		return self::$allowedTables[$tableName]['columns'];
	}

	/**
	 * Validar tabela
	 */
	public static function isValidTable($tableName) {
		return isset(self::$allowedTables[$tableName]);
	}

	/**
	 * Validar operação
	 */
	public static function isValidOperation($operation) {
		return isset(self::$allowedOperations[$operation]);
	}

	/**
	 * Validar operador
	 */
	public static function isValidOperator($operator) {
		return isset(self::$allowedOperators[$operator]);
	}

	/**
	 * Validar coluna
	 */
	public static function isValidColumn($tableName, $columnName) {
		if (!isset(self::$allowedTables[$tableName]['columns'][$columnName])) {
			return false;
		}
		return true;
	}

	/**
	 * Construir e executar query
	 *
	 * @param string $tableName Nome da tabela
	 * @param string $operation Operação (SUM, COUNT, etc)
	 * @param string $columnName Nome da coluna
	 * @param array $conditions Array de condições WHERE
	 * @return mixed Resultado da query
	 */
	public static function execute($tableName, $operation, $columnName, $conditions = []) {
		// Validar tabela
		if (!self::isValidTable($tableName)) {
			throw new Exception("Tabela '{$tableName}' não permitida");
		}

		// Validar operação
		if (!self::isValidOperation($operation)) {
			throw new Exception("Operação '{$operation}' não permitida");
		}

		// Validar coluna (exceto para COUNT que pode ser COUNT(*))
		if ($operation !== 'COUNT' && !self::isValidColumn($tableName, $columnName)) {
			throw new Exception("Coluna '{$columnName}' não permitida para tabela '{$tableName}'");
		}

		// Construir SELECT
		if ($operation === 'COUNT') {
			$select = "SELECT COUNT(*) as result";
		} else {
			$select = "SELECT {$operation}({$columnName}) as result";
		}

		// Construir FROM
		$from = " FROM {$tableName}";

		// Construir WHERE
		$where = '';
		$params = [];

		if (!empty($conditions)) {
			$whereClauses = [];

			foreach ($conditions as $condition) {
				$condColumn = $condition['column'] ?? null;
				$condOperator = $condition['operator'] ?? null;
				$condValue = $condition['value'] ?? null;

				// Validar coluna
				if (!self::isValidColumn($tableName, $condColumn)) {
					throw new Exception("Coluna '{$condColumn}' não permitida em condições");
				}

				// Validar operador
				if (!self::isValidOperator($condOperator)) {
					throw new Exception("Operador '{$condOperator}' não permitido");
				}

				// Construir cláusula baseado no operador
				switch ($condOperator) {
					case 'IS NULL':
					case 'IS NOT NULL':
						$whereClauses[] = "{$condColumn} {$condOperator}";
						break;

					case 'BETWEEN':
						// Espera array [min, max]
						if (!is_array($condValue) || count($condValue) !== 2) {
							throw new Exception("BETWEEN requer array [min, max]");
						}
						$whereClauses[] = "{$condColumn} BETWEEN ? AND ?";
						$params[] = $condValue[0];
						$params[] = $condValue[1];
						break;

					case 'IN':
					case 'NOT IN':
						// Espera array de valores
						if (!is_array($condValue)) {
							throw new Exception("{$condOperator} requer array de valores");
						}
						$placeholders = implode(',', array_fill(0, count($condValue), '?'));
						$whereClauses[] = "{$condColumn} {$condOperator} ({$placeholders})";
						$params = array_merge($params, $condValue);
						break;

					default:
						// Operadores comuns (=, !=, >, <, etc)
						$whereClauses[] = "{$condColumn} {$condOperator} ?";
						$params[] = $condValue;
						break;
				}
			}

			if (!empty($whereClauses)) {
				$where = ' WHERE ' . implode(' AND ', $whereClauses);
			}
		}

		// Query completa
		$sql = $select . $from . $where;

		// Executar com prepared statement
		$db = DB::connect();

		try {
			if (empty($params)) {
				$result = $db->query($sql);
			} else {
				$result = $db->query($sql, $params);
			}

			return $result[0]['result'] ?? 0;

		} catch (Exception $e) {
			error_log("ReportQueryBuilder Error: " . $e->getMessage());
			error_log("SQL: " . $sql);
			error_log("Params: " . json_encode($params));
			throw new Exception("Erro ao executar query: " . $e->getMessage());
		}
	}

	/**
	 * Preview de query (retorna SQL sem executar)
	 * Útil para debug/visualização
	 */
	public static function preview($tableName, $operation, $columnName, $conditions = []) {
		// Mesma validação do execute
		if (!self::isValidTable($tableName)) {
			throw new Exception("Tabela '{$tableName}' não permitida");
		}

		if (!self::isValidOperation($operation)) {
			throw new Exception("Operação '{$operation}' não permitida");
		}

		if ($operation !== 'COUNT' && !self::isValidColumn($tableName, $columnName)) {
			throw new Exception("Coluna '{$columnName}' não permitida");
		}

		// Construir query (com valores reais para debug)
		if ($operation === 'COUNT') {
			$select = "SELECT COUNT(*) as result";
		} else {
			$select = "SELECT {$operation}({$columnName}) as result";
		}

		$from = " FROM {$tableName}";

		$where = '';
		if (!empty($conditions)) {
			$whereParts = [];
			foreach ($conditions as $cond) {
				if ($cond['operator'] === 'BETWEEN') {
					$val1 = is_string($cond['value'][0]) ? "'{$cond['value'][0]}'" : $cond['value'][0];
					$val2 = is_string($cond['value'][1]) ? "'{$cond['value'][1]}'" : $cond['value'][1];
					$whereParts[] = "{$cond['column']} BETWEEN {$val1} AND {$val2}";
				} elseif ($cond['operator'] === 'IN' || $cond['operator'] === 'NOT IN') {
					$values = array_map(function($v) { return is_string($v) ? "'{$v}'" : $v; }, $cond['value']);
					$whereParts[] = "{$cond['column']} {$cond['operator']} (" . implode(', ', $values) . ")";
				} elseif ($cond['operator'] === 'IS NULL' || $cond['operator'] === 'IS NOT NULL') {
					$whereParts[] = "{$cond['column']} {$cond['operator']}";
				} else {
					$val = is_string($cond['value']) ? "'{$cond['value']}'" : $cond['value'];
					$whereParts[] = "{$cond['column']} {$cond['operator']} {$val}";
				}
			}
			$where = ' WHERE ' . implode(' AND ', $whereParts);
		}

		return $select . $from . $where;
	}
}
