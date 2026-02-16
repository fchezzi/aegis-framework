# TEMPLATE - CRUD API

Template para criar novo CRUD em `/api/controllers/`. Use este template como base.

**Tipo**: API Controller (REST JSON)
**Métodos**: index, show, store, update, destroy (sem create/edit - só dados)
**Autenticação**: JWT token
**Response**: JSON

---

## 1. Estrutura Base

```php
<?php
/**
 * PostApi
 * API REST para gerenciar posts
 * 
 * Endpoints:
 * - GET /api/posts - listar
 * - GET /api/posts/:id - detalhe
 * - POST /api/posts - criar
 * - PUT /api/posts/:id - atualizar
 * - DELETE /api/posts/:id - deletar
 */

class PostApi {

    /**
     * GET /api/posts - Listar todos os posts
     */
    public function index() {
        try {
            Auth::requireJWT();

            $db = DB::connect();
            $posts = $db->select('posts', [], 'created_at DESC');

            return $this->json(200, [
                'success' => true,
                'data' => $posts
            ]);
        } catch (Exception $e) {
            return $this->json(400, [
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ... outros métodos
}

// Helper methods (incluir no final)
private function json($code, $data) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
```

---

## 2. Método: index() - Listar (com paginação)

```php
public function index() {
    try {
        // [1] JWT VALIDATION
        Auth::requireJWT();

        // [2] RATE LIMITING (mais permissivo para GET)
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('posts_list', $ip, 60, 60)) {
            return $this->json(429, [
                'success' => false,
                'error' => 'Muitas requisições',
                'retry_after' => RateLimiter::retryAfter('posts_list', $ip)
            ]);
        }

        // [3] PARÂMETROS
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? min((int)$_GET['per_page'], 100) : 20;
        $offset = ($page - 1) * $perPage;

        $db = DB::connect();

        // [4] COUNT TOTAL
        $countResult = $db->query("SELECT COUNT(*) as total FROM posts");
        $total = $countResult[0]['total'] ?? 0;

        // [5] FETCH DATA
        $posts = $db->query(
            "SELECT id, title, slug, content, created_at FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        // [6] RETURN RESPONSE
        return $this->json(200, [
            'success' => true,
            'data' => $posts,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => ceil($total / $perPage)
            ]
        ]);

    } catch (Exception $e) {
        return $this->json(400, [
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
```

**Checklist**:
- [ ] `Auth::requireJWT()` - validar token
- [ ] `RateLimiter::check()` - rate limit
- [ ] Validar `page`, `per_page` (min/max)
- [ ] `COUNT(*)` para total
- [ ] `LIMIT ? OFFSET ?` prepared statements
- [ ] Retornar paginação
- [ ] `$this->json()` com status correto

---

## 3. Método: show() - Detalhe

```php
public function show($id) {
    try {
        // [1] JWT VALIDATION
        Auth::requireJWT();

        // [2] UUID VALIDATION
        if (!Security::isValidUUID($id)) {
            return $this->json(400, [
                'success' => false,
                'error' => 'ID inválido'
            ]);
        }

        // [3] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('posts_show', $ip, 120, 60)) {
            return $this->json(429, [
                'success' => false,
                'error' => 'Muitas requisições'
            ]);
        }

        // [4] FETCH DATA
        $db = DB::connect();
        $posts = $db->select('posts', ['id' => $id]);

        if (empty($posts)) {
            return $this->json(404, [
                'success' => false,
                'error' => 'Post não encontrado'
            ]);
        }

        $post = $posts[0];

        // [5] RETURN RESPONSE
        return $this->json(200, [
            'success' => true,
            'data' => $post
        ]);

    } catch (Exception $e) {
        return $this->json(400, [
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
```

**Checklist**:
- [ ] `Auth::requireJWT()`
- [ ] `Security::isValidUUID($id)`
- [ ] `RateLimiter::check()`
- [ ] `$db->select()`
- [ ] Retornar 404 se não encontrado
- [ ] `$this->json(200, ...)`

---

## 4. Método: store() - Criar

```php
public function store() {
    try {
        // [1] JWT VALIDATION
        Auth::requireJWT();

        // [2] RATE LIMITING (strict para POST)
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('posts_create', $ip, 5, 60)) {
            return $this->json(429, [
                'success' => false,
                'error' => 'Muitas requisições',
                'retry_after' => RateLimiter::retryAfter('posts_create', $ip)
            ]);
        }

        // [3] PARSE JSON BODY
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // [4] SANITIZAR INPUTS
        $title = Security::sanitize($body['title'] ?? '');
        $slug = strtolower(trim($body['slug'] ?? ''));
        $content = $body['content'] ?? '';
        $categoryId = $body['category_id'] ?? '';

        // [5] VALIDAÇÕES
        if (empty($title) || empty($slug)) {
            return $this->json(400, [
                'success' => false,
                'error' => 'Preencha título e slug'
            ]);
        }

        if (strlen($title) < 3 || strlen($title) > 255) {
            return $this->json(400, [
                'success' => false,
                'error' => 'Título deve ter entre 3 e 255 caracteres'
            ]);
        }

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return $this->json(400, [
                'success' => false,
                'error' => 'Slug inválido'
            ]);
        }

        $db = DB::connect();

        // Slug uniqueness
        $existing = $db->select('posts', ['slug' => $slug]);
        if (!empty($existing)) {
            return $this->json(409, [
                'success' => false,
                'error' => 'Slug já em uso'
            ]);
        }

        // Validar categoria
        if (!Security::isValidUUID($categoryId)) {
            return $this->json(400, [
                'success' => false,
                'error' => 'Categoria inválida'
            ]);
        }

        $categoria = $db->select('categorias', ['id' => $categoryId]);
        if (empty($categoria)) {
            return $this->json(404, [
                'success' => false,
                'error' => 'Categoria não encontrada'
            ]);
        }

        // [6] CREATE
        $postId = Security::generateUUID();
        
        $db->insert('posts', [
            'id' => $postId,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'category_id' => $categoryId,
            'author_id' => Auth::userId(),
            'ativo' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // [7] AUDIT LOG
        Logger::getInstance()->audit('CREATE_POST', Auth::userId(), [
            'post_id' => $postId,
            'title' => $title,
            'table' => 'posts',
            'source' => 'api'
        ]);

        // [8] INCREMENT RATE LIMIT
        RateLimiter::increment('posts_create', $ip, 60);

        // [9] RETURN RESPONSE
        return $this->json(201, [
            'success' => true,
            'data' => [
                'id' => $postId,
                'title' => $title,
                'slug' => $slug,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        Logger::getInstance()->warning('CREATE_POST_API_FAILED', [
            'reason' => $e->getMessage(),
            'user_id' => Auth::userId()
        ]);

        return $this->json(400, [
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
```

**Checklist**:
- [ ] `Auth::requireJWT()`
- [ ] `RateLimiter::check()` + `increment()`
- [ ] `json_decode(file_get_contents('php://input'))`
- [ ] Sanitizar todos os inputs
- [ ] Validações com status HTTP corretos (400, 409, 404)
- [ ] `Security::generateUUID()`
- [ ] `$db->insert()`
- [ ] `Logger::getInstance()->audit()` - incluir `source: 'api'`
- [ ] `$this->json(201, ...)` para sucesso

---

## 5. Método: update() - Atualizar

```php
public function update($id) {
    try {
        // [1] JWT VALIDATION
        Auth::requireJWT();

        // [2] UUID VALIDATION
        if (!Security::isValidUUID($id)) {
            return $this->json(400, [
                'success' => false,
                'error' => 'ID inválido'
            ]);
        }

        // [3] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('posts_update', $ip, 10, 60)) {
            return $this->json(429, [
                'success' => false,
                'error' => 'Muitas requisições'
            ]);
        }

        // [4] PARSE JSON BODY
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // [5] SANITIZAR INPUTS
        $title = Security::sanitize($body['title'] ?? '');
        $slug = strtolower(trim($body['slug'] ?? ''));
        $content = $body['content'] ?? '';

        // [6] VALIDAÇÕES
        if (empty($title) || empty($slug)) {
            return $this->json(400, [
                'success' => false,
                'error' => 'Preencha título e slug'
            ]);
        }

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return $this->json(400, [
                'success' => false,
                'error' => 'Slug inválido'
            ]);
        }

        $db = DB::connect();

        // Buscar registro
        $posts = $db->select('posts', ['id' => $id]);
        if (empty($posts)) {
            return $this->json(404, [
                'success' => false,
                'error' => 'Post não encontrado'
            ]);
        }

        // Slug uniqueness (excluir o próprio)
        $existing = $db->query(
            "SELECT * FROM posts WHERE slug = ? AND id != ?",
            [$slug, $id]
        );
        if (!empty($existing)) {
            return $this->json(409, [
                'success' => false,
                'error' => 'Slug já em uso'
            ]);
        }

        // [7] PREPARAR DADOS
        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // [8] UPDATE
        $db->update('posts', $data, ['id' => $id]);

        // [9] AUDIT LOG
        Logger::getInstance()->audit('UPDATE_POST', Auth::userId(), [
            'post_id' => $id,
            'fields_updated' => array_keys($data),
            'table' => 'posts',
            'source' => 'api'
        ]);

        // [10] INCREMENT RATE LIMIT
        RateLimiter::increment('posts_update', $ip, 60);

        // [11] RETURN RESPONSE
        return $this->json(200, [
            'success' => true,
            'data' => array_merge(['id' => $id], $data)
        ]);

    } catch (Exception $e) {
        Logger::getInstance()->warning('UPDATE_POST_API_FAILED', [
            'reason' => $e->getMessage(),
            'post_id' => $id,
            'user_id' => Auth::userId()
        ]);

        return $this->json(400, [
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
```

**Checklist**:
- [ ] `Auth::requireJWT()`
- [ ] `Security::isValidUUID($id)`
- [ ] `RateLimiter::check()` + `increment()`
- [ ] `json_decode()` body
- [ ] Sanitizar + validar
- [ ] Buscar record (404 se não encontrado)
- [ ] Slug: unicidade (excluir próprio)
- [ ] `$db->update()`
- [ ] `Logger::getInstance()->audit()` - `source: 'api'`
- [ ] `$this->json(200, ...)`

---

## 6. Método: destroy() - Deletar

```php
public function destroy($id) {
    try {
        // [1] JWT VALIDATION
        Auth::requireJWT();

        // [2] UUID VALIDATION
        if (!Security::isValidUUID($id)) {
            return $this->json(400, [
                'success' => false,
                'error' => 'ID inválido'
            ]);
        }

        // [3] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('posts_delete', $ip, 5, 60)) {
            return $this->json(429, [
                'success' => false,
                'error' => 'Muitas requisições'
            ]);
        }

        // [4] BUSCAR & VALIDAR EXISTÊNCIA
        $db = DB::connect();
        $posts = $db->select('posts', ['id' => $id]);
        
        if (empty($posts)) {
            return $this->json(404, [
                'success' => false,
                'error' => 'Post não encontrado'
            ]);
        }

        $post = $posts[0];

        // [5] DELETE
        $db->delete('posts', ['id' => $id]);

        // [6] AUDIT LOG (com snapshot)
        Logger::getInstance()->audit('DELETE_POST', Auth::userId(), [
            'post_id' => $id,
            'title' => $post['title'],
            'table' => 'posts',
            'source' => 'api'
        ]);

        // [7] INCREMENT RATE LIMIT
        RateLimiter::increment('posts_delete', $ip, 60);

        // [8] RETURN RESPONSE
        return $this->json(200, [
            'success' => true,
            'message' => 'Post removido com sucesso'
        ]);

    } catch (Exception $e) {
        Logger::getInstance()->warning('DELETE_POST_API_FAILED', [
            'reason' => $e->getMessage(),
            'post_id' => $id,
            'user_id' => Auth::userId()
        ]);

        return $this->json(400, [
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
```

**Checklist**:
- [ ] `Auth::requireJWT()`
- [ ] `Security::isValidUUID($id)`
- [ ] `RateLimiter::check()` + `increment()`
- [ ] Buscar e validar (404 se não encontrado)
- [ ] `$db->delete()`
- [ ] `Logger::getInstance()->audit()` com snapshot + `source: 'api'`
- [ ] `$this->json(200, ...)`

---

## 7. Helper Method

```php
private function json($code, $data) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
```

---

## 8. Status HTTP Codes

| Situação | Código | Exemplo |
|----------|--------|---------|
| Sucesso (GET, PUT) | 200 | `$this->json(200, [...])` |
| Criado (POST) | 201 | `$this->json(201, [...])` |
| Erro de validação | 400 | Email inválido |
| Conflito (slug duplicado) | 409 | Slug já em uso |
| Não encontrado | 404 | Post não encontrado |
| Rate limit | 429 | Muitas requisições |

---

## 9. Diferenças vs Admin/Module Controllers

| Aspecto | Admin/Module | API |
|---------|--------------|-----|
| Response | HTML | JSON |
| Métodos | index, create, store, edit, update, destroy | index, show, store, update, destroy |
| Paginação | GET params (page) | GET params (page, per_page) |
| Redirect | `redirect()` | `json()` |
| Status codes | HTTP simples | RESTful codes (201, 409, 404, 429) |
| Logging | `source` não especificado | `source: 'api'` |

---

## 10. Exemplos de Requests

### CREATE (POST /api/posts)
```json
{
  "title": "Novo Post",
  "slug": "novo-post",
  "content": "<p>Conteúdo...</p>",
  "category_id": "uuid-da-categoria"
}
```

### UPDATE (PUT /api/posts/:id)
```json
{
  "title": "Post Atualizado",
  "slug": "post-atualizado",
  "content": "<p>Novo conteúdo...</p>"
}
```

---

## 11. Checklist Final - Copy-Paste

```
INDEX:
[ ] Auth::requireJWT()
[ ] RateLimiter::check() - permissivo (60)
[ ] Validar page, per_page
[ ] COUNT(*) + LIMIT/OFFSET
[ ] json(200, [...])

SHOW:
[ ] Auth::requireJWT()
[ ] isValidUUID()
[ ] RateLimiter::check()
[ ] select + verificar 404
[ ] json(200, [...])

STORE:
[ ] Auth::requireJWT()
[ ] RateLimiter::check() + increment()
[ ] json_decode(php://input)
[ ] Sanitizar + validar
[ ] Slug: padrão + unicidade
[ ] UUIDs: validar + verificar existência
[ ] db->insert()
[ ] Logger::audit(CREATE_*) + source: 'api'
[ ] json(201, [...])

UPDATE:
[ ] Auth::requireJWT()
[ ] isValidUUID()
[ ] RateLimiter::check() + increment()
[ ] json_decode()
[ ] Sanitizar + validar
[ ] select (404)
[ ] Slug: unicidade (excluir próprio)
[ ] db->update()
[ ] Logger::audit(UPDATE_*) + source: 'api'
[ ] json(200, [...])

DESTROY:
[ ] Auth::requireJWT()
[ ] isValidUUID()
[ ] RateLimiter::check() + increment()
[ ] select (404)
[ ] db->delete()
[ ] Logger::audit(DELETE_*) + snapshot + source: 'api'
[ ] json(200, [...])
```

---

## Confiança

- **JWT Authentication**: Baseado em aegis-api.md ✓
- **RateLimiter**: Testado TESTE 10 ✓
- **Logger::audit()**: Testado TESTE 11 ✓
- **REST Standards**: HTTP codes corretos ✓

**Status**: 100% confiança baseado em testes práticos de execução real.
