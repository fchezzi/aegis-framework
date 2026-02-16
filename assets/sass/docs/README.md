# Arquitetura SASS AEGIS Framework v15

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Estrutura de Pastas](#estrutura-de-pastas)
3. [Shared - Ferramentas Compartilhadas](#shared---ferramentas-compartilhadas)
4. [Frontend - Site Público](#frontend---site-público)
5. [Members - Dashboard de Membros](#members---dashboard-de-membros)
6. [Admin - Painel Administrativo](#admin---painel-administrativo)
7. [Convenções e Padrões](#convenções-e-padrões)
8. [Como Usar em Projetos](#como-usar-em-projetos)
9. [Compilação](#compilação)
10. [Migração da Estrutura Antiga](#migração-da-estrutura-antiga)

---

## Visão Geral

A arquitetura SASS do AEGIS Framework foi reestruturada para separar completamente os três contextos principais:

- **Frontend**: Site público (landing pages, institucional, blog)
- **Members**: Dashboard de membros autenticados
- **Admin**: Painel administrativo principal

### Princípios da Arquitetura

✅ **Separação Total**: Cada contexto é independente e compila seu próprio CSS
✅ **Zero Duplicação**: Ferramentas comuns estão em `shared/`
✅ **BEM Methodology**: Nomenclatura consistente para todos os componentes
✅ **Modular**: Fácil adicionar/remover componentes e módulos
✅ **Escalável**: Estrutura preparada para crescimento do projeto

### Performance

Cada página carrega **apenas 1 CSS**:
- Página pública → `frontend.css`
- Dashboard de membros → `members.css`
- Painel admin → `admin.css`

Não há CSS duplicado ou não utilizado.

---

## Estrutura de Pastas

```
sass/
├── shared/                    # Ferramentas compartilhadas (6 arquivos)
│   ├── _reset.sass           # Reset CSS universal
│   ├── _mixins.sass          # Funções e mixins
│   ├── _responsive.sass      # Breakpoints e media queries
│   ├── _fonts.sass           # Declarações @font-face
│   ├── _colors.sass          # Paleta de cores (26 tons de cinza)
│   └── _classes.sass         # Classes utilitárias (u-*)
│
├── frontend/                  # Site público
│   ├── frontend.sass         # → compila para css/frontend.css
│   ├── base/
│   │   ├── _b-variables.sass # Variáveis do frontend
│   │   └── _base.sass        # Agregador base
│   ├── components/
│   │   ├── _components.sass  # Agregador de componentes
│   │   └── _model.sass       # Template para novos componentes
│   └── modules/
│       ├── _modules.sass     # Agregador de módulos
│       └── _model.sass       # Template para novos módulos
│
├── members/                   # Dashboard de membros
│   ├── members.sass          # → compila para css/members.css
│   ├── base/
│   │   ├── _variables.sass   # Variáveis do dashboard ($bgdash*)
│   │   └── _base.sass        # Agregador base
│   ├── layout/
│   │   ├── _l-dashboard.sass # Layout principal do dashboard
│   │   ├── _l-breadcrumb.sass
│   │   ├── _l-sidebar.sass
│   │   ├── _l-content.sass
│   │   └── _layout.sass      # Agregador de layouts
│   ├── components/
│   │   ├── _c-cards.sass     # 11 componentes existentes
│   │   ├── _c-filtros.sass
│   │   ├── _c-graficos.sass
│   │   ├── _c-tabelas.sass
│   │   ├── ... (mais 7)
│   │   ├── _components.sass  # Agregador
│   │   └── _model.sass       # Template
│   └── modules/
│       ├── _m-breadcrumb.sass # 7 módulos existentes
│       ├── _m-header.sass
│       ├── _m-sidebar.sass
│       ├── ... (mais 4)
│       ├── _modules.sass     # Agregador
│       └── _model.sass       # Template
│
└── admin/                     # Painel administrativo
    ├── admin.sass            # → compila para css/admin.css
    ├── base/
    │   ├── _variables.sass   # Variáveis do admin
    │   └── _base.sass        # Agregador base
    ├── components/           # (vazio - a criar conforme necessário)
    └── layout/               # (vazio - a criar conforme necessário)
```

---

## Shared - Ferramentas Compartilhadas

### 📁 `shared/_reset.sass`

Reset CSS universal aplicado a todos os contextos.

```sass
// Remove margens, paddings, outlines
html, body, h1, h2, h3, p, ul, li...
```

### 📁 `shared/_mixins.sass`

Funções de cores modernas (SASS nativo) e mixins reutilizáveis.

**Funções de cor:**
```sass
darken-color($color, $amount)    // Escurece cor
lighten-color($color, $amount)   // Clareia cor
saturate-color($color, $amount)  // Aumenta saturação
desaturate-color($color, $amount) // Diminui saturação
fade($color, $opacity)           // Adiciona transparência
```

**Mixins:**
```sass
@include flex($dir, $ai, $jc, $fw)  // Flex layout
@include placeholder { }            // Estilizar placeholders
@include center-absolute            // Centralizar absoluto
```

### 📁 `shared/_responsive.sass`

Breakpoints e mixins responsivos.

**Breakpoints:**
```sass
$phones: 320px
$phonesWide: 480px
$phtablet: 600px
$tabletSmall: 768px
$tablet: 900px
$tabletWide: 1024px
$desktop: 1200px
$desktopWide: 1440px
$desktopMegaWide: 1920px
```

**Uso:**
```sass
@include responsive-min($tablet)
  // Estilos para tablet e acima

@include responsive-max($tablet)
  // Estilos para abaixo de tablet
```

### 📁 `shared/_fonts.sass`

Declarações @font-face compartilhadas.

```sass
@font-face
  font-family: 'inter'
  src: url("../fonts/inter.ttf")
  font-display: swap

@font-face
  font-family: 'roboto'
  src: url("../fonts/roboto.ttf")
  font-display: swap
```

### 📁 `shared/_colors.sass`

Paleta de cores do sistema.

**26 tons de cinza:**
```sass
$gray-01: #FCFCFC  // Mais claro
$gray-10: #E5E6E7
$gray-50: #929497  // Meio tom
$gray-85: #4C4C4E
$gray-95: #3F3F41  // Mais escuro
```

**Cores auxiliares:**
```sass
$color-success: #27ae60  // Verde
$color-error: #e74c3c    // Vermelho
$color-warning: #f39c12  // Amarelo
$color-info: #3498db     // Azul
```

### 📁 `shared/_classes.sass`

Classes utilitárias responsivas.

```sass
.u-displaynone       // display: none
.u-displayblock      // display: block

// Controle de visibilidade responsivo
.u-no-up-tablet      // Esconde em tablet e acima
.u-no-down-desktop   // Esconde abaixo de desktop
```

---

## Frontend - Site Público

### Propósito

CSS para o site público (landing pages, institucional, blog, páginas de marketing).

### Arquivo Principal

**`frontend/frontend.sass`**
```sass
@use 'base/base'
@use 'components/components'
@use 'modules/modules'
```

Compila para: `assets/css/frontend.css`

### Base

**`frontend/base/_b-variables.sass`**

Variáveis específicas do frontend:
```sass
// Cores da marca (editáveis no admin)
$color-main: #6c10b8
$color-second: #C41C1C
$color-third: #A39D8F
$color-four: #A39D8F
$color-five: #A39D8F

// Fontes
$font-title: 'roboto', sans-serif
$font-text: 'inter', sans-serif
```

### Components

**Uso:** Componentes reutilizáveis em várias páginas.

**Exemplos:**
- `_c-button.sass` - Botões
- `_c-card.sass` - Cards
- `_c-modal.sass` - Modais
- `_c-form.sass` - Formulários

**Criar novo componente:**
```bash
# 1. Copiar template
cp frontend/components/_model.sass frontend/components/_c-button.sass

# 2. Editar _c-button.sass com BEM
.c-button
  padding: 12px 24px

  &__icon
    margin-right: 8px

  &--primary
    background: $color-main

# 3. Adicionar em _components.sass
@use 'c-button'
```

### Modules

**Uso:** Módulos específicos de páginas/seções.

**Exemplos:**
- `_m-hero.sass` - Seção hero
- `_m-newsletter.sass` - Newsletter
- `_m-galeria.sass` - Galeria
- `_m-footer.sass` - Rodapé

**Criar novo módulo:**
```bash
# 1. Copiar template
cp frontend/modules/_model.sass frontend/modules/_m-hero.sass

# 2. Editar _m-hero.sass
.m-hero
  height: 100vh
  background: $color-main

  &__title
    font-size: 48px

# 3. Adicionar em _modules.sass
@use 'm-hero'
```

---

## Members - Dashboard de Membros

### Propósito

CSS para o dashboard de usuários autenticados (área de membros).

### Arquivo Principal

**`members/members.sass`**
```sass
@use '../shared/reset'
@use '../shared/mixins' as *
@use '../shared/responsive' as *
@use '../shared/fonts'
@use '../shared/colors'
@use '../shared/classes'

@use 'base/base'
@use 'layout/layout'
@use 'modules/modules'
@use 'components/components'
```

Compila para: `assets/css/members.css`

**⚠️ Nota:** Arquivo está comentado. Descomentar quando implementar dashboard de membros no projeto.

### Base

**`members/base/_variables.sass`**

Variáveis do dashboard (editáveis via admin):
```sass
$bgdashheader: linear-gradient(135deg, #4C4C4E 0%, #000 100%)
$bgdashheaderdark: linear-gradient(45deg, #FFFFFF 0%, #E5E6E7 100%)
$bgdashmain: #4C4C4E
$bgdashmaindark: #FFFFFF
$bgdashbread: #E10909
$bgdashbreaddark: #F0F0F0
$bgdashaside: #E10909
$bgdashlogo: #000000
$bgdashlogodark: #FFFFFF
```

### Layout

Grid fixo do dashboard:

**`_l-dashboard.sass`** - Header (70px) + Breadcrumb (50px) + Main (restante)
**`_l-breadcrumb.sass`** - Breadcrumb fixo com toggle
**`_l-sidebar.sass`** - Sidebar lateral (255px, collapsible)
**`_l-content.sass`** - Área de conteúdo principal

### Components (11 existentes)

**Cards e Widgets:**
- `_c-cards.sass` - Cards de métricas
- `_c-widgets.sass` - Widgets do dashboard

**Tabelas e Gráficos:**
- `_c-tabelas.sass` - Tabelas de dados
- `_c-graficos.sass` - Gráficos e charts

**Filtros:**
- `_c-filtros.sass` - Filtros gerais
- `_c-filter-mesano.sass` - Filtro mês/ano

**Outros:**
- `_c-ultimaatualizacao.sass` - Widget de última atualização
- `_c-html-livre.sass` - Container HTML livre
- `_c-spacer.sass` - Espaçadores
- `_c-imagelink.sass` - Links com imagem

**Template:** `_model.sass`

### Modules (7 existentes)

**Dashboard:**
- `_m-breadcrumb.sass` - Breadcrumb com toggle
- `_m-header.sass` - Header do dashboard
- `_m-sidebar.sass` - Sidebar navigation
- `_m-home.sass` - Dashboard home

**Funcionalidades:**
- `_m-downloads.sass` - Área de downloads
- `_m-profile.sass` - Perfil do usuário
- `_m-page-builder.sass` - Page builder

**Template:** `_model.sass`

---

## Admin - Painel Administrativo

### Propósito

CSS para o painel administrativo principal do AEGIS (gerenciamento completo).

### Arquivo Principal

**`admin/admin.sass`**
```sass
@use '../shared/reset'
@use '../shared/mixins' as *
@use '../shared/responsive' as *
@use '../shared/fonts'
@use '../shared/colors'
@use '../shared/classes'

@use 'base/base'
@use 'components/components'
@use 'layout/layout'
```

Compila para: `assets/css/admin.css`

### Base

**`admin/base/_variables.sass`**

Variáveis específicas do admin (a serem definidas conforme necessidade).

### Components e Layout

Pastas vazias, prontas para receber componentes conforme o admin for desenvolvido.

**Próximos passos:**
- Criar componentes BEM para admin (botões, forms, tabelas, alerts)
- Migrar inline CSS atual para SASS + BEM
- Criar layouts específicos do admin

---

## Convenções e Padrões

### Metodologia BEM

**Block Element Modifier** - Nomenclatura consistente.

```sass
// BLOCK - Componente principal
.c-card
  display: block

// ELEMENT - Parte do componente (__)
.c-card__title
  font-size: 18px

.c-card__content
  padding: 20px

// MODIFIER - Variação do componente (--)
.c-card--featured
  border: 2px solid $color-main

.c-card--dark
  background: $gray-85
```

### Prefixos

**`c-`** = Component (reutilizável)
```sass
.c-button
.c-card
.c-modal
```

**`m-`** = Module (específico de página)
```sass
.m-hero
.m-newsletter
.m-footer
```

**`l-`** = Layout (estrutura)
```sass
.l-header
.l-sidebar
.l-content
```

**`u-`** = Utility (classe auxiliar)
```sass
.u-displaynone
.u-no-up-tablet
```

### Estrutura de Arquivo SASS

```sass
// 1. COMENTÁRIO DESCRITIVO
// Nome do Componente - Descrição

// 2. IMPORTS
@use 'sass:color'
@use '../base/variables' as *
@use '../../shared/mixins' as *
@use '../../shared/responsive' as *
@use '../../shared/colors' as *

// 3. VARIÁVEIS LOCAIS (se necessário)
$component-spacing: 20px

// 4. ESTILOS BEM
.c-nome-componente
  propriedade: valor

  // Elements
  &__elemento
    propriedade: valor

  // Modifiers
  &--modifier
    propriedade: valor

  // Responsive
  @include responsive-min($tablet)
    propriedade: valor
```

### Imports - as * vs. explícito

**Com `as *`** (acesso direto):
```sass
@use '../../shared/mixins' as *
@use '../../shared/colors' as *

.component
  color: $gray-50              // Direto
  @include flex(row, center)   // Direto
```

**Sem `as *`** (namespace):
```sass
@use '../../shared/mixins'
@use '../../shared/colors'

.component
  color: colors.$gray-50           // Com namespace
  @include mixins.flex(row, center) // Com namespace
```

**Quando usar cada um:**
- `as *` → Quando usar muito (mixins, responsive, colors)
- Sem `as *` → Quando usar pouco ou evitar conflitos

---

## Como Usar em Projetos

### 1. Novo Projeto AEGIS

A estrutura já vem pronta. Apenas adicione componentes conforme necessário.

```bash
# Frontend
cp frontend/components/_model.sass frontend/components/_c-hero.sass
cp frontend/modules/_model.sass frontend/modules/_m-newsletter.sass

# Adicionar nos agregadores
# frontend/components/_components.sass:
@use 'c-hero'

# frontend/modules/_modules.sass:
@use 'm-newsletter'
```

### 2. Adicionar Component ao Frontend

```bash
# 1. Criar arquivo
cp frontend/components/_model.sass frontend/components/_c-button.sass

# 2. Editar _c-button.sass
```

```sass
// Button Component
// Botões reutilizáveis do site

@use 'sass:color'
@use '../base/b-variables' as *
@use '../../shared/mixins' as *
@use '../../shared/responsive' as *
@use '../../shared/colors' as *

.c-button
  padding: 12px 24px
  border-radius: 4px
  font-family: $font-text
  cursor: pointer
  transition: all 0.3s ease

  &__icon
    margin-right: 8px
    width: 20px
    height: 20px

  &--primary
    background: $color-main
    color: white

    &:hover
      background: darken-color($color-main, 10%)

  &--secondary
    background: $gray-10
    color: $gray-85

  &--large
    padding: 16px 32px
    font-size: 18px

  @include responsive-max($tablet)
    width: 100%
    padding: 14px
```

```bash
# 3. Adicionar em _components.sass
echo "@use 'c-button'" >> frontend/components/_components.sass

# 4. Usar no HTML
```

```html
<button class="c-button c-button--primary">
  <span class="c-button__icon">→</span>
  Clique aqui
</button>

<button class="c-button c-button--secondary c-button--large">
  Botão grande
</button>
```

### 3. Adicionar Component ao Members Dashboard

```bash
# 1. Criar arquivo
cp members/components/_model.sass members/components/_c-notification.sass

# 2. Editar _c-notification.sass
```

```sass
// Notification Component Members
// Notificações do dashboard

@use 'sass:color'
@use '../base/variables' as *
@use '../../shared/mixins' as *
@use '../../shared/responsive' as *
@use '../../shared/colors' as *

.c-notification
  padding: 16px
  border-radius: 4px
  display: flex
  align-items: center
  gap: 12px

  &__icon
    width: 24px
    height: 24px
    flex-shrink: 0

  &__content
    flex: 1

  &__title
    font-weight: 600
    margin-bottom: 4px

  &__message
    color: $gray-60
    font-size: 14px

  &--success
    background: lighten-color($color-success, 90%)
    border-left: 4px solid $color-success

  &--error
    background: lighten-color($color-error, 90%)
    border-left: 4px solid $color-error

  &--warning
    background: lighten-color($color-warning, 90%)
    border-left: 4px solid $color-warning
```

```bash
# 3. Adicionar em _components.sass
echo "@use 'c-notification'" >> members/components/_components.sass

# 4. Descomentar members.sass se necessário
```

### 4. Ativar Dashboard de Members

Quando implementar dashboard de membros no projeto:

```bash
# Editar members/members.sass
# Descomentar todas as linhas
```

```sass
// Shared (ferramentas compartilhadas)
@use '../shared/reset'
@use '../shared/mixins' as *
@use '../shared/responsive' as *
@use '../shared/fonts'
@use '../shared/colors'
@use '../shared/classes'

// Base do members
@use 'base/base'

// Layout do members
@use 'layout/layout'

// Modules do members
@use 'modules/modules'

// Components do members (dashboard)
@use 'components/components'
```

---

## Compilação

### CodeKit (Recomendado)

O AEGIS usa **CodeKit** para compilar SASS.

**Configuração:**

1. Adicionar pasta `assets/sass` no CodeKit
2. Configurar 3 compiladores principais:

| Arquivo Fonte | Compila Para |
|---------------|--------------|
| `frontend/frontend.sass` | `assets/css/frontend.css` |
| `members/members.sass` | `assets/css/members.css` |
| `admin/admin.sass` | `assets/css/admin.css` |

3. **Importante:** Desabilitar compilação de arquivos parciais (`_*.sass`)

### Outros Compiladores

**Dart Sass (CLI):**
```bash
sass frontend/frontend.sass:../css/frontend.css
sass members/members.sass:../css/members.css
sass admin/admin.sass:../css/admin.css
```

**Watch mode:**
```bash
sass --watch frontend/frontend.sass:../css/frontend.css
```

**Gulp/Webpack:**
Configurar task para compilar os 3 arquivos principais.

---

## Migração da Estrutura Antiga

### O que mudou?

**Antes:**
```
sass/
├── base/
├── components/
├── layout/
├── modules/
└── so-main.sass
```

**Depois:**
```
sass/
├── shared/
├── frontend/
├── members/
└── admin/
```

### Arquivos Migrados

| Arquivo Antigo | Novo Local |
|----------------|------------|
| `base/_b-reset.sass` | `shared/_reset.sass` |
| `base/_b-mixins.sass` | `shared/_mixins.sass` |
| `base/_b-responsive.sass` | `shared/_responsive.sass` |
| `base/_b-colors.sass` | `shared/_colors.sass` |
| `base/_b-classes.sass` | `shared/_classes.sass` |
| `base/_b-variables.sass` (fonts) | `shared/_fonts.sass` |
| `base/_b-variables.sass` (cores) | `frontend/base/_b-variables.sass` |
| `base/_b-variables.sass` ($bgdash*) | `members/base/_variables.sass` |
| `components/*` | `members/components/*` |
| `layout/*` | `members/layout/*` |
| `modules/*` | `members/modules/*` |
| `so-main.sass` | `frontend/frontend.sass` |

### Imports Atualizados

**Antes:**
```sass
@use '../base/b-variables' as *
@use '../base/b-colors' as *
@use '../base/b-mixins' as *
```

**Depois (Frontend):**
```sass
@use '../base/b-variables' as *
@use '../../shared/colors' as *
@use '../../shared/mixins' as *
```

**Depois (Members):**
```sass
@use '../base/variables' as *
@use '../../shared/colors' as *
@use '../../shared/mixins' as *
```

---

## Troubleshooting

### Erro: "Can't find stylesheet to import"

**Causa:** Path de import incorreto.

**Solução:**
```sass
// ❌ Errado
@use 'base/variables'

// ✅ Correto
@use '../base/variables'
@use '../../shared/mixins'
```

### Erro: "Undefined variable"

**Causa:** Não importou o arquivo que contém a variável.

**Solução:**
```sass
// Se usar $gray-50
@use '../../shared/colors' as *

// Se usar $color-main
@use '../base/b-variables' as *

// Se usar $bgdashheader
@use '../base/variables' as *
```

### Erro: "Undefined mixin"

**Causa:** Não importou mixins ou responsive.

**Solução:**
```sass
@use '../../shared/mixins' as *
@use '../../shared/responsive' as *

// Agora pode usar:
@include flex(row, center, space-between, nowrap)
@include responsive-min($tablet)
```

### CSS não está sendo aplicado

**Causas possíveis:**

1. **Arquivo não foi importado no agregador:**
```sass
// Adicionar em _components.sass ou _modules.sass
@use 'c-seu-componente'
```

2. **Agregador não foi importado no compilador principal:**
```sass
// Verificar em frontend.sass / members.sass / admin.sass
@use 'components/components'
@use 'modules/modules'
```

3. **CSS não foi recompilado:**
- Salvar arquivo .sass
- Verificar se CodeKit compilou
- Verificar erros no console do CodeKit

---

## Checklist para Novos Projetos

### Frontend

- [ ] Criar componentes necessários em `frontend/components/`
- [ ] Criar módulos necessários em `frontend/modules/`
- [ ] Adicionar imports nos agregadores (`_components.sass`, `_modules.sass`)
- [ ] Definir cores da marca em `frontend/base/_b-variables.sass`
- [ ] Compilar e testar `frontend.css`

### Members (se houver dashboard)

- [ ] Descomentar `members/members.sass`
- [ ] Customizar variáveis em `members/base/_variables.sass`
- [ ] Adicionar novos components/modules se necessário
- [ ] Compilar e testar `members.css`

### Admin (migração futura)

- [ ] Criar components BEM para admin
- [ ] Migrar CSS inline para SASS
- [ ] Definir variáveis do admin
- [ ] Compilar e testar `admin.css`

---

## Boas Práticas

### ✅ Faça

- Use BEM para nomenclatura consistente
- Mantenha componentes pequenos e reutilizáveis
- Use variáveis para cores e tamanhos
- Aproveite mixins para código repetitivo
- Comente código complexo
- Teste em diferentes resoluções

### ❌ Não Faça

- Não crie classes fora do padrão BEM
- Não use `!important` (exceto utilitários)
- Não duplique código (use mixins e @use)
- Não coloque estilos inline no HTML
- Não modifique arquivos em `shared/` sem necessidade
- Não importe arquivos desnecessários

---

## Recursos Adicionais

### Documentação SASS
- [Sass Documentation](https://sass-lang.com/documentation)
- [Sass Guidelines](https://sass-guidelin.es/)

### Metodologia BEM
- [BEM Methodology](http://getbem.com/)
- [BEM Naming Cheat Sheet](https://9elements.com/bem-cheat-sheet/)

### Responsive Design
- [CSS-Tricks Media Queries](https://css-tricks.com/snippets/css/media-queries-for-standard-devices/)

---

## Histórico de Mudanças

### v15.2.2 (2025-02-05)

- ✨ Reestruturação completa da arquitetura SASS
- ✨ Separação total: Frontend, Members, Admin
- ✨ Criação da pasta `shared/` com ferramentas universais
- ✨ Implementação de templates `_model.sass`
- ✨ Migração de 22 arquivos para members/
- 🗑️ Remoção de código não utilizado (extends, wrapper)
- 📝 Documentação completa da arquitetura

---

## Suporte

Dúvidas ou problemas com a arquitetura SASS?

- Consulte esta documentação
- Verifique exemplos nos arquivos existentes
- Analise os templates `_model.sass`

---

**AEGIS Framework v15.2.2**
*Arquitetura SASS - Documentação Completa*
