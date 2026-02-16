# Workflows n8n - PageSpeed Insights

## Arquivos Disponíveis

1. **pagespeed-auto.json** - Análise Automática (Schedulada)
2. **pagespeed-manual.json** - Análise Manual (Webhook)

## Como Importar

### 1. Acessar n8n
```
http://localhost:5678
```

### 2. Importar Workflows

1. Clique em **Workflows** > **Add Workflow** > **Import from File**
2. Selecione `pagespeed-auto.json`
3. Clique em **Import**
4. Repita para `pagespeed-manual.json`

## Configuração dos Workflows

### Workflow 1: Análise Automática

**O que faz:** Roda periodicamente (diariamente às 3h por padrão)

**Passos:**
1. Abra o workflow **AEGIS PageSpeed - Análise Automática**
2. Clique no node **Schedule Trigger**
3. Configure o cron expression conforme desejado:
   - Diário: `0 3 * * *` (3h da manhã)
   - Semanal: `0 3 * * 0` (Domingo 3h)
   - Mensal: `0 3 1 * *` (Dia 1 de cada mês às 3h)
4. Salve e **Ative** o workflow (toggle no canto superior direito)

**Importante:** O workflow pegará automaticamente as configurações do Settings do AEGIS (frequência, horário, estratégias).

### Workflow 2: Análise Manual

**O que faz:** Dispara análise quando você clicar no botão no painel AEGIS

**Passos:**
1. Abra o workflow **AEGIS PageSpeed - Análise Manual**
2. Clique no node **Webhook Trigger**
3. Copie a **Webhook URL** (ex: `http://localhost:5678/webhook/aegis-pagespeed-manual`)
4. Cole esta URL no campo apropriado do painel AEGIS (quando implementado)
5. Salve e **Ative** o workflow

**Importante:** Este workflow responde imediatamente ao admin e processa em background.

## Fluxo de Dados

### Ambos os workflows seguem este fluxo:

1. **Buscar URLs** → Consulta AEGIS para pegar lista de páginas publicadas
2. **Split URLs** → Divide em lotes para processar uma por vez
3. **Split Strategies** → Divide entre mobile/desktop conforme configurado
4. **Analyze PageSpeed** → Chama API do Google PageSpeed Insights
5. **Transform Data** → Extrai apenas dados essenciais (~5KB vs 350KB)
6. **Save to AEGIS** → Envia dados para salvar no banco via webhook
7. **Wait (Rate Limit)** → Aguarda 2s entre requests (limite: 25k/dia)

## Dados Extraídos

### Lab Data (Sintético)
- LCP (Largest Contentful Paint)
- FCP (First Contentful Paint)
- CLS (Cumulative Layout Shift)
- INP (Interaction to Next Paint)
- Speed Index
- TTI (Time to Interactive)
- TBT (Total Blocking Time)

### Field Data (Usuários Reais)
- LCP, FCP, CLS, INP de usuários reais
- Categoria: FAST / AVERAGE / SLOW

### Extras
- Top 5 oportunidades de melhoria
- Diagnósticos (tamanho DOM, requests, bytes)

## Segurança

- **Webhook Secret:** Todos os dados enviados incluem o `webhook_secret` configurado no Settings
- **CSRF Token:** Workflows pegam token CSRF do endpoint `/admin/cache.php`
- **Rate Limiting:** Delay de 2s entre análises para respeitar limites do Google

## Troubleshooting

### Erro: "Token CSRF não fornecido"
- Verifique se o endpoint `/admin/cache.php?action=get_csrf` está funcionando
- Teste: `curl http://localhost:5757/aegis/admin/cache.php?action=get_csrf`

### Erro: "Webhook secret inválido"
- Verifique se o webhook_secret foi salvo corretamente no Settings
- Vá em `/admin/settings` e salve as configurações PageSpeed novamente

### Erro: "API Key inválida"
- Verifique se a Google API Key foi configurada corretamente
- Teste a API manualmente: https://developers.google.com/speed/docs/insights/v5/get-started

### Erro: Rate Limit Exceeded
- O Google permite 25.000 requests/dia
- Reduza a frequência de análises ou número de URLs

## Monitoramento

Acesse no n8n:
- **Executions** → Ver histórico de execuções
- **Logs** → Ver erros e detalhes
- **Metrics** → Ver performance dos workflows

## Próximos Passos

Após importar e configurar os workflows:
1. Ative ambos os workflows
2. Teste o workflow manual primeiro
3. Verifique se dados aparecem no banco: `tbl_pagespeed_reports`
4. Configure o schedule do workflow automático conforme desejado
