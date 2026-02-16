# Melhorias Futuras - AEGIS Framework

**Última atualização:** 2026-02-08
**Versão atual do SEO:** 1.0.0 (básico funcional)
**Avaliação:** 6.5/10 uso geral, 8/10 no contexto AEGIS

---

## Sistema SEO

### 🎯 Resumo Executivo

**Estado atual:** Sistema básico bem executado que resolve 80% dos casos de uso.

**Principais gaps:**
- Análise de conteúdo inexistente (só conta caracteres)
- Zero inteligência de SEO (não sugere melhorias)
- Sem preview visual
- Não integra com sitemap/robots.txt
- Sem análise competitiva

**Roadmap sugerido:**
- **Curto prazo:** Preview visual, detecção de duplicatas, clickbait
- **Médio prazo:** Sugestões inteligentes, sitemap automático
- **Longo prazo:** Integração Google Search Console, A/B testing

---

## 🔥 Curto Prazo (Impacto Alto, Esforço Baixo)

### 1. Preview Visual de SEO

**Prioridade:** ALTA
**Impacto:** ALTO (usuário vê exatamente como ficará)
**Esforço:** BAIXO (apenas frontend, sem backend)

**Problema:** Usuário preenche campos às cegas, sem ver resultado final.

**Solução:**
Adicionar 3 previews em tempo real no formulário:

1. **Google Search Preview**
   ```
   [🔍] Título SEO - Nome do Site
        https://seusite.com/pagina › slug-da-pagina
        Descrição SEO aparece aqui truncada em 160 caracteres...
   ```

2. **Facebook/WhatsApp Preview**
   ```
   ┌─────────────────────────────┐
   │ [Imagem OG 1200x630]        │
   ├─────────────────────────────┤
   │ OG Title                    │
   │ OG Description              │
   │ seusite.com                 │
   └─────────────────────────────┘
   ```

3. **Twitter/X Preview**
   ```
   ┌─────────────────────────────┐
   │ [Imagem 1200x630]           │
   ├─────────────────────────────┤
   │ Twitter Title               │
   │ Twitter Description         │
   │ 🔗 seusite.com              │
   └─────────────────────────────┘
   ```

**Implementação:**
```javascript
// admin/views/pages/edit.php
function updatePreviews() {
  const title = document.getElementById('seo_title').value;
  const desc = document.getElementById('seo_description').value;

  // Google Preview
  document.getElementById('preview-google-title').textContent =
    title + ' - ' + SITE_NAME;
  document.getElementById('preview-google-desc').textContent =
    desc.substring(0, 160) + (desc.length > 160 ? '...' : '');

  // Facebook Preview (usa fallback se OG vazio)
  const ogTitle = document.getElementById('seo_og_title').value || title;
  document.getElementById('preview-fb-title').textContent = ogTitle;

  // Twitter Preview (usa fallback se Twitter vazio)
  const twTitle = document.getElementById('seo_twitter_title').value || ogTitle;
  document.getElementById('preview-tw-title').textContent = twTitle;
}
```

**Arquivos a modificar:**
- `admin/views/pages/create.php` - Adicionar HTML dos previews
- `admin/views/pages/edit.php` - Adicionar HTML dos previews
- `assets/sass/admin/modules/_m-pagebase.sass` - Estilos dos previews

**Benefício:** Usuário vê imediatamente se texto está cortado, se imagem ficou boa, etc.

---

### 2. Detecção de Title Duplicado

**Prioridade:** ALTA
**Impacto:** ALTO (grave erro de SEO)
**Esforço:** BAIXO (uma query SQL)

**Problema:** Páginas com mesmo title competem entre si no Google (canibalização).

**Solução:**
Ao salvar, verificar se já existe outra página com mesmo `seo_title`:

```php
// PagesController.php - método store/update
$duplicates = $this->db()->query(
    "SELECT id, title, slug FROM pages
     WHERE seo_title = ? AND id != ? AND ativo = 1",
    [$seoTitle, $pageId]
);

if (!empty($duplicates)) {
    $this->error("⚠️ SEO Title duplicado! Já usado em: " . $duplicates[0]['title']);
    // Não bloquear, apenas avisar
}
```

**Interface:**
Mostrar aviso vermelho abaixo do campo:
```
❌ Este title já é usado em: "Página X" (/slug-x)
```

**Benefício:** Evita erro crítico de SEO que prejudica ranking.

---

### 3. Detecção de Clickbait e ALL CAPS

**Prioridade:** MÉDIA
**Impacto:** MÉDIO (melhora qualidade)
**Esforço:** BAIXO (regex simples)

**Problema:** Títulos sensacionalistas prejudicam credibilidade.

**Solução:**
Adicionar análise em `SEOAnalyzer.php`:

```php
private static function detectClickbait($title) {
    $issues = [];

    // ALL CAPS excessivo
    $upperWords = preg_match_all('/\b[A-Z]{4,}\b/', $title);
    if ($upperWords > 1) {
        $issues[] = 'Evite PALAVRAS TODAS MAIÚSCULAS (exceto siglas)';
    }

    // Caracteres especiais
    if (preg_match('/[!?]{2,}/', $title)) {
        $issues[] = 'Evite múltiplos !!! ou ???';
    }

    // Palavras clickbait
    $clickbait = [
        'INCRÍVEL', 'SEGREDO', 'VOCÊ NÃO VAI ACREDITAR',
        'URGENTE', 'ÚLTIMO DIA', 'CHOCANTE', 'BIZARRO'
    ];

    foreach ($clickbait as $word) {
        if (stripos($title, $word) !== false) {
            $issues[] = "Palavra sensacionalista detectada: '{$word}'";
        }
    }

    // Keyword stuffing
    $words = str_word_count(strtolower($title), 1, 'ÀÁÃÂÇÉÊÍÓÔÕÚàáãâçéêíóôõú');
    $wordCount = array_count_values($words);
    foreach ($wordCount as $word => $count) {
        if ($count >= 3 && strlen($word) > 3) {
            $issues[] = "Palavra '{$word}' repetida {$count}x (spam?)";
        }
    }

    return $issues;
}
```

**Interface:**
Mostrar avisos em amarelo:
```
⚠️ Palavra 'INCRÍVEL' detectada (clickbait)
⚠️ Múltiplos !!! detectados
```

**Benefício:** Incentiva títulos profissionais, não spam.

---

## 📅 Médio Prazo (Importante, Requer Planejamento)

### 4. Sugestões Inteligentes de Melhoria

**Prioridade:** MÉDIA
**Impacto:** ALTO (ensina boas práticas)
**Esforço:** MÉDIO (requer análise contextual)

**Problema:** Sistema só diz "está ruim", mas não ensina como melhorar.

**Solução:**
Adicionar sistema de sugestões automáticas:

```php
// SEOAnalyzer.php
public static function getSuggestions($data) {
    $suggestions = [];

    $title = $data['seo_title'] ?? '';
    $desc = $data['seo_description'] ?? '';

    // Sugestão: Palavra-chave no início
    $firstWord = strtok($title, ' ');
    if (strlen($firstWord) < 4) {
        $suggestions[] = [
            'type' => 'info',
            'field' => 'title',
            'message' => 'Coloque a palavra-chave principal no início do título'
        ];
    }

    // Sugestão: Call-to-action na description
    $ctas = ['descubra', 'aprenda', 'veja', 'conheça', 'saiba'];
    $hasCTA = false;
    foreach ($ctas as $cta) {
        if (stripos($desc, $cta) !== false) {
            $hasCTA = true;
            break;
        }
    }
    if (!$hasCTA) {
        $suggestions[] = [
            'type' => 'tip',
            'field' => 'description',
            'message' => 'Considere adicionar um call-to-action (descubra, aprenda, veja...)'
        ];
    }

    // Sugestão: Números atraem cliques
    if (!preg_match('/\d+/', $title)) {
        $suggestions[] = [
            'type' => 'tip',
            'field' => 'title',
            'message' => 'Números no título aumentam CTR (ex: "5 Dicas", "Guia 2026")'
        ];
    }

    // Sugestão: Perguntas funcionam bem
    if (preg_match('/^(como|o que|por que|quando|onde)/i', $title)) {
        $suggestions[] = [
            'type' => 'success',
            'field' => 'title',
            'message' => '✓ Título em formato de pergunta (bom para CTR)'
        ];
    }

    return $suggestions;
}
```

**Interface:**
Mostrar caixa de sugestões abaixo do formulário:
```
💡 Sugestões de Melhoria:
✓ Título em formato de pergunta (bom para CTR)
ℹ️ Coloque a palavra-chave principal no início
💡 Considere adicionar números no título (ex: "5 Dicas")
```

---

### 5. Geração Automática de Sitemap.xml

**Prioridade:** ALTA
**Impacto:** ALTO (essencial para SEO)
**Esforço:** MÉDIO (lógica + cache)

**Problema:** Google precisa do sitemap para indexar páginas corretamente.

**Solução:**
Criar controller para gerar sitemap dinamicamente:

```php
// public/controllers/SitemapController.php
class SitemapController extends BaseController {
    public function index() {
        header('Content-Type: application/xml; charset=utf-8');

        // Buscar todas as páginas ativas que não sejam noindex
        $pages = $this->db()->query(
            "SELECT slug, seo_canonical_url, updated_at, seo_robots
             FROM pages
             WHERE ativo = 1
             AND (seo_robots NOT LIKE '%noindex%' OR seo_robots IS NULL)
             ORDER BY updated_at DESC"
        );

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($pages as $page) {
            $url = !empty($page['seo_canonical_url'])
                ? $page['seo_canonical_url']
                : url('/' . $page['slug']);

            echo '<url>';
            echo '<loc>' . htmlspecialchars($url) . '</loc>';
            echo '<lastmod>' . date('c', strtotime($page['updated_at'])) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.8</priority>';
            echo '</url>';
        }

        echo '</urlset>';
        exit;
    }
}
```

**Rota:**
```php
// routes.php
$router->get('/sitemap.xml', 'SitemapController@index');
```

**Benefício:** Google indexa todas as páginas automaticamente.

---

### 6. Histórico de Mudanças SEO

**Prioridade:** BAIXA
**Impacto:** MÉDIO (útil para auditoria)
**Esforço:** MÉDIO (nova tabela + UI)

**Problema:** Não dá pra saber quem mudou SEO e quando.

**Solução:**
Criar tabela `seo_history`:

```sql
CREATE TABLE seo_history (
  id INT PRIMARY KEY AUTO_INCREMENT,
  page_id INT NOT NULL,
  user_id INT,
  field VARCHAR(50),
  old_value TEXT,
  new_value TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

Registrar mudanças no `PagesController::update()`:

```php
private function logSEOChange($pageId, $field, $oldValue, $newValue) {
    if ($oldValue !== $newValue) {
        $this->db()->insert('seo_history', [
            'page_id' => $pageId,
            'user_id' => Auth::id(),
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue
        ]);
    }
}
```

**Interface:**
Adicionar aba "Histórico SEO" na página de edição mostrando timeline.

---

### 7. Readability Score (Legibilidade)

**Prioridade:** BAIXA
**Impacto:** MÉDIO (melhora qualidade)
**Esforço:** ALTO (algoritmo complexo)

**Problema:** Textos muito complexos afastam leitores.

**Solução:**
Implementar Flesch Reading Ease Score:

```php
// SEOAnalyzer.php
private static function readabilityScore($text) {
    $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $words = str_word_count($text);
    $syllables = self::countSyllables($text);

    $avgSentenceLength = $words / count($sentences);
    $avgSyllablesPerWord = $syllables / $words;

    // Flesch Reading Ease
    $score = 206.835 - (1.015 * $avgSentenceLength) - (84.6 * $avgSyllablesPerWord);

    // Adaptar para português
    if ($score > 80) return 'Muito fácil';
    if ($score > 60) return 'Fácil';
    if ($score > 40) return 'Médio';
    if ($score > 20) return 'Difícil';
    return 'Muito difícil';
}
```

**Benefício:** Incentiva descrições claras e acessíveis.

---

## 🚀 Longo Prazo (Avançado, Requer Investimento)

### 8. Integração com Google Search Console API

**Prioridade:** MÉDIA
**Impacto:** ALTO (dados reais de performance)
**Esforço:** ALTO (OAuth + API)

**Problema:** Não sabemos como páginas performam no Google.

**Solução:**
Integrar Google Search Console API para mostrar:
- Impressões e cliques reais
- CTR (Click-Through Rate)
- Posição média no Google
- Queries que levam à página

**Implementação:**
1. OAuth 2.0 para autenticar com Google
2. Endpoint para buscar dados: `SearchConsole::getPageStats($url)`
3. Exibir na página de edição:
   ```
   📊 Performance Google (últimos 30 dias):
   - Impressões: 1.234
   - Cliques: 98 (CTR: 7.9%)
   - Posição média: 8.3
   ```

**Benefício:** Decisões baseadas em dados reais, não achismos.

---

### 9. A/B Testing de Titles e Descriptions

**Prioridade:** BAIXA
**Impacto:** ALTO (otimização baseada em dados)
**Esforço:** MUITO ALTO (infraestrutura complexa)

**Problema:** Não sabemos qual versão performa melhor.

**Solução:**
Sistema de testes A/B para SEO:

```sql
CREATE TABLE seo_ab_tests (
  id INT PRIMARY KEY AUTO_INCREMENT,
  page_id INT,
  variant_a_title VARCHAR(70),
  variant_b_title VARCHAR(70),
  variant_a_clicks INT DEFAULT 0,
  variant_b_clicks INT DEFAULT 0,
  variant_a_impressions INT DEFAULT 0,
  variant_b_impressions INT DEFAULT 0,
  winner VARCHAR(1), -- 'A' ou 'B'
  status ENUM('running', 'completed') DEFAULT 'running',
  created_at TIMESTAMP,
  completed_at TIMESTAMP
);
```

**Lógica:**
1. Criar 2 versões (A e B) de title/description
2. Alternar entre elas aleatoriamente (50/50)
3. Medir CTR de cada variante via Search Console
4. Após amostra significativa, declarar vencedor
5. Aplicar vencedor permanentemente

**Benefício:** Otimização científica de SEO.

---

### 10. Análise de Concorrentes

**Prioridade:** BAIXA
**Impacto:** MÉDIO (insights competitivos)
**Esforço:** MUITO ALTO (scraping + análise)

**Problema:** Não sabemos como concorrentes otimizam.

**Solução:**
Ferramenta para analisar concorrentes:

**Interface:**
```
🔍 Analisar Concorrente:
URL: [https://concorrente.com/pagina-similar]
[Analisar]

Resultados:
- Title: "Título do Concorrente" (58 chars)
- Description: "Descrição..." (155 chars)
- H1: "Título Principal"
- Palavras-chave detectadas: produto, serviço, qualidade
- Backlinks: 234
- Domain Authority: 45
```

**Implementação:**
1. Scraping de meta tags via cURL
2. Análise de heading tags (H1, H2, H3)
3. Extração de palavras-chave via TF-IDF
4. Integração com Moz API (Domain Authority)

**Benefício:** Insights para superar concorrência.

---

### 11. Gestão de Robots.txt

**Prioridade:** MÉDIA
**Impacto:** MÉDIO (controle de crawlers)
**Esforço:** BAIXO (editor simples)

**Problema:** robots.txt é hardcoded, difícil de gerenciar.

**Solução:**
Interface admin para editar robots.txt:

```php
// admin/controllers/RobotsController.php
public function index() {
    $robotsPath = ROOT_PATH . 'public/robots.txt';
    $content = file_exists($robotsPath)
        ? file_get_contents($robotsPath)
        : $this->getDefaultRobots();

    return $this->view('settings/robots', ['content' => $content]);
}

public function update() {
    $content = $_POST['robots_content'];

    // Validar sintaxe básica
    if (strpos($content, 'User-agent:') === false) {
        $this->error('Robots.txt inválido');
        return;
    }

    file_put_contents(ROOT_PATH . 'public/robots.txt', $content);
    $this->success('Robots.txt atualizado');
}

private function getDefaultRobots() {
    return "User-agent: *\n" .
           "Disallow: /admin/\n" .
           "Disallow: /api/\n" .
           "Sitemap: " . url('/sitemap.xml');
}
```

**Interface:**
Editor de código com syntax highlighting para robots.txt.

---

### 12. Internal/External Links Analyzer

**Prioridade:** BAIXA
**Impacto:** MÉDIO (melhora link juice)
**Esforço:** MÉDIO (parser HTML)

**Problema:** Não sabemos quantos links internos/externos cada página tem.

**Solução:**
Analisar links em páginas editáveis:

```php
// SEOAnalyzer.php
public static function analyzeLinks($html) {
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $links = $doc->getElementsByTagName('a');

    $internal = 0;
    $external = 0;
    $nofollow = 0;

    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        $rel = $link->getAttribute('rel');

        if (strpos($href, APP_URL) !== false || $href[0] === '/') {
            $internal++;
        } else {
            $external++;
        }

        if (strpos($rel, 'nofollow') !== false) {
            $nofollow++;
        }
    }

    return [
        'internal' => $internal,
        'external' => $external,
        'nofollow' => $nofollow,
        'total' => $internal + $external
    ];
}
```

**Interface:**
Mostrar na página de edição:
```
🔗 Análise de Links:
- Links internos: 12
- Links externos: 3 (2 nofollow)
- Densidade: 2.3% (ideal: 2-5%)
```

**Benefício:** Otimizar link juice e relevância.

---

### 13. Schema.org Dinâmico por Tipo de Página

**Prioridade:** BAIXA
**Impacto:** MÉDIO (rich snippets)
**Esforço:** MÉDIO (templates JSON-LD)

**Problema:** Atualmente só gera schema genérico `WebPage`.

**Solução:**
Schemas específicos por tipo de conteúdo:

**Article (Blog):**
```json
{
  "@type": "Article",
  "headline": "Título",
  "author": {"@type": "Person", "name": "Autor"},
  "datePublished": "2026-02-08",
  "image": "url-imagem"
}
```

**Product (E-commerce):**
```json
{
  "@type": "Product",
  "name": "Nome do Produto",
  "offers": {
    "@type": "Offer",
    "price": "99.90",
    "priceCurrency": "BRL"
  }
}
```

**Event:**
```json
{
  "@type": "Event",
  "name": "Nome do Evento",
  "startDate": "2026-03-15",
  "location": {"@type": "Place", "name": "Local"}
}
```

**Implementação:**
Adicionar campo "Tipo de Schema" no formulário + campos dinâmicos.

---

## 📋 Backlog (Ideias para Considerar)

### 14. Imagem OG: Crop/Resize Automático

**Problema:** Usuário envia imagem errada, precisa redimensionar manualmente.

**Solução:**
- Crop automático para 1200x630 (centro da imagem)
- Editor inline para ajustar crop
- Compressão automática (WebP)

---

### 15. Validação de Structured Data

**Problema:** JSON-LD pode ter erros de sintaxe.

**Solução:**
Integrar Google Rich Results Test API para validar automaticamente.

---

### 16. SEO Score por Página no Listado

**Problema:** Precisa abrir cada página pra ver score.

**Solução:**
Mostrar badge de score na lista de páginas:
```
Página 1    [A+ 90]
Página 2    [C 65]
Página 3    [F 20] ⚠️
```

---

### 17. Exportação de Dados SEO

**Problema:** Dados SEO presos no sistema.

**Solução:**
Exportar CSV/Excel com todos os dados SEO para análise externa.

---

### 18. Multi-idioma (hreflang)

**Problema:** Sites multi-idioma precisam de tags hreflang.

**Solução:**
```html
<link rel="alternate" hreflang="pt-br" href="https://site.com/pt/pagina">
<link rel="alternate" hreflang="en" href="https://site.com/en/page">
```

---

## 📊 Priorização Sugerida

### Implementar PRIMEIRO (Quick Wins):
1. ✅ Preview Visual (alto impacto, baixo esforço)
2. ✅ Detecção Title Duplicado (crítico para SEO)
3. ✅ Detecção Clickbait/ALL CAPS (melhora qualidade)

### Implementar SEGUNDO (Essenciais):
4. Sitemap.xml Automático (essencial para Google)
5. Sugestões Inteligentes (educativo)

### Implementar TERCEIRO (Nice to Have):
6. Histórico de Mudanças (auditoria)
7. Readability Score (qualidade)
8. Gestão Robots.txt (controle)

### Considerar FUTURO (Se houver demanda):
9. Google Search Console (requer OAuth)
10. A/B Testing (complexo)
11. Análise Concorrentes (scraping)
12. Internal Links Analyzer
13. Schema Dinâmico

### Backlog (Avaliar depois):
14-18. Crop automático, validação structured data, score no listado, export, hreflang

---

## 🎯 Roadmap Executivo

**Q1 2026 (se priorizar SEO):**
- Preview Visual
- Title Duplicado
- Clickbait Detection
- Sitemap.xml

**Q2 2026:**
- Sugestões Inteligentes
- Histórico SEO
- Robots.txt Manager

**Q3+ 2026:**
- Avaliar demanda para features avançadas
- Google Search Console se houver necessidade real
- A/B Testing apenas se site grande

---

## 💭 Notas Finais

**Filosofia:** Implementar conforme demanda real, não porque "seria legal ter".

**Prioridade atual:** Sistema básico funciona bem. Melhorias devem vir de:
1. Feedback de usuários reais
2. Problemas identificados no uso
3. Necessidades específicas de projetos

**Evitar:** Feature creep. Não transformar em Yoast clone se não houver necessidade.

---

**Última atualização:** 2026-02-08
**Responsável:** Fábio Chezzi
**Revisão sugerida:** Trimestral (avaliar se features foram realmente úteis)
