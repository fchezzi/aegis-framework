# PHPStan + PHP_CodeSniffer - Setup AEGIS

## ✅ Configuração Concluída

Os seguintes arquivos foram adicionados ao AEGIS:

- ✅ `composer.json` - Atualizado com PHPStan + PHP_CodeSniffer
- ✅ `phpstan.neon` - Configuração PHPStan (level 6)
- ✅ `phpcs.xml` - Configuração PHP_CodeSniffer (PSR-12)
- ✅ `scripts/check-code.sh` - Script helper para rodar ambos

---

## 🚀 Como Instalar

### 1. Instalar Composer (se não tiver)

```bash
# Baixar Composer
cd ~
curl -sS https://getcomposer.org/installer | php

# Mover para PATH global
sudo mv composer.phar /usr/local/bin/composer

# Verificar
composer --version
```

### 2. Instalar Dependências

```bash
cd /Users/fabiochezzi/Documents/websites/aegis
composer install
```

**Isso instala:**
- PHPStan (análise estática)
- PHP_CodeSniffer (padrão de código)

---

## 🔍 Como Usar

### Opção 1: Script Helper (Recomendado)

```bash
./scripts/check-code.sh
```

Roda **PHPStan + PHP_CodeSniffer** automaticamente.

### Opção 2: Comandos Individuais

```bash
# PHPStan (detectar bugs)
vendor/bin/phpstan analyse

# PHP_CodeSniffer (verificar estilo)
vendor/bin/phpcs

# PHP_CodeSniffer (corrigir automaticamente)
vendor/bin/phpcbf
```

---

## 📋 O que Cada Ferramenta Faz

### PHPStan
- ✅ Detecta bugs antes de rodar o código
- ✅ Verifica tipos (mesmo sem PHP 8+)
- ✅ Encontra variáveis undefined
- ✅ Detecta typos em métodos
- ✅ Level 6 = bom balanço

### PHP_CodeSniffer
- ✅ Verifica padrão PSR-12
- ✅ Detecta problemas de formatação
- ✅ `phpcbf` corrige automaticamente
- ✅ Código consistente em toda a base

---

## 🎯 Integração Git (Opcional)

### Pre-commit Hook

Bloqueia commit se houver erros:

```bash
# Criar arquivo
nano .git/hooks/pre-commit
```

**Conteúdo:**

```bash
#!/bin/bash
./scripts/check-code.sh
if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Commit bloqueado. Corrija os problemas acima primeiro."
    exit 1
fi
```

**Tornar executável:**

```bash
chmod +x .git/hooks/pre-commit
```

Agora todo commit roda verificação automática!

---

## ✅ Verificação

Após instalar, testar:

```bash
# 1. Verificar se instalou
ls vendor/bin/phpstan
ls vendor/bin/phpcs

# 2. Rodar script
./scripts/check-code.sh
```

---

## 🔄 Replicar em Outros Projetos AEGIS

### Método 1: Copiar arquivos

```bash
# Do projeto base
cd /Users/fabiochezzi/Documents/websites/aegis

# Para novo projeto
cp composer.json /path/to/novo-projeto/
cp phpstan.neon /path/to/novo-projeto/
cp phpcs.xml /path/to/novo-projeto/
cp scripts/check-code.sh /path/to/novo-projeto/scripts/

# Instalar no novo projeto
cd /path/to/novo-projeto
composer install
```

### Método 2: Template

Quando criar novo projeto AEGIS, já copiar com esses arquivos incluídos.

---

## 📊 Status

- ✅ Configuração criada
- ✅ Composer instalado
- ✅ PHPStan instalado (v1.12.32)
- ✅ PHP_CodeSniffer instalado (v3.13.5)
- ✅ Scripts prontos para uso

**Tudo pronto!** ✅

---

## 🎯 Como Usar (SUPER SIMPLES)

### **Verificar qualidade do código:**

```bash
cd /Users/fabiochezzi/Documents/websites/aegis
./check
```

### **Corrigir problemas automaticamente:**

```bash
./fix
```

**É só isso!** 🎉

---

**Criado em:** 15/02/2026
**Versão AEGIS:** 17.3.6
**Instalado em:** 15/02/2026
