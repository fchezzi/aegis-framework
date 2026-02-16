# AEGIS SASS - Quick Start Guide

## 🚀 Início Rápido

### Estrutura Básica

```
sass/
├── shared/        → Ferramentas compartilhadas (reset, mixins, colors)
├── frontend/      → Site público (landing pages, institucional)
├── members/       → Dashboard de membros autenticados
└── admin/         → Painel administrativo
```

### Compilação

| Arquivo | Compila Para |
|---------|--------------|
| `frontend/frontend.sass` | `css/frontend.css` |
| `members/members.sass` | `css/members.css` |
| `admin/admin.sass` | `css/admin.css` |

---

## ✨ Criar Component (Frontend)

```bash
# 1. Copiar template
cp frontend/components/_model.sass frontend/components/_c-button.sass

# 2. Editar arquivo com BEM
# .c-button
#   &__icon
#   &--primary

# 3. Adicionar import
echo "@use 'c-button'" >> frontend/components/_components.sass
```

---

## ✨ Criar Module (Frontend)

```bash
# 1. Copiar template
cp frontend/modules/_model.sass frontend/modules/_m-hero.sass

# 2. Editar arquivo
# .m-hero
#   &__title

# 3. Adicionar import
echo "@use 'm-hero'" >> frontend/modules/_modules.sass
```

---

## 📋 BEM Naming

```sass
// BLOCK
.c-card { }

// ELEMENT
.c-card__title { }
.c-card__content { }

// MODIFIER
.c-card--featured { }
.c-card--dark { }
```

---

## 📦 Prefixos

- `c-` = Component (reutilizável)
- `m-` = Module (específico de página)
- `l-` = Layout (estrutura)
- `u-` = Utility (classes auxiliares)

---

## 🎨 Variáveis Úteis

### Cores (shared/colors)
```sass
$gray-01 até $gray-95    // 26 tons de cinza
$color-success           // Verde
$color-error             // Vermelho
$color-warning           // Amarelo
$color-info              // Azul
```

### Breakpoints (shared/responsive)
```sass
$tablet: 900px
$desktop: 1200px
$desktopWide: 1440px

@include responsive-min($tablet)
  // Tablet e acima

@include responsive-max($tablet)
  // Abaixo de tablet
```

### Mixins (shared/mixins)
```sass
@include flex(row, center, space-between, nowrap)
@include center-absolute
@include placeholder { }

darken-color($color, 10%)
lighten-color($color, 20%)
```

---

## 📁 Template de Arquivo

```sass
// Nome do Component - Descrição

@use 'sass:color'
@use '../base/b-variables' as *
@use '../../shared/mixins' as *
@use '../../shared/responsive' as *
@use '../../shared/colors' as *

.c-nome-componente
  propriedade: valor

  &__elemento
    propriedade: valor

  &--modifier
    propriedade: valor

  @include responsive-min($tablet)
    propriedade: valor
```

---

## ⚠️ Troubleshooting

### "Can't find stylesheet"
```sass
// ❌ Errado
@use 'colors'

// ✅ Correto
@use '../../shared/colors' as *
```

### "Undefined variable"
```sass
// Importar o arquivo correto
@use '../../shared/colors' as *    // $gray-50
@use '../base/b-variables' as *    // $color-main
```

### CSS não aplica
1. Verificar import no agregador (`_components.sass`)
2. Verificar agregador no compilador principal (`frontend.sass`)
3. Recompilar SASS

---

## 📚 Documentação Completa

Ver: `README.md` para documentação detalhada.

---

**AEGIS Framework v15.2.2**
