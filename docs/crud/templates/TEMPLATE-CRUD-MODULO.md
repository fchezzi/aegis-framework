# TEMPLATE - CRUD MODULO

Template para criar novo CRUD em `/admin/modules/[modulo]/controllers/`. Use este template como base.

**Tipo**: Module Controller (sem herança, usando Auth/DB estaticamente)
**Métodos**: index, create, store, edit, update, destroy
**Autenticação**: `Auth::require()` (estático)

---

## 1. Estrutura Base

```php
<?php
/**
 * PostController
 * Gerenciar posts do módulo blog
 */

class PostController {

    /**
     * Listar todos os posts
     */
    public function index() {
        Auth::require();

        $db = DB::connect();
        $posts = $db->select('posts', [], 'created_at DESC');

        require __DIR__ . '/../views/posts/index.php';
    }

    // ... outros métodos
}
```

---

## 2. Método: index() - Listar

```php
public function index() {
    Auth::require();

    // [ ] Paginação (opcional)
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 50;
    $offset = ($page - 1) * $perPage;

    // [ ] Buscar registros
    $db = DB::connect();
    $posts = $db->query(
        "SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?",
        [$perPage, $offset]
    );

    // [ ] Render view
    require __DIR__ . '/../views/posts/index.php';
}
```

**Checklist**:
- [ ] `Auth::require()` - validar autenticação
- [ ] `DB::connect()` - obter conexão
- [ ] `$db->select()` ou `$db->query()` - prepared statements
- [ ] `require` view - renderizar template

---

## 3. Método: create() - Formulário

```php
public function create() {
    Auth::require();

    // [ ] Buscar dados necessários
    $db = DB::connect();
    $categorias = $db->select('categorias', [], 'name ASC');

    require __DIR__ . '/../views/posts/create.php';
}
```

**Checklist**:
- [ ] `Auth::require()` - autenticação
- [ ] `DB::connect()` - obter conexão
- [ ] Buscar dados relacionados
- [ ] View com `Security::generateCSRF()`

---

## 4. Método: store() - Criar Registro

```php
public function store() {
    Auth::require();

    try {
        // [1] CSRF VALIDATION
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        // [2] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('post_create', $ip, 5, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }

        // [3] SANITIZAR INPUTS
        $title = Security::sanitize($_POST['title'] ?? '');
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $content = $_POST['content'] ?? ''; // HTML editor, sanitizar depois
        $categoryId = $_POST['category_id'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // [4] VALIDAÇÕES
        if (empty($title) || empty($slug)) {
            throw new Exception('Preencha título e slug');
        }

        if (strlen($title) < 3 || strlen($title) > 255) {
            throw new Exception('Título deve ter entre 3 e 255 caracteres');
        }

        // Slug validation
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new Exception('Slug inválido. Use apenas letras, números e hífen.');
        }

        // Slug uniqueness
        $db = DB::connect();
        $existing = $db->select('posts', ['slug' => $slug]);
        if (!empty($existing)) {
            throw new Exception('Slug já em uso');
        }

        // Validar categoria
        if (!Security::isValidUUID($categoryId)) {
            throw new Exception('Categoria inválida');
        }

        $categoria = $db->select('categorias', ['id' => $categoryId]);
        if (empty($categoria)) {
            throw new Exception('Categoria não encontrada');
        }

        // [5] CREATE
        $postId = Security::generateUUID();
        
        $db->insert('posts', [
            'id' => $postId,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'category_id' => $categoryId,
            'ativo' => $ativo,
            'author_id' => Auth::userId(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // [6] AUDIT LOG
        Logger::getInstance()->audit('CREATE_POST', Auth::userId(), [
            'post_id' => $postId,
            'title' => $title,
            'slug' => $slug,
            'table' => 'posts'
        ]);

        // [7] INCREMENT RATE LIMIT
        RateLimiter::increment('post_create', $ip, 60);

        // [8] FEEDBACK & REDIRECT
        $_SESSION['success'] = "Post criado com sucesso!";
        Core::redirect('/admin/blog/posts');

    } catch (Exception $e) {
        Logger::getInstance()->warning('CREATE_POST_FAILED', [
            'reason' => $e->getMessage(),
            'title' => $title ?? 'unknown',
            'user_id' => Auth::userId()
        ]);

        $_SESSION['error'] = $e->getMessage();
        Core::redirect('/admin/blog/posts/create');
    }
}
```

**Checklist**:
- [ ] `Security::validateCSRF()` - primeira linha
- [ ] `RateLimiter::check()` + `increment()`
- [ ] Sanitizar inputs com `Security::sanitize()`
- [ ] Validar campos obrigatórios
- [ ] Slug: validar padrão + unicidade
- [ ] UUIDs: validar + verificar existência
- [ ] `Security::generateUUID()` para novo ID
- [ ] `$db->insert()` com prepared statements
- [ ] `Logger::getInstance()->audit()`
- [ ] Session success/error
- [ ] `Core::redirect()`

---

## 5. Método: edit() - Formulário de Edição

```php
public function edit($id) {
    Auth::require();

    // [ ] Validar UUID
    if (!Security::isValidUUID($id)) {
        $_SESSION['error'] = 'ID inválido';
        Core::redirect('/admin/blog/posts');
        return;
    }

    // [ ] Buscar registro
    $db = DB::connect();
    $posts = $db->select('posts', ['id' => $id]);
    
    if (empty($posts)) {
        $_SESSION['error'] = 'Post não encontrado';
        Core::redirect('/admin/blog/posts');
        return;
    }

    $post = $posts[0];

    // [ ] Buscar dados relacionados
    $categorias = $db->select('categorias', [], 'name ASC');

    require __DIR__ . '/../views/posts/edit.php';
}
```

**Checklist**:
- [ ] `Auth::require()`
- [ ] `Security::isValidUUID()`
- [ ] `$db->select()` - buscar registro
- [ ] Verificar se existe
- [ ] Buscar dados relacionados
- [ ] `require` view

---

## 6. Método: update() - Atualizar Registro

```php
public function update($id) {
    Auth::require();

    try {
        // [1] CSRF VALIDATION
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        // [2] UUID VALIDATION
        if (!Security::isValidUUID($id)) {
            throw new Exception('ID inválido');
        }

        // [3] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('post_update', $ip, 10, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }

        // [4] SANITIZAR INPUTS
        $title = Security::sanitize($_POST['title'] ?? '');
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $content = $_POST['content'] ?? '';
        $categoryId = $_POST['category_id'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // [5] VALIDAÇÕES
        if (empty($title) || empty($slug)) {
            throw new Exception('Preencha título e slug');
        }

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new Exception('Slug inválido');
        }

        $db = DB::connect();

        // Slug uniqueness (excluir o próprio)
        $existing = $db->query(
            "SELECT * FROM posts WHERE slug = ? AND id != ?",
            [$slug, $id]
        );
        if (!empty($existing)) {
            throw new Exception('Slug já em uso');
        }

        // Validar categoria
        if (!Security::isValidUUID($categoryId)) {
            throw new Exception('Categoria inválida');
        }

        $categoria = $db->select('categorias', ['id' => $categoryId]);
        if (empty($categoria)) {
            throw new Exception('Categoria não encontrada');
        }

        // [6] PREPARAR DADOS
        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'category_id' => $categoryId,
            'ativo' => $ativo,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // [7] UPDATE
        $db->update('posts', $data, ['id' => $id]);

        // [8] AUDIT LOG
        Logger::getInstance()->audit('UPDATE_POST', Auth::userId(), [
            'post_id' => $id,
            'fields_updated' => array_keys($data),
            'table' => 'posts'
        ]);

        // [9] INCREMENT RATE LIMIT
        RateLimiter::increment('post_update', $ip, 60);

        // [10] FEEDBACK & REDIRECT
        $_SESSION['success'] = "Post atualizado com sucesso!";
        Core::redirect('/admin/blog/posts');

    } catch (Exception $e) {
        Logger::getInstance()->warning('UPDATE_POST_FAILED', [
            'reason' => $e->getMessage(),
            'post_id' => $id,
            'user_id' => Auth::userId()
        ]);

        $_SESSION['error'] = $e->getMessage();
        Core::redirect('/admin/blog/posts/edit/' . $id);
    }
}
```

**Checklist**:
- [ ] `Security::validateCSRF()`
- [ ] `Security::isValidUUID($id)`
- [ ] `RateLimiter::check()` + `increment()`
- [ ] Sanitizar + validar
- [ ] Slug: padrão + unicidade (excluir próprio)
- [ ] Validar relacionamentos
- [ ] `$db->update()`
- [ ] `Logger::getInstance()->audit()`
- [ ] Session + redirect

---

## 7. Método: destroy() - Deletar Registro

```php
public function destroy($id) {
    Auth::require();

    try {
        // [1] CSRF VALIDATION
        Security::validateCSRF($_POST['csrf_token'] ?? '');

        // [2] UUID VALIDATION
        if (!Security::isValidUUID($id)) {
            throw new Exception('ID inválido');
        }

        // [3] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('post_delete', $ip, 5, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }

        // [4] BUSCAR & VALIDAR EXISTÊNCIA
        $db = DB::connect();
        $posts = $db->select('posts', ['id' => $id]);
        
        if (empty($posts)) {
            throw new Exception('Post não encontrado');
        }

        $post = $posts[0];

        // [5] DELETE
        $db->delete('posts', ['id' => $id]);

        // [6] AUDIT LOG (com snapshot)
        Logger::getInstance()->audit('DELETE_POST', Auth::userId(), [
            'post_id' => $id,
            'title' => $post['title'],
            'slug' => $post['slug'],
            'table' => 'posts'
        ]);

        // [7] INCREMENT RATE LIMIT
        RateLimiter::increment('post_delete', $ip, 60);

        // [8] FEEDBACK & REDIRECT
        $_SESSION['success'] = "Post removido com sucesso!";
        Core::redirect('/admin/blog/posts');

    } catch (Exception $e) {
        Logger::getInstance()->warning('DELETE_POST_FAILED', [
            'reason' => $e->getMessage(),
            'post_id' => $id,
            'user_id' => Auth::userId()
        ]);

        $_SESSION['error'] = $e->getMessage();
        Core::redirect('/admin/blog/posts');
    }
}
```

**Checklist**:
- [ ] `Security::validateCSRF()`
- [ ] `Security::isValidUUID($id)`
- [ ] `RateLimiter::check()` + `increment()`
- [ ] Buscar e validar existência
- [ ] `$db->delete()`
- [ ] `Logger::getInstance()->audit()` com snapshot
- [ ] Session + redirect

---

## 8. Diferenças vs Admin Controller

| Aspecto | Admin | Module |
|---------|-------|--------|
| Herança | `extends BaseController` | Nenhuma herança |
| Auth | `$this->requireAuth()` | `Auth::require()` |
| DB | `$this->db()` | `DB::connect()` |
| Input | `$this->input()` | `Security::sanitize($_POST)` |
| Render | `$this->render()` | `require` view |
| Redirect | `$this->redirect()` | `Core::redirect()` |
| Usuário | `$this->getUser()` | `Auth::userId()` |
| Feedback | `$this->success()` | `$_SESSION['success']` |

---

## 9. Estrutura de Diretórios

```
admin/modules/blog/
├── controllers/
│   └── PostController.php
├── views/
│   └── posts/
│       ├── index.php
│       ├── create.php
│       └── edit.php
└── models/
    └── Post.php (opcional)
```

---

## 10. Nomes de Ação para Logger

Ao usar em seu novo módulo, trocar:
- `POST` → `SEU_RECURSO`
- `CREATE_POST` → `CREATE_RECURSO`
- `UPDATE_POST` → `UPDATE_RECURSO`
- `DELETE_POST` → `DELETE_RECURSO`

---

## 11. Checklist Final - Copy-Paste

```
INDEX:
[ ] Auth::require()
[ ] DB::connect()
[ ] Select com ORDER BY
[ ] Paginação (se necessário)
[ ] require view

CREATE:
[ ] Auth::require()
[ ] DB::connect()
[ ] Buscar dados relacionados
[ ] require view

STORE:
[ ] validateCSRF() - primeira linha
[ ] RateLimiter::check() + increment()
[ ] Sanitizar inputs
[ ] Validar campos obrigatórios
[ ] Slug: padrão + unicidade
[ ] UUIDs: validar + verificar existência
[ ] generateUUID()
[ ] db->insert()
[ ] Logger::audit(CREATE_*)
[ ] $_SESSION + redirect()

EDIT:
[ ] Auth::require()
[ ] isValidUUID()
[ ] Select registro
[ ] Buscar dados relacionados
[ ] require view

UPDATE:
[ ] validateCSRF()
[ ] isValidUUID()
[ ] RateLimiter::check() + increment()
[ ] Sanitizar + validar
[ ] Slug: unicidade (excluir próprio)
[ ] db->update()
[ ] Logger::audit(UPDATE_*)
[ ] $_SESSION + redirect()

DESTROY:
[ ] validateCSRF()
[ ] isValidUUID()
[ ] RateLimiter::check() + increment()
[ ] Select e validar existência
[ ] db->delete()
[ ] Logger::audit(DELETE_*) + snapshot
[ ] $_SESSION + redirect()
```

---

## Confiança

- **Baseado em MemberController**: ✓ Funcionando em produção
- **Testado TESTE 10-14**: ✓ 100%
- **Padrão validado**: ✓ Seguro
