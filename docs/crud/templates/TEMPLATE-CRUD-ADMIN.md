# TEMPLATE - CRUD ADMIN

Template para criar novo CRUD em `/admin/controllers/`. Use este template como base.

**Tipo**: Admin Controller (herda de BaseController)
**Métodos**: index, create, store, edit, update, destroy
**Autenticação**: `$this->requireAuth()` (BaseController)

---

## 1. Estrutura Base

```php
<?php
/**
 * NovoController
 * Descrever o que este controller gerencia
 */

class NovoController extends BaseController {

    /**
     * Listar todos os registros
     */
    public function index() {
        $this->requireAuth();
        $user = $this->getUser();

        $registros = $this->db()->select('tabela', [], 'created_at DESC');

        $this->render('novo/index', [
            'registros' => $registros,
            'user' => $user
        ]);
    }

    // ... outros métodos
}
```

---

## 2. Método: index() - Listar

```php
public function index() {
    $this->requireAuth();
    $user = $this->getUser();

    // [ ] Paginação (opcional)
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 50;
    $offset = ($page - 1) * $perPage;

    // [ ] Buscar registros
    $registros = $this->db()->select('tabela', [], 'created_at DESC LIMIT ? OFFSET ?', [$perPage, $offset]);

    // [ ] Renderizar view
    $this->render('novo/index', [
        'registros' => $registros,
        'user' => $user
    ]);
}
```

**Checklist**:
- [ ] `$this->requireAuth()` - validar autenticação
- [ ] `$this->getUser()` - obter usuário autenticado
- [ ] `$this->db()->select()` - usar prepared statements
- [ ] `$this->render()` - renderizar template

---

## 3. Método: create() - Formulário

```php
public function create() {
    $this->requireAuth();
    $user = $this->getUser();

    // [ ] Buscar dados necessários (ex: categorias, grupos)
    $grupos = $this->db()->select('grupos', [], 'name ASC');

    $this->render('novo/create', [
        'user' => $user,
        'grupos' => $grupos
    ]);
}
```

**Checklist**:
- [ ] `$this->requireAuth()` - validar autenticação
- [ ] Buscar dados relacionados se necessário
- [ ] Passar dados para view
- [ ] View com `Security::generateCSRF()` no form

---

## 4. Método: store() - Criar Registro

```php
public function store() {
    $this->requireAuth();

    try {
        // [1] CSRF VALIDATION
        $this->validateCSRF();

        // [2] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('novo_create', $ip, 5, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }

        // [3] SANITIZAR INPUTS
        $name = $this->input('name'); // já faz sanitize
        $email = strtolower($this->input('email'));
        $password = $_POST['password'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // [4] VALIDAÇÕES
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception('Preencha todos os campos obrigatórios');
        }

        if (strlen($name) < 3 || strlen($name) > 255) {
            throw new Exception('Nome deve ter entre 3 e 255 caracteres');
        }

        if (!Security::validateEmail($email)) {
            throw new Exception('Email inválido');
        }

        $existing = $this->db()->select('usuarios', ['email' => $email]);
        if (!empty($existing)) {
            throw new Exception('Email já em uso');
        }

        $strengthErrors = Security::validatePasswordStrength($password);
        if (!empty($strengthErrors)) {
            throw new Exception(implode('. ', $strengthErrors));
        }

        // [5] CREATE
        $usuarioId = Security::generateUUID();
        $this->db()->insert('usuarios', [
            'id' => $usuarioId,
            'name' => $name,
            'email' => $email,
            'password' => Security::hashPassword($password),
            'ativo' => $ativo,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // [6] AUDIT LOG
        Logger::getInstance()->audit('CREATE_USUARIO', $this->getUser()['id'], [
            'usuario_id' => $usuarioId,
            'email' => $email,
            'table' => 'usuarios'
        ]);

        // [7] INCREMENT RATE LIMIT
        RateLimiter::increment('novo_create', $ip, 60);

        // [8] FEEDBACK & REDIRECT
        $this->success('Registro criado com sucesso!');
        $this->redirect('/admin/novo');

    } catch (Exception $e) {
        Logger::getInstance()->warning('CREATE_USUARIO_FAILED', [
            'reason' => $e->getMessage(),
            'email' => $email ?? 'unknown',
            'user_id' => $this->getUser()['id']
        ]);

        $this->error($e->getMessage());
        $this->redirect('/admin/novo/create');
    }
}
```

**Checklist**:
- [ ] `$this->validateCSRF()` - primeira linha
- [ ] `RateLimiter::check()` + `increment()` - rate limit
- [ ] `$this->input()` para GET/POST (já sanitiza)
- [ ] Validar campos obrigatórios
- [ ] Validar email + unicidade
- [ ] Validar senha (força)
- [ ] `Security::generateUUID()` para novo ID
- [ ] `$this->db()->insert()` com dados sanitizados
- [ ] `Logger::getInstance()->audit()` - registrar ação
- [ ] `$this->success()` / `$this->error()` - feedback
- [ ] `$this->redirect()` - redirecionar

---

## 5. Método: edit() - Formulário de Edição

```php
public function edit($id) {
    $this->requireAuth();
    $user = $this->getUser();

    // [ ] Validar UUID
    if (!Security::isValidUUID($id)) {
        $this->error('ID inválido');
        $this->redirect('/admin/novo');
        return;
    }

    // [ ] Buscar registro
    $registros = $this->db()->select('usuarios', ['id' => $id]);
    
    if (empty($registros)) {
        $this->error('Registro não encontrado');
        $this->redirect('/admin/novo');
        return;
    }

    $registro = $registros[0];

    // [ ] Buscar dados relacionados se necessário
    $grupos = $this->db()->select('grupos', [], 'name ASC');

    $this->render('novo/edit', [
        'registro' => $registro,
        'user' => $user,
        'grupos' => $grupos
    ]);
}
```

**Checklist**:
- [ ] `$this->requireAuth()` - autenticação
- [ ] `Security::isValidUUID($id)` - validar ID
- [ ] `$this->db()->select()` - buscar registro
- [ ] Tratar se não encontrado
- [ ] Buscar dados relacionados
- [ ] Passar para view

---

## 6. Método: update() - Atualizar Registro

```php
public function update($id) {
    $this->requireAuth();

    try {
        // [1] CSRF VALIDATION
        $this->validateCSRF();

        // [2] UUID VALIDATION
        if (!Security::isValidUUID($id)) {
            throw new Exception('ID inválido');
        }

        // [3] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('novo_update', $ip, 10, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }

        // [4] SANITIZAR INPUTS
        $name = $this->input('name');
        $email = strtolower($this->input('email'));
        $password = $_POST['password'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // [5] VALIDAÇÕES
        if (empty($name) || empty($email)) {
            throw new Exception('Preencha campos obrigatórios');
        }

        if (!Security::validateEmail($email)) {
            throw new Exception('Email inválido');
        }

        // Verificar unicidade (excluir o próprio)
        $existing = $this->db()->query(
            "SELECT * FROM usuarios WHERE email = ? AND id != ?",
            [$email, $id]
        );
        if (!empty($existing)) {
            throw new Exception('Email já em uso');
        }

        // Validar senha apenas se preenchida
        if (!empty($password)) {
            $strengthErrors = Security::validatePasswordStrength($password);
            if (!empty($strengthErrors)) {
                throw new Exception(implode('. ', $strengthErrors));
            }
        }

        // [6] PREPARAR DADOS
        $data = [
            'name' => $name,
            'email' => $email,
            'ativo' => $ativo,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($password)) {
            $data['password'] = Security::hashPassword($password);
        }

        // [7] UPDATE
        $this->db()->update('usuarios', $data, ['id' => $id]);

        // [8] AUDIT LOG
        Logger::getInstance()->audit('UPDATE_USUARIO', $this->getUser()['id'], [
            'usuario_id' => $id,
            'fields_updated' => array_keys($data),
            'table' => 'usuarios'
        ]);

        // [9] INCREMENT RATE LIMIT
        RateLimiter::increment('novo_update', $ip, 60);

        // [10] FEEDBACK & REDIRECT
        $this->success('Registro atualizado com sucesso!');
        $this->redirect('/admin/novo');

    } catch (Exception $e) {
        Logger::getInstance()->warning('UPDATE_USUARIO_FAILED', [
            'reason' => $e->getMessage(),
            'usuario_id' => $id,
            'user_id' => $this->getUser()['id']
        ]);

        $this->error($e->getMessage());
        $this->redirect('/admin/novo/edit/' . $id);
    }
}
```

**Checklist**:
- [ ] `$this->validateCSRF()` - primeira linha
- [ ] `Security::isValidUUID($id)` - validar ID
- [ ] `RateLimiter::check()` + `increment()` - rate limit
- [ ] Validações: campos, email, senha (se preenchida)
- [ ] Email: unicidade (excluir próprio)
- [ ] `$this->db()->update()` com WHERE
- [ ] `Logger::getInstance()->audit()` - registrar
- [ ] `$this->success()` / `$this->error()`
- [ ] `$this->redirect()`

---

## 7. Método: destroy() - Deletar Registro

```php
public function destroy($id) {
    $this->requireAuth();

    try {
        // [1] CSRF VALIDATION
        $this->validateCSRF();

        // [2] UUID VALIDATION
        if (!Security::isValidUUID($id)) {
            throw new Exception('ID inválido');
        }

        // [3] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('novo_delete', $ip, 5, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }

        // [4] BUSCAR & VALIDAR EXISTÊNCIA
        $registros = $this->db()->select('usuarios', ['id' => $id]);
        
        if (empty($registros)) {
            throw new Exception('Registro não encontrado');
        }

        $registro = $registros[0];

        // [5] VALIDAÇÕES ADICIONAIS (ex: não deletar admin principal)
        if ($registro['email'] === 'admin@example.com') {
            throw new Exception('Não é possível deletar o admin principal');
        }

        // [6] DELETE
        $this->db()->delete('usuarios', ['id' => $id]);

        // [7] AUDIT LOG (com snapshot dos dados)
        Logger::getInstance()->audit('DELETE_USUARIO', $this->getUser()['id'], [
            'usuario_id' => $id,
            'email' => $registro['email'],
            'name' => $registro['name'],
            'table' => 'usuarios'
        ]);

        // [8] INCREMENT RATE LIMIT
        RateLimiter::increment('novo_delete', $ip, 60);

        // [9] FEEDBACK & REDIRECT
        $this->success('Registro removido com sucesso!');
        $this->redirect('/admin/novo');

    } catch (Exception $e) {
        Logger::getInstance()->warning('DELETE_USUARIO_FAILED', [
            'reason' => $e->getMessage(),
            'usuario_id' => $id,
            'user_id' => $this->getUser()['id']
        ]);

        $this->error($e->getMessage());
        $this->redirect('/admin/novo');
    }
}
```

**Checklist**:
- [ ] `$this->validateCSRF()` - primeira linha
- [ ] `Security::isValidUUID($id)` - validar ID
- [ ] `RateLimiter::check()` + `increment()` - rate limit
- [ ] Buscar e validar existência
- [ ] Validações: pode deletar? (ex: não admin principal)
- [ ] `$this->db()->delete()` - deletar
- [ ] `Logger::getInstance()->audit()` - incluir snapshot
- [ ] `$this->success()` / `$this->error()`
- [ ] `$this->redirect()`

---

## 8. Nomes de Ação para Logger

Ao usar em seu novo controller, trocar:
- `USUARIO` → `NOVO` (seu recurso)
- `CREATE_USUARIO` → `CREATE_NOVO`
- `UPDATE_USUARIO` → `UPDATE_NOVO`
- `DELETE_USUARIO` → `DELETE_NOVO`

Padrão: `[ACAO]_[RECURSO_SINGULAR]`

---

## 9. Nomes de Rate Limiter

Ao usar em seu novo controller, trocar:
- `novo_create` → seu recurso
- `novo_update` → seu recurso
- `novo_delete` → seu recurso

Padrão: `[recurso]_[acao]`

---

## 10. Checklist Final - Copy-Paste

```
INDEX:
[ ] Auth::require()
[ ] Select com ORDER BY
[ ] Paginação (se muitos registros)
[ ] Render template

CREATE:
[ ] Auth::require()
[ ] Buscar dados relacionados
[ ] Render template

STORE:
[ ] validateCSRF() - primeira linha
[ ] RateLimiter::check() + increment()
[ ] Sanitizar inputs
[ ] Validar campos obrigatórios
[ ] Validar email (formato + unicidade)
[ ] Validar senha (força)
[ ] generateUUID() para novo ID
[ ] db()->insert()
[ ] Logger::audit(CREATE_*)
[ ] success() + redirect()

EDIT:
[ ] Auth::require()
[ ] isValidUUID()
[ ] Select registro
[ ] Buscar dados relacionados
[ ] Render template

UPDATE:
[ ] validateCSRF() - primeira linha
[ ] isValidUUID()
[ ] RateLimiter::check() + increment()
[ ] Sanitizar + validar
[ ] Email: unicidade (excluir próprio)
[ ] Senha: opcional, validar se preenchida
[ ] db()->update()
[ ] Logger::audit(UPDATE_*)
[ ] success() + redirect()

DESTROY:
[ ] validateCSRF() - primeira linha
[ ] isValidUUID()
[ ] RateLimiter::check() + increment()
[ ] Select e validar existência
[ ] db()->delete()
[ ] Logger::audit(DELETE_*) + snapshot
[ ] success() + redirect()
```

---

## Dúvidas Frequentes

**P: Preciso de paginação?**
R: Sim, se tabela pode ter 100+ registros. Use LIMIT/OFFSET.

**P: Preciso de search/filtro?**
R: Opcional, mas use `Security::escapeLike()` em LIKE queries.

**P: Quando password é opcional?**
R: Sempre obrigatório em CREATE, opcional em UPDATE.

**P: Como validar campos relacionados (grupos, categorias)?**
R: Use `Security::isValidUUID()` em cada um, depois `db()->select()` para validar existência.

---

## Confiança

- **Baseado em TESTE 10-14**: ✓ 100%
- **Testado em MemberController**: ✓ Funcionando
- **AdminController já segue padrão**: ✓ Referência
