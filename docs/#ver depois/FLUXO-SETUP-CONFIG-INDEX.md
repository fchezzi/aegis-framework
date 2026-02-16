# FLUXO: Relação Entre setup.php, config.php, _config.php e index.php

**Data:** 2026-02-12  
**Status:** ✅ DOCUMENTADO  

---

## 🎯 RESUMO EXECUTIVO

```
INSTALAÇÃO (1x na vida)
setup.php → Gera _config.php

RUNTIME (toda requisição)
index.php → Carrega _config.php
      ↓
Routes carregadas
      ↓
Application executa

FALLBACK (scripts soltos)
_config.php → Carrega config.php (se existir .env)
      ↓
config.php → Define constantes OU carrega .env
```

---

## 📊 DIAGRAMA COMPLETO

```
┌─────────────────────────────────────────────────────────────────────┐
│                        PRIMEIRO ACESSO (INSTALAÇÃO)                 │
└─────────────────────────────────────────────────────────────────────┘

User abre http://localhost/aegis/ (PRIMEIRA VEZ)
  ↓
index.php executado (linha 22)
  ↓
if (!file_exists(_config.php))
  ├─ SIM: Redireciona para setup.php
  │   └─ setup.php executado
  │       ├─ Mostra formulário HTML
  │       ├─ Usuário preenche:
  │       │  - DB_TYPE (mysql/supabase/none)
  │       │  - DB_HOST, DB_NAME, DB_USER, DB_PASS
  │       │  - APP_URL
  │       │  - TINYMCE_API_KEY
  │       │  - ADMIN_NAME, ADMIN_SUBTITLE
  │       │  - ENABLE_MEMBERS (sim/não)
  │       └─ POST enviado
  │           ↓
  │       setup.php processa (linha 201-250)
  │           ├─ Valida CSRF
  │           ├─ Testa conexão com DB
  │           ├─ Importa schema (cria tabelas)
  │           ├─ Chama Core::generateConfig($data)
  │           │   └─ CoreConfig::generate($data)
  │           │       ├─ Carrega template do core/CoreConfig.php
  │           │       ├─ Substitui placeholders
  │           │       └─ Escreve arquivo _config.php
  │           ├─ Cria usuário admin
  │           └─ Redireciona para /admin/login
  │
  └─ NÃO: _config.php JÁ EXISTE
      └─ Continua normalmente (ver diagrama abaixo)

┌─────────────────────────────────────────────────────────────────────┐
│                    REQUISIÇÃO NORMAL (APÓS INSTALAÇÃO)              │
└─────────────────────────────────────────────────────────────────────┘

User abre http://localhost/aegis/admin/pages
  ↓
index.php executado (linha 15)
  ├─ Configura sessão PHP
  │  └─ ini_set('session.cookie_*', ...)
  │     if (session_status() === PHP_SESSION_NONE) session_start();
  │
  ├─ Verifica se _config.php existe (linha 22)
  │  └─ SIM, continua
  │
  ├─ Carrega _config.php (linha 29)
  │  └─ require_once __DIR__ . '/_config.php'
  │     Define constantes: DB_TYPE, DB_HOST, DB_NAME, etc.
  │
  ├─ Carrega vendor/autoload.php (se existir) - linha 32
  │  └─ Carrega dependências Composer (PHPSpreadsheet, etc)
  │
  ├─ Carrega core/Autoloader.php (linha 37)
  │  └─ Autoloader::register()
  │     Permite carregamento automático de classes
  │
  ├─ Carrega core/helpers.php (linha 41)
  │  └─ Define funções globais (url(), env(), etc)
  │
  ├─ Core::configure() (linha 44)
  │  └─ Detecta ambiente (development/production)
  │     Define constantes adicionais
  │
  ├─ Configura timezone (linha 47)
  │  └─ date_default_timezone_set(Settings::get('timezone', 'America/Sao_Paulo'))
  │
  ├─ Registra DebugBar (se DEBUG_MODE = true) (linha 50)
  │  └─ DebugBar::register()
  │
  ├─ Registra ErrorHandler (linha 55)
  │  └─ ErrorHandler::register(DEBUG_MODE)
  │
  ├─ Registra Middlewares (linha 58)
  │  └─ Middleware::register()
  │
  ├─ Define Security Headers (linha 61)
  │  └─ Security::setHeaders()
  │
  ├─ Carrega routes.php (linha 66)
  │  └─ require_once __DIR__ . '/routes.php'
  │     └─ Carrega: api.php → public.php → admin.php → modules → catchall.php
  │
  └─ Router::run() (linha 69)
     └─ Encontra rota correta e executa controller
        └─ Controller carregado
           └─ Retorna resposta

┌─────────────────────────────────────────────────────────────────────┐
│                    API CARREGADA DIRETAMENTE                        │
└─────────────────────────────────────────────────────────────────────┘

JavaScript fetch('/api/metriccard-data.php')
  ↓
index.php NÃO é executado (arquivo direto!)
  ↓
api/metriccard-data.php executado (linha 13)
  ├─ require_once __DIR__ . '/../_config.php'
  │  └─ Carrega _config.php diretamente
  │     Define: DB_TYPE, DB_HOST, DB_NAME, etc.
  │     NÃO carrega config.php (porque _config.php não faz require dele)
  │
  ├─ require_once __DIR__ . '/../core/Autoloader.php' (linha 14)
  │  └─ Autoloader::register()
  │
  ├─ Session já foi iniciada por config.php (NEW - após fix)
  │  └─ Se _config.php carrega config.php, sessão já existe
  │  └─ Se não, config.php (linha 145) garante:
  │     if (session_status() === PHP_SESSION_NONE) session_start();
  │
  └─ API executa lógica
     └─ Retorna JSON

┌─────────────────────────────────────────────────────────────────────┐
│                      SCRIPT SOLTO (CLI ou cron)                     │
└─────────────────────────────────────────────────────────────────────┘

php scripts/sync-menu-permissions.php
  ↓
scripts/sync-menu-permissions.php (linha 1)
  ├─ require_once __DIR__ . '/_config.php'
  │  └─ Carrega _config.php diretamente
  │     Define constantes de banco
  │
  ├─ require_once __DIR__ . '/core/Autoloader.php'
  │  └─ Autoloader::register()
  │
  └─ Script executa
     └─ Acessa DB, faz alterações
```

---

## 🔍 DETALHES DE CADA ARQUIVO

### 1. setup.php - WIZARD DE INSTALAÇÃO

**Quando é executado:**
- UMA VEZ na vida do projeto
- Quando `_config.php` não existe
- User acessa `/setup.php`

**O que faz:**
```php
1. Mostra formulário HTML
2. User preenche dados
3. Testa conexão com banco
4. Importa schema (CREATE TABLE)
5. Cria usuário admin
6. Gera _config.php via Core::generateConfig()
```

**Dados coletados:**
```php
$configData = [
    'DB_TYPE' => 'mysql',           // ou supabase, none
    'DB_HOST' => 'localhost',       // user input
    'DB_NAME' => 'aegis_db',        // user input
    'DB_USER' => 'root',            // user input
    'DB_PASS' => '****',            // user input
    'APP_URL' => 'http://localhost',// user input
    'TINYMCE_API_KEY' => 'xyz...',  // optional
    'ENABLE_MEMBERS' => true,       // user choice
    'ADMIN_NAME' => 'AEGIS',        // default
    'ADMIN_SUBTITLE' => 'Admin'     // default
];
```

**Resultado:**
- ✅ Arquivo `_config.php` criado
- ✅ Banco de dados criado
- ✅ Tabelas importadas
- ✅ Usuário admin criado
- ✅ Redireciona para /admin/login

---

### 2. _config.php - CONFIGURAÇÃO ATIVA (Gerada)

**Quando é criado:**
- Após setup.php completar
- NUNCA é versionado (.gitignore)
- É único para cada instalação

**O que contém:**
```php
<?php
define('DEBUG_MODE', false);
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'aegis_db');
define('DB_USER', 'root');
define('DB_PASS', '****');
define('APP_URL', 'http://localhost:5757/aegis');
define('ADMIN_NAME', 'AEGIS');
define('TINYMCE_API_KEY', 'xyz...');
define('ENABLE_MEMBERS', true);
define('ROOT_PATH', __DIR__ . '/');
define('STORAGE_PATH', ROOT_PATH . 'storage/');
// ... mais paths
function url($path = '') { ... }
```

**Quem carrega:**
- index.php (linha 29)
- API diretas (linha 13)
- Scripts soltos
- Qualquer arquivo que `require_once _config.php`

**Ciclo de vida:**
```
Criado por setup.php
  ↓
Carregado por index.php/APIs (toda requisição)
  ↓
Define constantes do banco e aplicação
  ↓
Config.php PODE tentar carregá-lo também (fallback)
```

---

### 3. config.php - CONFIGURATION LOADER (Fallback/Bridge)

**Quando é carregado:**
- NUNCA automaticamente (não é incluído por index.php)
- Apenas se EXPLICITAMENTE carregado
- Opcional em projeto
- Suporte a .env (para Docker/cloud)

**O que faz:**
```php
1. Se existe .env:
   - Carrega Env helper
   - Valida .env
   - Define constantes via Env::get()

2. Se não existe .env:
   - Fallback para require _config.php

3. Auto-detecta ENVIRONMENT
   - localhost → development
   - outro → production

4. Define paths (STORAGE, LOG, UPLOAD, CACHE)

5. Inicia SESSION:
   - if (session_status() === PHP_SESSION_NONE)
     └─ ini_set('session.cookie_*', ...)
     └─ session_start()

6. Define ERROR_REPORTING conforme ENVIRONMENT

7. Define HELPER FUNCTIONS (url, env, is_production)
```

**Quando seria útil:**
- Projetos com .env (Docker, Heroku, etc)
- Múltiplos ambientes (dev, staging, prod)
- CI/CD pipelines

**Status no AEGIS:**
- ⚠️ Criado mas NÃO É USADO
- index.php carrega _config.php direto
- Apenas APIs que carregam _config.php depois poderiam usar config.php
- Candidato a remoção OU melhoria

---

### 4. index.php - ENTRY POINT

**Quando é executado:**
- TODA requisição web que não é arquivo estático
- Primeiro arquivo PHP executado

**Fluxo:**
```php
// 1. SEGURANÇA DE SESSÃO (antes de output)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', auto-detect HTTPS);
session_start();

// 2. VERIFICAR INSTALAÇÃO
if (!file_exists(_config.php))
    header('Location: setup.php');  // Redirecionar para wizard

// 3. CARREGADOR DE DEPENDÊNCIAS
require_once _config.php;          // Constantes
require_once vendor/autoload.php;  // Composer
require_once core/Autoloader.php;  // Autoload
require_once core/helpers.php;     // Funções globais

// 4. BOOTSTRAP DA APLICAÇÃO
Core::configure();                 // Detectar ambiente
date_default_timezone_set(...);    // Timezone
DebugBar::register();              // Se DEBUG_MODE
ErrorHandler::register();          // Exceções
Middleware::register();            // Middlewares
Security::setHeaders();            // Headers de segurança

// 5. ROTEAMENTO
require_once routes.php;           // Carregar rotas
Router::run();                     // Executar
```

**Importância:**
- ✅ CRÍTICO para toda aplicação
- ✅ Única entrada web
- ✅ Orquestra bootstrap
- ✅ Segurança de sessão começa aqui

---

## 🔄 CICLO DE VIDA COMPLETO

```
PRIMEIRO ACESSO (Instalação)
┌────────────────────────────────────────┐
│ 1. User acessa http://localhost/aegis  │
│ 2. index.php verifica _config.php      │
│ 3. _config.php NÃO existe              │
│ 4. Redireciona para setup.php          │
│ 5. setup.php mostra wizard             │
│ 6. User preenche formulário            │
│ 7. setup.php gera _config.php          │
│ 8. setup.php cria banco                │
│ 9. Redireciona para /admin/login       │
│ 10. index.php executa (com _config.php)│
│ 11. Aplicação carrega normalmente      │
└────────────────────────────────────────┘
                   ↓
           REQUISIÇÕES NORMAIS (Uso)
┌────────────────────────────────────────┐
│ User acessa qualquer página            │
│ ↓                                      │
│ index.php executa                      │
│ ↓                                      │
│ Carrega _config.php (EXISTE)           │
│ ↓                                      │
│ Bootstrap da aplicação                 │
│ ↓                                      │
│ Router encontra rota                   │
│ ↓                                      │
│ Controller executa                     │
│ ↓                                      │
│ Retorna resposta                       │
└────────────────────────────────────────┘
                   ↓
         DEPLOY EM NOVA RÉPLICA
┌────────────────────────────────────────┐
│ 1. Clone do repositório                │
│ 2. _config.php NÃO existe (gitignore)  │
│ 3. User acessa http://replica/aegis    │
│ 4. index.php redireciona para setup    │
│ 5. setup.php gera _config.php          │
│ 6. Nova réplica está funcional         │
│ 7. Dados sincronizados se necessário   │
└────────────────────────────────────────┘
```

---

## 🎯 RELACIONAMENTO PRÁTICO

### Cenário 1: Desenvolvimento Local

```
1. clone repositório
   └─ _config.php não existe (.gitignore)

2. php artisan setup (ou acessar setup.php)
   └─ gera _config.php localmente
   └─ DB_NAME = 'aegis_dev'

3. Toda requisição
   └─ index.php carrega _config.php
   └─ Usa DB_NAME = 'aegis_dev'

4. Commit code
   └─ _config.php não é commitado
```

### Cenário 2: Deploy em Servidor

```
1. push para servidor
   └─ _config.php não sobe (gitignore)

2. Primeira requisição
   └─ index.php redireciona para setup.php
   └─ Admin acessa setup.php manualmente

3. setup.php preenchido com dados de produção
   └─ DB_NAME = 'aegis_prod'
   └─ APP_URL = 'https://example.com'

4. _config.php gerado em produção
   └─ Usa credenciais de produção

5. Próximas requisições usam novo _config.php
   └─ Banco de produção é acessado
```

### Cenário 3: API Direta (sem index.php)

```
1. fetch('/api/metriccard-data.php')
   └─ index.php NÃO é executado

2. api/metriccard-data.php
   ├─ require_once _config.php
   │  └─ Carrega constantes
   │  └─ Sessão pode estar iniciada
   │
   ├─ require_once Autoloader
   │  └─ Autoload funciona
   │
   └─ API executa normalmente
```

---

## 📋 TABELA COMPARATIVA

| Aspecto | setup.php | _config.php | config.php | index.php |
|---------|-----------|------------|-----------|-----------|
| **Quando** | 1x na vida | Toda requisição | Opcional | Toda req web |
| **Gerado por** | User + form | setup.php | Não (manual/.env) | Não |
| **Versionado** | SIM | NÃO (.gitignore) | Talvez | SIM |
| **Contém credenciais** | Não (form) | SIM | Talvez (.env) | NÃO |
| **Executável** | Sim (HTML) | Sim (PHP constants) | Sim (PHP) | Sim (HTML) |
| **Função** | Wizard | Config ativa | Fallback/bridge | Entry point |
| **Crítico** | Sim (1x) | SIM (sempre) | Não | SIM (sempre) |
| **Independente** | Sim | Sim | Não (precisa .env ou _config) | Não (precisa _config) |

---

## ✅ CONCLUSÃO

### Ordem de Carregamento (toda requisição)

```
1. index.php                    (entry point web)
   ↓
2. Verifica _config.php existe
   ├─ NÃO: setup.php (redireciona)
   └─ SIM: continua
   ↓
3. require _config.php          (constantes)
   ↓
4. require vendor/autoload.php  (dependências)
   ↓
5. require Autoloader           (autoload)
   ↓
6. require helpers.php          (funções globais)
   ↓
7. Core::configure()            (bootstrap)
   ↓
8. require routes.php           (rotas)
   ↓
9. Router::run()                (executa)
```

### Quando config.php é Usado

❌ **ATUALMENTE:** Não é usado em AEGIS

✅ **PODERIA SER USADO SE:**
- Projeto tiver `.env` (Docker/cloud)
- Quiser suporte a variáveis de ambiente
- Quiser centralizar configuração (fallback)

### Melhoria Proposta

`config.php` é um arquivo "ponte" que **não está sendo utilizado**. Opções:

1. **Deletar** (simplificar)
2. **Refatorar** (usar para .env bridge)
3. **Manter** (como opção futura para Docker)

**Recomendação:** Manter por enquanto (usável para futuro Docker/cloud).

