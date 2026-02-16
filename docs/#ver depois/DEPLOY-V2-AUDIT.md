# 🔍 AUDITORIA COMPLETA - Deploy V2

**Data:** 27/01/2026
**Objetivo:** Garantir deploy perfeito para produção
**Status:** 🚨 PROBLEMAS ENCONTRADOS

---

## 📊 ANÁLISE ATUAL

### ✅ O QUE ESTÁ SENDO INCLUÍDO (Correto)

#### Diretórios Principais (linhas 59-68)
```php
$dirsToClean = [
    'admin',      ✅ Necessário
    'core',       ✅ Necessário
    'database',   ✅ Necessário
    'frontend',   ✅ Necessário
    'modules',    ✅ Necessário
    'routes',     ✅ Necessário
    'api',        ✅ Necessário
    'public'      ✅ Necessário
];
```

#### Outros Diretórios (linha 70)
```php
$otherDirs = [
    'storage',    ✅ Necessário
    'assets',     ✅ Necessário
    'components', ✅ Necessário
    'vendor'      ✅ Necessário
];
```

#### Arquivos Individuais (linha 104)
```php
$individualFiles = [
    'index.php',  ✅ Necessário
    'routes.php', ✅ Necessário
    'setup.php',  ⚠️ QUESTIONÁVEL (servidor novo precisa?)
    'config.php', ✅ Necessário (template)
    '.htaccess'   ✅ Necessário
];
```

### ✅ O QUE ESTÁ SENDO EXCLUÍDO (Correto)

#### Exclusões no tar (linhas 131-137)
```bash
--exclude='_config.php'           ✅ Correto (credenciais)
--exclude='.env'                  ✅ Correto (credenciais)
--exclude='*.backup'              ✅ Correto (temporários)
--exclude='storage/cache/*'       ✅ Correto (cache)
--exclude='storage/logs/*.log'    ✅ Correto (logs)
--exclude='storage/uploads/*'     ✅ Condicional (via checkbox)
```

---

## 🚨 PROBLEMAS ENCONTRADOS

### 1. ❌ PASTAS PERIGOSAS/DESNECESSÁRIAS NÃO EXCLUÍDAS

**Encontradas na raiz do projeto:**

| Pasta | Problema | Impacto | Prioridade |
|-------|----------|---------|------------|
| `.claude/` | Documentação interna | Expõe arquitetura | 🔴 ALTA |
| `debug/` | Arquivos de debug | Expõe erros/testes | 🔴 ALTA |
| `docs/` | Documentação | Expõe arquitetura | 🟡 MÉDIA |
| `tests/` | Testes unitários | Desnecessário | 🟡 MÉDIA |
| `bkp/` | Backups locais | Pode ter dados sensíveis | 🔴 ALTA |
| `deploys/` | Pacotes antigos | Desnecessário | 🟢 BAIXA |
| `scripts/` | Scripts auxiliares | Desnecessário | 🟢 BAIXA |
| `uploads/` | Confusão (storage/uploads) | Duplicação? | 🟡 MÉDIA |

**⚠️ ATUALMENTE:** Essas pastas estão sendo copiadas SE existirem no $otherDirs!

### 2. ⚠️ ARQUIVOS POTENCIALMENTE PERIGOSOS

**Não estão sendo excluídos:**
- `*.md` (README, CHANGELOG, etc) - expõem info do projeto
- `composer.json` / `composer.lock` - expõem versões
- `package.json` / `package-lock.json` - se existirem
- `.gitignore` - expõe o que é ignorado
- `.git/` - se existir (improvável mas perigoso)
- `*.sql` - backups de banco na raiz
- `.DS_Store` - lixo do macOS

### 3. ⚠️ setup.php EM PRODUÇÃO

**Problema:**
- `setup.php` permite reinstalar o sistema
- Em produção isso é PERIGOSO (pode dropar banco)

**Deveria:**
- NÃO incluir em deploy de produção
- OU incluir com proteção (senha especial)

### 4. ⚠️ PERMISSÕES storage/

**Código atual (linhas 113-123):**
```php
$requiredDirs = [
    'storage/cache',
    'storage/logs',
    'storage/uploads'
];
foreach ($requiredDirs as $dir) {
    mkdir($dir, 0755, true);
    touch($dir . '/.gitkeep');
}
```

**Problema:**
- Cria com 0755, mas deveria ser 0777 em produção (gravável pelo PHP)
- Não cria `storage/sessions/` (se AEGIS usar)

### 5. ⚠️ .htaccess NÃO É GARANTIDO

**Código verifica (linhas 151-154):**
```php
exec("tar -tzf " . escapeshellarg($codigoPath) . " | grep -E '^\\.htaccess$'", $htaccessCheck);
if (empty($htaccessCheck)) {
    error_log("AVISO: .htaccess NÃO está no pacote tar.gz!");
}
```

**Problema:**
- Apenas loga aviso, não FALHA se .htaccess não for incluído
- .htaccess é CRÍTICO (rotas não funcionam sem ele)

### 6. ⚠️ VENDOR SEM VERIFICAÇÃO

**Problema:**
- Inclui vendor/ inteiro (correto)
- Mas não verifica se está completo (composer install)
- Se faltar alguma lib, sistema quebra em produção

---

## 🎯 RECOMENDAÇÕES CRÍTICAS

### 🔴 PRIORIDADE MÁXIMA

#### 1. EXCLUIR PASTAS PERIGOSAS
```php
// ADICIONAR na linha 70 (ou criar lista de exclusão):
$excludeDirs = [
    '.claude',
    'debug',
    'docs',
    'tests',
    'bkp',
    'deploys',
    'scripts',
    '.git'
];
```

#### 2. EXCLUIR ARQUIVOS PERIGOSOS NO TAR
```bash
--exclude='*.md' \
--exclude='.gitignore' \
--exclude='.DS_Store' \
--exclude='*.sql' \
--exclude='composer.json' \
--exclude='composer.lock' \
--exclude='package*.json' \
--exclude='phpunit.xml' \
--exclude='.editorconfig'
```

#### 3. REMOVER setup.php DE PRODUÇÃO
```php
// MODIFICAR linha 104:
if ($ambiente !== 'producao') {
    $individualFiles[] = 'setup.php';
}
// Setup só vai em homologação/teste
```

#### 4. FALHAR SE .htaccess AUSENTE
```php
// MODIFICAR linhas 151-154:
if (empty($htaccessCheck)) {
    throw new Exception('.htaccess CRÍTICO não foi incluído no pacote!');
}
```

### 🟡 PRIORIDADE MÉDIA

#### 5. CORRIGIR PERMISSÕES storage/
```php
// Criar com 0777 (gravável)
mkdir($dir, 0777, true);

// Adicionar storage/sessions se necessário
$requiredDirs = [
    'storage/cache',
    'storage/logs',
    'storage/uploads',
    'storage/sessions'  // se AEGIS usar
];
```

#### 6. VERIFICAR uploads/ vs storage/uploads/
```bash
# Investigar se existe duplicação
ls -la /Users/fabiochezzi/Documents/websites/aegis/uploads/
ls -la /Users/fabiochezzi/Documents/websites/aegis/storage/uploads/

# Se uploads/ for duplicado, adicionar ao excludeDirs
```

#### 7. ADICIONAR VERIFICAÇÃO vendor/
```php
// Antes de empacotar:
if (!file_exists($tempCodeDir . 'vendor/autoload.php')) {
    throw new Exception('vendor/ incompleto! Execute: composer install');
}
```

### 🟢 MELHORIAS OPCIONAIS

#### 8. LOG DE AUDITORIA
```php
// Criar arquivo no ZIP com lista completa do que foi incluído
$auditFile = $tempDir . 'PACOTE-CONTEUDO.txt';
exec("tar -tzf " . escapeshellarg($codigoPath), $allFiles);
file_put_contents($auditFile, implode("\n", $allFiles));
$zip->addFile($auditFile, 'PACOTE-CONTEUDO.txt');
```

#### 9. CHECKSUM MD5
```php
// Gerar MD5 do pacote para validação
$md5 = md5_file($zipPath);
file_put_contents($zipPath . '.md5', $md5);
```

#### 10. README-PRODUCAO.md
```php
// Criar README específico para produção
$readmeProducao = "AEGIS Framework - Deploy Produção\n";
$readmeProducao .= "Versão: {$versao}\n";
$readmeProducao .= "Ambiente: {$ambiente}\n\n";
$readmeProducao .= "NÃO incluso por segurança:\n";
$readmeProducao .= "- Documentação (.claude, docs)\n";
$readmeProducao .= "- Arquivos de debug\n";
$readmeProducao .= "- Testes\n";
$readmeProducao .= "- setup.php (produção)\n";
file_put_contents($tempCodeDir . 'README-PRODUCAO.md', $readmeProducao);
```

---

## 📋 CHECKLIST DE SEGURANÇA

### Arquivos Sensíveis
- [x] `_config.php` excluído
- [x] `.env` excluído
- [x] `*.backup` excluído
- [ ] `*.sql` não excluído ⚠️
- [ ] `composer.json` não excluído ⚠️

### Pastas Perigosas
- [ ] `.claude/` não excluída 🚨
- [ ] `debug/` não excluída 🚨
- [ ] `docs/` não excluída ⚠️
- [ ] `tests/` não excluída ⚠️
- [ ] `bkp/` não excluída 🚨
- [x] `storage/cache/*` excluído
- [x] `storage/logs/*.log` excluído

### Arquivos Críticos
- [x] `.htaccess` incluído (mas não garante)
- [x] `index.php` incluído
- [x] `vendor/` incluído (mas não verifica)
- [ ] `setup.php` incluído em produção 🚨

### Estrutura
- [x] Pastas necessárias incluídas
- [x] Permissões storage/ criadas
- [ ] Permissões corretas (0777) ⚠️

---

## 🎯 IMPLEMENTAÇÃO SUGERIDA

### Opção 1: Lista Branca (MAIS SEGURO)
```php
// Só incluir o que é explicitamente necessário
$allowedDirs = [
    'admin', 'core', 'database', 'frontend',
    'modules', 'routes', 'api', 'public',
    'storage', 'assets', 'components', 'vendor'
];

// Tudo fora dessa lista é ignorado automaticamente
```

### Opção 2: Lista Negra (MAIS FLEXÍVEL)
```php
// Excluir o que é perigoso
$excludeDirs = [
    '.claude', 'debug', 'docs', 'tests',
    'bkp', 'deploys', 'scripts', '.git'
];

// Todo o resto é incluído
```

**RECOMENDAÇÃO:** Opção 1 (Lista Branca) é MAIS SEGURA

---

## 🔐 SCORE DE SEGURANÇA ATUAL

| Categoria | Score | Observação |
|-----------|-------|------------|
| Exclusão de credenciais | 10/10 | ✅ Perfeito (_config, .env) |
| Exclusão de debug | 3/10 | 🚨 Pastas debug/tests incluídas |
| Exclusão de docs | 2/10 | 🚨 .claude/docs incluídos |
| Arquivos críticos | 8/10 | ⚠️ .htaccess não garante |
| setup.php em prod | 0/10 | 🚨 Perigoso! |
| Permissões | 7/10 | ⚠️ 0755 ao invés de 0777 |
| **TOTAL** | **5.0/10** | 🟡 PRECISA MELHORIAS |

---

## ✅ SCORE ESPERADO APÓS CORREÇÕES

| Categoria | Score Atual | Score Esperado |
|-----------|-------------|----------------|
| Exclusão de credenciais | 10/10 | 10/10 |
| Exclusão de debug | 3/10 | 10/10 |
| Exclusão de docs | 2/10 | 10/10 |
| Arquivos críticos | 8/10 | 10/10 |
| setup.php em prod | 0/10 | 10/10 |
| Permissões | 7/10 | 10/10 |
| **TOTAL** | **5.0/10** | **10/10** 🎯

---

## 📝 CONCLUSÃO

**STATUS ATUAL:** 🟡 FUNCIONAL mas com RISCOS DE SEGURANÇA

**PROBLEMAS CRÍTICOS:**
1. 🚨 Pastas .claude, debug, bkp sendo incluídas
2. 🚨 setup.php em produção (pode reinstalar sistema)
3. ⚠️ Documentação exposta
4. ⚠️ Permissões storage/ não ideais

**PRÓXIMO PASSO:**
Implementar correções na ordem de prioridade (🔴 → 🟡 → 🟢)

---

**Auditoria realizada por:** Claude Code
**Data:** 27/01/2026
**Versão AEGIS:** 14.0.7
