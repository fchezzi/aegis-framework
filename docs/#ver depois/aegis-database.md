# AEGIS Framework - Pasta /database/

**Versão AEGIS:** 14.0.7
**Data:** 2026-01-18

[← Voltar ao índice](aegis-estrutura.md)

---

## 📊 RESUMO

**Total:** 48 arquivos
**Schemas:** 6 arquivos (1.336 linhas - MySQL + Supabase)
**Adapters:** 5 arquivos (757 linhas)
**Migrations:** 15 arquivos
**Utils:** 3 arquivos (drops, resets)
**Archived:** 11 arquivos (histórico)
**App-specific:** 7 tabelas de canais (Instagram, X, TikTok, etc.)
**Deploy:** 1 schema completo (695 linhas)

---

## 🏗️ ARQUITETURA

### Estrutura

```
database/
├── adapters/          # Database abstraction layer (5 arquivos)
├── schemas/           # Schemas completos MySQL + Supabase (6 arquivos)
├── migrations/        # Alterações incrementais (15 arquivos)
├── utils/             # Scripts utilitários (3 arquivos)
├── _archived/         # Migrations antigas (11 arquivos)
├── samples/           # Dados de exemplo (CSV)
├── DEPLOY-SCHEMA-COMPLETO.sql  # Schema unificado para deploy
└── create_tbl_*.sql   # Tabelas APP-SPECIFIC (7 arquivos)
```

---

## 📁 ADAPTERS (5 arquivos - 757 linhas)

### 1. DatabaseInterface.php (80 linhas)

**Função:** Contrato (interface) para todos adapters

**Métodos obrigatórios:**
```php
public function connect();
public function disconnect();
public function select($table, $where = [], $options = []);
public function insert($table, $data);
public function update($table, $data, $where);
public function delete($table, $where);
public function query($sql, $params = []);
public function getLastId();
public function tableExists($table);
public function getColumns($table);
```

**Classificação:** 100% CORE

---

### 2. DatabaseFactory.php (55 linhas)

**Função:** Factory pattern para criar adapters

**Suporta 3 tipos:**
```php
public static function create($type, $config = []) {
    switch (strtolower($type)) {
        case 'mysql':
            return new MySQLAdapter(...);

        case 'supabase':
            return new SupabaseAdapter(...);

        case 'none':
            return new NoneAdapter(); // Sites estáticos

        default:
            throw new Exception("Database type '{$type}' not supported");
    }
}
```

**Auto-connect:** Chama `$adapter->connect()` antes de retornar (linha 33, 44)

**Classificação:** 100% CORE

---

### 3. MySQLAdapter.php (250 linhas)

**Função:** Implementação MySQL via PDO

**Recursos:**

**Connection (linhas 23-66):**
- Conecta SEM especificar banco (linha 26)
- Cria banco se não existir: `CREATE DATABASE IF NOT EXISTS` (linha 46)
- **UTF8MB4 forçado** em 6 variáveis de sessão (linhas 52-57)
- **Connection Pooling DESABILITADO** (linha 40)
  - Motivo: Causava charset errado + duplicação de registros
  - Problema: `PERSISTENT=true` impedia re-execução de `SET NAMES`
- PHP 8.5+ compatibility (linha 29): `Pdo\Mysql::ATTR_INIT_COMMAND`
- SQL mode: `TRADITIONAL` (linha 60)

**Select (linhas 72-120):**
- Sanitização nome tabela: `preg_replace('/[^a-zA-Z0-9_]/', '', $table)` (linha 74)
- Prepared statements (linha 96)
- Suporte ORDER, LIMIT, OFFSET (linhas 103-118)

**Insert (linhas 122-143):**
- Prepared statements
- Retorna `lastInsertId()` (linha 141)

**Update (linhas 145-172):**
- Prepared statements
- Retorna bool (linha 170)

**Delete (linhas 174-194):**
- Prepared statements
- Retorna bool (linha 192)

**Query customizada (linhas 196-213):**
- Detecta SELECT vs outros (linha 205)
- Retorna array ou bool

**Helpers:**
- `getLastId()` - PDO::lastInsertId()
- `tableExists($table)` - Query `SHOW TABLES LIKE` (linha 223)
- `getColumns($table)` - Query `SHOW COLUMNS FROM` (linha 234)

**Segurança:**
- Prepared statements em TODOS os métodos
- Sanitização de table names
- PDO::ATTR_EMULATE_PREPARES = false (linha 34)

**Classificação:** 100% CORE

---

### 4. SupabaseAdapter.php (323 linhas)

**Função:** Implementação Supabase REST API

**Connection (linhas 15-24):**
- cURL-based (REST API)
- Valida URL + Key (linha 18)

**Headers padrão (linhas 26-34):**
```php
'apikey: ' . $this->apiKey,
'Authorization: Bearer ' . $this->apiKey,
'Content-Type: application/json',
'Prefer: return=representation' // Retorna dados após INSERT/UPDATE
```

**Select (linhas 54-89):**
- Endpoint: `GET /rest/v1/{table}?{filters}`
- Filtra via query params: `column=eq.value`
- Suporte ORDER, LIMIT, OFFSET (linha 66-83)

**Insert (linhas 91-132):**
- Endpoint: `POST /rest/v1/{table}`
- Auto-gera UUID se não fornecido (linha 96)
- Retorna ID do registro criado (linha 128)

**Update (linhas 134-163):**
- Endpoint: `PATCH /rest/v1/{table}?{where}`
- Retorna bool (linha 159)

**Delete (linhas 165-190):**
- Endpoint: `DELETE /rest/v1/{table}?{where}`
- Retorna bool (linha 186)

**Query customizada (linhas 192-215):**
- **Via RPC:** `/rest/v1/rpc/exec_query` (linha 197)
- Requer função `exec_query()` no Supabase (definida em supabase-schema.sql)

**tableExists (linhas 227-264):**
- Consulta `information_schema.tables` via RPC

**getColumns (linhas 266-306):**
- **Via RPC:** `/rest/v1/rpc/get_table_columns` (linha 271)
- Requer função `get_table_columns()` no Supabase

**Classificação:** 100% CORE

---

### 5. NoneAdapter.php (49 linhas)

**Função:** Stub para sites estáticos (sem banco)

**Todos métodos:**
- `connect()` → `return true` (linha 8)
- `select()` → `return []` (linha 12)
- `insert()` → `return null` (linha 16)
- `update()` → `return false` (linha 20)
- Etc.

**Uso:** Sites puramente estáticos (sem admin, sem membros)

**Classificação:** 100% CORE

---

## 📄 SCHEMAS (6 arquivos - 1.336 linhas)

### Arquitetura Multi-DB

**Par completo:**
- `mysql-schema.sql` (316 linhas) + `mysql-schema-minimal.sql` (137 linhas)
- `supabase-schema.sql` (501 linhas) + `supabase-schema-minimal.sql` (275 linhas)

**Globals:**
- `supabase-global-setup.sql` (83 linhas) - Funções RPC
- `supabase-query-function.sql` (24 linhas) - exec_query()

---

### mysql-schema.sql (316 linhas)

**17 tabelas CORE-AEGIS:**

**1. Autenticação (2 tabelas):**
- `users` - Admins (id VARCHAR(36), email UNIQUE, password bcrypt)
- `members` - Usuários site (+ avatar)

**2. Permissões (5 tabelas):**
- `groups` - Grupos de membros
- `member_groups` - N:N (member ↔ group)
- `pages` - Páginas do site
- `page_permissions` - N:N (group ↔ page)
- `member_page_permissions` - N:N (member ↔ page) - permissões individuais

**3. Sistema (4 tabelas):**
- `modules` - Módulos instalados (name, version, config JSON)
- `settings` - Configurações key-value (JSON)
- `security_tests` - Resultados health check
- `performance_tests` - Testes performance

**4. Page Builder (2 tabelas):**
- `page_blocks` - Blocos de layout (order, cols, component_name)
- `page_cards` - Cards dentro dos blocos (component_data JSON)

**5. Menu (1 tabela):**
- `menu_items` - Menu dinâmico (parent_id, ordem, icon, grupos)

**6. Includes (1 tabela):**
- `includes` - Includes customizáveis (header, footer, GTM)

**7. Cache (1 tabela):**
- `cache` - Cache persistente (key, value, expires_at)

**8. Queue (1 tabela):**
- `queue_jobs` - Fila de processamento (status, attempts, payload JSON)

**Índices:**
- SEMPRE em: id, email, slug, ativo, created_at
- Foreign keys com `ON DELETE CASCADE`

**Charset:** UTF8MB4 + utf8mb4_unicode_ci

**Engine:** InnoDB (suporta transactions + FK)

**Classificação:** 100% CORE

---

### supabase-schema.sql (501 linhas)

**Diferenças do MySQL:**

**1. Funções globais (linhas 9-50):**
```sql
-- exec_sql(query TEXT) - Executar DDL via RPC
-- get_table_columns(p_table_name TEXT) - Listar colunas
-- exec_query(query_text TEXT) - SELECT com retorno JSON
```

**2. UUID nativo (linha 56):**
```sql
id UUID PRIMARY KEY DEFAULT gen_random_uuid()
```

**3. Triggers updated_at (linhas 66-77):**
```sql
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();
```

**4. Tipos Postgres:**
- `BOOLEAN` (não `TINYINT`)
- `TIMESTAMP WITH TIME ZONE` (não `TIMESTAMP`)
- `JSONB` (não `JSON`)
- `TEXT` (não `VARCHAR(255)`)

**5. Mesmas 17 tabelas do MySQL**

**Classificação:** 100% CORE

---

### supabase-global-setup.sql (83 linhas)

**Função:** Setup inicial Supabase (antes do schema)

**Funções RPC:**
1. `exec_sql(query TEXT)` - Executar DDL (CREATE TABLE, etc.)
2. `get_table_columns(p_table_name TEXT)` - Metadata
3. `exec_query(query_text TEXT)` - SELECT → JSONB

**Uso:** ModuleInstaller executa schemas via RPC (sem acesso direto ao Postgres)

**SECURITY DEFINER:** Funções executam com privilégios do owner (linha 21)

**Classificação:** 100% CORE

---

### mysql-schema-minimal.sql (137 linhas)

**Função:** Schema mínimo (sem membros)

**6 tabelas:**
- users
- pages
- page_blocks
- page_cards
- modules
- settings

**Uso:** Sites sem área de membros (ENABLE_MEMBERS = false)

**Classificação:** 100% CORE

---

### supabase-schema-minimal.sql (275 linhas)

**Função:** Schema mínimo Supabase

**Mesmas 6 tabelas do mysql-schema-minimal**

**Classificação:** 100% CORE

---

## 📂 MIGRATIONS (15 arquivos)

**Padrão:**
- Alterações incrementais (ADD COLUMN, CREATE TABLE, etc.)
- Nome: `YYYY_MM_DD_description.sql` ou descritivo

**Exemplos:**

**1. 2026_01_11_create_report_tables.sql**
- Cria `report_templates` + `report_cells`
- Para módulo Reports

**2. add_is_public_to_pages.sql**
- Adiciona campo `is_public TINYINT(1)` em `pages`

**3. add_module_name_to_pages.sql**
- Relaciona página → módulo (`module_name VARCHAR(100)`)

**4. 003_module_migrations.sql**
- Sistema de tracking de módulos instalados

**5. 2024_01_01_000003_create_queue_tables.php**
- **Única migration PHP** (para usar classe Migration)
- Cria tabela `queue_jobs`

**Classificação:** 90% CORE / 10% APP-SPECIFIC

---

## 🛠️ UTILS (3 arquivos)

### 1. LIMPAR_BANCO.sql

**Função:** Deletar TODOS os dados (manter estrutura)

```sql
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE users;
TRUNCATE TABLE members;
-- ... todas tabelas
SET FOREIGN_KEY_CHECKS = 1;
```

**Uso:** Limpar ambiente de testes

---

### 2. GERAR_DROPS.sql

**Função:** Gerar comandos DROP TABLE

```sql
SELECT CONCAT('DROP TABLE IF EXISTS `', table_name, '`;')
FROM information_schema.tables
WHERE table_schema = 'futebolenergia';
```

**Uso:** Destruir banco completo (gera SQL para executar)

---

### 3. reset-all-data.sql

**Função:** Reset completo (estrutura + dados)

```sql
DROP DATABASE IF EXISTS futebolenergia;
CREATE DATABASE futebolenergia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Classificação:** 100% CORE

---

## 📦 DEPLOY-SCHEMA-COMPLETO.sql (695 linhas)

**Função:** Schema unificado para deploy em produção

**Versão:** 14.0.1 (linha 4)
**Framework:** AEGIS v13 (linha 6)

**Estrutura:**

**PARTE 1: CORE AEGIS (17 tabelas)** (linha 18)
- Todas tabelas do framework

**PARTE 2: MÓDULOS INSTALADOS** (linha ~300)
- Blog (3 tabelas)
- Palpites (6 tabelas + 2 views)
- Reports (3 tabelas)

**PARTE 3: APP-SPECIFIC** (linha ~500)
- Tabelas de canais (instagram, x, tiktok, etc.)
- Tabelas de programas

**Instruções:**
```sql
-- 1. CREATE DATABASE futebolenergia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 2. USE futebolenergia;
-- 3. Executar este arquivo completo
```

**SET FOREIGN_KEY_CHECKS = 0** (linha 15)

**Classificação:** 60% CORE / 40% APP-SPECIFIC

---

## 📝 TABELAS APP-SPECIFIC (7 arquivos)

**Padrão:** Tabelas de canais sociais

**1. create_tbl_instagram.sql**
- Campos: data, seguidores, alcance, impressoes, etc.

**2. create_tbl_x.sql** (Twitter/X)
- Campos: seguidores, tweets, impressoes

**3. create_tbl_x_inscritos.sql**
- Relacionamento premium

**4. create_tbl_tiktok.sql**
- Campos: videos, visualizacoes, curtidas

**5. create_tbl_twitch.sql**
- Campos: lives, espectadores, horas

**6. create_tbl_app.sql**
- App mobile analytics

**7. create_tbl_website.sql**
- Website analytics

**Classificação:** 100% APP-SPECIFIC (Futebol Energia)

---

## 📚 _ARCHIVED (11 arquivos)

**Função:** Histórico de migrations antigas

**Exemplos:**
- `migrate-contents-to-pages.sql` - Migração sistema antigo
- `MIGRACAO_FOTO_URL_PARA_PATH.sql` - Ajuste de paths
- `SUPABASE_RLS_PALPITES.sql` - Row Level Security
- `OTIMIZACAO_MATERIALIZED_VIEWS.sql` - Performance

**Classificação:** Histórico (não usar)

---

## 🎯 PADRÕES IDENTIFICADOS

### 1. Multi-DB Architecture

**Abstraction Layer:**
```
DatabaseInterface (contrato)
    ↓
DatabaseFactory (factory)
    ↓
MySQLAdapter | SupabaseAdapter | NoneAdapter
```

**Zero vendor lock-in:** Trocar banco = mudar 1 linha no config

---

### 2. UUID Everywhere

**MySQL:**
```sql
id VARCHAR(36) PRIMARY KEY
```

**Supabase:**
```sql
id UUID PRIMARY KEY DEFAULT gen_random_uuid()
```

**Motivo:** Segurança (não expor IDs sequenciais)

---

### 3. Charset Enforcement

**MySQL (6 comandos):**
```sql
SET character_set_client = utf8mb4
SET character_set_connection = utf8mb4
SET character_set_results = utf8mb4
SET character_set_server = utf8mb4
SET collation_connection = utf8mb4_unicode_ci
SET collation_server = utf8mb4_unicode_ci
```

**Motivo:** Emojis, caracteres especiais, acentos

---

### 4. Prepared Statements Always

**Todos adapters:**
```php
$stmt = $this->pdo->prepare($sql);
$stmt->execute($params);
```

**Zero concatenação de SQL**

---

### 5. Foreign Keys com CASCADE

```sql
FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
```

**Motivo:** Limpeza automática (deletar member → deletar permissões)

---

### 6. Índices Estratégicos

**Sempre em:**
- `email` (login)
- `slug` (páginas)
- `ativo` (filtros)
- `created_at` (ordenação)
- Foreign keys (JOIN performance)

---

### 7. JSON para Config

**Tabelas com JSONB/JSON:**
- `modules.config` - Configuração módulo
- `settings.value` - Settings dinâmicos
- `page_cards.component_data` - Config componentes
- `security_tests.details` - Resultados testes

---

### 8. Timestamps Automáticos

**MySQL:**
```sql
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

**Supabase (via trigger):**
```sql
CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();
```

---

## 📊 ESTATÍSTICAS

**Total:** 48 arquivos

**Por categoria:**
- Schemas: 6 arquivos (1.336 linhas)
- Adapters: 5 arquivos (757 linhas)
- Migrations: 15 arquivos (~500 linhas)
- Utils: 3 arquivos (~100 linhas)
- Archived: 11 arquivos (histórico)
- App-specific: 7 tabelas (~200 linhas)
- Deploy: 1 arquivo (695 linhas)

**Total estimado:** ~3.600 linhas de SQL + PHP

**Classificação geral:**
- **CORE-AEGIS:** 75% (schemas, adapters, utils, migrations framework)
- **APP-SPECIFIC:** 25% (tabelas canais, deploy completo)

---

## 🔧 OPORTUNIDADES

### Pontos Fortes
✅ Multi-DB abstraction (MySQL + Supabase + None)
✅ Factory pattern bem implementado
✅ Prepared statements em 100% dos casos
✅ UUID security
✅ UTF8MB4 enforcement
✅ Foreign keys com CASCADE
✅ Índices estratégicos
✅ Schema minimal para sites simples
✅ RPC functions para Supabase (exec_sql, exec_query)
✅ Migrations organizadas
✅ Utils para reset/cleanup

### Melhorias Identificadas

1. **Connection Pooling:**
   - Comentário diz que foi desabilitado (linha 40 MySQLAdapter)
   - Investigar solução alternativa (connection reuse sem charset bug)

2. **Migration tracking:**
   - Criar tabela `migrations` (track executadas)
   - Comando `php artisan migrate` automático

3. **Seeding:**
   - Criar pasta `seeds/` com dados iniciais
   - Admin padrão, páginas exemplo

4. **Schema versioning:**
   - Integrar com `Version.php` do core
   - Auto-detect schema changes

5. **Rollback automation:**
   - Cada migration ter `.down.sql` correspondente
   - Comando `php artisan migrate:rollback`

6. **Database backup:**
   - Script para backup automático (mysqldump, pg_dump)
   - Integrar com admin/deploy

7. **Query logging:**
   - Log de queries lentas (> 1s)
   - Integração com DebugBar

8. **Transaction support:**
   - Wrapper para transactions (beginTransaction, commit, rollback)
   - Útil para operações complexas

---

## 📝 NOTA FINAL: 9/10

Sistema de database **extremamente profissional**, com abstraction layer completa, suporte multi-DB nativo e segurança rigorosa.

**Destaques:**
- Multi-DB sem vendor lock-in
- Prepared statements 100%
- UUID security
- UTF8MB4 enforcement (6 comandos)
- RPC functions para Supabase
- Schema minimal para sites simples
- Deploy schema unificado

**Único ponto negativo:**
- Connection pooling desabilitado (performance trade-off)
- Falta migration tracking automático
