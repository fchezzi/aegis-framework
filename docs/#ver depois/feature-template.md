# 🚀 Template: Nova Feature

**Tempo estimado:** 1-4h (depende da complexidade)
**Complexidade:** Média-Alta

---

## 📋 Antes de Começar

**Perguntas obrigatórias:**

- [ ] Qual o nome da feature? (ex: "Sistema de notificações", "Export para Excel")
- [ ] Qual o objetivo? (descrever em 1 frase)
- [ ] Onde vai ser implementada? (admin, módulo, frontend)
- [ ] Precisa de tabela nova? (listar se sim)
- [ ] Precisa de permissões? (sim/não)
- [ ] Tem impacto em performance? (sim/não)
- [ ] Tem risco de segurança? (sim/não)

---

## 🎯 Passo 1: Planejamento (5-10 min)

### 1.1 Especificação Rápida

Escrever 3-5 bullet points do que a feature FAZ:

```markdown
**Feature:** Sistema de Notificações

**O que faz:**
- Envia notificações por email para usuários
- Exibe badge no menu com contador
- Permite marcar como lida/não lida
- Histórico de notificações nos últimos 30 dias
```

### 1.2 Arquivos Afetados

Listar TODOS os arquivos que vão ser criados/modificados:

```markdown
**Criar:**
- database/schemas/notifications.sql
- core/Notification.php
- admin/controllers/NotificationsController.php
- admin/views/notifications.php
- frontend/templates/partials/notifications-badge.php

**Modificar:**
- admin/views/includes/header.php (adicionar badge)
- core/Core.php (helper sendNotification)
```

### 1.3 Decisões Arquiteturais

Se a feature envolve escolha entre alternativas, documentar:

```markdown
**Decisão:** Como enviar emails?
- Opção A: PHP mail() - simples, mas pode cair em spam
- Opção B: SMTP externo - mais confiável, precisa configuração
- **Escolhido:** Opção B (SMTP) - mais profissional

**Rationale:** Emails institucionais precisam de deliverability alta
```

---

## 🛠️ Passo 2: Implementação

### 2.1 Database (se necessário)

**Template SQL:**

```sql
CREATE TABLE IF NOT EXISTS `tbl_notifications` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'error') NOT NULL DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`, `read`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `tbl_members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Checklist DB:**
- ✅ Índices para queries frequentes
- ✅ Foreign keys com ON DELETE apropriado
- ✅ ENUM para status/tipos (performance)
- ✅ Timestamps

### 2.2 Core Class (se necessário)

**Localização:** `core/{Feature}.php`

**Template:**

```php
<?php
/**
 * @doc {Feature}
 *
 * {Descrição da feature em 1 linha}
 *
 * @security {Descrever se tem impacto de segurança}
 * @performance {Descrever se tem impacto de performance}
 */

class {Feature} {
    private static $instance = null;
    private $db;

    private function __construct() {
        $this->db = DB::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Método principal da feature
     *
     * @param array $params Parâmetros
     * @return mixed Resultado
     */
    public function execute($params) {
        // ✅ VALIDAÇÃO
        if (!$this->validate($params)) {
            return false;
        }

        // ✅ IMPLEMENTAÇÃO
        try {
            $result = $this->process($params);
            return $result;
        } catch (Exception $e) {
            error_log("Feature error: " . $e->getMessage());
            return false;
        }
    }

    private function validate($params) {
        // Validar parâmetros
        return true;
    }

    private function process($params) {
        // Lógica principal
        return true;
    }
}
```

### 2.3 Controller

Usar template de CRUD se aplicável, ou criar controller específico:

```php
<?php
/**
 * @doc {Feature}Controller
 * @api Endpoints da feature
 */

require_once __DIR__ . '/../../_config.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/{Feature}.php';

Auth::require();

class {Feature}Controller {
    private $feature;

    public function __construct() {
        $this->feature = {Feature}::getInstance();
    }

    /**
     * @api POST /admin/{feature}/execute
     * Executa a feature
     */
    public function execute() {
        // ✅ CSRF
        if (!Security::validateCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            return ['error' => 'Token CSRF inválido'];
        }

        // ✅ VALIDAÇÃO
        $params = $this->validateInput($_POST);
        if (!$params) {
            http_response_code(400);
            return ['error' => 'Parâmetros inválidos'];
        }

        // ✅ EXECUÇÃO
        $result = $this->feature->execute($params);

        if ($result) {
            return ['success' => true, 'data' => $result];
        }

        http_response_code(500);
        return ['error' => 'Erro ao executar feature'];
    }

    private function validateInput($data) {
        // Validar e sanitizar
        return $data;
    }
}
```

### 2.4 Frontend (se necessário)

**View PHP:**

```php
<?php
require_once __DIR__ . '/../_config.php';
require_once BASE_PATH . '/core/Auth.php';
Auth::require();

$pageTitle = '{Feature}';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <h1><?= $pageTitle ?></h1>

    <!-- Conteúdo da feature -->
    <div class="card">
        <div class="card-body">
            <form id="form-{feature}">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <!-- Campos do formulário -->
                <button type="submit" class="btn btn-primary">Executar</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('form-{feature}').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    const response = await fetch('/admin/controllers/{Feature}Controller.php?action=execute', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        alert('Sucesso!');
    } else {
        alert(result.error || 'Erro');
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
```

---

## 🔒 Passo 3: Segurança

**Checklist obrigatório:**

- [ ] **Input Validation:** Todos os inputs validados (tipo, formato, tamanho)
- [ ] **Output Encoding:** XSS prevention (htmlspecialchars)
- [ ] **SQL Injection:** Prepared statements ou $db methods
- [ ] **CSRF:** Token em todos os formulários POST/PUT/DELETE
- [ ] **Authentication:** Auth::require() quando necessário
- [ ] **Authorization:** Verificar permissões do usuário
- [ ] **File Upload:** Se tem upload, validar MIME + extensão + tamanho
- [ ] **Rate Limiting:** Se API pública, limitar requests

---

## ⚡ Passo 4: Performance

**Checklist obrigatório:**

- [ ] **Database:**
  - Índices nas colunas de busca
  - Evitar SELECT * (especificar colunas)
  - Usar LIMIT/OFFSET (paginação)
  - Evitar N+1 queries

- [ ] **Caching:**
  - Dados raramente mudam? → Cachear
  - TTL apropriado (ex: 1 hora)
  - Invalidar quando dados mudam

- [ ] **Assets:**
  - JS/CSS minificados
  - Imagens otimizadas
  - Lazy loading se muitas imagens

---

## 🧪 Passo 5: Testes

**Testes manuais obrigatórios:**

1. **Happy Path:** Feature funciona com inputs válidos?
2. **Edge Cases:** Testa com inputs vazios, nulos, muito grandes?
3. **Security:** Tenta XSS, SQL Injection, CSRF?
4. **Performance:** Funciona rápido mesmo com muitos dados?
5. **Errors:** Mensagens de erro são claras e não expõem info sensível?

**Checklist:**

```markdown
- [ ] Testei com usuário admin
- [ ] Testei com usuário sem permissão (deve bloquear)
- [ ] Testei com inputs inválidos (deve rejeitar)
- [ ] Testei com muitos dados (performance ok?)
- [ ] Testei XSS básico (deve sanitizar)
- [ ] Testei sem CSRF token (deve bloquear)
```

---

## 📝 Passo 6: Documentação

**Atualizar arquivos obrigatórios:**

```bash
✅ .claude/memory/index.json
   - Adicionar componente em "coreComponents" ou seção apropriada
   - Listar dependências

✅ .claude/memory/changelog.json
   - Entry tipo "feature"
   - Severidade: "major" ou "normal"
   - Descrever o que foi feito

✅ .claude/memory/codebase-map.json
   - Adicionar arquivos criados
   - Mapear dependências

✅ .claude/memory/sessions.json
   - Registrar tarefa na sessão atual

✅ .claude/memory/decisions.json (se aplicável)
   - Se tomou decisão arquitetural, criar ADR
```

**Se feature pública (para usuários):**

```bash
✅ docs/guides/{FEATURE}_GUIA.md
   - Como usar a feature
   - Screenshots se relevante
   - Troubleshooting comum
```

---

## 🎯 Exemplo Completo

**Feature:** Sistema de Notificações por Email

**Arquivos criados:**
1. `database/schemas/notifications.sql` (30 linhas)
2. `core/Notification.php` (150 linhas)
3. `admin/controllers/NotificationsController.php` (100 linhas)
4. `admin/views/notifications.php` (120 linhas)

**Tempo:** 3 horas

**Checklist final:**
- ✅ Tabela criada com índices
- ✅ SMTP configurado
- ✅ Controller com CSRF
- ✅ View funcional
- ✅ Testes manuais feitos
- ✅ Documentação atualizada

---

**Versão:** 1.0.0
**Criado em:** 2025-01-20
**Uso:** Adaptar conforme necessidade da feature
