<?php
/**
 * MenuController
 * Gerenciar itens de menu hierárquico
 */

class MenuController {

	/**
	 * Listar todos os itens de menu
	 */
	public function index() {
		Auth::require();
		$user = Auth::user();

		$db = DB::connect();
		$menuItems = $db->select('menu_items', [], 'ordem ASC');

		// Organizar em hierarquia
		$menuTree = $this->buildTree($menuItems);

		require __DIR__ . '/../views/menu/index.php';
	}

	/**
	 * Exibir formulário de criar item
	 */
	public function create() {
		Auth::require();
		$user = Auth::user();

		$db = DB::connect();

		// Buscar páginas disponíveis
		$pages = $this->getAvailablePages();

		// Buscar módulos instalados
		$modules = $this->getInstalledModules();

		// Buscar grupos para permissões
		$groups = [];
		if (Core::membersEnabled()) {
			$groups = $db->select('groups', [], 'name ASC');
		}

		// Buscar itens de menu (para parent)
		$menuItems = $db->select('menu_items', [], 'label ASC');

		require __DIR__ . '/../views/menu/create.php';
	}

	/**
	 * Salvar novo item de menu
	 */
	public function store() {
		Auth::require();

		// LOCK GLOBAL: Permite apenas 1 insert por vez
		$lockFile = sys_get_temp_dir() . '/aegis_menu_insert.lock';
		$fp = fopen($lockFile, 'c');

		if (!flock($fp, LOCK_EX | LOCK_NB)) {
			fclose($fp);
			$_SESSION['error'] = 'Outra inserção em andamento. Tente novamente.';
			Core::redirect('/admin/menu/create');
			return;
		}

		try {
			// Validar CSRF
			$csrfToken = $_POST['csrf_token'] ?? '';
			Security::validateCSRF($csrfToken);

			$label = Security::sanitize($_POST['label'] ?? '');
			$type = $_POST['type'] ?? 'page';
			$icon = Security::sanitize($_POST['icon'] ?? '');
			$parentId = $_POST['parent_id'] ?? null;
			$visible = isset($_POST['visible']) ? 1 : 0;
			$permissionType = $_POST['permission_type'] ?? 'authenticated';

			// Validações
			if (empty($label)) {
				throw new Exception('O label é obrigatório');
			}

			if (!in_array($type, ['page', 'link', 'category', 'module'])) {
				throw new Exception('Tipo inválido');
			}

			$url = null;
			$pageSlug = null;
			$moduleName = null;

			if ($type === 'link') {
				$url = Security::sanitize($_POST['url'] ?? '');
				if (empty($url)) {
					throw new Exception('URL é obrigatória para links');
				}
			} elseif ($type === 'page') {
				$pageSlug = Security::sanitize($_POST['page_slug'] ?? '');
				if (empty($pageSlug)) {
					throw new Exception('Página é obrigatória');
				}
			} elseif ($type === 'module') {
				$moduleName = Security::sanitize($_POST['module_name'] ?? '');
				if (empty($moduleName)) {
					throw new Exception('Módulo é obrigatório');
				}
				// Buscar URL do módulo
				$modules = $this->getInstalledModules();
				foreach ($modules as $module) {
					if ($module['name'] === $moduleName && !empty($module['public_url'])) {
						$url = $module['public_url'];
						break;
					}
				}
			}

			$db = DB::connect();

			// ✅ ANTI-DUPLICATA: Verificar se já existe item idêntico
			$whereCheck = ['label' => $label, 'type' => $type];
			if ($type === 'page' && $pageSlug) {
				$whereCheck['page_slug'] = $pageSlug;
			} elseif ($type === 'link' && $url) {
				$whereCheck['url'] = $url;
			} elseif ($type === 'module' && $moduleName) {
				$whereCheck['module_name'] = $moduleName;
			}

			$existing = $db->select('menu_items', $whereCheck);
			if (!empty($existing)) {
				throw new Exception('Item de menu já existe com estes dados');
			}

			// Buscar próxima ordem (compatível com Supabase)
			$allItems = $db->select('menu_items', []);
			$maxOrdem = 0;
			foreach ($allItems as $item) {
				if ($item['ordem'] > $maxOrdem) {
					$maxOrdem = $item['ordem'];
				}
			}
			$ordem = $maxOrdem + 1;

			// Se não tem sistema de membros, forçar tudo como público
			if (!Core::membersEnabled()) {
				$permissionType = 'public';
			}

			// Preparar dados para insert
			$insertData = [
				'label' => $label,
				'type' => $type,
				'url' => $url,
				'page_slug' => $pageSlug,
				'module_name' => $moduleName,
				'icon' => $icon,
				'parent_id' => empty($parentId) ? null : $parentId,
				'ordem' => $ordem,
				'visible' => $visible,
				'permission_type' => $permissionType
			];

			// Se tem sistema de membros, incluir permissões avançadas
			if (Core::membersEnabled()) {
				$groupId = null;
				$memberId = null;

				if ($permissionType === 'group') {
					// Múltiplos grupos (array de IDs)
					$groupIds = $_POST['group_ids'] ?? [];
					if (!empty($groupIds) && is_array($groupIds)) {
						$groupId = implode(',', $groupIds);
					}
				} elseif ($permissionType === 'member') {
					$memberId = $_POST['member_id'] ?? null;
				}

				$insertData['group_id'] = $groupId;
				$insertData['member_id'] = $memberId;
			}

			// Supabase não precisa de 'id' (gera automaticamente via gen_random_uuid())
			// MySQL precisa de 'id' explícito
			if (DB_TYPE === 'mysql') {
				$insertData['id'] = Security::generateUUID();
				$insertData['created_at'] = date('Y-m-d H:i:s');
			}

			// Inserir item (charset já configurado no PDO)
			$itemId = $db->insert('menu_items', $insertData);

			$_SESSION['success'] = 'Item de menu criado com sucesso!';

			// Liberar lock
			flock($fp, LOCK_UN);
			fclose($fp);

			Core::redirect('/admin/menu');

		} catch (Exception $e) {
			// Liberar lock em caso de erro
			flock($fp, LOCK_UN);
			fclose($fp);

			$_SESSION['error'] = $e->getMessage();
			Core::redirect('/admin/menu/create');
		}
	}

	/**
	 * Exibir formulário de editar item
	 */
	public function edit($id) {
		Auth::require();
		$user = Auth::user();

		$db = DB::connect();
		$items = $db->select('menu_items', ['id' => $id]);

		if (empty($items)) {
			$_SESSION['error'] = 'Item de menu não encontrado';
			Core::redirect('/admin/menu');
		}

		$item = $items[0];

		// Buscar páginas disponíveis
		$pages = $this->getAvailablePages();

		// Buscar módulos instalados
		$modules = $this->getInstalledModules();

		// Buscar grupos para permissões
		$groups = [];
		if (Core::membersEnabled()) {
			$groups = $db->select('groups', [], 'name ASC');
		}

		// Buscar itens de menu (para parent) - exceto o próprio (compatível Supabase)
		$allMenuItems = $db->select('menu_items', []);
		$menuItems = [];
		foreach ($allMenuItems as $menuItem) {
			if ($menuItem['id'] !== $id) {
				$menuItems[] = $menuItem;
			}
		}
		// Ordenar por label
		usort($menuItems, function($a, $b) {
			return strcmp($a['label'], $b['label']);
		});

		require __DIR__ . '/../views/menu/edit.php';
	}

	/**
	 * Atualizar item de menu
	 */
	public function update($id) {
		Auth::require();

		try {
			// Validar CSRF
			$csrfToken = $_POST['csrf_token'] ?? '';
			Security::validateCSRF($csrfToken);

			$label = Security::sanitize($_POST['label'] ?? '');
			$type = $_POST['type'] ?? 'page';
			$icon = Security::sanitize($_POST['icon'] ?? '');
			$parentId = $_POST['parent_id'] ?? null;
			$visible = isset($_POST['visible']) ? 1 : 0;
			$permissionType = $_POST['permission_type'] ?? 'authenticated';

			// Validações
			if (empty($label)) {
				throw new Exception('O label é obrigatório');
			}

			if (!in_array($type, ['page', 'link', 'category', 'module'])) {
				throw new Exception('Tipo inválido');
			}

			$url = null;
			$pageSlug = null;
			$moduleName = null;

			if ($type === 'link') {
				$url = Security::sanitize($_POST['url'] ?? '');
				if (empty($url)) {
					throw new Exception('URL é obrigatória para links');
				}
			} elseif ($type === 'page') {
				$pageSlug = Security::sanitize($_POST['page_slug'] ?? '');
				if (empty($pageSlug)) {
					throw new Exception('Página é obrigatória');
				}
			} elseif ($type === 'module') {
				$moduleName = Security::sanitize($_POST['module_name'] ?? '');
				if (empty($moduleName)) {
					throw new Exception('Módulo é obrigatório');
				}
				// Buscar URL do módulo
				$modules = $this->getInstalledModules();
				foreach ($modules as $module) {
					if ($module['name'] === $moduleName && !empty($module['public_url'])) {
						$url = $module['public_url'];
						break;
					}
				}
			}

			$db = DB::connect();

			// Se não tem sistema de membros, forçar tudo como público
			if (!Core::membersEnabled()) {
				$permissionType = 'public';
			}

			// Preparar dados para update
			$updateData = [
				'label' => $label,
				'type' => $type,
				'url' => $url,
				'page_slug' => $pageSlug,
				'module_name' => $moduleName,
				'icon' => $icon,
				'parent_id' => empty($parentId) ? null : $parentId,
				'visible' => $visible,
				'permission_type' => $permissionType,
				'updated_at' => date('Y-m-d H:i:s')
			];

			// Se tem sistema de membros, incluir permissões avançadas
			if (Core::membersEnabled()) {
				$groupId = null;
				$memberId = null;

				if ($permissionType === 'group') {
					// Múltiplos grupos (array de IDs)
					$groupIds = $_POST['group_ids'] ?? [];
					if (!empty($groupIds) && is_array($groupIds)) {
						$groupId = implode(',', $groupIds);
					}
				} elseif ($permissionType === 'member') {
					$memberId = $_POST['member_id'] ?? null;
				}

				$updateData['group_id'] = $groupId;
				$updateData['member_id'] = $memberId;
			}

			// Atualizar item
			$db->update('menu_items', $updateData, ['id' => $id]);

			$_SESSION['success'] = 'Item de menu atualizado com sucesso!';
			Core::redirect('/admin/menu');

		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
			Core::redirect('/admin/menu/' . $id . '/edit');
		}
	}

	/**
	 * Deletar item de menu
	 */
	public function destroy($id) {
		Auth::require();

		try {
			Security::validateCSRF($_POST['csrf_token'] ?? '');

			$db = DB::connect();

			// Verificar se item existe
			$items = $db->select('menu_items', ['id' => $id]);
			if (empty($items)) {
				throw new Exception('Item de menu não encontrado');
			}

			// Verificar se tem filhos
			$children = $db->select('menu_items', ['parent_id' => $id]);
			if (!empty($children)) {
				throw new Exception('Não é possível deletar um item que possui subitens. Delete os subitens primeiro.');
			}

			$db->delete('menu_items', ['id' => $id]);

			$_SESSION['success'] = 'Item de menu deletado com sucesso!';
			Core::redirect('/admin/menu');

		} catch (Exception $e) {
			$_SESSION['error'] = $e->getMessage();
			Core::redirect('/admin/menu');
		}
	}

	/**
	 * Atualizar ordem dos itens (AJAX)
	 */
	public function updateOrder() {
		Auth::require();

		try {
			// Ler JSON do body
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Validar CSRF com token do JSON
			if (!isset($data['csrf_token']) || empty($data['csrf_token'])) {
				throw new Exception('CSRF token não fornecido');
			}

			if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
				throw new Exception('CSRF token inválido');
			}

			$tree = $data['tree'] ?? [];

			if (empty($tree)) {
				throw new Exception('Nenhum item para ordenar');
			}

			$db = DB::connect();

			// Processar árvore recursivamente
			$this->processTreeOrder($tree, null, $db);

			Core::json(['success' => true, 'message' => 'Ordem atualizada com sucesso']);

		} catch (Exception $e) {
			Core::json(['success' => false, 'message' => $e->getMessage()], 400);
		}
	}

	/**
	 * Processar ordem da árvore recursivamente
	 */
	private function processTreeOrder($items, $parentId, $db) {
		foreach ($items as $index => $item) {
			$itemId = $item['id'];
			$children = $item['children'] ?? [];

			// Atualizar ordem e parent_id
			$db->update('menu_items', [
				'ordem' => $index + 1,
				'parent_id' => $parentId
			], ['id' => $itemId]);

			// Processar filhos recursivamente
			if (!empty($children)) {
				$this->processTreeOrder($children, $itemId, $db);
			}
		}
	}

	/**
	 * Construir árvore hierárquica
	 */
	private function buildTree($items, $parentId = null) {
		$tree = [];

		foreach ($items as $item) {
			if ($item['parent_id'] == $parentId) {
				$item['children'] = $this->buildTree($items, $item['id']);
				$tree[] = $item;
			}
		}

		return $tree;
	}

	/**
	 * Buscar páginas disponíveis
	 */
	private function getAvailablePages() {
		$db = DB::connect();
		$pages = $db->select('pages', ['ativo' => 1], 'title ASC');

		$result = [];
		foreach ($pages as $page) {
			$result[] = [
				'slug' => $page['slug'],
				'title' => $page['title']
			];
		}

		return $result;
	}

	/**
	 * Buscar módulos instalados
	 */
	private function getInstalledModules() {
		// Buscar lista de módulos instalados
		$installedModules = explode(',', defined('INSTALLED_MODULES') ? INSTALLED_MODULES : '');
		$installedModules = array_filter($installedModules); // Remove vazios

		if (empty($installedModules)) {
			return [];
		}

		$modulesPath = __DIR__ . '/../../modules';
		$modules = [];

		if (!is_dir($modulesPath)) {
			return $modules;
		}

		foreach ($installedModules as $moduleName) {
			$moduleName = trim($moduleName);
			$modulePath = $modulesPath . '/' . $moduleName;
			$moduleJsonPath = $modulePath . '/module.json';

			if (is_dir($modulePath) && file_exists($moduleJsonPath)) {
				$moduleJson = json_decode(file_get_contents($moduleJsonPath), true);

				if ($moduleJson && isset($moduleJson['name'])) {
					$modules[] = [
						'name' => $moduleJson['name'],
						'label' => $moduleJson['label'] ?? $moduleJson['name'],
						'description' => $moduleJson['description'] ?? '',
						'public_url' => $moduleJson['public_url'] ?? null
					];
				}
			}
		}

		return $modules;
	}
}
