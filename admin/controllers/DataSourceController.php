<?php
/**
 * DataSourceController
 * CRUD para fontes de dados customizáveis de relatórios
 */

class DataSourceController {

	/**
	 * Listar todas as fontes de dados
	 */
	public function index() {
		$db = DB::connect();
		$sources = $db->select('report_data_sources', [], 'name ASC');

		require ROOT_PATH . 'admin/views/data-sources/index.php';
	}

	/**
	 * Formulário de criação
	 */
	public function create() {
		$tables = ReportQueryBuilder::getAllowedTables();
		$operations = ReportQueryBuilder::getAllowedOperations();
		$operators = ReportQueryBuilder::getAllowedOperators();

		require ROOT_PATH . 'admin/views/data-sources/create.php';
	}

	/**
	 * Salvar nova fonte de dados
	 */
	public function store() {
		Security::validateCSRF();

		// DEBUG
		error_log("=== DEBUG STORE ===");
		error_log("ALL POST: " . print_r($_POST, true));
		error_log("date_filters: " . json_encode($_POST['date_filters'] ?? 'NOT SET'));
		error_log("conditions: " . json_encode($_POST['conditions'] ?? 'NOT SET'));

		try {
			$name = trim($_POST['name'] ?? '');
			$description = trim($_POST['description'] ?? '');
			$tableName = trim($_POST['table_name'] ?? '');
			$operation = trim($_POST['operation'] ?? '');
			$columnName = trim($_POST['column_name'] ?? '');

			// Validações básicas
			if (empty($name)) {
				throw new Exception('Nome é obrigatório');
			}

			if (empty($tableName) || empty($operation)) {
				throw new Exception('Tabela e operação são obrigatórios');
			}

			// Validar tabela
			if (!ReportQueryBuilder::isValidTable($tableName)) {
				throw new Exception('Tabela não permitida');
			}

			// Validar operação
			if (!ReportQueryBuilder::isValidOperation($operation)) {
				throw new Exception('Operação não permitida');
			}

			// Validar coluna (se não for COUNT)
			if ($operation !== 'COUNT') {
				if (empty($columnName)) {
					throw new Exception('Coluna é obrigatória para esta operação');
				}
				if (!ReportQueryBuilder::isValidColumn($tableName, $columnName)) {
					throw new Exception('Coluna não permitida');
				}
			} else {
				$columnName = '*'; // COUNT sempre usa *
			}

			// Processar filtros de data primeiro
			$conditions = [];
			if (isset($_POST['date_filters']) && is_array($_POST['date_filters'])) {
				foreach ($_POST['date_filters'] as $filter) {
					$column = trim($filter['column'] ?? '');
					$type = trim($filter['type'] ?? '');

					if (empty($column)) {
						continue;
					}

					// Validar coluna
					if (!ReportQueryBuilder::isValidColumn($tableName, $column)) {
						throw new Exception("Coluna '{$column}' não permitida");
					}

					if ($type === 'month') {
						// Filtro por mês
						$month = trim($filter['month'] ?? '');
						$year = trim($filter['year'] ?? '');

						if (empty($month) || empty($year)) {
							continue;
						}

						// Criar condição BETWEEN para o mês completo
						$startDate = "{$year}-{$month}-01";
						$endDate = date('Y-m-t', strtotime($startDate)); // Último dia do mês

						$conditions[] = [
							'column' => $column,
							'operator' => 'BETWEEN',
							'value' => [$startDate, $endDate]
						];

					} elseif ($type === 'period') {
						// Filtro por período
						$startDate = trim($filter['start_date'] ?? '');
						$endDate = trim($filter['end_date'] ?? '');

						if (empty($startDate) || empty($endDate)) {
							continue;
						}

						$conditions[] = [
							'column' => $column,
							'operator' => 'BETWEEN',
							'value' => [$startDate, $endDate]
						];
					}
				}
			}

			// Processar outras condições
			if (isset($_POST['conditions']) && is_array($_POST['conditions'])) {
				foreach ($_POST['conditions'] as $cond) {
					$condColumn = trim($cond['column'] ?? '');
					$condOperator = trim($cond['operator'] ?? '');
					$condValue = trim($cond['value'] ?? '');

					// Pular condições vazias
					if (empty($condColumn) || empty($condOperator)) {
						continue;
					}

					// Validar coluna
					if (!ReportQueryBuilder::isValidColumn($tableName, $condColumn)) {
						throw new Exception("Coluna '{$condColumn}' não permitida");
					}

					// Validar operador
					if (!ReportQueryBuilder::isValidOperator($condOperator)) {
						throw new Exception("Operador '{$condOperator}' não permitido");
					}

					// Tratar valores especiais
					if ($condOperator === 'IS NULL' || $condOperator === 'IS NOT NULL') {
						$condValue = null;
					} elseif ($condOperator === 'BETWEEN') {
						// Espera formato: "valor1,valor2"
						$values = explode(',', $condValue);
						if (count($values) !== 2) {
							throw new Exception("BETWEEN requer dois valores separados por vírgula");
						}
						$condValue = [trim($values[0]), trim($values[1])];
					} elseif ($condOperator === 'IN' || $condOperator === 'NOT IN') {
						// Espera formato: "valor1,valor2,valor3"
						$values = explode(',', $condValue);
						$condValue = array_map('trim', $values);
					}

					$conditions[] = [
						'column' => $condColumn,
						'operator' => $condOperator,
						'value' => $condValue
					];
				}
			}

			// Testar query antes de salvar
			try {
				$testResult = ReportQueryBuilder::execute($tableName, $operation, $columnName, $conditions);
			} catch (Exception $e) {
				throw new Exception("Erro ao testar query: " . $e->getMessage());
			}

			// Salvar no banco
			$db = DB::connect();

			$id = Core::generateUuid();

			$data = [
				'id' => $id,
				'name' => $name,
				'description' => $description,
				'table_name' => $tableName,
				'operation' => $operation,
				'column_name' => $columnName,
				'conditions' => json_encode($conditions),
				'created_at' => date('Y-m-d H:i:s')
			];

			$db->insert('report_data_sources', $data);

			$_SESSION['success'] = "Fonte de dados '{$name}' criada com sucesso! Resultado do teste: {$testResult}";
			Core::redirect('/admin/data-sources');

		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
			Core::redirect('/admin/data-sources/create');
		}
	}

	/**
	 * Formulário de edição
	 */
	public function edit($id) {
		$db = DB::connect();
		$sources = $db->select('report_data_sources', ['id' => $id]);

		if (empty($sources)) {
			$_SESSION['error'] = 'Fonte de dados não encontrada';
			Core::redirect('/admin/data-sources');
			return;
		}

		$source = $sources[0];
		$source['conditions'] = json_decode($source['conditions'], true) ?? [];

		$tables = ReportQueryBuilder::getAllowedTables();
		$operations = ReportQueryBuilder::getAllowedOperations();
		$operators = ReportQueryBuilder::getAllowedOperators();

		require ROOT_PATH . 'admin/views/data-sources/edit.php';
	}

	/**
	 * Atualizar fonte de dados
	 */
	public function update($id) {
		Security::validateCSRF();

		try {
			$name = trim($_POST['name'] ?? '');
			$description = trim($_POST['description'] ?? '');
			$tableName = trim($_POST['table_name'] ?? '');
			$operation = trim($_POST['operation'] ?? '');
			$columnName = trim($_POST['column_name'] ?? '');

			// Validações (mesmas da store)
			if (empty($name)) {
				throw new Exception('Nome é obrigatório');
			}

			if (empty($tableName) || empty($operation)) {
				throw new Exception('Tabela e operação são obrigatórios');
			}

			if (!ReportQueryBuilder::isValidTable($tableName)) {
				throw new Exception('Tabela não permitida');
			}

			if (!ReportQueryBuilder::isValidOperation($operation)) {
				throw new Exception('Operação não permitida');
			}

			if ($operation !== 'COUNT') {
				if (empty($columnName)) {
					throw new Exception('Coluna é obrigatória para esta operação');
				}
				if (!ReportQueryBuilder::isValidColumn($tableName, $columnName)) {
					throw new Exception('Coluna não permitida');
				}
			} else {
				$columnName = '*';
			}

			// Processar filtros de data primeiro
			$conditions = [];
			if (isset($_POST['date_filters']) && is_array($_POST['date_filters'])) {
				foreach ($_POST['date_filters'] as $filter) {
					$column = trim($filter['column'] ?? '');
					$type = trim($filter['type'] ?? '');

					if (empty($column)) {
						continue;
					}

					if (!ReportQueryBuilder::isValidColumn($tableName, $column)) {
						throw new Exception("Coluna '{$column}' não permitida");
					}

					if ($type === 'month') {
						$month = trim($filter['month'] ?? '');
						$year = trim($filter['year'] ?? '');

						if (empty($month) || empty($year)) {
							continue;
						}

						$startDate = "{$year}-{$month}-01";
						// Usar primeiro dia do mês seguinte para incluir todo o último dia
						$nextMonth = date('Y-m-01', strtotime("{$startDate} +1 month"));

						// Usar >= e < ao invés de BETWEEN para datetime
						$conditions[] = [
							'column' => $column,
							'operator' => '>=',
							'value' => $startDate
						];
						$conditions[] = [
							'column' => $column,
							'operator' => '<',
							'value' => $nextMonth
						];

					} elseif ($type === 'period') {
						$startDate = trim($filter['start_date'] ?? '');
						$endDate = trim($filter['end_date'] ?? '');

						if (empty($startDate) || empty($endDate)) {
							continue;
						}

						$conditions[] = [
							'column' => $column,
							'operator' => 'BETWEEN',
							'value' => [$startDate, $endDate]
						];
					}
				}
			}

			// Processar outras condições
			if (isset($_POST['conditions']) && is_array($_POST['conditions'])) {
				foreach ($_POST['conditions'] as $cond) {
					$condColumn = trim($cond['column'] ?? '');
					$condOperator = trim($cond['operator'] ?? '');
					$condValue = trim($cond['value'] ?? '');

					if (empty($condColumn) || empty($condOperator)) {
						continue;
					}

					if (!ReportQueryBuilder::isValidColumn($tableName, $condColumn)) {
						throw new Exception("Coluna '{$condColumn}' não permitida");
					}

					if (!ReportQueryBuilder::isValidOperator($condOperator)) {
						throw new Exception("Operador '{$condOperator}' não permitido");
					}

					if ($condOperator === 'IS NULL' || $condOperator === 'IS NOT NULL') {
						$condValue = null;
					} elseif ($condOperator === 'BETWEEN') {
						$values = explode(',', $condValue);
						if (count($values) !== 2) {
							throw new Exception("BETWEEN requer dois valores separados por vírgula");
						}
						$condValue = [trim($values[0]), trim($values[1])];
					} elseif ($condOperator === 'IN' || $condOperator === 'NOT IN') {
						$values = explode(',', $condValue);
						$condValue = array_map('trim', $values);
					}

					$conditions[] = [
						'column' => $condColumn,
						'operator' => $condOperator,
						'value' => $condValue
					];
				}
			}

			// Testar query
			try {
				$testResult = ReportQueryBuilder::execute($tableName, $operation, $columnName, $conditions);
			} catch (Exception $e) {
				throw new Exception("Erro ao testar query: " . $e->getMessage());
			}

			// Atualizar no banco
			$db = DB::connect();

			$data = [
				'name' => $name,
				'description' => $description,
				'table_name' => $tableName,
				'operation' => $operation,
				'column_name' => $columnName,
				'conditions' => json_encode($conditions),
				'updated_at' => date('Y-m-d H:i:s')
			];

			$db->update('report_data_sources', $data, ['id' => $id]);

			$_SESSION['success'] = "Fonte de dados '{$name}' atualizada! Resultado do teste: {$testResult}";
			Core::redirect('/admin/data-sources');

		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
			Core::redirect('/admin/data-sources/edit/' . $id);
		}
	}

	/**
	 * Deletar fonte de dados
	 */
	public function destroy($id) {
		Security::validateCSRF();

		try {
			$db = DB::connect();

			// Verificar se existe
			$sources = $db->select('report_data_sources', ['id' => $id]);
			if (empty($sources)) {
				throw new Exception('Fonte de dados não encontrada');
			}

			// Deletar
			$db->delete('report_data_sources', ['id' => $id]);

			$_SESSION['success'] = 'Fonte de dados deletada com sucesso!';

		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
		}

		Core::redirect('/admin/data-sources');
	}

	/**
	 * Duplicar fonte de dados
	 */
	public function duplicate($id) {
		try {
			$db = DB::connect();

			// Buscar fonte original
			$sources = $db->select('report_data_sources', ['id' => $id]);
			if (empty($sources)) {
				throw new Exception('Fonte de dados não encontrada');
			}

			$original = $sources[0];

			// Criar cópia com novo ID e nome
			$newId = Core::generateUuid();
			$newName = $original['name'] . ' (Cópia)';

			$data = [
				'id' => $newId,
				'name' => $newName,
				'description' => $original['description'],
				'table_name' => $original['table_name'],
				'operation' => $original['operation'],
				'column_name' => $original['column_name'],
				'conditions' => $original['conditions'],
				'created_at' => date('Y-m-d H:i:s')
			];

			$db->insert('report_data_sources', $data);

			$_SESSION['success'] = "Fonte de dados duplicada com sucesso! Redirecionando para edição...";
			Core::redirect('/admin/data-sources/edit/' . $newId);

		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
			Core::redirect('/admin/data-sources');
		}
	}

	/**
	 * AJAX: Obter colunas de uma tabela
	 */
	public function getColumns() {
		header('Content-Type: application/json');

		$tableName = $_GET['table'] ?? '';

		if (empty($tableName)) {
			echo json_encode(['error' => 'Tabela não informada']);
			exit;
		}

		$columns = ReportQueryBuilder::getTableColumns($tableName);

		if (empty($columns)) {
			echo json_encode(['error' => 'Tabela não permitida']);
			exit;
		}

		echo json_encode(['columns' => $columns]);
		exit;
	}

	/**
	 * AJAX: Preview de query
	 */
	public function preview() {
		header('Content-Type: application/json');

		try {
			$tableName = $_POST['table_name'] ?? '';
			$operation = $_POST['operation'] ?? '';
			$columnName = $_POST['column_name'] ?? '';

			if ($operation === 'COUNT') {
				$columnName = '*';
			}

			// Processar filtros de data primeiro (igual ao store)
			$conditions = [];
			if (isset($_POST['date_filters']) && is_array($_POST['date_filters'])) {
				foreach ($_POST['date_filters'] as $filter) {
					$column = trim($filter['column'] ?? '');
					$type = trim($filter['type'] ?? '');

					if (empty($column)) {
						continue;
					}

					if ($type === 'month') {
						$month = trim($filter['month'] ?? '');
						$year = trim($filter['year'] ?? '');

						if (empty($month) || empty($year)) {
							continue;
						}

						$startDate = "{$year}-{$month}-01";
						// Usar primeiro dia do mês seguinte para incluir todo o último dia
						$nextMonth = date('Y-m-01', strtotime("{$startDate} +1 month"));

						// Usar >= e < ao invés de BETWEEN para datetime
						$conditions[] = [
							'column' => $column,
							'operator' => '>=',
							'value' => $startDate
						];
						$conditions[] = [
							'column' => $column,
							'operator' => '<',
							'value' => $nextMonth
						];

					} elseif ($type === 'period') {
						$startDate = trim($filter['start_date'] ?? '');
						$endDate = trim($filter['end_date'] ?? '');

						if (empty($startDate) || empty($endDate)) {
							continue;
						}

						$conditions[] = [
							'column' => $column,
							'operator' => 'BETWEEN',
							'value' => [$startDate, $endDate]
						];
					}
				}
			}

			// Processar outras condições
			if (isset($_POST['conditions']) && is_array($_POST['conditions'])) {
				foreach ($_POST['conditions'] as $cond) {
					$condColumn = trim($cond['column'] ?? '');
					$condOperator = trim($cond['operator'] ?? '');
					$condValue = trim($cond['value'] ?? '');

					if (empty($condColumn) || empty($condOperator)) {
						continue;
					}

					// Tratar valores especiais
					if ($condOperator === 'IS NULL' || $condOperator === 'IS NOT NULL') {
						$condValue = null;
					} elseif ($condOperator === 'BETWEEN') {
						$values = explode(',', $condValue);
						if (count($values) === 2) {
							$condValue = [trim($values[0]), trim($values[1])];
						}
					} elseif ($condOperator === 'IN' || $condOperator === 'NOT IN') {
						$values = explode(',', $condValue);
						$condValue = array_map('trim', $values);
					}

					$conditions[] = [
						'column' => $condColumn,
						'operator' => $condOperator,
						'value' => $condValue
					];
				}
			}

			$sql = ReportQueryBuilder::preview($tableName, $operation, $columnName, $conditions);
			$result = ReportQueryBuilder::execute($tableName, $operation, $columnName, $conditions);

			echo json_encode([
				'success' => true,
				'sql' => $sql,
				'result' => $result
			]);

		} catch (Exception $e) {
			echo json_encode([
				'success' => false,
				'error' => $e->getMessage()
			]);
		}

		exit;
	}
}
