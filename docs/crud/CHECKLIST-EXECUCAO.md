# CHECKLIST DE EXECUÇÃO - CRIAR CRUD

**USAR TODA VEZ QUE CRIAR UM CRUD**

Marque cada item conforme avança. **Só entregue se todos os □ virarem ✓**

---

## PRÉ-REQUISITOS

```
□ GUIA-PRATICO.md aberto e sendo consultado
□ Nome do recurso definido (ex: Banner, Category, Product)
□ Tabela já existe no banco OU vai ser criada agora
```

---

## PASSO -2: COLETAR REQUISITOS

```
□ Pergunta 1: O que é este recurso? _________________
□ Pergunta 2: Quem acessa admin? (Admin geral/Super admin/etc)
□ Pergunta 3: Quais campos? (listar todos)
□ Pergunta 4: Comportamentos especiais?
  □ Upload de arquivo? (SIM/NÃO)
  □ Ordenação/ranking? (SIM/NÃO)
  □ Status ativo/inativo? (SIM/NÃO)
  □ Datas especiais? (SIM/NÃO)
  □ Relacionamentos? (SIM/NÃO)
□ Pergunta 5: Exibir em frontend? (SIM/NÃO)
□ Pergunta 6: Se SIM, aonde? _________________
□ Pergunta 7: Se SIM, como? _________________
```

---

## PASSO -1: CRIAR TABELA NO BANCO

```
□ Arquivo SQL criado em /migrations/XXX_create_[recurso]_table.sql
□ Charset: utf8mb4
□ Collation: utf8mb4_unicode_ci
□ PRIMARY KEY: CHAR(36) UUID
□ Índices em: ativo, order, created_at (conforme necessário)
□ Reserved keywords com backticks (`order`, `group`, `key`, etc)
□ Migration executada: tabela existe no banco
```

---

## PASSO 0: PREPARAÇÃO

```
□ Classe verificada: nome único em /admin/controllers/
□ Arquivo criado: /admin/controllers/[Recurso]Controller.php
□ Nome do arquivo = nome da classe exatamente
```

---

## PASSO 1: ESCOLHER TEMPLATE

```
□ Template ADMIN aberto: /docs/crud/templates/TEMPLATE-CRUD-ADMIN.md
□ Estrutura base copiada para o controller
```

---

## PASSO 2: IMPLEMENTAR index()

```
□ $this->requireAuth() adicionado
□ Query com prepared statements
□ ORDER BY implementado
□ $this->render() chamado com dados
```

---

## PASSO 3: IMPLEMENTAR create()

```
□ $this->requireAuth() adicionado
□ Dados relacionados buscados (se necessário)
□ $this->render() chamado com dados
```

---

## PASSO 4: IMPLEMENTAR store() [CRÍTICO]

### [1] CSRF VALIDATION
```
□ $this->validateCSRF() como PRIMEIRA linha do try
□ CHECKPOINT CSRF aprovado
```

### [2] RATE LIMITING
```
□ RateLimiter::check('recurso_create', $ip, 5, 60) ANTES de validações
□ HTTP 429 retornado se bloqueado
□ RateLimiter::increment() DEPOIS do insert bem-sucedido
□ CHECKPOINT RATE LIMIT aprovado
```

### [3] SANITIZAÇÃO
```
□ Todos inputs com Security::sanitize()
□ Emails com strtolower()
□ Passwords NÃO sanitizados (hash depois)
```

### [4B] UPLOAD DE ARQUIVO (se houver)
```
□ Validação de UPLOAD_ERR_OK
□ Validação de tamanho (5MB)
□ Validação de MIME type (finfo_file)
□ Validação de extensão
□ Diretório criado: /storage/uploads/[recurso]/
□ Nome gerado: UUID + timestamp + extensão
□ move_uploaded_file() executado
□ chmod 0644 aplicado
□ Path RELATIVO salvo no banco
□ CHECKPOINT UPLOAD aprovado
```

### [4] VALIDAÇÕES
```
□ Campos obrigatórios: empty() checks
□ Email: Security::validateEmail() + uniqueness
□ Senha: Security::validatePasswordStrength()
□ UUID: Security::isValidUUID() + existence
□ Strings: strlen() min/max
□ Slug: regex + uniqueness
□ Arrays: is_array() + filtrados
```

### [5] CREATE
```
□ $id = Security::generateUUID()
□ Dados preparados (apenas sanitizados/validados)
□ $this->db()->insert() com prepared statements
□ SEM concatenação de strings em SQL
```

### [6] AUDIT LOG
```
□ Logger::getInstance()->audit('CREATE_[RECURSO]', user_id, [...])
□ Nome: CREATE_[RECURSO] (maiúsculas, singular)
□ Array tem: resource_id, table, contexto
□ CHECKPOINT LOGGER aprovado
```

### [7] INCREMENT RATE LIMIT
```
□ RateLimiter::increment('recurso_create', $ip, 60) APÓS insert
```

### [8] FEEDBACK & REDIRECT
```
□ $this->success() com mensagem
□ $this->redirect() para listagem
```

### [9] EXCEPTION HANDLING
```
□ catch (Exception $e) implementado
□ Logger::warning('[CREATE]_FAILED') no catch
□ $this->error() com mensagem
□ Redirect de volta para create
```

---

## PASSO 5: IMPLEMENTAR edit()

```
□ $this->requireAuth() adicionado
□ Security::isValidUUID($id) validado
□ Registro buscado e verificado se existe
□ Dados relacionados buscados (se necessário)
□ $this->render() chamado com registro
```

---

## PASSO 6: IMPLEMENTAR update() [CRÍTICO]

```
□ [1] CSRF validation ✓
□ [2] UUID validation ANTES de queries ✓
□ [3] Rate limiting (10 em 60s) ✓
□ [4] Sanitização ✓
□ [4B] Upload (se houver): deletar arquivo antigo ✓
□ [5] Validações (email uniqueness EXCLUI próprio) ✓
□ [6] UPDATE com prepared statements ✓
□ [7] Logger::audit('UPDATE_[RECURSO]') com fields_updated ✓
□ [8] RateLimiter::increment() ✓
□ [9] Feedback + redirect ✓
□ [10] Exception handling ✓
```

---

## PASSO 7: IMPLEMENTAR destroy() [CRÍTICO]

```
□ [1] CSRF validation ✓
□ [2] UUID validation ✓
□ [3] Rate limiting (5 em 60s) ✓
□ [4] Registro buscado e guardado (para logger) ✓
□ [5] Validações adicionais (não pode deletar X?) ✓
□ [6A] Deletar arquivo físico (se upload) ✓
□ [6B] DELETE com prepared statements ✓
□ [7] Logger::audit('DELETE_[RECURSO]') com SNAPSHOT ✓
□ [8] RateLimiter::increment() ✓
□ [9] Feedback + redirect ✓
□ [10] Exception handling ✓
```

---

## PASSO 8: CRIAR VIEWS

```
□ Diretório criado: mkdir -p /admin/views/[recurso]/
□ Arquivo criado: index.php
□ Arquivo criado: create.php
□ Arquivo criado: edit.php
□ chmod 644 executado em TODOS arquivos
□ Outputs com htmlspecialchars()
□ CSRF token nos forms (create.php, edit.php)
```

---

## GATE PASSO 8: VERIFICAÇÃO FINAL [BLOQUEIO]

**⛔ NÃO PROSSIGA PARA PASSO 9 ATÉ COMPLETAR 100%**

### Checkpoint 1/3: Segurança Crítica
```
□ CSRF validation em store/update/destroy?
□ RateLimiter::check() + increment() em store/update/destroy?
□ Logger::audit() em store/update/destroy?
□ Prepared statements em TODAS queries?
□ Nenhuma concatenação de SQL?
□ Reserved keywords com backticks?
```

### Checkpoint 2/3: Validações e Feedback
```
□ Empty checks para obrigatórios?
□ Email: formato + uniqueness?
□ Senha: força (create) + opcional (update)?
□ UUID: validation + existence?
□ Strings: min/max?
□ Try/catch em todos métodos?
□ Logger::warning() nos catches?
□ Mensagens de sucesso/erro?
```

### Checkpoint 3/3: Estrutura e Nomenclatura
```
□ 6 métodos implementados?
□ Herança: extends BaseController?
□ Auth em TODOS métodos?
□ Nomes de ação: CREATE_*, UPDATE_*, DELETE_* (singular, maiúsculas)?
□ RateLimiter keys consistentes?
```

**❌ SE ALGUM CHECKPOINT FALHOU: VOLTE E CORRIJA**

**✅ SE TODOS PASSARAM: LIBERADO PARA PASSO 9**

---

## PASSO 9: ADICIONAR ROUTES

```
□ Arquivo correto: /routes/admin.php
□ Rota 1: GET /admin/[recurso] → index()
□ Rota 2: GET /admin/[recurso]/create → create()
□ Rota 3: POST /admin/[recurso] → store()
□ Rota 4: GET /admin/[recurso]/:id/edit → edit($id)
□ Rota 5: POST /admin/[recurso]/:id → update($id)
□ Rota 6: POST /admin/[recurso]/:id/delete → destroy($id)
□ Todas com Auth::require()
□ Parâmetros $id passados corretamente
```

---

## PASSO 11: ADMIN CRUD COMPLETO

```
□ Controller em /admin/controllers/ ✓
□ 6 métodos funcionando ✓
□ Views em /admin/views/ ✓
□ Rotas em /routes/admin.php ✓
□ CRUD 100% funcional ✓
```

---

## PASSO 11B: FRONTEND DISPLAY (OPCIONAL)

**Só preencher se resposta da pergunta 6 foi SIM**

```
□ Controller criado: /frontend/controllers/Frontend[Recurso]Controller.php
□ Método index() implementado (read-only)
□ Método api() implementado (JSON)
□ View criada: /frontend/views/[recurso]/index.php
□ chmod 644 na view
□ Rotas adicionadas em /routes/public.php
□ Integrado na página: home.php (ou outra)
□ Testado: dados aparecem corretamente
```

---

## PASSO 12: TESTES OBRIGATÓRIOS [BLOQUEIO FINAL]

**⛔ NÃO ENTREGUE SEM PASSAR NOS 4 TESTES**

### 🧪 TESTE 1: Funcionalidade Básica
```
□ GET /admin/[recurso] → 200 OK, lista aparece
□ GET /admin/[recurso]/create → 200 OK, form aparece
□ POST create → registro criado no banco
□ GET /admin/[recurso]/[id]/edit → 200 OK, form com dados
□ POST edit → registro atualizado no banco
□ POST delete → registro removido do banco
```

### 🔒 TESTE 2: Segurança
```
□ Remover CSRF token → submit bloqueado
□ 10 submits rápidos → rate limit bloqueou
□ SELECT * FROM logs_audit → registros CREATE/UPDATE/DELETE existem
□ SQL injection test ('); DROP TABLE--) → bloqueado
```

### 📁 TESTE 3: Permissões
```
□ ls -la /admin/views/[recurso]/*.php → todos 644
□ Se não: chmod 644 /admin/views/[recurso]/*.php
□ Testar no browser: sem erro 500
```

### 📊 TESTE 4: Auditoria
```
□ SELECT * FROM logs_audit WHERE action LIKE 'CREATE_%' ORDER BY created_at DESC LIMIT 5
□ Logs têm: user_id, ip, resource_id, table
□ Logs de DELETE têm snapshot de dados deletados
```

---

## VALIDAÇÃO AUTOMÁTICA (OPCIONAL)

```bash
□ php /scripts/validate-crud.php [RecursoController]
□ Score: 100% (7/7 checks)
```

---

## ENTREGA FINAL

```
□ TODOS os checkboxes acima estão marcados ✓
□ TODOS os testes passaram ✓
□ TODOS os gates foram aprovados ✓
□ CRUD testado no browser ✓
□ Código commitado ✓
```

---

## 🎉 PRONTO!

**Se TODOS os itens estão ✓, seu CRUD está pronto para produção.**

**Score final:** _____ / _____ checks (objetivo: 100%)
