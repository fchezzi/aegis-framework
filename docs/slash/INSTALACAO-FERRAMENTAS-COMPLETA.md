# 🎯 LISTA COMPLETA - Instalação de Ferramentas (Qualidade + SEO)

**Data:** 2026-02-14
**Versão AEGIS:** 17.3.6
**Tempo Total:** 13h50min-14h50min
**Aplicação:** Princípio Pareto 80/20

---

## 📋 TIER 1: ESSENCIAL (3h50min - 80% do valor)

```
[ ] 1.  HTTPS/SSL              →  2min   (verificação)
[ ] 2.  GTM/GA4                →  3min   (validação)
[ ] 3.  robots.txt             →  5min   (criar)
[ ] 4.  UptimeRobot            →  5min   (configurar)
[ ] 5.  Sitemap.xml            → 30min   (desenvolver)
[ ] 6.  Backup Automático n8n  →  1h     (workflow)
[ ] 7.  Search Console API     →  2h     (integração completa)
[ ] 8.  PHPStan                → 10min   (instalar)
```

**TOTAL TIER 1:** 3h50min | **Impacto:** 80%

---

## 📋 TIER 2: IMPORTANTE (6h-7h - +15% do valor)

```
[ ] 9.  Google Analytics API    →  4-5h  (integração completa)
[ ] 10. Rate Limiting           →  2h    (classe + implementação)
[ ] 11. Security Headers        → 40min  (código + testes)
```

**TOTAL TIER 2:** 6h-7h | **Impacto:** +15%

---

## 📋 TIER 3: BOM TER (4h - +5% do valor)

```
[ ] 12. PHP_CodeSniffer         → 15min  (qualidade código)
[ ] 13. Logger Melhorado        →  3h    (logs estruturados MySQL)
[ ] 14. Microsoft Clarity       → 10min  (heatmaps)
[ ] 15. Pa11y                   → 30min  (acessibilidade)
```

**TOTAL TIER 3:** 4h | **Impacto:** +5%

---

## 🎯 ORDEM DE EXECUÇÃO DETALHADA

### **TIER 1: HOJE (3h50min)**

#### **1. HTTPS/SSL** (2min) - VERIFICAÇÃO

**Objetivo:** Confirmar que site está com certificado SSL ativo

**Passos:**
1. Abrir navegador
2. Acessar: `https://seusite.com`
3. Verificar cadeado verde no navegador
4. Clicar no cadeado → Certificado válido?

**Se não tiver HTTPS:**
- PARAR TUDO
- Instalar Let's Encrypt (grátis)
- Configurar redirect HTTP → HTTPS

**Validação:**
- [ ] URL começa com `https://`
- [ ] Navegador não mostra "Não seguro"
- [ ] Certificado válido (não expirado)

---

#### **2. GTM/GA4** (3min) - VALIDAÇÃO

**Objetivo:** Confirmar que Google Tag Manager e Analytics funcionam

**Passos:**
1. Abrir site no navegador
2. F12 → Console
3. Procurar mensagens GTM (sem erros)
4. Google Analytics → Relatórios → Tempo Real
5. Navegar no site e ver sessão aparecendo

**Se não funcionar:**
- Verificar código GTM em `frontend/includes/_head.php`
- Confirmar GTM-XXXXX correto
- Testar GA4 conectado no painel GTM

**Validação:**
- [ ] GTM carrega sem erros (console limpo)
- [ ] GA4 mostra visita em Tempo Real
- [ ] dataLayer funcionando

---

#### **3. robots.txt** (5min) - CRIAR

**Objetivo:** Criar arquivo robots.txt para controlar indexação Google

**Arquivo:** `/public/robots.txt`

**Conteúdo:**
```txt
User-agent: *
Disallow: /admin/
Disallow: /api/
Disallow: /storage/logs/
Disallow: /storage/cache/
Allow: /storage/uploads/

Sitemap: https://seusite.com/sitemap.xml
```

**Passos:**
1. Criar arquivo `/public/robots.txt`
2. Colar conteúdo acima
3. Ajustar URL do sitemap (domínio correto)

**Validação:**
- [ ] Acessar `https://seusite.com/robots.txt`
- [ ] Arquivo aparece corretamente
- [ ] Testar no Google: https://www.google.com/webmasters/tools/robots-testing-tool

---

#### **4. UptimeRobot** (5min) - CONFIGURAR

**Objetivo:** Monitoramento 24/7, alerta se site cair

**Passos:**
1. Acessar: https://uptimerobot.com
2. **Sign Up** (grátis, 50 monitores)
3. **Add New Monitor:**
   - Monitor Type: `HTTP(s)`
   - Friendly Name: `AEGIS - [Nome do Projeto]`
   - URL: `https://seusite.com`
   - Monitoring Interval: `5 minutes`
4. **Alert Contacts:**
   - Email: seu@email.com
   - Telegram (opcional): conectar bot
5. **Create Monitor**

**Validação:**
- [ ] Monitor ativo (status verde)
- [ ] Testar: pausar servidor → alerta chega em 5min
- [ ] Email de alerta configurado

---

#### **5. Sitemap.xml Automático** (30min) - DESENVOLVER

**Objetivo:** Sitemap gerado do banco, Google indexa tudo

**Arquivo:** `/public/sitemap.xml.php`

**Código completo:**
```php
<?php
require_once '../bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$db = DB::connect();

// Páginas públicas
$pages = $db->query("
    SELECT slug, updated_at
    FROM pages
    WHERE ativo = 1
    AND (seo_robots NOT LIKE '%noindex%' OR seo_robots IS NULL)
    ORDER BY updated_at DESC
")->fetchAll();

// Módulos públicos
$modules = [];
foreach (glob(ROOT_PATH . 'modules/*/module.json') as $file) {
    $config = json_decode(file_get_contents($file), true);
    if ($config['public'] ?? false) {
        $modules[] = [
            'slug' => basename(dirname($file)),
            'updated_at' => date('Y-m-d', filemtime($file))
        ];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Homepage
echo "<url>";
echo "<loc>" . htmlspecialchars(APP_URL) . "</loc>";
echo "<priority>1.0</priority>";
echo "<changefreq>daily</changefreq>";
echo "</url>";

// Páginas
foreach ($pages as $page) {
    echo "<url>";
    echo "<loc>" . htmlspecialchars(url('/' . $page['slug'])) . "</loc>";
    echo "<lastmod>" . date('Y-m-d', strtotime($page['updated_at'])) . "</lastmod>";
    echo "<priority>0.8</priority>";
    echo "<changefreq>weekly</changefreq>";
    echo "</url>";
}

// Módulos
foreach ($modules as $module) {
    echo "<url>";
    echo "<loc>" . htmlspecialchars(url('/' . $module['slug'])) . "</loc>";
    echo "<lastmod>{$module['updated_at']}</lastmod>";
    echo "<priority>0.7</priority>";
    echo "<changefreq>weekly</changefreq>";
    echo "</url>";
}

echo '</urlset>';
```

**Passos:**
1. Criar arquivo `/public/sitemap.xml.php`
2. Colar código acima
3. Testar: `https://seusite.com/sitemap.xml.php`
4. Validar XML: https://www.xml-sitemaps.com/validate-xml-sitemap.html

**Opcional - Rewrite rule (.htaccess):**
```apache
RewriteRule ^sitemap\.xml$ sitemap.xml.php [L]
```

**Validação:**
- [ ] Sitemap acessível e válido (XML bem formado)
- [ ] Todas páginas públicas listadas
- [ ] Módulos públicos listados
- [ ] URLs completas (com https://)

---

#### **6. Backup Automático n8n** (1h) - WORKFLOW

**Objetivo:** Backup diário MySQL + arquivos, rotação 30 dias

**Workflow n8n:**

**Nodes:**
1. **Cron** (Schedule Trigger)
   - Mode: Every Day
   - Hour: 3
   - Minute: 0

2. **MySQL Dump** (Execute Command)
   ```bash
   mysqldump -u [USER] -p'[PASS]' [DATABASE] | gzip > /path/backups/db-$(date +\%Y\%m\%d).sql.gz
   ```

3. **Tar Arquivos** (Execute Command)
   ```bash
   tar -czf /path/backups/files-$(date +\%Y\%m\%d).tar.gz \
     /Users/fabiochezzi/Documents/websites/aegis \
     --exclude='storage/logs' \
     --exclude='storage/cache' \
     --exclude='node_modules'
   ```

4. **Upload Google Drive** (Google Drive node)
   - File: `/path/backups/db-*.sql.gz`
   - Folder: `AEGIS Backups`

5. **Upload Files** (Google Drive node)
   - File: `/path/backups/files-*.tar.gz`
   - Folder: `AEGIS Backups`

6. **Cleanup** (Execute Command)
   ```bash
   find /path/backups -name "*.gz" -mtime +30 -delete
   ```

7. **Notificação Sucesso** (Send Email / Telegram)
   - Mensagem: "✅ Backup AEGIS concluído - [data]"

8. **Notificação Erro** (Send Email - error workflow)
   - Mensagem: "❌ BACKUP FALHOU - Verificar urgente!"

**Validação:**
- [ ] Workflow criado e ativo
- [ ] Executar manual → arquivos criados
- [ ] Upload Google Drive OK
- [ ] **CRÍTICO:** Testar restauração (criar DB teste)
- [ ] Rotação 30 dias funciona

---

#### **7. Google Search Console API** (2h) - INTEGRAÇÃO

**Objetivo:** Posições Google, queries, Core Web Vitals

**Etapa 1: Service Account (30min)**

1. Google Cloud Console: https://console.cloud.google.com
2. Criar projeto "AEGIS SEO"
3. Ativar APIs:
   - Search Console API
   - PageSpeed Insights API
4. Criar Service Account:
   - Nome: "aegis-service-account"
   - Baixar JSON de credenciais
5. Adicionar Service Account no Search Console:
   - https://search.google.com/search-console
   - Settings → Users → Add User
   - Email: `aegis-service-account@[project-id].iam.gserviceaccount.com`
   - Permission: Owner

**Etapa 2: Tabelas MySQL (15min)**

```sql
-- Queries (palavras-chave)
CREATE TABLE gsc_queries (
    id CHAR(36) PRIMARY KEY,
    query VARCHAR(512) NOT NULL,
    date DATE NOT NULL,
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_query (query(191)),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pages
CREATE TABLE gsc_pages (
    id CHAR(36) PRIMARY KEY,
    page_url VARCHAR(512) NOT NULL,
    date DATE NOT NULL,
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_url (page_url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Core Web Vitals
CREATE TABLE gsc_vitals (
    id CHAR(36) PRIMARY KEY,
    page_url VARCHAR(512) NOT NULL,
    metric_type ENUM('LCP', 'FID', 'CLS') NOT NULL,
    good_percent DECIMAL(5,2) DEFAULT 0,
    needs_improvement_percent DECIMAL(5,2) DEFAULT 0,
    poor_percent DECIMAL(5,2) DEFAULT 0,
    device ENUM('DESKTOP', 'MOBILE') NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_url (page_url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Errors (404s, etc)
CREATE TABLE gsc_errors (
    id CHAR(36) PRIMARY KEY,
    page_url VARCHAR(512) NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    severity ENUM('ERROR', 'WARNING') NOT NULL,
    detected_at DATE NOT NULL,
    resolved BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_detected (detected_at),
    INDEX idx_resolved (resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Etapa 3: Classe PHP (1h)**

**Arquivo:** `/core/GoogleSearchConsole.php`

```php
<?php
/**
 * GoogleSearchConsole - Integração com API do Google Search Console
 *
 * @version 1.0.0
 */

class GoogleSearchConsole {

    private $client;
    private $service;
    private $siteUrl;

    public function __construct() {
        $credentialsPath = ROOT_PATH . 'config/google-service-account.json';

        if (!file_exists($credentialsPath)) {
            throw new Exception('Google Service Account credentials not found');
        }

        // Usar biblioteca oficial Google
        // composer require google/apiclient
        $this->client = new Google_Client();
        $this->client->setAuthConfig($credentialsPath);
        $this->client->addScope(Google_Service_SearchConsole::WEBMASTERS_READONLY);

        $this->service = new Google_Service_SearchConsole($this->client);
        $this->siteUrl = APP_URL; // De _config.php
    }

    /**
     * Sincronizar queries (últimos 7 dias)
     */
    public function syncQueries($startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $endDate ?? date('Y-m-d', strtotime('-1 day'));

        $request = new Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query', 'date']);
        $request->setRowLimit(1000);

        $response = $this->service->searchanalytics->query($this->siteUrl, $request);

        $db = DB::connect();

        foreach ($response->getRows() as $row) {
            $query = $row->getKeys()[0];
            $date = $row->getKeys()[1];
            $clicks = $row->getClicks();
            $impressions = $row->getImpressions();
            $ctr = $row->getCtr();
            $position = $row->getPosition();

            // Insert ou update
            $stmt = $db->prepare("
                INSERT INTO gsc_queries (id, query, date, clicks, impressions, ctr, position)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    clicks = VALUES(clicks),
                    impressions = VALUES(impressions),
                    ctr = VALUES(ctr),
                    position = VALUES(position)
            ");

            $id = Core::generateUUID();
            $stmt->execute([$id, $query, $date, $clicks, $impressions, $ctr, $position]);
        }

        return count($response->getRows());
    }

    /**
     * Sincronizar páginas
     */
    public function syncPages($startDate = null, $endDate = null) {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $endDate ?? date('Y-m-d', strtotime('-1 day'));

        $request = new Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['page', 'date']);
        $request->setRowLimit(1000);

        $response = $this->service->searchanalytics->query($this->siteUrl, $request);

        $db = DB::connect();

        foreach ($response->getRows() as $row) {
            $pageUrl = $row->getKeys()[0];
            $date = $row->getKeys()[1];
            $clicks = $row->getClicks();
            $impressions = $row->getImpressions();
            $ctr = $row->getCtr();
            $position = $row->getPosition();

            $stmt = $db->prepare("
                INSERT INTO gsc_pages (id, page_url, date, clicks, impressions, ctr, position)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    clicks = VALUES(clicks),
                    impressions = VALUES(impressions),
                    ctr = VALUES(ctr),
                    position = VALUES(position)
            ");

            $id = Core::generateUUID();
            $stmt->execute([$id, $pageUrl, $date, $clicks, $impressions, $ctr, $position]);
        }

        return count($response->getRows());
    }
}
```

**Etapa 4: API Endpoint (15min)**

**Arquivo:** `/admin/api/sync-gsc.php`

```php
<?php
require_once '../../bootstrap.php';

Auth::require(); // Só admin pode sincronizar

try {
    $gsc = new GoogleSearchConsole();

    $queriesCount = $gsc->syncQueries();
    $pagesCount = $gsc->syncPages();

    echo json_encode([
        'success' => true,
        'queries_synced' => $queriesCount,
        'pages_synced' => $pagesCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

**Validação:**
- [ ] Service Account criado
- [ ] Credenciais JSON salvas
- [ ] Tabelas MySQL criadas
- [ ] Classe GoogleSearchConsole funciona
- [ ] API endpoint retorna dados
- [ ] Dados salvos no banco

---

#### **8. PHPStan** (10min) - INSTALAR

**Objetivo:** Análise estática, detectar bugs antes de acontecerem

**Instalação:**

```bash
cd /Users/fabiochezzi/Documents/websites/aegis
composer require --dev phpstan/phpstan
```

**Configuração:**

**Arquivo:** `/phpstan.neon`

```neon
parameters:
    level: 6
    paths:
        - core
        - admin
        - modules
        - public
    excludePaths:
        - */vendor/*
        - */storage/*
    ignoreErrors:
        - '#Call to an undefined method PDO::#'
```

**Rodar análise:**

```bash
vendor/bin/phpstan analyse
```

**Integrar git hook (opcional):**

**Arquivo:** `.git/hooks/pre-commit`

```bash
#!/bin/bash
vendor/bin/phpstan analyse --error-format=table
if [ $? -ne 0 ]; then
    echo "❌ PHPStan encontrou erros. Commit bloqueado."
    exit 1
fi
```

**Validação:**
- [ ] PHPStan instalado
- [ ] Roda sem erro fatal
- [ ] Identifica problemas reais
- [ ] Level 6 funcionando

---

## 📊 RESUMO TIER 1

| # | Item | Tempo | Status |
|---|------|-------|--------|
| 1 | HTTPS/SSL | 2min | [ ] |
| 2 | GTM/GA4 | 3min | [ ] |
| 3 | robots.txt | 5min | [ ] |
| 4 | UptimeRobot | 5min | [ ] |
| 5 | Sitemap.xml | 30min | [ ] |
| 6 | Backup n8n | 1h | [ ] |
| 7 | Search Console API | 2h | [ ] |
| 8 | PHPStan | 10min | [ ] |

**TOTAL:** 3h50min

---

## 🔄 TIER 2 & 3 (detalhes completos disponíveis quando necessário)

**TIER 2 (6h-7h):**
- Google Analytics API
- Rate Limiting
- Security Headers

**TIER 3 (4h):**
- PHP_CodeSniffer
- Logger Melhorado
- Microsoft Clarity
- Pa11y

---

**Versão:** 1.0.0
**Data:** 2026-02-14
**Responsável:** Claude (Guardião AEGIS)
**Status:** ✅ Documentação completa
