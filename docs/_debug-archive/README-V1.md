# ⚡ AEGIS Framework v1.0

> Framework PHP modular, seguro e reutilizável para projetos web escaláveis

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MySQL%20%7C%20Supabase-green)](https://supabase.com)
[![License](https://img.shields.io/badge/License-Proprietary-red)]()

---

## 🎯 O que é AEGIS?

Framework PHP completo para construir dashboards, painéis admin e aplicações web com:
- ✅ Segurança nativa (CSRF, XSS, SQL injection protection)
- ✅ Sistema de componentes reutilizáveis (PageBuilder)
- ✅ Arquitetura modular (plugins/módulos instaláveis)
- ✅ Multi-database (MySQL local ou Supabase cloud)
- ✅ Sistema de permissões granular
- ✅ Rate limiting integrado
- ✅ Cache estratégico

---

## 📦 Instalação

### Requisitos
- PHP 8.2+
- MySQL 5.7+ ou Supabase
- Composer
- Apache/Nginx com mod_rewrite

### Quick Start (3 minutos)

```bash
# 1. Clone o projeto
git clone [seu-repo]/aegis-framework.git
cd aegis-framework

# 2. Instale dependências
composer install

# 3. Execute o installer
# Acesse: http://localhost/setup.php
# Preencha 3 telas e pronto!
```

---

## 🚀 Recursos Principais

### 1. PageBuilder com 10 Componentes
```php
// Criar tabela dinâmica
Component::render('tabelas', [
    'data_source' => 'database',
    'table' => 'tbl_users',
    'columns' => 'id,name,email,created_at',
    'sortable' => 'yes',
    'pagination' => 'yes'
]);

// Criar gráfico
Component::render('graficos', [
    'chart_type' => 'line',
    'table' => 'tbl_analytics',
    'columns' => 'views,clicks',
    'date_field' => 'date'
]);
```

**Componentes disponíveis:**
- Tabelas (sortable, searchable, pagination)
- Gráficos (ApexCharts - line, bar, pie, donut)
- Cards de métricas (com comparação de período)
- Filtros (data, dropdown, multi-select)
- Hero sections
- HTML livre
- Image links
- Spacers

### 2. Sistema de Módulos

Módulos são mini-aplicações independentes com suas próprias rotas, views, controllers e banco.

**Estrutura de um módulo:**
```
modules/blog/
├── module.json          # Metadata
├── routes.php           # Rotas públicas e admin
├── controllers/         # Lógica
├── views/               # Templates
├── database/            # Schema SQL
└── assets/              # CSS/JS específico
```

**Módulos inclusos:**
- **Blog**: Sistema completo de blog com categorias, SEO, posts relacionados
- **Palpites**: Sistema de palpites de jogos com ranking

### 3. Multi-Database

Suporte nativo para:
- **MySQL** (local ou remoto)
- **Supabase** (PostgreSQL cloud)
- **None** (modo estático sem banco)

Troca entre bancos apenas alterando `_config.php`:
```php
define('DB_TYPE', 'mysql'); // ou 'supabase' ou 'none'
```

### 4. Segurança por Padrão

```php
// Prepared statements obrigatórios
$db->query("SELECT * FROM users WHERE id = ?", [$id]);

// CSRF automático em forms
<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

// Sanitização
$clean = Security::sanitize($_POST['name']);

// Rate limiting
Middleware::throttle('60,60'); // 60 req por 60s
```

### 5. Sistema de Permissões

```php
// Admin vs Member
Auth::require();         // Admin only
MemberAuth::require();   // Member only

// Permissões granulares por página
Permission::check($memberId, $pageSlug);

// Permissões em menu
MenuPermissionChecker::canAccess($menuItem);
```

---

## 📁 Estrutura do Projeto

```
aegis-framework/
├── admin/              # Painel administrativo
│   ├── controllers/    # Controllers do admin
│   └── views/          # Views do admin
├── api/                # REST APIs
├── assets/             # CSS, JS, imagens
├── components/         # Componentes do PageBuilder
├── core/               # Classes fundamentais (54 classes)
├── database/           # Adapters e migrations
├── frontend/           # Templates públicos
│   ├── pages/          # Páginas
│   ├── templates/      # Layouts
│   └── includes/       # Partials
├── modules/            # Módulos instaláveis
├── public/             # Controllers públicos
├── routes/             # Definições de rotas
├── storage/            # Logs, cache, uploads
├── uploads/            # Arquivos de usuários
├── _config.php         # Configuração (não commitar)
├── index.php           # Entry point
├── setup.php           # Installer wizard
├── composer.json       # Dependências
└── README.md           # Este arquivo
```

---

## 🔧 Uso Básico

### Criar uma Rota

```php
// routes/public.php
Router::get('/produtos', function() {
    $db = DB::connect();
    $produtos = $db->select('produtos', ['ativo' => 1]);
    require ROOT_PATH . 'frontend/pages/produtos.php';
});
```

### Criar um Controller

```php
// admin/controllers/ProdutoController.php
class ProdutoController extends BaseController {
    public function index() {
        $produtos = $this->db->select('produtos');
        $this->view('produtos/index', ['produtos' => $produtos]);
    }

    public function create() {
        Security::validateCSRF($_POST['csrf_token']);

        $data = [
            'id' => Security::generateUUID(),
            'nome' => Security::sanitize($_POST['nome']),
            'preco' => (float) $_POST['preco']
        ];

        $this->db->insert('produtos', $data);
        Core::redirect('/admin/produtos');
    }
}
```

### Criar uma Migration

```sql
-- database/migrations/create_produtos.sql
CREATE TABLE produtos (
    id VARCHAR(36) PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🎨 Personalização

### Temas
```php
// _config.php
define('THEME', 'dark'); // ou 'light'
```

### Logo
```
assets/img/logo.svg      # Logo principal
assets/img/logo-dark.svg # Logo para dark mode
```

### Cores
```scss
// assets/sass/_variables.sass
$primary: #667eea
$secondary: #764ba2
```

---

## 🔐 Segurança

### Checklist de Deploy

- [ ] Mudar `DEBUG_MODE` para `false`
- [ ] Mudar `session.cookie_secure` para `1`
- [ ] Usar credenciais fortes no banco
- [ ] Configurar HTTPS
- [ ] Ativar HSTS headers
- [ ] Revisar permissões de pastas (755/644)
- [ ] Configurar backup automático

### Proteções Ativas

- ✅ CSRF tokens em todos os forms
- ✅ Prepared statements (zero concatenação SQL)
- ✅ XSS protection via `htmlspecialchars()`
- ✅ Rate limiting (60 req/min)
- ✅ Session hijacking protection
- ✅ Password hashing (bcrypt cost 12)
- ✅ Upload validation (MIME type real)
- ✅ Scripts bloqueados via `.htaccess`

---

## 📊 Performance

### Cache
```php
// Cache automático de 5 segundos
Cache::set('chave', $dados, 5);
$dados = Cache::get('chave');
```

### Queries Otimizadas
```php
// Evitar N+1
$posts = $db->query("
    SELECT p.*, a.name as author_name
    FROM posts p
    LEFT JOIN authors a ON p.author_id = a.id
");
```

### Índices no Banco
```sql
-- Adicionar índices para queries frequentes
CREATE INDEX idx_posts_status ON posts(status);
CREATE INDEX idx_posts_created_at ON posts(created_at);
```

---

## 🧪 Testes

```bash
# Executar testes (quando implementado em v2)
php aegis test

# Testes manuais
# Ver: TESTES-V1.md
```

---

## 📚 Documentação

- **MELHORIAS-V2.md** - Roadmap completo para v2.0
- **TESTES-V1.md** - Checklist de testes manuais
- **docs/aegis/** - Documentação técnica do framework
- **.claude/** - Comandos e processos para Claude Code

---

## 🆘 Suporte

### Problemas Comuns

**Erro: "Table 'users' doesn't exist"**
```bash
# Executar installer novamente ou importar schema manualmente
mysql -u root -p database < database/schemas/mysql-schema.sql
```

**Erro: "CSRF token inválido"**
```php
// Verificar se sessão está iniciada
session_start(); // No topo do index.php
```

**Página em branco**
```php
// Ativar debug mode temporariamente
define('DEBUG_MODE', true); // _config.php
```

---

## 🗺️ Roadmap

### v1.0 (Atual)
- ✅ Sistema seguro e funcional
- ✅ 10 componentes
- ✅ 2 módulos
- ✅ Multi-database

### v2.0 (6 meses)
- [ ] CLI de scaffolding
- [ ] Query Builder fluente
- [ ] Testes automatizados
- [ ] Docker Compose
- [ ] Marketplace de módulos

Ver: **MELHORIAS-V2.md** para roadmap completo

---

## 📄 Licença

Proprietary - Uso interno

---

## 👨‍💻 Autor

**Fábio Chezzi** + Claude Code AI

---

## ⭐ Próximos Passos

1. Execute os testes em `TESTES-V1.md`
2. Customize o tema em `assets/sass/`
3. Crie seu primeiro módulo
4. Leia `MELHORIAS-V2.md` para evoluir o framework

**Bom desenvolvimento! 🚀**
