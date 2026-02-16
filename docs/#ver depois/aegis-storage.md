# AEGIS Framework - Pasta /storage/

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-18

[← Voltar ao índice](aegis-estrutura.md)

---

## 📊 RESUMO

**Total:** 47 arquivos
**Logs:** 29 arquivos (aegis-*.log diários + error.log + php-errors.log)
**Cache:** 2 arquivos (.gitkeep + 1 cache ativo)
**Uploads:** 4 imagens (avatars members)
**Config:** 3 arquivos JSON (settings, versions, last-bump)

---

## 🏗️ ARQUITETURA

### Estrutura

```
storage/
├── cache/             # Cache de arquivos (file driver)
├── logs/              # Logs diários do framework
├── uploads/           # Uploads de arquivos (organizados por módulo)
│   ├── blog/          # Imagens blog
│   ├── members/       # Avatars membros
│   │   └── avatars/   # Subpasta avatars
│   ├── palpiteiros/   # Fotos palpiteiros
│   └── times/         # Escudos times
├── settings.json      # Configurações do site (editável via admin)
├── versions.json      # Histórico de versões (auto-bump)
└── last-bump.txt      # Data do último bump
```

---

## 📁 PASTAS

### 1. cache/ (2 arquivos)

**Função:** Cache persistente (driver `file` do Cache.php)

**Arquivos:**
- `.gitkeep` - Manter pasta no Git
- `f4e6e04a97de78810d88b2e7118cbdd2.cache` - Cache ativo (hash MD5 da chave)

**Formato arquivo cache:**
```php
// Gerado por Cache::set($key, $value, $ttl)
serialize([
    'value' => $data,
    'expires' => time() + $ttl
]);
```

**Limpeza automática:** Cache::clear() ou /admin/cache

**Classificação:** 100% CORE

---

### 2. logs/ (29 arquivos)

**Função:** Logs do framework (Logger.php)

**Padrão de nomes:**
- `aegis-YYYY-MM-DD.log` - Log diário (rotação automática)
- `error.log` - Erros gerais (fallback)
- `php-errors.log` - Erros PHP (error_log)

**Formato (JSON estruturado):**
```
[YYYY-MM-DD HH:MM:SS] [LEVEL] MESSAGE | {"context":"json"}
```

**Exemplo real (linha 1 de aegis-2026-01-16.log):**
```
[2026-01-16 11:25:09] [INFO] AUDIT: Admin login | {"type":"audit","user_id":"07d744ce-69d1-4d22-b857-454459090542","ip":"::1","email":"fabio@sociaholic.com.br"}
```

**Níveis suportados:**
- `[INFO]` - Informações gerais
- `[WARNING]` - Alertas (ex: CSRF failed)
- `[ERROR]` - Erros críticos
- `[DEBUG]` - Debug (apenas dev)

**Tipos de logs:**
- **AUDIT:** Login, logout, ações admin
- **SECURITY:** CSRF fails, rate limit, tentativas suspeitas
- **ERROR:** Exceptions, falhas de banco
- **PERFORMANCE:** Queries lentas

**Rotação:** Arquivo novo por dia (YYYY-MM-DD)

**Limpeza:** Manual (ou script agendado)

**Classificação:** 100% CORE

---

### 3. uploads/ (4 imagens + estrutura)

**Função:** Armazenamento de arquivos enviados (Upload.php)

**Segurança (.htaccess - 25 linhas):**

**Bloqueios:**
```apache
# 1. Desabilitar PHP completamente
php_flag engine off

# 2. NEGAR TUDO por padrão
Order Deny,Allow
Deny from all

# 3. Whitelist APENAS arquivos seguros
<FilesMatch "\.(jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx|txt|csv)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# 4. BLOQUEAR dupla extensão (bypass)
<FilesMatch "\.(php|phtml|php3|php4|php5|phps|cgi|pl|py|sh|exe|bat|com|dll|so)\.">
    Order Deny,Allow
    Deny from all
</FilesMatch>
```

**Headers de segurança:**
```apache
Header set X-Content-Type-Options "nosniff"
Header set Content-Security-Policy "default-src 'none'; img-src 'self'; style-src 'none'; script-src 'none';"
```

**Proteções:**
- ✅ PHP execution OFF
- ✅ Deny all por padrão
- ✅ Whitelist de extensões
- ✅ Bloqueio dupla extensão (.php.jpg)
- ✅ Content-Type nosniff
- ✅ CSP restritiva

**Subpastas:**

**blog/ (vazia)**
- Imagens de posts (TinyMCE upload)

**members/avatars/ (4 imagens)**
- `69618cb13abbc_1768000689.png`
- `696188c796abf_1767999687.png`
- `6961899ebdb4b_1767999902.png`
- `6961886f04994_1767999599.png`

**Padrão nome:** `{hash}_{timestamp}.{ext}`
- Hash: Parte do uniqid() (linha Upload.php:XXX)
- Timestamp: Unix timestamp do upload

**palpiteiros/ (vazia)**
- Fotos dos palpiteiros (módulo Palpites)

**times/ (vazia)**
- Escudos dos times (módulo Palpites)

**Classificação:** 80% CORE / 20% APP-SPECIFIC (subpastas de módulos)

---

## 📄 ARQUIVOS DE CONFIGURAÇÃO

### 1. settings.json (12 linhas)

**Função:** Configurações editáveis via /admin/settings

**Estrutura:**
```json
{
    "admin_email": "fabio@sociaholic.com.br",
    "site_name": "AEGIS Framework2.0",
    "maintenance_mode": false,
    "timezone": "America/Sao_Paulo",
    "theme_color_main": "#6c10b8",
    "theme_color_second": "#C41C1C",
    "theme_color_third": "#A39D8F",
    "theme_font_primary": "'roboto', sans-serif",
    "theme_font_secondary": "'inter', sans-serif",
    "tinymce_api_key": "no-api-key"
}
```

**Campos:**

**Essenciais:**
- `admin_email` - Email do administrador (notificações)
- `site_name` - Nome do site (exibido no admin)
- `maintenance_mode` - true/false (bloqueia acesso público)
- `timezone` - Fuso horário (PHP date_default_timezone_set)

**Tema:**
- `theme_color_main` - Cor principal (hex)
- `theme_color_second` - Cor secundária (hex)
- `theme_color_third` - Cor terciária (hex)
- `theme_font_primary` - Font stack principal
- `theme_font_secondary` - Font stack secundária

**Integrações:**
- `tinymce_api_key` - Chave API TinyMCE (editor WYSIWYG)

**Acesso no código:**
```php
Settings::get('site_name'); // "AEGIS Framework2.0"
Settings::set('maintenance_mode', true);
```

**Classificação:** 100% CORE

---

### 2. versions.json (549 linhas - truncado em 50)

**Função:** Histórico completo de versões (Version.php auto-bump)

**Estrutura (cada entrada):**
```json
{
    "version": "14.0.6",
    "previous_version": "14.0.5",
    "type": "patch",
    "description": "Auto-bump: Apenas modificações em arquivos existentes",
    "changes": [
        ".DS_Store",
        "deploys/.DS_Store",
        "config.codekit3",
        "admin/.DS_Store",
        "admin/deploy.php",
        "admin/api/import-csv.php",
        "admin/api/process-csv.php"
    ],
    "date": "2026-01-16",
    "timestamp": 1768562709
}
```

**Campos:**
- `version` - Versão atual (semver: major.minor.patch)
- `previous_version` - Versão anterior
- `type` - Tipo de bump (`major`, `minor`, `patch`)
- `description` - Descrição do bump (manual ou auto)
- `changes` - Array de arquivos modificados (git diff)
- `date` - Data do bump (YYYY-MM-DD)
- `timestamp` - Unix timestamp

**Auto-bump regras (Version.php):**
- **PATCH:** Modificações em arquivos existentes
- **MINOR:** Novos arquivos criados
- **MAJOR:** Deletions ou mudanças estruturais

**Acesso:**
```php
Version::getCurrentVersion(); // "14.0.6"
Version::getHistory(); // Array completo
```

**Uso:** /admin/version (visualização + bump manual)

**Classificação:** 100% CORE

---

### 3. last-bump.txt (1 linha)

**Função:** Data do último bump (cache rápido)

**Conteúdo:**
```
2026-01-16
```

**Uso:** Version.php lê este arquivo para evitar bump duplicado no mesmo dia

**Classificação:** 100% CORE

---

## 🎯 PADRÕES IDENTIFICADOS

### 1. Rotação de Logs

**Automática por dia:**
```
aegis-2025-11-26.log
aegis-2025-11-27.log
aegis-2025-12-01.log
...
aegis-2026-01-16.log
```

**Vantagens:**
- Facilita auditoria (1 arquivo = 1 dia)
- Logs não crescem infinitamente
- Fácil deletar logs antigos (> 30 dias)

---

### 2. JSON Estruturado nos Logs

**Formato:**
```
[timestamp] [level] message | {"context":"json"}
```

**Parse fácil:**
```php
$parts = explode(' | ', $line);
$context = json_decode($parts[1], true);
```

**Campos comuns:**
- `type` - Categoria (audit, security, error)
- `user_id` - UUID do usuário
- `ip` - IP do cliente
- `method` - HTTP method (GET, POST)
- `uri` - Request URI

---

### 3. Upload Naming

**Pattern:** `{hash}_{timestamp}.{ext}`

**Exemplo:** `69618cb13abbc_1768000689.png`

**Vantagens:**
- Zero colisão (hash + timestamp únicos)
- Ordenação cronológica (timestamp)
- Não revela nome original (segurança)

---

### 4. Uploads Organizados por Módulo

```
uploads/
├── blog/          → Módulo blog
├── members/       → Módulo members (CORE)
├── palpiteiros/   → Módulo palpites
└── times/         → Módulo palpites
```

**Self-contained:** Deletar módulo = deletar pasta uploads

---

### 5. .htaccess Defense in Depth

**Camadas:**
1. PHP execution OFF
2. Deny ALL
3. Whitelist extensões seguras
4. Block double extension
5. Security headers

**Protege contra:**
- PHP shell upload
- Bypass via .php.jpg
- MIME sniffing attacks
- XSS via SVG

---

### 6. Settings em JSON (não banco)

**Motivo:**
- Sites estáticos (DB_TYPE=none) precisam de settings
- Performance (1 file read vs query)
- Fácil backup/restore

**Trade-off:**
- Não auditável (sem log de mudanças)
- Race condition possível (escritas simultâneas)

---

## 📊 ESTATÍSTICAS

**Total:** 47 arquivos

**Por categoria:**
- Logs: 29 arquivos (~10-50KB cada, total ~1MB)
- Cache: 2 arquivos (.gitkeep + 1 cache)
- Uploads: 4 imagens (avatars)
- Config: 3 JSON (settings, versions, last-bump)
- Security: 1 .htaccess (uploads)
- Sistema: 8 .DS_Store (lixo macOS)

**Tamanho estimado:** ~1.5MB (logs + uploads)

**Classificação geral:**
- **CORE-AEGIS:** 90% (logs, cache, settings, versions)
- **APP-SPECIFIC:** 10% (uploads de módulos específicos)

---

## 🔧 OPORTUNIDADES

### Pontos Fortes
✅ Logs estruturados (JSON parseable)
✅ Rotação diária automática
✅ Upload security layers (5 camadas)
✅ Settings fora do banco (static-friendly)
✅ Version tracking completo
✅ Uploads organizados por módulo
✅ .htaccess hardened

### Melhorias Identificadas

1. **Log rotation automática:**
   - Deletar logs > 30 dias (cron job)
   - Comprimir logs antigos (.gz)

2. **Cache stats:**
   - Dashboard em /admin/cache
   - Hit rate, size, keys count

3. **Upload limits:**
   - Max file size configurável (settings.json)
   - Quota por usuário/módulo

4. **Settings validation:**
   - Schema JSON (validar tipos)
   - Backup antes de salvar

5. **Log search:**
   - Interface em /admin/logs
   - Filtros: level, type, user, date range

6. **.gitkeep removal:**
   - Gerar .gitkeep automaticamente se pasta vazia
   - Não commitar .gitkeep (desnecessário)

7. **Cleanup .DS_Store:**
   - Adicionar ao .gitignore global
   - Script para limpar recursivamente

8. **Storage stats:**
   - Dashboard: total size, files count, breakdown por módulo
   - Alertas: storage > 80%

9. **Upload CDN:**
   - Integração com S3/DigitalOcean Spaces
   - Fallback local se CDN falhar

10. **Log levels configuráveis:**
    - Settings: `log_level` (DEBUG, INFO, WARNING, ERROR)
    - Ambiente dev: DEBUG
    - Produção: WARNING

---

## ⚠️ AVISOS DE SEGURANÇA

### 1. Nunca commitar storage/

**Git deve ignorar:**
```gitignore
storage/cache/*
!storage/cache/.gitkeep

storage/logs/*
!storage/logs/.gitkeep

storage/uploads/*
!storage/uploads/.gitkeep

storage/settings.json
storage/versions.json
storage/last-bump.txt
```

**Motivo:**
- Logs contém IPs, emails, UUIDs
- Settings pode ter API keys
- Uploads são dados do usuário

---

### 2. Backups regulares

**Crítico:**
- `settings.json` - Config do site
- `versions.json` - Histórico
- `uploads/` - Arquivos usuários

**Não crítico:**
- `logs/` - Pode ser recriado
- `cache/` - Temporário

---

### 3. Permissões corretas

**Recomendado:**
```bash
chmod 755 storage/
chmod 755 storage/cache/
chmod 755 storage/logs/
chmod 755 storage/uploads/

chmod 644 storage/settings.json
chmod 644 storage/versions.json
```

**Nunca 777!**

---

## 📝 NOTA FINAL: 9/10

Pasta `/storage/` **extremamente bem organizada**, com logs estruturados, segurança rigorosa em uploads e versionamento automático.

**Destaques:**
- Logs JSON parseables (auditoria fácil)
- 5 camadas de segurança em uploads
- Settings fora do banco (static-friendly)
- Version tracking completo (auto-bump)
- Upload naming anti-colisão

**Único ponto negativo:**
- Falta limpeza automática de logs antigos
- Settings sem validação de schema
- 8 arquivos .DS_Store (lixo macOS)
