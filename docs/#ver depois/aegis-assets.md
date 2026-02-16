# AEGIS Framework - Pasta /assets/

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-17

[← Voltar ao índice](aegis-estrutura.md)

---

## 📁 Subpastas

### css/
- `so-main.css` - CSS final compilado (gerado do SASS)
- `so-main.css.map` - Source map

**Classificação:** CORE (gerado automaticamente)
**Regra:** NÃO editar manualmente

---

### fonts/
- `inter.ttf` - Font Inter (875 KB)
- `roboto.ttf` - Font Roboto (468 KB)

**Classificação:** CORE-AEGIS

---

### img/
- `logo.svg` - Logo (APP-FE)
- `avatar/` - Avatars padrão (CORE)
- `uploads/` - 106 arquivos (APP-FE)

**Classificação:** MISTO

---

### js/
**Principais:**
- `admin.js` - Admin panel
- `aegis-metricards.js` - MetricCards
- `aegis-tables.js` - Tables
- `filtros-*.js` - Filtros dinâmicos
- `graficos-*.js` - ApexCharts
- `*-min.js` - Versões minificadas (CodeKit)

**Subpastas:**
- `components/` - JS modulares
- `core/` - JS core framework

**Classificação:** CORE-AEGIS

---

### sass/
**Estrutura:**
- `so-main.sass` - Entry point
- `base/` - Variáveis, reset, tipografia
- `components/` - Botões, cards, forms
- `layout/` - Header, footer, grid
- `modules/` - Específicos do projeto

**Arquivo importante:**
- `base/_b-variables.sass` - Editado por SettingsController

**Classificação:** CORE-AEGIS
**Compilação:** CodeKit → css/so-main.css

---

## 🔧 Workflow

1. Editar: `sass/**/*.sass`
2. CodeKit compila: `css/so-main.css`
3. Editar: `js/*.js`
4. CodeKit minifica: `js/*-min.js`
