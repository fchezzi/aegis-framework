# Changelog - Blog Module

## [1.1.0] - 2025-11-23/24

### Added
- **Editor WYSIWYG TinyMCE** em formulários de posts
- **Upload de imagens inline** (arrastar, colar, botão)
- **Embed de vídeos do YouTube** (automático via URL)
- **SEO-friendly URLs** `/:categoria-slug/:post-slug`
- **Padrão de acesso público/privado** (`checkModuleAccess`)
- **Validação de conflito de slugs** (categorias vs páginas AEGIS)
- Migration para `MEDIUMTEXT` (suporta até 16MB de conteúdo)
- Config: `"label"`, `"public": false`, `"public_url": "/blog"` no module.json

### Changed
- Campo `conteudo`: TEXT → MEDIUMTEXT (64KB → 16MB)
- Validação: 10.000 → 100.000 caracteres
- Ordem de rotas: específicas antes de genéricas
- AdminCategoriasController: valida slug contra `pages` table
- `checkModuleAccess()` libera acesso por padrão se página não existir

### Fixed
- Router order: `/blog` não dava 404 mais
- `ModuleManager::loadAllRoutes()` movido antes de `/:slug`
- Mensagens de erro em create/edit de categorias (SESSION vs GET)
- Conflitos de slug entre categorias e páginas do AEGIS

---

## [1.0.0] - 2025-11-23

### Added
- Sistema completo de blog com categorias
- Posts relacionados (manual + automático)
- Cache estratégico (listagens e detalhes)
- Admin CRUD completo
- Frontend público responsivo
- Visualizações automáticas
- Suporte MySQL + Supabase
- Instalação via `/admin/modules`
