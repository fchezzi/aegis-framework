# 📰 Módulo Blog - Instruções de Instalação

**Versão:** 1.0.0
**Autor:** AEGIS Framework
**Data:** 2025-11-23

---

## 🎯 **Descrição**

Sistema completo de blog com categorias, posts, posts relacionados híbridos e compartilhamento social.

---

## ✨ **Funcionalidades**

### Admin:
- ✅ CRUD completo de posts
- ✅ CRUD completo de categorias
- ✅ Posts relacionados híbridos (manual + automático)
- ✅ Upload de imagens (até 5MB)
- ✅ Editor de conteúdo HTML
- ✅ Sistema de visualizações
- ✅ Slugs únicos (SEO-friendly)

### Frontend:
- ✅ Listagem paginada (10 posts por página)
- ✅ Listagem por categoria
- ✅ Página individual de post
- ✅ Posts relacionados automáticos ou manuais
- ✅ Compartilhamento social (Facebook, Twitter, WhatsApp, LinkedIn, Copy link)
- ✅ Contador de visualizações
- ✅ Sidebar com categorias
- ✅ Design responsivo

---

## 📋 **Requisitos**

- AEGIS Framework v4.0.0+
- PHP 8.0+
- MySQL 5.7+ OU Supabase
- Extensões PHP: PDO, GD ou Imagick (para upload de imagens)
- Permissão de escrita em `storage/uploads/blog/`

---

## 🚀 **Instalação Automática (Recomendado)**

1. **Acesse o admin do AEGIS:**
   ```
   http://seusite.com/admin/modules
   ```

2. **Clique em "Instalar" no módulo Blog**

3. **O sistema irá automaticamente:**
   - Criar as 3 tabelas no banco de dados
   - Criar a categoria padrão "Notícias"
   - Registrar a página pública `/blog`
   - Adicionar ao menu
   - Criar pasta `storage/uploads/blog/`

4. **Pronto! Acesse:**
   - Admin: `http://seusite.com/admin/blog/posts`
   - Público: `http://seusite.com/blog`

---

## 🔧 **Instalação Manual (Avançado)**

### Passo 1: Copiar arquivos

Copie a pasta `blog/` completa para `modules/blog/`

### Passo 2: Executar SQL

**MySQL:**
```bash
mysql -u usuario -p database_name < modules/blog/database/mysql-schema.sql
```

**Supabase:**
Execute o conteúdo de `modules/blog/database/supabase-schema.sql` no SQL Editor do Supabase

### Passo 3: Criar pasta de uploads

```bash
mkdir -p storage/uploads/blog
chmod 755 storage/uploads/blog
```

### Passo 4: Registrar módulo

Execute no banco de dados:

```sql
-- Registrar página
INSERT INTO pages (id, slug, is_module_page, is_public, module_name, title, ativo)
VALUES (
    UUID(),
    'blog',
    1,
    1,
    'blog',
    'Blog',
    1
);

-- Adicionar ao menu
INSERT INTO menu (id, page_id, label, ordem, parent_id, ativo)
SELECT
    UUID(),
    id,
    'Blog',
    50,
    NULL,
    1
FROM pages
WHERE slug = 'blog';
```

---

## 🗄️ **Estrutura do Banco de Dados**

### Tabelas criadas:

1. **`tbl_blog_categorias`**
   - Categorias dos posts
   - Campos: id, nome, slug, descricao, ativo, ordem

2. **`tbl_blog_posts`**
   - Posts do blog
   - Campos: id, titulo, slug, introducao, conteudo, imagem, categoria_id, autor_id, visualizacoes, ativo

3. **`tbl_blog_relacionados`**
   - Posts relacionados manuais
   - Campos: id, post_id, post_relacionado_id, ordem

### Views criadas:

- **`vw_blog_posts_completo`** - Posts com categoria e autor
- **`vw_blog_categorias_stats`** - Categorias com contadores

---

## 📍 **Rotas do Módulo**

### Admin (autenticado):
- `GET /admin/blog/posts` - Listar posts
- `GET /admin/blog/posts/create` - Criar post
- `POST /admin/blog/posts/store` - Salvar post
- `GET /admin/blog/posts/{id}/edit` - Editar post
- `POST /admin/blog/posts/{id}/update` - Atualizar post
- `POST /admin/blog/posts/{id}/delete` - Deletar post
- `GET /admin/blog/categorias` - Listar categorias
- `GET /admin/blog/categorias/create` - Criar categoria
- ... (CRUD completo de categorias)

### Público:
- `GET /blog` - Listagem de posts
- `GET /blog/pagina/{page}` - Paginação
- `GET /blog/categoria/{slug}` - Posts por categoria
- `GET /blog/{slug}` - Post individual

---

## ⚙️ **Configurações**

Configurações podem ser editadas em `modules/blog/module.json`:

```json
"configuration": {
    "posts_per_page": 10,
    "max_image_size": 5242880,
    "allowed_image_types": ["jpg", "jpeg", "png", "webp"],
    "auto_related_posts_limit": 3,
    "enable_views_counter": true,
    "enable_social_share": true
}
```

---

## 🧪 **Testando a Instalação**

### 1. Criar categoria:
```
http://seusite.com/admin/blog/categorias/create
```

### 2. Criar post:
```
http://seusite.com/admin/blog/posts/create
```

### 3. Visualizar no frontend:
```
http://seusite.com/blog
```

### 4. Testar posts relacionados:
- Crie 3+ posts na mesma categoria
- Acesse um post individual
- Verifique se aparecem posts relacionados automáticos
- No admin, edite o post e adicione posts relacionados manuais

---

## 🔐 **Segurança**

O módulo implementa:

✅ **CSRF Protection** - Todos os formulários protegidos
✅ **SQL Injection Prevention** - Prepared statements
✅ **XSS Protection** - htmlspecialchars em outputs
✅ **File Upload Security** - 7 camadas de validação
✅ **Auth Protection** - Todos os endpoints admin protegidos
✅ **Input Sanitization** - Security::sanitize() em todos inputs
✅ **Unique Slugs** - Validação de slugs únicos
✅ **File Type Validation** - Apenas JPG, PNG, WEBP
✅ **File Size Validation** - Máximo 5MB

---

## 🚑 **Desinstalação**

### Via Admin (Recomendado):
```
http://seusite.com/admin/modules
```
Clique em "Desinstalar" no módulo Blog

### Manual:
```bash
# Execute o rollback SQL
mysql -u usuario -p database_name < modules/blog/database/rollback.sql

# Ou no Supabase, execute o conteúdo de rollback.sql

# Deletar arquivos
rm -rf modules/blog/
rm -rf storage/uploads/blog/
```

---

## 📊 **Performance**

- Cache implementado em listagens (5min TTL)
- Cache implementado em posts individuais (10min TTL)
- Índices otimizados para queries rápidas
- Lazy loading de imagens
- Paginação para evitar sobrecarga

---

## 🐛 **Troubleshooting**

### Erro: "Column 'imagem' not found"
**Solução:** Execute novamente o schema SQL

### Imagens não aparecem
**Solução:** Verifique permissões da pasta `storage/uploads/blog/`
```bash
chmod 755 storage/uploads/blog
```

### Posts relacionados não aparecem
**Solução:** Crie mais posts na mesma categoria OU adicione manualmente no admin

### Erro 404 nas rotas
**Solução:** Verifique se o módulo está instalado em `/admin/modules`

---

## 📚 **Documentação Adicional**

- **Planejamento:** `modules/blog/PLANEJAMENTO.md`
- **Schemas:** `modules/blog/database/*.sql`
- **Metadados:** `modules/blog/module.json`

---

## 📝 **Changelog**

### v1.0.0 (2025-11-23)
- ✨ Release inicial
- ✅ CRUD completo de posts e categorias
- ✅ Posts relacionados híbridos
- ✅ Compartilhamento social
- ✅ Sistema de visualizações
- ✅ Upload de imagens
- ✅ Paginação
- ✅ Cache implementado

---

## 🆘 **Suporte**

Para problemas ou dúvidas, consulte:
- Documentação AEGIS: `/docs/`
- Issues: GitHub do projeto

---

**Desenvolvido com ❤️ para AEGIS Framework v4.0.0**
