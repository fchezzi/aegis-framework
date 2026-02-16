# AEGIS Framework - Arquivos da Raiz

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-17
**Responsável:** Fábio Chezzi

[← Voltar ao índice](aegis-estrutura.md)

---

## 🟢 CORE DO FRAMEWORK (OBRIGATÓRIOS)

### index.php
**Função:** Entry point - primeira linha executada em qualquer requisição

**O que faz:**
- Configura sessão PHP com segurança
- Redireciona para setup.php se _config.php não existir
- Carrega _config.php, autoloader, helpers
- Executa Router::run()

**Status:** CRÍTICO

---

### _config.php
**Função:** Configuração ativa (gerada pelo setup.php)

**Contém:**
- Credenciais MySQL
- APP_URL, DEBUG_MODE, ENABLE_MEMBERS
- INSTALLED_MODULES

**Status:** CRÍTICO

---

### routes.php
**Função:** Routes loader

**Ordem:**
1. routes/api.php
2. routes/public.php
3. routes/admin.php
4. ModuleManager::loadAllRoutes()
5. routes/catchall.php

**Status:** CRÍTICO

---

### setup.php
**Função:** Wizard de instalação

**O que faz:**
- Interface web de instalação
- Testa DB, cria tabelas
- Gera _config.php

**Status:** CRÍTICO (primeira vez)

---

## 🟡 DEPENDENCIES (NECESSÁRIOS PARA REPORTS)

### composer.json
**Função:** Lista de dependências PHP

**Contém:** phpoffice/phpspreadsheet

**Status:** Necessário se usar Reports/Excel

---

### composer.lock
**Função:** Lock de versões exatas

**Status:** Necessário se usar composer

---

### composer.phar
**Função:** Executável do Composer

**Comando:** `php composer.phar install`

**Status:** Necessário se usar composer

---

## 🟡 SERVER CONFIG

### .htaccess
**Função:** Regras Apache para URL rewrite

**Status:** CRÍTICO em servidor Apache

---

### .gitignore
**Função:** Arquivos ignorados pelo Git

**Ignora:** _config.php, vendor/, storage/, .DS_Store

**Status:** IMPORTANTE

---

## 🔵 METADATA

### .aegis-version
**Função:** Versão do framework

**Conteúdo:** 14.0.6

**Status:** OPCIONAL

---

## 🛠️ DEV TOOLS

### config.codekit3
**Função:** Configuração do CodeKit (compila SASS, minifica JS)

**Status:** ESSENCIAL para desenvolvimento
