# 🚀 AEGIS - Roadmap Consolidado de Melhorias

> Documento ÚNICO consolidando roadmap estratégico + guia prático de instalação
> Criado em: 15/02/2026

---

## 📊 STATUS ATUAL

**Versão:** 17.3.6

**Já implementado:**
- ✅ Google Tag Manager
- ✅ Favicons customizáveis
- ✅ Credenciais FTP
- ✅ Sistema de Settings completo
- ✅ Módulo Artigos + Email + RD Station
- ✅ **PageSpeed Insights v2.0 (COMPLETO - 15/02/2026)**
  - Sistema de URLs dinâmicas (CRUD)
  - Queue/Status system (pending → processing → completed/failed)
  - Background processing com mediana
  - 100% Lucide icons (13 substituições)
  - Extração FULL de dados (98% úteis)
  - 2 tabelas, 4 migrations, 22 arquivos, 6 rotas
  - **Documentação completa:** `pagespeed-insights.md`, `pagespeed-summary.md`, `pagespeed-quickstart.md`

---

## 🔖 PONTO DE PARADA (15/02/2026)

**Status Atual:**
- ✅ PageSpeed Insights v2.0 **100% finalizado e operacional**
- ✅ Roadmap consolidado documentado (este arquivo)

**Próxima Implementação:**
- **TIER 1 / Semana 1** → **Uptime Monitoring (UptimeRobot)** - 5min
- **Decisão pendente:** Como verificar no painel se está configurado
  - Opção 1: Widget com webhook (dados reais via webhook)
  - Opção 2: API do UptimeRobot (dados ao vivo)
  - Opção 3: Indicador simples on/off (básico)

**Para retomar:**
```
Continuar implementação AEGIS TIER 1 - Uptime Monitoring
Decidir entre: webhook, API ou indicador simples no painel
```

---

## 🎯 PRÓXIMAS IMPLEMENTAÇÕES (Ordem de Execução)

### **SEMANA 1 - TIER 1** (4h-5h - 80% do valor)

```
[ ] 1.  Uptime Monitoring      →  5min   (UptimeRobot - CRÍTICO)
[ ] 2.  HTTPS/SSL              →  2min   (Verificação - CRÍTICO)
[ ] 3.  GTM/GA4 Validação      →  3min   (Confirmar funcionando)
[ ] 4.  robots.txt             →  5min   (Criar arquivo)
[ ] 5.  Security Headers       → 40min   (bootstrap.php - ALTO)
[ ] 6.  Sitemap Automático     → 30min   (sitemap.xml.php)
[ ] 7.  PHPStan                → 10min   (Análise estática)
[ ] 8.  Backup Automático n8n  →  1-2h   (MySQL + arquivos - CRÍTICO)
[ ] 9.  Relatórios IA          →  2-3h   (Dashboard diário - ALTO)
```
**TOTAL:** 4h-5h | **Resultado:** Site monitorado 24/7, backup funcionando, primeiro relatório IA

---

### **SEMANA 2 - TIER 2** (12h-14h - +15% do valor)

```
[ ] 10. Rate Limiting          →  2h     (Classe + proteção brute force)
[ ] 11. Search Console API     →  5-6h   (Queries, posições, CWV)
[ ] 12. Google Analytics API   →  4-5h   (Tráfego, widgets dashboard)
```
**TOTAL:** 12h-14h | **Resultado:** SEO rastreado, Analytics sincronizado, proteção contra bots

---

### **SEMANA 3 - TIER 3** (6h-7h - +5% do valor)

```
[ ] 13. Logger Melhorado       →  3h     (Logs estruturados MySQL)
[ ] 14. Cruzamento Dados IA    →  2h     (GA + GSC + PageSpeed insights)
[ ] 15. PHP_CodeSniffer        → 15min   (Padrão código PSR-12)
[ ] 16. Microsoft Clarity      → 10min   (Heatmaps + session recording)
[ ] 17. Pa11y                  → 30min   (Acessibilidade WCAG)
```
**TOTAL:** 6h-7h | **Resultado:** Logs pesquisáveis, oportunidades IA detectadas, qualidade código

---

### **SEMANA 4 - TIER 4** (3h-4h - Opcional)

```
[ ] 18. Event Tracking GA4     →  2h     (Conversões, downloads, scroll)
[ ] 19. CDN Cloudflare         → 15min   (Cache global - se alto tráfego)
[ ] 20. Documentação Completa  →  2h     (Admin guides + docs técnicos)
[ ] 21. Testes End-to-End      →  1h     (Validação completa integrada)
```
**TOTAL:** 3h-4h | **Resultado:** Sistema documentado, testado, pronto para produção

---

### 📊 RESUMO GERAL

| Semana | TIER | Itens | Tempo | Impacto |
|--------|------|-------|-------|---------|
| **Semana 1** | TIER 1 | 9 itens | 4-5h | **80%** |
| **Semana 2** | TIER 2 | 3 itens | 12-14h | **+15%** |
| **Semana 3** | TIER 3 | 5 itens | 6-7h | **+5%** |
| **Semana 4** | TIER 4 | 4 itens | 3-4h | Nice to have |
| **TOTAL** | - | **21 itens** | **25-30h** | **100%** |

**Custo total:** $0-0.12/mês
**Economia vs WordPress:** $400-800/ano por projeto

---

## 📋 RESUMO EXECUTIVO

### 🎯 Visão Geral

**Objetivo:** Transformar AEGIS em framework com monitoramento completo, automação inteligente e custo zero.

**Prazo:** 4 semanas (25-30h desenvolvimento)
**Custo:** $0-0.12/mês por projeto
**ROI:** Economia de $400-800/ano vs plugins pagos + 5-10h/mês de tempo

**Organização:** Pareto 80/20 (TIER 1 = 80% do valor em 20% do tempo)

---

## 📋 LISTA COMPLETA CONSOLIDADA

### 🔴 TIER 1: ESSENCIAL (4h-5h - 80% do valor)

**Semana 1 - Fundação Crítica**

| # | Item | Tempo | Impacto | Status |
|---|------|-------|---------|--------|
| 1 | Uptime Monitoring | 5min | CRÍTICO | [ ] |
| 2 | Security Headers | 40min | ALTO | [ ] |
| 3 | Backup Automático | 1-2h | CRÍTICO | [ ] |
| 4 | Relatórios IA | 2-3h | ALTO | [ ] |
| 5 | HTTPS/SSL | 2min | CRÍTICO | [ ] |
| 6 | GTM/GA4 Validação | 3min | ALTO | [ ] |
| 7 | robots.txt | 5min | MÉDIO | [ ] |
| 8 | Sitemap Automático | 30min | ALTO | [ ] |
| 9 | PHPStan | 10min | MÉDIO | [ ] |

**TOTAL TIER 1:** 4h-5h | **Impacto:** 80% do valor

---

### 🟠 TIER 2: IMPORTANTE (12h-14h - +15% do valor)

**Semana 2 - Segurança + SEO Base**

| # | Item | Tempo | Impacto | Status |
|---|------|-------|---------|--------|
| 10 | Rate Limiting | 2h | ALTO | [ ] |
| 11 | Google Search Console API | 5-6h | ALTO | [ ] |
| 12 | Google Analytics API | 4-5h | MÉDIO | [ ] |

**TOTAL TIER 2:** 12h-14h | **Impacto:** +15% do valor

---

### 🟡 TIER 3: BOM TER (6h-7h - +5% do valor)

**Semana 3 - SEO Completo + Logs**

| # | Item | Tempo | Impacto | Status |
|---|------|-------|---------|--------|
| 13 | Logger Melhorado | 3h | MÉDIO | [ ] |
| 14 | Cruzamento de Dados IA | 2h | MÉDIO | [ ] |
| 15 | PHP_CodeSniffer | 15min | BAIXO | [ ] |
| 16 | Microsoft Clarity | 10min | BAIXO | [ ] |
| 17 | Pa11y (Acessibilidade) | 30min | BAIXO | [ ] |

**TOTAL TIER 3:** 6h-7h | **Impacto:** +5% do valor

---

### 🟢 TIER 4: OPCIONAL (3h-4h - Nice to Have)

**Semana 4 - Extras + Finalização**

| # | Item | Tempo | Impacto | Status |
|---|------|-------|---------|--------|
| 18 | Event Tracking GA4 | 2h | BAIXO | [ ] |
| 19 | CDN Cloudflare | 15min | BAIXO | [ ] |
| 20 | Documentação Completa | 2h | MÉDIO | [ ] |
| 21 | Testes End-to-End | 1h | MÉDIO | [ ] |

**TOTAL TIER 4:** 3h-4h

---

## 🚀 IMPLEMENTAÇÃO DETALHADA

### **TIER 1: ESSENCIAL (4h-5h)**

---

#### **1. Uptime Monitoring** (5min) - CRÍTICO

**Objetivo:** Detectar site fora do ar antes do cliente reclamar

**Opção A: UptimeRobot (RECOMENDADO)**
- ✅ Grátis até 50 monitores
- Verifica a cada 5min
- Alertas: Email, SMS, Slack, Telegram, Webhook
- Dashboard com histórico de uptime (99.9%, etc)

**Instalação:**

```bash
# 1. Acessar
open https://uptimerobot.com

# 2. Sign Up (grátis, 50 monitores)

# 3. Add New Monitor:
#    - Monitor Type: HTTP(s)
#    - Friendly Name: AEGIS - [Nome do Projeto]
#    - URL: https://seusite.com
#    - Monitoring Interval: 5 minutes

# 4. Alert Contacts:
#    - Email: seu@email.com
#    - Telegram (opcional): conectar bot

# 5. Create Monitor
```

**Validação:**
- [ ] Monitor ativo (status verde)
- [ ] Testar: pausar servidor → alerta chega em 5min
- [ ] Email de alerta configurado

**Estimativa:** 5min
**Custo:** $0/mês
**Prioridade:** CRÍTICA

---

#### **2. Security Headers** (40min) - ALTO

**Objetivo:** Proteger contra XSS, clickjacking, MIME sniffing

**Implementação:**

**Arquivo: `bootstrap.php` (adicionar no topo)**

```php
// Security Headers
header("X-Frame-Options: SAMEORIGIN"); // Anti-clickjacking
header("X-Content-Type-Options: nosniff"); // Anti-MIME sniffing
header("X-XSS-Protection: 1; mode=block"); // Anti-XSS (legacy browsers)
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Content Security Policy (ajustar conforme necessário)
$csp = "default-src 'self'; ";
$csp .= "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://unpkg.com; ";
$csp .= "style-src 'self' 'unsafe-inline'; ";
$csp .= "img-src 'self' data: https:; ";
$csp .= "font-src 'self' data:; ";
$csp .= "connect-src 'self' https://www.google-analytics.com; ";
$csp .= "frame-ancestors 'self';";

header("Content-Security-Policy: " . $csp);

// HSTS (só em produção com HTTPS)
if ($_SERVER['HTTPS'] ?? false) {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}
```

**Validação:**
- [ ] https://securityheaders.com score ≥ A
- [ ] Site funciona normalmente (GTM, fontes, imagens)
- [ ] Console sem erros CSP
- [ ] HSTS funcionando (só HTTPS)

**Estimativa:** 10-30min (testar cuidadosamente)
**Custo:** $0
**Prioridade:** ALTA

---

#### **3. Backup Automático** (1-2h) - CRÍTICO

**Objetivo:** Backup diário do banco + arquivos com histórico de 30 dias

**Implementação n8n:**

**Workflow diário (3h):**

1. **MySQL Dump:**
```bash
mysqldump -u user -p'pass' database | gzip > backup-$(date +%Y%m%d).sql.gz
```

2. **Tar arquivos:**
```bash
tar -czf files-$(date +%Y%m%d).tar.gz /path/to/aegis \
  --exclude='storage/logs' \
  --exclude='storage/cache' \
  --exclude='node_modules'
```

3. **Upload destino:**
   - Google Drive (API grátis, 15GB)
   - Dropbox (API grátis, 2GB)
   - AWS S3 (~$0.50/mês por projeto)

4. **Limpeza:**
```bash
find /backups -name "*.gz" -mtime +30 -delete
```

5. **Notificação:**
   - Sucesso: Log silencioso
   - Falha: Alerta URGENTE

**Nodes n8n:**

```
1. Cron (Every Day, 3:00)
2. Execute Command (MySQL Dump)
3. Execute Command (Tar Files)
4. Google Drive Upload (db-*.sql.gz)
5. Google Drive Upload (files-*.tar.gz)
6. Execute Command (Cleanup old backups)
7. Send Email/Telegram (Success notification)
8. Error Workflow (Send URGENT alert)
```

**Validação:**
- [ ] Workflow criado e ativo
- [ ] Executar manual → arquivos criados
- [ ] Upload Google Drive OK
- [ ] **CRÍTICO:** Testar restauração (criar DB teste)
- [ ] Rotação 30 dias funciona

**Estimativa:** 1-2h dev
**Custo:** $0-0.50/mês (depende do destino)
**Prioridade:** CRÍTICA

---

#### **4. Relatórios IA Automáticos** (2-3h) - ALTO

**Objetivo:** Relatórios diários automáticos com insights de IA salvos no admin

**Implementação:**

**Tabela MySQL:**

```sql
CREATE TABLE ai_reports (
    id CHAR(36) PRIMARY KEY,
    type ENUM('daily', 'weekly', 'alert', 'custom') NOT NULL,
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    content LONGTEXT,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_type (type),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**API Endpoint:** `api/ai-reports.php`

```php
<?php
require_once '../bootstrap.php';

// Autenticação via webhook secret
$secret = $_POST['webhook_secret'] ?? '';
$expectedSecret = Settings::get('ai_webhook_secret');

if ($secret !== $expectedSecret) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$data = [
    'id' => Core::generateUUID(),
    'type' => $_POST['type'] ?? 'daily',
    'title' => $_POST['title'] ?? '',
    'summary' => $_POST['summary'] ?? '',
    'content' => $_POST['content'] ?? '',
    'severity' => $_POST['severity'] ?? 'info'
];

$db = DB::connect();
$stmt = $db->prepare("
    INSERT INTO ai_reports (id, type, title, summary, content, severity)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $data['id'],
    $data['type'],
    $data['title'],
    $data['summary'],
    $data['content'],
    $data['severity']
]);

echo json_encode(['success' => true, 'id' => $data['id']]);
```

**n8n Workflow diário (8h):**

```
1. Cron (Every Day, 8:00)
2. Execute Command (Claude Code CLI)
   → Comando: claude --prompt "Analise AEGIS e gere relatório markdown"
3. HTTP Request POST (api/ai-reports.php)
   → Body: { webhook_secret, type, title, summary, content, severity }
4. Send Email (resumo + link relatório completo)
5. Send Telegram (opcional - resumo curto)
```

**Views Admin:**
- `/admin/ai-reports` (lista com cards)
- `/admin/ai-reports/view/:id` (relatório completo)

**Dados do relatório:**
- Erros de logs (últimas 24h)
- PageSpeed das 5 páginas principais
- Conversões/visitas (se Analytics integrado)
- Problemas críticos identificados
- Sugestões de melhorias

**Validação:**
- [ ] Relatório gerado automaticamente todo dia 8h
- [ ] Email recebido com resumo correto
- [ ] Admin (`/admin/ai-reports`) mostra últimos 30 relatórios
- [ ] Relatório individual abre corretamente
- [ ] Alertas funcionam (simular erro crítico e verificar notificação)

**Estimativa:** 2-3h dev
**Custo:** $0/mês (Claude Code local)
**Prioridade:** ALTA

---

#### **5. HTTPS/SSL** (2min) - CRÍTICO

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

#### **6. GTM/GA4 Validação** (3min) - ALTO

**Objetivo:** Confirmar que Google Tag Manager e Analytics funcionam

**Passos:**
1. Abrir site no navegador
2. F12 → Console
3. Procurar mensagens GTM (sem erros)
4. Google Analytics → Relatórios → Tempo Real
5. Navegar no site e ver sessão aparecendo

**Validação:**
- [ ] GTM carrega sem erros (console limpo)
- [ ] GA4 mostra visita em Tempo Real
- [ ] dataLayer funcionando

---

#### **7. robots.txt** (5min) - MÉDIO

**Objetivo:** Criar arquivo robots.txt para controlar indexação Google

**Arquivo:** `/public/robots.txt`

```txt
User-agent: *
Disallow: /admin/
Disallow: /api/
Disallow: /storage/logs/
Disallow: /storage/cache/
Allow: /storage/uploads/

Sitemap: https://seusite.com/sitemap.xml
```

**Validação:**
- [ ] Acessar `https://seusite.com/robots.txt`
- [ ] Arquivo aparece corretamente
- [ ] Testar no Google: https://www.google.com/webmasters/tools/robots-testing-tool

---

#### **8. Sitemap Automático** (30min) - ALTO

**Objetivo:** Sitemap.xml gerado automaticamente a partir do banco

**Arquivo:** `/public/sitemap.xml.php`

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

**n8n workflow (semanal):**
```
Cron (domingo 2h):
  → HTTP GET seusite.com/sitemap.xml.php
  → Save file public/sitemap.xml
  → Submit to Google Search Console (API)
```

**Validação:**
- [ ] `/sitemap.xml` acessível e válido
- [ ] Todas páginas públicas listadas
- [ ] Módulos públicos listados
- [ ] Google Search Console aceita o sitemap

**Estimativa:** 30min-1h dev
**Custo:** $0
**Prioridade:** ALTA (SEO)

---

#### **9. PHPStan** (10min) - MÉDIO

**Objetivo:** Análise estática, detectar bugs antes de acontecerem

**Instalação:**

```bash
cd /Users/fabiochezzi/Documents/websites/aegis
composer require --dev phpstan/phpstan
```

**Configuração:** `/phpstan.neon`

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

**Integrar git hook (opcional):** `.git/hooks/pre-commit`

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

### **TIER 2: IMPORTANTE (12h-14h)**

---

#### **10. Rate Limiting** (2h) - ALTO

**Objetivo:** Proteger contra bots, brute force, DDoS básico

**Classe:** `core/RateLimit.php`

```php
<?php
/**
 * RateLimit - Proteção contra brute force e DDoS
 */
class RateLimit {

    public static function check($key, $maxAttempts = 10, $windowSeconds = 60) {
        $db = DB::connect();

        // Limpa expirados
        $db->query("DELETE FROM rate_limits WHERE expires_at < NOW()");

        // Conta tentativas
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM rate_limits
            WHERE key_hash = ? AND expires_at > NOW()
        ");
        $keyHash = hash('sha256', $key);
        $stmt->execute([$keyHash]);
        $count = $stmt->fetchColumn();

        if ($count >= $maxAttempts) {
            http_response_code(429);
            exit(json_encode(['error' => 'Too many requests. Try again later.']));
        }

        // Registra tentativa
        $expiresAt = date('Y-m-d H:i:s', time() + $windowSeconds);
        $db->prepare("
            INSERT INTO rate_limits (id, key_hash, expires_at)
            VALUES (?, ?, ?)
        ")->execute([Core::generateUUID(), $keyHash, $expiresAt]);
    }
}
```

**Tabela:**

```sql
CREATE TABLE rate_limits (
    id CHAR(36) PRIMARY KEY,
    key_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_key_expires (key_hash, expires_at)
) ENGINE=InnoDB;
```

**Uso:**

```php
// Login admin
RateLimit::check('admin_login:' . $_SERVER['REMOTE_ADDR'], 5, 300); // 5 tentativas / 5min

// Login members
RateLimit::check('member_login:' . $_SERVER['REMOTE_ADDR'], 10, 600); // 10 / 10min

// Formulário contato
RateLimit::check('contact_form:' . $_SERVER['REMOTE_ADDR'], 3, 3600); // 3 / 1h

// APIs públicas
RateLimit::check('api:' . $_SERVER['REMOTE_ADDR'], 100, 60); // 100 / 1min
```

**Locais críticos:**
- `/admin/login` (admin/controllers/AuthController.php)
- `/login` (members, se houver)
- `/api/*` (todos endpoints públicos)
- Formulários de contato/lead

**Validação:**
- [ ] Login bloqueado após X tentativas
- [ ] Mensagem de erro 429 clara
- [ ] Limite reseta após tempo configurado
- [ ] APIs públicas protegidas

**Estimativa:** 2h dev
**Custo:** $0
**Prioridade:** ALTA

---

#### **11. Google Search Console API** (5-6h) - ALTO

**Objetivo:** Dados SEO (queries, posições, cliques) + Core Web Vitals

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

**Etapa 3: Classe PHP (2-3h)** - `/core/GoogleSearchConsole.php`

```php
<?php
/**
 * GoogleSearchConsole - Integração com API do Google Search Console
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
        $this->siteUrl = APP_URL;
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

**Etapa 4: API Endpoint (15min)** - `/admin/api/sync-gsc.php`

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

**Etapa 5: Views Admin (1-2h)**

- `/admin/seo/overview` (visão geral)
- `/admin/seo/keywords` (monitoramento queries)
- `/admin/seo/vitals` (Core Web Vitals por página)
- `/admin/seo/errors` (404s, problemas de indexação)

**Automação n8n:**
- Cron diário (2h): Sincroniza dados de ontem
- Alertas automáticos:
  - Página caiu >5 posições
  - Novas 404s detectadas
  - Core Web Vitals degradados

**Validação:**
- [ ] Service Account criado
- [ ] Credenciais JSON salvas
- [ ] Tabelas MySQL criadas
- [ ] Classe GoogleSearchConsole funciona
- [ ] API endpoint retorna dados
- [ ] Dados salvos no banco
- [ ] Views admin acessíveis
- [ ] Queries sincronizadas com posições corretas
- [ ] Core Web Vitals aparecem
- [ ] 404s detectadas aparecem

**Estimativa:** 5-6h dev
**Custo:** $0/mês (API grátis, ilimitada)
**Prioridade:** ALTA

---

#### **12. Google Analytics API** (4-5h) - MÉDIO

**Objetivo:** Métricas de tráfego salvas no MySQL + widgets no admin

**Setup necessário:**
1. Service Account Google Cloud (mesmas credenciais do GSC)
2. Ativar Google Analytics Data API
3. JSON de credenciais
4. Property ID do GA4

**Tabelas MySQL:**

```sql
-- Métricas diárias
CREATE TABLE analytics_daily (
    id CHAR(36) PRIMARY KEY,
    date DATE NOT NULL,
    sessions INT DEFAULT 0,
    users INT DEFAULT 0,
    pageviews INT DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0,
    avg_duration DECIMAL(8,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Performance por página
CREATE TABLE analytics_pages (
    id CHAR(36) PRIMARY KEY,
    page_url VARCHAR(512) NOT NULL,
    date DATE NOT NULL,
    pageviews INT DEFAULT 0,
    users INT DEFAULT 0,
    avg_time DECIMAL(8,2) DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_url (page_url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Origens de tráfego
CREATE TABLE analytics_sources (
    id CHAR(36) PRIMARY KEY,
    source VARCHAR(255) NOT NULL,
    medium VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    sessions INT DEFAULT 0,
    users INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Classe:** `/core/GoogleAnalytics.php` (2-3h de dev)

**Widgets dashboard:**
- Usuários hoje (comparação vs ontem)
- Pageviews hoje
- Taxa de conversão
- Duração média
- Gráfico últimos 7 dias (Chart.js)

**Páginas admin:**
- `/admin/analytics/overview` (visão geral)
- `/admin/analytics/pages` (páginas mais visitadas)
- `/admin/analytics/sources` (origens de tráfego)

**Automação n8n:**
- Cron diário (1h): Sincroniza métricas de ontem
- Cache 5min em widgets ao vivo

**Validação:**
- [ ] Dados sincronizados nas últimas 24h
- [ ] Widgets dashboard mostram métricas corretas (comparar com GA4 web)
- [ ] Gráficos renderizam sem erro de console
- [ ] Comparação de períodos calcula diferenças corretamente
- [ ] Cache de 5min funcionando
- [ ] Páginas admin acessíveis

**Estimativa:** 4-5h dev
**Custo:** $0/mês (API grátis até 50k requests/dia)
**Prioridade:** MÉDIA

---

### **TIER 3: BOM TER (6h-7h)**

---

#### **13. Logger Melhorado** (3h) - MÉDIO

**Objetivo:** Logs estruturados, busca fácil, alertas automáticos

**Classe:** `core/Logger.php`

```php
<?php
class Logger {
    const CRITICAL = 'CRITICAL';
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';

    public static function log($level, $message, $context = []) {
        $db = DB::connect();

        $log = [
            'id' => Core::generateUUID(),
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'user_id' => Auth::getUserId() ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $stmt = $db->prepare("
            INSERT INTO system_logs
            (id, level, message, context, url, method, user_id, ip, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $log['id'], $log['level'], $log['message'], $log['context'],
            $log['url'], $log['method'], $log['user_id'], $log['ip'],
            $log['user_agent'], $log['created_at']
        ]);

        if ($level === self::CRITICAL) {
            self::sendAlert($log);
        }
    }

    public static function critical($msg, $ctx = []) { self::log(self::CRITICAL, $msg, $ctx); }
    public static function error($msg, $ctx = []) { self::log(self::ERROR, $msg, $ctx); }
    public static function warning($msg, $ctx = []) { self::log(self::WARNING, $msg, $ctx); }
    public static function info($msg, $ctx = []) { self::log(self::INFO, $msg, $ctx); }

    private static function sendAlert($log) {
        // n8n webhook ou email direto
        $webhook = 'https://n8n.local/webhook/critical-log';
        file_get_contents($webhook . '?' . http_build_query($log));
    }
}
```

**Tabela:**

```sql
CREATE TABLE system_logs (
    id CHAR(36) PRIMARY KEY,
    level ENUM('CRITICAL', 'ERROR', 'WARNING', 'INFO') NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    url VARCHAR(512),
    method VARCHAR(10),
    user_id CHAR(36),
    ip VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;
```

**View admin:** `/admin/logs`
- Filtros: level, data, usuário, URL
- Busca: "erro no módulo artigos"
- Export CSV
- Auto-delete > 90 dias (cron)

**Uso:**

```php
try {
    // código
} catch (Exception $e) {
    Logger::error('Falha ao processar artigo', [
        'artigo_id' => $id,
        'exception' => $e->getMessage()
    ]);
}

Logger::info('Usuário fez login', ['user_id' => $userId]);
Logger::critical('Database connection failed');
```

**Validação:**
- [ ] Logs salvos no banco corretamente
- [ ] Filtros funcionam
- [ ] Busca funciona
- [ ] Export CSV funciona
- [ ] Alertas críticos disparam
- [ ] Auto-delete > 90 dias funcionando

**Estimativa:** 3h dev
**Custo:** $0
**Prioridade:** MÉDIA

---

#### **14. Cruzamento de Dados IA** (2h) - MÉDIO

**Objetivo:** Insights poderosos combinando GA + GSC + PageSpeed

**Exemplos de queries:**

**Oportunidades SEO:**
```sql
-- Páginas com muito tráfego GA mas pouco SEO (otimizar)
SELECT ga.page_url, ga.users, gsc.clicks, gsc.position
FROM analytics_pages ga
LEFT JOIN gsc_pages gsc ON ga.page_url = gsc.page_url
WHERE ga.users > 500 AND gsc.clicks < 100 AND gsc.position > 10;
```

**Performance vs Conversão:**
```sql
-- Páginas lentas que afetam conversão
SELECT ps.url, ps.performance_score, ga.bounce_rate, ga.avg_time
FROM tbl_pagespeed_reports ps
JOIN analytics_pages ga ON ps.url = ga.page_url
WHERE ps.performance_score < 50 AND ga.bounce_rate > 60;
```

**IA analisa automaticamente e inclui no relatório:**
```markdown
## 💡 Oportunidades Detectadas

1. **/servicos/premium** tem 1.200 visitas/mês (GA) mas só 45 vêm do Google (GSC).
   Posição média: 18.2
   **Ação:** Otimizar SEO → Potencial de +400 visitas orgânicas/mês

2. **/artigos** tem PageSpeed 68/100 e bounce rate 72%.
   **Ação:** Melhorar performance → Reduzir bounce rate ~15%
```

**Estimativa:** 2h dev (queries + lógica IA)
**Prioridade:** MÉDIA
**Dependências:** GA + GSC + PageSpeed implementados

---

#### **15. PHP_CodeSniffer** (15min) - BAIXO

**Objetivo:** Padrão de código consistente (PSR-12, etc)

**Instalação:**

```bash
cd /Users/fabiochezzi/Documents/websites/aegis
composer require --dev squizlabs/php_codesniffer
```

**Configuração:** `/phpcs.xml`

```xml
<?xml version="1.0"?>
<ruleset name="AEGIS">
    <description>AEGIS Framework Code Standards</description>

    <rule ref="PSR12"/>

    <file>core</file>
    <file>admin</file>
    <file>modules</file>

    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>*/storage/*</exclude-pattern>
</ruleset>
```

**Rodar verificação:**

```bash
vendor/bin/phpcs

# Fix automático
vendor/bin/phpcbf
```

**Integrar git hook:** `.git/hooks/pre-commit`

```bash
#!/bin/bash
vendor/bin/phpcs --standard=PSR12
if [ $? -ne 0 ]; then
    echo "❌ Code style violations. Run phpcbf to fix."
    exit 1
fi
```

**Validação:**
- [ ] PHP_CodeSniffer instalado
- [ ] Roda sem erro fatal
- [ ] Identifica violações de estilo
- [ ] phpcbf corrige automaticamente

**Estimativa:** 15min
**Custo:** $0
**Prioridade:** BAIXA

---

#### **16. Microsoft Clarity** (10min) - BAIXO

**Objetivo:** Session recordings + heatmaps (ver o que usuário faz)

**Setup:**

1. Acessar: https://clarity.microsoft.com
2. Sign up (grátis ilimitado)
3. Add New Project:
   - Name: AEGIS - [Nome Projeto]
   - Website: https://seusite.com
4. Copy tracking code
5. Adicionar em `frontend/includes/_head.php` (após GTM):

```html
<!-- Microsoft Clarity -->
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "SEU_PROJECT_ID");
</script>
```

**Funcionalidades:**
- Gravação de sessões (usuário navegando)
- Heatmaps de cliques
- Scroll maps
- Rage clicks (usuário clicando repetido = frustração)
- Dead clicks (clicou mas nada aconteceu)

**Validação:**
- [ ] Script instalado
- [ ] Clarity detectando visitas
- [ ] Sessões gravadas aparecem no dashboard
- [ ] Heatmaps gerando dados

**Estimativa:** 10min
**Custo:** $0/mês (grátis ilimitado)
**Prioridade:** BAIXA (UX insights)

---

#### **17. Pa11y (Acessibilidade)** (30min) - BAIXO

**Objetivo:** Detectar problemas de acessibilidade (WCAG compliance)

**Instalação:**

```bash
npm install -g pa11y
```

**Rodar teste:**

```bash
pa11y https://seusite.com

# Teste completo
pa11y https://seusite.com --standard WCAG2AA --reporter html > report.html
```

**Automatizar (n8n workflow mensal):**

```
Cron (1º dia do mês):
  → Execute Command: pa11y https://seusite.com --reporter json
  → Parse JSON
  → If errors > 10:
      → Send Email (lista de problemas)
      → Create GitHub Issue (se integrado)
```

**Problemas comuns detectados:**
- Imagens sem atributo `alt`
- Links sem texto descritivo
- Contraste de cores insuficiente
- Form labels ausentes
- Headings fora de ordem (H1 → H3, pulou H2)

**Validação:**
- [ ] Pa11y instalado
- [ ] Roda sem erro
- [ ] Identifica problemas reais
- [ ] Relatório HTML legível

**Estimativa:** 30min setup
**Custo:** $0
**Prioridade:** BAIXA (se cliente precisa compliance)

---

### **TIER 4: OPCIONAL (3h-4h)**

---

#### **18. Event Tracking GA4** (2h) - BAIXO

**Objetivo:** Rastrear conversões importantes

**Eventos importantes:**
- Lead gerado (formulário enviado)
- Artigo baixado
- Contato via WhatsApp
- Tempo em página > 3min (engajamento)
- Scroll 75% (leu até o fim)

**Google Tag Manager (já instalado):**

```javascript
// Formulário enviado
document.querySelector('form').addEventListener('submit', function() {
    gtag('event', 'generate_lead', {
        'event_category': 'engagement',
        'event_label': 'contact_form'
    });
});

// Download artigo
gtag('event', 'file_download', {
    'file_name': 'artigo-cientifico.pdf',
    'file_extension': 'pdf'
});

// Scroll tracking
var scrolled75 = false;
window.addEventListener('scroll', function() {
    var scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
    if (scrollPercent > 75 && !scrolled75) {
        scrolled75 = true;
        gtag('event', 'scroll', {
            'event_category': 'engagement',
            'percent_scrolled': 75
        });
    }
});
```

**Análise no GA4:**
- Quais páginas geram mais leads
- Qual origem (SEO, social, direct) converte melhor
- Funil: visita → scroll → formulário → conversão

**Estimativa:** 2h dev
**Custo:** $0
**Prioridade:** MÉDIA (se foco em conversão)

---

#### **19. CDN Cloudflare** (15min) - BAIXO

**Objetivo:** Cache global, proteção DDoS, SSL grátis

**Quando vale a pena:**
- ✅ Tráfego > 5k visitas/dia
- ✅ Usuários em regiões distantes do servidor
- ✅ Muitas imagens/assets pesados
- ❌ Site local (só SP, por exemplo)

**Setup:**

```bash
# 1. cloudflare.com → Add site
# 2. Mudar DNS do domínio pros nameservers do Cloudflare
# 3. Configurar:
#    - SSL/TLS: Full
#    - Cache: Everything
#    - Auto Minify: CSS, JS, HTML
#    - Brotli: ON
#    - Rocket Loader: ON (testar, pode quebrar JS)
```

**Estimativa:** 15min setup
**Custo:** $0/mês (plano Free)
**Prioridade:** BAIXA (avaliar após tráfego crescer)

---

#### **20. Documentação Completa** (2h) - MÉDIO

**Objetivo:** Docs para admin e desenvolvedores

**Criar:**

**Admin Guides:**
- `docs/admin-guide/analytics.md` - Como ler métricas, glossário
- `docs/admin-guide/seo-reports.md` - Interpretar GSC, Core Web Vitals
- `docs/admin-guide/alerts.md` - Tipos de alerta, priorização
- `docs/admin-guide/backup-restore.md` - Procedimento de emergência

**Docs Técnicos:**
- `.claude/integrations/google-analytics.md` - Setup, API, troubleshooting
- `.claude/integrations/search-console.md` - Setup, queries, Core Web Vitals
- `.claude/integrations/ai-reports.md` - Como funciona, customizar relatórios
- `.claude/security/rate-limiting.md` - Configuração, whitelist, ajustes
- `.claude/backup-restore-guide.md` - Restauração passo a passo

**Estimativa:** 2h escrita + screenshots
**Prioridade:** MÉDIO

---

#### **21. Testes End-to-End** (1h) - MÉDIO

**Objetivo:** Validar que tudo funciona integrado

**Checklist:**
- [ ] Backup roda e restaura corretamente
- [ ] Relatórios IA geram diariamente
- [ ] GA/GSC sincronizam dados
- [ ] Rate limiting bloqueia após X tentativas
- [ ] Security headers score A
- [ ] Sitemap.xml válido
- [ ] Logs estruturados funcionam
- [ ] Alertas disparam corretamente

**Estimativa:** 1h validação
**Prioridade:** MÉDIO

---

## 🎯 RESULTADO FINAL (Após 1 Mês)

```
╔═══════════════════════════════════════════════════════════╗
║         AEGIS Admin Dashboard - Visão Consolidada         ║
╠═══════════════════════════════════════════════════════════╣
║                                                           ║
║  📊 ANALYTICS (Google Analytics 4)                        ║
║    • Usuários: 1.240 hoje (↑ 8% vs ontem)               ║
║    • Pageviews: 3.680 (↑ 12%)                           ║
║    • Bounce rate: 42% (↓ 3%)                            ║
║    • Gráfico últimos 7 dias                              ║
║                                                           ║
║  🔍 SEO (Google Search Console)                           ║
║    • Cliques hoje: 520 (↑ 18%)                          ║
║    • Posição média: 11.2 (↑ 0.8)                        ║
║    • Top 5 queries + posições                            ║
║    • Core Web Vitals: 85% páginas OK                     ║
║                                                           ║
║  ⚡ PERFORMANCE (PageSpeed Insights)                      ║
║    • Score médio: 92/100                                 ║
║    • 3 páginas precisam otimização                       ║
║    • Tendência últimos 30 dias                           ║
║                                                           ║
║  🤖 RELATÓRIOS IA (Automáticos)                          ║
║    • Último: 15/02 08:00 - ✅ 0 problemas críticos      ║
║    • Histórico: 30 relatórios                            ║
║    • Alertas: 2 avisos SEO                               ║
║                                                           ║
║  🚨 ALERTAS ATIVOS                                        ║
║    • Uptime: 99.98% (7 dias)                            ║
║    • Backup: ✅ Último em 15/02 03:00                   ║
║    • Logs: 3 warnings (não críticos)                     ║
║                                                           ║
║  🔒 SEGURANÇA                                             ║
║    • Rate limiting: 12 bots bloqueados hoje             ║
║    • Security score: A (securityheaders.com)             ║
║    • Backups: 30 dias disponíveis                        ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝
```

**Funcionalidades Ativas:**
- ✅ Monitoramento 24/7 (uptime, métricas, SEO, performance)
- ✅ Automação completa (backups, relatórios, alertas, syncs)
- ✅ Segurança reforçada (rate limiting, headers, CSRF, sanitização)
- ✅ Inteligência artificial (insights, oportunidades, cruzamento de dados)
- ✅ Custo total: **$0-0.12/mês**

---

## 💰 ESTIMATIVA DE CUSTOS

### Custos Recorrentes:

| Serviço | Plano | Custo/mês |
|---------|-------|-----------|
| Google Analytics API | Grátis (50k requests/dia) | $0 |
| Search Console API | Grátis (ilimitado) | $0 |
| PageSpeed Insights API | Grátis (25k requests/dia) | $0 |
| UptimeRobot | Free (50 monitores, 5min) | $0 |
| n8n | Self-hosted | $0 |
| Claude Code | Local (via Claude Pro) | $0 |
| Backup storage (Google Drive) | 15GB grátis | $0 |
| Backup storage (AWS S3) | ~5GB × $0.023/GB | $0.12 |
| Cloudflare CDN | Free plan | $0 |
| **TOTAL** | | **$0-0.12/mês** |

**Por projeto/ano:** $0-1.44

**10 projetos/ano:** $0-14.40 (vs $4.000-8.000 com WordPress + plugins)

**Economia anual:** ~$4.000-8.000

---

## 🎯 CRONOGRAMA SUGERIDO

### Semana 1 (4-5h):
- ✅ UptimeRobot (5min)
- ✅ Security Headers (40min)
- ✅ Backup automático (1-2h)
- ✅ Relatórios IA (2-3h)
- ✅ HTTPS/SSL (2min)
- ✅ GTM/GA4 (3min)
- ✅ robots.txt (5min)
- ✅ PHPStan (10min)

### Semana 2 (12h-14h):
- ✅ Sitemap (30min)
- ✅ Rate Limiting (2h)
- ✅ Google Search Console API (5-6h)
- ✅ Google Analytics API (4-5h)

### Semana 3 (6h-7h):
- ✅ Logger melhorado (3h)
- ✅ Cruzamento de dados IA (2h)
- ✅ PHP_CodeSniffer (15min)
- ✅ Microsoft Clarity (10min)
- ✅ Pa11y (30min)

### Semana 4 (3h-4h):
- ✅ Event Tracking GA4 (2h)
- ✅ CDN Cloudflare (15min)
- ✅ Documentação (2h)
- ✅ Testes end-to-end (1h)

**Total: ~25-30h em 1 mês**

---

## 🆚 AEGIS vs ALTERNATIVAS

### WordPress + Plugins Premium

| Feature | WordPress | AEGIS Framework |
|---------|-----------|-----------------|
| **Analytics** | Jetpack Stats ($14/mês) ou MonsterInsights ($99/ano) | GA integrado **$0** |
| **SEO** | Yoast Premium ($99/ano) ou RankMath Pro ($59/ano) | GSC integrado **$0** |
| **Backups** | UpdraftPlus Premium ($70/ano) ou BackupBuddy ($80/ano) | n8n automático **$0** |
| **Uptime** | Jetpack Monitor ($9/mês) ou ManageWP ($2/site) | UptimeRobot **$0** |
| **Performance** | WP Rocket ($59/ano) ou NitroPack ($21/mês) | PageSpeed nativo **$0** |
| **Security** | Wordfence Premium ($119/ano) ou Sucuri ($200/ano) | Headers + Rate limit **$0** |
| **Logs** | Query Monitor (grátis mas básico) | Logger estruturado **$0** |
| **Relatórios IA** | Não existe | Sistema próprio **$0** |
| **TOTAL/ano** | **~$400-800/ano por site** | **$0-6/ano** |

**Vantagens AEGIS:**
- ✅ Tudo integrado (1 dashboard, não 10 plugins)
- ✅ Não quebra entre updates
- ✅ Leve (WP + plugins = 100MB+, AEGIS = 15MB)
- ✅ Customizável 100%
- ✅ Dados consolidados
- ✅ **Custo próximo de $0**

---

## 📊 PRIORIZAÇÃO GERAL

### 🔴 CRÍTICO (fazer AGORA após PageSpeed):
1. **Uptime Monitoring** (5min)
2. **Backup Automático** (1-2h)
3. **Security Headers** (40min)

### 🟠 ALTO (fazer essa semana):
4. **Sistema de Relatórios IA** (2-3h)
5. **Rate Limiting** (2h)
6. **Sitemap Automático** (30min)
7. **Google Search Console** (5-6h)

### 🟡 MÉDIO (fazer esse mês):
8. **Google Analytics** (4-5h)
9. **Logger Melhorado** (3h)
10. **Cruzamento de Dados** (2h)
11. **PHPStan** (10min)

### 🟢 BAIXO (avaliar necessidade):
12. **PHP_CodeSniffer** (15min)
13. **Microsoft Clarity** (10min)
14. **Pa11y** (30min)
15. **Event Tracking GA4** (2h)
16. **CDN Cloudflare** (15min - só se tráfego alto)

### ⚪ MUITO BAIXO (backlog futuro):
- A/B Testing
- Docker
- Multi-idioma
- PWA
- WebSockets

---

## ✅ CHECKLIST DE VALIDAÇÃO

### TIER 1:
- [ ] UptimeRobot ativo e alertando
- [ ] https://securityheaders.com score ≥ A
- [ ] Backup manual testado e restaurado
- [ ] Relatório IA gerado automaticamente
- [ ] HTTPS funcionando
- [ ] GTM/GA4 validado
- [ ] robots.txt acessível
- [ ] Sitemap.xml válido
- [ ] PHPStan rodando

### TIER 2:
- [ ] Login bloqueado após X tentativas
- [ ] GSC sincronizando queries
- [ ] GA sincronizando métricas
- [ ] Core Web Vitals aparecem
- [ ] Widgets dashboard funcionando

### TIER 3:
- [ ] Logs estruturados no banco
- [ ] IA cruza dados e detecta oportunidades
- [ ] PHP_CodeSniffer rodando
- [ ] Clarity gravando sessões
- [ ] Pa11y detecta problemas

---

## 💼 IMPACTO ESPERADO

**Para você (desenvolvedor):**
- ⏱️ **Tempo economizado:** 5-10h/mês
- 💰 **Custo economizado:** $400-800/ano por projeto
- 😌 **Stress reduzido:** Alertas proativos
- 📈 **Upsell:** Mostra valor concreto
- 🎯 **Profissionalismo:** Dashboard classe mundial

**Para o cliente:**
- 🚀 **Site mais rápido:** PageSpeed otimizado
- 📊 **Transparência:** Métricas reais
- 🔒 **Segurança:** Backups + proteção
- 📈 **Resultados:** SEO melhorando
- 💪 **Confiança:** Problemas resolvidos antes de afetar

---

## 🚨 PLANO DE ROLLBACK

### Se integração GA/GSC quebrar site:
```php
// Comentar inclusão da classe no bootstrap:
// require_once 'core/GoogleAnalytics.php';
```
**Tempo recuperação:** < 15min

### Se backup falhar:
```bash
# Backup manual imediato
mysqldump -u user -p'pass' database | gzip > manual-backup-$(date +%Y%m%d-%H%M).sql.gz
tar -czf manual-files-$(date +%Y%m%d-%H%M).tar.gz /path/to/aegis
```
**Tempo recuperação:** 30min

### Se rate limiting bloquear usuários legítimos:
```sql
-- Limpar tabela para IP específico
DELETE FROM rate_limits WHERE key_hash = SHA2('admin_login:192.168.1.100', 256);
```
**Tempo recuperação:** 5min

---

## ✅ CONCLUSÃO

**Sistema completo após todas as fases:**

```
AEGIS Framework v18+
├─ Monitoramento Total
│  ├─ Uptime (UptimeRobot - 5min intervals)
│  ├─ Analytics (GA4 - tráfego, conversões, comportamento)
│  ├─ SEO (Search Console - queries, posições, CWV)
│  ├─ Performance (PageSpeed + CWV reais)
│  └─ Logs estruturados (crítico, erro, warning, info)
│
├─ Automação Completa
│  ├─ Backups diários (banco + arquivos, 30 dias histórico)
│  ├─ Relatórios IA diários (insights, problemas, oportunidades)
│  ├─ Alertas proativos (uptime, SEO, performance, segurança)
│  └─ Sincronização de dados (GA, GSC, PageSpeed → MySQL)
│
├─ Segurança Reforçada
│  ├─ Rate limiting (anti brute force, DDoS básico)
│  ├─ Security headers (XSS, clickjacking, MIME sniff)
│  ├─ CSRF protection (já tem)
│  └─ Input sanitization (já tem)
│
├─ Admin Dashboard Poderoso
│  ├─ Widgets ao vivo (GA + GSC + PageSpeed - cache 5min)
│  ├─ Histórico de métricas (comparação temporal)
│  ├─ Relatórios IA (últimos 30 dias, busca, filtros)
│  ├─ Logs buscáveis (por level, data, usuário, URL)
│  └─ Alertas centralizados (tudo em 1 lugar)
│
└─ SEO Otimizado
   ├─ Sitemap automático (atualização semanal)
   ├─ Core Web Vitals monitorados (LCP, FID, CLS)
   ├─ Keywords tracking (posições, tendências)
   └─ Oportunidades detectadas (IA analisa e sugere)
```

---

**Versão:** 3.0.0 CONSOLIDADA
**Criado em:** 15/02/2026
**Baseado em:**
- `roadmap-melhorias.md` (2,252 linhas - visão estratégica)
- `INSTALACAO-FERRAMENTAS-COMPLETA.md` (634 linhas - código prático)

**Responsável:** Claude (Guardião AEGIS)
**Status:** ✅ Documento consolidado COMPLETO
**Próximo passo:** Implementar TIER 1 (4-5h)
