# ÍNDICE DE CONSULTA RÁPIDA - AEGIS Docs

**Data:** 2026-02-12  
**Última atualização:** 2026-02-12  
**Versão:** 1.0.0

---

## 🎯 COMO USAR ESTE ÍNDICE

1. **Identifique o que você quer fazer** (abaixo)
2. **Encontre o documento correspondente**
3. **Claude lê automaticamente antes de começar**

---

## 📋 MATRIZ DE CONSULTA POR TAREFA

### 🏗️ ARQUITETURA & ESTRUTURA

| Tarefa | Arquivo(s) | Prioridade |
|--------|-----------|-----------|
| Entender estrutura geral | `aegis-estrutura.md` | ⭐⭐⭐ |
| Explorar pasta específica | `aegis-[pasta].md` (admin, api, core, routes, modules, etc) | ⭐⭐ |
| Status atual do projeto | `_state.md` | ⭐⭐ |
| Histórico de mudanças | `CHANGELOG-2026-*.md` | ⭐ |

---

### 💻 DESENVOLVIMENTO - CRUD ADMIN

| Tarefa | Arquivo(s) | O que ler |
|--------|-----------|----------|
| **Criar novo CRUD** | `PADROES-AEGIS-CONTROLLERS.md` | Padrão 6 métodos, checklist, erro comum |
| Entender controllers | `aegis-admin.md` | 15 controllers detalhados |
| Estrutura rotas | `aegis-routes.md` | Como organizar routes.php |
| Padrão RESTful | `PADROES-AEGIS-CONTROLLERS.md` | Endpoints e métodos |

**Fluxo:**
1. Ler `PADROES-AEGIS-CONTROLLERS.md` (estrutura base)
2. Ler `aegis-routes.md` (como registrar rotas)
3. Ler `SECURITY-PATTERNS.md` (validações/segurança)

---

### 📦 DESENVOLVIMENTO - MÓDULOS

| Tarefa | Arquivo(s) |
|--------|-----------|
| Criar novo módulo | `AEGIS-MODULOS-GUIA-COMPLETO.md` |
| Estrutura módulos | `aegis-modules.md` |
| Padrão module.json | `AEGIS-MODULOS-GUIA-COMPLETO.md` seção 3 |
| Database (MySQL vs Supabase) | `AEGIS-MODULOS-GUIA-COMPLETO.md` seção 4 |
| Rotas do módulo | `AEGIS-MODULOS-GUIA-COMPLETO.md` seção 5 |
| Controllers & Views | `AEGIS-MODULOS-GUIA-COMPLETO.md` seção 6-7 |

**Leitura rápida:** 3 arquivos = 2h
1. `AEGIS-MODULOS-GUIA-COMPLETO.md` (completo)
2. `aegis-modules.md` (referência rápida)

---

### 🛣️ ROTAS & NAVEGAÇÃO

| Tarefa | Arquivo(s) |
|--------|-----------|
| Sistema de rotas completo | `aegis-routes.md` |
| Padrão RESTful | `PADROES-AEGIS-CONTROLLERS.md` |
| Menu dinâmico | `sistema-includes.md` |
| Rotas públicas/privadas | `AEGIS-MODULOS-GUIA-COMPLETO.md` seção 8 |

---

### 🎨 DESIGN & FRONTEND

| Tarefa | Arquivo(s) |
|--------|-----------|
| Design system completo | `design-system-admin.md` |
| Includes (header/footer) | `sistema-includes.md` |
| Estrutura frontend | `aegis-frontend.md` |
| Componentes | `aegis-components.md` |

---

### 🔐 SEGURANÇA

| Tarefa | Arquivo(s) |
|--------|-----------|
| Padrões de segurança | `SECURITY-PATTERNS.md` |
| Auditoria de segurança | `SECURITY-RESOLUTION.md` |
| REGRAS críticas | `.claude/REGRAS.md` |

---

### 📊 BANCO DE DADOS

| Tarefa | Arquivo(s) |
|--------|-----------|
| Schemas MySQL vs Supabase | `aegis-database.md` |
| Estrutura database | `aegis-database.md` |
| Migrations | `AEGIS-MODULOS-GUIA-COMPLETO.md` seção 4 |

---

### ⚡ APIs

| Tarefa | Arquivo(s) |
|--------|-----------|
| APIs existentes | `aegis-api.md` |
| API REST versionada | `aegis-routes.md` seção 2 |
| Endpoints públicos | `aegis-api.md` |

---

### 🚀 DEPLOY & PERFORMANCE

| Tarefa | Arquivo(s) |
|--------|-----------|
| Deploy V1 (código) | `aegis-admin.md` seção "Deploy" |
| Deploy V2 (código+banco) | `DEPLOY-V2-AUDIT-CORRIGIDO.md` |
| PageSpeed Insights | `pagespeed-insights.md` |
| Performance | `COMO-USAR-PAGESPEED-COMPLETO.md` |

---

### 📝 DOCUMENTAÇÃO ESPECÍFICA

| Tópico | Arquivo(s) |
|--------|-----------|
| Google Tag Manager | `_state.md` v17.2.0 |
| Favicons customizáveis | `_state.md` v17.1.0 |
| Upload FTP | `_state.md` v17.3.0 |
| Módulo Blog | `modulo-blog.md` |
| Sistema SEO | `sistema-seo.md` |
| Padrões de código | `PADROES-AEGIS-CONTROLLERS.md` |

---

## 🗂️ ESTRUTURA DE ARQUIVOS

```
docs/
├── INDICE-CONSULTA-RAPIDA.md          ← Você está aqui
├── PADROES-AEGIS-CONTROLLERS.md        ← Padrão CRUD (NOVO)
├── REGRAS.md                           ← Regras críticas
│
├── ARQUITETURA (entender estrutura)
│   ├── aegis-estrutura.md              ← LEIA PRIMEIRO
│   ├── aegis-raiz.md
│   ├── aegis-admin.md
│   ├── aegis-api.md
│   ├── aegis-routes.md
│   ├── aegis-core*.md (3 arquivos)
│   ├── aegis-frontend.md
│   ├── aegis-database.md
│   ├── aegis-components.md
│   ├── aegis-assets.md
│   ├── aegis-modules.md
│   ├── aegis-storage.md
│   └── aegis-profile.md
│
├── DESENVOLVIMENTO
│   ├── AEGIS-MODULOS-GUIA-COMPLETO.md  ← Para criar módulos
│   ├── design-system-admin.md          ← UI/UX admin
│   ├── sistema-includes.md             ← Header/footer
│   └── sistema-seo.md
│
├── SEGURANÇA
│   ├── SECURITY-PATTERNS.md
│   └── SECURITY-RESOLUTION.md
│
├── DEPLOY & PERFORMANCE
│   ├── DEPLOY-V2-AUDIT-CORRIGIDO.md
│   ├── pagespeed-insights.md
│   ├── COMO-USAR-PAGESPEED-COMPLETO.md
│   └── UPLOAD-FIX-2026-01-31.md
│
├── CHANGELOG (histórico)
│   ├── CHANGELOG-2026-02-07.md
│   ├── CHANGELOG-2026-01-27.md
│   ├── CHANGELOG-2026-01-23.md
│   └── CHANGELOG-2026-01-18.md
│
├── REFERÊNCIA RÁPIDA
│   ├── _state.md                       ← Status atual
│   ├── README.md                       ← Overview PageSpeed
│   └── modulo-blog.md                  ← Módulo exemplo
```

---

## ✅ CHECKLIST: ONBOARDING CLAUDE

### Primeira Sessão
- [ ] Ler `aegis-estrutura.md`
- [ ] Ler `.claude/REGRAS.md`
- [ ] Ler `PADROES-AEGIS-CONTROLLERS.md`
- [ ] Ler `SECURITY-PATTERNS.md`

**Tempo:** 3-4 horas  
**Resultado:** Entender arquitetura + padrões core

### Segunda Sessão (antes de começar trabalho)
- [ ] Ler `aegis-routes.md`
- [ ] Ler `design-system-admin.md`
- [ ] (Se for criar módulo) Ler `AEGIS-MODULOS-GUIA-COMPLETO.md`

**Tempo:** 2-3 horas

---

## 🚀 FLUXOS RÁPIDOS POR TIPO DE TAREFA

### Criar CRUD Admin
```
1. PADROES-AEGIS-CONTROLLERS.md (copiar template)
2. aegis-routes.md (entender rotas)
3. SECURITY-PATTERNS.md (validações)
4. design-system-admin.md (UI)
```

### Criar Módulo
```
1. AEGIS-MODULOS-GUIA-COMPLETO.md (guia completo)
2. aegis-modules.md (referência)
3. aegis-routes.md (rotas do módulo)
```

### Implementar Feature Admin
```
1. aegis-admin.md (padrão controllers)
2. design-system-admin.md (layout)
3. SECURITY-PATTERNS.md (validações)
4. aegis-routes.md (rotas)
```

### Entender Erro/Bug
```
1. aegis-estrutura.md (contexto)
2. Arquivo específico (aegis-[area].md)
3. SECURITY-PATTERNS.md (se segurança)
```

---

## 📊 TAMANHOS APROXIMADOS

| Documento | Linhas | Tempo Leitura |
|-----------|--------|---------------|
| PADROES-AEGIS-CONTROLLERS.md | 400 | 20 min |
| AEGIS-MODULOS-GUIA-COMPLETO.md | 1000 | 60 min |
| aegis-estrutura.md | 300 | 20 min |
| aegis-routes.md | 500 | 30 min |
| design-system-admin.md | 700 | 40 min |
| SECURITY-PATTERNS.md | 400 | 25 min |
| aegis-admin.md | 300 | 20 min |
| SECURITY-RESOLUTION.md | 200 | 15 min |
| sistema-includes.md | 800 | 40 min |

---

## 🔄 PROTOCOLO DE ATUALIZAÇÃO

**Toda vez que eu aprender um padrão novo:**

1. Documento é criado/atualizado em `docs/`
2. Registrado em `_state.md` (seção NOVIDADES)
3. Entrada adicionada aqui (INDICE-CONSULTA-RAPIDA.md)
4. Próxima sessão: SEMPRE consultar este índice

---

## 💡 COMO CLAUDE USA ESTE ÍNDICE

```
Você: "Cria novo CRUD de clientes"

Claude:
1. Consulta INDICE-CONSULTA-RAPIDA.md
2. Encontra: Criar novo CRUD → PADROES-AEGIS-CONTROLLERS.md
3. Lê: PADROES-AEGIS-CONTROLLERS.md
4. Lê: aegis-routes.md
5. Lê: SECURITY-PATTERNS.md
6. Gera código 95% pronto
```

---

## 🎯 PRÓXIMOS DOCUMENTOS A CRIAR

- [ ] PADROES-AEGIS-ROUTES.md (padrões rotas)
- [ ] PADROES-AEGIS-SCHEMAS.md (MySQL vs Supabase)
- [ ] PADROES-AEGIS-UPLOAD.md (upload seguro)
- [ ] PADROES-AEGIS-API.md (APIs REST)
- [ ] PADROES-AEGIS-PERMISSIONS.md (permissões)

---

**Registrado por:** Claude Code + Fábio Chezzi  
**Data:** 2026-02-12  
**Versão:** 1.0.0
