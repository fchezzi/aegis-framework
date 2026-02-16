# Sistema SEO - AEGIS Framework

**Versão:** 1.0.0
**Data:** 2026-02-08
**Autor:** Claude Code (supervisionado por Fábio Chezzi)

---

## Visão Geral

Sistema completo de otimização SEO para páginas do AEGIS Framework, incluindo:
- Meta tags básicas (title, description, robots, canonical)
- Open Graph Protocol (Facebook, WhatsApp, LinkedIn)
- Twitter Cards (X/Twitter)
- Schema.org JSON-LD (structured data)
- Upload e validação de imagens OG
- Analisador de qualidade SEO (scoring 0-100)
- Interface visual com feedback em tempo real

---

## Estrutura de Arquivos

### Core Classes

**`core/SEO.php`** (215 linhas)
- Renderiza todas as meta tags dinamicamente
- Usa fallbacks inteligentes (SEO básico → OG → Twitter)
- Gera JSON-LD para Schema.org

**`core/SEOAnalyzer.php`** (289 linhas)
- Calcula score SEO de 0 a 100
- Avalia qualidade de title e description
- Considera fallbacks no scoring
- Retorna grade (A+, A, B, C, D, F) e sugestões

### Controllers

**`admin/controllers/PagesController.php`**
- Método `uploadOGImage()` - Upload com validação completa
- Método `store()` - Salva novos dados SEO
- Método `update()` - Atualiza dados SEO e gerencia imagens

### Views

**`admin/views/pages/create.php`** (550+ linhas)
- Formulário SEO organizado em cards visuais
- Upload de imagem com preview
- Contador de caracteres em tempo real
- Score SEO calculado via JavaScript

**`admin/views/pages/edit.php`** (560+ linhas)
- Mesmas funcionalidades do create
- Exibe imagem OG atual
- Campo hidden para detectar imagem existente

### Frontend

**`frontend/includes/_head.php`**
- Renderiza SEO tags se `$page` disponível
- Usa `SEO::render()` e `SEO::renderJsonLD()`

---

## Banco de Dados

### Tabela: `pages`

**11 novos campos SEO:**

```sql
ALTER TABLE pages
  ADD COLUMN seo_title VARCHAR(70) NULL,
  ADD COLUMN seo_description VARCHAR(160) NULL,
  ADD COLUMN seo_robots VARCHAR(50) DEFAULT 'index,follow',
  ADD COLUMN seo_canonical_url VARCHAR(255) NULL,
  ADD COLUMN seo_og_type VARCHAR(20) DEFAULT 'website',
  ADD COLUMN seo_og_title VARCHAR(95) NULL,
  ADD COLUMN seo_og_description TEXT NULL,
  ADD COLUMN seo_og_image VARCHAR(255) NULL,
  ADD COLUMN seo_twitter_card ENUM('summary','summary_large_image') DEFAULT 'summary',
  ADD COLUMN seo_twitter_title VARCHAR(70) NULL,
  ADD COLUMN seo_twitter_description VARCHAR(200) NULL;
```

**Nota:** Esses campos são adicionados automaticamente pelo setup/instalação.

---

## Uso

### 1. Configurar SEO de uma Página

Acesse: `/admin/pages/edit/{id}`

**Campos obrigatórios:**
- ✅ **SEO Title** (50-60 chars ideal = 30 pontos)
- ✅ **SEO Description** (150-160 chars ideal = 30 pontos)

**Campos opcionais (mas recomendados):**
- OG Title (95 chars max)
- OG Description (texto)
- OG Image (upload: 1200x630px ideal)
- Twitter Title (70 chars max)
- Twitter Description (200 chars max)
- Canonical URL (URL completa)
- Robots (index,follow / noindex,nofollow)
- OG Type (website, article, product, etc.)
- Twitter Card (summary, summary_large_image)

### 2. Upload de Imagem OG

**Validações:**
- **Formato:** JPG, PNG, WebP
- **Tamanho:** Máximo 2MB
- **Dimensões mínimas:** 600x315px
- **Dimensões ideais:** 1200x630px (proporção 1.9:1)
- **Proporção:** Tolerância de ±0.3 (aviso se fora do ideal)

**Armazenamento:** `storage/uploads/seo/og-{uniqid}.{ext}`

**Preview:** Exibido em tempo real com dimensões

### 3. Fallbacks Inteligentes

Se campos opcionais vazios, o sistema usa:

| Campo vazio | Fallback |
|------------|----------|
| OG Title | `seo_title` |
| OG Description | `seo_description` |
| Twitter Title | `seo_title` ou `seo_og_title` |
| Twitter Description | `seo_description` ou `seo_og_description` |
| Canonical URL | `url('/' . $slug)` |

**Exemplo:**
```php
// Usuário preencheu apenas SEO básico
$page['seo_title'] = "Sobre Nós";
$page['seo_description'] = "Conheça nossa história...";
$page['seo_og_title'] = ""; // vazio

// Renderização usa fallback
echo SEO::render($page);
// Output: <meta property="og:title" content="Sobre Nós">
```

---

## Sistema de Scoring

### Distribuição de Pontos (100 total)

| Critério | Pontos | Condição |
|----------|--------|----------|
| **SEO Title perfeito** | 30 | 50-60 caracteres |
| SEO Title bom | 20 | 40-70 caracteres |
| SEO Title ruim | 10 | Qualquer outro |
| **SEO Description perfeita** | 30 | 150-160 caracteres |
| SEO Description boa | 20 | 120-160 caracteres |
| SEO Description ruim | 10 | Qualquer outro |
| **OG customizado** | 20 | Title E Description preenchidos |
| OG com fallback | 15 | Usa SEO básico |
| OG parcial | 10 | Apenas um preenchido |
| **Twitter customizado** | 10 | Title E Description preenchidos |
| Twitter com fallback | 7 | Usa SEO básico |
| Twitter parcial | 5 | Apenas um preenchido |
| **Canonical customizado** | 5 | URL preenchida |
| Canonical com fallback | 3 | Usa slug automático |
| **OG Image** | 5 | Imagem enviada |

### Grades

| Score | Grade | Descrição |
|-------|-------|-----------|
| 90-100 | A+ | Excelente! SEO otimizado. |
| 80-89 | A | Muito bom! Pequenos ajustes podem melhorar. |
| 70-79 | B | Bom, mas há espaço para melhorias. |
| 60-69 | C | Regular. Recomenda-se otimizar. |
| 50-59 | D | Abaixo do ideal. Precisa de atenção. |
| 0-49 | F | Crítico! SEO precisa ser configurado. |

### Cálculo em Tempo Real

**Backend (PHP):**
```php
$score = SEOAnalyzer::score($pageData);
$analysis = SEOAnalyzer::analyze($pageData);
// ['score' => 90, 'grade' => 'A+', 'issues' => [], 'suggestions' => []]
```

**Frontend (JavaScript):**
```javascript
function calculateScore() {
  // Atualiza em tempo real conforme usuário digita
  // Mesmo algoritmo do backend
  return score;
}
```

---

## Renderização Frontend

### Meta Tags Básicas

```html
<title>SEO Title - Nome do Site</title>
<meta name="description" content="SEO Description aqui...">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://seusite.com/pagina">
```

### Open Graph

```html
<meta property="og:type" content="website">
<meta property="og:url" content="https://seusite.com/pagina">
<meta property="og:title" content="OG Title">
<meta property="og:description" content="OG Description">
<meta property="og:image" content="https://seusite.com/storage/uploads/seo/og-xxx.png">
<meta property="og:site_name" content="Nome do Site">
<meta property="og:locale" content="pt_BR">
```

### Twitter Cards

```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Twitter Title">
<meta name="twitter:description" content="Twitter Description">
<meta name="twitter:image" content="https://seusite.com/storage/uploads/seo/og-xxx.png">
```

### JSON-LD (Schema.org)

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "SEO Title",
  "description": "SEO Description",
  "url": "https://seusite.com/pagina",
  "image": "https://seusite.com/storage/uploads/seo/og-xxx.png"
}
</script>
```

---

## Classes e Métodos

### SEO.php

#### `SEO::render($page)`
Renderiza todas as meta tags básicas, OG e Twitter.

**Parâmetros:**
- `$page` (array) - Dados da página do banco

**Retorna:** String HTML com todas as meta tags

**Uso:**
```php
<?php echo SEO::render($page); ?>
```

#### `SEO::renderJsonLD($page)`
Renderiza JSON-LD structured data.

**Parâmetros:**
- `$page` (array) - Dados da página do banco

**Retorna:** String HTML com `<script type="application/ld+json">`

**Uso:**
```php
<?php echo SEO::renderJsonLD($page); ?>
```

#### Métodos privados
- `renderTitle($page)` - Title tag
- `renderDescription($page)` - Meta description
- `renderRobots($page)` - Meta robots
- `renderCanonical($page)` - Link canonical
- `renderOpenGraph($page)` - OG tags
- `renderTwitterCard($page)` - Twitter tags

### SEOAnalyzer.php

#### `SEOAnalyzer::score($data)`
Calcula score de 0 a 100.

**Parâmetros:**
- `$data` (array) - Dados SEO da página

**Retorna:** Integer (0-100)

**Uso:**
```php
$score = SEOAnalyzer::score($pageData);
// 90
```

#### `SEOAnalyzer::analyze($data)`
Análise completa com issues e suggestions.

**Parâmetros:**
- `$data` (array) - Dados SEO da página

**Retorna:** Array com análise detalhada

**Uso:**
```php
$analysis = SEOAnalyzer::analyze($pageData);
// [
//   'score' => 90,
//   'grade' => 'A+',
//   'issues' => [],
//   'suggestions' => ['Considere preencher Twitter Card...']
// ]
```

#### Métodos privados
- `analyzeTitleScore($title)` - Score do title (0-30)
- `analyzeDescriptionScore($desc)` - Score da description (0-30)
- `analyzeTitle($title)` - Análise detalhada do title
- `analyzeDescription($desc)` - Análise detalhada da description
- `getGrade($score)` - Converte score em grade
- `getGradeDescription($grade)` - Descrição da grade

---

## Integração com PageController

### Disponibilizando $page para templates

**`public/controllers/PageController.php`:**

```php
private function renderPage($slug, $pageData, $member) {
    $pageFile = ROOT_PATH . 'frontend/pages/' . $slug . '.php';

    if (file_exists($pageFile)) {
        $pageSlug = $slug;
        $pageTitle = $pageData['title'];
        $pageContext = $pageData['context'] ?? 'public';
        $page = $pageData; // ← Variável disponível para _head.php
        require_once $pageFile;
    }
}
```

**Importante:** Usar `require_once` direto, **não** `Core::requireInclude()` que isola scope.

### Template da página

**`frontend/pages/exemplo.php`:**

```php
<?php
// Pegar usuário logado
$user = Auth::user() ?? MemberAuth::member() ?? null;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php require_once ROOT_PATH . 'frontend/includes/_head.php'; ?>
</head>
<body>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
</body>
</html>
```

---

## Troubleshooting

### Problema: SEO tags não aparecem

**Causa:** Variável `$page` não disponível em `_head.php`

**Solução:**
1. Verificar se `PageController::renderPage()` define `$page = $pageData`
2. Verificar se usa `require_once` direto (não `Core::requireInclude()`)

**Debug:**
```php
// Em _head.php, adicionar temporariamente:
echo "<!-- DEBUG: \$page isset? " . (isset($page) ? 'SIM' : 'NÃO') . " -->\n";
```

### Problema: Imagem OG não carrega (403 Forbidden)

**Causa:** Imagem salva fora de `storage/`

**Solução:**
1. Verificar se `uploadOGImage()` salva em `ROOT_PATH . 'storage/uploads/seo/'`
2. Verificar se retorna caminho com `storage/uploads/seo/` no início
3. Testar acesso: `http://seusite.com/storage/uploads/seo/og-xxx.png`

**Estrutura correta:**
```
aegis/
├── storage/
│   └── uploads/
│       └── seo/
│           └── og-698918805dd1f.png
```

**Banco de dados:**
```sql
seo_og_image = 'storage/uploads/seo/og-698918805dd1f.png'
```

**URL gerada:**
```php
url($page['seo_og_image'])
// http://localhost:5757/aegis/storage/uploads/seo/og-698918805dd1f.png
```

### Problema: Score sempre 60

**Causa:** JavaScript não detecta imagem já enviada

**Solução:**
Adicionar campo hidden em `edit.php`:
```html
<input type="hidden" id="seo_og_image_current" value="<?= htmlspecialchars($page['seo_og_image'] ?? '') ?>">
```

E verificar no JavaScript:
```javascript
const ogImageFile = document.getElementById('seo_og_image').value;
const ogImageCurrent = document.getElementById('seo_og_image_current')?.value || '';
if (ogImageFile || ogImageCurrent) {
  score += 5;
}
```

### Problema: URL helper concatena errado

**Causa:** Função `url()` antiga sem barras

**Solução correta em `_config.php` e `CoreConfig.php`:
```php
function url($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}
```

**Exemplo:**
```php
APP_URL = 'http://localhost:5757/aegis'
$path = 'uploads/seo/og-xxx.png'

// ERRADO: http://localhost:5757/aegisuploads/seo/og-xxx.png
return APP_URL . $path;

// CERTO: http://localhost:5757/aegis/uploads/seo/og-xxx.png
return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
```

---

## Arquivos Modificados

### Novos arquivos criados
- `core/SEO.php`
- `core/SEOAnalyzer.php`
- `docs/melhorias-futuras.md`
- `docs/sistema-seo.md` (este arquivo)

### Arquivos modificados
- `admin/controllers/PagesController.php` - Upload e CRUD SEO
- `admin/views/pages/create.php` - Interface SEO
- `admin/views/pages/edit.php` - Interface SEO
- `public/controllers/PageController.php` - Disponibiliza `$page`
- `frontend/includes/_head.php` - Renderiza SEO tags
- `assets/sass/admin/modules/_m-pagebase.sass` - Estilos SEO widget
- `_config.php` - Função `url()` corrigida
- `core/CoreConfig.php` - Template `url()` corrigida
- `docs/aegis-core-01.md` - Documentação `url()` atualizada
- `.htaccess` - Regra para uploads (depois revertida)

### Migração SQL necessária
```sql
ALTER TABLE pages
  ADD COLUMN seo_title VARCHAR(70) NULL,
  ADD COLUMN seo_description VARCHAR(160) NULL,
  ADD COLUMN seo_robots VARCHAR(50) DEFAULT 'index,follow',
  ADD COLUMN seo_canonical_url VARCHAR(255) NULL,
  ADD COLUMN seo_og_type VARCHAR(20) DEFAULT 'website',
  ADD COLUMN seo_og_title VARCHAR(95) NULL,
  ADD COLUMN seo_og_description TEXT NULL,
  ADD COLUMN seo_og_image VARCHAR(255) NULL,
  ADD COLUMN seo_twitter_card ENUM('summary','summary_large_image') DEFAULT 'summary',
  ADD COLUMN seo_twitter_title VARCHAR(70) NULL,
  ADD COLUMN seo_twitter_description VARCHAR(200) NULL;
```

---

## Testes Recomendados

### 1. Validação de Meta Tags
- Facebook Sharing Debugger: https://developers.facebook.com/tools/debug/
- Twitter Card Validator: https://cards-dev.twitter.com/validator
- Google Rich Results Test: https://search.google.com/test/rich-results

### 2. Preview Social
- Como aparece no Facebook/WhatsApp
- Como aparece no Twitter/X
- Como aparece no LinkedIn
- Como aparece no Google Search

### 3. Testes de Upload
- Upload de imagem 600x315px (mínimo)
- Upload de imagem 1200x630px (ideal)
- Upload de imagem muito pequena (deve falhar)
- Upload de arquivo > 2MB (deve falhar)
- Upload de PDF (deve falhar - formato inválido)
- Upload de imagem com proporção errada (deve avisar)

### 4. Testes de Fallback
- Preencher apenas SEO básico → OG/Twitter usam fallback
- Preencher OG mas não Twitter → Twitter usa OG
- Deixar canonical vazio → usa slug automático

---

## Manutenção

### Atualizar limites de caracteres

**Arquivo:** `core/SEOAnalyzer.php`

```php
// Title (linha ~129)
if ($length >= 50 && $length <= 60) {
    return 30; // Alterar limites aqui
}

// Description (linha ~157)
if ($length >= 150 && $length <= 160) {
    return 30; // Alterar limites aqui
}
```

**Arquivos:** `admin/views/pages/create.php` e `edit.php`

```javascript
// Atualizar JavaScript (linha ~410 e ~450)
const maxLength = field === 'title' ? 70 : 160;

if (length >= 50 && length <= 60) { // Title perfeito
  status.textContent = '✅ Perfeito!';
}

if (length >= 150 && length <= 160) { // Description perfeita
  status.textContent = '✅ Perfeito!';
}
```

### Adicionar novos tipos OG

**Arquivo:** `admin/views/pages/create.php` e `edit.php`

```html
<select id="seo_og_type" name="seo_og_type">
  <option value="website">Website</option>
  <option value="article">Article (Blog/News)</option>
  <option value="product">Product (E-commerce)</option>
  <!-- Adicionar novos tipos aqui -->
  <option value="video.movie">Video - Movie</option>
  <option value="music.song">Music - Song</option>
</select>
```

---

## Referências

- Open Graph Protocol: https://ogp.me/
- Twitter Cards: https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards
- Schema.org WebPage: https://schema.org/WebPage
- Google SEO Guidelines: https://developers.google.com/search/docs
- Meta Tags Best Practices: https://moz.com/learn/seo/meta-description

---

## Changelog

### v1.0.0 (2026-02-08)
- ✅ Sistema SEO completo implementado
- ✅ 11 campos SEO na tabela pages
- ✅ Classes SEO.php e SEOAnalyzer.php
- ✅ Upload de imagem OG com validação
- ✅ Interface visual com scoring em tempo real
- ✅ Fallbacks inteligentes
- ✅ JSON-LD structured data
- ✅ Função url() corrigida para paths corretos
- ✅ Storage em storage/uploads/seo/
- ✅ Documentação completa
