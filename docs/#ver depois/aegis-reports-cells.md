# AEGIS Reports - Mapeamento de Células

Documentação completa do processo de mapeamento de células em relatórios Excel com fontes de dados dinâmicas.

---

## Estrutura do Sistema

### Tabelas Envolvidas

**1. `report_templates`**
- Armazena templates de relatórios Excel
- Campos principais: `id`, `name`, `file_path`, `visible`

**2. `report_cells`**
- Mapeia células do Excel para fontes de dados
- Campos: `id`, `template_id`, `sheet_name`, `cell_ref`, `data_source_key`, `created_at`
- Exemplo: Célula `C38` na aba `2025` → fonte `custom_views-crt-jan25`

**3. `report_data_sources`**
- Define fontes de dados (queries dinâmicas)
- Campos: `id`, `name`, `description`, `table_name`, `operation`, `column_name`, `conditions`
- Tipos de ID:
  - **Slug**: `views-crt-jan25` (padrão simples)
  - **UUID**: `0fae43f1-a16a-4a88-b8b1-01b9f5ebb0d5` (gerado automaticamente)

---

## Padrão de Nomenclatura

### Fontes de Dados (data_source_key)

**Sempre usar prefixo `custom_`:**
```
custom_views-crt-jan25
custom_website-visitantes-97fm-fev26
custom_0fae43f1-a16a-4a88-b8b1-01b9f5ebb0d5
```

### Padrão de Mês
- jan, fev, mar, abr, mai, jun, jul, ago, set, out, nov, dez

### Padrão de Ano
- `/25` para 2025
- `/26` para 2026

---

## Processo Manual: Adicionar Células Individuais

### 1. Verificar se a célula já existe

```sql
SELECT * FROM report_cells
WHERE template_id = 'energia97-2025'
  AND sheet_name = '2025'
  AND cell_ref = 'C38';
```

### 2. Buscar a fonte de dados disponível

```sql
SELECT id, name FROM report_data_sources
WHERE name LIKE '%cortes%jan/25%';
```

### 3. Inserir a célula

```sql
INSERT INTO report_cells (id, template_id, sheet_name, cell_ref, data_source_key)
VALUES (
  UUID(),
  'energia97-2025',
  '2025',
  'C38',
  'custom_views-crt-jan25'
);
```

---

## Processo em Lote: Preencher Meses Completos

### Cenário: Completar uma linha (12 meses)

**Exemplo:** Linha 38 (visualizações cortes) de Jan a Dez

#### Passo 1: Verificar células existentes

```sql
SELECT cell_ref, data_source_key
FROM report_cells
WHERE template_id = 'energia97-2025'
  AND sheet_name = '2025'
  AND cell_ref LIKE '%38'
ORDER BY cell_ref;
```

#### Passo 2: Verificar fontes disponíveis

```sql
SELECT id, name FROM report_data_sources
WHERE name LIKE 'yt - visualizações - cortes - %/25'
ORDER BY name;
```

#### Passo 3: Inserir células faltantes

```sql
INSERT INTO report_cells (id, template_id, sheet_name, cell_ref, data_source_key) VALUES
(UUID(), 'energia97-2025', '2025', 'I38', 'custom_views-crt-jul25'),
(UUID(), 'energia97-2025', '2025', 'J38', 'custom_views-crt-ago25'),
(UUID(), 'energia97-2025', '2025', 'K38', 'custom_views-crt-set25'),
(UUID(), 'energia97-2025', '2025', 'L38', 'custom_views-crt-out25'),
(UUID(), 'energia97-2025', '2025', 'M38', 'custom_views-crt-nov25'),
(UUID(), 'energia97-2025', '2025', 'N38', 'custom_views-crt-dez25');
```

---

## Processo Avançado: Duplicar Aba Completa (2025 → 2026)

### Desafio

Copiar todas as células de uma aba (2025) para outra (2026), trocando as fontes de dados corretamente.

**Problema:** Fontes com UUID têm IDs diferentes entre anos.

### Solução: JOIN por NAME

#### Passo 1: Validar quantas células podem ser criadas

```sql
SELECT
  COUNT(*) as total_com_fonte_2026_disponivel
FROM report_cells c25
INNER JOIN report_data_sources ds25 ON ds25.id = REPLACE(c25.data_source_key, 'custom_', '')
INNER JOIN report_data_sources ds26 ON ds26.name = REPLACE(ds25.name, '/25', '/26')
WHERE c25.template_id = 'energia97-2025'
  AND c25.sheet_name = '2025'
  AND c25.cell_ref NOT IN (
    SELECT cell_ref FROM report_cells
    WHERE template_id = 'energia97-2025' AND sheet_name = '2026'
  );
```

#### Passo 2: Preview do mapeamento (10 exemplos)

```sql
SELECT
  c25.cell_ref as celula,
  c25.data_source_key as fonte_2025,
  CONCAT('custom_', ds26.id) as fonte_2026,
  ds25.name as nome_fonte_2025,
  ds26.name as nome_fonte_2026
FROM report_cells c25
INNER JOIN report_data_sources ds25 ON ds25.id = REPLACE(c25.data_source_key, 'custom_', '')
INNER JOIN report_data_sources ds26 ON ds26.name = REPLACE(ds25.name, '/25', '/26')
WHERE c25.template_id = 'energia97-2025'
  AND c25.sheet_name = '2025'
  AND c25.cell_ref NOT IN (
    SELECT cell_ref FROM report_cells
    WHERE template_id = 'energia97-2025' AND sheet_name = '2026'
  )
ORDER BY c25.cell_ref
LIMIT 10;
```

#### Passo 3: Executar INSERT completo

```sql
INSERT INTO report_cells (id, template_id, sheet_name, cell_ref, data_source_key)
SELECT
  UUID(),
  'energia97-2025',
  '2026',
  c25.cell_ref,
  CONCAT('custom_', ds26.id)
FROM report_cells c25
INNER JOIN report_data_sources ds25 ON ds25.id = REPLACE(c25.data_source_key, 'custom_', '')
INNER JOIN report_data_sources ds26 ON ds26.name = REPLACE(ds25.name, '/25', '/26')
WHERE c25.template_id = 'energia97-2025'
  AND c25.sheet_name = '2025'
  AND c25.cell_ref NOT IN (
    SELECT cell_ref FROM report_cells
    WHERE template_id = 'energia97-2025' AND sheet_name = '2026'
  );
```

#### Passo 4: Validar resultado

```sql
-- Contar células por aba
SELECT
  sheet_name as aba,
  COUNT(*) as total_celulas
FROM report_cells
WHERE template_id = 'energia97-2025'
GROUP BY sheet_name;

-- Validar integridade (todas células têm fontes válidas?)
SELECT
  COUNT(*) as total_celulas_2026,
  SUM(CASE WHEN ds.id IS NOT NULL THEN 1 ELSE 0 END) as com_fonte_valida,
  SUM(CASE WHEN ds.id IS NULL THEN 1 ELSE 0 END) as sem_fonte
FROM report_cells c
LEFT JOIN report_data_sources ds ON ds.id = REPLACE(c.data_source_key, 'custom_', '')
WHERE c.template_id = 'energia97-2025'
  AND c.sheet_name = '2026';
```

---

## Backup e Segurança

### Sempre fazer backup ANTES de operações em lote

```bash
# Criar backup
/Applications/MAMP/Library/bin/mysqldump -h 127.0.0.1 -P 8889 -u root -proot futebolenergia > backup-$(date +%Y%m%d-%H%M%S).sql

# Testar backup
mysql -h 127.0.0.1 -P 8889 -u root -proot -e "CREATE DATABASE futebolenergia_teste;"
mysql -h 127.0.0.1 -P 8889 -u root -proot futebolenergia_teste < backup-XXXXXXXXX.sql

# Validar
mysql -h 127.0.0.1 -P 8889 -u root -proot -e "
  SELECT COUNT(*) FROM futebolenergia.report_cells;
  SELECT COUNT(*) FROM futebolenergia_teste.report_cells;
"

# Deletar teste
mysql -h 127.0.0.1 -P 8889 -u root -proot -e "DROP DATABASE futebolenergia_teste;"
```

### Restaurar backup em caso de erro

```bash
/Applications/MAMP/Library/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot futebolenergia < backup-XXXXXXXXX.sql
```

---

## Interface Web: Filtro de Células

### Funcionalidade

Filtro em tempo real na página de edição de relatórios para localizar células rapidamente.

**Arquivo:** `/admin/views/reports/edit.php`

**Campo de busca:** Filtra por aba, célula ou nome da fonte de dados.

**Uso:**
- Digite "38" para ver todas as células da linha 38
- Digite "2026" para ver células da aba 2026
- Digite "cortes" para ver células de visualizações de cortes

---

## Casos de Uso Comuns

### 1. Adicionar nova métrica mensal (12 células)

**Cenário:** Adicionar linha 50 com "Downloads App" para 2025

```sql
-- 1. Criar fontes de dados (se não existirem)
-- Fazer via interface admin em /admin/data-sources/create

-- 2. Verificar IDs das fontes
SELECT id, name FROM report_data_sources
WHERE name LIKE 'app - downloads%2025%'
ORDER BY name;

-- 3. Inserir células
INSERT INTO report_cells (id, template_id, sheet_name, cell_ref, data_source_key) VALUES
(UUID(), 'energia97-2025', '2025', 'C50', 'custom_app-downloads-jan25'),
(UUID(), 'energia97-2025', '2025', 'D50', 'custom_app-downloads-fev25'),
-- ... continuar para todos os meses
(UUID(), 'energia97-2025', '2025', 'N50', 'custom_app-downloads-dez25');
```

### 2. Adicionar nova aba (ano novo)

**Cenário:** Criar aba 2027 com base em 2026

```sql
-- Use o processo avançado (JOIN por NAME) descrito acima
-- Trocar REPLACE(ds25.name, '/25', '/26') por REPLACE(ds26.name, '/26', '/27')
```

### 3. Corrigir célula com fonte errada

```sql
-- 1. Verificar célula atual
SELECT * FROM report_cells
WHERE template_id = 'energia97-2025'
  AND sheet_name = '2025'
  AND cell_ref = 'C38';

-- 2. Atualizar
UPDATE report_cells
SET data_source_key = 'custom_views-crt-jan25'
WHERE template_id = 'energia97-2025'
  AND sheet_name = '2025'
  AND cell_ref = 'C38';
```

---

## Troubleshooting

### Problema: Células não aparecem selecionadas na interface

**Causa:** Falta prefixo `custom_` no `data_source_key`

**Solução:**
```sql
UPDATE report_cells
SET data_source_key = CONCAT('custom_', data_source_key)
WHERE data_source_key NOT LIKE 'custom_%';
```

### Problema: Fonte de dados não encontrada

**Validar:**
```sql
SELECT c.cell_ref, c.data_source_key, ds.name
FROM report_cells c
LEFT JOIN report_data_sources ds ON ds.id = REPLACE(c.data_source_key, 'custom_', '')
WHERE c.template_id = 'energia97-2025'
  AND ds.id IS NULL;
```

### Problema: Duplicação acidental

**Verificar duplicatas:**
```sql
SELECT cell_ref, sheet_name, COUNT(*) as qtd
FROM report_cells
WHERE template_id = 'energia97-2025'
GROUP BY cell_ref, sheet_name
HAVING COUNT(*) > 1;
```

**Remover duplicatas (manter apenas a mais recente):**
```sql
DELETE c1 FROM report_cells c1
INNER JOIN report_cells c2
WHERE c1.template_id = c2.template_id
  AND c1.sheet_name = c2.sheet_name
  AND c1.cell_ref = c2.cell_ref
  AND c1.created_at < c2.created_at;
```

---

## Checklist: Antes de Executar Operações em Lote

- [ ] Fazer backup completo do banco
- [ ] Testar restauração do backup
- [ ] Validar preview do INSERT (10 exemplos)
- [ ] Confirmar que todas as fontes de destino existem
- [ ] Executar INSERT
- [ ] Validar contagem de células criadas
- [ ] Validar integridade (fontes válidas)
- [ ] Fazer novo backup pós-operação
- [ ] Testar relatório na interface web

---

## Histórico de Mudanças

### 2026-01-24

#### Sessão 1: Duplicação completa aba 2025 → 2026
- **Operação:** Duplicação automática de todas células existentes
  - Células criadas: 360 em cada aba (720 total)
  - Método: JOIN por NAME para resolver UUIDs diferentes
  - Resultado: 100% das células com fontes válidas
  - Backups: `backup-20260124-095139.sql`, `backup-20260124-100703.sql`, `backup-20260124-103108.sql`, `backup-20260124-104900.sql`

#### Sessão 2: Mapeamento individual de linhas (YouTube, Website, Redes Sociais)
- **Operação:** Criação de 17 linhas completas (12 meses × 2 anos = 24 células cada)
  - **Linha 38:** yt - visualizações - cortes (24 células)
  - **Linha 44:** website - visitantes - 97fm (24 células)
  - **Linha 47:** website - visitantes - futebolenergia97 (24 células)
  - **Linha 53:** fb - seguidores - energia97 (24 células)
  - **Linha 54:** fb - visualizações - energia97 (24 células)
  - **Linha 57:** fb - seguidores - futebol energia (24 células)
  - **Linha 58:** fb - visualizações - futebol energia (24 células)
  - **Linha 64:** in - seguidores - energia97 (24 células)
  - **Linha 65:** in - visualizações - energia97 (24 células)
  - **Linha 68:** in - seguidores - futebolenergia97 (24 células)
  - **Linha 69:** in - visualizações - futebolenergia97 (24 células)
  - **Linha 72:** in - seguidores - energianaveia (24 células)
  - **Linha 73:** in - visualizações - energianaveia (24 células)
  - **Linha 79:** x - inscritos - energia97 (24 células)
  - **Linha 80:** x - visualizações - energia97 (24 células)
  - **Linha 83:** x - inscritos - futenergia97 (24 células)
  - **Linha 84:** x - visualizações - futenergia97 (24 células)
  - **Total:** 408 células criadas (17 linhas × 24 células)
  - **Método:** INSERT direto com mapeamento de meses via CASE
  - **Resultado:** 100% das células com fontes válidas
  - **Backups:** `backup-20260124-110603.sql`, `backup-20260124-113232.sql`

#### Resultado Final do Dia
- **Total de células:** 1.056 (528 em 2025 + 528 em 2026)
- **Células criadas hoje:** 1.056 (partiu de 332 para 1.056)
- **Taxa de sucesso:** 100% (0 erros)
- **Filtro de busca:** Implementado na interface de edição
- **Documentação:** Criada (`aegis-reports-cells.md`)

---

## Referências

- **Arquivo principal:** `admin/views/reports/edit.php`
- **Controller:** `admin/controllers/ReportController.php`
- **Query Builder:** `core/ReportQueryBuilder.php`
- **Migrations:** `database/migrations/2026_01_11_create_report_tables.sql`
- **Documentação AEGIS:** `docs/aegis-database.md`
