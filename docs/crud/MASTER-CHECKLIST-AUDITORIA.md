# MASTER CHECKLIST - AUDITORIA

Checklist master de auditoria e logging para todos os CRUDs. Define o padrão de rastreamento de operações.

**Confiança: 100% (testado em execução real)**

---

## 1. LOGGING DE CRIAÇÃO (store)

### Obrigatório para: `store()` - após INSERT bem-sucedido

- [ ] **Registrar criação com Logger::audit()**
  - Código:
    ```php
    Logger::getInstance()->audit('CREATE_RECURSO', Auth::userId(), [
        'resource_id' => $resourceId,
        'table' => 'resources',
        'email' => $email,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a'
    ]);
    ```
  - Localização: Logo após `$db->insert()` bem-sucedido
  - Campos mínimos: `resource_id`, `table`, `action_details`

- [ ] **Usar nomes de ação descritivos**
  - Padrão: `CREATE_RECURSO`, `CREATE_ADMIN`, `CREATE_MEMBER`
  - Padrão: `CRUD_[ACAO]_[RECURSO_NO_SINGULAR]`
  - Exemplos válidos:
    - `CREATE_ADMIN`
    - `CREATE_MEMBER`
    - `CREATE_PAGE`
    - `CREATE_BLOG_POST`

- [ ] **Incluir contexto relevante**
  - Email do usuário criado (para CREATE_ADMIN, CREATE_MEMBER)
  - Campos importantes modificados
  - Identificadores únicos (UUID do novo recurso)
  - IP de origem

---

## 2. LOGGING DE ATUALIZAÇÃO (update)

### Obrigatório para: `update()` - após UPDATE bem-sucedido

- [ ] **Registrar atualização com Logger::audit()**
  - Código:
    ```php
    $fieldsChanged = array_keys($data);
    Logger::getInstance()->audit('UPDATE_RECURSO', Auth::userId(), [
        'resource_id' => $id,
        'table' => 'resources',
        'fields_updated' => $fieldsChanged,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a'
    ]);
    ```
  - Localização: Logo após `$db->update()` bem-sucedido
  - Campos mínimos: `resource_id`, `table`, `fields_updated`

- [ ] **Registrar QUAIS campos foram alterados**
  - Array de nomes dos campos: `['name', 'email', 'status']`
  - Não incluir valores antigos/novos (questão de privacidade)
  - Apenas nomes dos campos alterados

- [ ] **Usar nomes de ação UPDATE_RECURSO**
  - Padrão: `UPDATE_ADMIN`, `UPDATE_MEMBER`, `UPDATE_PAGE`
  - Padrão: `CRUD_[ACAO]_[RECURSO_NO_SINGULAR]`

---

## 3. LOGGING DE DELEÇÃO (destroy)

### Obrigatório para: `destroy()` - após DELETE bem-sucedido

- [ ] **Registrar deleção com Logger::audit()**
  - Código:
    ```php
    Logger::getInstance()->audit('DELETE_RECURSO', Auth::userId(), [
        'resource_id' => $id,
        'table' => 'resources',
        'resource_data' => [
            'email' => $deleted['email'],
            'name' => $deleted['name']
        ],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a'
    ]);
    ```
  - Localização: Logo após `$db->delete()` bem-sucedido
  - Campos mínimos: `resource_id`, `table`, `resource_data` (snapshot antes de deletar)

- [ ] **Capturar dados ANTES de deletar**
  - Fazer SELECT do recurso antes de DELETE
  - Armazenar campos relevantes (email, nome, status)
  - Para poder restaurar/auditar depois
  - Exemplo:
    ```php
    // ANTES de deletar
    $member = $db->select('members', ['id' => $id])[0];
    
    // DELETE
    $db->delete('members', ['id' => $id]);
    
    // AUDIT
    Logger::getInstance()->audit('DELETE_MEMBER', Auth::userId(), [
        'resource_id' => $id,
        'table' => 'members',
        'resource_data' => $member
    ]);
    ```

- [ ] **Usar nomes de ação DELETE_RECURSO**
  - Padrão: `DELETE_ADMIN`, `DELETE_MEMBER`, `DELETE_PAGE`

---

## 4. INFORMAÇÕES DE CONTEXTO AUTOMÁTICAS

### Campos inclusos automaticamente pelo Logger

O método `Logger::audit()` adiciona automaticamente:

```php
Logger::getInstance()->audit('ACTION', $userId, [
    // seus dados customizados aqui
]);

// Resultado no log:
// [timestamp] [INFO] AUDIT: ACTION | {
//     "type": "audit",
//     "user_id": "user-uuid-123",    // ← automático (do parâmetro)
//     "ip": "203.0.113.42",           // ← automático (do $_SERVER)
//     "seu_campo": "seu_valor"        // ← seus dados customizados
// }
```

Campos automáticos:
- `type: "audit"` - marca como auditoria
- `user_id` - ID do usuário que fez a ação (do parâmetro)
- `ip` - IP de origem (de $_SERVER['REMOTE_ADDR'])
- Timestamp - hora da operação (automático no Logger)

---

## 5. LOGGING DE FALHAS

### Para: Operações que falharam (erros, exceções)

- [ ] **Registrar falhas significativas**
  - Validação falhou
  - Email já existe
  - Permissão negada
  - Código:
    ```php
    try {
        // operação
    } catch (Exception $e) {
        Logger::getInstance()->warning('UPDATE_MEMBER_FAILED', [
            'reason' => $e->getMessage(),
            'member_id' => $id,
            'user_id' => Auth::userId()
        ]);
        throw $e;
    }
    ```

- [ ] **NÃO registrar em audit falhas esperadas**
  - Apenas `info`, `warning`, `error` - não `audit()`
  - `audit()` é para operações bem-sucedidas
  - Falhas usam outros níveis

---

## 6. LOGGING DE OPERAÇÕES EM MASSA

### Para: Quando múltiplos recursos são alterados

- [ ] **Registrar operação global + resumo**
  - Código:
    ```php
    // Após atualizar múltiplos recursos
    Logger::getInstance()->audit('BULK_UPDATE_MEMBERS', Auth::userId(), [
        'resources_affected' => 25,
        'table' => 'members',
        'fields_updated' => ['status', 'ativo'],
        'filter_applied' => 'group_id = ?'
    ]);
    ```
  - Incluir: quantidade afetada, filtro aplicado, campos alterados

- [ ] **Alternativa: Logar cada um individualmente**
  - Se operação é crítica/importante
  - Logar cada um: mais verboso, melhor para auditoria
  - Decidir por tipo de operação

---

## 7. RETENÇÃO E LIMPEZA DE LOGS

### Configuração global (já feita em Logger.php)

- [ ] **Logs rotacionam diariamente**
  - Arquivo: `storage/logs/aegis-YYYY-MM-DD.log`
  - Manter últimos 30 dias por padrão
  - Editar em Logger::configure() se precisar mudar

- [ ] **Logs ficam acessíveis para auditoria**
  - Admin pode consultar logs em painel
  - Método: `Logger::read(100, 'audit')` - últimas 100 entradas AUDIT
  - Localização: `/storage/logs/`

---

## 8. EXEMPLO COMPLETO: MemberController::store()

```php
public function store() {
    Auth::require();
    
    if (!Core::membersEnabled()) {
        Core::redirect('/admin');
    }
    
    try {
        // 1. CSRF
        Security::validateCSRF($_POST['csrf_token'] ?? '');
        
        // 2. RATE LIMIT
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('member_create', $ip, 5, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }
        
        // 3. VALIDAÇÕES
        $email = Security::sanitize($_POST['email'] ?? '');
        $name = Security::sanitize($_POST['name'] ?? '');
        
        if (empty($email) || empty($name)) {
            throw new Exception('Campos obrigatórios');
        }
        
        if (!Security::validateEmail($email)) {
            throw new Exception('Email inválido');
        }
        
        // 4. CREATE
        $memberId = MemberAuth::createMember(
            $email,
            $_POST['password'] ?? '',
            $name,
            $_POST['groups'] ?? [],
            $_POST['ativo'] ?? 0
        );
        
        // 5. AUDIT - CRIAR MEMBRO
        Logger::getInstance()->audit('CREATE_MEMBER', Auth::userId(), [
            'member_id' => $memberId,
            'email' => $email,
            'table' => 'members'
        ]);
        
        // 6. INCREMENT RATE LIMIT
        RateLimiter::increment('member_create', $ip, 60);
        
        $_SESSION['success'] = "Membro criado com sucesso!";
        Core::redirect('/admin/members');
        
    } catch (Exception $e) {
        Logger::getInstance()->warning('CREATE_MEMBER_FAILED', [
            'reason' => $e->getMessage(),
            'email' => $email ?? 'unknown',
            'user_id' => Auth::userId()
        ]);
        
        $_SESSION['error'] = $e->getMessage();
        Core::redirect('/admin/members/create');
    }
}
```

---

## 9. EXEMPLO COMPLETO: MemberController::update()

```php
public function update($id) {
    Auth::require();
    
    if (!Core::membersEnabled()) {
        Core::redirect('/admin');
    }
    
    try {
        // 1. CSRF
        Security::validateCSRF($_POST['csrf_token'] ?? '');
        
        // 2. UUID VALIDATION
        if (!Security::isValidUUID($id)) {
            throw new Exception('ID inválido');
        }
        
        // 3. RATE LIMIT
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('member_update', $ip, 10, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }
        
        // 4. VALIDAÇÕES
        $data = [
            'name' => Security::sanitize($_POST['name'] ?? ''),
            'email' => Security::sanitize($_POST['email'] ?? ''),
        ];
        
        if (!Security::validateEmail($data['email'])) {
            throw new Exception('Email inválido');
        }
        
        // 5. UPDATE
        MemberAuth::updateMember($id, $data);
        
        // 6. AUDIT - ATUALIZAR MEMBRO
        Logger::getInstance()->audit('UPDATE_MEMBER', Auth::userId(), [
            'member_id' => $id,
            'fields_updated' => array_keys($data),
            'table' => 'members'
        ]);
        
        // 7. INCREMENT RATE LIMIT
        RateLimiter::increment('member_update', $ip, 60);
        
        $_SESSION['success'] = "Membro atualizado!";
        Core::redirect('/admin/members');
        
    } catch (Exception $e) {
        Logger::getInstance()->warning('UPDATE_MEMBER_FAILED', [
            'reason' => $e->getMessage(),
            'member_id' => $id,
            'user_id' => Auth::userId()
        ]);
        
        $_SESSION['error'] = $e->getMessage();
        Core::redirect('/admin/members/edit/' . $id);
    }
}
```

---

## 10. EXEMPLO COMPLETO: MemberController::destroy()

```php
public function destroy($id) {
    Auth::require();
    
    if (!Core::membersEnabled()) {
        Core::redirect('/admin');
    }
    
    try {
        // 1. CSRF
        Security::validateCSRF($_POST['csrf_token'] ?? '');
        
        // 2. UUID VALIDATION
        if (!Security::isValidUUID($id)) {
            throw new Exception('ID inválido');
        }
        
        // 3. RATE LIMIT
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('member_delete', $ip, 5, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }
        
        // 4. SNAPSHOT - capturar dados ANTES de deletar
        $db = DB::connect();
        $member = $db->select('members', ['id' => $id])[0] ?? null;
        
        if (!$member) {
            throw new Exception('Membro não encontrado');
        }
        
        // 5. DELETE
        MemberAuth::deleteMember($id);
        
        // 6. AUDIT - DELETAR MEMBRO
        Logger::getInstance()->audit('DELETE_MEMBER', Auth::userId(), [
            'member_id' => $id,
            'email' => $member['email'],
            'name' => $member['name'],
            'table' => 'members'
        ]);
        
        // 7. INCREMENT RATE LIMIT
        RateLimiter::increment('member_delete', $ip, 60);
        
        $_SESSION['success'] = "Membro removido!";
        Core::redirect('/admin/members');
        
    } catch (Exception $e) {
        Logger::getInstance()->warning('DELETE_MEMBER_FAILED', [
            'reason' => $e->getMessage(),
            'member_id' => $id,
            'user_id' => Auth::userId()
        ]);
        
        $_SESSION['error'] = $e->getMessage();
        Core::redirect('/admin/members');
    }
}
```

---

## Checklist Rápido para Auditoria

```
[ ] CREATE: Logger::audit('CREATE_*', userId, ['resource_id', 'table', 'contexto'])
[ ] UPDATE: Logger::audit('UPDATE_*', userId, ['resource_id', 'fields_updated', 'table'])
[ ] DELETE: Logger::audit('DELETE_*', userId, ['resource_id', 'snapshot_dados', 'table'])
[ ] Nomes de ações: CREATE_RECURSO, UPDATE_RECURSO, DELETE_RECURSO
[ ] Campos mínimos: resource_id, table, tipo_acao
[ ] Contexto: email, name, status (dados relevantes)
[ ] Logs: armazenados em storage/logs/aegis-YYYY-MM-DD.log
[ ] Limpeza: automática, 30 dias de retenção
```

---

## Confiança & Evidências

- **Logger::audit()**: Testado e funcionando ✓
- **Criação de arquivos**: storage/logs/ criado automaticamente ✓
- **Rotação de logs**: diária por padrão ✓
- **Campos automáticos**: user_id, ip inclusos ✓

**Status**: 100% confiança baseado em testes práticos de execução real.
