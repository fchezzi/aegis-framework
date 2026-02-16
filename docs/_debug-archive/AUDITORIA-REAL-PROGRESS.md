# AUDITORIA REAL - EM PROGRESSO

## APIs LIDAS (5/10)

### ✅ chart-data.php
- **Auth:** OK (linha 17)
- **Whitelist tabelas:** OK (linha 38)
- **Problema encontrado:** Linhas 109-124 usam `$dateField` em DATE_FORMAT sem whitelist de campos. Apenas sanitiza com `Security::sanitize()` que usa `htmlspecialchars()` (não protege SQL).
- **Severidade:** ALTA

### ✅ get-columns.php
- **Auth:** OK - Admin only (linha 12)
- **Problema encontrado:** Linha 27 usa interpolação `{$table}` em SHOW COLUMNS. Tem regex mas sem whitelist.
- **Severidade:** MÉDIA

### ✅ get-tables.php
- **Auth:** OK - Admin only (linha 12)
- **Problema encontrado:** Retorna TODAS as tabelas do banco sem filtro (linha 18-28). Expõe estrutura.
- **Severidade:** MÉDIA

### ✅ list-canais.php
- **Auth:** OK (linha 11)
- **SQL:** OK - Hardcoded, sem inputs user
- **Status:** SEGURO

### ✅ metriccard-data.php
- **Auth:** OK (linha 18)
- **Whitelist tabelas:** OK (linha 41)
- **Whitelist operations:** OK (linha 42)
- **Problema encontrado:** Linhas 56-60 usa `Security::sanitize()` em `$column`, `$dateField`, `$conditionColumn` e depois usa direto em SQL (linha 151). `Security::sanitize()` NÃO protege SQL injection.
- **Severidade:** CRÍTICA

## PRÓXIMOS
- table-data.php
- upload-image.php
- youtube-data.php
- api/controllers/AuthApiController.php
