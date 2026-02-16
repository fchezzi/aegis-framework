# 📘 GUIA TÉCNICO - CRIAR CRUD AEGIS

**Pré-requisito:** Você já leu `1-START.md` e definiu as 4 flags.

**Este documento:** Passos técnicos detalhados para implementação.

---

## 📚 ÍNDICE DE PASSOS

- **PASSO -1:** Criar Tabela no Banco
- **PASSO 0:** Validação UUID Obrigatória
- **PASSO 1:** Escolher Template
- **PASSO 2:** index() com Paginação Obrigatória
- **PASSO 3:** create() - Formulário de Criação
- **PASSO 4:** store() - Criar Registro
  - **PASSO 4B:** Upload de Arquivos (se $needs_upload = True)
  - **PASSO 4C:** Path Traversal Protection (se tem upload)
- **PASSO 5:** edit() - Formulário de Edição
- **PASSO 6:** update() - Atualizar Registro
- **PASSO 7:** destroy() - Deletar Registro
- **PASSO 8:** Adicionar Rotas
- **PASSO 9:** Adicionar Link no Menu
- **PASSO 10:** Testar CRUD Admin
- **PASSO 11:** Checkpoint Frontend
  - **PASSO 11B:** Criar Display Frontend (se $needs_frontend_display = True)
- **PASSO 12:** Validação Automática
- **PASSO 13:** Entregar

---

## ⚠️ REGRAS ABSOLUTAS

**ANTES de começar qualquer passo:**

```
[ ] Li 1-START.md e defini as 4 flags?
[ ] Tenho as flags anotadas?
[ ] Sei que "opcional" = verificar flag?
```

**Durante implementação:**

```
❌ PROIBIDO:
- Usar SELECT *
- Pular paginação no index()
- Não validar UUID em edit/update/destroy
- Deletar arquivo sem path traversal protection
- Não otimizar imagens uploadadas

✅ OBRIGATÓRIO:
- SELECT com campos específicos
- Paginação em index() (LIMIT/OFFSET)
- UUID validation
- Path protection antes de unlink()
- Otimizar imagens automaticamente
```

---

## PASSO -1: CRIAR TABELA NO BANCO

### 📁 Criar migration

**Arquivo:** `/migrations/XXX_create_[recurso]_table.sql`

**Template base:**

```sql
-- Migration: Criar tabela [RECURSO]
-- Data: YYYY-MM-DD

CREATE TABLE IF NOT EXISTS `tbl_[recurso]` (
    `id` CHAR(36) PRIMARY KEY COMMENT 'UUID v4',

    -- CAMPOS PERSONALIZADOS (baseado no PASSO -2)
    `nome` VARCHAR(255) NOT NULL,
    `descricao` TEXT,

    -- CAMPO UPLOAD (se $needs_upload = True)
    `imagem` VARCHAR(255),

    -- CAMPO ORDENAÇÃO (se $needs_ordering = True)
    `order` INT DEFAULT 0,

    -- CAMPO STATUS (se $needs_status = True)
    `ativo` TINYINT(1) DEFAULT 1,

    -- CAMPOS PADRÃO (sempre incluir)
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- ÍNDICES
    KEY `idx_created` (`created_at`)

    -- ÍNDICE STATUS (se $needs_status = True)
    , KEY `idx_ativo` (`ativo`)

    -- ÍNDICE ORDENAÇÃO (se $needs_ordering = True)
    , KEY `idx_order` (`order`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### ⚠️ ATENÇÃO: Reserved Keywords

Se usar campos com nomes reservados do MySQL, **SEMPRE usar backticks:**

**Palavras reservadas comuns:**
- `order` → ``order``
- `group` → ``group``
- `key` → ``key``
- `read` → ``read``
- `index` → ``index``

**Exemplo correto:**
```sql
CREATE TABLE tbl_banner (
    `order` INT DEFAULT 0,  -- ✅ Com backticks
    -- ...
);

SELECT id, titulo, `order` FROM tbl_banner ORDER BY `order` ASC;
```

### Executar migration

```bash
mysql -u root -proot aegis < migrations/XXX_create_[recurso]_table.sql
```

**Checklist:**
```
[ ] Arquivo migration criado em /migrations/
[ ] Todos os campos do PASSO -2 incluídos
[ ] Campos condicionais baseados em flags
[ ] Reserved keywords com backticks
[ ] Índices apropriados
[ ] Migration executada com sucesso
```

---

## PASSO 0: VALIDAÇÃO UUID OBRIGATÓRIA

**Todo método que recebe `$id` DEVE validar formato UUID v4.**

### Implementação obrigatória

**Adicionar em:** `edit($id)`, `update($id)`, `destroy($id)`

```php
public function edit($id) {
    $this->requireAuth();

    // ⛔ UUID VALIDATION - PRIMEIRA COISA
    if (!Security::isValidUUID($id)) {
        http_response_code(400);
        die('ID inválido');
    }

    // Agora é seguro usar $id
    $registro = $this->db()->query(
        "SELECT * FROM tbl_recurso WHERE id = ?",
        [$id]
    );

    // ...
}
```

### Função Security::isValidUUID()

**Arquivo:** `/core/Security.php`

```php
public static function isValidUUID($uuid) {
    if (empty($uuid) || !is_string($uuid)) {
        return false;
    }

    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    return preg_match($pattern, $uuid) === 1;
}
```

**Checklist:**
```
[ ] Security::isValidUUID() existe em /core/Security.php
[ ] edit($id) valida UUID antes de query
[ ] update($id) valida UUID antes de query
[ ] destroy($id) valida UUID antes de query
[ ] Retorna HTTP 400 se inválido
```

---

## PASSO 1: ESCOLHER TEMPLATE

**Usar templates prontos em:** `/docs/crud/templates/`

- `TEMPLATE-CRUD-ADMIN.md` → CRUDs admin (maioria dos casos)
- `TEMPLATE-CRUD-MODULO.md` → CRUDs de módulos específicos
- `TEMPLATE-CRUD-API.md` → APIs REST

**Ação:** Copiar template escolhido como base.

---

## PASSO 2: index() COM PAGINAÇÃO OBRIGATÓRIA

### ⚠️ PAGINAÇÃO É OBRIGATÓRIA (NÃO OPCIONAL)

**Implementação completa:**

```php
public function index() {
    $this->requireAuth();
    $user = $this->getUser();

    // [1] PAGINAÇÃO - OBRIGATÓRIA
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = 50; // Ajustar: 20-100

    if ($page < 1) $page = 1;

    $offset = ($page - 1) * $perPage;

    // [2] CONTAR TOTAL
    $totalResult = $this->db()->query(
        "SELECT COUNT(*) as total FROM tbl_recurso"
    );
    $total = $totalResult[0]['total'] ?? 0;
    $totalPages = ceil($total / $perPage);

    // [3] BUSCAR PÁGINA ATUAL
    // ⚠️ SELECT ESPECÍFICO (NÃO SELECT *)
    $registros = $this->db()->query(
        "SELECT id, nome, ativo, `order`, created_at
         FROM tbl_recurso
         ORDER BY `order` ASC
         LIMIT ? OFFSET ?",
        [$perPage, $offset]
    );

    // [4] RENDERIZAR
    $this->render('recurso/index', [
        'user' => $user,
        'registros' => $registros,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'total' => $total,
        'perPage' => $perPage
    ]);
}
```

### View com paginação

```php
<!-- Tabela -->
<table>
    <?php foreach ($registros as $reg): ?>
        <tr>
            <td><?= htmlspecialchars($reg['nome']) ?></td>
            <!-- ... -->
        </tr>
    <?php endforeach; ?>
</table>

<!-- Paginação -->
<?php if ($totalPages > 1): ?>
    <nav class="pagination">
        <?php if ($currentPage > 1): ?>
            <a href="?page=<?= $currentPage - 1 ?>">« Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $currentPage): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?= $currentPage + 1 ?>">Próxima »</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
```

**Checklist:**
```
[ ] Paginação implementada (page, perPage, offset)
[ ] COUNT(*) para total de registros
[ ] SELECT específico (NÃO SELECT *)
[ ] LIMIT/OFFSET na query
[ ] View recebe dados de paginação
[ ] Links de navegação funcionando
```

---

## PASSO 3: create() - Formulário de Criação

```php
public function create() {
    $this->requireAuth();
    $user = $this->getUser();

    // Buscar dados relacionados se necessário
    // Ex: $categorias = $this->db()->query("SELECT id, nome FROM tbl_categorias");

    $this->render('recurso/create', [
        'user' => $user
        // 'categorias' => $categorias
    ]);
}
```

**View:** Formulário HTML com `Security::generateCSRF()`

---

## PASSO 4: store() - Criar Registro

### Ordem rigorosa obrigatória:

```php
public function store() {
    $this->requireAuth();

    try {
        // [1] CSRF VALIDATION - PRIMEIRA LINHA
        $this->validateCSRF();

        // [2] RATE LIMITING
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('recurso_create', $ip, 5, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }

        // [3] SANITIZAR INPUTS
        $nome = Security::sanitize($_POST['nome'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $order = (int) ($_POST['order'] ?? 0);

        // [4] VALIDAÇÕES
        if (empty($nome)) {
            throw new Exception('Nome é obrigatório');
        }

        if (strlen($nome) > 255) {
            throw new Exception('Nome muito longo (máx 255)');
        }

        // [5] CREATE
        $id = Security::generateUUID();

        $this->db()->query(
            "INSERT INTO tbl_recurso (id, nome, descricao, `order`, ativo, created_at)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$id, $nome, $descricao, $order, $ativo, date('Y-m-d H:i:s')]
        );

        // [6] AUDIT LOG
        Logger::getInstance()->audit('CREATE_RECURSO', $this->getUser()['id'], [
            'recurso_id' => $id,
            'nome' => $nome
        ]);

        // [7] RATE LIMIT INCREMENT
        RateLimiter::increment('recurso_create', $ip, 60);

        // [8] FEEDBACK
        $this->success('Registro criado com sucesso!');
        $this->redirect('/admin/recurso');

    } catch (Exception $e) {
        Logger::getInstance()->warning('CREATE_RECURSO_FAILED', [
            'reason' => $e->getMessage(),
            'user_id' => $this->getUser()['id']
        ]);

        $this->error($e->getMessage());
        $this->redirect('/admin/recurso/create');
    }
}
```

**Checklist:**
```
[ ] validateCSRF() como primeira linha
[ ] RateLimiter::check() + increment()
[ ] Sanitização de todos inputs
[ ] Validações de campos obrigatórios
[ ] Security::generateUUID() para ID
[ ] Prepared statements
[ ] Logger.audit() após sucesso
[ ] Feedback + redirect
```

---

## PASSO 4B: UPLOAD DE ARQUIVOS

**⚠️ EXECUTAR APENAS SE:** `$needs_upload = True`

### Implementação completa

```php
// Adicionar no store() após sanitização, antes de validações

// [4B] UPLOAD DE ARQUIVO
if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    throw new Exception('Imagem é obrigatória');
}

// Validar tamanho (5MB máximo)
$maxSize = 5 * 1024 * 1024;
if ($_FILES['imagem']['size'] > $maxSize) {
    throw new Exception('Imagem muito grande. Máximo: 5MB');
}

// Validar tipo MIME
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['imagem']['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimes)) {
    throw new Exception('Tipo não permitido. Use: JPG, PNG, GIF, WEBP');
}

// Validar extensão
$extension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($extension, $allowedExtensions)) {
    throw new Exception('Extensão não permitida');
}

// Criar diretório se não existir
$uploadDir = __DIR__ . '/../../storage/uploads/recurso/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        throw new Exception('Erro ao criar diretório de upload');
    }
}

// Gerar nome único
$fileId = Security::generateUUID();
$timestamp = time();
$fileName = $fileId . '_' . $timestamp . '.' . $extension;
$filePath = $uploadDir . $fileName;

// Mover arquivo
if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $filePath)) {
    throw new Exception('Erro ao salvar arquivo');
}

// Permissões
chmod($filePath, 0644);

// ⚡ OTIMIZAR IMAGEM (OBRIGATÓRIO)
$this->optimizeImage($filePath, $mimeType);

// Path relativo para banco
$relativeFilePath = '/storage/uploads/recurso/' . $fileName;
```

### Método optimizeImage()

```php
/**
 * Otimizar imagem automaticamente
 */
private function optimizeImage($filePath, $mimeType) {
    try {
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filePath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($filePath);
                break;
            default:
                return false;
        }

        if (!$image) return false;

        // Redimensionar se muito grande (max 1920px)
        $width = imagesx($image);
        $height = imagesy($image);

        $maxWidth = 1920;
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) ($height * ($maxWidth / $width));

            $resized = imagescale($image, $newWidth, $newHeight, IMG_BICUBIC);

            if ($resized) {
                imagedestroy($image);
                $image = $resized;
            }
        }

        // Salvar com compressão otimizada
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($image, $filePath, 85);
                break;
            case 'image/png':
                imagepng($image, $filePath, 6);
                break;
            case 'image/gif':
                imagegif($image, $filePath);
                break;
            case 'image/webp':
                imagewebp($image, $filePath, 85);
                break;
        }

        imagedestroy($image);
        chmod($filePath, 0644);

        return true;

    } catch (Exception $e) {
        error_log('Image optimization failed: ' . $e->getMessage());
        return false;
    }
}
```

**Checklist:**
```
[ ] Validação MIME + extensão + tamanho
[ ] Diretório /storage/uploads/[recurso]/
[ ] Nome único (UUID + timestamp)
[ ] move_uploaded_file() executado
[ ] Permissões 0644
[ ] optimizeImage() executado
[ ] Path relativo salvo no banco
```

---

## PASSO 4C: PATH TRAVERSAL PROTECTION

**⚠️ EXECUTAR SEMPRE QUE:** Tiver `unlink()` no código (update ou destroy)

### Proteção obrigatória

```php
// No update() ou destroy(), ANTES de deletar arquivo antigo:

if (!empty($oldImagePath) && file_exists(__DIR__ . '/../../' . $oldImagePath)) {

    // [1] Resolver paths absolutos
    $uploadBasePath = realpath(__DIR__ . '/../../storage/uploads/');
    $oldImageFullPath = realpath(__DIR__ . '/../../' . $oldImagePath);

    // [2] Validar que arquivo está DENTRO de /storage/uploads/
    if ($oldImageFullPath && strpos($oldImageFullPath, $uploadBasePath) === 0) {
        unlink($oldImageFullPath); // SEGURO
    } else {
        // ATAQUE DETECTADO
        Logger::getInstance()->critical('PATH_TRAVERSAL_ATTEMPT', [
            'user_id' => $this->getUser()['id'],
            'attempted_path' => $oldImagePath,
            'resource_id' => $id
        ]);
        throw new Exception('Path inválido detectado');
    }
}
```

**Checklist:**
```
[ ] realpath() para resolver path absoluto
[ ] strpos() verifica se está dentro de /storage/uploads/
[ ] Log CRITICAL se detectar ataque
[ ] Throw exception se path inválido
```

---

## PASSO 5, 6, 7: edit(), update(), destroy()

**Seguir templates em `/docs/crud/templates/TEMPLATE-CRUD-ADMIN.md`**

**Pontos críticos:**
- UUID validation em todos
- CSRF + Rate Limiting em update/destroy
- Path protection antes de unlink()
- Logger.audit() após modificações

---

## PASSO 8: ADICIONAR ROTAS

**Arquivo:** `/routes/admin.php`

```php
// CRUD: Recurso
Router::get('/admin/recurso', function() {
    $controller = new RecursoController();
    $controller->index();
});

Router::get('/admin/recurso/create', function() {
    $controller = new RecursoController();
    $controller->create();
});

Router::post('/admin/recurso', function() {
    $controller = new RecursoController();
    $controller->store();
});

Router::get('/admin/recurso/:id/edit', function($id) {
    $controller = new RecursoController();
    $controller->edit($id);
});

Router::post('/admin/recurso/:id', function($id) {
    $controller = new RecursoController();
    $controller->update($id);
});

Router::post('/admin/recurso/:id/delete', function($id) {
    $controller = new RecursoController();
    $controller->destroy($id);
});
```

---

## PASSO 9: ADICIONAR LINK NO MENU

**Arquivo:** `/admin/views/includes/sidebar.php`

```php
<li>
    <a href="/admin/recurso">
        <i data-lucide="icon-name"></i>
        Nome do Recurso
    </a>
</li>
```

---

## PASSO 10: TESTAR CRUD ADMIN

**Testar manualmente:**
```
[ ] index() carrega e exibe registros
[ ] create() abre formulário
[ ] store() cria registro
[ ] edit() carrega dados corretos
[ ] update() atualiza registro
[ ] destroy() deleta registro
[ ] Paginação funciona
[ ] Upload funciona (se tem)
```

---

## PASSO 11: CHECKPOINT FRONTEND

### ⛔ PARE AQUI - VERIFICAÇÃO OBRIGATÓRIA

```python
# Reler resposta da pergunta 6 do PASSO -2 (1-START.md)
# Verificar valor de $needs_frontend_display

if $needs_frontend_display == True:
    print("🚨 PASSO 11B é OBRIGATÓRIO")
    print("Usuário solicitou frontend display")
    goto PASSO_11B

else:
    print("❓ Frontend display não foi solicitado")
    perguntar_usuario("Deseja criar frontend display mesmo assim?")

    if usuario_responde_sim():
        goto PASSO_11B
    else:
        goto PASSO_12
```

---

## PASSO 11B: CRIAR DISPLAY FRONTEND

**⚠️ EXECUTAR SE:** `$needs_frontend_display = True` OU usuário solicitar

### 11B.1: Controller Frontend

```php
<?php
// /frontend/controllers/FrontendRecursoController.php

class FrontendRecursoController {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getActive() {
        try {
            $registros = $this->db->query(
                "SELECT id, nome, descricao, imagem
                 FROM tbl_recurso
                 WHERE ativo = 1
                 ORDER BY `order` ASC"
            );

            return $registros ?? [];

        } catch (Exception $e) {
            error_log('FrontendRecursoController::getActive() ERROR: ' . $e->getMessage());
            return [];
        }
    }
}
```

### 11B.2: Partial Reutilizável

```php
<?php
// /frontend/views/partials/recurso-display.php

$controller = new FrontendRecursoController();
$registros = $controller->getActive();

if (empty($registros)) return;
?>

<section class="c-recurso-display">
    <?php foreach ($registros as $reg): ?>
        <div class="c-recurso-display__item">
            <?php if (!empty($reg['imagem'])): ?>
                <img src="<?= htmlspecialchars($reg['imagem']) ?>"
                     alt="<?= htmlspecialchars($reg['nome']) ?>"
                     loading="lazy">
            <?php endif; ?>

            <h3><?= htmlspecialchars($reg['nome']) ?></h3>
            <p><?= htmlspecialchars($reg['descricao']) ?></p>
        </div>
    <?php endforeach; ?>
</section>
```

### 11B.3: Preview no Admin

Adicionar no final de `/admin/views/recurso/index.php`:

```php
<!-- Preview Frontend -->
<?php if (!empty($registros)): ?>
    <hr style="margin: 40px 0;">

    <h2>Preview Frontend</h2>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
        <?php Core::requireInclude('frontend/views/partials/recurso-display.php', true); ?>
    </div>

    <h3>Código para copiar:</h3>
    <pre><code>&lt;?php Core::requireInclude('frontend/views/partials/recurso-display.php', true); ?&gt;</code></pre>
<?php endif; ?>
```

### 11B.4: SASS Dedicado

**Arquivo:** `/assets/sass/frontend/components/_recurso-display.sass`

```sass
// Componente: Recurso Display

.c-recurso-display
  display: grid
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))
  gap: 20px
  padding: 20px 0

  &__item
    background: white
    padding: 20px
    border-radius: 8px
    box-shadow: 0 2px 4px rgba(0,0,0,0.1)

    img
      width: 100%
      height: auto
      border-radius: 4px
      margin-bottom: 15px

    h3
      font-size: 1.5rem
      margin-bottom: 10px

    p
      color: #666
      line-height: 1.6
```

**Adicionar import em:** `/assets/sass/frontend/components/_components.sass`

```sass
@use 'recurso-display'
```

### 11B.5: Testar Preview

```
[ ] Preview aparece no admin
[ ] Display funciona no frontend
[ ] SASS compilado sem erros
[ ] Código para copiar funciona
```

### 11B.6: Documentar Uso

Criar `/docs/recurso-customizacao.md` com instruções de customização.

**Checklist PASSO 11B completo:**
```
[ ] Controller frontend criado
[ ] Partial reutilizável criado
[ ] Preview no admin funcionando
[ ] SASS dedicado compilado
[ ] Testado em página real
[ ] Documentação criada
```

---

## PASSO 12: VALIDAÇÃO AUTOMÁTICA

### Executar script

```bash
php scripts/validate-crud.php RecursoController
```

### Analisar resultado

```
SCORE: X/20 (Y%)

- 100%: ✅ Perfeito, entregar
- 90-99%: ✅ Bom, entregar com avisos
- < 90%: ❌ Corrigir antes de entregar
```

### Se < 90%, corrigir itens faltantes

**Erros comuns:**
- ❌ SELECT * encontrado → trocar por campos específicos
- ❌ Sem paginação → adicionar LIMIT/OFFSET
- ❌ Sem UUID validation → adicionar em edit/update/destroy
- ❌ Sem path protection → adicionar antes de unlink()

**Rodar novamente até atingir 90%+**

---

## PASSO 13: ENTREGAR

### Checklist final

```
✅ CRUD ADMIN:
[ ] 6 métodos implementados
[ ] Segurança: CSRF, Rate Limiting, UUID, Path Protection
[ ] Performance: Paginação, SELECT específico
[ ] Upload otimizado (se aplicável)
[ ] Audit log completo

✅ FRONTEND (se solicitado):
[ ] Controller frontend criado
[ ] Partial reutilizável
[ ] Preview funcionando
[ ] SASS compilado
[ ] Documentação criada

✅ VALIDAÇÃO:
[ ] Script executado
[ ] Score 90%+
[ ] Sem erros críticos
```

### Anúncio de entrega

```
✅ CRUD de [Recurso] criado com sucesso!

📁 Arquivos criados:
- /migrations/XXX_create_[recurso]_table.sql
- /admin/controllers/RecursoController.php
- /admin/views/recurso/index.php
- /admin/views/recurso/create.php
- /admin/views/recurso/edit.php
[se frontend:]
- /frontend/controllers/FrontendRecursoController.php
- /frontend/views/partials/recurso-display.php
- /assets/sass/frontend/components/_recurso-display.sass
- /docs/recurso-customizacao.md

📊 Validação: XX/20 (XX%)
✅ Segurança: 10/10
✅ Performance: 10/10
✅ Escalabilidade: 10/10

Pronto para usar!
```

---

## ⛔ CHECKPOINTS FINAIS

**ANTES de entregar, verificar:**

### Segurança (20 itens)
```
[ ] UUID validation em edit/update/destroy
[ ] CSRF em store/update/destroy
[ ] Rate Limiting com check + increment
[ ] Path traversal protection
[ ] Sanitização de inputs
[ ] Prepared statements em 100%
[ ] Backticks em reserved keywords
[ ] Upload: MIME + extensão + tamanho
[ ] Permissões corretas (0644/0755)
[ ] Audit log completo
```

### Performance (12 itens)
```
[ ] Zero SELECT *
[ ] Paginação no index()
[ ] Campos específicos em queries
[ ] Índices na tabela
[ ] Imagens otimizadas
[ ] imagedestroy() após uso
```

### Escalabilidade (8 itens)
```
[ ] Arquitetura separada (admin/frontend)
[ ] Storage organizado
[ ] Preparado para 10k+ registros
```

**Score total esperado: 40/40 (100%)**

---

**Fim do guia técnico.**

**Próximo:** `/docs/crud/3-VALIDATE.md`
