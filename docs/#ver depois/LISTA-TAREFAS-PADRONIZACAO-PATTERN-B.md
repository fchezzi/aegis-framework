# 📋 LISTA DE TAREFAS: Padronização Pattern B em AEGIS

**Data:** 2026-02-12  
**Objetivo:** Converter AEGIS inteiro para Pattern B (Static) e replicar para 4 projetos  
**Estimativa Total:** ~40-50 horas

---

## FASE 1: ANÁLISE E DOCUMENTAÇÃO (6h)

### 1.1 Ler TODOS os 14 controllers que já usam Pattern B
- **O quê:** Analisar GroupController, MenuController, PagesController, etc. para entender padrões específicos
- **Por quê:** Cada controller tem técnicas especiais (batch queries, file locking, recursion) que preciso documentar
- **Tamanho:** ~400 linhas cada × 5 controllers importantes
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 95%
  - ✅ Já analisei 3 (AdminController, MemberController, AuthController)
  - ✅ Sei ler PHP
  - ❌ Pode ter peculiaridades em componentes não analisados
- **Risco:** BAIXO
- **Notas:** GroupController e MenuController têm padrões avançados que não posso perder

---

### 1.2 Documentar "Template CRUD Pattern B" final
- **O quê:** Criar template oficial que TODOS os futuros CRUDs vão usar
- **Por quê:** Sem template claro, devs vão criar variações
- **Inclui:**
  - [ ] Header e imports obrigatórios
  - [ ] Estrutura 6-método CRUD
  - [ ] Comentários de segurança
  - [ ] Validações padrão (CSRF, sanitização, UUID)
  - [ ] Try/catch + session + redirect
  - [ ] Tratamento de erros
  - [ ] Paginação (se aplicável)
  - [ ] Proteções específicas (delete, etc)
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 85%
  - ✅ Analisei padrão em 5+ controllers
  - ❌ Pode ter edge cases que não descobri
  - ❌ Batch queries e técnicas avançadas precisam documentação extra
- **Risco:** MÉDIO
- **Notas:** Esse é O template que será copiado 4x. Precisa estar 100% correto

---

### 1.3 Verificar se BaseController está sendo usado em MAIS lugares
- **O quê:** Grep por "extends BaseController" em TODO o projeto
- **Por quê:** Pode haver classes que não são controllers usando BaseController
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 95%
  - ✅ Posso usar ferramenta Grep
  - ✅ Padrão é claro ("extends BaseController")
- **Risco:** BAIXO
- **Notas:** Se existir outras classes, preciso refatorar também

---

### 1.4 Mapear todas as funções de BaseController que usamos
- **O quê:** Lista de CADA método chamado em Pattern A controllers
  - [ ] `$this->requireAuth()`
  - [ ] `$this->getUser()`
  - [ ] `$this->db()`
  - [ ] `$this->input($field)`
  - [ ] `$this->validateCSRF()`
  - [ ] `$this->render($view, $data)`
  - [ ] `$this->success($msg)`
  - [ ] `$this->error($msg)`
  - [ ] `$this->redirect($url)`
- **Por quê:** Preciso saber o que cada método faz para substituir corretamente
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 90%
  - ✅ Já li AdminController que usa tudo
  - ✅ Consigo ler BaseController.php
  - ❌ Pode ter métodos que não descobri
- **Risco:** BAIXO
- **Notas:** Essencial antes de refatorar

---

## FASE 2: REFATORAÇÃO DE CONTROLLERS EXISTENTES (8h)

### 2.1 Refatorar AdminController (de Pattern A → Pattern B)
- **O quê:** 
  ```php
  // Antes:
  class AdminController extends BaseController {
      public function index() {
          $this->requireAuth();
          $this->db()->select(...);
          $this->render(...);
      }
  }
  
  // Depois:
  class AdminController {
      public function index() {
          Auth::require();
          $db = DB::connect();
          $admins = $db->select(...);
          require __DIR__ . '/../views/admins/index.php';
      }
  }
  ```
- **Métodos:** 6 (index, create, store, edit, update, destroy)
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 85%
  - ✅ Conheço o padrão
  - ✅ Tenho MemberController como referência
  - ❌ Pode quebrar se BaseController faz algo não óbvio
  - ❌ Preciso testar cada método
- **Risco:** MÉDIO (se quebrar admin, sistema fica inacessível)
- **Notas:**
  - CRÍTICO: AdminController é entrada do sistema
  - Precisa de testes completos (create, edit, delete)
  - Protegê-lo de erro é prioridade

---

### 2.2 Refatorar FontsController (de Pattern A → Pattern B)
- **O quê:** Mesma refatoração que AdminController
- **Métodos:** 4 CRUD + 2 extras (preview, download)
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 88%
  - ✅ Padrão é o mesmo
  - ✅ Menos crítico que AdminController (não quebra login)
  - ❌ Tem métodos extras que podem ter lógica escondida
- **Risco:** BAIXO
- **Notas:** Testar upload e preview após refatorar

---

### 2.3 Refatorar SettingsController (de Pattern A → Pattern B)
- **O quº:** Mesma refatoração
- **Métodos:** 2 (index, update) - NÃO é CRUD completo
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 82%
  - ✅ Padrão é claro
  - ❌ É um método especial (update sem store)
  - ❌ Pode ter lógica de settings que não entendo completamente
- **Risco:** MÉDIO (settings é critico para sistema funcionar)
- **Notas:**
  - Ler SettingsController inteiro
  - Entender como Settings::all() funciona
  - Testar SMTP, GTM, FTP, cores, etc. após refatorar

---

### 2.4 Verificar views de Pattern A controllers
- **O quê:** As views dos 3 controllers acima precisam passar `$user` via sessão/query?
- **Por quê:** Pattern A passa `$user` na render(), Pattern B precisa buscar direto
- **Verificação:**
  - [ ] `admin/views/admins/*.php` → usa `$user`?
  - [ ] `admin/views/fonts/*.php` → usa `$user`?
  - [ ] `admin/views/settings.php` → usa `$user`?
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 70%
  - ✅ Consigo ler views
  - ❌ Não sei como $user é usado em cada view
  - ❌ Pode ser necessário buscar $user em cada view
- **Risco:** MÉDIO
- **Notas:** Se views usam $user, preciso adicionar no controller refatorado

---

## FASE 3: DOCUMENTAÇÃO DO PADRÃO (4h)

### 3.1 Criar PADRÃO-CRUD-PATTERN-B.md oficial
- **O quê:** Documento que SERÁ a fonte de verdade para todos os CRUDs
- **Inclui:**
  - [ ] Template base com 6 métodos
  - [ ] Checklist de segurança (CSRF, sanitização, UUID, validações)
  - [ ] Exemplo REAL (MemberController)
  - [ ] Técnicas avançadas (batch queries, paginação, file locking)
  - [ ] Proteções específicas (delete, cascata, etc)
  - [ ] Como nomear controllers, views, rotas
  - [ ] Como testar cada método
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 80%
  - ✅ Tenho informação suficiente
  - ❌ Pode ter gaps quando devs tentarem usar
  - ❌ Precisarei iterar depois de feedback real
- **Risco:** MÉDIO
- **Notas:** Documento vivo (vai ser atualizado constantemente)

---

### 3.2 Atualizar REGRAS.md com nova regra: "Use Pattern B"
- **O quê:** Adicionar regra inviolável: "Todos os controllers devem ser Pattern B (Static)"
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 99%
  - ✅ Decisão executiva tomada
  - ✅ Só adicionar uma linha
- **Risco:** ZERO
- **Notas:** Rápido

---

### 3.3 Atualizar PADROES-AEGIS-CONTROLLERS.md (já existe)
- **O quê:** Remover referência a "2 padrões", deixar só Pattern B
- **Status:** ⏳ NÃO INICIADO (já existe versão antiga)
- **Confiança:** 95%
  - ✅ Arquivo ja existe
  - ✅ Só remover seções antigas
- **Risco:** BAIXO
- **Notas:** Deletar seção de "Padrão A" inteiro

---

## FASE 4: TESTES (10h)

### 4.1 Testar AdminController refatorado (completo)
- **O quê:** 
  - [ ] Fazer login
  - [ ] Listar admins
  - [ ] Criar novo admin
  - [ ] Editar admin
  - [ ] Atualizar admin
  - [ ] Deletar admin (com proteção de único ativo)
  - [ ] Testar CSRF validation
  - [ ] Testar duplicação de email
  - [ ] Testar validação de senha fraca
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 60%
  - ✅ Consigo testar manualmente
  - ❌ Não há testes automatizados em AEGIS
  - ❌ Pode haver bugs que só aparecem em produção
  - ❌ Pode haver lógica de BaseController que esconde comportamento
- **Risco:** ALTO
- **Notas:**
  - CRÍTICO: AdminController não pode quebrar
  - Se quebrar, não acesso admin
  - Precisa de backup antes de refatorar

---

### 4.2 Testar FontsController refatorado
- **O quê:**
  - [ ] Listar fontes
  - [ ] Fazer upload de fonte WOFF2
  - [ ] Visualizar preview
  - [ ] Deletar fonte
  - [ ] Validação de MIME type
  - [ ] Validação de tamanho
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 75%
  - ✅ Menos crítico
  - ❌ Envolve upload de arquivo (pode quebrar)
- **Risco:** MÉDIO
- **Notas:** Testar com arquivo real

---

### 4.3 Testar SettingsController refatorado
- **O quê:**
  - [ ] Acessar settings
  - [ ] Salvar SMTP
  - [ ] Salvar GTM
  - [ ] Salvar FTP
  - [ ] Salvar cores
  - [ ] Validação de cores (#RRGGBB)
  - [ ] Validação de emails
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 65%
  - ✅ Posso testar campos
  - ❌ SettingsController é MUITO complexo
  - ❌ Pode quebrar integração com SMTP, GTM, FTP
  - ❌ Settings pode ter estado compartilhado que não vejo
- **Risco:** ALTO
- **Notas:**
  - Testar cada integração separadamente
  - Backup de storage/settings.json ANTES
  - Se quebrar settings, sistema pode ficar com config errada

---

### 4.4 Testar que Pattern B controllers ainda funcionam
- **O quê:** Verificar que refatoração NÃO quebrou:
  - [ ] MemberController (CRUD + permissions)
  - [ ] GroupController (CRUD + members + permissions)
  - [ ] MenuController (CRUD + updateOrder)
  - [ ] PagesController (CRUD + SEO + permissões)
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 90%
  - ✅ Não vou mexer neles (só testar)
- **Risco:** BAIXO
- **Notas:** Regressão testing

---

### 4.5 Testes de Segurança
- **O quê:**
  - [ ] CSRF token validation
  - [ ] Sanitização de inputs
  - [ ] SQL injection prevention
  - [ ] UUID validation
  - [ ] Permissão check (Auth::require())
  - [ ] Rate limiting (AuthController)
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 70%
  - ✅ Consigo testar manualmente
  - ❌ Sem testes automatizados é difícil
- **Risco:** MÉDIO
- **Notas:** Testar bypass de CSRF, SQL injection simples

---

## FASE 5: REPLICAÇÃO PARA 4 PROJETOS (15h)

### 5.1 Preparar "Package AEGIS Replicável"
- **O quº:** Criar arquivo lista de arquivos que devem ser copiados
- **Inclui:**
  - [ ] `admin/controllers/*.php` (14+ controllers)
  - [ ] `admin/views/**/*.php` (todas as views)
  - [ ] `core/*.php` (classes base)
  - [ ] `routes/*.php` (roteamento)
  - [ ] `assets/sass/**/*.sass` (estilos)
  - [ ] `.claude/REGRAS.md` (updated)
  - [ ] `docs/PADROES-AEGIS-CONTROLLERS.md` (updated)
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 85%
  - ✅ Consigo listar arquivos
  - ❌ Pode haver dados de configuração que não devem ser copiados
  - ❌ Pode haver paths absolutos que mudam entre projetos
- **Risco:** MÉDIO
- **Notas:** Documento vivo (iterar conforme descubro gaps)

---

### 5.2 Replicar para DryWash
- **O quê:** Copiar padrão AEGIS para projeto DryWash
- **Procedimento:**
  - [ ] Backup completo de DryWash
  - [ ] Copiar controllers refatorados
  - [ ] Copiar views atualizadas
  - [ ] Testar login
  - [ ] Testar CRUD de admin/members/etc
  - [ ] Testar que dados específicos de DryWash não foram perdidos
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 70%
  - ✅ Sei copiar arquivos
  - ❌ DryWash pode ter customizações que conflitam
  - ❌ Dados específicos podem ser perdidos
- **Risco:** ALTO (produção!)
- **Notas:**
  - Primeira replicação é teste real
  - Se quebrar, recovery é complexo
  - Precisa de aprovação antes de começar

---

### 5.3 Replicar para BIGS
- **O quê:** Mesmo que 5.2, mas para BIGS
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 80%
  - ✅ Processo já testado em DryWash
  - ❌ BIGS pode ter peculiaridades diferentes
- **Risco:** MÉDIO
- **Notas:** Iteração 2 (melhorias descobertas em DryWash)

---

### 5.4 Replicar para Futebol Energia
- **O quê:** Mesmo que 5.3, mas para Futebol
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 85%
  - ✅ Processo consolidado em 2 replicações
  - ❌ Futebol pode ter módulo de scout que não conheço
- **Risco:** BAIXO-MÉDIO
- **Notas:** Processo está padronizado agora

---

### 5.5 Replicar para +1 projeto (nome TBD)
- **O quê:** Mesmo que 5.4
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 90%
  - ✅ Processo bem testado
  - ✅ Documentação pronta
- **Risco:** BAIXO
- **Notas:** Última replicação = menos surpresas

---

### 5.6 Testar replicação: Fazer mudança em AEGIS e propagar
- **O quê:** Adicionar 1 campo novo a admin (ex: "cpf")
  - [ ] Adicionar em AEGIS
  - [ ] Testar em AEGIS
  - [ ] Propagar para DryWash, BIGS, Futebol, +1
  - [ ] Testar que campo apareceu em TODOS
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 75%
  - ✅ Teste prático de replicação
  - ❌ Pode haver divergências entre projetos
- **Risco:** MÉDIO
- **Notas:** Valida que "ReplicaFormula" funciona

---

## FASE 6: DOCUMENTAÇÃO PARA DEVS (4h)

### 6.1 Criar guia "CRUD Pattern B - Passo a Passo"
- **O quê:** Tutorial prático para criar novo CRUD
- **Inclui:**
  - [ ] Copy/paste de template
  - [ ] Passo 1: Criar controller vazio
  - [ ] Passo 2: Implementar index()
  - [ ] Passo 3: Implementar create()
  - [ ] Passo 4: Implementar store() com validações
  - [ ] Passo 5: Implementar edit()
  - [ ] Passo 6: Implementar update()
  - [ ] Passo 7: Implementar destroy() com proteções
  - [ ] Passo 8: Criar views
  - [ ] Passo 9: Adicionar rotas
  - [ ] Passo 10: Testar completo
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 75%
  - ✅ Tenho conhecimento técnico
  - ❌ Pode ser muito denso ou fácil demais
- **Risco:** BAIXO
- **Notas:** Iteração com devs depois que liberado

---

### 6.2 Criar checklist "Validação CRUD Pattern B"
- **O quê:** Checklist que dev segue para garantir que CRUD está "pronto"
- **Inclui:**
  - [ ] Auth::require() na linha 1 de CADA método?
  - [ ] if (!Core::membersEnabled()) redirect() presente?
  - [ ] CSRF validation no store/update/destroy?
  - [ ] Sanitização em TODOS os inputs ($_POST, $_GET)?
  - [ ] UUID generation para IDs?
  - [ ] Validação de duplicata (se aplicável)?
  - [ ] Try/catch em métodos de escrita?
  - [ ] $\_SESSION['success']/['error'] + redirect?
  - [ ] Proteção de deleção (ex: "não pode deletar único admin")?
  - [ ] Views com $user variable?
  - [ ] Routes adicionadas em admin.php?
  - [ ] Testado CSRF bypass?
  - [ ] Testado SQL injection simples?
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 90%
  - ✅ Tenho referência em MemberController
  - ✅ Checklist é prático
- **Risco:** BAIXO
- **Notas:** Rápido de criar

---

### 6.3 Criar documento "Troubleshooting Pattern B"
- **O quê:** FAQ de problemas comuns
- **Inclui:**
  - [ ] "Por que recebo erro de Auth::require()?"
  - [ ] "Por que CSRF validation falha?"
  - [ ] "Como debugar query?"
  - [ ] "Como adicionar paginação?"
  - [ ] "Como fazer batch query?"
  - [ ] "Onde está o $user?"
  - [ ] "Como validar UUID?"
  - [ ] "Como fazer soft delete?"
- **Status:** ⏳ NÃO INICIADO
- **Confiança:** 70%
  - ✅ Antecipo problemas comuns
  - ❌ Pode haver surpresas que não prevejo
- **Risco:** BAIXO
- **Notas:** Documento vivo (adicionar problemas conforme surgem)

---

## FASE 7: IMPLEMENTAÇÃO DAS 10 MELHORIAS (20h) - OPCIONAL

### 7.1-7.10 Implementar as 10 melhorias de CRUD
*(Esses podem ser feitos DEPOIS da replicação, não antes)*

- 7.1 Transações (CRÍTICO) - Confiança: 70%
- 7.2 Soft Delete - Confiança: 80%
- 7.3 Validator Centralizado - Confiança: 75%
- 7.4 Batch Delete/Update - Confiança: 85%
- 7.5 Rate Limiting - Confiança: 80%
- 7.6 Auditoria Automática - Confiança: 60%
- 7.7 Helper Duplicação - Confiança: 90%
- 7.8 Mensagens Padronizadas - Confiança: 95%
- 7.9 Paginação Consistente - Confiança: 95%
- 7.10 Cache Invalidation - Confiança: 75%

**Status:** ⏳ BLOQUEADO (esperar Phase 5 terminar)

---

## 📊 RESUMO DE CONFIANÇA

| Fase | Tarefa | Confiança | Risco | Prioridade |
|------|--------|-----------|-------|-----------|
| 1.1 | Ler todos Pattern B controllers | 95% | BAIXO | 🔴 CRÍTICA |
| 1.2 | Documentar Template CRUD B | 85% | MÉDIO | 🔴 CRÍTICA |
| 1.3 | Verificar BaseController usage | 95% | BAIXO | 🟡 ALTA |
| 1.4 | Mapear funções BaseController | 90% | BAIXO | 🔴 CRÍTICA |
| 2.1 | Refatorar AdminController | 85% | MÉDIO | 🔴 CRÍTICA |
| 2.2 | Refatorar FontsController | 88% | BAIXO | 🟡 ALTA |
| 2.3 | Refatorar SettingsController | 82% | MÉDIO | 🟡 ALTA |
| 2.4 | Verificar views Pattern A | 70% | MÉDIO | 🟡 ALTA |
| 3.1 | Criar PADRÃO-CRUD-B.md | 80% | MÉDIO | 🟡 ALTA |
| 3.2 | Atualizar REGRAS.md | 99% | ZERO | 🟢 BAIXA |
| 3.3 | Atualizar PADROES-AEGIS.md | 95% | BAIXO | 🟢 BAIXA |
| 4.1 | Testar AdminController | 60% | ALTO | 🔴 CRÍTICA |
| 4.2 | Testar FontsController | 75% | MÉDIO | 🟡 ALTA |
| 4.3 | Testar SettingsController | 65% | ALTO | 🔴 CRÍTICA |
| 4.4 | Regressão Pattern B controllers | 90% | BAIXO | 🟡 ALTA |
| 4.5 | Testes de Segurança | 70% | MÉDIO | 🟡 ALTA |
| 5.1 | Preparar Package Replicável | 85% | MÉDIO | 🟡 ALTA |
| 5.2 | Replicar para DryWash | 70% | ALTO | 🔴 CRÍTICA |
| 5.3 | Replicar para BIGS | 80% | MÉDIO | 🔴 CRÍTICA |
| 5.4 | Replicar para Futebol | 85% | MÉDIO | 🔴 CRÍTICA |
| 5.5 | Replicar para +1 | 90% | BAIXO | 🟡 ALTA |
| 5.6 | Testar propagação mudanças | 75% | MÉDIO | 🟡 ALTA |
| 6.1 | Criar guia CRUD B | 75% | BAIXO | 🟢 BAIXA |
| 6.2 | Criar checklist validação | 90% | BAIXO | 🟢 BAIXA |
| 6.3 | Criar FAQ troubleshooting | 70% | BAIXO | 🟢 BAIXA |

---

## 🎯 TASKS BLOQUEANTES (FAZER PRIMEIRO)

**BLOQUEANTES (não posso fazer nada sem isso):**
1. ✅ 1.1 Ler todos Pattern B controllers
2. ✅ 1.4 Mapear funções BaseController
3. ✅ 1.2 Documentar Template CRUD B

**CRÍTICAS (se quebrar, quebra tudo):**
1. 2.1 Refatorar AdminController
2. 4.1 Testar AdminController
3. 5.2 Replicar DryWash

**ALTAS (importante, mas não quebra se atrasar):**
1. 2.2 Refatorar FontsController
2. 2.3 Refatorar SettingsController
3. 3.1 Criar PADRÃO-CRUD-B.md

---

## ⚠️ RISCOS PRINCIPAIS

### Risk 1: AdminController Refatoração (CRÍTICO)
- **Probabilidade:** 30% (pode quebrar)
- **Impacto:** 100% (sistema fica inacessível)
- **Mitigação:**
  - Backup completo antes
  - Testar em dev antes de produção
  - Ter rollback plan

### Risk 2: SettingsController Refatoração (CRÍTICO)
- **Probabilidade:** 25% (configurações complexas)
- **Impacto:** 80% (sistema pode ficar com config errada)
- **Mitigação:**
  - Entender SettingsController completamente antes
  - Testar cada integração (SMTP, GTM, FTP)
  - Backup de settings.json

### Risk 3: Replicação para DryWash (CRÍTICO)
- **Probabilidade:** 40% (primeira replicação)
- **Impacto:** 70% (produção pode quebrar)
- **Mitigação:**
  - Testar em staging primeiro
  - Procedimento documentado e aprovado
  - Rollback plan claro

### Risk 4: Testes de Segurança Incompletos (MÉDIO)
- **Probabilidade:** 50% (sem testes automatizados)
- **Impacto:** 60% (vulnerabilidades podem passar)
- **Mitigação:**
  - Testes manuais rigorosos
  - Code review antes de produção
  - Scanners de segurança se disponível

---

## 📋 PRÓXIMOS PASSOS

**Recomendação:**
1. ✅ Você aprova essa lista?
2. ✅ Quer que eu comece pela Fase 1 (análise)?
3. ✅ Quer que I liste gap de confiança que preciso preencher?

