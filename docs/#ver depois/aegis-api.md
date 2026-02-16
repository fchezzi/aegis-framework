# AEGIS Framework - Pasta /api/

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-18 *(Atualizado: Correção sessão APIs)*

[← Voltar ao índice](aegis-estrutura.md)

---

## ⚠️ IMPORTANTE: Autenticação em APIs

**Problema Identificado:** APIs com autenticação retornavam 401 mesmo com usuário logado.

**Causa:** `config.php` só inicia sessão se `ENVIRONMENT` está definido. APIs carregadas diretamente não tinham sessão.

**Solução:** Todas as APIs com autenticação agora incluem:

```php
// Garantir que sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

**Localização:** Após `Autoloader::register()`, antes de verificação de autenticação.

**Ver:** [CHANGELOG-2026-01-18.md](CHANGELOG-2026-01-18.md) para detalhes completos.

---

## 🟢 APIs 100% CORE-AEGIS (5 arquivos)

### table-data.php
**Buscar dados de qualquer tabela**
- Params: table, columns, value_field, date_field, order_by, limit
- Segurança: sanitização, prepared statements
- **Autenticação:** ✅ Requer Auth ou MemberAuth (linhas 11-14: session_start)
- **Uso:** Componentes dinâmicos, filtros, dashboards

### get-columns.php
**SHOW COLUMNS de uma tabela**
- Param: table (obrigatório)
- **Autenticação:** ✅ Requer Admin (linhas 11-14: session_start)
- **Uso:** Page Builder - Popular dropdowns de colunas

### get-tables.php
**SHOW TABLES do banco**
- Whitelist prefixos: tbl_, canais, youtube_, pages, modules
- Blocklist: users, members, sessions, groups, permissions
- **Autenticação:** ✅ Requer Admin (linhas 11-14: session_start)
- **Uso:** Page Builder - Popular dropdowns de tabelas

### upload-image.php
**Upload de imagens**
- Tipos: JPG, PNG, GIF, WEBP
- Máx: 5MB
- Validação MIME real
- **Autenticação:** ✅ Requer Auth ou MemberAuth (linhas 12-15: session_start)
- **Uso:** Page Builder, formulários, perfis
- **Retorno:** `{success: true, path: "uploads/..."}`

### AuthApiController.php
**API REST JWT**
- POST /api/v1/auth/login
- POST /api/v1/auth/refresh
- POST /api/v1/auth/logout
- GET /api/v1/auth/me
- Rate limiting: 5/5min
- **Autenticação:** JWT tokens (não usa sessão PHP)

---

## 🟡 APIs MISTO (2 arquivos)

### chart-data.php
**MISTO (80% CORE / 20% APP-FE)**
- Arquitetura genérica ApexCharts
- Whitelist 6 tabelas hardcoded
- **Autenticação:** ✅ Requer Auth ou MemberAuth (linhas 11-14: session_start)
- **Uso:** Gráficos dinâmicos
- Para tornar 100% CORE: mover whitelist para config

### metriccard-data.php
**MISTO (75% CORE / 25% APP-FE)**
- Operações: SUM, COUNT, AVG, MAX, MIN, LAST
- Comparação período anterior automático
- Whitelist 13 tabelas hardcoded
- **Autenticação:** ✅ Requer Auth ou MemberAuth (linhas 17-20: session_start)
- **Debug:** Linhas 22-33 retornam info de sessão se 401 (remover em produção)
- **Uso:** MetricCard component
- Para tornar 100% CORE: mover whitelist para config

---

## 🔴 APIs DEPRECADAS (2 arquivos)

### list-canais.php
**APP-FE ESPECÍFICO**
- Status: ❌ NÃO USADO (verificado)
- Substituída por: table-data.php
- **Autenticação:** ✅ session_start adicionado (linhas 11-14) para consistência
- **Ação:** Pode ser deletado

### youtube-data.php
**APP-FE ESPECÍFICO**
- Status: ❌ NÃO USADO (verificado)
- Substituída por: table-data.php
- **Autenticação:** ✅ session_start adicionado (linhas 11-14) para consistência
- **Ação:** Pode ser deletado

---

## 🗑️ LIXO

**test-encerrado.json** - Mock data (deletar)
