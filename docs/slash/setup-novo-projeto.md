# Setup Novo Projeto AEGIS

> **Quando usar:** Tarefas de instalação, configuração inicial, troubleshooting de setup

---

## 📋 Processo de Instalação

### 1. Upload e Banco de Dados
```bash
# 1. Upload arquivos para servidor
# 2. Criar banco MySQL ou configurar Supabase
# 3. Acessar setup.php no navegador
```

### 2. Wizard de Instalação (setup.php)
```
Preencher:
- Tipo de banco: mysql / supabase / none
- Credenciais do banco
- URL do projeto (com ou sem subpasta)
- Email/senha do primeiro admin
```

**Resultado:** Cria tabelas + admin + `_config.php`

### 3. Login Admin
```
Acessar: /admin
Usar credenciais criadas no wizard
```

---

## ⚙️ _config.php (Configurações Principais)

### Database
```php
define('DB_TYPE', 'mysql');  // mysql, supabase, none
define('DB_HOST', 'localhost');
define('DB_NAME', 'nome_banco');
define('DB_USER', 'usuario');
define('DB_PASS', 'senha');
```

### Environment
```php
define('DEBUG_MODE', false);  // true em dev, false em produção
define('APP_URL', 'https://seudominio.com');  // ou https://seudominio.com/subpasta
define('TIMEZONE', 'America/Sao_Paulo');
```

### Members System
```php
define('ENABLE_MEMBERS', true);  // false = todo frontend público (REGRAS.md #5)
```

### Security
```php
define('AEGIS_API_TOKEN', 'token-aqui');  // Trocar em produção (REGRAS.md)
```

**⚠️ NUNCA versionar _config.php com credenciais reais**

---

## 📦 Instalação de Módulos

### Via Admin
```
1. Acessar /admin/modules
2. Clicar "Instalar" no módulo desejado
3. Sistema cria tabelas automaticamente
```

### Programaticamente
```php
$result = ModuleManager::install('nome-modulo');
if ($result['success']) {
    // Instalado
}
```

**Fonte de verdade:** `module.json` com `"public": true/false` (REGRAS.md #10)

---

## 🛡️ Segurança em Produção (Checklist)

```
□ DEBUG_MODE = false
□ Deletar setup.php do servidor
□ HTTPS habilitado
□ Senhas fortes
□ .htaccess configurado (Apache) ou nginx.conf
□ _config.php no .gitignore
□ AEGIS_API_TOKEN trocado
□ Permissões corretas: 755 pastas, 644 arquivos
```

### .htaccess Recomendado (Apache)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Bloquear arquivos sensíveis
<FilesMatch "(^\..*|composer\.(json|lock)|package\.json|\.md)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## 🆘 Troubleshooting (Problemas Comuns)

### 500 Internal Server Error
```bash
# 1. Verificar logs
tail -f storage/logs/error.log

# 2. Ativar debug temporário
# _config.php: DEBUG_MODE = true

# 3. Verificar permissões
chmod 755 pasta/
chmod 644 arquivo.php

# 4. Verificar .htaccess existe
```

### 404 em Todas as Rotas
```bash
# 1. Verificar .htaccess existe e está correto
# 2. Apache: verificar mod_rewrite habilitado
a2enmod rewrite
service apache2 restart

# 3. Verificar APP_URL em _config.php
# Deve incluir subpasta se houver
```

### Upload Não Funciona
```bash
# 1. Verificar permissões
chmod 777 storage/uploads/

# 2. Verificar php.ini
upload_max_filesize = 10M
post_max_size = 10M

# 3. Verificar validações (REGRAS.md #9)
# - Tipo/extensão permitida
# - Tamanho máximo
# - Mime type real
```

### Email Não Envia
```php
// Verificar configurações SMTP em /admin/settings
// Gmail: usar App Password, não senha normal
// https://myaccount.google.com/apppasswords

// Verificar logs
tail -f storage/logs/error.log
```

### Banco de Dados Não Conecta
```bash
# 1. Verificar credenciais em _config.php
# 2. Testar conexão manualmente
mysql -u usuario -p nome_banco

# 3. Verificar se banco existe
# 4. Verificar permissões do usuário MySQL
```

### Módulo Não Instala
```bash
# 1. Verificar module.json existe
# 2. Verificar database/mysql-schema.sql ou supabase-schema.sql
# 3. Verificar logs de erro
# 4. Tentar instalação manual via SQL
```

---

## 🔧 Comandos Úteis

### Verificar Instalação
```bash
# PHP está funcionando?
php -v

# Módulos PHP instalados
php -m

# Logs em tempo real
tail -f storage/logs/error.log

# Testar sintaxe PHP
php -l arquivo.php
```

### Permissões Rápidas
```bash
# Aplicar permissões padrão
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Storage com escrita
chmod -R 777 storage/
```

---

## 📚 Pós-Instalação

### Configurar via /admin/settings
```
Email (SMTP):
- Servidor: smtp.gmail.com
- Porta: 587 (TLS) ou 465 (SSL)
- Usuário: email completo
- Senha: App Password

RD Station (opcional):
- API Key do painel RD Station

Tema:
- Cores (hex)
- Fontes (CSS)

TinyMCE (se usar editor):
- API Key de tiny.cloud
```

### Próximos Passos
```
1. Configurar SMTP em /admin/settings
2. Instalar módulos necessários em /admin/modules
3. Criar conteúdo inicial
4. Configurar backup do banco
5. Monitorar storage/logs/
```

---

## 🎯 Referências

- **REGRAS.md #5:** Sistema sem members (ENABLE_MEMBERS = false)
- **REGRAS.md #9:** Upload validações obrigatórias
- **REGRAS.md #10:** Módulos vs Páginas
- **known-issues.md:** Problemas conhecidos com soluções

---

**Versão:** 2.0.0
**Data:** 2026-02-14
**Changelog:** Removido workflows manuais e checklists de teste do usuário, focado apenas em setup técnico e troubleshooting
