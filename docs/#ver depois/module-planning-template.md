# 📋 Template de Planejamento de Módulo

> Use este template ANTES de começar a desenvolver qualquer módulo novo

---

## 1️⃣ **INFORMAÇÕES BÁSICAS**

### Nome do módulo:
`[nome-slug]` (ex: blog, produtos, eventos)

### Título:
`[Título Amigável]` (ex: Blog de Notícias, Catálogo de Produtos)

### Descrição:
_O que o módulo faz em 1-2 frases_

### Versão inicial:
`1.0.0`

### Autor:
_Seu nome/empresa_

---

## 2️⃣ **FUNCIONALIDADES**

### Admin (área restrita):
- [ ] Listar registros
- [ ] Criar novo registro
- [ ] Editar registro existente
- [ ] Deletar registro
- [ ] Buscar/filtrar
- [ ] Ordenar (drag & drop ou manual)
- [ ] Ativar/desativar
- [ ] Upload de arquivos/imagens
- [ ] Outras:

### Frontend (área pública):
- [ ] Listagem pública
- [ ] Página de detalhes
- [ ] Busca/filtro público
- [ ] Paginação
- [ ] Comentários
- [ ] Compartilhamento social
- [ ] Formulário de envio
- [ ] Outras:

### Permissões necessárias:
- [ ] Página pública (acessível sem login)
- [ ] Restrito a grupos específicos
- [ ] Restrito a members autenticados
- [ ] Apenas admin

---

## 3️⃣ **BANCO DE DADOS**

### Tabelas necessárias:

#### Tabela principal: `tbl_[nome]`

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `id` | UUID/VARCHAR(36) | ✅ | Primary key |
| `titulo` | VARCHAR(255) | ✅ | Título do registro |
| `slug` | VARCHAR(255) | ✅ | URL amigável (único) |
| `descricao` | TEXT | ❌ | Descrição breve |
| `conteudo` | LONGTEXT | ❌ | Conteúdo completo (HTML) |
| `imagem` | VARCHAR(500) | ❌ | Caminho da imagem |
| `ativo` | BOOLEAN | ✅ | Ativo/inativo |
| `ordem` | INT | ❌ | Ordem de exibição |
| `created_at` | TIMESTAMP | ✅ | Data de criação |
| `updated_at` | TIMESTAMP | ✅ | Data de atualização |

_Adicione outros campos conforme necessário_

#### Tabelas relacionadas (se houver):

**Exemplo: Tabela de categorias**
```
tbl_[nome]_categorias
- id
- nome
- slug
- ativo
```

**Exemplo: Tabela many-to-many**
```
tbl_[nome]_relacionamento
- id
- [nome]_id (FK)
- categoria_id (FK)
```

### Índices necessários:
- [ ] `idx_slug` (para busca rápida por URL)
- [ ] `idx_ativo` (para filtrar ativos/inativos)
- [ ] `idx_ordem` (para ordenação)
- [ ] `idx_created_at` (para ordenar por data)
- [ ] Outros:

### Views/Queries complexas:
_Liste queries SQL complexas que vão ser usadas frequentemente_

```sql
-- Exemplo: Listar com contagem de relacionamentos
SELECT
    p.*,
    COUNT(c.id) as total_comentarios
FROM tbl_posts p
LEFT JOIN tbl_comentarios c ON c.post_id = p.id
GROUP BY p.id
ORDER BY p.created_at DESC
```

---

## 4️⃣ **ROTAS**

### Admin:
- `GET /admin/[modulo]` → Listar
- `GET /admin/[modulo]/create` → Formulário criar
- `POST /admin/[modulo]/store` → Salvar novo
- `GET /admin/[modulo]/{id}/edit` → Formulário editar
- `POST /admin/[modulo]/{id}/update` → Salvar edição
- `POST /admin/[modulo]/{id}/delete` → Deletar
- Outras rotas:

### Frontend:
- `GET /[modulo]` → Listagem pública
- `GET /[modulo]/{slug}` → Página de detalhes
- Outras rotas:

---

## 5️⃣ **ARQUIVOS NECESSÁRIOS**

### Estrutura de pastas:

```
modules/[nome]/
├── module.json              ← Metadados do módulo
├── routes.php              ← Rotas do módulo
├── install.md              ← Instruções de instalação
│
├── controllers/            ← Lógica de negócio
│   ├── AdminController.php
│   └── PublicController.php
│
├── models/                 ← (opcional) Classes de modelo
│   └── [Nome]Model.php
│
├── views/
│   ├── admin/              ← Interfaces admin
│   │   ├── index.php       (listagem)
│   │   ├── create.php      (formulário criar)
│   │   └── edit.php        (formulário editar)
│   │
│   └── public/             ← Interfaces públicas
│       ├── index.php       (listagem)
│       └── detalhes.php    (página individual)
│
└── database/
    ├── mysql-schema.sql    ← Schema MySQL
    ├── supabase-schema.sql ← Schema Supabase
    └── rollback.sql        ← SQL para desinstalar
```

---

## 6️⃣ **VALIDAÇÕES E SEGURANÇA**

### Validações de entrada:
- [ ] Título: obrigatório, max 255 chars
- [ ] Slug: obrigatório, único, apenas letras/números/hífen
- [ ] Email: formato válido (se aplicável)
- [ ] URL: formato válido (se aplicável)
- [ ] Imagem: tipos permitidos (jpg, png, webp), tamanho máx
- [ ] Outras:

### Sanitização:
- [ ] `Security::sanitize()` em TODOS os inputs de texto
- [ ] `Security::validateCSRF()` em TODOS os POST/PUT/DELETE
- [ ] `htmlspecialchars()` ao exibir conteúdo HTML
- [ ] Upload de arquivos com validação de tipo/tamanho

### Rate Limiting:
- [ ] Formulário público: max 5 envios em 5min
- [ ] Outras ações que precisam rate limit:

---

## 7️⃣ **DEPENDÊNCIAS**

### Classes do AEGIS necessárias:
- [ ] `DB` (banco de dados)
- [ ] `Security` (sanitização, CSRF, UUID)
- [ ] `Auth` (se tem área admin)
- [ ] `MemberAuth` (se restringe por member)
- [ ] `Permission` (se usa grupos/permissões)
- [ ] `FileUpload` (se tem upload de arquivos)
- [ ] `SimpleCache` (para otimização)
- [ ] `RateLimit` (para proteção)

### Bibliotecas externas (se houver):
- [ ] Nenhuma (recomendado)
- [ ] Outras:

---

## 8️⃣ **ASSETS**

### CSS necessário:
- [ ] Estilo para listagem
- [ ] Estilo para formulários
- [ ] Estilo para cards/grid
- [ ] Responsivo mobile
- [ ] Dark mode (opcional)

### JavaScript necessário:
- [ ] Validação de formulários
- [ ] Preview de imagens
- [ ] Confirmação de exclusão
- [ ] Drag & drop para ordenar
- [ ] AJAX para ações rápidas
- [ ] Outros:

---

## 9️⃣ **PERFORMANCE**

### Cache:
- [ ] Listar registros (TTL: 5min)
- [ ] Detalhes de registro (TTL: 10min)
- [ ] Contadores/estatísticas (TTL: 1min)
- [ ] Outros:

### Otimizações:
- [ ] Eager loading de relacionamentos
- [ ] Paginação (20-50 itens por página)
- [ ] Lazy loading de imagens
- [ ] Índices no banco de dados
- [ ] Query optimization

---

## 🔟 **TESTES**

### Cenários de teste:

#### Funcionalidades:
- [ ] Criar registro novo
- [ ] Editar registro existente
- [ ] Deletar registro
- [ ] Buscar/filtrar
- [ ] Ordenar
- [ ] Upload de arquivo
- [ ] Ativar/desativar

#### Segurança:
- [ ] XSS: tentar injetar `<script>alert('xss')</script>`
- [ ] SQL Injection: tentar `'; DROP TABLE--`
- [ ] CSRF: tentar submeter form sem token
- [ ] Upload malicioso: tentar enviar PHP/executável

#### Performance:
- [ ] Listar 1000+ registros
- [ ] Busca com muitos resultados
- [ ] Upload de arquivo grande

#### Compatibilidade:
- [ ] MySQL SEM members
- [ ] MySQL COM members
- [ ] Supabase SEM members
- [ ] Supabase COM members

---

## 1️⃣1️⃣ **DOCUMENTAÇÃO**

### README do módulo deve incluir:
- [ ] Descrição do módulo
- [ ] Funcionalidades
- [ ] Requisitos (banco, PHP version)
- [ ] Instruções de instalação
- [ ] Instruções de uso (admin e frontend)
- [ ] Screenshots (opcional)
- [ ] Changelog
- [ ] Licença

---

## 1️⃣2️⃣ **ESTIMATIVA**

### Tempo estimado de desenvolvimento:
- Planejamento: _____ horas
- Database schemas: _____ horas
- Controllers: _____ horas
- Views (admin): _____ horas
- Views (frontend): _____ horas
- Testes: _____ horas
- Documentação: _____ horas
- **TOTAL:** _____ horas

### Complexidade:
- [ ] Simples (CRUD básico, sem relacionamentos)
- [ ] Média (CRUD + relacionamentos + upload)
- [ ] Alta (CRUD + relacionamentos + lógica complexa + integrações)

---

## ✅ **APROVAÇÃO**

- [ ] Planejamento revisado
- [ ] Estrutura de banco validada
- [ ] Rotas definidas
- [ ] Segurança mapeada
- [ ] Pronto para desenvolvimento!

---

**Data do planejamento:** ___________
**Desenvolvedor:** ___________
**Revisado por:** ___________

---

**Versão:** 1.0.0
**Criado em:** 2025-11-23
**Propósito:** Template para planejar módulos antes de desenvolver
