# AEGIS SASS - Referência de Variáveis

## 🎨 Cores do Sistema (shared/colors)

### Paleta de Cinzas (26 tons)

```sass
$gray-01: #FCFCFC   // ⬜ Quase branco
$gray-02: #FAFAFA
$gray-03: #F7F7F7
$gray-04: #F5F5F5
$gray-05: #F0F1F1
$gray-06: #F0F0F0
$gray-07: #EDEDED
$gray-08: #EBEBEB
$gray-09: #E8E8E8
$gray-10: #E5E6E7   // ⬜ Cinza muito claro
$gray-15: #DBDDDE
$gray-20: #D0D2D4
$gray-25: #C6C7C9
$gray-30: #BBBDBF
$gray-35: #B1B3B5
$gray-40: #A6A9AB
$gray-45: #9C9EA1
$gray-50: #929497   // ◼️ Cinza médio
$gray-55: #898B8E
$gray-60: #808184
$gray-65: #76787A
$gray-70: #6C6D70
$gray-75: #626466
$gray-80: #57585A
$gray-85: #4C4C4E   // ⬛ Cinza escuro
$gray-90: #3F3F41
$gray-95: #3F3F41   // ⬛ Quase preto
```

### Cores Funcionais

```sass
$color-success: #27ae60   // 🟢 Verde - Sucesso
$color-error: #e74c3c     // 🔴 Vermelho - Erro
$color-warning: #f39c12   // 🟡 Amarelo - Aviso
$color-info: #3498db      // 🔵 Azul - Informação
```

**Uso:**
```sass
.mensagem-sucesso
  color: $color-success
  background: lighten-color($color-success, 90%)
```

---

## 🎨 Cores da Marca (frontend/base/variables)

```sass
$color-main: #6c10b8      // 🟣 Roxo - Principal
$color-second: #C41C1C    // 🔴 Vermelho - Secundária
$color-third: #A39D8F     // 🟤 Bege - Terciária
$color-four: #A39D8F      // (definir conforme projeto)
$color-five: #A39D8F      // (definir conforme projeto)
```

**Uso:**
```sass
.botao-principal
  background: $color-main

  &:hover
    background: darken-color($color-main, 10%)
```

---

## 🎨 Cores do Dashboard (members/base/variables)

### Variáveis Dashboard (editáveis via admin)

```sass
// Headers
$bgdashheader: linear-gradient(135deg, #4C4C4E 0%, #000 100%)
$bgdashheaderdark: linear-gradient(45deg, #FFFFFF 0%, #E5E6E7 100%)

// Main
$bgdashmain: #4C4C4E
$bgdashmaindark: #FFFFFF

// Breadcrumb
$bgdashbread: #E10909
$bgdashbreaddark: #F0F0F0

// Sidebar
$bgdashaside: #E10909

// Logo
$bgdashlogo: #000000
$bgdashlogodark: #FFFFFF
```

**Uso:**
```sass
.l-header
  background: $bgdashheader

  body:not(.dark) &
    background: $bgdashheaderdark
```

---

## 📱 Breakpoints (shared/responsive)

### Tamanhos de Tela

```sass
$phones: 320px          // 📱 Celular pequeno
$phonesWide: 480px      // 📱 Celular grande
$phtablet: 600px        // 📱 Phone-Tablet
$tabletSmall: 768px     // 📱 Tablet pequeno
$tablet: 900px          // 📱 Tablet
$tabletWide: 1024px     // 💻 Tablet grande
$desktop: 1200px        // 💻 Desktop
$desktopWide: 1440px    // 🖥️ Desktop grande
$desktopMegaWide: 1920px // 🖥️ Desktop extra grande
```

### Mixins Responsivos

**Min-width (de X para cima):**
```sass
@include responsive-min($tablet)
  // Aplica em tablet e acima (≥900px)
  font-size: 18px

@include responsive-min($desktop)
  // Aplica em desktop e acima (≥1200px)
  font-size: 20px
```

**Max-width (de X para baixo):**
```sass
@include responsive-max($tablet)
  // Aplica abaixo de tablet (<900px)
  font-size: 14px

@include responsive-max($phones)
  // Aplica apenas em celulares pequenos (<320px)
  font-size: 12px
```

**Range (entre X e Y):**
```sass
@include responsive-range($tablet, $desktop)
  // Aplica apenas entre 900px e 1200px
  font-size: 16px
```

---

## 🔤 Tipografia

### Fontes

```sass
$font-title: 'roboto', sans-serif    // Títulos e headings
$font-text: 'inter', sans-serif      // Texto e parágrafos
```

**Uso:**
```sass
h1, h2, h3
  font-family: $font-title

p, a, span
  font-family: $font-text
```

### Classes Automáticas

Já aplicado globalmente em `base/_b-variables.sass`:

```sass
p, a, ul, li, button, label, table
  font-family: $font-text    // ← Automático

h1, h2, h3, h4, h5, h6
  font-family: $font-title   // ← Automático
```

---

## 🎯 Classes Utilitárias (shared/classes)

### Display

```sass
.u-displaynone      // display: none !important
.u-displayblock     // display: block !important
```

### Visibilidade Responsiva

**Esconder de X para cima:**
```sass
.u-no-up-tablet        // Esconde em ≥900px (tablet+)
.u-no-up-tabletwide    // Esconde em ≥1024px (tablet wide+)
.u-no-up-desktop       // Esconde em ≥1200px (desktop+)
.u-no-up-desktopwide   // Esconde em ≥1440px (desktop wide+)
```

**Esconder abaixo de X:**
```sass
.u-no-down-tablet      // Esconde em <900px (mobile)
.u-no-down-tabletwide  // Esconde em <1024px
.u-no-down-desktop     // Esconde em <1200px
.u-no-down-desktopwide // Esconde em <1440px
```

**Exemplo de uso:**
```html
<!-- Mostrar apenas em desktop -->
<div class="u-no-down-desktop">
  Conteúdo visível apenas em desktop
</div>

<!-- Mostrar apenas em mobile -->
<div class="u-no-up-tablet">
  Conteúdo visível apenas em mobile
</div>
```

### Classes de Cores (Frontend)

```sass
// Texto
.u-c-main       // color: $color-main
.u-c-second     // color: $color-second
.u-c-third      // color: $color-third

// Background
.u-bg-main      // background: $color-main
.u-bg-second    // background: $color-second
.u-bg-third     // background: $color-third
```

---

## 🛠️ Funções de Cores (shared/mixins)

### Manipulação de Luminosidade

```sass
darken-color($color, $amount)
// Escurece a cor (0% a 100%)
// Exemplo: darken-color($color-main, 10%)

lighten-color($color, $amount)
// Clareia a cor (0% a 100%)
// Exemplo: lighten-color($color-main, 20%)
```

**Exemplo:**
```sass
.botao
  background: $color-main

  &:hover
    background: darken-color($color-main, 10%)    // 10% mais escuro

  &:active
    background: darken-color($color-main, 20%)    // 20% mais escuro

.botao-outline
  border: 1px solid $color-main
  background: lighten-color($color-main, 95%)     // Quase branco
```

### Manipulação de Saturação

```sass
saturate-color($color, $amount)
// Aumenta saturação (mais vibrante)
// Exemplo: saturate-color($color-main, 30%)

desaturate-color($color, $amount)
// Diminui saturação (mais cinza/opaco)
// Exemplo: desaturate-color($color-main, 40%)
```

**Exemplo:**
```sass
.card-featured
  background: saturate-color($color-main, 20%)   // Mais vibrante

.card-disabled
  background: desaturate-color($color-main, 50%) // Mais opaco
```

### Transparência

```sass
fade($color, $opacity)
// Adiciona transparência
// $opacity: 0 (transparente) a 1 (opaco)
// Exemplo: fade($color-main, 0.5)  // 50% transparente
```

**Exemplo:**
```sass
.overlay
  background: fade($gray-95, 0.8)    // Preto 80% opaco

.highlight
  background: fade($color-main, 0.1) // Roxo 10% opaco (quase transparente)
```

---

## 🎛️ Mixins Úteis (shared/mixins)

### Flex Layout

```sass
@include flex($direction, $align-items, $justify-content, $flex-wrap)
```

**Exemplos:**
```sass
.container
  @include flex(row, center, space-between, nowrap)
  // flex-direction: row
  // align-items: center
  // justify-content: space-between
  // flex-wrap: nowrap

.card
  @include flex(column, flex-start, flex-start, wrap)
```

**Valores comuns:**
- `$direction`: row, column, row-reverse, column-reverse
- `$align-items`: flex-start, center, flex-end, stretch, baseline
- `$justify-content`: flex-start, center, flex-end, space-between, space-around
- `$flex-wrap`: nowrap, wrap, wrap-reverse

### Placeholder

```sass
@include placeholder
  // Estilos do placeholder
```

**Exemplo:**
```sass
input
  @include placeholder
    color: $gray-50
    font-style: italic
```

### Centralização Absoluta

```sass
@include center-absolute
// position: absolute
// top: 50%
// left: 50%
// transform: translate(-50%, -50%)
```

**Exemplo:**
```sass
.modal
  @include center-absolute
  width: 500px
  height: 300px
```

---

## 📊 Referência Rápida de Uso

### Importar tudo que precisa

```sass
// Arquivo de component/module
@use 'sass:color'
@use '../base/b-variables' as *      // $color-main, $font-title
@use '../../shared/mixins' as *      // darken-color(), @include flex()
@use '../../shared/responsive' as *  // $tablet, @include responsive-min()
@use '../../shared/colors' as *      // $gray-50, $color-success
```

### Exemplo Completo

```sass
.c-card
  padding: 20px
  background: $gray-01
  border: 1px solid $gray-20
  border-radius: 8px
  @include flex(column, flex-start, flex-start, nowrap)

  &__header
    width: 100%
    padding-bottom: 16px
    border-bottom: 1px solid $gray-10
    margin-bottom: 16px

  &__title
    font-family: $font-title
    font-size: 20px
    color: $gray-90

  &__content
    font-family: $font-text
    color: $gray-70
    line-height: 1.6

  &--featured
    border-color: $color-main
    background: lighten-color($color-main, 95%)

    .c-card__title
      color: $color-main

  &--success
    border-left: 4px solid $color-success
    background: lighten-color($color-success, 95%)

  @include responsive-min($tablet)
    padding: 30px

    &__title
      font-size: 24px

  @include responsive-max($phones)
    padding: 15px

    &__title
      font-size: 18px
```

---

## 🔍 Busca Rápida

**Precisa de...**

- ✅ Cor de cinza → `$gray-XX` (shared/colors)
- ✅ Cor da marca → `$color-main` (frontend/base/variables)
- ✅ Cor do dashboard → `$bgdash*` (members/base/variables)
- ✅ Breakpoint → `$tablet`, `$desktop` (shared/responsive)
- ✅ Escurecer cor → `darken-color()` (shared/mixins)
- ✅ Clarear cor → `lighten-color()` (shared/mixins)
- ✅ Media query → `@include responsive-min()` (shared/responsive)
- ✅ Flex layout → `@include flex()` (shared/mixins)
- ✅ Esconder mobile → `.u-no-down-tablet` (shared/classes)
- ✅ Esconder desktop → `.u-no-up-tablet` (shared/classes)

---

**AEGIS Framework v15.2.2**
*Referência Completa de Variáveis*
