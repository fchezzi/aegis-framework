# AEGIS Framework - Pasta /modules/

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-18

[← Voltar ao índice](aegis-estrutura.md)

---

## 📊 RESUMO

**Total:** 3 módulos instalados
**Arquivos:** 69 arquivos (21 blog + 37 palpites + 11 reports)
**Padrão:** module.json + routes.php + controllers/ + views/ + database/

---

## 🏗️ ARQUITETURA DE MÓDULOS

### Estrutura Padrão

```
modules/{nome}/
├── module.json           # Manifesto do módulo
├── routes.php            # Rotas do módulo
├── controllers/          # Controllers MVC
├── views/                # Templates (admin + public)
├── database/             # Schemas SQL
│   ├── mysql-schema.sql
│   ├── supabase-schema.sql
│   └── rollback.sql
├── CHANGELOG.md          # Histórico de versões (opcional)
└── install.md            # Guia de instalação (opcional)
```

### Padrão de Acesso Público

**Arquivo:** `.module-public-access-pattern.md` (245 linhas)

**Regras:**
1. **Prefixo obrigatório:** `/{nome_modulo}/` em TODAS as rotas públicas
2. **Exceção:** Apenas `blog` pode ter rotas sem prefixo (SEO)
3. **Controle:** Campo `"public"` no `module.json` (true/false)
4. **Helper:** `checkModuleAccess($pageSlug)` em cada rota pública
5. **Separação:** Módulos ≠ Páginas (não criar na tabela `pages`)

**Comportamento:**
- `ENABLE_MEMBERS = false` → Tudo público
- `"public": true` → Público (sem login)
- `"public": false` → Privado (exige `MemberAuth::require()`)

---

## 🟢 MÓDULO: blog

**Arquivos:** 21 (3 controllers + 7 views + 4 schemas + 4 docs)

### module.json

```json
{
  "name": "blog",
  "version": "1.1.0",
  "public": true,
  "public_url": "/blog",
  "dependencies": {
    "core": ["DB", "Security", "Auth", "Upload", "SimpleCache"],
    "tables": [
      "tbl_blog_relacionados",
      "tbl_blog_posts",
      "tbl_blog_categorias"
    ]
  }
}
```

### Features

- Editor WYSIWYG TinyMCE com upload inline
- Embed YouTube automático
- SEO-friendly URLs: `/:categoria/:post`
- Posts relacionados (manual + automático)
- CRUD completo
- Sistema de visualizações
- Cache estratégico
- Paginação (10/página)
- MEDIUMTEXT (16MB conteúdo)

### Estrutura

**Controllers:**
- `AdminCategoriasController.php` - CRUD categorias
- `AdminPostsController.php` - CRUD posts
- `PublicBlogController.php` - Frontend público

**Views Admin:**
- `posts/index.php` - Listagem posts
- `posts/create.php` - Criar post
- `posts/edit.php` - Editar post
- `categorias/index.php` - Listagem categorias
- `categorias/create.php` - Criar categoria
- `categorias/edit.php` - Editar categoria

**Views Public:**
- `index.php` - Listagem posts (home)
- `categoria.php` - Posts por categoria
- `post.php` - Detalhe do post

**Database:**
- `mysql-schema.sql` - 3 tabelas MySQL
- `supabase-schema.sql` - 3 tabelas Supabase
- `rollback.sql` - DROP tables
- `migration-mediumtext.sql` - Migração conteúdo

**Docs:**
- `CHANGELOG.md` - Histórico versões
- `install.md` - Guia instalação
- `EDITOR-GUIDE.md` - Guia editor TinyMCE

**Classificação:** 100% CORE-AEGIS (reutilizável)

---

## 🔵 MÓDULO: palpites

**Arquivos:** 37 (5 controllers + 16 views + 1 API + 6 schemas + 2 assets)

### module.json

```json
{
  "name": "palpites",
  "version": "1.0.0",
  "public": false,
  "public_url": "/palpites/exibicao-palpites",
  "tables": [
    "tbl_palpiteiros",
    "tbl_times",
    "tbl_jogos_palpites",
    "tbl_palpites",
    "tbl_resultados_reais",
    "cache_ranking_palpiteiros"
  ],
  "views": [
    "vw_pontuacao_palpites",
    "vw_ranking_palpiteiros"
  ]
}
```

### Features

- Sistema completo de palpites esportivos
- Ranking de palpiteiros
- Exibição ao vivo
- Resultados automáticos
- Cache de ranking
- 3 páginas públicas (exibição, resultados, ranking)

### Estrutura

**Controllers:**
- `PalpiteirosController.php` - CRUD palpiteiros
- `TimesController.php` - CRUD times
- `JogosController.php` - CRUD jogos
- `PalpitesController.php` - CRUD palpites
- `ResultadosController.php` - Processamento resultados

**API:**
- `api/updates.php` - Updates em tempo real

**Database:**
- `mysql-schema.sql` - 6 tabelas + 2 views MySQL
- `supabase-schema.sql` - 6 tabelas + 2 views Supabase
- `rollback.sql` - DROP tables/views
- `add-permission-pages.sql` - Permissões
- `cleanup-mysql.sql` - Limpeza legacy
- `performance-indexes.sql` - Índices otimização

**Assets:**
- `css/` - Estilos específicos
- `js/` - JavaScript exibição

**Classificação:** 80% APP-SPECIFIC / 20% CORE (lógica específica futebol)

---

## 🟡 MÓDULO: reports

**Arquivos:** 11 (2 controllers + 2 views + schemas)

### module.json

```json
{
  "name": "Reports",
  "version": "1.0.0",
  "public": false,
  "has_admin": true,
  "has_frontend": false,
  "dependencies": {
    "phpspreadsheet": "^1.0"
  },
  "tables": [
    "report_data_sources",
    "report_templates",
    "report_cells"
  ]
}
```

### Features

- Geração de relatórios Excel
- Fontes de dados customizáveis
- Templates reutilizáveis
- PhpSpreadsheet integration

### Estrutura

**Controllers:**
- `ReportTemplatesController.php` - CRUD templates
- `ReportDataSourcesController.php` - CRUD fontes

**Core:**
- `ReportDataSources.php` - Gerenciamento fontes (já documentado em core/)
- `ReportQueryBuilder.php` - Query builder seguro (já documentado em core/)

**Database:**
- Migrations para 3 tabelas

**Classificação:** 70% CORE / 30% APP-SPECIFIC

---

## 📂 ARQUIVOS GERAIS

### .gitkeep
Arquivo vazio para manter pasta no Git

### .module-public-access-pattern.md (245 linhas)
Documentação do padrão de acesso público/privado (detalhado acima)

### .module-public-access-pattern.html
Versão HTML da documentação

---

## 🎯 PADRÕES IDENTIFICADOS

### 1. Manifesto (module.json)
**Campos obrigatórios:**
- `name` - Identificador único
- `version` - Semver
- `public` - Controle acesso
- `dependencies` - Core classes + tabelas

**Campos opcionais:**
- `public_url` - Rota principal
- `public_pages` - Lista páginas públicas
- `menu` - Itens menu admin/public
- `configuration` - Settings do módulo
- `features` - Lista de features

### 2. Rotas (routes.php)
**Padrão:**
```php
// Helper obrigatório
function checkModuleAccess($pageSlug) { ... }

// Rotas públicas
Router::get('/modulo/rota', function() {
    checkModuleAccess('module_modulo');
    // controller code
});

// Rotas admin
Router::get('/admin/modulo', function() {
    Auth::require();
    // controller code
});
```

### 3. Database
**Multi-DB obrigatório:**
- `mysql-schema.sql` - Schema MySQL
- `supabase-schema.sql` - Schema Supabase (UUID)
- `rollback.sql` - DROP tables

**Extras:**
- Migrations para alterações incrementais
- Performance indexes
- Cleanup scripts

### 4. Controllers
**Padrão MVC:**
- Admin controllers estendem `BaseController`
- Public controllers podem ser standalone
- CRUD pattern: index, create, store, edit, update, destroy

### 5. Views
**Organização:**
- `admin/` - Templates admin panel
- `public/` - Templates frontend

---

## 📊 RESUMO GERAL

**Total analisado:** 69 arquivos em 3 módulos

**Por tipo:**
- **CORE-AEGIS:** blog (100%)
- **MISTO:** reports (70% CORE)
- **APP-SPECIFIC:** palpites (80% específico futebol)

**Arquitetura:**
- ✅ Modular (plug-and-play)
- ✅ Multi-DB (MySQL + Supabase)
- ✅ Public/Private control via JSON
- ✅ Self-contained (tudo dentro da pasta)
- ✅ Documentado (CHANGELOG, install.md)

**Qualidade média:** 9/10 (padrão consistente, bem documentado)

---

## 🔧 OPORTUNIDADES

### Melhorias Identificadas

1. **Padronizar docs:**
   - Todo módulo deveria ter CHANGELOG.md
   - Todo módulo deveria ter install.md

2. **Versionamento:**
   - Integrar com `Version.php` do core
   - Auto-bump ao modificar módulo

3. **Tests:**
   - Criar pasta `tests/` em cada módulo
   - Unit tests para controllers

4. **Assets:**
   - Mover CSS/JS do módulo para `/assets/modules/{nome}/`
   - Build process centralizado

5. **API:**
   - Padronizar APIs em `api/` dentro do módulo
   - Usar `ApiController` do core

---

## 📝 NOTA FINAL: 9/10

Sistema de módulos **maduro** e **bem arquitetado**, com padrões claros e documentação completa.
