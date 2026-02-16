# 🔧 Configuração dos Workflows PageSpeed

## 📍 Como Trocar entre Localhost e Produção

Cada workflow tem um node **"⚙️ Config"** no início que define a URL base do AEGIS.

### Passo a Passo:

1. Abra o workflow no n8n
2. Clique no primeiro node **"⚙️ Config"**
3. Edite o campo `BASE_URL`:

**Para DESENVOLVIMENTO (localhost):**
```
http://localhost:5757/aegis
```

**Para PRODUÇÃO:**
```
https://seudominio.com
```

4. Salve o workflow
5. **IMPORTANTE:** Ative o workflow (toggle no canto superior direito)

---

## 🔄 URLs que Precisam Funcionar

Para os workflows funcionarem, essas URLs precisam estar acessíveis:

### 1. CSRF Token
```
GET {BASE_URL}/admin/cache.php?action=get_csrf
```
**Retorna:** `{"csrf_token": "abc123..."}`

### 2. Trigger (Pegar URLs)
```
POST {BASE_URL}/admin/api/pagespeed-trigger.php
Body: csrf_token=abc123
```
**Retorna:** Lista de URLs + config

### 3. Save (Salvar Relatório)
```
POST {BASE_URL}/admin/api/pagespeed-save.php
Body: JSON com dados do relatório
```
**Retorna:** `{"success": true}`

---

## 🧪 Testando a Conexão

Antes de importar os workflows, teste se as URLs funcionam:

**Teste 1 - CSRF:**
```bash
curl http://localhost:5757/aegis/admin/cache.php?action=get_csrf
# Deve retornar: {"csrf_token":"..."}
```

**Teste 2 - Trigger (precisa do CSRF):**
```bash
# Pegue o token do teste anterior
curl -X POST \
  -d "csrf_token=SEU_TOKEN_AQUI" \
  http://localhost:5757/aegis/admin/api/pagespeed-trigger.php
# Deve retornar: {"success":true, "urls":[...]}
```

---

## 🚀 Ambientes Suportados

### ✅ Localhost (Desenvolvimento)
- n8n: `http://localhost:5678`
- AEGIS: `http://localhost:5757/aegis`
- **Limitação:** Workflows param quando Mac desliga

### ✅ Produção (n8n Digital Ocean)
- n8n: `https://n8n-n8n.tqqo2j.easypanel.host`
- AEGIS: `https://seudominio.com`
- **Vantagem:** Workflows rodam 24/7

### ⚠️ Híbrido (NÃO FUNCIONA)
- n8n: Digital Ocean (online)
- AEGIS: Localhost (offline para internet)
- **Problema:** n8n não alcança localhost

**Solução para híbrido:** Usar Cloudflare Tunnel ou ngrok temporariamente

---

## 📋 Checklist de Deploy

Ao mover AEGIS de localhost → produção:

- [ ] Fazer deploy do AEGIS no servidor
- [ ] Configurar domínio (ex: `https://aegis.seudominio.com`)
- [ ] Testar endpoints manualmente (CSRF, trigger, save)
- [ ] Abrir workflows no n8n
- [ ] Mudar `BASE_URL` no node "⚙️ Config" de cada workflow
- [ ] Salvar workflows
- [ ] Reativar workflows
- [ ] Testar análise manual no admin
- [ ] Verificar se dados aparecem no banco

---

## 🔐 Segurança em Produção

### Obrigatório:
- [ ] HTTPS habilitado (não HTTP)
- [ ] Webhook secret configurado no Settings
- [ ] Firewall: permitir apenas IP do n8n (opcional mas recomendado)

### IP do n8n Digital Ocean:
Para adicionar ao firewall/whitelist, descubra o IP:
```bash
# No n8n, rode um workflow com HTTP Request para:
https://api.ipify.org?format=json
# Retorna o IP público do seu n8n
```

---

## ❓ FAQ

**P: Posso ter workflows diferentes para localhost e produção?**
R: Sim! Duplique os workflows e nomeie:
- "AEGIS PageSpeed - Auto (LOCAL)"
- "AEGIS PageSpeed - Auto (PROD)"

**P: Preciso trocar algo além da BASE_URL?**
R: Não! Tudo mais é dinâmico (API key vem do Settings)

**P: O que acontece se AEGIS cair?**
R: Workflow falha, n8n retenta automaticamente (configurável)

**P: Posso rodar análises em múltiplos projetos?**
R: Sim! Duplique os workflows e mude a BASE_URL para cada projeto

---

## 📞 Suporte

Se encontrar erro:
1. Verifique logs do n8n (Executions tab)
2. Teste URLs manualmente com curl
3. Verifique logs do AEGIS: `/Applications/MAMP/logs/php_error.log`
