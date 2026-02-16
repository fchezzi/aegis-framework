# Guia: Criar Data Sources

> Processo rápido para criar fontes de dados customizáveis para relatórios

---

## 📋 Quando Usar

Sempre que precisar filtrar dados de uma tabela com condições específicas (período, canal, etc) e usar em relatórios/dashboards.

---

## 🚀 Processo (Via SQL Direto)

### Passo 1: Gerar UUID

```bash
uuidgen | tr '[:upper:]' '[:lower:]'
```

### Passo 2: Verificar Dados Disponíveis (Opcional)

```bash
/Applications/MAMP/Library/bin/mysql -u root -proot futebolenergia -e "
SELECT canal_id, MIN(data) as primeira_data, MAX(data) as ultima_data, COUNT(*) as total
FROM [TABELA]
GROUP BY canal_id;"
```

### Passo 3: Inserir Data Source

```bash
/Applications/MAMP/Library/bin/mysql -u root -proot futebolenergia -e "
INSERT INTO report_data_sources (
    id,
    name,
    description,
    table_name,
    operation,
    column_name,
    conditions,
    created_at
) VALUES (
    '[UUID_GERADO]',
    '[NOME]',
    '[DESCRIÇÃO]',
    '[TABELA]',
    '[OPERAÇÃO]',
    '[COLUNA]',
    '[CONDITIONS_JSON]',
    NOW()
);"
```

### Passo 4: Validar Query

```bash
/Applications/MAMP/Library/bin/mysql -u root -proot futebolenergia -e "
SELECT [OPERACAO]([COLUNA]) as resultado
FROM [TABELA]
WHERE [CONDITIONS];"
```

---

## 📊 Exemplo Real: Seguidores Facebook Janeiro/2025

```bash
# 1. Gerar UUID
UUID=$(uuidgen | tr '[:upper:]' '[:lower:]')

# 2. Inserir
/Applications/MAMP/Library/bin/mysql -u root -proot futebolenergia -e "
INSERT INTO report_data_sources (
    id,
    name,
    description,
    table_name,
    operation,
    column_name,
    conditions,
    created_at
) VALUES (
    '$UUID',
    'fb - seguidores - energia97 - jan/25',
    'Total de seguidores do Energia 97 no Facebook em janeiro de 2025',
    'tbl_facebook',
    'SUM',
    'total_seguidores',
    '[{\"column\":\"data\",\"operator\":\"BETWEEN\",\"value\":[\"2025-01-01\",\"2025-01-31\"]},{\"column\":\"canal_id\",\"operator\":\"=\",\"value\":\"11\"}]',
    NOW()
);"

# 3. Testar
/Applications/MAMP/Library/bin/mysql -u root -proot futebolenergia -e "
SELECT SUM(total_seguidores) as resultado
FROM tbl_facebook
WHERE data BETWEEN '2025-01-01' AND '2025-01-31'
AND canal_id = 11;"
```

---

## ⚡ Processo Automatizado (Múltiplos Meses)

**Use quando precisar criar 12+ data sources de uma vez.**

### Template PHP para Geração em Massa

```php
<?php
function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$meses = [
    ['num' => '01', 'nome' => 'janeiro', 'abrev' => 'jan', 'ultimo_dia' => '31'],
    ['num' => '02', 'nome' => 'fevereiro', 'abrev' => 'fev', 'ultimo_dia' => '28'],
    // ... todos os 12 meses
];

$sql = "";

foreach ($meses as $mes) {
    $uuid = generateUUID();
    $nome = "[PREFIXO] - {$mes['abrev']}/25";
    $desc = "[DESCRIÇÃO] {$mes['nome']} de 2025";
    $inicio = "2025-{$mes['num']}-01";
    $fim = "2025-{$mes['num']}-{$mes['ultimo_dia']}";

    $conditions = json_encode([
        ['column' => 'data', 'operator' => 'BETWEEN', 'value' => [$inicio, $fim]],
        ['column' => 'canal_id', 'operator' => '=', 'value' => '[CANAL_ID]']
    ]);

    $sql .= "INSERT INTO report_data_sources VALUES (\n";
    $sql .= "    '{$uuid}',\n";
    $sql .= "    '{$nome}',\n";
    $sql .= "    '{$desc}',\n";
    $sql .= "    '[TABELA]',\n";
    $sql .= "    '[OPERACAO]',\n";
    $sql .= "    '[COLUNA]',\n";
    $sql .= "    '{$conditions}',\n";
    $sql .= "    NOW(),\n";
    $sql .= "    NULL\n";
    $sql .= ");\n\n";
}

echo $sql;
```

### Executar

```bash
# 1. Criar script
cat > /tmp/generate_ds.php << 'EOF'
[COLE O SCRIPT ACIMA ADAPTADO]
EOF

# 2. Gerar SQL
php /tmp/generate_ds.php > /tmp/datasources.sql

# 3. Executar
/Applications/MAMP/Library/bin/mysql -u root -proot futebolenergia < /tmp/datasources.sql

# 4. Validar
/Applications/MAMP/Library/bin/mysql -u root -proot futebolenergia -e "
SELECT COUNT(*) as total FROM report_data_sources WHERE name LIKE '[PREFIXO]%';"
```

### Exemplo Real: 24 meses de Facebook Seguidores

**Criar para 2 canais (10 e 11) de jan/25 a dez/26:**
1. Adaptar script PHP com loops para 2025 e 2026
2. Executar para canal 10
3. Executar para canal 11
4. Resultado: 48 data sources criadas em segundos

---

## 🔧 Operações Disponíveis

- **COUNT** - Contar registros
- **SUM** - Somar valores
- **AVG** - Média
- **MAX** - Valor máximo
- **MIN** - Valor mínimo

---

## 📝 Formato JSON das Condições

### Filtro por Período (Mês)

```json
[
  {
    "column": "data",
    "operator": "BETWEEN",
    "value": ["2025-01-01", "2025-01-31"]
  }
]
```

### Filtro por Valor Exato

```json
[
  {
    "column": "canal_id",
    "operator": "=",
    "value": "11"
  }
]
```

### Múltiplos Filtros (Combinar)

```json
[
  {
    "column": "data",
    "operator": "BETWEEN",
    "value": ["2025-01-01", "2025-01-31"]
  },
  {
    "column": "canal_id",
    "operator": "=",
    "value": "11"
  }
]
```

### Outros Operadores

- `=` - Igual
- `!=` - Diferente
- `>` - Maior que
- `<` - Menor que
- `>=` - Maior ou igual
- `<=` - Menor ou igual
- `BETWEEN` - Entre dois valores (array)
- `IN` - Em lista de valores (array)
- `NOT IN` - Não está em lista (array)
- `IS NULL` - É nulo
- `IS NOT NULL` - Não é nulo
- `LIKE` - Contém texto

---

## 📊 Tabelas Disponíveis

### tbl_facebook
**Colunas principais:**
- `canal_id` (int)
- `data` (date)
- `total_seguidores` (int)
- `visualizacoes` (int)
- `interacoes` (int)
- `ganhos` (decimal)

### tbl_youtube
**Colunas principais:**
- `canal_id` (int)
- `video_views` (int)
- `video_watchtime` (int)
- `video_likes` (int)
- `video_comments` (int)

### tbl_instagram
**Colunas principais:**
- `canal_id` (int)
- `data` (date)
- `seguidores_total` (int)
- `visualizacoes_total` (int)
- `interacoes_total` (int)

### tbl_x (Twitter)
**Colunas principais:**
- `canal_id` (int)
- `data` (date)
- `impressoes` (int)
- `engajamento` (int)

### tbl_tiktok
**Colunas principais:**
- `canal_id` (int)
- `data` (date)
- `visualizacoes_publicacoes` (int)
- `seguidores` (int)

---

## 🔍 IDs dos Canais

Para descobrir o `canal_id`:

```bash
/Applications/MAMP/Library/bin/mysql -u root -proot futebolenergia -e "
SELECT id, nome FROM canais ORDER BY nome;"
```

**Principais:**
- `10` - Energia em Campo
- `11` - Energia 97 (Rádio)
- `12` - Damas em Campo

---

## ⚠️ Notas Importantes

1. **JSON deve ter escape duplo:** `\"` nas queries SQL
2. **BETWEEN usa array:** `["valor1", "valor2"]`
3. **Datas no formato:** `YYYY-MM-DD`
4. **Validar sempre** a query antes de usar em produção
5. **NULL como resultado** significa sem dados no período

---

## 🎯 Template Rápido

```bash
UUID=$(uuidgen | tr '[:upper:]' '[:lower:]')

/Applications/MAMP/Library/bin/mysql -u root -proot futebolenergia -e "
INSERT INTO report_data_sources VALUES (
    '$UUID',
    '[NOME_CURTO]',
    '[DESCRIÇÃO_COMPLETA]',
    '[TABELA]',
    '[SUM|COUNT|AVG|MAX|MIN]',
    '[COLUNA]',
    '[JSON_CONDITIONS]',
    NOW(),
    NULL
);"
```

---

## 📂 Localização

Data sources ficam em:
- **Banco:** Tabela `report_data_sources`
- **Admin:** `/admin/data-sources`
- **Uso:** Relatórios e dashboards customizáveis

---

---

## 💬 Comandos Rápidos para o Assistente

Quando você precisar de data sources, use estes formatos:

### Uma data source única
```
"Criar data source: [tabela] - [operação] - [coluna] - [canal] - [mês]/[ano]"
```
**Exemplo:** `Criar data source: instagram - SUM - seguidores_total - damas em campo - fev/25`

### Múltiplas data sources (série mensal)
```
"Criar data sources em série: [tabela] - [operação] - [coluna] - [canal] - [período]"
```
**Exemplo:** `Criar data sources em série: tiktok - SUM - visualizacoes_publicacoes - energia97 - jan/25 a dez/26`

### Duplicar série para outro canal
```
"Duplicar data sources de [canal origem] para [canal destino] - mesma métrica"
```
**Exemplo:** `Duplicar data sources de energia97 para futebol energia - mesma métrica`

O assistente vai:
1. Gerar script PHP automatizado
2. Criar todos os INSERTs com UUIDs únicos
3. Executar no banco
4. Validar resultado

---

**Versão:** 1.1
**Data:** 2026-01-13
**Autor:** Tático ⚡
