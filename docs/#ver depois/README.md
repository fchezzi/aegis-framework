# AEGIS Framework - Documentação

Documentação completa do sistema PageSpeed Insights implementado em 2026-02-09.

---

## 📚 Documentos Disponíveis

### 1. [pagespeed-insights.md](./pagespeed-insights.md) - Documentação Completa
**30+ páginas | Leitura: 15 min**

Documentação técnica detalhada de todo o sistema. Consulte este documento para:
- Arquitetura completa
- Todos os arquivos criados/modificados
- Fluxo de funcionamento detalhado
- Troubleshooting avançado
- Próximos passos
- Dados técnicos (Core Web Vitals, thresholds, etc.)

**Quando usar:** Implementação, debugging, arquitetura

---

### 2. [pagespeed-quickstart.md](./pagespeed-quickstart.md) - Quick Start
**5 páginas | Leitura: 3 min**

Guia rápido para começar a usar o sistema. Contém:
- Comandos essenciais
- Checklist pré-teste
- Troubleshooting rápido
- Credenciais e configs
- Próximo passo amanhã

**Quando usar:** Retomar trabalho rapidamente, testes

---

### 3. [pagespeed-summary.md](./pagespeed-summary.md) - Resumo Executivo
**8 páginas | Leitura: 5 min**

Resumo executivo do que foi implementado. Inclui:
- Lista de todos os arquivos
- Status de testes
- Bugs corrigidos
- Checklist final
- Próximas ações

**Quando usar:** Overview rápido, status do projeto

---

## 🚀 Como Começar Amanhã

### Opção Rápida (Comandos)
```bash
cd /Users/fabiochezzi/Documents/websites/aegis

# Testar tudo
./scripts/test-pagespeed.sh all

# Abrir dashboard
./scripts/test-pagespeed.sh dashboard
```

### Opção Documentada
1. Abrir: [pagespeed-quickstart.md](./pagespeed-quickstart.md)
2. Seguir seção "🎯 Próximo Passo Amanhã"
3. Executar comandos da seção "Checklist Pré-Teste"

---

## 📂 Arquivos Auxiliares

### Scripts de Teste
**`/scripts/test-pagespeed.sh`**
```bash
# Executar todos os testes
./scripts/test-pagespeed.sh all

# Comandos disponíveis:
# csrf, urls, save, db, api, workflow, dashboard, n8n, help
```

### Dados Mock
**`/storage/mock-pagespeed-data.json`**

JSON pronto para testar salvamento sem chamar Google API:
```bash
curl -X POST http://localhost:5757/aegis/admin/api/pagespeed-save.php \
  -H "Content-Type: application/json" \
  -d @storage/mock-pagespeed-data.json
```

---

## 🔍 Localização Rápida

### Preciso encontrar...

**"Como funciona o fluxo?"**
→ [pagespeed-insights.md](./pagespeed-insights.md) - Seção "Fluxo de Funcionamento"

**"Qual endpoint usar?"**
→ [pagespeed-insights.md](./pagespeed-insights.md) - Seção "Arquivos do Sistema"

**"Como testar?"**
→ [pagespeed-quickstart.md](./pagespeed-quickstart.md) - Seção "Testar Sistema"

**"Deu erro, e agora?"**
→ [pagespeed-quickstart.md](./pagespeed-quickstart.md) - Seção "Troubleshooting Rápido"

**"O que foi implementado?"**
→ [pagespeed-summary.md](./pagespeed-summary.md) - Seção "Arquivos Criados"

**"Qual o próximo passo?"**
→ [pagespeed-summary.md](./pagespeed-summary.md) - Seção "Próximas Ações"

---

## 🎯 Problema Atual

**Status:** 🟡 Sistema funcional, bloqueado por quota Google API

**Solução:** Criar nova API key OU aguardar renovação

**Detalhes:** Ver qualquer um dos 3 docs acima

---

## 📞 Suporte Rápido

### Banco de Dados
```bash
mysql -u root -proot aegis -e "SELECT * FROM tbl_pagespeed_reports LIMIT 5;"
```

### Dashboard
```
http://localhost:5757/aegis/admin/pagespeed
```

### n8n
```
http://localhost:5678
```

### Logs
```bash
# n8n
docker logs n8n --tail 50

# PHP (se configurado)
tail -f /Applications/MAMP/logs/php_error.log
```

---

## ✅ Checklist Rápida

Amanhã, antes de testar:

- [ ] MAMP rodando
- [ ] n8n rodando (`docker ps | grep n8n`)
- [ ] Workflow n8n ativo
- [ ] Nova Google API key (se criou)
- [ ] Ler [pagespeed-quickstart.md](./pagespeed-quickstart.md)

---

**Última atualização:** 2026-02-09 10:45 BRT
**Versão:** 1.0.0
