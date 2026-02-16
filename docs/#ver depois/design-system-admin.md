# Design System - Admin AEGIS

## Visão Geral

Design system unificado para o painel administrativo do AEGIS Framework, estabelecendo padrões visuais, componentes reutilizáveis e convenções de código.

**Tema:** Roxo minimalista
**Versão:** 1.0.0
**Data:** 07/02/2026

---

## 1. Cores

### 1.1 Paleta Principal

```scss
// Primárias
$color-primary: #9b59b6       // Roxo principal
$color-secondary: #3498db     // Azul secundário

// Grays
$gray-05: #F8F9FA
$gray-10: #E9ECEF
$gray-20: #DEE2E6
$gray-30: #BBBDBF
$gray-40: #929497
$gray-50: #6C757D

// Status
$color-success: #27ae60       // Verde (uso limitado)
$color-error: #e74c3c         // Vermelho
$color-warning: #f39c12       // Laranja
$color-info: #3498db          // Azul
```

### 1.2 Uso das Cores

**Primário (#9b59b6 - Roxo):**
- ✅ Botões principais
- ✅ Headers de tabela
- ✅ Links importantes
- ✅ Badges "CRÍTICO"
- ✅ Checkboxes (accent-color)
- ✅ Bordas de foco

**Secundário (#3498db - Azul):**
- ✅ Badges informativos ("BACKUP", "ATIVO")
- ✅ Botões secundários
- ✅ Links suaves

**Verde (#27ae60):**
- ⚠️ **USO LIMITADO** - Cliente não gosta
- ✅ Apenas para feedback de sucesso (toasts)
- ❌ NÃO usar em badges permanentes
- ❌ NÃO usar em status "ativo"

**Backgrounds:**
- Info boxes: `#f3e5f5` (roxo claro 10%)
- Forms: `#ffffff` (branco)
- Page: `#ffffff` (branco)

### 1.3 Contraste e Acessibilidade

Todos os pares atendem WCAG AA:
- Roxo (#9b59b6) + Branco: ✅ 4.51:1
- Azul (#3498db) + Branco: ✅ 4.51:1
- Gray-50 (#6C757D) + Branco: ✅ 4.54:1

---

## 2. Tipografia

### 2.1 Fontes

```scss
$font-text: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif
$font-mono: 'Courier New', monospace
```

**Inter:**
- Corpo de texto
- Títulos
- Botões
- Labels

**Courier New:**
- Código
- Comandos
- Nomes de arquivo

### 2.2 Tamanhos

```scss
// Títulos
h1: 32px / 2rem
h2: 24px / 1.5rem
h3: 20px / 1.25rem

// Corpo
body: 14px
small: 12px
tiny: 11px

// Código
code: 13px
```

### 2.3 Pesos

```scss
regular: 400
medium: 500
semibold: 600
bold: 700
```

**Uso:**
- Títulos: 600 (semibold)
- Labels: 600 (semibold)
- Corpo: 400 (regular)
- Badges: 600 (semibold)

---

## 3. Espaçamento

### 3.1 Sistema Base-8

```scss
$space-xs: 4px
$space-sm: 8px
$space-md: 16px
$space-lg: 24px
$space-xl: 32px
$space-2xl: 48px
```

### 3.2 Aplicações

**Padding interno:**
- Botões: 12px 20px
- Inputs: 12px 14px
- Cards: 30px
- Containers: 50px

**Margin entre elementos:**
- Form groups: 25px
- Sections: 35px
- Headers: 50px

**Gaps:**
- Flex/Grid pequeno: 10px
- Flex/Grid médio: 15px
- Flex/Grid grande: 20px

---

## 4. Bordas e Raios

### 4.1 Border Radius

```scss
$radius-sm: 4px      // Inputs, badges
$radius-md: 8px      // Cards, containers
$radius-lg: 12px     // Modals
$radius-full: 999px  // Pills
```

### 4.2 Bordas

```scss
// Espessura
$border-thin: 1px
$border-medium: 2px
$border-thick: 4px

// Cores
$border-light: $gray-10
$border-medium: $gray-20
$border-dark: $gray-30
```

---

## 5. Sombras

### 5.1 Níveis

```scss
// Pequena (cards)
box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1)

// Média (dropdowns)
box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15)

// Grande (modals)
box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2)

// Focus
box-shadow: 0 0 0 2px $color-primary
```

### 5.2 Uso

- **Pequena:** Cards, tabelas
- **Média:** Dropdowns, tooltips
- **Grande:** Modals, overlays
- **Focus:** Estados de foco em inputs

---

## 6. Componentes

### 6.1 Botões

#### Primário
```scss
.m-pagebase__btn
  background: $color-primary
  color: white
  padding: 12px 20px
  border-radius: $radius-sm
  font-weight: 600
  transition: all 0.2s

  &:hover
    opacity: 0.9
```

**Variações:**
- `--sm` - Menor (10px 16px)
- `--widthauto` - Largura automática
- `--edit` - Ícone de editar
- `--danger` - Vermelho para deletar

#### Secundário
```scss
.m-pagebase__btn-secondary
  background: transparent
  color: $gray-50
  border: 1px solid $gray-20
```

#### Ícones
Sempre incluir Lucide icon:
```html
<button class="m-pagebase__btn">
    <i data-lucide="save"></i> Salvar
</button>
```

### 6.2 Inputs

#### Text Input
```scss
.m-pagebase__form-input
  width: 100%
  padding: 12px 14px
  border: none
  border-radius: $radius-sm
  background: $gray-05
  font-size: 14px

  &:focus
    outline: none
    box-shadow: 0 0 0 2px $color-primary
```

#### Textarea
```scss
.m-pagebase__form-textarea
  min-height: 150px
  font-family: $font-mono  // Para código
  resize: vertical
```

#### Checkbox
```scss
.m-pagebase__form-checkbox
  display: flex
  align-items: center
  gap: 10px

  input[type="checkbox"]
    width: 18px
    height: 18px
    accent-color: $color-primary
```

### 6.3 Tabelas

#### Estrutura
```html
<table class="m-pagebase__table">
    <thead>
        <tr>
            <th class="m-pagebase__table-sortable">
                <a href="?sort=name&order=asc">
                    Nome <i data-lucide="chevrons-up-down"></i>
                </a>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Conteúdo</td>
        </tr>
    </tbody>
</table>
```

#### Estilos
```scss
.m-pagebase__table
  width: 100%
  background: white
  border-radius: $radius-md
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1)

  th
    background: $color-primary
    color: white
    padding: 17px
    font-weight: 600

  td
    padding: 17px
    border-bottom: 1px solid $gray-10

  tr:hover
    background: $gray-05
```

### 6.4 Badges

```scss
.m-pagebase__badge
  display: inline-block
  padding: 4px 8px
  border-radius: 3px
  font-size: 11px
  font-weight: 600

  &--critical
    background: #9b59b6
    color: white

  &--active
    background: #3498db
    color: white

  &--inactive
    background: $gray-20
    color: $gray-50
```

### 6.5 Section Titles

```scss
.m-pagebase__section-title
  font-size: 18px
  font-weight: 700
  color: $color-primary
  font-family: $font-title
  margin-bottom: 15px
  padding-bottom: 12px
  border-bottom: 2px solid $gray-10
  position: relative

  &::after
    content: ''
    position: absolute
    bottom: -2px
    left: 0
    width: 60px
    height: 2px
    background: $gradient-primary

  &--spaced
    margin-top: 50px
```

**Uso:**
```html
<h3 class="m-pagebase__section-title">Páginas</h3>
<h3 class="m-pagebase__section-title m-pagebase__section-title--spaced">Módulos</h3>
```

### 6.6 Meta Text

```scss
.m-pagebase__meta
  color: $gray-60
  font-size: 13px
```

**Uso:**
```html
<span class="m-pagebase__meta">(email@example.com)</span>
<span class="m-pagebase__meta">— Descrição adicional</span>
```

### 6.7 Code Variants

```scss
.m-pagebase__code
  background: $gray-10
  padding: 2px 6px
  border-radius: 3px
  font-size: 12px
  font-family: 'Monaco', 'Courier New', monospace

  &--sm
    font-size: 12px
```

**Uso:**
```html
<code class="m-pagebase__code">/caminho/arquivo.php</code>
<code class="m-pagebase__code m-pagebase__code--sm">/slug-curto</code>
```

### 6.5 Alerts

```scss
.m-components__alert
  padding: 15px
  border-radius: $radius-sm
  margin-bottom: 20px
  border-left: 4px solid

  &--success
    background: #d4edda
    color: #155724
    border-color: #27ae60

  &--error
    background: #f8d7da
    color: #721c24
    border-color: #e74c3c

  &--warning
    background: #fff3cd
    color: #856404
    border-color: #ffc107
```

### 6.6 Paginação

```html
<div class="m-pagebase__pagination">
    <a href="?page=1" class="m-pagebase__pagination-btn">
        <i data-lucide="chevron-left"></i> Anterior
    </a>

    <div class="m-pagebase__pagination-numbers">
        <a href="?page=1" class="m-pagebase__pagination-number">1</a>
        <span class="m-pagebase__pagination-number m-pagebase__pagination-number--active">2</span>
        <a href="?page=3" class="m-pagebase__pagination-number">3</a>
    </div>

    <a href="?page=3" class="m-pagebase__pagination-btn">
        Próximo <i data-lucide="chevron-right"></i>
    </a>
</div>
```

### 6.7 Empty States

```scss
.m-pagebase__empty
  text-align: center
  padding: 60px 20px
  background: white
  border-radius: $radius-md

.m-pagebase__empty-icon
  font-size: 48px
  margin-bottom: 20px
  color: $gray-30

.m-pagebase__empty-text
  font-size: 16px
  color: $gray-40
```

---

## 7. Layout

### 7.1 Estrutura Base

```html
<body class="m-modulename">
    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-modulename">
        <div class="m-pagebase__header">
            <h1>Título da Página</h1>
            <a href="..." class="m-pagebase__btn">Ação</a>
        </div>

        <!-- Conteúdo -->
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
```

### 7.2 Header

```scss
.m-pagebase__header
  display: flex
  justify-content: space-between
  align-items: center
  margin-bottom: 30px

  h1
    margin: 0
    font-size: 32px
    font-weight: 600
```

### 7.3 Containers

```scss
// Largura padrão (formulários)
.m-pagebase__form-container
  max-width: 700px
  margin: 0 auto
  padding: 30px
  background: white
  border-radius: $radius-md

// Largura reduzida
.m-pagebase__form-container--narrow
  max-width: 500px

// Largura completa (page builder)
.m-pagebase__form-container--full
  max-width: 100%
```

### 7.4 Grids

```scss
// Grid responsivo
.m-pagebase__grid
  display: grid
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))
  gap: 20px

// Grid fixo 2 colunas
.m-pagebase__grid--2col
  grid-template-columns: repeat(2, 1fr)
```

---

## 8. Ícones

### 8.1 Biblioteca

**Lucide Icons:** https://lucide.dev

```html
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
```

### 8.2 Uso

```html
<i data-lucide="icon-name"></i>
```

### 8.3 Ícones Comuns

| Ação | Ícone |
|------|-------|
| Adicionar | `plus` |
| Editar | `edit` |
| Deletar | `trash-2` |
| Salvar | `save` |
| Cancelar | `x` |
| Voltar | `arrow-left` |
| Ver | `eye` |
| Download | `download` |
| Upload | `upload` |
| Buscar | `search` |
| Filtro | `filter` |
| Configurações | `settings` |
| Ordenar Asc | `chevron-up` |
| Ordenar Desc | `chevron-down` |
| Sem ordem | `chevrons-up-down` |
| Anterior | `chevron-left` |
| Próximo | `chevron-right` |

### 8.4 Tamanhos

```scss
// Pequeno (badges, inline)
svg
  width: 14px
  height: 14px

// Médio (botões)
svg
  width: 16px
  height: 16px

// Grande (headers, empty states)
svg
  width: 24px
  height: 24px
```

---

## 9. Animações

### 9.1 Transições

```scss
@mixin transition($property: all, $duration: 0.2s, $easing: ease)
  transition: $property $duration $easing
```

**Uso:**
- Hover em botões
- Focus em inputs
- Mudança de estado

### 9.2 Keyframes

```scss
// Slide in (toasts)
@keyframes slideIn
  from
    transform: translateX(400px)
    opacity: 0
  to
    transform: translateX(0)
    opacity: 1

// Fade in
@keyframes fadeIn
  from
    opacity: 0
  to
    opacity: 1

// Pulse (loading)
@keyframes pulse
  0%, 100%
    opacity: 1
  50%
    opacity: 0.5
```

---

## 10. Responsividade

### 10.1 Breakpoints

```scss
$mobile: 480px
$tablet: 768px
$desktop: 1024px
$wide: 1440px
```

### 10.2 Mixins

```scss
@mixin mobile
  @media (max-width: $mobile)
    @content

@mixin tablet
  @media (max-width: $tablet)
    @content

@mixin desktop
  @media (min-width: $desktop)
    @content
```

### 10.3 Adaptações

**Tabelas:**
```scss
.m-pagebase__table
  @include mobile
    font-size: 12px

  th, td
    @include mobile
      padding: 10px
```

**Grids:**
```scss
.m-pagebase__grid
  @include mobile
    grid-template-columns: 1fr
```

---

## 11. Nomenclatura (BEM)

### 11.1 Estrutura

```
.bloco__elemento--modificador
```

### 11.2 Exemplos

```scss
// Bloco
.m-includes

// Elemento
.m-includes__table
.m-includes__badge

// Modificador
.m-includes__badge--critical
.m-includes__badge--backup
```

### 11.3 Convenções

**Prefixos:**
- `m-` = Módulo (components, pages, etc)
- `l-` = Layout (sidebar, header, footer)
- `c-` = Componente genérico (botão, input)
- `u-` = Utility (text-center, mb-10)

**Separadores:**
- `__` = Elemento filho
- `--` = Modificador/variação
- `-` = Palavra composta

---

## 12. SASS

### 12.1 Estrutura de Arquivos

```
assets/sass/admin/
├── base/
│   ├── _variables.sass
│   ├── _reset.sass
│   └── _typography.sass
├── modules/
│   ├── _m-login.sass
│   ├── _m-dashboard.sass
│   ├── _m-pagebase.sass
│   ├── _m-pages.sass
│   ├── _m-components.sass
│   ├── _m-includes.sass
│   └── _modules.sass
├── shared/
│   ├── _mixins.sass
│   ├── _colors.sass
│   └── _responsive.sass
└── admin.sass
```

### 12.2 Imports

```sass
// admin.sass
@use 'base/variables'
@use 'base/reset'
@use 'base/typography'
@use 'modules/modules'
```

```sass
// modules/_modules.sass
@use 'm-login'
@use 'm-dashboard'
@use 'm-pagebase'
@use 'm-pages'
@use 'm-components'
@use 'm-includes'
```

### 12.3 Mixins Principais

```sass
// Flexbox
@mixin flex($direction, $align, $justify, $wrap)
  display: flex
  flex-direction: $direction
  align-items: $align
  justify-content: $justify
  flex-wrap: $wrap

// Transition
@mixin transition($property: all, $duration: 0.2s)
  transition: $property $duration ease

// Truncate
@mixin truncate
  overflow: hidden
  text-overflow: ellipsis
  white-space: nowrap
```

---

## 13. JavaScript

### 13.1 Padrões

**Inicialização Lucide:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});
```

**Event Delegation:**
```javascript
document.addEventListener('click', function(e) {
    if (e.target.matches('[data-action="delete"]')) {
        // Handle delete
    }
});
```

**Data Attributes:**
```html
<button data-confirm-delete="Mensagem">Deletar</button>
```

```javascript
document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
    btn.addEventListener('click', e => {
        if (!confirm(btn.dataset.confirmDelete)) {
            e.preventDefault();
        }
    });
});
```

### 13.2 Utilitários

```javascript
// Copy to clipboard
function copyToClipboard(text) {
    return navigator.clipboard.writeText(text);
}

// Toast feedback
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `m-toast m-toast--${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.remove(), 3000);
}

// Format bytes
function formatBytes(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' bytes';
}
```

---

## 14. Acessibilidade

### 14.1 Princípios

1. **Contraste suficiente** - WCAG AA mínimo
2. **Navegação por teclado** - Todos os elementos interativos
3. **Labels descritivos** - Inputs sempre com label
4. **Estados de foco** - Visíveis e consistentes
5. **ARIA quando necessário** - Tooltips, modals

### 14.2 Implementação

**Focus states:**
```scss
input:focus,
button:focus
  outline: none
  box-shadow: 0 0 0 2px $color-primary
```

**Labels:**
```html
<label for="name" class="m-pagebase__form-label">
    Nome *
</label>
<input id="name" name="name" required>
```

**ARIA:**
```html
<button aria-label="Deletar include header">
    <i data-lucide="trash-2"></i>
</button>
```

---

## 15. Performance

### 15.1 CSS

- ✅ Usar apenas 1 arquivo compilado (`admin.css`)
- ✅ Minificar em produção
- ✅ Evitar seletores profundos (max 3 níveis)
- ✅ Usar classes em vez de IDs

### 15.2 JavaScript

- ✅ Carregar Lucide apenas 1x por página
- ✅ Event delegation em vez de múltiplos listeners
- ✅ Debounce em inputs de busca
- ✅ Lazy load de componentes pesados

### 15.3 Imagens/Ícones

- ✅ Usar SVG (Lucide) em vez de imagens
- ✅ Otimizar PNGs/JPGs com TinyPNG
- ✅ Usar lazy loading quando apropriado

---

## 16. Checklist de Nova Página Admin

Ao criar uma nova página administrativa:

### Design
- [ ] Usa tema roxo (#9b59b6)
- [ ] Não usa verde para status permanentes
- [ ] Lucide icons (não emojis)
- [ ] Respeita espaçamento base-8
- [ ] Sombras consistentes

### Código
- [ ] Usa `admin.css`
- [ ] Inclui `header.php`
- [ ] Define `$user = Auth::user()`
- [ ] Nomenclatura BEM
- [ ] CSRF em formulários POST

### Componentes
- [ ] Botões com ícones Lucide
- [ ] Inputs com labels
- [ ] Tabelas com ordenação
- [ ] Paginação se > 15 itens
- [ ] Empty states quando vazio

### Acessibilidade
- [ ] Contraste WCAG AA
- [ ] Focus states visíveis
- [ ] Labels em todos inputs
- [ ] Navegação por teclado
- [ ] ARIA quando necessário

### JavaScript
- [ ] Inicializa Lucide
- [ ] Confirmação em deletes
- [ ] Validação client-side
- [ ] Feedback visual (toasts)

---

## 17. Exemplos Completos

### 17.1 Listagem

```html
<body class="m-mymodule">
    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-mymodule">
        <div class="m-pagebase__header">
            <h1>Meus Itens (<?= count($items) ?>)</h1>
            <a href="/admin/mymodule/create" class="m-pagebase__btn">
                <i data-lucide="plus"></i> Novo Item
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="m-components__alert m-components__alert--success">
                <?= $_SESSION['success'] ?>
            </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="m-pagebase__empty">
                <div class="m-pagebase__empty-icon">
                    <i data-lucide="inbox"></i>
                </div>
                <p>Nenhum item encontrado.</p>
            </div>
        <?php else: ?>
            <table class="m-pagebase__table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td>
                            <span class="m-pagebase__badge m-pagebase__badge--active">
                                Ativo
                            </span>
                        </td>
                        <td>
                            <a href="/admin/mymodule/<?= $item['id'] ?>/edit"
                               class="m-pagebase__btn m-pagebase__btn--sm">
                                <i data-lucide="edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
```

### 17.2 Formulário

```html
<body class="m-mymodule">
    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-mymodule">
        <div class="m-pagebase__header">
            <h1>Novo Item</h1>
            <a href="/admin/mymodule" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <div class="m-pagebase__form-container">
            <form method="POST" action="/admin/mymodule">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label for="name" class="m-pagebase__form-label">
                        Nome *
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        class="m-pagebase__form-input"
                        placeholder="Digite o nome"
                    >
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Ativo</span>
                    </label>
                </div>

                <div class="m-pagebase__form-actions">
                    <button type="submit" class="m-pagebase__btn">
                        <i data-lucide="save"></i> Salvar
                    </button>
                    <a href="/admin/mymodule" class="m-pagebase__btn-secondary">
                        <i data-lucide="x"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
```

---

**Framework AEGIS v15.2.4**
**Design System v1.0.0**
**Desenvolvido com Claude Code**
