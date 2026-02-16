# Módulo Blog - AEGIS Framework

**Versão:** 1.0.0
**Data:** 2026-02-08
**Localização:** `modules/blog/`

---

## Visão Geral

Módulo completo de blog para AEGIS Framework com:
- Sistema de posts e categorias
- Editor TinyMCE para conteúdo rico
- Upload de imagens
- Posts relacionados
- Visualizações e métricas
- Sistema de slugs automáticos
- Categorização com contadores

---

## Estrutura de Arquivos

```
modules/blog/
├── controllers/
│   ├── AdminPostsController.php        # CRUD de posts (admin)
│   ├── AdminCategoriasController.php   # CRUD de categorias (admin)
│   └── PublicBlogController.php        # Visualização pública
├── views/
│   ├── admin/                          # Templates do admin
│   │   ├── posts/
│   │   └── categorias/
│   └── public/                         # Templates públicos
│       ├── index.php                   # Home do blog
│       ├── categoria.php               # Listagem por categoria
│       └── post.php                    # Post individual
├── database/
│   └── migrations/                     # SQL de instalação
├── routes.php                          # Rotas do módulo
├── module.json                         # Metadata do módulo
├── install.md                          # Guia de instalação
├── CHANGELOG.md                        # Histórico de versões
└── EDITOR-GUIDE.md                     # Guia do editor TinyMCE
```

---

## Banco de Dados

### Tabela: `tbl_blog_posts`

```sql
CREATE TABLE tbl_blog_posts (
  id VARCHAR(36) PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  introducao VARCHAR(350) NOT NULL,
  conteudo MEDIUMTEXT NOT NULL,
  imagem VARCHAR(500) NULL,
  categoria_id VARCHAR(36) NOT NULL,
  autor_id VARCHAR(36) NULL,
  visualizacoes INT DEFAULT 0,
  ativo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES tbl_blog_categorias(id) ON DELETE CASCADE
);
```

### Tabela: `tbl_blog_categorias`

```sql
CREATE TABLE tbl_blog_categorias (
  id VARCHAR(36) PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  descricao TEXT NULL,
  ativo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabela: `tbl_blog_posts_relacionados`

```sql
CREATE TABLE tbl_blog_posts_relacionados (
  post_id VARCHAR(36) NOT NULL,
  relacionado_id VARCHAR(36) NOT NULL,
  PRIMARY KEY (post_id, relacionado_id),
  FOREIGN KEY (post_id) REFERENCES tbl_blog_posts(id) ON DELETE CASCADE,
  FOREIGN KEY (relacionado_id) REFERENCES tbl_blog_posts(id) ON DELETE CASCADE
);
```

---

## Rotas

### Admin (autenticadas)

| Método | Rota | Controller | Ação |
|--------|------|------------|------|
| GET | `/admin/blog` | - | Redireciona para posts |
| GET | `/admin/blog/posts` | AdminPostsController | Listar posts |
| GET | `/admin/blog/posts/create` | AdminPostsController | Criar post |
| POST | `/admin/blog/posts/store` | AdminPostsController | Salvar post |
| GET | `/admin/blog/posts/:id/edit` | AdminPostsController | Editar post |
| POST | `/admin/blog/posts/:id/update` | AdminPostsController | Atualizar post |
| POST | `/admin/blog/posts/:id/delete` | AdminPostsController | Deletar post |
| POST | `/admin/blog/posts/:id/relacionados/add` | AdminPostsController | Adicionar relacionado (AJAX) |
| POST | `/admin/blog/posts/:id/relacionados/remove` | AdminPostsController | Remover relacionado (AJAX) |
| GET | `/admin/blog/posts/:id/relacionados/search` | AdminPostsController | Buscar relacionados (AJAX) |
| POST | `/admin/blog/upload-image` | AdminPostsController | Upload imagem TinyMCE |
| GET | `/admin/blog/categorias` | AdminCategoriasController | Listar categorias |
| GET | `/admin/blog/categorias/create` | AdminCategoriasController | Criar categoria |
| POST | `/admin/blog/categorias/store` | AdminCategoriasController | Salvar categoria |
| GET | `/admin/blog/categorias/:id/edit` | AdminCategoriasController | Editar categoria |
| POST | `/admin/blog/categorias/:id/update` | AdminCategoriasController | Atualizar categoria |
| POST | `/admin/blog/categorias/:id/delete` | AdminCategoriasController | Deletar categoria |

### Público

| Método | Rota | Controller | Ação |
|--------|------|------------|------|
| GET | `/blog` | PublicBlogController | Home do blog |
| GET | `/blog/pagina/:page` | PublicBlogController | Paginação |
| GET | `/blog/:categoria_slug` | PublicBlogController | Posts por categoria |
| GET | `/blog/:categoria_slug/pagina/:page` | PublicBlogController | Categoria com paginação |
| GET | `/blog/:categoria/:post` | PublicBlogController | Post individual |

**Nota:** Rota de posts individuais está em `routes.php` principal (~linha 942) com redirects 301 de URLs antigas (~linha 904).

---

## Upload de Imagens

### Configuração

**Destino:** `storage/uploads/blog/`

**Limites:**
- Tamanho máximo: 5MB
- Formatos: JPG, PNG, GIF, WebP
- Validação MIME type com fallback

### Classe Responsável

**`core/Upload.php`:**

```php
public static function image($file, $destination = '', $options = [])
```

**Uso no blog:**
```php
$upload = Upload::image($_FILES['file'], 'blog');
// Salva em: storage/uploads/blog/{filename}
```

### Caminho no Banco de Dados

**Formato salvo:** `/uploads/blog/post-{slug}-{timestamp}-{id}.{ext}`

**Exemplo:** `/uploads/blog/post-design-patterns-1770486449-5.jpg`

### Renderização no Frontend

**Templates:**
```php
// Em post.php, categoria.php, index.php
<?php if ($post['imagem']): ?>
    <img src="<?= url('storage' . $post['imagem']) ?>">
<?php endif; ?>
```

**Lógica:**
- Banco: `/uploads/blog/post-xxx.jpg`
- Concatena: `storage` + `/uploads/blog/post-xxx.jpg`
- Resultado: `storage/uploads/blog/post-xxx.jpg`
- URL final: `http://seusite.com/storage/uploads/blog/post-xxx.jpg`

---

## TinyMCE Editor

### Configuração

**API Key:** Definida em `_config.php`:
```php
define('TINYMCE_API_KEY', 'sua-chave-aqui');
```

### Upload de Imagens Inline

**Endpoint:** `/admin/blog/upload-image`

**Formato resposta:**
```json
{
  "location": "http://seusite.com/storage/uploads/blog/image-xxx.jpg"
}
```

**Imagens salvas em:** `storage/uploads/blog/`

### Plugins Habilitados

Verificar em `admin/views/posts/create.php` e `edit.php` a configuração completa do TinyMCE.

---

## Sistema de Categorias

### Funcionalidades

- Slug automático gerado do nome
- Contador de posts por categoria
- Ordenação por nome
- Ativo/Inativo

### Validação

- Nome único por categoria
- Slug único (gerado automaticamente)
- Não pode deletar categoria com posts

### Acesso Público

**URL:** `/blog/{categoria-slug}`

**Fallback:** Se página com slug não existir, `PageController` tenta categoria do blog (linha 123-142).

---

## Posts Relacionados

### Sistema

- Relação many-to-many entre posts
- Bidirecional (se A relacionado com B, então B com A)
- Interface AJAX para adicionar/remover
- Busca em tempo real

### Tabela

```sql
tbl_blog_posts_relacionados (post_id, relacionado_id)
```

### Uso

**Na edição de post:**
1. Buscar posts existentes (AJAX)
2. Adicionar relacionamento
3. Aparece automaticamente no post público

---

## Visualizações

### Sistema de Contagem

**Incremento:**
- Cada acesso ao post incrementa `visualizacoes`
- Contador visível no post público

**Query:**
```sql
UPDATE tbl_blog_posts
SET visualizacoes = visualizacoes + 1
WHERE id = ?
```

---

## CSS e Estilos

### Estado Atual

**Admin:** `assets/sass/admin/modules/_m-blog.sass` (apenas gerenciamento)

**Público:** CSS inline nos templates (PROBLEMA!)

**Templates com CSS inline:**
- `views/public/post.php` (linhas 5-25)
- `views/public/categoria.php`
- `views/public/index.php`

### Problema Identificado

❌ **CSS inline hardcoded**
- Não reutiliza estilos do site
- Difícil manter
- Não compila/minifica
- Repetição em cada template
- Sem variáveis, mixins

### Solução Planejada

Criar `assets/sass/frontend/modules/_m-blog.sass` para substituir CSS inline.

**Benefícios:**
- Estilos compilados e minificados
- Reutilização de variáveis do AEGIS
- Manutenção centralizada
- Integração com tema do site

---

## Troubleshooting

### Problema: Imagens não carregam (403 Forbidden)

**Causa:** Imagens fora de `storage/`

**Solução:**
```bash
# Mover imagens antigas para storage
mkdir -p storage/uploads/blog
mv uploads/blog/* storage/uploads/blog/
```

**Verificar paths nos templates:**
```php
// CORRETO
url('storage' . $post['imagem'])

// ERRADO (duplica path)
url('storage/uploads/' . $post['imagem'])
```

### Problema: Upload falha

**Verificar:**
1. `UPLOAD_PATH` em `_config.php`:
   ```php
   define('UPLOAD_PATH', STORAGE_PATH . 'uploads/');
   ```
2. Permissões da pasta:
   ```bash
   chmod 755 storage/uploads/blog
   ```
3. Limite PHP `upload_max_filesize` e `post_max_size`

### Problema: TinyMCE não carrega

**Verificar:**
1. API Key definida em `_config.php`
2. Conexão com internet (TinyMCE via CDN)
3. Console do navegador para erros JS

### Problema: Categoria não aparece

**Verificar:**
1. Categoria marcada como ativa (`ativo = 1`)
2. Existe pelo menos 1 post publicado na categoria
3. Post também está ativo

### Problema: Posts relacionados não aparecem

**Verificar:**
1. Relação salva na tabela `tbl_blog_posts_relacionados`
2. Posts relacionados estão ativos
3. Query no `PublicBlogController` inclui relacionados

---

## Migração de URLs Antigas

### Problema

URLs antigas conflitavam com outros módulos:
- Antiga: `/:categoria/:post`
- Nova: `/blog/:categoria/:post`

### Solução

**Redirects 301** em `routes.php` principal (~linha 904):
```php
// Redirecionar URLs antigas para novas
Router::get('/:categoria/:post', function($catSlug, $postSlug) {
    // Verificar se é post do blog
    // Se sim: 301 redirect para /blog/:categoria/:post
});
```

**Benefício:** SEO preservado, links antigos continuam funcionando.

---

## Module.json

### Configuração

```json
{
  "name": "Blog",
  "version": "1.0.0",
  "description": "Sistema de blog com posts, categorias e editor WYSIWYG",
  "public": true,
  "dependencies": []
}
```

**`public: true`:** Módulo acessível sem login (se `ENABLE_MEMBERS` desabilitado).

---

## Checklist de Instalação

- [ ] Executar migrations SQL (`database/migrations/`)
- [ ] Criar pasta `storage/uploads/blog/` com permissões 755
- [ ] Definir `TINYMCE_API_KEY` em `_config.php`
- [ ] Criar pelo menos 1 categoria
- [ ] Testar criação de post com imagem
- [ ] Testar visualização pública
- [ ] Verificar contador de visualizações
- [ ] Testar posts relacionados

---

## Melhorias Futuras

### CSS Frontend
- [ ] Criar `_m-blog.sass` para frontend
- [ ] Remover CSS inline dos templates
- [ ] Integrar com variáveis do site
- [ ] Adicionar responsividade

### Funcionalidades
- [ ] Sistema de tags
- [ ] Comentários
- [ ] Busca de posts
- [ ] RSS feed
- [ ] Sitemap específico do blog
- [ ] SEO dedicado para posts
- [ ] Imagens OG automáticas
- [ ] Breadcrumbs
- [ ] Compartilhamento social

### Performance
- [ ] Cache de listagens
- [ ] Lazy loading de imagens
- [ ] Paginação com AJAX
- [ ] Otimização de queries

### Admin
- [ ] Editor de código (markdown?)
- [ ] Preview antes de publicar
- [ ] Agendamento de posts
- [ ] Rascunhos
- [ ] Versionamento de conteúdo

---

## Arquivos Modificados Recentemente

**2026-02-08:**
- `views/public/post.php` - Corrigido path de imagem (`storage` + `$post['imagem']`)
- `views/public/categoria.php` - Corrigido path de imagem
- `views/public/index.php` - Corrigido path de imagem

**Motivo:** Imagens estavam em `uploads/blog/` mas código esperava em `storage/uploads/blog/`.

**Solução:** Movidas imagens + corrigido concatenação de path.

---

## Referências

- TinyMCE Docs: https://www.tiny.cloud/docs/
- Upload de Imagens: `core/Upload.php`
- Roteamento: `modules/blog/routes.php`
- Fallback de Categorias: `public/controllers/PageController.php:123-142`

---

## Changelog

### v1.0.0 (2026-02-08)
- ✅ Sistema de posts e categorias funcional
- ✅ TinyMCE integrado com upload
- ✅ Posts relacionados (AJAX)
- ✅ Sistema de visualizações
- ✅ Paginação
- ✅ Slugs automáticos
- ✅ Redirects 301 de URLs antigas
- ✅ Correção de paths de imagens
- ⚠️ CSS inline (precisa migrar para SASS)

---

**Documentação criada em:** 2026-02-08
**Responsável:** Fábio Chezzi
**Última atualização:** 2026-02-08
