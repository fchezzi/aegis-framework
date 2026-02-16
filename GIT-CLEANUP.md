# AEGIS - Limpeza Git (Arquivos Sensíveis)

## ⚠️ PROBLEMA DETECTADO

Os seguintes arquivos **JÁ FORAM COMMITADOS** no Git e contêm informações sensíveis:

### 🔴 CRÍTICO - Contêm Senhas/API Keys:
- `_config.php` - Senhas de banco, SMTP, API keys (RD Station, UptimeRobot, TinyMCE)
- `_config.php.backup.*` - Backups com as mesmas senhas

### 🟡 ATENÇÃO - Arquivos Grandes/Desnecessários:
- `composer.phar` (3.1MB) - Executável do Composer (disponível via download)
- `config.codekit3` (11MB) - Arquivo local do CodeKit (não deve ir pro repositório)
- `composer.lock` - Lock file do Composer (pode causar conflitos)

---

## 🛠️ SOLUÇÃO: Remover do Git (mas manter no disco)

### Passo 1: Remover do índice do Git

```bash
cd /Users/fabiochezzi/Documents/websites/aegis

# Remover arquivos sensíveis (mas manter no disco)
git rm --cached _config.php
git rm --cached _config.php.backup*
git rm --cached composer.phar
git rm --cached config.codekit3
git rm --cached composer.lock

# Verificar o que foi removido
git status
```

### Passo 2: Commitar as remoções

```bash
git add .gitignore
git commit -m "Security: Remove sensitive files and update .gitignore

- Remove _config.php (contains passwords and API keys)
- Remove _config.php.backup files
- Remove composer.phar (3.1MB, available via download)
- Remove config.codekit3 (11MB, local IDE file)
- Remove composer.lock
- Update .gitignore to v2.0 (complete audit)
- Add _config.example.php as template"
```

### Passo 3: Verificar que funcionou

```bash
# Verificar que os arquivos ainda existem no disco
ls -la _config.php composer.phar config.codekit3

# Verificar que NÃO estão mais no Git
git ls-files | grep -E '(_config\.php|composer\.phar|config\.codekit3)'
# (não deve retornar nada)
```

---

## 🔐 SEGURANÇA EXTRA: Limpar Histórico (Opcional)

**⚠️ ATENÇÃO:** Isso reescreve o histórico do Git e pode causar problemas se o repo já foi compartilhado!

Se o repositório **NÃO foi enviado para o GitHub/GitLab ainda**, você pode limpar o histórico:

```bash
# Instalar BFG Repo Cleaner (mais rápido que git filter-branch)
brew install bfg

# Fazer backup do repo
cp -r /Users/fabiochezzi/Documents/websites/aegis /Users/fabiochezzi/Documents/aegis-backup

# Limpar arquivos do histórico
cd /Users/fabiochezzi/Documents/websites/aegis
bfg --delete-files _config.php
bfg --delete-files composer.phar
bfg --delete-files config.codekit3

# Limpar referências antigas
git reflog expire --expire=now --all
git gc --prune=now --aggressive
```

**Alternativa com git filter-repo:**

```bash
# Instalar git-filter-repo
brew install git-filter-repo

# Remover arquivos do histórico
git filter-repo --invert-paths --path _config.php --path composer.phar --path config.codekit3 --force
```

---

## 📋 CHECKLIST PÓS-LIMPEZA

- [ ] Arquivos sensíveis removidos do Git (`git ls-files`)
- [ ] Arquivos ainda existem no disco local
- [ ] `.gitignore` atualizado para v2.0
- [ ] `_config.example.php` criado como template
- [ ] Commit realizado com as mudanças
- [ ] **ANTES de fazer push:** Verificar que não há senhas no histórico

---

## 🔄 WORKFLOW FUTURO

### Para novos desenvolvedores:

1. Clone o repositório
2. Copiar `_config.example.php` para `_config.php`
3. Preencher com credenciais locais
4. Nunca commitar `_config.php`

### Para você (desenvolvedor principal):

1. Manter `_config.php` local apenas
2. Usar `.gitignore` v2.0 (já configurado)
3. Sempre verificar `git status` antes de commitar
4. Nunca commitar:
   - Senhas
   - API keys
   - Tokens
   - Arquivos grandes desnecessários

---

## 🚨 CREDENCIAIS COMPROMETIDAS

**IMPORTANTE:** As seguintes credenciais foram expostas no Git e devem ser TROCADAS:

### Trocar Imediatamente:
- ✅ **SMTP Gmail:** App Password `fluqtbzrjsvxkrcf`
  - Revogue em: https://myaccount.google.com/apppasswords
  - Gere nova senha

- ✅ **RD Station API Key:** `ec7ec89963b10f2f5139fad15c28fd72`
  - Revogue em: https://app.rdstation.com.br/configuracoes/integracao
  - Gere nova chave

- ✅ **UptimeRobot API Key:** `u3314914-b10b8031802b846b64aa61f7`
  - Revogue em: https://uptimerobot.com/dashboard
  - Gere nova chave

### Verificar:
- ⚠️ **TinyMCE API Key:** `8egj3ik46nfeqf945bziqgnsonrem0166rk4alvn7ud9coi3`
  - Normalmente não é crítico (rate limit apenas)
  - Mas recomendo trocar se possível

- ⚠️ **MySQL Password:** `root`
  - OK para ambiente local
  - NUNCA usar em produção!

---

## 📚 REFERÊNCIAS

- [Git - Removing Sensitive Data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)
- [BFG Repo Cleaner](https://rtyley.github.io/bfg-repo-cleaner/)
- [git-filter-repo](https://github.com/newren/git-filter-repo)

---

**Data da Auditoria:** 2026-02-16
**Versão .gitignore:** 2.0
**Status:** ⚠️ Ação Necessária
