# 🚨 PROTOCOLO DE ERRO

> **Regra única:** Errou → Verifica Known Issues → Para → Analisa → Reporta → Aguarda

---

## ⚠️ QUANDO USAR

**SEMPRE que algo der errado:**
- Erro 404/500
- Comportamento inesperado
- Algo não aparece
- Usuário diz "não funcionou"
- Warning/Notice que bloqueia execução

**QUANDO NÃO USAR:**
- Warnings de lint não críticos
- Deprecated notices que não quebram
- Erros já resolvidos (verificar known-issues primeiro)

---

## 📋 PASSOS OBRIGATÓRIOS (NA ORDEM)

### 0️⃣ VERIFICAR KNOWN ISSUES PRIMEIRO

**ANTES de reportar, ler:**
```
docs/slash/memory/known-issues.md
```

**Problemas já resolvidos:**
1. Edit Tool falha (tabs vs spaces) → Usar Write
2. Duplicatas no menu → Verificar existing antes de insert
3. Páginas públicas não aparecem → MenuBuilder ordem de verificação
4. [E mais 9 problemas documentados]

**Se encontrar solução:** Aplicar e continuar.
**Se não encontrar:** Seguir para passo 1.

---

### 1️⃣ PARAR IMEDIATAMENTE

- ❌ NÃO criar mais arquivos
- ❌ NÃO editar mais código
- ❌ NÃO tentar "consertar" sozinho
- ❌ NÃO assumir que "deve ser X"

---

### 2️⃣ ANALISAR O ERRO

**Checklist obrigatório:**
```
□ O que EU mudei? (listar arquivos + linhas específicas)
□ O que deu errado? (erro exato ou comportamento observado)
□ Dados no banco estão corretos? (rodar query SELECT e mostrar resultado)
□ Arquivo existe no path esperado? (ls/find)
□ Sintaxe está válida? (php -l para PHP)
□ Rota está registrada? (grep no routes.php)
□ Verificado known-issues.md? (sim/não)
□ Causa provável? (hipótese fundamentada ou "não sei")
```

**Comandos úteis:**
```bash
# Verificar sintaxe PHP
php -l arquivo.php

# Verificar se rota existe
grep "nome-rota" routes/*.php

# Verificar dados no banco
mysql -e "SELECT * FROM tabela WHERE condição"
```

---

### 3️⃣ REPORTAR AO CHEZZI

**Template obrigatório:**
```
Mudei:
- [Arquivo X linha Y]: [o que mudei]
- [Arquivo Z]: (criado novo)

Deu erro:
- [Erro exato copiado OU comportamento observado]

Verificações:
- Dados no banco: [resultado da query]
- Sintaxe: [php -l resultado]
- Rota registrada: [sim/não + grep resultado]
- Known issues: [verificado - não encontrado]

Causa provável:
- [Hipótese fundamentada] OU [Não sei]

Possíveis ações:
A) [Opção 1]
B) [Opção 2]
C) Reverter mudanças

Aguardo decisão.
```

**Formato de rollback (se necessário):**
```
Reverter:
1. Deletar: [arquivo X, arquivo Y]
2. Restaurar: [arquivo Z para estado anterior]
3. Query SQL: [DELETE/UPDATE se alterou banco]
```

---

### 4️⃣ AGUARDAR DECISÃO

- ⏸️ NÃO fazer nada até Chezzi responder
- ⏸️ NÃO assumir solução
- ⏸️ NÃO tentar "outra abordagem"
- ⏸️ NÃO criar arquivos de debug/teste

---

## ❌ PROIBIDO

**Nunca fazer:**
- ❌ Criar arquivo "debug.php" para investigar
- ❌ Tentar "outra abordagem" sem autorização
- ❌ Modificar mais coisas "testando"
- ❌ Assumir causa sem dados concretos
- ❌ Usar Edit múltiplas vezes (tabs vs spaces → usar Write)
- ❌ Reportar sem verificar known-issues.md primeiro

---

## ✅ EXEMPLO CORRETO

### Situação: Criei rota `/admin/check`, deu 404

**❌ ERRADO:**
```
Claude: "Deixa eu criar outra rota..."
Claude: "Vou modificar .htaccess..."
Claude: "Talvez seja problema de permissão..."
[Cria 5 arquivos sem parar]
```

**✅ CERTO:**
```
Claude: "Mudei:
- routes/admin.php linha 82: adicionei Router::get('/admin/check', ...)
- Criado: admin/check.php

Deu erro:
- 404 ao acessar /admin/check

Verificações:
- Rota registrada: grep encontrou em routes/admin.php linha 82
- Arquivo existe: ls confirma admin/check.php existe
- Sintaxe: php -l admin/check.php → OK
- Known issues: verificado - não encontrado

Causa provável:
- Rota específica pode estar DEPOIS de rota genérica (REGRAS.md #10)
- Ou cache de rotas não foi limpo

Possíveis ações:
A) Verificar ordem das rotas em routes/admin.php
B) Limpar cache em storage/cache/
C) Reverter (deletar admin/check.php e linha 82)

Aguardo decisão."

[PARA e AGUARDA]
```

---

## 🎯 CLASSIFICAÇÃO DE ERROS

### 🔴 CRÍTICO (Parar imediatamente)
- Erro 500 (quebra aplicação)
- Erro SQL que impede funcionamento
- Breaking change em schema
- Perda de dados

### 🟡 MODERADO (Parar e reportar)
- Erro 404 em funcionalidade nova
- Comportamento inesperado
- Warning que bloqueia feature
- Duplicatas/inconsistências

### 🟢 LEVE (Corrigir e documentar)
- Warning de deprecated (não crítico)
- Notice de PHP (não bloqueia)
- Lint warnings (corrigir após feature funcionar)

**Regra:** Na dúvida, tratar como MODERADO.

---

## 🔄 PROTOCOLO DE ROLLBACK

**Se Chezzi pedir rollback:**

1. **Deletar arquivos criados:**
```bash
rm arquivo1.php arquivo2.php
```

2. **Reverter arquivos modificados:**
```bash
git checkout -- arquivo.php
# OU usar Read + Write para versão anterior
```

3. **Reverter banco (se necessário):**
```sql
DELETE FROM tabela WHERE id = 'uuid-criado';
-- OU
UPDATE tabela SET campo = 'valor-anterior' WHERE condição;
```

4. **Confirmar rollback:**
```
Rollback concluído:
- Deletados: [lista]
- Revertidos: [lista]
- Banco: [query executada]

Sistema restaurado ao estado anterior.
```

---

## 📊 INTEGRAÇÃO COM OUTROS DOCS

**Fluxo completo:**
1. Erro acontece
2. Ler `docs/slash/memory/known-issues.md` → Solução conhecida?
3. Se não: Seguir ERRO-PROTOCOL
4. Se envolver classe core: Consultar `docs/slash/REFACTORING-GUIDE.md`
5. Se envolver regras violadas: Reler `docs/slash/REGRAS.md`

---

## 🎯 MANTRAS

**FAZER:**
> "Para → Verifica Known Issues → Analisa → Reporta → Aguarda"

**NÃO FAZER:**
> ~~"Tenta → Assume → Cria → Viaja → Quebra Mais"~~

**LEMBRAR:**
> "Edit falha? Write. Erro? Para. Dúvida? Pergunta."

---

**Versão:** 2.0.0
**Data:** 2026-02-14
**Changelog:** Adicionado passo 0 (known-issues), classificação de erros, protocolo de rollback, integração com outros docs
