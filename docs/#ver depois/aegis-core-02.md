# AEGIS Framework - Pasta /core/ (Parte 2)

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-18
**Arquivos:** 21-40

[← Voltar ao índice](aegis-core.md)

---

## 🟢 ARQUIVOS ANALISADOS (7 arquivos)

### 21. Logger.php (610 linhas) - 10/10

**Função:** Sistema de logging PSR-3 compliant com rotação e alertas

**Recursos:**

1. **PSR-3 Log Levels (RFC 5424) (linha 36-56)**
   ```php
   const EMERGENCY = 'emergency'; // 7 - Sistema inutilizável
   const ALERT     = 'alert';     // 6 - Ação imediata
   const CRITICAL  = 'critical';  // 5 - Condições críticas
   const ERROR     = 'error';     // 4 - Erros runtime
   const WARNING   = 'warning';   // 3 - Avisos
   const NOTICE    = 'notice';    // 2 - Eventos significativos
   const INFO      = 'info';      // 1 - Informativo
   const DEBUG     = 'debug';     // 0 - Debug (apenas dev)
   ```

2. **Configuração completa (linha 62-71)**
   ```php
   Logger::configure([
       'path' => '/custom/logs/',
       'level' => Logger::WARNING,  // Log apenas warning+
       'daily' => true,             // Um arquivo por dia
       'max_files' => 30,           // Últimos 30 dias
       'max_file_size' => 5242880,  // 5MB
       'alert_email' => 'admin@example.com'
   ]);
   ```

3. **Interpolação de placeholders (linha 147-169)**
   ```php
   Logger::info('User {name} logged in from {ip}', [
       'name' => 'João',
       'ip' => '192.168.1.1'
   ]);
   // Output: User João logged in from 192.168.1.1
   ```
   Suporta: string, numeric, __toString, array/object (JSON), bool, null

4. **Formatação de linha (linha 174-198)**
   ```
   [2026-01-18 14:30:45] [ERROR] Payment failed | {"order_id":123,"amount":50.00}
     Exception: RuntimeException: Gateway timeout
     File: /app/payments/Gateway.php:45
     Trace:
       Gateway->charge()
       PaymentController->process()
   ```

5. **Multi-destination logging (linha 203-236)**
   - **Arquivo:** Via writeToFile()
   - **PHP error_log:** Níveis error+ (linha 219-221)
   - **Email alert:** Níveis critical+ (linha 224-226)
   - **Custom handlers:** Adicionais via addHandler() (linha 229-235)

6. **Rotação inteligente (linha 244-291)**
   - **Daily mode:** Um arquivo por dia (`aegis-2026-01-18.log`)
   - **Single mode:** Rotação por tamanho
   ```
   aegis.log (5MB)
   → aegis.log.1 (5MB)
   → aegis.log.2 (5MB)
   → ... (até max_files)
   ```

7. **Auto-cleanup (linha 296-319)**
   - Executa 1% das vezes (performance)
   - Remove arquivos além de max_files
   - Ordena por filemtime (mantém mais recentes)

8. **Email alerts (linha 324-374)**
   - Níveis: CRITICAL, ALERT, EMERGENCY
   - Emoji no subject: 🔴 💀 🟠
   - Email completo:
     - Mensagem
     - Contexto (exception, custom data)
     - Servidor, URL, IP
     - Timestamp
   - Headers: X-Priority: 1, X-Mailer

9. **Métodos PSR-3 (linha 380-418)**
   ```php
   $logger = Logger::getInstance();
   $logger->emergency('Sistema down');
   $logger->alert('Database unreachable');
   $logger->critical('Disk full');
   $logger->error('Payment failed', ['order' => 123]);
   $logger->warning('Cache miss rate high');
   $logger->notice('New user registered');
   $logger->info('Cron job completed');
   $logger->debug('Query executed', ['sql' => '...']);
   ```

10. **Static API (linha 424-454)**
    ```php
    Logger::logError('Failed to connect');
    Logger::logInfo('User logged in');
    Logger::logDebug('Cache hit');
    ```

11. **Utility methods (linha 463-505)**
    - `query($sql, $bindings, $time)` - Log SQL queries
    - `request()` - Log HTTP request (method, URI, IP)
    - `security($event, $context)` - Security events
    - `audit($action, $userId, $context)` - Auditoria

12. **Admin methods (linha 514-609)**
    - `read($lines, $level)` - Ler últimas N linhas (filtro por level)
    - `clear()` - Deletar todos logs
    - `getSize()` - Tamanho total em bytes
    - `getFiles()` - Lista arquivos com metadata (nome, size, modified)

13. **Debug auto-skip (linha 409-414)**
    ```php
    public function debug($message, $context = []) {
        if (class_exists('CoreEnvironment') && !CoreEnvironment::isDev()) {
            return; // Debug apenas em dev
        }
        $this->write(self::DEBUG, $message, $context);
    }
    ```

**Pontos Fortes:**
- PSR-3 compliant: Standard da indústria
- RFC 5424 levels: Hierarquia clara
- Rotação automática: Previne disk full
- Email alerts: Notificação crítica automática
- Exception logging: Stack trace completo
- Context interpolation: Placeholders {key}
- Multi-handler: Extensível
- Performance: Cleanup probabilístico (1%), file locking
- Admin tools: read(), clear(), getFiles()
- Environment-aware: Debug apenas dev

**Pontos Fracos:**
- Nenhum crítico
- Email via mail() nativo (poderia usar PHPMailer) - mas é suficiente

**Classificação:** 100% CORE-AEGIS

---

### 22. MemberAuth.php (337 linhas) - 10/10

**Função:** Autenticação de usuários/membros do site (não-admin)

**Recursos:**

1. **Login com segurança (linha 38-85)**
   - **Rate limiting:** 5 tentativas em 5 minutos POR EMAIL
   - **Password upgrade:** Rehash automático transparente
   - **Session security:** Regenera ID após login
   - **Reset rate limit:** Limpa rate limit após sucesso
   ```php
   if (!RateLimit::check($rateLimitKey, 5, 300)) {
       throw new Exception('Muitas tentativas. Aguarde 5 minutos.');
   }

   $result = Security::verifyAndRehash($password, $member['password']);
   if ($result['newHash'] !== null) {
       $db->update('members', ['password' => $result['newHash']], ['id' => $member['id']]);
   }
   ```

2. **Logout limpo (linha 90-98)**
   - Unset todas variáveis de sessão do member
   - Session regenerate (anti session fixation)
   - Não destrói sessão inteira (preserva flash messages)

3. **Revalidação periódica (linha 103-128)**
   ```php
   public static function check() {
       // Revalidar a cada 5 minutos (300s)
       if (($now - $lastCheck) > 300) {
           // Verificar se member ainda ativo
           $members = $db->select('members', ['id' => $_SESSION['member_id'], 'ativo' => 1]);

           if (empty($members)) {
               // Desativado/deletado - logout automático
               self::logout();
               return false;
           }

           $_SESSION['member_last_validation'] = $now;
       }
   }
   ```
   - Valida se member ainda está ativo no DB
   - Auto-logout se desativado/deletado
   - TTL: 5 minutos (não consulta DB a cada request)

4. **Member() atualizado (linha 133-149)**
   - Busca dados frescos do DB (inclui avatar)
   - Auto-logout se deletado entre requests
   - Retorna null se não logado

5. **Middleware require() (linha 154-158)**
   ```php
   public static function require() {
       if (!self::check()) {
           Core::redirect('/login');
       }
   }
   ```

6. **Criar member (linha 163-209)**
   - Valida email (FILTER_VALIDATE_EMAIL)
   - Valida força de senha (Security::validatePasswordStrength)
   - Verifica duplicata de email
   - UUID (não auto_increment)
   - Hash com Security::hashPassword
   - **Grupo padrão:** Adiciona DEFAULT_MEMBER_GROUP automaticamente
   - **Grupos customizados:** Via Permission::addMemberToGroup
   ```php
   if (defined('DEFAULT_MEMBER_GROUP') && DEFAULT_MEMBER_GROUP !== null) {
       $groupIds[] = DEFAULT_MEMBER_GROUP;
   }

   foreach ($groupIds as $groupId) {
       Permission::addMemberToGroup($memberId, $groupId);
   }
   ```

7. **Update member (linha 214-260)**
   - **Sanitização:** Todos campos sanitizados
   - **Email:** Valida + verifica duplicata (exceto próprio)
   - **Password:** Valida força + rehash
   - **Avatar:** Sanitiza path
   - **Ativo:** Type cast para int
   - Retorna true se updateData vazio

8. **Delete member (linha 265-276)**
   - Cascade delete:
     1. member_groups
     2. member_page_permissions
     3. members
   ```php
   $db->delete('member_groups', ['member_id' => $memberId]);
   $db->delete('member_page_permissions', ['member_id' => $memberId]);
   $db->delete('members', ['id' => $memberId]);
   ```

9. **getPaginasPermitidas() (linha 284-335)**
   - Scan filesystem: `frontend/pages/*.php`
   - Extrai título via regex: `$pageTitle = "..."`
   - Fallback título: slug
   - **TODO comentário:** Sistema de permissões futuro (linha 330)
   - Retorna todas se `!Core::membersEnabled()`

**Pontos Fortes:**
- Separação clara: Admin vs Member auth
- Rate limiting independente: Por email, não IP
- Revalidação periódica: Detecta desativação/deleção
- Password upgrade: Transparente e automático
- Cascade delete: Limpa permissões associadas
- UUID: Não auto_increment
- Security-first: Validações robustas

**Pontos Fracos:**
- `getPaginasPermitidas()` tem TODO (linha 330) - sistema de permissões incompleto
- Scan filesystem toda vez (poderia cachear lista de páginas)

**Classificação:** 100% CORE-AEGIS

---

### 23. MenuBuilder.php (63 linhas) - 9/10

**Função:** Builder simplificado de menu hierárquico com permissões

**Recursos:**

1. **Render completo (linha 35-38)**
   ```php
   public static function render($memberId = null) {
       $items = self::getFilteredMenu($memberId);
       return MenuRenderer::render($items);
   }
   ```
   - One-liner: Filtra + renderiza
   - Delega filtragem para MenuPermissionChecker
   - Delega renderização para MenuRenderer

2. **getFilteredMenu() (linha 46-54)**
   - Busca todos itens visíveis do DB
   - Ordena por coluna `ordem` ASC
   - Delega filtragem de permissões
   ```php
   $allItems = $db->select('menu_items', ['visible' => 1], 'ordem ASC');
   return MenuPermissionChecker::filter($allItems, $memberId);
   ```

3. **clearCache() (linha 59-61)**
   ```php
   public static function clearCache() {
       MenuPermissionChecker::clearCache();
   }
   ```
   Proxy para invalidação de cache

**Pontos Fortes:**
- Separation of concerns: Builder → Checker → Renderer
- API simples: `MenuBuilder::render($memberId)`
- Stateless: Sem propriedades de instância
- Cache invalidation: Método exposto

**Pontos Fracos:**
- Muito simples (quase um proxy)
- Não documenta estrutura esperada de `menu_items` table
- Sem hierarquia (parent/child) - apesar de mencionar no doc block

**Classificação:** 100% CORE-AEGIS

---

### 24. MenuPermissionChecker.php (275 linhas) - 10/10

**Função:** Sistema de filtragem de menu por permissões com cache otimizado

**Recursos:**

1. **Filter principal (linha 21-47)**
   - Inicializa PermissionManager (reutiliza cache)
   - Cache em sessão: `menu_perms_{memberId}`
   - TTL: 60 segundos
   - Pre-fetch inteligente na cache miss
   ```php
   if ($memberId !== null) {
       PermissionManager::initialize($memberId);
   }

   $cachedData = self::getCachedData($cacheKey);
   if ($cachedData !== null) {
       return self::filterWithCache($items, $cachedData, $memberId);
   }
   ```

2. **Pre-fetch otimizado (linha 56-81)**
   - **Grupos do member:** Via PermissionManager (O(1))
   - **Páginas:** Indexed por slug E por ID
   - **Módulos:** Metadata de todos instalados
   ```php
   $data['memberGroupIds'] = PermissionManager::getMemberGroups($memberId);

   foreach ($allPages as $page) {
       $data['pagesBySlug'][$page['slug']] = $page;
       $data['pagesById'][$page['id']] = $page;
   }

   $data['moduleMetadata'] = self::getModuleMetadata();
   ```

3. **canAccessItem() (linha 108-144)**
   - **Páginas públicas:** Retorna true direto (linha 116-119)
   - **Permission type:** public/authenticated/group/member
   - **Módulos:** Delega para PermissionManager (linha 126-128)
   - **Páginas privadas:** Permissões granulares (linha 131-141)

4. **Permission types (linha 158-192)**
   ```php
   switch ($item['permission_type']) {
       case 'public':
           return true;

       case 'authenticated':
           return ($memberId !== null);

       case 'group':
           // Múltiplos grupos (CSV)
           $allowedGroupIds = explode(',', $item['group_id']);
           foreach ($allowedGroupIds as $allowedGroupId) {
               if (in_array(trim($allowedGroupId), $data['memberGroupIds'])) {
                   return true;
               }
           }
           return false;

       case 'member':
           return ($memberId === $item['member_id']);
   }
   ```
   - **public:** Todos
   - **authenticated:** Qualquer member logado
   - **group:** CSV de group_ids (OR logic)
   - **member:** Member específico

5. **Module access (linha 150-153)**
   ```php
   private static function canAccessModule($item, $data, $memberId) {
       return PermissionManager::canAccessModule($memberId, $item['module_name']);
   }
   ```
   Delega para PermissionManager (O(1) lookup)

6. **Page permission (linha 203-206)**
   ```php
   private static function hasPagePermission($pageId, $data, $memberId) {
       return PermissionManager::canAccessPage($memberId, $pageId);
   }
   ```
   Delega para PermissionManager (O(1) lookup)

7. **Module metadata (linha 211-232)**
   - Le INSTALLED_MODULES (CSV constant)
   - Carrega `modules/{name}/module.json`
   - Indexed por module name
   ```php
   foreach ($installedModules as $moduleName) {
       $moduleJsonPath = ROOT_PATH . "modules/{$moduleName}/module.json";
       if (file_exists($moduleJsonPath)) {
           $json = @file_get_contents($moduleJsonPath);
           $metadata[$moduleName] = json_decode($json, true);
       }
   }
   ```

8. **Session cache (linha 237-260)**
   - Storage: `$_SESSION['menu_cache'][$key]`
   - TTL check: `time() - $cached['timestamp'] < 60`
   - Evita DB queries repetidas
   ```php
   $_SESSION['menu_cache'][$key] = [
       'timestamp' => time(),
       'data' => $data
   ];
   ```

9. **Cache invalidation (linha 266-273)**
   ```php
   public static function clearCache() {
       unset($_SESSION['menu_cache']);
       PermissionManager::invalidateAll();
   }
   ```
   Limpa cache do menu E do PermissionManager

**Pontos Fortes:**
- **Performance:** Session cache (60s TTL), pre-fetch, indexed lookups
- **Delegation:** PermissionManager para permissões granulares (O(1))
- **Multi-grupo:** CSV support com OR logic
- **Modular:** Suporta módulos dinâmicos via JSON
- **Cache compartilhado:** Reutiliza PermissionManager cache
- **Dual invalidation:** Limpa ambos caches

**Pontos Fracos:**
- Nenhum crítico
- Session cache cresce indefinidamente (sem cleanup de keys antigas) - mas expira naturalmente

**Classificação:** 100% CORE-AEGIS

---

### 25. MenuRenderer.php (217 linhas) - 9.5/10

**Função:** Renderizador recursivo de menu hierárquico com detecção de estado ativo

**Recursos:**

1. **Render principal (linha 13-42)**
   ```php
   public static function render($items, $config = []) {
       $config = array_merge([
           'containerClass' => 'menu',
           'itemClass' => 'menu-item',
           'linkClass' => 'menu-link',
           'activeClass' => 'active',
           'hasChildrenClass' => 'has-children'
       ], $config);

       $tree = self::buildTree($items);
       $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

       return self::renderTree($tree, $config, $currentPath);
   }
   ```
   - Config completo: Classes CSS customizáveis
   - Build tree: Organiza hierarquia
   - Current path: Para detecção de active

2. **buildTree() recursivo (linha 20-31)**
   ```php
   private static function buildTree($items, $parentId = null) {
       $tree = [];
       foreach ($items as $item) {
           if ($item['parent_id'] == $parentId) {
               $item['children'] = self::buildTree($items, $item['id']);
               $tree[] = $item;
           }
       }
       return $tree;
   }
   ```
   - Parent-child matching: `parent_id == $parentId`
   - Recursão: Processa filhos automaticamente
   - Array flat → tree hierárquico

3. **renderTree() recursivo (linha 52-89)**
   ```php
   private static function renderTree($tree, $config, $currentPath, $level = 0) {
       $html = '<ul class="' . htmlspecialchars($config['containerClass']) . ' level-' . $level . '">';

       foreach ($tree as $item) {
           $classes = [$config['itemClass']];

           if (self::hasActiveChild($item, $currentPath)) {
               $classes[] = $config['activeClass'];
           }

           if (!empty($item['children'])) {
               $classes[] = $config['hasChildrenClass'];
           }

           $html .= '<li class="' . implode(' ', $classes) . '">';
           $html .= self::renderLink($item, $config, $currentPath);

           if (!empty($item['children'])) {
               $html .= self::renderTree($item['children'], $config, $currentPath, $level + 1);
           }

           $html .= '</li>';
       }

       $html .= '</ul>';
       return $html;
   }
   ```
   - Level tracking: `level-0`, `level-1`, `level-2`
   - Classes dinâmicas: active, has-children
   - Recursão: Renderiza sub-menus automaticamente

4. **renderLink() (linha 97-130)**
   ```php
   private static function renderLink($item, $config, $currentPath) {
       $url = self::getItemUrl($item);
       $itemPath = parse_url($url, PHP_URL_PATH);

       $classes = [$config['linkClass']];
       if ($itemPath === $currentPath) {
           $classes[] = $config['activeClass'];
       }

       $target = '';
       if (!empty($item['target']) && $item['target'] === '_blank') {
           $target = ' target="_blank" rel="noopener noreferrer"';
       }

       $icon = '';
       if (!empty($item['icon'])) {
           $icon = '<i class="' . htmlspecialchars($item['icon']) . '"></i> ';
       }

       return sprintf(
           '<a href="%s" class="%s"%s>%s%s</a>',
           htmlspecialchars($url),
           implode(' ', $classes),
           $target,
           $icon,
           htmlspecialchars($item['title'])
       );
   }
   ```
   - Exact match: `$itemPath === $currentPath`
   - Target blank: `rel="noopener noreferrer"` (segurança)
   - Icon support: Class CSS do ícone
   - XSS protection: htmlspecialchars em tudo

5. **getItemUrl() (linha 139-167)**
   ```php
   private static function getItemUrl($item) {
       // Link externo
       if (!empty($item['external_url'])) {
           return $item['external_url'];
       }

       // Módulo
       if ($item['type'] === 'module' && !empty($item['module_name'])) {
           return Core::url('module/' . $item['module_name']);
       }

       // Página
       if ($item['type'] === 'page' && !empty($item['page_slug'])) {
           return Core::url($item['page_slug']);
       }

       // Fallback: URL direto
       return $item['url'] ?? '#';
   }
   ```
   - Prioridade: external_url → module → page → url
   - Core::url(): URL helper (normaliza paths)
   - Fallback: `#` se nenhum URL

6. **hasActiveChild() recursivo (linha 198-215)**
   ```php
   private static function hasActiveChild($item, $currentPath) {
       $itemUrl = self::getItemUrl($item);
       $itemPath = parse_url($itemUrl, PHP_URL_PATH);

       // Item atual é active
       if ($itemPath === $currentPath) {
           return true;
       }

       // Verificar filhos recursivamente
       if (!empty($item['children'])) {
           foreach ($item['children'] as $child) {
               if (self::hasActiveChild($child, $currentPath)) {
                   return true;
               }
           }
       }

       return false;
   }
   ```
   - Recursão: Verifica toda árvore de filhos
   - Active propagation: Parent fica active se child for active
   - CSS: Permite breadcrumb visual

**Pontos Fortes:**
- Recursão limpa: buildTree + renderTree + hasActiveChild
- Active detection: Exact match + parent propagation
- Customizável: Todas classes CSS configuráveis
- Segurança: htmlspecialchars em tudo, rel="noopener noreferrer"
- Icon support: Class-based (Lucide, Font Awesome)
- Level tracking: CSS `level-0`, `level-1` para styling
- Multi-type: Suporta página, módulo, external_url

**Pontos Fracos:**
- Nenhum crítico
- Não usa aria-current="page" (acessibilidade) - mas é menor
- Sem suporte a dropdown toggle (apenas CSS-based)

**Classificação:** 100% CORE-AEGIS

---

### 26. Middleware.php (396 linhas) - 10/10

**Função:** Sistema de middleware com stack execution e middlewares pré-configurados

**Recursos:**

1. **Registro de middlewares (linha 27-50)**
   ```php
   public static function register() {
       self::registerAuthMiddleware();      // Auth (admin)
       self::registerMemberMiddleware();    // MemberAuth
       self::registerGuestMiddleware();     // Não logado
       self::registerCsrfMiddleware();      // CSRF token
       self::registerApiAuthMiddleware();   // JWT Bearer
       self::registerThrottleMiddleware();  // Rate limiting
       self::registerRoleMiddleware();      // Role check
       self::registerScopeMiddleware();     // JWT scopes
   }
   ```
   Todos middlewares CORE registrados no bootstrap

2. **Auth middleware (linha 63-82)**
   ```php
   private static function registerAuthMiddleware() {
       Router::middleware('auth', function($next) {
           if (!Auth::check()) {
               if (Request::isAjax()) {
                   http_response_code(401);
                   echo json_encode(['success' => false, 'error' => 'Não autenticado']);
                   return;
               }
               Core::redirect('/admin/login');
               return;
           }
           return $next();
       });
   }
   ```
   - Auth::check(): Verifica admin logado
   - AJAX: JSON response (401)
   - HTML: Redirect para login
   - $next(): Continua pipeline

3. **Member middleware (linha 88-107)**
   ```php
   private static function registerMemberMiddleware() {
       Router::middleware('member', function($next) {
           if (!MemberAuth::check()) {
               if (Request::isAjax()) {
                   http_response_code(401);
                   echo json_encode(['success' => false, 'error' => 'Não autenticado']);
                   return;
               }
               Core::redirect('/login');
               return;
           }
           return $next();
       });
   }
   ```
   - MemberAuth::check(): Verifica member logado
   - Idêntico ao auth, mas com MemberAuth
   - Redirect: `/login` (não `/admin/login`)

4. **Guest middleware (linha 113-132)**
   ```php
   private static function registerGuestMiddleware() {
       Router::middleware('guest', function($next) {
           if (Auth::check()) {
               Core::redirect('/admin/dashboard');
               return;
           }

           if (MemberAuth::check()) {
               Core::redirect('/');
               return;
           }

           return $next();
       });
   }
   ```
   - Bloqueia rotas de guest se logado
   - Admin: Redirect `/admin/dashboard`
   - Member: Redirect `/`
   - Uso: Login/register pages

5. **CSRF middleware (linha 138-157)**
   ```php
   private static function registerCsrfMiddleware() {
       Router::middleware('csrf', function($next) {
           if ($_SERVER['REQUEST_METHOD'] === 'POST') {
               $token = $_POST['csrf_token'] ?? $_POST['_token'] ?? '';

               if (!Security::validateCsrfToken($token)) {
                   if (Request::isAjax()) {
                       http_response_code(403);
                       echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
                       return;
                   }
                   die('Token CSRF inválido');
               }
           }
           return $next();
       });
   }
   ```
   - POST only: GET não precisa CSRF
   - Fallbacks: `csrf_token` ou `_token`
   - Security::validateCsrfToken(): Valida + regenera
   - AJAX: JSON (403), HTML: die()

6. **API Auth JWT (linha 178-209)**
   ```php
   private static function registerApiAuthMiddleware() {
       Router::middleware('api.auth', function($next) {
           $token = Request::bearerToken();

           if (!$token) {
               http_response_code(401);
               echo json_encode(['success' => false, 'error' => 'Token não fornecido']);
               return;
           }

           try {
               $payload = JWT::decode($token);

               // Verificar blacklist
               if (JWT::isBlacklisted($token)) {
                   http_response_code(401);
                   echo json_encode(['success' => false, 'error' => 'Token revogado']);
                   return;
               }

               // Disponibilizar para controller
               $_REQUEST['jwt_user'] = $payload;
               $_REQUEST['jwt_token'] = $token;

           } catch (Exception $e) {
               http_response_code(401);
               echo json_encode(['success' => false, 'error' => 'Token inválido: ' . $e->getMessage()]);
               return;
           }

           return $next();
       });
   }
   ```
   - Request::bearerToken(): Extrai `Authorization: Bearer {token}`
   - JWT::decode(): Valida signature + expiration
   - Blacklist check: JWT::isBlacklisted()
   - Injeta: `$_REQUEST['jwt_user']` e `$_REQUEST['jwt_token']`

7. **Throttle middleware factory (linha 222-263)**
   ```php
   private static function registerThrottleMiddleware() {
       Router::middleware('throttle', function($next, $maxRequests = 60, $perSeconds = 60) {
           $limiter = RateLimit::getInstance();
           $key = 'throttle:' . Request::ip() . ':' . $_SERVER['REQUEST_URI'];

           if (!$limiter->check($key, $maxRequests, $perSeconds)) {
               $retryAfter = $limiter->retryAfter($key, $perSeconds);

               http_response_code(429);
               header("X-RateLimit-Limit: {$maxRequests}");
               header("X-RateLimit-Remaining: 0");
               header("Retry-After: {$retryAfter}");

               echo json_encode([
                   'success' => false,
                   'error' => "Muitas requisições. Aguarde {$retryAfter} segundos."
               ]);
               return;
           }

           $remaining = $limiter->remaining($key, $maxRequests, $perSeconds);
           header("X-RateLimit-Limit: {$maxRequests}");
           header("X-RateLimit-Remaining: {$remaining}");

           return $next();
       });
   }
   ```
   - Parametrizável: `throttle:60,60` (60 requests em 60s)
   - Key: IP + URI (por endpoint)
   - Headers RFC: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`
   - Status 429: Too Many Requests

8. **Role middleware factory (linha 320-350)**
   ```php
   public static function role($roles) {
       if (!is_array($roles)) {
           $roles = [$roles];
       }

       return function($next) use ($roles) {
           if (!Auth::check()) {
               http_response_code(401);
               echo json_encode(['success' => false, 'error' => 'Não autenticado']);
               return;
           }

           $user = Auth::user();
           $userRole = $user['role'] ?? $user['tipo'] ?? 'user';

           if (!in_array($userRole, $roles)) {
               http_response_code(403);
               echo json_encode(['success' => false, 'error' => 'Role insuficiente']);
               return;
           }

           return $next();
       };
   }
   ```
   - Factory: `Middleware::role(['admin', 'editor'])`
   - Fallbacks: `role` ou `tipo`
   - Multi-role: OR logic
   - 401 se não logado, 403 se role errado

9. **Scope middleware factory (linha 358-394)**
   ```php
   public static function scope($requiredScopes) {
       if (!is_array($requiredScopes)) {
           $requiredScopes = [$requiredScopes];
       }

       return function($next) use ($requiredScopes) {
           $token = Request::bearerToken();

           if (!$token) {
               http_response_code(401);
               echo json_encode(['error' => 'Token não fornecido']);
               return;
           }

           $payload = JWT::decode($token);
           $userScopes = $payload['scopes'] ?? [];

           foreach ($requiredScopes as $scope) {
               if (!in_array($scope, $userScopes)) {
                   http_response_code(403);
                   echo json_encode(['error' => "Scope '{$scope}' necessário"]);
                   return;
               }
           }

           return $next();
       };
   }
   ```
   - Factory: `Middleware::scope(['read:users', 'write:users'])`
   - JWT payload: `scopes` array
   - AND logic: Precisa de TODOS os scopes
   - 401 se sem token, 403 se scope faltando

**Pontos Fortes:**
- Separation: Named middlewares vs factory middlewares
- Named: auth, member, guest, csrf, api.auth, throttle (Router::middleware)
- Factory: role(), scope() (retornam closures)
- Pipeline pattern: $next() continua execução
- RFC compliance: Rate limiting headers, status codes corretos
- Dual response: AJAX (JSON) vs HTML (redirect/die)
- JWT complete: Bearer token, blacklist, scopes
- Parametrizável: throttle:60,60, role(['admin'])

**Pontos Fracos:**
- Nenhum crítico
- Throttle por IP+URI (não por usuário) - mas é padrão

**Classificação:** 100% CORE-AEGIS

---

### 27. Migration.php (615 linhas) - 10/10

**Função:** Sistema de migrations Laravel-style com Blueprint pattern

**Recursos:**

1. **Abstract class (linha 12-22)**
   ```php
   abstract class Migration {
       protected $db;
       protected $executed = false;

       public function __construct() {
           $this->db = DB::getInstance();
       }

       abstract public function up();    // Criar/modificar
       abstract public function down();  // Reverter
   }
   ```
   - Abstract methods: up() e down() obrigatórios
   - DB instance: Injetado automaticamente
   - Flag executed: Previne execução dupla

2. **Schema API (linha 30-49)**
   ```php
   protected function create($table, Closure $callback) {
       $blueprint = new Blueprint($table);
       $callback($blueprint);

       $sql = $blueprint->toCreateSql();
       $this->db->execute($sql);

       foreach ($blueprint->getIndexes() as $index) {
           $this->db->execute($index);
       }
   }

   protected function table($table, Closure $callback) {
       $blueprint = new Blueprint($table, 'alter');
       $callback($blueprint);

       foreach ($blueprint->getAlterStatements() as $statement) {
           $this->db->execute($statement);
       }
   }
   ```
   - create(): Nova tabela + indexes
   - table(): Modificar tabela existente
   - Blueprint pattern: Fluent API

3. **Drop/Rename (linha 54-63)**
   ```php
   protected function drop($table) {
       $this->db->execute("DROP TABLE IF EXISTS `{$table}`");
   }

   protected function dropIfExists($table) {
       $this->db->execute("DROP TABLE IF EXISTS `{$table}`");
   }

   protected function rename($from, $to) {
       $this->db->execute("RENAME TABLE `{$from}` TO `{$to}`");
   }
   ```

4. **Blueprint: 21 column types (linha 193-298)**
   ```php
   // IDs
   public function id() { return $this->bigIncrements('id')->primary(); }
   public function uuid($name) { return $this->addColumn($name, 'CHAR(36)'); }

   // Strings
   public function string($name, $length = 255) { return $this->addColumn($name, "VARCHAR({$length})"); }
   public function text($name) { return $this->addColumn($name, 'TEXT'); }
   public function longText($name) { return $this->addColumn($name, 'LONGTEXT'); }

   // Numbers
   public function integer($name) { return $this->addColumn($name, 'INT'); }
   public function bigInteger($name) { return $this->addColumn($name, 'BIGINT'); }
   public function tinyInteger($name) { return $this->addColumn($name, 'TINYINT'); }
   public function decimal($name, $precision = 10, $scale = 2) {
       return $this->addColumn($name, "DECIMAL({$precision},{$scale})");
   }
   public function float($name) { return $this->addColumn($name, 'FLOAT'); }

   // Booleans
   public function boolean($name) { return $this->addColumn($name, 'TINYINT(1)')->default(0); }

   // Dates
   public function date($name) { return $this->addColumn($name, 'DATE'); }
   public function datetime($name) { return $this->addColumn($name, 'DATETIME'); }
   public function timestamp($name) { return $this->addColumn($name, 'TIMESTAMP'); }
   public function timestamps() {
       $this->timestamp('created_at')->nullable()->default('CURRENT_TIMESTAMP');
       $this->timestamp('updated_at')->nullable()->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
   }

   // Special
   public function json($name) { return $this->addColumn($name, 'JSON'); }
   public function enum($name, array $values) {
       $valuesList = implode("','", $values);
       return $this->addColumn($name, "ENUM('{$valuesList}')");
   }
   ```

5. **Column modifiers (linha 303-334)**
   ```php
   public function nullable() {
       $this->columns[count($this->columns) - 1]['nullable'] = true;
       return $this;
   }

   public function default($value) {
       if ($value === 'CURRENT_TIMESTAMP' || $value === 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') {
           $this->columns[count($this->columns) - 1]['default'] = $value;
       } else {
           $this->columns[count($this->columns) - 1]['default'] = "'{$value}'";
       }
       return $this;
   }

   public function unsigned() {
       $this->columns[count($this->columns) - 1]['unsigned'] = true;
       return $this;
   }

   public function unique() {
       $column = $this->columns[count($this->columns) - 1]['name'];
       $this->indexes[] = "UNIQUE KEY `{$column}` (`{$column}`)";
       return $this;
   }
   ```
   - Fluent API: `$table->string('email')->nullable()->unique()`
   - Modifica último column adicionado

6. **Indexes (linha 339-362)**
   ```php
   public function primary($column = 'id') {
       $this->primary = $column;
       return $this;
   }

   public function index($columns, $name = null) {
       if (!is_array($columns)) $columns = [$columns];
       $columnList = implode('`,`', $columns);
       $name = $name ?: implode('_', $columns) . '_index';
       $this->indexes[] = "INDEX `{$name}` (`{$columnList}`)";
       return $this;
   }

   public function unique($columns, $name = null) {
       if (!is_array($columns)) $columns = [$columns];
       $columnList = implode('`,`', $columns);
       $name = $name ?: implode('_', $columns) . '_unique';
       $this->indexes[] = "UNIQUE KEY `{$name}` (`{$columnList}`)";
       return $this;
   }
   ```
   - Multi-column support: `index(['user_id', 'post_id'])`
   - Auto-naming: `user_id_post_id_index`

7. **Foreign keys (linha 364-375)**
   ```php
   public function foreign($column) {
       $fk = new ForeignKeyDefinition($this->table, $column);
       $this->foreignKeys[] = $fk;
       return $fk;
   }

   // ForeignKeyDefinition class
   public function references($column) { $this->references = $column; return $this; }
   public function on($table) { $this->on = $table; return $this; }
   public function onDelete($action) { $this->onDelete = $action; return $this; }
   public function onUpdate($action) { $this->onUpdate = $action; return $this; }
   ```
   Uso:
   ```php
   $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
   ```

8. **toCreateSql() (linha 404-456)**
   ```php
   public function toCreateSql() {
       $sql = "CREATE TABLE `{$this->table}` (\n";

       // Columns
       foreach ($this->columns as $column) {
           $sql .= "  `{$column['name']}` {$column['type']}";

           if (!empty($column['unsigned'])) $sql .= ' UNSIGNED';
           if (empty($column['nullable'])) $sql .= ' NOT NULL';
           if (isset($column['default'])) $sql .= " DEFAULT {$column['default']}";
           if (!empty($column['autoIncrement'])) $sql .= ' AUTO_INCREMENT';

           $sql .= ",\n";
       }

       // Primary key
       if ($this->primary) {
           $sql .= "  PRIMARY KEY (`{$this->primary}`),\n";
       }

       // Indexes
       foreach ($this->indexes as $index) {
           $sql .= "  {$index},\n";
       }

       // Foreign keys (inline)
       foreach ($this->foreignKeys as $fk) {
           $sql .= "  " . $fk->toSql() . ",\n";
       }

       $sql = rtrim($sql, ",\n") . "\n";
       $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

       return $sql;
   }
   ```
   - Engine: InnoDB (transactions + foreign keys)
   - Charset: utf8mb4 (emoji support)
   - Collation: unicode_ci (case-insensitive)

9. **Alter table support (linha 463-518)**
   ```php
   public function addColumn($name, $type, $after = null) {
       $sql = "ALTER TABLE `{$this->table}` ADD `{$name}` {$type}";
       if ($after) $sql .= " AFTER `{$after}`";
       $this->alterStatements[] = $sql;
       return $this;
   }

   public function dropColumn($name) {
       $this->alterStatements[] = "ALTER TABLE `{$this->table}` DROP COLUMN `{$name}`";
       return $this;
   }

   public function renameColumn($from, $to, $type) {
       $this->alterStatements[] = "ALTER TABLE `{$this->table}` CHANGE `{$from}` `{$to}` {$type}";
       return $this;
   }

   public function modifyColumn($name, $type) {
       $this->alterStatements[] = "ALTER TABLE `{$this->table}` MODIFY `{$name}` {$type}";
       return $this;
   }
   ```
   - addColumn: Suporta AFTER (posicionamento)
   - renameColumn: Precisa redefinir type (MySQL limitation)

10. **Exemplo de uso completo (linha 552-614)**
    ```php
    class CreateUsersTable extends Migration {
        public function up() {
            $this->create('users', function($table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->enum('role', ['admin', 'editor', 'user'])->default('user');
                $table->boolean('active')->default(1);
                $table->timestamps();

                $table->index('email');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('SET NULL');
            });
        }

        public function down() {
            $this->drop('users');
        }
    }

    // Alter example
    class AddPhoneToUsers extends Migration {
        public function up() {
            $this->table('users', function($table) {
                $table->addColumn('phone', 'VARCHAR(20)', 'email');
            });
        }

        public function down() {
            $this->table('users', function($table) {
                $table->dropColumn('phone');
            });
        }
    }
    ```

**Pontos Fortes:**
- Laravel-compatible API: Familiar para devs
- Blueprint pattern: Fluent interface
- 21 column types: Completo
- Foreign keys: CASCADE, SET NULL, etc
- Timestamps helper: created_at + updated_at automático
- InnoDB + utf8mb4: Production-ready defaults
- Reversible: down() method obrigatório
- Alter support: ADD, DROP, RENAME, MODIFY columns

**Pontos Fracos:**
- Nenhum crítico
- Sem migration rollback em batch (mas tem down())
- Sem soft deletes helper (mas é fácil adicionar)

**Classificação:** 100% CORE-AEGIS

---

### 28. Migrator.php (445 linhas) - 10/10

**Função:** Gerenciador de migrations (executar, rollback, status, criar)

**Recursos:**

1. **Batch system (linha 186-212)**
   ```php
   public static function migrate() {
       $pending = self::getPending();
       $batch = self::getLastBatch() + 1;

       foreach ($pending as $name => $file) {
           $migration = self::resolve($file);
           $migration->up();

           self::db()->insert(self::$table, [
               'migration' => $name,
               'batch' => $batch
           ]);
       }
   }
   ```
   - Batch incremental: Agrupa migrations executadas juntas
   - Permite rollback por batch
   - Tracking completo: migration + batch + timestamp

2. **Rollback por steps (linha 221-267)**
   ```php
   public static function rollback($steps = 1) {
       $batch = self::getLastBatch();

       for ($i = 0; $i < $steps && $batch > 0; $i++, $batch--) {
           $migrations = self::db()->query(
               "SELECT migration FROM migrations WHERE batch = ? ORDER BY migration DESC",
               [$batch]
           );

           foreach ($migrations as $row) {
               $migration = self::resolve($files[$name]);
               $migration->down();

               self::db()->delete(self::$table, ['migration' => $name]);
           }
       }
   }
   ```
   - Steps: Quantos batches reverter
   - DESC order: Reverte na ordem inversa
   - Delete do registry: Remove tracking

3. **Reset e Refresh (linha 272-283)**
   ```php
   public static function reset() {
       $batch = self::getLastBatch();
       return self::rollback($batch); // Rollback ALL
   }

   public static function refresh() {
       self::reset();
       return self::migrate(); // Fresh start
   }
   ```
   - reset(): Rollback completo
   - refresh(): Reset + Migrate (fresh)

4. **Status report (linha 290-304)**
   ```php
   public static function status() {
       $files = self::getMigrationFiles();
       $ran = self::getRan();

       $status = [];
       foreach ($files as $name => $file) {
           $status[] = [
               'migration' => $name,
               'status' => in_array($name, $ran) ? 'Ran' : 'Pending'
           ];
       }
       return $status;
   }
   ```
   - Lista todas: Ran + Pending
   - Formato array de arrays

5. **Create command (linha 314-350)**
   ```php
   public static function create($name, $table = null, $create = true) {
       $timestamp = date('Y_m_d_His');
       $filename = "{$timestamp}_{$name}.php";
       $className = self::studly($name);

       // Auto-detect
       if (preg_match('/create_(\w+)_table/', $name, $matches)) {
           $table = $matches[1];
           $create = true;
       } elseif (preg_match('/add_\w+_to_(\w+)/', $name, $matches)) {
           $table = $matches[1];
           $create = false;
       }

       $template = $create ?
           self::getCreateTableTemplate($className, $table) :
           self::getAlterTableTemplate($className, $table);

       file_put_contents($filepath, $template);
   }
   ```
   - Timestamp filename: `2026_01_18_143000_create_users_table.php`
   - Auto-detect: Nome → tipo de template
   - StudlyCase: `create_users_table` → `CreateUsersTable`

6. **Templates inteligentes (linha 378-443)**
   - **Create table (linha 378-397):**
     ```php
     class {$className} extends Migration {
         public function up() {
             $this->create('{$table}', function($table) {
                 $table->uuid('id')->primary();
                 $table->timestamps();
             });
         }
         public function down() {
             $this->drop('{$table}');
         }
     }
     ```

   - **Alter table (linha 402-423):** Template com addColumn/dropColumn
   - **Blank (linha 428-443):** Template vazio

7. **Auto-resolve (linha 355-366)**
   ```php
   protected static function resolve($file) {
       require_once $file;

       $content = file_get_contents($file);
       if (preg_match('/class\s+(\w+)\s+extends\s+Migration/', $content, $matches)) {
           $className = $matches[1];
           return new $className();
       }

       throw new Exception("Classe de migration não encontrada");
   }
   ```
   - Extrai class name via regex
   - Instancia automaticamente

8. **Migrations table (linha 109-118)**
   ```sql
   CREATE TABLE IF NOT EXISTS migrations (
       id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       migration VARCHAR(255) NOT NULL,
       batch INT UNSIGNED NOT NULL,
       executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   )
   ```
   - Auto-created: ensureTable()
   - Batch tracking: Rollback granular

9. **Output callback (linha 66-79)**
   ```php
   public static function setOutput($callback) {
       self::$output = $callback;
   }

   protected static function output($message, $type = 'info') {
       if (self::$output) {
           call_user_func(self::$output, $message, $type);
       } else {
           echo "[{$type}] {$message}\n";
       }
   }
   ```
   - Customizável: Web UI vs CLI
   - Types: info, success, error, warning

**Pontos Fortes:**
- Laravel-compatible API: Familiar
- Batch system: Rollback granular
- Template generator: Auto-detect
- Status tracking: migrations table
- Output customizável: CLI vs Web
- Auto-resolve: Regex (reflection-free)
- Safe rollback: DESC order
- Fresh command: reset() + migrate()

**Pontos Fracos:**
- Nenhum crítico
- Template detection só inglês (create_, add_to)

**Classificação:** 100% CORE-AEGIS

---

### 29. ModuleInstaller.php (334 linhas) - 10/10

**Função:** Instalador de módulos com validação e auto-setup

**Recursos:**

1. **10-step installation (linha 16-74)**
   ```php
   public static function install($moduleName) {
       // 1. Validar módulo
       // 2. Ler metadados
       // 3. Validar requisitos
       // 4. Executar schema SQL
       // 5. Registrar módulo na tabela modules
       // 6. Criar menu item (se necessário)
       // 7. Adicionar à lista de instalados
       // 8. Atualizar config
       // 9. Invalidar cache
       // 10. Auto-bump version
   }
   ```
   - Pipeline claro: 10 etapas
   - Rollback on error: try-catch global

2. **Requirements validation (linha 106-131)**
   ```php
   private static function validateRequirements($metadata) {
       // Database requirement
       if (isset($metadata['requires']['database'])) {
           $requiredDbs = $metadata['requires']['database'];
           $currentDb = DB_TYPE;

           if (!in_array($currentDb, $requiredDbs)) {
               return ['valid' => false, 'message' => 'Requer banco: ...'];
           }
       }

       // Members requirement
       if ($metadata['requires']['members'] === true) {
           if (!Core::membersEnabled()) {
               return ['valid' => false, 'message' => 'Requer membros'];
           }
       }
   }
   ```
   - Database type: mysql, supabase, none
   - Members: Core::membersEnabled()
   - Bloqueia install incompatível

3. **Multi-DB schema (linha 136-175)**
   ```php
   private static function executeSchema($moduleName, $metadata) {
       $dbType = DB_TYPE;
       $schemaFile = ROOT_PATH . "modules/{$moduleName}/database/{$dbType}-schema.sql";

       $sql = file_get_contents($schemaFile);
       $sql = self::cleanSqlComments($sql); // Remove -- e /* */

       $queries = array_filter(array_map('trim', explode(';', $sql)));

       foreach ($queries as $query) {
           if (!empty($query)) {
               $db->execute($query);
           }
       }
   }
   ```
   - Schema por DB: `mysql-schema.sql`, `supabase-schema.sql`
   - Comment cleanup: linha 180-188
   - Multi-query: Split por `;`

4. **Module registry (linha 194-237)**
   ```php
   private static function registerModule($moduleName, $metadata) {
       $isPublic = !empty($metadata['public']) ? 1 : 0;
       $label = $metadata['label'] ?? $moduleName;
       $version = $metadata['version'] ?? '1.0.0';

       $existing = $db->select('modules', ['name' => $moduleName]);

       if (!empty($existing)) {
           $db->update('modules', [...], ['name' => $moduleName]);
       } else {
           $db->insert('modules', [...]);
       }
   }
   ```
   - Upsert logic: Update se existe
   - is_public: Controla acesso
   - Version tracking: metadata.version

5. **Auto menu creation (linha 242-281)**
   ```php
   private static function createMenuItemIfNeeded($moduleName, $metadata) {
       if (empty($metadata['label']) || empty($metadata['public_url'])) {
           return;
       }

       $existingMenu = $db->select('menu_items', [
           'module_name' => $moduleName,
           'type' => 'module'
       ]);

       if (!empty($existingMenu)) return;

       $maxOrdem = $db->query("SELECT MAX(ordem) FROM menu_items WHERE parent_id IS NULL");
       $ordem = ($maxOrdem[0]['max_ordem'] ?? 0) + 1;

       $db->insert('menu_items', [
           'id' => Security::generateUUID(),
           'label' => $metadata['label'],
           'type' => 'module',
           'module_name' => $moduleName,
           'permission_type' => $metadata['public'] ? 'public' : 'authenticated',
           'ordem' => $ordem
       ]);
   }
   ```
   - Condicional: label + public_url required
   - Skip duplicates
   - Auto-ordem: MAX + 1

6. **Config update dual-mode (linha 286-332)**
   ```php
   public static function updateInstalledModules($modules) {
       $modulesString = implode(',', $modules);

       // .env first
       if (file_exists(ROOT_PATH . '.env')) {
           $envContent = file_get_contents(ROOT_PATH . '.env');

           if (strpos($envContent, 'INSTALLED_MODULES=') !== false) {
               $envContent = preg_replace(
                   '/^INSTALLED_MODULES=.*$/m',
                   'INSTALLED_MODULES=' . $modulesString,
                   $envContent
               );
           } else {
               $envContent .= "\nINSTALLED_MODULES=" . $modulesString . "\n";
           }

           file_put_contents(ROOT_PATH . '.env', $envContent);
       }

       // Fallback: _config.php
       // Similar logic with define()
   }
   ```
   - Priority: `.env` > `_config.php`
   - Update: Regex replace
   - CSV format: `module1,module2`

7. **Cache invalidation (linha 59-61)**
   ```php
   ModuleManager::clearCache();
   PermissionManager::invalidateAll();
   ```
   - Dual invalidation: Manager + Permissions

8. **Metadata cache (linha 80-101)**
   ```php
   private static function readMetadata($moduleName) {
       $cacheKey = "module_metadata_{$moduleName}";
       $cached = SimpleCache::get($cacheKey);

       if ($cached !== null) return $cached;

       $metadata = json_decode(file_get_contents(...), true);
       SimpleCache::set($cacheKey, $metadata, 3600); // 1h

       return $metadata;
   }
   ```
   - SimpleCache: 1h TTL
   - Per-module key

**Pontos Fortes:**
- 10-step pipeline: Completo
- Multi-DB: Schema por tipo
- Requirements validation: Previne incompatibilidades
- Auto menu: Zero config
- Dual config: .env + _config.php
- Upsert: Update se existe
- Cache invalidation: Dupla

**Pontos Fracos:**
- Nenhum crítico

**Classificação:** 100% CORE-AEGIS

---

### 30. ModuleManager.php (236 linhas) - 10/10

**Função:** Gerenciador de módulos (listar, carregar rotas, cache)

**Recursos:**

1. **L1 + L2 caching (linha 25-55)**
   ```php
   public static function getInstalled() {
       // L1: Static (in-memory)
       if (self::$cachedModules !== null) {
           return self::$cachedModules;
       }

       // L2: File cache (1h)
       $cached = Cache::get('installed_modules');
       if ($cached !== null) {
           self::$cachedModules = $cached;
           return $cached;
       }

       // Cache miss
       $modules = explode(',', INSTALLED_MODULES);

       // Save to both
       Cache::set('installed_modules', $modules, 3600);
       self::$cachedModules = $modules;

       return $modules;
   }
   ```
   - L1: Static var (request-scoped)
   - L2: File cache (1h TTL)
   - Performance: ~0 consultas após warm

2. **Dual cache clear (linha 62-71)**
   ```php
   public static function clearCache() {
       self::$cachedModules = null;              // L1
       Cache::delete('installed_modules');        // L2

       // Cascade: metadata cache
       $available = self::getAvailable();
       foreach ($available as $moduleName => $metadata) {
           SimpleCache::delete("module_metadata_{$moduleName}");
       }
   }
   ```
   - L1 + L2 invalidation
   - Cascading: metadata também

3. **Available modules scan (linha 78-116)**
   ```php
   public static function getAvailable() {
       $modulesPath = ROOT_PATH . 'modules/';
       $dirs = scandir($modulesPath);

       $available = [];
       foreach ($dirs as $dir) {
           if ($dir === '.' || $dir === '..' || strpos($dir, '.') === 0) {
               continue;
           }

           if (!is_dir($modulesPath . $dir)) continue;

           if (!file_exists($modulePath . '/module.json')) continue;

           $metadata = self::readModuleMetadata($dir);
           $metadata['installed'] = self::isInstalled($dir);

           $available[$dir] = $metadata;
       }

       return $available;
   }
   ```
   - Filesystem scan: `modules/*/`
   - Validation: Requer module.json
   - Enrichment: Flag installed

4. **Auto-load routes (linha 158-179)**
   ```php
   public static function loadAllRoutes() {
       $installed = self::getInstalled();

       foreach ($installed as $moduleName) {
           self::loadModuleRoutes($moduleName);
       }
   }

   private static function loadModuleRoutes($moduleName) {
       $routeFile = ROOT_PATH . "modules/{$moduleName}/routes.php";

       if (file_exists($routeFile)) {
           require_once $routeFile;
       }
   }
   ```
   - Bootstrap: Chamado no inicio
   - Optional: File não obrigatório

5. **Menu items extract (linha 186-199)**
   ```php
   public static function getMenuItems() {
       $installed = self::getInstalled();
       $menuItems = [];

       foreach ($installed as $moduleName) {
           $metadata = self::readModuleMetadata($moduleName);

           if ($metadata && isset($metadata['menu']['admin'])) {
               $menuItems[] = $metadata['menu']['admin'];
           }
       }

       return $menuItems;
   }
   ```
   - Admin menu: metadata.menu.admin
   - Filter: Apenas com menu

6. **Facade methods (linha 207-234)**
   ```php
   public static function install($moduleName) {
       return ModuleInstaller::install($moduleName);
   }

   public static function uninstall($moduleName, $confirmed = false) {
       return ModuleUninstaller::uninstall($moduleName, $confirmed);
   }
   ```
   - Facade pattern: Proxy
   - Single entry point

**Pontos Fortes:**
- L1 + L2 caching: Performance agressiva
- Dual invalidation: L1 + L2 + metadata
- Auto-load routes: Zero config
- Facade pattern: API unificada
- Available scan: Auto-discovery

**Pontos Fracos:**
- Nenhum crítico

**Classificação:** 100% CORE-AEGIS

---

### 31. ModuleUninstaller.php (282 linhas) - 10/10

**Função:** Desinstalador de módulos com validação e cleanup

**Recursos:**
- **7-step uninstall:** Metadata → drop tables → remove menu → update config → cache → version
- **Transaction + FK disable (linha 81-86):** `SET FOREIGN_KEY_CHECKS = 0` para MySQL
- **Verify tables deleted (linha 144-158):** SELECT 1 FROM table (exception = deletado)
- **Views support (linha 89-97):** DROP VIEW IF EXISTS antes das tabelas
- **Supabase workflow (linha 185-228):** verifySupabaseDeletion() + finalizeUninstall() (2-step)
- **Cascade cleanup (linha 251-267):** module_migrations + virtual pages
- **Error collection (linha 100-107):** Array de erros, não falha na primeira

**Classificação:** 100% CORE-AEGIS

---

### 32. Notification.php (733 linhas) - 10/10

**Função:** Sistema multi-canal de notificações (database, mail, sms, slack, broadcast)

**Recursos:**
- **5 canais (linha 119-138):** database, mail, sms, slack, broadcast
- **Laravel-like API:** `Notification::send($user, new OrderShipped())`
- **Via() fluent (linha 69-72):** `Notification::via('mail')->send(...)`
- **Query builder (linha 301-422):** for($userId)->unread()->get(), markAsRead(), delete()
- **NotificationMail (linha 519-607):** Fluent builder (subject, line, action, salutation)
- **Slack support (linha 612-732):** Webhook, attachments, fields, color
- **Broadcast cache (linha 287-289):** Cache polling (300s TTL) para WebSocket
- **Error handling (linha 103-112):** Silencioso, log via Logger

**Classificação:** 100% CORE-AEGIS

---

### 33. PageBuilder.php (389 linhas) - 10/10

**Função:** Renderizador visual de páginas com blocos/cards e cache otimizado

**Recursos:**
- **WHERE IN optimization (linha 64-79):** Busca cards de todos blocos em 1 query
- **Hash map cards (linha 82-85):** O(1) lookup por block_id
- **Array buffer (linha 88-145):** implode() ao invés de concatenação
- **L1 cache (linha 31, 240-242):** Static var (request-scoped)
- **L2 cache (linha 246-255):** File cache (5min TTL)
- **Component integration (linha 112-123):** Component::render() + error handling
- **Stats API (linha 365-387):** getCacheStats() (memory + file size)
- **Clear cache (linha 312-338):** Específico ou global

**Classificação:** 100% CORE-AEGIS

---

### 34. Permission.php (359 linhas) - 10/10

**Função:** Permissões granulares (individual > grupo > bloqueado)

**Recursos:**
- **Hierarquia (linha 42-74):** Individual (prioridade) → grupo → bloqueado
- **MySQL vs Supabase (linha 61-68):** MySQL (presença=permitido) vs Supabase (allow column)
- **Subquery grupo (linha 84-97):** WHERE IN com subselect de member_groups
- **WHERE IN optimization (linha 131-132):** Busca pages em 1 query
- **Grant/Deny (linha 162-248):** grantIndividual, denyIndividual (Supabase only), removeIndividual
- **Cache invalidation (linha 200, 231, 262):** PermissionManager::invalidate($memberId)

**Classificação:** 100% CORE-AEGIS

---

### 35. PermissionManager.php (473 linhas) - 10/10

**Função:** Cache unificado de permissões (pre-fetch + O(1) lookup)

**Recursos:**
- **Pre-fetch 5 queries (linha 93-156):** groups, individual perms, group perms pages, group perms modules, public pages
- **L1 cache (linha 41):** Static array (request-scoped)
- **L2 cache (linha 398-441):** APCu (priority) > Session (fallback), 5min TTL
- **O(1) lookup (linha 181-215):** canAccessPage, canAccessModule (hash map)
- **Public detection (linha 224-266):** isPublicPage, isPublicPageBySlug, isPublicModule
- **DB > JSON fallback (linha 284-321):** Busca modules table, fallback module.json
- **Invalidation (linha 370-390):** invalidate($memberId), invalidateAll()

**Classificação:** 100% CORE-AEGIS

---

### 36. Preloader.php (262 linhas) - 10/10

**Função:** OPcache preload automation (PHP 7.4+)

**Recursos:**
- **Core classes (linha 23-38):** 13 classes essenciais
- **opcache_compile_file (linha 89):** Preload individual
- **PHP 7.4+ check (linha 101-105):** PHP_VERSION_ID >= 70400
- **generate() (linha 128-153):** Cria preload.php automático
- **Smart collect (linha 158-191):** Top 20 models + 10 controllers (por filemtime)
- **OPcache stats (linha 196-216):** Memory, hit rate, scripts cached
- **clearOpcache (linha 221-226):** opcache_reset()
- **CLI auto-exec (linha 259-261):** Executa se chamado direto

**Classificação:** 100% CORE-AEGIS

---

### 37. QueryBuilder.php (999 linhas) - 10/10

**Função:** Query builder Laravel-style com fluent interface

**Recursos:**
- **Fluent API:** `DB::table('users')->where('ativo', 1)->orderBy('name')->get()`
- **WHERE types (linha 186-370):** basic, in, notIn, null, notNull, between, like, nested, raw
- **Nested queries (linha 334-349):** Closure para agrupamento `where(function($q) {...})`
- **JOIN support (linha 375-415):** inner, left, right
- **Agregações (linha 632-689):** count, sum, avg, min, max
- **Helper methods:** latest(), oldest(), pluck(), exists(), doesntExist()
- **CRUD completo (linha 692-833):** insert, update, delete, truncate, updateOrInsert
- **Increment/Decrement (linha 776-804):** Atomic updates
- **Paginação (linha 547-549):** forPage($page, $perPage)
- **Debug (linha 973-997):** toSql(), getBindings(), dd()

**Classificação:** 100% CORE-AEGIS

---

### 38. QueryCache.php (461 linhas) - 10/10

**Função:** Cache automático de SELECT queries com invalidação por tabela

**Recursos:**
- **Key generation (linha 266-269):** MD5 hash de `SQL + serialize(bindings)`
- **Remember pattern (linha 145-192):** Cache::get() → executor() → Cache::set()
- **Table registry (linha 294-315):** Mapeia tabela → array de cache keys
- **extractTables() (linha 320-349):** Regex para FROM, JOIN, INSERT, UPDATE, DELETE
- **Invalidação granular (linha 211-235):** Por tabela (usa registry)
- **Ignore patterns (linha 64-83):** Auto-skip INSERT/UPDATE/DELETE + tables blacklist
- **Stats tracking (linha 54-59, 358-379):** hits, misses, writes, invalidations, hit_rate
- **Trait QueryCacheable (linha 395-460):** cache($ttl), noCache(), executeWithCache()
- **DebugBar integration (linha 166-189):** Log cache HIT/MISS
- **Bypass mode (linha 197-206):** Temporariamente desabilita cache

**Classificação:** 100% CORE-AEGIS

---

### 39. Queue.php (685 linhas) - 10/10

**Função:** Sistema de filas com delayed jobs e multi-driver (database, redis, sync)

**Recursos:**
- **3 drivers (linha 31-144):** database, redis, sync (executa imediato)
- **Delayed jobs (linha 110-112):** later($delay, $job, $data)
- **Redis delayed (linha 252-261):** Sorted set com score=timestamp
- **Migration automática (linha 288-299):** zRangeByScore → rPush
- **Worker options (linha 336-375):** sleep, max_jobs, timeout, memory limit
- **Retry exponencial (linha 499):** `pow(2, $attempts) * 10` (20s, 40s, 80s...)
- **Failed jobs table (linha 225-236):** Exception + trace storage
- **Retry failed (linha 573-607):** retry($id), retryAll()
- **Job base class (linha 630-684):** Abstract handle(), optional failed()
- **Dispatch static (linha 672-683):** MyJob::dispatch($data)
- **Stats (linha 619-624):** pushed, processed, failed, pending

**Classificação:** 100% CORE-AEGIS

---

### 40. RateLimit.php (156 linhas) - 9/10

**Função:** Rate limiting simples baseado em sessão (sliding window)

**Recursos:**
- **Sliding window (linha 90-102):** Array de timestamps, cleanup via array_filter
- **Storage (linha 130):** MD5 hash da key + session
- **Block system (linha 52-85):** Timestamp futuro `$_SESSION[{key}_blocked] = time() + $seconds`
- **isBlocked() (linha 60-71):** Auto-cleanup se expirado
- **check() (linha 19-36):** cleanup → count → addAttempt
- **reset() (linha 41-47):** Limpa contador (após login bem-sucedido)
- **Middleware helper (linha 136-154):** Auto-block 5min em 429

**Limitações:**
- Session-only: Perde dados se sessão expirar
- Não persistente: Não sobrevive restart
- Per-session: Não compartilha entre servers

**Uso ideal:** Login attempts, form submissions (single-server)

**Classificação:** 100% CORE-AEGIS

---

## 📊 RESUMO PARTE 2 (20 arquivos - COMPLETO ✅)

| # | Arquivo | Linhas | Qualidade | Função Principal |
|---|---------|--------|-----------|------------------|
| 21 | Logger | 610 | 10/10 | Sistema de logging PSR-3 compliant |
| 22 | MemberAuth | 337 | 10/10 | Autenticação de membros (não-admin) |
| 23 | MenuBuilder | 63 | 9/10 | Builder de menu hierárquico |
| 24 | MenuPermissionChecker | 275 | 10/10 | Filtragem de menu por permissões |
| 25 | MenuRenderer | 217 | 9.5/10 | Renderizador recursivo de menu |
| 26 | Middleware | 396 | 10/10 | Sistema de middleware com stack |
| 27 | Migration | 615 | 10/10 | Migrations Laravel-style com Blueprint |
| 28 | Migrator | 445 | 10/10 | Gerenciador de migrations (batch, rollback) |
| 29 | ModuleInstaller | 334 | 10/10 | Instalador de módulos com validação |
| 30 | ModuleManager | 236 | 10/10 | Gerenciador de módulos (cache L1+L2) |
| 31 | ModuleUninstaller | 282 | 10/10 | Desinstalador com transaction e verify |
| 32 | Notification | 733 | 10/10 | Notificações multi-canal (5 canais) |
| 33 | PageBuilder | 389 | 10/10 | Page builder otimizado com cache L1+L2 |
| 34 | Permission | 359 | 10/10 | Permissões granulares (individual > grupo) |
| 35 | PermissionManager | 473 | 10/10 | Cache unificado (pre-fetch + O(1)) |
| 36 | Preloader | 262 | 10/10 | OPcache preload (PHP 7.4+) |
| 37 | QueryBuilder | 999 | 10/10 | Query builder Laravel-style fluent |
| 38 | QueryCache | 461 | 10/10 | Cache automático de queries |
| 39 | Queue | 685 | 10/10 | Sistema de filas multi-driver |
| 40 | RateLimit | 156 | 9/10 | Rate limiting session-based |

**Total analisado:** 8.327 linhas
**Média de qualidade:** 9.88/10
**Classificação:** 100% CORE-AEGIS (20/20)
