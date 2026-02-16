# AEGIS Framework - Pasta /frontend/

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-18

[← Voltar ao índice](aegis-estrutura.md)

---

## 📊 RESUMO

**Total:** 41 arquivos
**Páginas:** 25 páginas (.php)
**Includes:** 7 includes (_*.php)
**Templates:** 3 templates (dashboard, basic, dashboard-menu-auto)
**Controllers:** 1 controller (DownloadController)
**Views:** 1 view (login)
**Components:** 0 (pasta vazia)

**Total de linhas (pages):** ~2.102 linhas

---

## 🏗️ ARQUITETURA

### Estrutura Padrão

```
frontend/
├── includes/          # Partials reutilizáveis (header, footer, head)
├── templates/         # Layouts de página (dashboard, basic)
├── pages/             # Páginas finais (home, dashboard, canais)
├── views/             # Views isoladas (login)
├── controllers/       # Controllers frontend (DownloadController)
└── components/        # Componentes isolados (vazio)
```

---

## 📁 INCLUDES (7 arquivos)

### 1. _head.php (15 linhas)

**Função:** Meta tags + CSS + Lucide Icons

**Conteúdo:**
```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="<?= url('assets/img/favicon.svg') ?>" />
<link rel="stylesheet" href="<?= url('/assets/css/so-main.css') ?>">
<script src="https://unpkg.com/lucide@latest"></script>
```

**Classificação:** 100% CORE

---

### 2. _header.php (67 linhas)

**Função:** Header responsivo com dark mode + avatar + dropdown

**Features:**
- Detecção automática de contexto (Admin vs Member) - linhas 3-13
- Logo clicável
- Theme toggle (dark/light)
- User dropdown:
  - Avatar (default ou custom)
  - Nome do usuário
  - Link /profile
  - Link /logout
- Lucide Icons (moon, sun, user, log-out)

**Lógica de autenticação (linhas 1-14):**
```php
if (!isset($user)) {
    $isAdminArea = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') !== false;

    if ($isAdminArea) {
        $user = Auth::user() ?? null;
    } else {
        $user = MemberAuth::member() ?? null;
    }
}
```

**Classificação:** 100% CORE

---

### 3. _footer.php (20 linhas)

**Função:** Footer + scripts essenciais

**Conteúdo:**
- Copyright dinâmico: `<?= date('Y') ?>`
- Créditos: "AEGIS Framework - Criado com Claude Code"
- Scripts JS:
  - debug-simples.js
  - filtros-fix.js (preservar data ao trocar canal)
  - filtros-autoload.js (auto-aplicação padrão)
- Fecha </body></html>

**Classificação:** 100% CORE

---

### 4. _aside.php (12 linhas)

**Função:** Sidebar do dashboard

**Conteúdo:**
```html
<aside class="l-sidebar" id="sidebar">
    <nav class="m-sidebar">
        <ul class="m-sidebar__menu">
            <?php require ROOT_PATH . 'frontend/includes/_menu-dinamico.php'; ?>
        </ul>
    </nav>
</aside>
```

**Classificação:** 100% CORE

---

### 5. _menu-dinamico.php (14 linhas)

**Função:** Menu renderizado via MenuBuilder

**Lógica:**
```php
// Pegar ID do member logado (null se não estiver logado)
$member = MemberAuth::member();
$memberId = $member ? $member['id'] : null;

// Renderizar menu
echo MenuBuilder::render($memberId);
```

**Features:**
- Filtro automático por permissões
- Suporta submenus/accordion
- Classe "menu-item--active" via URL atual
- Zero hardcode (100% database-driven)

**Classificação:** 100% CORE

---

### 6. _gtm-head.php (7 linhas)

**Função:** Google Tag Manager <head>

**Conteúdo:** Vazio (placeholder)

**Uso:** Include customizável via admin (/admin/includes)

**Classificação:** 100% CORE

---

### 7. _gtm-body.php (vazio)

**Função:** Google Tag Manager <body>

**Conteúdo:** Vazio (placeholder)

**Classificação:** 100% CORE

---

## 📄 TEMPLATES (3 arquivos)

### 1. dashboard.php (87 linhas)

**Função:** Template completo para páginas do dashboard

**Estrutura:**
```php
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- GTM Head -->
    <?php Core::requireInclude('frontend/includes/_gtm-head.php', true); ?>

    <!-- Head comum -->
    <?php Core::requireInclude('frontend/includes/_head.php', true); ?>

    <title><?= htmlspecialchars($title) ?> - Energia 97</title>
</head>
<body>
    <!-- GTM Body -->
    <?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>

    <!-- Dark mode script (inline - carrega ANTES de renderizar) -->
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme !== 'light') {
            document.body.classList.add('dark');
        }
    </script>

    <!-- Header -->
    <?php Core::requireInclude('frontend/includes/_header.php', true); ?>

    <!-- Breadcrumb -->
    <?= Core::renderBreadcrumb([
        ['Home', '/'],
        ['Dashboard', '/dashboard'],
        [htmlspecialchars($title)]
    ]) ?>

    <!-- Main -->
    <main class="l-main">
        <!-- Aside -->
        <?php Core::requireInclude('frontend/includes/_aside.php', true); ?>

        <!-- Content -->
        <div class="l-content">
            <?php
            // Renderizar blocos do Page Builder
            echo PageBuilder::render($slug);
            ?>
        </div>
    </main>

    <!-- Scripts -->
    <script src="<?= url('/assets/js/theme-toggle-min.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard-min.js') ?>"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
```

**Variáveis esperadas (linhas 2-9):**
- `$pageTitle` ou default `{NAME}` (substituído pelo admin)
- `$pageSlug` ou default `{SLUG}` (substituído pelo admin)
- `$user` - Auto-detect (Auth::user() ou MemberAuth::member())

**Classificação:** 100% CORE

---

### 2. basic.php (34 linhas)

**Função:** Template minimalista (só head/body)

**Uso:** Páginas sem header/footer/sidebar

**Estrutura:**
```php
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php Core::requireInclude('frontend/includes/_gtm-head.php', true); ?>
    <?php Core::requireInclude('frontend/includes/_head.php', true); ?>
    <title>Energia 97 - Dashboard</title>
</head>
<body>
    <?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>

    <!-- Conteúdo customizado aqui -->
</body>
</html>
```

**Classificação:** 100% CORE

---

### 3. dashboard-menu-auto.php (46 linhas)

**Função:** Exemplo de integração MenuBuilder

**Tipo:** Documentação (não é template usado)

**Conteúdo (linhas 11-30):**
```php
<!-- SIDEBAR -->
<aside class="l-sidebar" id="sidebar">
    <nav class="m-sidebar">
        <ul class="m-sidebar__menu">
            <?php
            // Pegar ID do member logado (ou null se for admin/público)
            $member = MemberAuth::member();
            $memberId = $member ? $member['id'] : null;

            // Renderizar menu dinâmico
            echo MenuBuilder::render($memberId);
            ?>
        </ul>
    </nav>
</aside>
```

**Instruções (linhas 32-45):**
1. Substituir conteúdo da tag `<ul class="m-sidebar__menu">` no dashboard.php
2. Colar código acima
3. Deletar itens de menu manuais
4. Menu será gerado automaticamente do banco

**Classificação:** 100% CORE

---

## 📄 PAGES (25 arquivos)

### Padrão Geral

**Todas páginas seguem estrutura:**
```php
<?php
$user = Auth::user() ?? MemberAuth::member() ?? null;
$title = isset($pageTitle) ? $pageTitle : '{NAME}';
$slug = isset($pageSlug) ? $pageSlug : '{SLUG}';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- includes -->
</head>
<body>
    <!-- includes + PageBuilder::render($slug) -->
</body>
</html>
```

### Páginas Principais

**1. home.php (56 linhas)**
- Landing page pública
- Logo centralizada
- 2 links: /admin/login e /dashboard
- Sem header/footer (standalone)
- **Classificação:** 90% CORE / 10% APP (texto hardcoded)

**2. dashboard.php (87 linhas)**
- Template dashboard completo
- Header + Aside + PageBuilder
- Breadcrumb automático
- Dark mode toggle
- **Classificação:** 100% CORE (idêntico ao template)

**3. blank-page.php**
- Página em branco (template vazio)
- **Classificação:** 100% CORE

**4. layout-page.php**
- Exemplo de layout customizado
- **Classificação:** 100% CORE

**5. dashboard-page.php**
- Alias do dashboard.php
- **Classificação:** 100% CORE

### Páginas de Canais (APP-SPECIFIC)

**Estrutura idêntica, só muda título/slug:**
- youtube.php - `$title = 'youtube'`, `$slug = 'youtube'`
- instagram.php - `$title = 'Instagram'`, `$slug = 'instagram'`
- facebook.php - `$title = 'Facebook'`, `$slug = 'facebook'`
- tik-tok.php - `$title = 'TikTok'`, `$slug = 'tik-tok'`
- twitch.php - `$title = 'Twitch'`, `$slug = 'twitch'`
- x.php - `$title = 'X (Twitter)'`, `$slug = 'x'`
- app.php - `$title = 'App'`, `$slug = 'app'`
- website.php - `$title = 'Website'`, `$slug = 'website'`

**Programas (APP-SPECIFIC):**
- energia-em-campo.php - Programa 1
- damas-em-campo.php - Programa 2
- morde-e-assopra.php - Programa 3
- tempo-de-jogo.php - Programa 4
- estadio-97.php - Programa 5

**Outras:**
- cards.php - Exemplo de MetricCards
- charts.php - Exemplo de gráficos
- filtros.php - Exemplo de filtros
- tabelas.php - Exemplo de tabelas
- downloads.php - Listagem de relatórios
- profile.php - Perfil do usuário

**Pasta sem título/ (lixo):**
- website.php (duplicado)
- youtube.php (duplicado)

**Classificação geral pages:** 40% CORE / 60% APP-SPECIFIC (nomes de canais/programas)

---

## 🎮 CONTROLLERS (1 arquivo)

### DownloadController.php (132 linhas)

**Função:** Gerar e baixar relatórios Excel para membros/admins

**Métodos:**

1. **index()** (linhas 17-30)
   - Lista relatórios disponíveis (visible=1)
   - Requer autenticação (Auth::check() OU MemberAuth::check())
   - Carrega view: frontend/pages/downloads.php

2. **generate($templateId)** (linhas 35-120)
   - Gera relatório Excel via PhpSpreadsheet
   - Busca template + cells no banco
   - Carrega arquivo Excel base (IOFactory::load)
   - Preenche células via ReportDataSources::execute()
   - **Suporta múltiplas abas:** $sheet = $spreadsheet->getSheetByName($sheetName) (linha 81)
   - Headers para download (linha 105-107)
   - Escreve direto no output (php://output) - linha 111
   - **NÃO salva no disco** (seguro)

3. **sanitizeFileName($name)** (linhas 125-130)
   - Remove caracteres especiais
   - Preg_replace: `[^a-zA-Z0-9_-]` → `_`
   - Consolida underscores múltiplos

**Segurança:**
- Validação autenticação (linha 19, 38)
- Validação template existe + visible=1 (linha 47)
- file_exists() antes de IOFactory::load (linha 65)
- Try/catch completo (linha 43)
- Error logging (linha 116)
- Filename sanitization (linha 102)

**Classificação:** 90% CORE / 10% APP (específico para reports module)

---

## 👁️ VIEWS (1 arquivo)

### login.php (86 linhas)

**Função:** Página de login para membros (área pública)

**Features:**
- CSS inline (standalone)
- Gradient background (purple)
- Form responsivo
- CSRF token (Security::generateCSRF())
- Error display (via $_SESSION['error'])
- Auto-unset error (linha 65)

**Campos:**
- Email (required, autofocus)
- Password (required)
- CSRF token (hidden)

**Action:** POST para url('/login')

**Estilo:**
- Design moderno (gradiente, sombras)
- Responsivo (max-width: 400px)
- Focus state (border-color)
- Hover state (opacity)

**Classificação:** 100% CORE

---

## 📦 COMPONENTS (pasta vazia)

**Status:** Vazia (0 arquivos)

**Propósito:** Futura criação de componentes isolados (Vue/React-style)

---

## 🎯 PADRÕES IDENTIFICADOS

### 1. Template Pattern

**Hierarquia:**
```
Template (dashboard.php)
    → Includes (_head, _header, _footer, _aside, _menu-dinamico)
        → Page (dashboard, youtube, instagram)
            → PageBuilder::render($slug)
                → Components (Cards, Tabelas, Gráficos)
```

### 2. User Detection

**Context-aware (linhas 3-13 de _header.php):**
```php
if (!isset($user)) {
    $isAdminArea = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') !== false;

    if ($isAdminArea) {
        $user = Auth::user() ?? null; // Admin
    } else {
        $user = MemberAuth::member() ?? null; // Member
    }
}
```

### 3. Variable Substitution

**Placeholders para Page Builder (linhas 6-9 de dashboard.php):**
```php
// Quando criado via admin, {NAME} e {SLUG} são substituídos
$title = isset($pageTitle) ? $pageTitle : '{NAME}';
$slug = isset($pageSlug) ? $pageSlug : '{SLUG}';
```

### 4. GTM Ready

**Todos templates incluem:**
- _gtm-head.php (antes do </head>)
- _gtm-body.php (logo após <body>)

**Editáveis via:** /admin/includes

### 5. Dark Mode First

**Script inline ANTES de renderizar (linhas 37-43 de dashboard.php):**
```php
<script>
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme !== 'light') {
        document.body.classList.add('dark');
    }
</script>
```

**Evita:** Flash de conteúdo claro (FOUC)

### 6. Breadcrumb Automático

**Helper do Core (linha 49 de dashboard.php):**
```php
<?= Core::renderBreadcrumb([
    ['Home', '/'],
    ['Dashboard', '/dashboard'],
    [htmlspecialchars($title)]
]) ?>
```

### 7. Avatar Fallback

**Linha 37-39 de _header.php:**
```php
$avatarUrl = !empty($user['avatar'])
    ? url($user['avatar'])
    : url('/assets/img/avatar/default.jpeg');
```

---

## 📊 ESTATÍSTICAS

**Total:** 41 arquivos (~2.500 linhas estimadas)

**Por categoria:**
- Includes: 7 arquivos (~140 linhas)
- Templates: 3 arquivos (~167 linhas)
- Pages: 25 arquivos (~2.102 linhas)
- Controllers: 1 arquivo (132 linhas)
- Views: 1 arquivo (86 linhas)
- Components: 0 arquivos

**Classificação geral:**
- **CORE-AEGIS:** 60% (includes, templates, controllers, views, páginas exemplo)
- **APP-SPECIFIC:** 40% (páginas de canais/programas específicos do Futebol Energia)

---

## 🔧 OPORTUNIDADES

### Pontos Fortes
✅ Separação clara (includes, templates, pages)
✅ Reutilização via includes (DRY)
✅ MenuBuilder integrado (zero hardcode)
✅ Dark mode sem FOUC
✅ Avatar fallback
✅ CSRF em formulários
✅ Error handling consistente
✅ GTM ready
✅ Context-aware user detection

### Melhorias Identificadas

1. **Consolidar páginas duplicadas:**
   - youtube.php, instagram.php, etc. → Criar 1 página genérica "canal.php"
   - Reduzir de 25 para ~10 páginas

2. **Component-based architecture:**
   - Mover lógica repetida para /frontend/components/
   - Criar componentes reutilizáveis (CardList, ChannelCard, etc.)

3. **Layout inheritance:**
   - Criar sistema de extends (Laravel Blade-style)
   - Evitar duplicação de estrutura HTML

4. **Asset versioning:**
   - Adicionar ?v={VERSION} nos assets
   - Cache busting automático

5. **Lazy loading:**
   - Lucide Icons: carregar sob demanda
   - Remover unpkg.com (usar bundle local)

6. **SEO:**
   - Meta description dinâmica (campo no banco)
   - Open Graph tags
   - Twitter Cards

7. **Pasta sem título/:**
   - Deletar duplicados

---

## 📝 NOTA FINAL: 8/10

Frontend **bem estruturado** e **organizado**, com padrão de templates reutilizáveis e integração profunda com o Page Builder.

**Destaques:**
- MenuBuilder integration perfeita
- Dark mode sem flash
- Context-aware user detection
- GTM ready

**Pontos de atenção:**
- 60% páginas são APP-SPECIFIC (nomes de canais hardcoded)
- Muitas páginas duplicadas (mesma estrutura, só muda título)
- Pasta "sem título/" com lixo
- Falta componentes isolados (/components/ vazia)
