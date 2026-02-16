# AUDIT PLAN - DISSECAR AEGIS INTEIRO

**Data:** 2026-02-12  
**Status:** INICIADO  
**Escopo:** Replicabilidade máxima + Qualidade de código + Segurança  
**Formato:** Honesto, testes antes de afirmações, sem fabricações

---

## 📋 OBJETIVO GERAL

Transformar AEGIS no framework mais replicável possível para as 4 réplicas idênticas (DryWash, BIGS, Futebol, +1).

**O que significa "replicável":**
- ❌ Copiar e colar (péssimo)
- ❌ Encontrar hardcodes em 10 lugares
- ✅ Copiar, mudar variáveis de config, pronto
- ✅ Schema idêntico, dados diferentes
- ✅ Sem imports estranhos, sem paths hardcoded

---

## 🔍 FASE 1: MAPEAMENTO ESTRUTURAL

### 1.1 Estrutura de Diretórios (✅ COMPLETO)

```
/root
├── /admin                    → Painel administrativo
├── /api                      → Endpoints públicos
├── /assets                   → CSS, JS, imagens
├── /components               → Sistema de componentes
├── /core                     → Classes core (73 arquivos!)
├── /database                 → Schemas, migrations, adapters
├── /frontend                 → Páginas públicas + includes
├── /modules                  → Sistema modular (artigos, blog, palpites)
├── /public                   → Controllers públicos
├── /routes                   → 4 arquivos de roteamento
├── /storage                  → Cache, logs, uploads
├── /scripts                  → Deploy, utilidades
├── config.php                → Configuração global
├── index.php                 → Entry point
└── routes.php                → Router principal
```

**Observações iniciais:**
- Estrutura é clara e bem separada
- Bom uso de `/modules` para extensibilidade
- `/core` tem muitas classes - precisa auditar duplicação

---

## 🔐 FASE 2: AUDITORIA POR CAMADA

### 2.1 DATABASE LAYER (Core)

**Objetivo:** Garantir que replicação não quebra integridade de dados

**Arquivos a auditar:**
- `/database/adapters/DatabaseInterface.php` - Define contrato
- `/database/adapters/MySQLAdapter.php` - Implementação MySQL
- `/database/adapters/SupabaseAdapter.php` - Implementação Supabase
- `/database/schemas/mysql-schema.sql` - Schema principal
- `/database/schemas/supabase-schema.sql` - Schema Supabase
- `/database/migrations/` - 17+ migrations

**Checklist:**
- [ ] Ambos adapters implementam interface completa?
- [ ] Há divergências entre MySQL e Supabase que quebram replicação?
- [ ] Migrations sãoidênticas nos dois bancos?
- [ ] Foreign keys estão corretas em ambos?
- [ ] Há hardcodes de database_name em queries?

**Status:** PENDENTE

---

### 2.2 ROUTING LAYER (Core + Routes)

**Objetivo:** Garantir que routing não depende de paths hardcoded

**Arquivos a auditar:**
- `/routes/api.php` - API endpoints
- `/routes/public.php` - Public pages
- `/routes/admin.php` - Admin routes
- `/routes/catchall.php` - Fallback
- `routes.php` - Router principal
- `/core/Router.php` - Classe router

**Checklist:**
- [ ] Há hardcodes de `/admin/`?
- [ ] Há hardcodes de domínios ou IPs?
- [ ] Module routing é dinâmico?
- [ ] Controllers podem ser movidos sem quebrar rotas?

**Status:** PENDENTE

---

### 2.3 CONTROLLERS (17 arquivos)

**Pattern Atual:**
- Pattern A (3 controllers): AdminController, FontsController, SettingsController
  - Extendem BaseController
  - Usam `$this->requireAuth()`, `$this->db()`, `$this->render()`
  
- Pattern B (14 controllers): MemberController, GroupController, MenuController, etc
  - Estáticos/diretos
  - Usam `Auth::require()`, `DB::connect()`, `require`

**Checklist:**
- [ ] Qual pattern é mais replicável? (Já testado: B vence)
- [ ] Refatorar AdminController, FontsController, SettingsController?
- [ ] Cada controller tem proteções específicas do recurso?

**Status:** ANÁLISE COMPLETA (documento COMPARATIVO-PATTERNS-A-vs-B.md)

---

### 2.4 SECURITY LAYER (Core)

**Classes críticas:**
- `Security.php` - Sanitização, CSRF, UUID
- `Auth.php` - Autenticação
- `Permission.php` + `PermissionManager.php` - Sistema de permissões
- `RateLimit.php` + `RateLimiter.php` - Rate limiting

**Checklist:**
- [ ] Há validações inconsistentes entre controllers?
- [ ] Rate limiting está implementado em endpoints críticos?
- [ ] CSRF tokens são gerados e validados uniformemente?
- [ ] Há SQL injection vectors?
- [ ] Sessions são seguras em ambientes replicados?

**Status:** PENDENTE

---

### 2.5 MODULES SYSTEM

**Módulos atualmente instalados:**
1. artigos
2. blog
3. palpites

**Checklist:**
- [ ] Module discovery é dinâmico ou hardcoded?
- [ ] Cada módulo pode ser ligado/desligado?
- [ ] Schemas são isolados (não afetam core)?
- [ ] Routes de módulo não conflitam com core?

**Status:** PENDENTE

---

## 🎯 FASE 3: REPLICABILIDADE

### 3.1 Checklist de Replicação

Para cada projeto ser idêntico a AEGIS, preciso garantir:

**Configuração:**
- [ ] Há hardcodes em `config.php`?
- [ ] Há `define()` que dependem de ambiente?
- [ ] SASS variables estão centralizadas em `_variables.sass`?
- [ ] Fontes são carregadas via `assets/fonts.php` ou hardcoded?

**Database:**
- [ ] Schema é idêntico em MySQL e Supabase?
- [ ] Migrations são portáveis?
- [ ] Há stored procedures ou triggers que divergem?

**Uploads:**
- [ ] Path de uploads é configurável?
- [ ] Há `.htaccess` necessários em `/storage`?

**Frontend:**
- [ ] Há API calls para domínios hardcoded?
- [ ] AJAX usa relative URLs?

**Admin:**
- [ ] Settings são carregados de `settings.json`?
- [ ] Logo, cores, fontes são customizáveis?

**Status:** PENDENTE

---

## 📊 ANÁLISE RÁPIDA (Estimativa sem testes)

| Camada | Confiança | Problemas Esperados |
|--------|-----------|---------------------|
| Database | 70% | Migrações divergem entre MySQL/Supabase |
| Routing | 60% | Pode haver hardcodes em module routes |
| Controllers | 60% | Pattern A vs B precisa padronizar |
| Security | 50% | Validações inconsistentes |
| Modules | 40% | Module discovery pode quebrar em réplicas |
| Frontend | 65% | API paths podem estar hardcoded |
| Settings | 80% | Já é customizável via admin |

**Confiança GERAL:** ~60% | Razão: Não foram feitos testes reais

---

## 🧪 TESTING STRATEGY

Para elevar confiança para 95%+, preciso:

1. **Criar teste de replicação:**
   - [ ] Copiar AEGIS em pasta teste
   - [ ] Mudar config.php
   - [ ] Rodar setup.php
   - [ ] Verificar se funciona

2. **Testar cada camada:**
   - [ ] Database: Criar registro em MySQL, replicar em Supabase
   - [ ] Routing: Acessar admin/users, admin/groups, admin/members
   - [ ] Security: Tentar SQL injection, CSRF bypass
   - [ ] Modules: Ligar/desligar módulos, verificar integridade

3. **Testar replicação real:**
   - [ ] Copiar AEGIS para pasta DryWash
   - [ ] Mudar database, domínio, logo
   - [ ] Verificar se admin funciona 100%

---

## 📌 PRÓXIMOS PASSOS

**Hoje:**
- [ ] Audit Database Layer (2h)
- [ ] Audit Routing Layer (1h)
- [ ] Audit Security Layer (2h)

**Amanhã:**
- [ ] Testes de replicação
- [ ] Documentar problemas encontrados
- [ ] Propor soluções

---

## ⚠️ REGRAS DO AUDIT

1. ❌ Não crio documentos bonitos, crio análises reais
2. ❌ Não digo confiança > 65% sem testes
3. ✅ Testo TUDO antes de afirmar
4. ✅ Se encontro problema, documento com:
   - Arquivo afetado (com linha)
   - O que é o problema
   - Impacto na replicação
   - Solução proposta

---

**Última atualização:** 2026-02-12 00:00  
**Responsável:** Claude Code + Fábio Chezzi  
**Status:** INICIANDO FASE 2

