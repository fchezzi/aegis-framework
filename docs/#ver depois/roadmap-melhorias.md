# 🚀 AEGIS - Roadmap de Melhorias

> Documento consolidado de todas as melhorias recomendadas para o framework AEGIS.
> Criado em: 10/02/2026

---

## 📊 STATUS ATUAL

**Versão:** 17.3.2

**Já implementado:**
- ✅ Google Tag Manager
- ✅ Favicons customizáveis
- ✅ Credenciais FTP
- ✅ Sistema de Settings completo
- ✅ Módulo Artigos + Email + RD Station
- 🔄 **PageSpeed (em implantação)**

---

## 🔖 PONTO DE PARADA (10/02/2026)

**Status Atual:**
- ✅ PageSpeed em fase final de implantação
- ✅ Roadmap completo documentado

**Próxima Implementação:**
- **Semana 1** → Item 1: **Uptime Monitoring (UptimeRobot)**
- **Decisão pendente:** Como verificar no painel se está configurado
  - Opção 1: Widget com webhook (dados reais via webhook)
  - Opção 2: API do UptimeRobot (dados ao vivo)
  - Opção 3: Indicador simples on/off (básico)

**Para retomar:**
```
Continuar implementação AEGIS Semana 1 - Uptime Monitoring
Decidir entre: webhook, API ou indicador simples no painel
```

---

## 📋 RESUMO EXECUTIVO

### 🎯 Visão Geral

**Objetivo:** Transformar AEGIS em framework com monitoramento completo, automação inteligente e custo zero.

**Prazo:** 4 semanas (25-30h desenvolvimento)
**Custo:** $0-0.12/mês por projeto
**ROI:** Economia de $400-800/ano vs plugins pagos + 5-10h/mês de tempo

---

### 🔴 **SEMANA 1** (4-5h) - Fundação Crítica

**Objetivo:** Proteção básica + primeira automação

**Implementações:**
1. **Uptime Monitoring** (5min)
   - UptimeRobot configurado
   - Alertas via email/Telegram
   - Detecção de downtime em 5min

2. **Security Headers** (40min)
   - Proteção XSS, clickjacking, MIME sniffing
   - Score A no securityheaders.com
   - HSTS em produção

3. **Backup Automático** (1-2h)
   - n8n workflow diário às 3h
   - MySQL dump + arquivos (.tar.gz)
   - Upload Google Drive/S3
   - Rotação 30 dias

4. **Relatórios IA** (2-3h)
   - n8n executa Claude Code diariamente
   - Análise de logs, PageSpeed, métricas
   - Email com resumo + salva no admin
   - API endpoint `api/ai-reports.php`

**Resultado Semana 1:**
- ✅ Site monitorado 24/7
- ✅ Backup automático funcionando
- ✅ Primeiro relatório IA chegando no email todo dia 8h
- ✅ Segurança básica implementada

---

### 🟠 **SEMANA 2** (7-8h) - Segurança + SEO Base

**Objetivo:** Proteção avançada + Google encontra tudo

**Implementações:**
5. **Rate Limiting** (2h)
   - Classe `RateLimit.php`
   - Proteção login admin (5 tentativas/5min)
   - Proteção APIs públicas
   - Tabela `rate_limits`

6. **Sitemap Automático** (1h)
   - Geração dinâmica de sitemap.xml
   - Páginas + módulos + itens dinâmicos
   - Atualização semanal via n8n
   - Submit automático ao Google

7. **Google Analytics** (4-5h)
   - Service Account configurado
   - Classe `GoogleAnalytics.php`
   - Sync diário de métricas (sessions, users, bounce rate)
   - Widgets dashboard (usuários hoje, gráficos)
   - Views admin `/admin/analytics/*`

**Resultado Semana 2:**
- ✅ Site protegido contra brute force
- ✅ 100% páginas indexáveis no sitemap
- ✅ Métricas de tráfego sincronizando diariamente
- ✅ Dashboard mostrando dados do Analytics

---

### 🟡 **SEMANA 3** (8-9h) - SEO Completo + Logs

**Objetivo:** Visibilidade total no Google + rastreabilidade

**Implementações:**
8. **Google Search Console** (5-6h)
   - Classe `GoogleSearchConsole.php`
   - Sync diário: queries, posições, cliques
   - Core Web Vitals por página
   - Detecção de 404s e erros
   - Views admin `/admin/seo/*`
   - Alertas de queda de ranking

9. **Logger Melhorado** (3h)
   - Classe `Logger.php`
   - Logs estruturados no MySQL
   - 4 níveis: CRITICAL, ERROR, WARNING, INFO
   - View admin `/admin/logs` (filtros, busca, export)
   - Alertas automáticos em erros críticos

**Resultado Semana 3:**
- ✅ Posições no Google monitoradas
- ✅ Core Web Vitals rastreados
- ✅ Palavras-chave top 50 visíveis
- ✅ Logs estruturados e pesquisáveis
- ✅ Alertas automáticos de problemas SEO

---

### 🟢 **SEMANA 4** (4-5h) - Inteligência + Finalização

**Objetivo:** IA cruza dados + sistema documentado

**Implementações:**
10. **Cruzamento de Dados** (2h)
    - Queries SQL combinando GA + GSC + PageSpeed
    - IA detecta oportunidades automaticamente
    - Relatórios incluem insights cruzados
    - Exemplo: "Página X tem tráfego alto mas SEO baixo → otimizar"

11. **Documentação** (2h)
    - Admin guides (analytics, SEO, alertas, backup)
    - Docs técnicos (integrations, security)
    - Screenshots + FAQs
    - Procedimentos de emergência

12. **Testes End-to-End** (1h)
    - Checklist completo de validação
    - Testar restauração de backup
    - Simular alertas
    - Validar todos os widgets

**Resultado Semana 4:**
- ✅ Relatórios IA com insights poderosos
- ✅ Oportunidades detectadas automaticamente
- ✅ Sistema 100% documentado
- ✅ Tudo testado e validado

---

### 🎯 **RESULTADO FINAL (Após 1 Mês)**

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
║    • Último: 10/02 08:00 - ✅ 0 problemas críticos      ║
║    • Histórico: 30 relatórios                            ║
║    • Alertas: 2 avisos SEO                               ║
║                                                           ║
║  🚨 ALERTAS ATIVOS                                        ║
║    • Uptime: 99.98% (7 dias)                            ║
║    • Backup: ✅ Último em 10/02 03:00                   ║
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

### 💼 **IMPACTO ESPERADO**

**Para você (desenvolvedor):**
- ⏱️ **Tempo economizado:** 5-10h/mês (detecção + investigação de problemas)
- 💰 **Custo economizado:** $400-800/ano por projeto (vs plugins pagos)
- 😌 **Stress reduzido:** Alertas proativos (sabe antes do cliente)
- 📈 **Upsell:** Mostra valor concreto → cliente paga mais
- 🎯 **Profissionalismo:** Dashboard classe mundial

**Para o cliente:**
- 🚀 **Site mais rápido:** PageSpeed otimizado continuamente
- 📊 **Transparência:** Acesso a métricas reais em tempo real
- 🔒 **Segurança:** Backups diários + proteção contra ataques
- 📈 **Resultados:** SEO melhorando consistentemente (posições visíveis)
- 💪 **Confiança:** Problemas resolvidos antes de afetar negócio

---

### 🚀 **FASES FUTURAS (Backlog)**

#### Fase 4: **Business Intelligence** (baixa prioridade)
- Event Tracking GA4 (rastrear conversões específicas)
- A/B Testing (testar variações de páginas)
- Microsoft Clarity (heatmaps, session recordings)
- **Quando:** Se foco em otimização de conversão

#### Fase 5: **DevOps & Infraestrutura** (muito baixa prioridade)
- CI/CD (deploy automático via GitHub Actions)
- Docker (ambiente reproduzível)
- CDN Cloudflare (se tráfego > 5k visitas/dia)
- **Quando:** Se equipe crescer ou escala aumentar

#### Fase 6: **Expansão & Features Avançadas** (futuro distante)
- Multi-idioma (PT + EN + ES)
- PWA (site instalável como app)
- WebSockets (notificações real-time)
- **Quando:** Requisito específico de cliente

---

### ✅ **PRÓXIMO PASSO**

**Após finalizar PageSpeed:**
1. Revisar este roadmap
2. Confirmar prioridades (semanas 1-4)
3. Executar Semana 1 (fundação crítica)
4. Validar resultados
5. Seguir para Semana 2

**Comando para retomar:** `/aegis` + mencionar "roadmap"

---

## 🎯 MÉTRICAS DE SUCESSO (KPIs)

### Após Fase 1 (Relatórios + Analytics):
- ✅ 100% uptime detectado (vs descobrir por cliente)
- ✅ Tempo de resposta a problemas < 6h
- ✅ Dashboard com dados atualizados diariamente
- ✅ 0 dias sem relatório IA gerado

### Após Fase 2 (Segurança):
- ✅ 0 backups falhados no mês
- ✅ 0 bots conseguindo brute force (tentativas bloqueadas)
- ✅ Security score ≥ A (securityheaders.com)
- ✅ Restauração de backup testada mensalmente

### Após Fase 3 (SEO):
- ✅ 100% páginas públicas no sitemap
- ✅ Posição média Google ↑ 10% (3 meses)
- ✅ Cliques orgânicos ↑ 20% (3 meses)
- ✅ 0 páginas com CWV crítico

### Métricas Gerais (6 meses):
- ✅ Tempo de detecção de problemas: < 1h (vs 24-48h antes)
- ✅ Decisões baseadas em dados: 100% (vs achismo)
- ✅ Custo mensal total: < $1/projeto
- ✅ Satisfação cliente: Acesso self-service a métricas

---

## 🎯 PRÓXIMAS IMPLEMENTAÇÕES

### FASE 1: INTEGRAÇÕES & RELATÓRIOS IA (Prioridade ALTA)

#### 1.1 Sistema de Relatórios IA Automáticos

**Objetivo:** Relatórios diários automáticos com insights de IA salvos no admin.

**Implementação:**
- Tabela `ai_reports` (id, type, title, summary, content, severity, created_at)
- API endpoint: `api/ai-reports.php` (recebe relatórios do n8n)
- Views admin:
  - `/admin/ai-reports` (lista com cards)
  - `/admin/ai-reports/view/:id` (relatório completo)
- n8n workflow diário (8h):
  1. Executa Claude Code CLI
  2. Gera relatório markdown completo
  3. POST na API com resumo + conteúdo
  4. Envia email/Slack com resumo + link

**Dados do relatório:**
- Erros de logs (últimas 24h)
- PageSpeed das 5 páginas principais
- Conversões/visitas (se Analytics integrado)
- Problemas críticos identificados
- Sugestões de melhorias

**Estimativa:** 2-3h dev
**Custo:** $0/mês (Claude Code local)
**Dependências:** Nenhuma

---

#### 1.2 Google Analytics Integration

**Objetivo:** Métricas de tráfego salvas no MySQL + widgets no admin.

**Implementação:**

**Backend:**
- Classe `core/GoogleAnalytics.php` (wrapper da API)
- API endpoint: `api/sync-analytics.php` (sincroniza dados)
- Tabelas MySQL:
  - `analytics_daily` (sessions, users, pageviews, bounce_rate, avg_duration)
  - `analytics_pages` (performance por página)
  - `analytics_sources` (organic, direct, referral, social)

**Frontend:**
- Widgets dashboard:
  - Usuários hoje (comparação vs ontem)
  - Pageviews hoje
  - Taxa de conversão
  - Duração média
  - Gráfico últimos 7 dias (Chart.js)
- Páginas admin:
  - `/admin/analytics/overview` (visão geral)
  - `/admin/analytics/pages` (páginas mais visitadas)
  - `/admin/analytics/sources` (origens de tráfego)

**Automação n8n:**
- Cron diário (1h): Sincroniza métricas de ontem
- Cache 5min em widgets ao vivo

**Setup necessário:**
1. Service Account Google Cloud
2. Ativar Google Analytics Data API
3. JSON de credenciais
4. Property ID do GA4

**Estimativa:** 4-5h dev
**Custo:** $0/mês (API grátis até 50k requests/dia)
**Dependências:** Service Account criado

---

#### 1.3 Google Search Console Integration

**Objetivo:** Dados SEO (queries, posições, cliques) + Core Web Vitals.

**Implementação:**

**Backend:**
- Classe `core/GoogleSearchConsole.php`
- API endpoint: `api/sync-gsc.php`
- Tabelas MySQL:
  - `gsc_queries` (query, impressions, clicks, ctr, position)
  - `gsc_pages` (performance por página)
  - `gsc_vitals` (LCP, FID, CLS por página/device)
  - `gsc_errors` (404s, 500s, erros de indexação)

**Frontend:**
- Widgets dashboard:
  - Cliques hoje (SEO)
  - Posição média
  - Top 5 queries
  - Alertas SEO (quedas de ranking, novas 404s)
- Páginas admin:
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

**Setup necessário:**
1. Mesmas credenciais do GA (Service Account)
2. Adicionar Service Account como "Owner" no Search Console
3. Site URL (ex: `https://seusite.com`)

**Estimativa:** 5-6h dev
**Custo:** $0/mês (API grátis, ilimitada)
**Dependências:** Service Account criado

---

#### 1.4 Cruzamento de Dados (GA + GSC + PageSpeed)

**Objetivo:** Insights poderosos combinando múltiplas fontes.

**Exemplos:**

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
SELECT ps.url, ps.score, ga.bounce_rate, ga.avg_duration
FROM pagespeed_results ps
JOIN analytics_pages ga ON ps.url = ga.page_url
WHERE ps.score < 50 AND ga.bounce_rate > 60;
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
**Dependências:** GA + GSC + PageSpeed implementados

---

### FASE 2: SEGURANÇA & MONITORAMENTO (Prioridade ALTA)

#### 2.1 Uptime Monitoring

**Objetivo:** Detectar site fora do ar antes do cliente reclamar.

**Opção A: UptimeRobot (RECOMENDADO)**
- ✅ Grátis até 50 monitores
- Verifica a cada 5min
- Alertas: Email, SMS, Slack, Telegram, Webhook
- Dashboard com histórico de uptime (99.9%, etc)
- Páginas de status públicas (opcional)

**Setup:**
1. uptimerobot.com → Cadastro
2. Add Monitor → HTTP(s)
3. URL: `https://seusite.com`
4. Interval: 5min
5. Alertas: Email + Telegram

**Opção B: n8n + Claude Code**
```
Cron a cada 5min:
  → HTTP Request seusite.com
  → If status ≠ 200:
      → Wait 1min
      → Retry
      → If still down:
          → Alerta URGENTE (Telegram/Email)
          → Log em tabela `uptime_incidents`
```

**Estimativa:** 5min (UptimeRobot) ou 1h (n8n)
**Custo:** $0/mês
**Prioridade:** CRÍTICA

---

#### 2.2 Backup Automático

**Objetivo:** Backup diário do banco + arquivos com histórico de 30 dias.

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
   - Servidor remoto via FTP (usar credenciais do Settings)

4. **Limpeza:**
   ```bash
   find /backups -name "*.gz" -mtime +30 -delete
   ```

5. **Notificação:**
   - Sucesso: Log silencioso
   - Falha: Alerta URGENTE

**Estrutura de pastas:**
```
backups/
├── 2026-02-10/
│   ├── database.sql.gz
│   └── files.tar.gz
├── 2026-02-09/
│   ├── database.sql.gz
│   └── files.tar.gz
...
```

**Estimativa:** 1-2h dev
**Custo:** $0-0.50/mês (depende do destino)
**Prioridade:** CRÍTICA

---

#### 2.3 Rate Limiting

**Objetivo:** Proteger contra bots, brute force, DDoS básico.

**Implementação:**

**Classe `core/RateLimit.php`:**
```php
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

**Estimativa:** 2h dev
**Custo:** $0
**Prioridade:** ALTA

---

#### 2.4 Security Headers

**Objetivo:** Proteger contra XSS, clickjacking, MIME sniffing.

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
$csp .= "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com; ";
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
- https://securityheaders.com
- Testar após implementar (pode quebrar funcionalidades)

**Estimativa:** 10-30min (testar cuidadosamente)
**Custo:** $0
**Prioridade:** ALTA

---

#### 2.5 Logger Melhorado

**Objetivo:** Logs estruturados, busca fácil, alertas automáticos.

**Implementação:**

**Classe `core/Logger.php`:**
```php
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

        // Salva no banco
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

        // Alerta se crítico
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

**View admin: `/admin/logs`**
- Filtros: level, data, usuário, URL
- Busca: "erro no módulo artigos"
- Export CSV
- Auto-delete > 90 dias (cron)

**Uso no código:**
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

**Estimativa:** 3h dev
**Custo:** $0
**Prioridade:** MÉDIA

---

### FASE 3: SEO & PERFORMANCE (Prioridade MÉDIA)

#### 3.1 Sitemap Automático

**Objetivo:** Sitemap.xml gerado automaticamente a partir do banco.

**Implementação:**

**Arquivo: `public/sitemap.xml.php`**
```php
<?php
require_once '../bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$db = DB::connect();

// Páginas públicas
$pages = $db->query("
    SELECT slug, updated_at FROM pages WHERE is_public = 1
")->fetchAll();

// Módulos públicos (ler module.json)
$modules = [];
foreach (glob('../modules/*/module.json') as $file) {
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
echo "<loc>https://seusite.com/</loc>";
echo "<priority>1.0</priority>";
echo "<changefreq>daily</changefreq>";
echo "</url>";

// Páginas
foreach ($pages as $page) {
    echo "<url>";
    echo "<loc>https://seusite.com/{$page['slug']}</loc>";
    echo "<lastmod>" . date('Y-m-d', strtotime($page['updated_at'])) . "</lastmod>";
    echo "<priority>0.8</priority>";
    echo "</url>";
}

// Módulos
foreach ($modules as $module) {
    echo "<url>";
    echo "<loc>https://seusite.com/{$module['slug']}</loc>";
    echo "<lastmod>{$module['updated_at']}</lastmod>";
    echo "<priority>0.7</priority>";
    echo "</url>";

    // Se módulo tem itens dinâmicos (ex: artigos)
    if ($module['slug'] === 'artigos') {
        $artigos = $db->query("SELECT slug, updated_at FROM artigos WHERE status = 'published'")->fetchAll();
        foreach ($artigos as $artigo) {
            echo "<url>";
            echo "<loc>https://seusite.com/artigos/{$artigo['slug']}</loc>";
            echo "<lastmod>" . date('Y-m-d', strtotime($artigo['updated_at'])) . "</lastmod>";
            echo "<priority>0.6</priority>";
            echo "</url>";
        }
    }
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

**robots.txt (adicionar):**
```
Sitemap: https://seusite.com/sitemap.xml
```

**Estimativa:** 1h dev
**Custo:** $0
**Prioridade:** ALTA (SEO)

---

#### 3.2 CDN (Cloudflare)

**Objetivo:** Cache global, proteção DDoS, SSL grátis.

**Quando vale a pena:**
- ✅ Tráfego > 5k visitas/dia
- ✅ Usuários em regiões distantes do servidor
- ✅ Muitas imagens/assets pesados
- ❌ Site local (só SP, por exemplo)

**Setup:**
1. cloudflare.com → Add site
2. Mudar DNS do domínio pros nameservers do Cloudflare
3. Configurar:
   - SSL/TLS: Full
   - Cache: Everything
   - Auto Minify: CSS, JS, HTML
   - Brotli: ON
   - Rocket Loader: ON (testar, pode quebrar JS)

**Estimativa:** 15min setup
**Custo:** $0/mês (plano Free)
**Prioridade:** BAIXA (avaliar após tráfego crescer)

---

### FASE 4: BUSINESS & CONVERSÃO (Prioridade BAIXA)

#### 4.1 Event Tracking (GA4)

**Objetivo:** Rastrear conversões importantes.

**Eventos importantes:**
- Lead gerado (formulário enviado)
- Artigo baixado
- Contato via WhatsApp
- Tempo em página > 3min (engajamento)
- Scroll 75% (leu até o fim)

**Implementação:**

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

#### 4.2 A/B Testing

**Objetivo:** Testar variações de páginas pra ver qual converte mais.

**Ferramentas:**
- Google Optimize (grátis, mas descontinuado em 2023)
- VWO (~$200/mês)
- Optimizely (~$500/mês)
- Solução própria (complexo)

**Recomendação:** Só se tráfego > 10k/mês e foco em otimização.

**Prioridade:** MUITO BAIXA

---

### FASE 5: DEVOPS & INFRAESTRUTURA (Prioridade BAIXA)

#### 5.1 CI/CD (Deploy Automático)

**Objetivo:** Push no GitHub → Deploy automático.

**Implementação:**

**GitHub Actions (grátis):**
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Deploy via FTP
        uses: SamKirkland/FTP-Deploy-Action@4.3.0
        with:
          server: ${{ secrets.FTP_HOST }}
          username: ${{ secrets.FTP_USER }}
          password: ${{ secrets.FTP_PASSWORD }}
          server-dir: /public_html/
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**
            **/storage/logs/**
```

**OU via SSH:**
```yaml
- name: Deploy via SSH
  uses: appleboy/ssh-action@master
  with:
    host: ${{ secrets.SSH_HOST }}
    username: ${{ secrets.SSH_USER }}
    key: ${{ secrets.SSH_KEY }}
    script: |
      cd /var/www/aegis
      git pull origin main
      composer install --no-dev
      php artisan migrate (se tiver)
```

**Estimativa:** 1h setup
**Custo:** $0
**Prioridade:** BAIXA (manual funciona bem por enquanto)

---

#### 5.2 Docker Container

**Objetivo:** Ambiente reproduzível, fácil de deployar.

**Quando vale:**
- ✅ Múltiplos ambientes (dev, staging, prod)
- ✅ Equipe grande (todos com mesmo setup)
- ❌ Projeto solo (overhead desnecessário)

**Prioridade:** MUITO BAIXA

---

### FASE 6: FEATURES EXTRAS (Prioridade BAIXA)

#### 6.1 Multi-idioma

**Objetivo:** Site em PT + EN + ES.

**Complexidade:** ALTA
- Duplicar todas as views
- Sistema de traduções
- Rotas com prefixo (/en/, /es/)
- Sitemap multi-idioma

**Só se:** Cliente internacional.

---

#### 6.2 PWA (Progressive Web App)

**Objetivo:** Site instalável como app, funciona offline.

**Implementação:**
- manifest.json
- Service Worker (cache offline)
- Ícones de app

**Só se:** Uso mobile intenso, precisa offline.

---

#### 6.3 WebSockets / Real-time

**Objetivo:** Notificações em tempo real, chat ao vivo.

**Tecnologias:**
- Socket.io
- Pusher (~$50/mês)
- Laravel Echo (se migrar pra Laravel)

**Só se:** Feature específica precisa (chat, notif real-time).

---

## ✅ CHECKLIST DE VALIDAÇÃO

### Relatórios IA:
- [ ] Relatório gerado automaticamente todo dia 8h
- [ ] Email recebido com resumo correto
- [ ] Admin (`/admin/ai-reports`) mostra últimos 30 relatórios
- [ ] Relatório individual abre corretamente
- [ ] Alertas funcionam (simular erro crítico e verificar notificação)
- [ ] Markdown renderizado corretamente

### Google Analytics:
- [ ] Dados sincronizados nas últimas 24h
- [ ] Widgets dashboard mostram métricas corretas (comparar com GA4 web)
- [ ] Gráficos renderizam sem erro de console
- [ ] Comparação de períodos calcula diferenças corretamente
- [ ] Cache de 5min funcionando (não recarrega a cada refresh)
- [ ] Páginas admin (`/admin/analytics/*`) acessíveis

### Google Search Console:
- [ ] Queries sincronizadas com posições corretas
- [ ] Core Web Vitals por página aparecem
- [ ] 404s detectadas aparecem na lista
- [ ] Alertas de queda de ranking funcionam (simular)
- [ ] Comparação de períodos funciona

### Backup:
- [ ] Backup roda automaticamente todo dia 3h
- [ ] Arquivo `.sql.gz` criado com tamanho > 0
- [ ] Arquivo `.tar.gz` criado com tamanho > 0
- [ ] Upload pro destino confirmado (Google Drive/S3/FTP)
- [ ] **CRÍTICO:** Restauração testada e funcionando (criar banco teste)
- [ ] Rotação de 30 dias funcionando (backups antigos deletados)
- [ ] Notificação de falha funciona (simular erro)

### Rate Limiting:
- [ ] Login admin bloqueado após 5 tentativas erradas
- [ ] Mensagem de erro 429 clara para usuário
- [ ] Whitelist de IPs funcionando (se configurado)
- [ ] Limite reseta após tempo configurado (5min, 1h, etc)
- [ ] APIs públicas protegidas

### Security Headers:
- [ ] https://securityheaders.com score ≥ A
- [ ] Site funciona normalmente (GTM, fontes, imagens)
- [ ] Console sem erros CSP
- [ ] HSTS funcionando (só HTTPS)

### Sitemap:
- [ ] `/sitemap.xml` acessível e válido (XML bem formado)
- [ ] Todas páginas públicas listadas
- [ ] Módulos públicos listados
- [ ] Itens dinâmicos (artigos, etc) listados
- [ ] Google Search Console aceita o sitemap
- [ ] Atualização semanal funcionando (n8n)

### Logger:
- [ ] Logs salvos no banco corretamente
- [ ] Filtros funcionam (`/admin/logs`)
- [ ] Busca funciona
- [ ] Export CSV funciona
- [ ] Alertas críticos disparam (testar)
- [ ] Auto-delete > 90 dias funcionando

---

## 🚨 PLANO DE ROLLBACK

### Se integração GA/GSC quebrar site:

**Sintomas:** Dashboard não carrega, erro 500, timeout.

**Ações:**
1. Comentar inclusão da classe no bootstrap:
   ```php
   // require_once 'core/GoogleAnalytics.php';
   ```
2. Remover widgets do dashboard (comentar includes)
3. Verificar erro em `storage/logs/error.log`
4. Verificar credenciais Service Account
5. Testar API manualmente (curl/Postman)
6. Fix + teste em ambiente local
7. Deploy novamente

**Tempo estimado de recuperação:** < 15min

---

### Se backup falhar:

**Sintomas:** n8n workflow com erro, arquivo não criado, upload falha.

**Diagnóstico:**
1. Verificar espaço em disco: `df -h`
2. Verificar credenciais destino (Google Drive, S3, FTP)
3. Testar comando mysqldump manualmente
4. Verificar permissões de escrita em `/backups`

**Ações emergenciais:**
```bash
# Backup manual imediato
mysqldump -u user -p'pass' database | gzip > manual-backup-$(date +%Y%m%d-%H%M).sql.gz
tar -czf manual-files-$(date +%Y%m%d-%H%M).tar.gz /path/to/aegis
```

**Correção automação:**
- Ajustar workflow n8n
- Validar próximo backup (acompanhar às 3h)

**Tempo estimado:** 30min diagnóstico + fix

---

### Se rate limiting bloquear usuários legítimos:

**Sintomas:** Reclamações de "não consigo fazer login", erro 429.

**Ações imediatas:**
1. Aumentar limites temporariamente:
   ```php
   RateLimit::check('admin_login:' . $_SERVER['REMOTE_ADDR'], 20, 300); // era 5, virou 20
   ```
2. Limpar tabela `rate_limits` para IP específico:
   ```sql
   DELETE FROM rate_limits WHERE key_hash = SHA2('admin_login:192.168.1.100', 256);
   ```
3. Adicionar whitelist de IPs conhecidos (escritório, VPN, etc):
   ```php
   $whitelist = ['192.168.1.100', '10.0.0.5'];
   if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
       return; // Skip rate limit
   }
   ```

**Ajuste definitivo:**
- Analisar logs: quantos requests legítimos por minuto?
- Ajustar thresholds baseado em dados reais
- Implementar captcha após X tentativas (em vez de bloquear)

**Tempo estimado:** 5min emergencial, 1h fix definitivo

---

### Se security headers quebrarem funcionalidades:

**Sintomas:** Fontes não carregam, scripts externos bloqueados, console com erros CSP.

**Diagnóstico:**
- Abrir console do navegador (F12)
- Procurar erros tipo: `Refused to load... violates Content Security Policy`

**Fix:**
1. Identificar domínio bloqueado (ex: `https://fonts.googleapis.com`)
2. Adicionar ao CSP:
   ```php
   $csp .= "font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; ";
   ```
3. Testar novamente

**Rollback temporário:**
```php
// Comentar CSP completamente (deixar outros headers)
// header("Content-Security-Policy: " . $csp);
```

**Tempo estimado:** 15min por ajuste

---

### Se relatório IA parar de gerar:

**Sintomas:** Último relatório há 2+ dias, email não chega.

**Diagnóstico:**
1. Verificar workflow n8n (status, último run, erros)
2. Verificar se Claude Code CLI funciona manualmente:
   ```bash
   cd /Users/fabiochezzi/Documents/websites/aegis
   claude --prompt "Teste"
   ```
3. Verificar API endpoint (`api/ai-reports.php`) responde:
   ```bash
   curl -X POST https://seusite.com/api/ai-reports.php \
     -H "X-API-Token: [TOKEN]" \
     -d '{"type":"test","title":"Test","content":"Test"}'
   ```

**Fix:**
- Se n8n: Reiniciar workflow
- Se Claude Code: Verificar credenciais/instalação
- Se API: Verificar logs PHP, banco de dados

**Geração manual emergencial:**
```bash
claude --prompt "Analise AEGIS e gere relatório markdown" > relatorio-manual.md
```

**Tempo estimado:** 20min diagnóstico + fix

---

## 🖥️ REQUISITOS DE INFRAESTRUTURA

### Servidor (mínimo - funcionamento básico):
- **PHP:** 7.4+ (compatível AEGIS atual)
- **MySQL:** 5.7+ ou 8.0+
- **Apache/Nginx:** Qualquer versão recente
- **Extensões PHP:**
  - `pdo_mysql` (banco de dados)
  - `curl` (APIs externas)
  - `json` (manipulação JSON)
  - `gd` ou `imagick` (manipulação de imagens)
  - `mbstring` (strings multibyte)
- **Cron jobs:** Habilitados (para backups, sync)
- **RAM:** 512MB (suficiente para sites pequenos)
- **Disco:** 10GB (código + banco + uploads)
- **SSL/HTTPS:** Obrigatório (APIs Google exigem)

### Servidor (recomendado - todas as features):
- **PHP:** 8.2+ (melhor performance, recursos modernos)
- **MySQL:** 8.0+ (JSON functions, melhor performance)
- **RAM:** 1GB+ (analytics + logs crescem)
- **Disco:** 20GB+ (backups ocupam espaço)
- **Extensões PHP adicionais:**
  - `opcache` (cache de bytecode, +30% performance)
  - `redis` ou `memcached` (cache de queries - opcional)
- **Node.js:** 18+ (se usar build tools - opcional)

### Ferramentas externas:
- **n8n:** Rodando local (`http://localhost:5678`) OU cloud
  - RAM: 512MB dedicada
  - Sempre ligado (workflows automáticos)
- **Google Cloud Project:**
  - Service Account criado
  - APIs habilitadas: Analytics Data API, Search Console API, PageSpeed Insights API
  - Credenciais JSON baixadas
- **UptimeRobot:** Conta grátis (não precisa servidor)
- **Backup destination:**
  - Google Drive (15GB grátis) OU
  - AWS S3 (~$0.50/mês) OU
  - Servidor FTP remoto

### Desenvolvimento local (opcional):
- **MAMP/XAMPP/Laravel Valet:** Ambiente PHP local
- **Git:** Versionamento
- **Composer:** Gerenciador de dependências (se usar)
- **Claude Code CLI:** Instalado e configurado

---

## 🔄 MIGRAÇÃO DE PROJETOS EXISTENTES

### Projetos AEGIS < v18 (sem integrações):

**Checklist de migração:**

1. **BACKUP COMPLETO (CRÍTICO)**
   ```bash
   mysqldump -u user -p database > backup-pre-migration.sql
   tar -czf backup-files.tar.gz /path/to/project
   ```

2. **Rodar migrations SQL:**
   ```bash
   mysql -u user -p database < database/migrations/v18-analytics.sql
   mysql -u user -p database < database/migrations/v18-gsc.sql
   mysql -u user -p database < database/migrations/v18-ai-reports.sql
   mysql -u user -p database < database/migrations/v18-rate-limits.sql
   mysql -u user -p database < database/migrations/v18-logs.sql
   ```

3. **Copiar classes novas:**
   ```bash
   cp core/GoogleAnalytics.php /path/to/project/core/
   cp core/GoogleSearchConsole.php /path/to/project/core/
   cp core/PageSpeed.php /path/to/project/core/
   cp core/RateLimit.php /path/to/project/core/
   cp core/Logger.php /path/to/project/core/
   ```

4. **Atualizar `bootstrap.php`:**
   - Adicionar security headers (copiar do template)
   - Require novas classes

5. **Criar API endpoints:**
   ```bash
   cp api/ai-reports.php /path/to/project/api/
   cp api/sync-analytics.php /path/to/project/api/
   cp api/sync-gsc.php /path/to/project/api/
   ```

6. **Copiar views admin:**
   ```bash
   cp -r admin/views/ai-reports /path/to/project/admin/views/
   cp -r admin/views/analytics /path/to/project/admin/views/
   cp -r admin/views/seo /path/to/project/admin/views/
   cp admin/views/logs.php /path/to/project/admin/views/
   ```

7. **Configurar n8n workflows:**
   - Importar JSON dos workflows
   - Ajustar URLs/credenciais para o projeto específico
   - Ativar workflows

8. **Testar TUDO:**
   - [ ] Site carrega normalmente
   - [ ] Admin acessível
   - [ ] Não há erros no console
   - [ ] Tabelas novas criadas corretamente
   - [ ] Backup manual funciona

**Tempo estimado:** 1-2h por projeto

---

### Projetos em produção (zero downtime):

**Estratégia:**

1. **Testar em staging primeiro:**
   - Clone completo do ambiente
   - Migração em staging
   - Testes completos
   - Só então produção

2. **Deploy fora de horário de pico:**
   - Madrugada (2-5h) ou
   - Domingo/feriado

3. **Modo manutenção (se necessário):**
   ```php
   // public/.maintenance (criar arquivo)
   // index.php detecta e mostra página de manutenção
   ```

4. **Rollback pronto:**
   - Backup do banco ANTES da migration
   - Git tag da versão anterior
   - SQL de rollback (DROP TABLEs novas)
   ```bash
   git tag v17.3.2-pre-migration
   git push --tags
   ```

5. **Monitoramento pós-deploy:**
   - Acompanhar logs por 30min
   - Verificar UptimeRobot (uptime OK?)
   - Testar páginas principais
   - Verificar Analytics continua funcionando

**Se algo quebrar:**
```bash
# Rollback do banco
mysql -u user -p database < backup-pre-migration.sql

# Rollback do código
git reset --hard v17.3.2-pre-migration

# Reload
php-fpm reload (ou reiniciar Apache)
```

**Tempo estimado:** 2-3h (incluindo preparação + monitoramento)

---

## 📖 DOCUMENTAÇÃO PARA USUÁRIOS

### Criar após implementar:

#### 1. **Admin Guide - Analytics** (`.claude/admin-guide/analytics.md`)
- Como interpretar métricas (sessions, users, bounce rate)
- O que significa cada gráfico
- Como comparar períodos
- Quando se preocupar (bounce rate > 70%?)
- Glossário de termos (pageview vs session vs user)

#### 2. **Admin Guide - SEO Reports** (`.claude/admin-guide/seo-reports.md`)
- Como ler posição média (11.2 é bom ou ruim?)
- O que fazer quando página cai no ranking
- Como identificar oportunidades (queries posição 11-20)
- Quando otimizar conteúdo
- Core Web Vitals: metas e como melhorar

#### 3. **Admin Guide - Alertas** (`.claude/admin-guide/alerts.md`)
- Tipos de alerta (crítico, warning, info)
- Tempo de resposta esperado
- Como priorizar (uptime > SEO > performance)
- Quem contactar em caso de site fora do ar

#### 4. **Admin Guide - Backup & Restore** (`.claude/admin-guide/backup-restore.md`)
- Como verificar se backup está rodando
- Como restaurar backup manualmente (passo a passo)
- Quando restaurar (hack, corrupção, erro humano)
- Onde estão os backups (Google Drive, S3, etc)
- **CRÍTICO:** Procedimento de emergência

---

## 🔧 MANUTENÇÃO RECORRENTE

### Semanal (15min):
- [ ] Verificar se relatórios IA geraram (últimos 7 dias)
- [ ] Revisar alertas da semana (falsos positivos?)
- [ ] Verificar top 5 erros em logs
- [ ] Conferir uptime da semana (meta: > 99.5%)

### Mensal (1h):
- [ ] **CRÍTICO:** Testar restauração de backup (criar DB teste)
- [ ] Limpar logs > 90 dias (ou verificar se cron fez)
- [ ] Revisar limites de rate limiting:
  - Alguém legítimo foi bloqueado?
  - Bots conseguindo passar?
  - Ajustar thresholds se necessário
- [ ] Verificar espaço em disco:
  ```bash
  df -h
  du -sh /path/to/backups
  ```
  - Backups crescendo muito? Ajustar rotação.
- [ ] Revisar top 10 queries GSC:
  - Oportunidades novas?
  - Páginas caindo?

### Trimestral (2-3h):
- [ ] Atualizar dependências (se usar Composer):
  ```bash
  composer update
  ```
- [ ] Revisar security headers:
  - Novos padrões? (https://securityheaders.com/blog)
  - Testar se tudo ainda funciona
- [ ] Análise de custo (se usar APIs pagas):
  - Google APIs ainda grátis?
  - S3/backup crescendo?
- [ ] Revisar documentação:
  - Algo mudou que precisa atualizar docs?
  - Novos membros da equipe precisam de tutorial?

### Anual (1 dia):
- [ ] Auditoria de segurança completa:
  - Scan de vulnerabilidades (OWASP ZAP, etc)
  - Revisar permissões de usuários
  - Trocar senhas críticas (DB, FTP, etc)
  - Revisar tokens de API (rotar se possível)
- [ ] Revisão de integrações:
  - APIs Google mudaram? (changelog)
  - Novos recursos disponíveis?
  - Deprecations chegando?
- [ ] Planejamento de novas features:
  - O que funcionou bem?
  - O que ninguém usa? (deletar?)
  - O que falta? (roadmap ano seguinte)
- [ ] Performance review:
  - Site ficou mais rápido ou mais lento?
  - Banco cresceu muito? (otimizar queries)
  - Servidor precisa upgrade?

---

## 💼 CASOS DE USO REAIS

### Cenário 1: Cliente reclama "site está lento"

**Sem o sistema:**
1. Você abre o site → "parece normal pra mim"
2. Pede screenshot pro cliente
3. Tenta reproduzir → não consegue (conexão, cache, dispositivo diferente)
4. Fica sem dados concretos → "vai passar, era instabilidade"
5. Cliente insatisfeito

**Com o sistema:**
1. Abre `/admin/analytics` → Bounce rate subiu de 42% → 68% nas últimas 6h
2. Abre `/admin/seo/vitals` → LCP degradou de 2.1s → 4.8s
3. Verifica PageSpeed → Detecta imagem nova de 5.2MB sem otimizar em `/servicos`
4. **Fix em 10min:** Comprime imagem, sobe novamente
5. Cliente vê métricas melhorando em tempo real → satisfeito

**Tempo de resolução:** 10min (vs horas de tentativa e erro)

---

### Cenário 2: Tráfego caiu 50% da noite pro dia

**Sem o sistema:**
1. Cliente avisa 3 dias depois
2. Você não sabe quando começou
3. Não sabe a causa (Google? Site? Concorrente?)
4. Investigação demorada

**Com o sistema:**
1. **Alerta automático 6h depois da queda** (n8n detecta)
2. Abre GSC → 5 páginas saíram do índice do Google
3. Verifica logs → Erro 500 nessas páginas desde ontem 14h
4. Identifica bug: Query SQL quebrada após deploy
5. **Fix imediato** → Páginas voltam ao índice em 24-48h
6. Perda minimizada (6h vs 3 dias)

**Impacto:** Economizou 70% do tráfego que seria perdido

---

### Cenário 3: Reunião semanal com cliente

**Sem o sistema:**
- "O site está bem, tivemos umas visitas..."
- Cliente: "Quantas?"
- Você: "Não sei exatamente, mas parece bom"
- Dados genéricos, sem confiança
- Cliente questiona valor do trabalho

**Com o sistema:**
- Abre `/admin/dashboard` na reunião
- "1.240 usuários ontem, +8% vs semana passada"
- "Posição média Google subiu de 13.2 → 11.8 (10 posições!)"
- "3 palavras-chave entraram no Top 10"
- "PageSpeed melhorou 12 pontos após otimizações"
- Mostra gráficos, tendências, comparações
- **Cliente vê valor concreto** → renova contrato

**Resultado:** Retenção de cliente, upsell de serviços

---

### Cenário 4: Hackear tentou brute force no login

**Sem rate limiting:**
1. Bot tenta 10.000 senhas em 5min
2. Servidor sobrecarregado → site lento/fora
3. Possível sucesso se senha fraca
4. Você descobre depois (se descobrir)

**Com rate limiting:**
1. Bot bloqueado após 5 tentativas
2. Servidor normal
3. Log registra IP + tentativas
4. **Alerta automático** → você bloqueia IP no firewall
5. Zero impacto em usuários legítimos

**Impacto:** Evitou invasão + downtime

---

### Cenário 5: Backup salvou o projeto

**Situação:**
- Dev deletou tabela crítica sem querer (`DROP TABLE users`)
- Ou: ransomware criptografou arquivos
- Ou: atualização quebrou banco irremediavelmente

**Sem backup:**
- Projeto perdido
- Recriar do zero (semanas/meses)
- Clientes perdidos
- Reputação destruída

**Com backup automático:**
1. Acessa backup de ontem (3h atrás)
2. Restaura banco em 15min
3. Restaura arquivos
4. **Perda: 3h de dados** (vs projeto inteiro)
5. Site volta ao ar
6. Cliente nem nota

**Impacto:** Salvou o negócio

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
- ✅ Não quebra entre updates (plugins WP sempre quebram)
- ✅ Leve (WP + plugins = 100MB+, AEGIS = 15MB)
- ✅ Customizável 100% (código próprio)
- ✅ Dados consolidados (não precisa abrir 5 plataformas)
- ✅ **Custo próximo de $0**

**Desvantagens AEGIS:**
- ❌ Precisa desenvolver (vs plugins prontos em 1 clique)
- ❌ Sem comunidade (suporte próprio, sem fóruns)
- ❌ Requer conhecimento técnico (não é no-code)
- ❌ Features novas demoram mais (vs marketplace WP)

**Conclusão:** AEGIS vale a pena se você é dev e quer controle + economia.

---

### Webflow / Wix / Squarespace (No-code)

| Feature | Plataformas No-code | AEGIS |
|---------|---------------------|-------|
| **Hospedagem** | Incluso (mas preso) | Qualquer servidor |
| **Customização** | Limitado (template) | 100% código próprio |
| **Analytics** | Básico incluso | GA completo |
| **SEO** | Básico incluso | GSC + controle total |
| **Custo/mês** | $23-70/mês por site | $0-1/mês |
| **Lock-in** | Total (não exporta) | Zero (código seu) |

**Quando usar no-code:** Cliente não-técnico, site simples, lançar rápido.
**Quando usar AEGIS:** Dev avançado, controle total, múltiplos projetos.

---

## ⚠️ RISCOS E MITIGAÇÕES

### Risco 1: APIs Google mudarem/quebrarem

**Probabilidade:** Baixa (Google mantém retrocompatibilidade)
**Impacto:** Alto (integrações param de funcionar)

**Mitigação:**
- ✅ Usar bibliotecas oficiais Google (versões estáveis, não beta)
- ✅ Monitorar deprecation notices:
  - https://developers.google.com/analytics/devguides/reporting/data/v1/deprecations
  - https://developers.google.com/webmaster-tools/deprecations
- ✅ Dados já salvos no MySQL (histórico preservado)
- ✅ Fallback: Se API quebrar, widgets mostram "dados não disponíveis" mas site funciona

**Ação em caso de quebra:**
1. Logs mostrarão erro de API
2. Verificar changelog Google
3. Atualizar código conforme nova API
4. Deploy fix

**Tempo de recuperação:** 1-2h (se API mudou), 1-2 dias (se mudança grande)

---

### Risco 2: Backups consumirem muito espaço

**Probabilidade:** Média (banco + uploads crescem)
**Impacto:** Médio (custo S3 ou disco cheio)

**Cenário:**
- Banco de 50MB → 500MB em 1 ano (uploads, logs)
- Backups: 500MB × 30 dias = 15GB

**Mitigação:**
- ✅ Compressão gzip (reduz 70-90%)
  - 500MB → 50-150MB comprimido
  - 15GB → 1.5-4.5GB real
- ✅ Rotação 30 dias (não infinito)
- ✅ Excluir `storage/logs` e `storage/cache` do backup (desnecessário)
- ✅ Monitorar espaço mensalmente:
  ```bash
  du -sh /backups
  ```
- ✅ Se crescer muito:
  - Reduzir rotação (30 → 15 dias)
  - Backup semanal em vez de diário (manter 4 semanas)
  - Limpar uploads antigos (imagens de 2+ anos atrás?)

**Custo real:** Mesmo em cenário de 5GB backup:
- Google Drive: Grátis (até 15GB)
- AWS S3: ~$0.12/mês
- Dropbox: Grátis (até 2GB) ou $12/mês (2TB, múltiplos projetos)

---

### Risco 3: Rate limiting bloquear usuários legítimos

**Probabilidade:** Baixa (se thresholds bem configurados)
**Impacto:** Alto (frustração de usuário, perda de conversão)

**Cenário problemático:**
- Escritório com IP compartilhado (NAT)
- 10 funcionários tentam login ao mesmo tempo
- 5 tentativas / 5min → bloqueio

**Mitigação:**
- ✅ Limites generosos inicialmente:
  - Admin login: 10 tentativas / 10min (não 5/5min)
  - APIs: 1000 requests / 1h (não 100/1min)
- ✅ Whitelist de IPs conhecidos:
  ```php
  $whitelist = ['192.168.1.0/24', '10.0.0.5']; // Escritório, VPN
  if (ipInRange($_SERVER['REMOTE_ADDR'], $whitelist)) {
      return; // Skip rate limit
  }
  ```
- ✅ Logs detalhados (ver quem foi bloqueado)
- ✅ Ajustar conforme uso real (analisar logs mensalmente)
- ✅ Mensagem clara: "Muitas tentativas. Tente novamente em 5 minutos" (não genérico "erro")
- ✅ Implementar captcha após X tentativas (melhor que bloquear):
  ```php
  if ($attempts > 3 && $attempts < 10) {
      // Mostrar captcha
  } elseif ($attempts >= 10) {
      // Bloquear
  }
  ```

---

### Risco 4: Relatórios IA custarem muito (se migrar pra API)

**Probabilidade:** Baixa (usando Claude Code local)
**Impacto:** Médio (custo recorrente)

**Cenário:** Se migrar de Claude Code local → Claude API:

**Cálculo:**
- Relatório diário = ~5k tokens input + 2k tokens output
- Claude Haiku: $0.25/M input + $1.25/M output
- Por relatório: $0.0125 input + $0.0025 output = **$0.015/relatório**
- Por mês: $0.015 × 30 = **$0.45/mês por projeto**
- 10 projetos: **$4.50/mês** (aceitável)

**Se usar Claude Sonnet (melhor qualidade):**
- $3/M input + $15/M output
- Por relatório: $0.045/relatório
- Por mês: **$1.35/mês por projeto**
- 10 projetos: **$13.50/mês**

**Mitigação:**
- ✅ **Continuar com Claude Code local** (custo $0)
- ✅ Se migrar: Usar Haiku (barato, qualidade OK)
- ✅ Limitar frequência: Diário (não horário)
- ✅ Cache de relatórios: Não regenerar se dados iguais
- ✅ Relatórios on-demand pagos pelo cliente (pass-through)

**Conclusão:** Mesmo migrando pra API, custo é aceitável ($0.45-1.35/mês por projeto).

---

### Risco 5: Complexidade de manutenção crescer

**Probabilidade:** Média (quanto mais features, mais complexo)
**Impacto:** Médio (tempo de manutenção aumenta)

**Cenário:**
- 5 integrações (GA, GSC, PageSpeed, Email, RD Station)
- 10+ workflows n8n
- 15 tabelas MySQL novas
- 20+ endpoints de API
- 50+ arquivos de código

**Se algo quebra:** Difícil diagnosticar (muitas peças móveis).

**Mitigação:**
- ✅ **Documentação completa** (este roadmap + docs técnicos)
- ✅ **Código limpo e comentado:**
  ```php
  // GoogleAnalytics::getDailyMetrics()
  // Busca métricas de tráfego para um período específico
  // @param string $propertyId - GA4 Property ID (ex: properties/123456)
  // @param string $startDate - YYYY-MM-DD
  // @param string $endDate - YYYY-MM-DD
  // @return array - ['sessions', 'users', 'pageviews', 'bounceRate', 'avgDuration']
  ```
- ✅ **Testes de validação** (checklist neste roadmap)
- ✅ **Logs estruturados** (classe Logger facilita debug)
- ✅ **Monitoramento proativo** (alertas detectam problemas antes de quebrar tudo)
- ✅ **Rollback fácil** (git tags, backup antes de mudanças)
- ✅ **Não implementar tudo de uma vez** (fases 1-3 primeiro, depois avaliar 4-6)

**Regra de ouro:** Se uma feature não é usada por 3+ meses → **DELETAR** (menos código = menos manutenção).

---

## 📊 PRIORIZAÇÃO GERAL

### 🔴 CRÍTICO (fazer AGORA após PageSpeed):
1. **Uptime Monitoring** (5min - UptimeRobot)
2. **Backup Automático** (1-2h - n8n)
3. **Security Headers** (10min - bootstrap.php)

### 🟠 ALTO (fazer essa semana):
4. **Sistema de Relatórios IA** (2-3h)
5. **Rate Limiting** (2h)
6. **Sitemap Automático** (1h)

### 🟡 MÉDIO (fazer esse mês):
7. **Google Analytics** (4-5h)
8. **Google Search Console** (5-6h)
9. **Logger Melhorado** (3h)
10. **Cruzamento de Dados** (2h)

### 🟢 BAIXO (avaliar necessidade):
11. **Event Tracking GA4** (2h)
12. **CDN Cloudflare** (15min - só se tráfego alto)
13. **CI/CD** (1h - se equipe crescer)

### ⚪ MUITO BAIXO (backlog futuro):
- A/B Testing
- Docker
- Multi-idioma
- PWA
- WebSockets

---

## 💰 ESTIMATIVA DE CUSTOS

### Setup Inicial:
- **Desenvolvimento:** ~35-45h total (fases 1-3)
- **Custo dev:** $0 (você + Claude Code)
- **Tempo:** 4 semanas (paralelo com outros projetos)

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

### Semana 1 (pós-PageSpeed):
**Tempo:** 4-5h
- ✅ UptimeRobot (5min)
- ✅ Security Headers (10min + 30min teste)
- ✅ Backup automático (1-2h setup n8n)
- ✅ Relatórios IA (2-3h dev + n8n)

**Validação:**
- [ ] Site monitorado (UptimeRobot ativo)
- [ ] https://securityheaders.com score A
- [ ] Backup manual funciona
- [ ] Relatório gerado manualmente

---

### Semana 2:
**Tempo:** 7-8h
- ✅ Rate Limiting (2h classe + implementação)
- ✅ Sitemap (1h código + n8n)
- ✅ Google Analytics:
  - Setup Service Account (30min)
  - Desenvolvimento (4-5h)

**Validação:**
- [ ] Login bloqueado após X tentativas
- [ ] Sitemap.xml válido
- [ ] GA sincronizando dados
- [ ] Widgets dashboard funcionando

---

### Semana 3:
**Tempo:** 8-9h
- ✅ Google Search Console (5-6h dev)
- ✅ Logger melhorado (3h classe + view admin)

**Validação:**
- [ ] GSC sincronizando queries
- [ ] Core Web Vitals aparecendo
- [ ] Logs estruturados no banco
- [ ] Busca de logs funciona

---

### Semana 4:
**Tempo:** 4-5h
- ✅ Cruzamento de dados (2h queries + IA)
- ✅ Ajustes finais (1h)
- ✅ Documentação (2h escrita + screenshots)
- ✅ Testes end-to-end (1h)

**Validação:**
- [ ] Relatório IA inclui cruzamento de dados
- [ ] Oportunidades detectadas automaticamente
- [ ] Docs criados (admin-guide/*)
- [ ] Checklist completo ✅

---

### Total: ~25-30h em 1 mês

**Distribuição:**
- Dev backend: 15h (50%)
- Dev frontend: 7h (23%)
- Setup/config: 4h (13%)
- Testes: 3h (10%)
- Docs: 2h (7%)

---

## 📚 DOCUMENTAÇÃO NECESSÁRIA

**Criar após implementar:**

### Técnica:
- `.claude/integrations/google-analytics.md` - Setup, API, troubleshooting
- `.claude/integrations/search-console.md` - Setup, queries, Core Web Vitals
- `.claude/integrations/ai-reports.md` - Como funciona, customizar relatórios
- `.claude/security/rate-limiting.md` - Configuração, whitelist, ajustes
- `.claude/backup-restore-guide.md` - Restauração passo a passo

### Usuário (Admin):
- `docs/admin-guide/analytics.md` - Como ler métricas, glossário
- `docs/admin-guide/seo-reports.md` - Interpretar GSC, Core Web Vitals
- `docs/admin-guide/alerts.md` - Tipos de alerta, priorização
- `docs/admin-guide/backup-restore.md` - Procedimento de emergência

### Visual:
- Screenshots de cada tela admin
- Vídeo tutorial de 5min (opcional)
- FAQ com problemas comuns

---

## 🚀 OUTRAS IDEIAS (Brainstorm Futuro)

### Automações Inteligentes:

1. **Auto-otimização de imagens**
   - n8n detecta imagens > 500KB em `/uploads`
   - Chama TinyPNG API (500 compressões grátis/mês)
   - Substitui original automaticamente
   - Notifica: "3 imagens otimizadas, economia: 2.1MB"

2. **Auto-geração de meta descriptions**
   - Detecta páginas sem meta description
   - Claude analisa conteúdo da página
   - Gera descrição otimizada (150-160 chars)
   - Salva no banco → renderiza automaticamente

3. **Monitoramento de concorrentes**
   - Lista 3-5 concorrentes principais
   - Semanalmente: Scrape PageSpeed, ranking palavras-chave
   - Compara com seu site
   - Alerta: "Concorrente X melhorou PageSpeed de 68 → 92"

4. **Content suggestions (SEO)**
   - IA analisa GSC: queries com impressões altas mas cliques baixos
   - Ex: "dry wash preço" - 5k impressões, posição 12, 80 cliques
   - Sugere: "Criar página '/precos' otimizada pra essa keyword → potencial +300 cliques/mês"

5. **Auto-fix de problemas simples**
   - Detecta imagem sem atributo `alt` → IA gera alt baseado no contexto
   - Detecta link quebrado 404 → Sugere redirecionamento 301 pra página similar
   - Detecta código duplicado → Refatora automaticamente (com aprovação)

---

### Integrações Adicionais:

1. **Microsoft Clarity (Heatmaps)**
   - Session recordings (ver o que usuário faz)
   - Heatmaps (onde clica, onde scrolls)
   - **Custo:** $0 (grátis ilimitado)
   - **Setup:** 10min (adicionar script)
   - **Valor:** Identificar problemas de UX

2. **WhatsApp Business API**
   - Chat integrado no site (widget)
   - Automações: "Olá! Como posso ajudar?" (bot)
   - Encaminha pra atendente humano se necessário
   - **Custo:** Variável (~$5-20/mês)
   - **Valor:** Conversão +15-30%

3. **Zapier / Make (n8n alternativa)**
   - Mais integrações prontas (5.000+ apps)
   - Interface mais polida
   - **Custo:** $20-30/mês
   - **Quando usar:** Se n8n não tem integração que você precisa

4. **Notion / Airtable (gestão de conteúdo)**
   - Cliente edita conteúdo no Notion (interface amigável)
   - Webhook sincroniza com banco AEGIS
   - **Custo:** $0-10/mês
   - **Valor:** Cliente não-técnico consegue editar

---

### Performance Avançada:

1. **Image CDN (Cloudinary / imgix)**
   - Resize automático conforme dispositivo (mobile = menor)
   - Conversão WebP automática (-50% tamanho)
   - Lazy load inteligente (só carrega quando vai aparecer)
   - **Custo:** $0-50/mês (depende do tráfego)
   - **Valor:** PageSpeed +10-20 pontos

2. **Database query optimization**
   - Slow query log MySQL (queries > 1s)
   - Script analisa e sugere índices:
     ```sql
     -- Detectado: SELECT * FROM gsc_queries WHERE date BETWEEN...
     -- Sugestão: CREATE INDEX idx_date ON gsc_queries(date);
     ```
   - Auto-add indexes (com aprovação)

3. **Asset bundling (Webpack / Vite)**
   - Combina 10 arquivos CSS → 1 arquivo
   - Tree shaking (remove código não usado)
   - Code splitting (carrega só o necessário)
   - **Resultado:** -60% tamanho JS/CSS

---

### SEO Avançado:

1. **Schema.org automático**
   - Detecta tipo de página (artigo, produto, serviço)
   - Gera JSON-LD automaticamente:
     ```json
     {
       "@type": "Article",
       "headline": "Como funciona lavagem a seco",
       "author": "DryWash",
       "datePublished": "2026-02-10"
     }
     ```
   - Google mostra rich snippets (estrelas, preço, etc)

2. **Internal linking automático**
   - IA analisa conteúdo de cada página
   - Sugere links internos relevantes:
     - "Página '/servicos' deveria linkar pra '/precos' (mencionou 'valores')"
   - Melhora SEO + UX

3. **Content decay detection**
   - Detecta artigos com tráfego caindo consistentemente
   - Ex: "Artigo X perdeu 40% tráfego em 3 meses"
   - Sugere: Atualizar conteúdo, adicionar informações recentes

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

## 🎯 RESULTADO ESPERADO

**Antes (sem sistema):**
- ❌ Descobre problemas quando cliente reclama (24-48h depois)
- ❌ Decisões baseadas em "achismo" (sem dados concretos)
- ❌ Backup manual (quando lembra)
- ❌ Uptime desconhecido (quanto tempo ficou fora do ar?)
- ❌ SEO no escuro (posição no Google? Não sabe)
- ❌ Performance medida "no olhômetro"
- ❌ Cliente questiona valor ("o que você fez essa semana?")

**Depois (com sistema completo):**
- ✅ Descobre problemas **antes** dos clientes (< 1h detecção)
- ✅ Decisões baseadas em **dados reais** (métricas, gráficos, tendências)
- ✅ Backup automático **diário** (30 dias histórico, testado mensalmente)
- ✅ Uptime 99.9%+ **comprovado** (dashboard público se quiser)
- ✅ SEO **transparente** (cliente vê ranking subindo)
- ✅ Performance **objetiva** (score 92/100 vs 68/100)
- ✅ Cliente vê **valor concreto** ("1.240 usuários, +8% vs semana passada")

---

## 💼 IMPACTO NO NEGÓCIO

### Para você (desenvolvedor):
- ⏱️ **Tempo economizado:** 5-10h/mês (vs investigar problemas manualmente)
- 💰 **Custo economizado:** $400-800/ano por projeto (vs plugins pagos)
- 😌 **Stress reduzido:** Alertas proativos (não apaga incêndio)
- 📈 **Upsell:** Mostra valor → cliente paga mais
- 🎯 **Profissionalismo:** Dashboard classe mundial

### Para o cliente:
- 🚀 **Site mais rápido:** PageSpeed otimizado
- 📊 **Transparência:** Acesso a métricas reais
- 🔒 **Segurança:** Backups + proteção
- 📈 **Resultados:** SEO melhorando consistentemente
- 💪 **Confiança:** Problemas resolvidos antes de afetar

---

## 🏆 DIFERENCIAIS COMPETITIVOS

**O que outros devs NÃO têm:**
1. Relatórios IA automáticos (único)
2. Dashboard consolidado (GA + GSC + PageSpeed + Logs)
3. Custo $0/mês (vs centenas em plugins)
4. Histórico permanente (MySQL vs dashboards temporários)
5. Código próprio (customizável 100%)
6. Alertas proativos (detecta antes de quebrar)

**Você pode vender como:**
- "Monitoramento 24/7 com IA"
- "Dashboard executivo em tempo real"
- "Alertas proativos de problemas"
- "Relatórios semanais automatizados"
- **Charge extra:** $50-200/mês por projeto (justificável pelo valor)

---

**Versão:** 2.0.0
**Criado em:** 10/02/2026
**Última atualização:** 10/02/2026
**Responsável:** Claude (Guardião AEGIS)
**Páginas:** 150+ linhas de roadmap classe mundial
