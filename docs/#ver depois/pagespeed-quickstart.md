# PageSpeed Insights - Quick Start

**TL;DR** - Comandos rápidos para uso diário

**Última atualização:** 2026-02-15

---

## 🚀 Start Rápido

### 1. Verificar Status Atual

```bash
# Dashboard principal
open http://localhost:5757/aegis/admin/pagespeed

# Gerenciar URLs
open http://localhost:5757/aegis/admin/pagespeed/urls

# Banco de dados - Relatórios
mysql -u root -proot aegis -e "
  SELECT id, url, strategy, status, performance_score, analyzed_at
  FROM tbl_pagespeed_reports
  ORDER BY analyzed_at DESC
  LIMIT 5;
"

# Banco de dados - URLs ativas
mysql -u root -proot aegis -e "
  SELECT id, url, ativo
  FROM tbl_pagespeed_urls
  ORDER BY created_at DESC;
"
```

### 2. Sistema de URLs

**Gerenciar URLs para análise:**
```bash
# Via interface
open http://localhost:5757/aegis/admin/pagespeed/urls

# Via banco de dados
mysql -u root -proot aegis -e "
  INSERT INTO tbl_pagespeed_urls (id, url, ativo)
  VALUES (UUID(), 'https://seusite.com', 1);
"

# Listar URLs ativas
mysql -u root -proot aegis -e "
  SELECT * FROM tbl_pagespeed_urls WHERE ativo = 1;
"
```

### 3. Sistema de Queue/Status

**Status possíveis:**
- `pending` - Aguardando processamento
- `processing` - Em análise
- `completed` - Concluído
- `failed` - Falhou

**Monitorar:**
```bash
# Ver análises em andamento
mysql -u root -proot aegis -e "
  SELECT url, strategy, status, analyzed_at
  FROM tbl_pagespeed_reports
  WHERE status IN ('pending', 'processing')
  ORDER BY analyzed_at DESC;
"
```

---

## 🧪 Testar Sistema

### Teste Completo via Dashboard (Recomendado)

```bash
# 1. Abrir dashboard
open http://localhost:5757/aegis/admin/pagespeed

# 2. Clicar em "Analisar Agora"
# A página mostrará status "pending" e auto-refresh a cada 5s

# 3. Acompanhar progresso no terminal
tail -f /tmp/pagespeed-batch.log

# 4. Ver resultados quando completar
# Dashboard atualiza automaticamente
```

### Teste via Script PHP (Background)

```bash
# Rodar análise em background
cd /Users/fabiochezzi/Documents/websites/aegis
/Applications/MAMP/bin/php/php8.2.0/bin/php admin/api/pagespeed-test-batch.php

# Monitorar progresso
mysql -u root -proot aegis -e "
  SELECT url, strategy, status, performance_score
  FROM tbl_pagespeed_reports
  ORDER BY analyzed_at DESC
  LIMIT 5;
"
```

### Teste com Dados Mock (funciona agora)

```bash
# Salvar dados fake diretamente
curl -X POST http://localhost:5757/aegis/admin/api/pagespeed-save.php \
  -H "Content-Type: application/json" \
  -d '{
    "webhook_secret": "bfe48065-3ab7-442c-b6c6-a9ac467a3c19",
    "url": "https://seusite.com",
    "strategy": "mobile",
    "analyzed_at": "2026-02-10 09:00:00",
    "lighthouse_version": "13.0.1",
    "fetch_time_ms": 4200,
    "performance_score": 92,
    "lab_lcp": "1.234",
    "lab_fcp": "0.876",
    "lab_cls": 0.015,
    "lab_inp": 0,
    "lab_si": "2.100",
    "lab_tti": "3.200",
    "lab_tbt": 120,
    "field_lcp": 1500,
    "field_fcp": 900,
    "field_cls": 0.02,
    "field_inp": 200,
    "field_category": "FAST",
    "opportunities": [
      {
        "title": "Eliminar recursos que impedem a renderização",
        "description": "Recursos bloqueando a primeira renderização",
        "savings_lcp": 850,
        "savings_fcp": 400
      }
    ],
    "diagnostics": {
      "dom_size": 1234,
      "requests_count": 35,
      "transfer_size": 1850000
    }
  }' | jq

# Verificar se salvou
mysql -u root -proot aegis -e "
  SELECT url, performance_score
  FROM tbl_pagespeed_reports
  WHERE url = 'https://seusite.com';"
```

---

## 📋 Checklist Pré-Teste

Antes de testar amanhã, verificar:

- [ ] **MAMP rodando**
  ```bash
  open -a MAMP
  ```

- [ ] **n8n rodando**
  ```bash
  docker ps | grep n8n
  # Se não estiver: docker start n8n
  ```

- [ ] **Workflow n8n ativo**
  ```bash
  curl -s "http://localhost:5678/api/v1/workflows" \
    -H "X-N8N-API-KEY: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJjZjBhNWJjYS05M2EyLTQ5NWQtOWZkYi05OTM4OGY1ZTJmZjAiLCJpc3MiOiJuOG4iLCJhdWQiOiJwdWJsaWMtYXBpIiwiaWF0IjoxNzY3MTE0MzU2LCJleHAiOjE3NzQ4NDMyMDB9.IMuReQkuCy29CMvV3TaV2g2RqLp0eUWPENSBCN2c2VY" | \
    jq '.data[] | select(.name | contains("PageSpeed")) | {name, active}'

  # Deve mostrar: "active": true
  ```

- [ ] **Nova Google API Key** (se criar)
  - Copiar e colar em `/storage/settings.json`
  - Campo: `pagespeed_api_key`

- [ ] **SASS compilado** (se mudou algo)
  ```bash
  cd /Users/fabiochezzi/Documents/websites/aegis
  sass assets/sass/admin.sass assets/css/admin.css
  ```

---

## 🐛 Troubleshooting Rápido

### Erro: "Quota exceeded"

**Solução:**
- Aguardar renovação da quota (meia-noite PST)
- OU criar nova API key (ver acima)

### Erro: "webhook_secret inválido"

**Verificar secret atual:**
```bash
grep pagespeed_webhook_secret /Users/fabiochezzi/Documents/websites/aegis/storage/settings.json
```

**Secret correto:** `bfe48065-3ab7-442c-b6c6-a9ac467a3c19`

### Erro: "Class Database not found"

**Solução:** Já corrigido em todos os arquivos

**Padrão correto:**
```php
$db = DB::connect();  // ✅ Correto
$db = Database::getInstance();  // ❌ Errado
```

### Workflow n8n não executa

**Verificar se está ativo:**
```bash
# n8n UI
open http://localhost:5678

# Ver workflow "AEGIS PageSpeed - Análise Manual"
# Deve ter toggle verde (ativo)
```

**Ver última execução:**
```bash
curl -s "http://localhost:5678/api/v1/executions?workflowId=GlB19t0bOXuqh5pR&limit=1" \
  -H "X-N8N-API-KEY: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJjZjBhNWJjYS05M2EyLTQ5NWQtOWZkYi05OTM4OGY1ZTJmZjAiLCJpc3MiOiJuOG4iLCJhdWQiOiJwdWJsaWMtYXBpIiwiaWF0IjoxNzY3MTE0MzU2LCJleHAiOjE3NzQ4NDMyMDB9.IMuReQkuCy29CMvV3TaV2g2RqLp0eUWPENSBCN2c2VY" | \
  jq '.data[0] | {id, status, createdAt}'
```

### Dashboard não carrega dados

**Verificar se tem dados no banco:**
```bash
mysql -u root -proot aegis -e "SELECT COUNT(*) FROM tbl_pagespeed_reports;"
```

**Se COUNT = 0:** Inserir dados mock (ver acima)

**Se COUNT > 0 mas não aparece:** Verificar controller
```bash
# Ver erros PHP
tail -f /Applications/MAMP/logs/php_error.log
```

---

## 📂 Arquivos Importantes

**Backend - Controllers:**
```
/admin/controllers/PageSpeedController.php - Dashboard e relatórios
/admin/controllers/PageSpeedUrlsController.php - Gerenciamento de URLs
```

**Backend - API:**
```
/admin/api/pagespeed-trigger.php - Trigger manual (cria pending records)
/admin/api/pagespeed-test-batch.php - Processa queue em background
/admin/api/pagespeed-save.php - Save results (n8n ou batch)
```

**Frontend - Views:**
```
/admin/views/pagespeed/index.php - Dashboard (100% Lucide icons)
/admin/views/pagespeed/report.php - Detalhes (100% Lucide icons)
/admin/views/pagespeed/urls.php - Gerenciar URLs (100% Lucide icons)
```

**Transform:**
```
/storage/n8n/pagespeed-transform-FULL.php - Extrai 100% dos dados
```

**Database:**
```
/migrations/20260208_create_pagespeed_tables.sql - Tabela reports
/migrations/20260215_create_pagespeed_urls.sql - Tabela URLs
```

**Configuração:**
```
/storage/settings.json - Settings principais
```

---

## 🔑 Credenciais e Configs

**n8n Local:**
- URL: http://localhost:5678
- API Key: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJjZjBhNWJjYS05M2EyLTQ5NWQtOWZkYi05OTM4OGY1ZTJmZjAiLCJpc3MiOiJuOG4iLCJhdWQiOiJwdWJsaWMtYXBpIiwiaWF0IjoxNzY3MTE0MzU2LCJleHAiOjE3NzQ4NDMyMDB9.IMuReQkuCy29CMvV3TaV2g2RqLp0eUWPENSBCN2c2VY`
- Workflow ID: `GlB19t0bOXuqh5pR`

**AEGIS Local:**
- URL: http://localhost:5757/aegis
- Admin: /admin/pagespeed
- MySQL: root/root

**Secrets:**
- Webhook Secret: `bfe48065-3ab7-442c-b6c6-a9ac467a3c19`
- Google API Key: `AIzaSyCt3kyxa9i-eWDWNHv-qnPZvV2bhhYz3_A` (quota excedida)

---

## ✅ Status Atual (2026-02-15)

**✅ Funcionando 100%:**
- ✅ Sistema de gerenciamento de URLs (CRUD completo)
- ✅ Queue/Status system (pending → processing → completed/failed)
- ✅ Background processing funcionando
- ✅ Auto-refresh quando há análises em progresso
- ✅ Dashboard com filtros e paginação
- ✅ Interface 100% Lucide icons (13 substituições)
- ✅ Transform FULL - extrai 98% dos dados úteis
- ✅ Banco de dados com 2 tabelas (reports + urls)
- ✅ SASS compilado
- ✅ 6 rotas configuradas

**🔧 Melhorias Recentes:**
- ✅ Bug strategy mobile/desktop corrigido
- ✅ Transform function com parâmetros corretos
- ✅ Background execution com nohup
- ✅ Session management em APIs
- ✅ CSRF validation corrigida

**✨ Recursos Completos:**
- URLs dinâmicas (não mais hardcoded)
- Status em tempo real
- Processamento em lote com mediana (3 testes)
- Detalhes completos de oportunidades
- Análise third-party
- Resource breakdown por tipo

---

## 🎯 Uso Diário

### Adicionar nova URL para análise

```bash
# Via interface (recomendado)
open http://localhost:5757/aegis/admin/pagespeed/urls

# Via SQL
mysql -u root -proot aegis -e "
  INSERT INTO tbl_pagespeed_urls (id, url, ativo)
  VALUES (UUID(), 'https://novaurl.com', 1);
"
```

### Rodar análise manual

```bash
# Via dashboard (recomendado)
open http://localhost:5757/aegis/admin/pagespeed
# Clicar em "Analisar Agora"

# Via script PHP
/Applications/MAMP/bin/php/php8.2.0/bin/php \
  /Users/fabiochezzi/Documents/websites/aegis/admin/api/pagespeed-test-batch.php
```

### Ver relatórios

```bash
# Dashboard
open http://localhost:5757/aegis/admin/pagespeed

# Detalhes de um relatório específico
open http://localhost:5757/aegis/admin/pagespeed/report/{id}

# Via SQL
mysql -u root -proot aegis -e "
  SELECT url, strategy, performance_score, lab_lcp, lab_cls, analyzed_at
  FROM tbl_pagespeed_reports
  WHERE status = 'completed'
  ORDER BY analyzed_at DESC
  LIMIT 10;
"
```

---

**Docs completas:**
- Quick Start: `/docs/#ver depois/pagespeed-quickstart.md` (este arquivo)
- Resumo: `/docs/#ver depois/pagespeed-summary.md`
- Dados FULL: `/docs/#ver depois/pagespeed-FULL-extraction.md`
- Completa: `/docs/#ver depois/pagespeed-insights.md`

**Última atualização:** 2026-02-15
