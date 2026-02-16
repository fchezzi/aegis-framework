# 🔍 Guia Completo: Sistema de Filtros

Sistema flexível de filtros integrado com tabelas dinâmicas.

## ✅ O que foi implementado

### 1. Componente Filtros
- ✅ Dropdown dinâmico (busca dados do banco)
- ✅ Filtro de data (com presets: 7, 30, 90 dias)
- ✅ Configuração flexível (apenas dropdown, apenas data, ou ambos)
- ✅ JavaScript automático para integração
- ✅ Dark/Light mode completo
- ✅ Responsivo (mobile-first)

### 2. Integração com Tabelas
- ✅ Evento customizado `aegisFilterApplied`
- ✅ Recarga automática via AJAX
- ✅ Hierarquia: Canal (prioridade 1) → Data (prioridade 2)
- ✅ Compatível com busca e ordenação da tabela

### 3. Page Builder
- ✅ Componente visível no builder
- ✅ Campos dinâmicos (tabelas e colunas do banco)
- ✅ Campos dependentes (colunas carregam após selecionar tabela)
- ✅ Validação automática

---

## 🎯 Como usar no Page Builder

### Passo 1: Adicionar Filtro
1. Acesse `/admin/pages/builder`
2. Arraste o componente **🔍 Filtros**
3. Configure:
   - **Mostrar filtro dropdown?** → `yes` ou `no`
   - **Mostrar filtro de data?** → `yes` ou `no`
   - **Tabela** → Selecione do dropdown (ex: `canais_youtube`)
   - **Campo Valor** → Campo usado como valor (ex: `id`)
   - **Campo Label** → Campo exibido (ex: `nome`)

### Passo 2: Adicionar Tabela
1. Arraste o componente **📊 Tabelas**
2. Configure:
   - **Data Source** → `dynamic`
   - **Data Source URL** → `/api/videos-filtrados.php`
   - **Columns** → `["Título", "Canal", "Data", "Views"]`
   - **Sortable/Searchable/Pagination** → Ative conforme necessário

### Passo 3: Publicar
- Salve a página
- Os filtros e tabela já estão integrados automaticamente!

---

## 📋 Configurações Disponíveis

### Filtros

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `filter_group` | text | Grupo do filtro (default: "default") |
| `show_select` | select | Mostrar dropdown? (yes/no) |
| `select_label` | text | Label do dropdown |
| `table` | select | Tabela fonte (dinâmico do banco) |
| `value_field` | select | Campo valor (dinâmico da tabela) |
| `label_field` | select | Campo label (dinâmico da tabela) |
| `platform_filter` | select | Filtrar dropdown por plataforma (all/youtube/tiktok/instagram/facebook) |
| `show_date` | select | Mostrar filtro de data? (yes/no) |
| `date_label` | text | Label do filtro de data |
| `show_presets` | select | Mostrar atalhos de data? (yes/no) - Inclui: 7, 30, 90 dias, Este mês, Este ano |

### Tabelas

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `filter_group` | text | Grupo do filtro (default: "default") |
| `platform_filter` | select | Pré-filtrar por plataforma (all/youtube/tiktok/instagram/facebook) |
| `data_source` | select | `static` ou `dynamic` |
| `data_source_url` | text | URL da API (se dynamic) |
| `columns` | json | Array com nomes das colunas |
| `sortable` | select | Permitir ordenação? |
| `searchable` | select | Mostrar busca? |
| `pagination` | select | Mostrar paginação? |

---

## 🔧 Como criar seu próprio endpoint

```php
<?php
// /api/seu-endpoint.php

require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

header('Content-Type: application/json');

try {
    // PEGAR FILTROS
    $platform = $_GET['platform'] ?? null;   // Plataforma (fixo da tabela)
    $canalId = $_GET['select'] ?? null;      // Dropdown
    $dateStart = $_GET['date_start'] ?? null; // Data início
    $dateEnd = $_GET['date_end'] ?? null;     // Data fim

    // QUERY BASE
    $query = "SELECT col1, col2, col3 FROM tabela WHERE 1=1";
    $params = [];

    // FILTRO 0: Plataforma (PRÉ-FILTRO FIXO)
    if ($platform) {
        $query .= " AND plataforma = ?";
        $params[] = $platform;
    }

    // FILTRO 1: Canal (PRIORIDADE MÁXIMA)
    if ($canalId) {
        $query .= " AND canal_id = ?";
        $params[] = $canalId;
    }

    // FILTRO 2: Data (PRIORIDADE SECUNDÁRIA)
    if ($dateStart) {
        $query .= " AND data >= ?";
        $params[] = $dateStart;
    }

    if ($dateEnd) {
        $query .= " AND data <= ?";
        $params[] = $dateEnd;
    }

    // EXECUTAR
    $stmt = DB::prepare($query);
    $results = $stmt->execute($params);

    // FORMATAR (array de arrays)
    $rows = [];
    foreach ($results as $row) {
        $rows[] = [
            $row['col1'],
            $row['col2'],
            $row['col3']
        ];
    }

    // RETORNAR (apenas array)
    echo json_encode($rows);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

**Importante:**
- ✅ Retorne **array de arrays**: `[["val1", "val2"], ["val3", "val4"]]`
- ✅ Respeite a hierarquia: `platform` (fixo) → `select` (dropdown) → `date_start/date_end`
- ✅ Use `DB::prepare()` para segurança

---

## 🌐 Filtro de Plataforma

**Novidade:** Agora você pode configurar uma tabela para mostrar apenas dados de uma plataforma específica!

### Como funciona?

Quando você cria uma tabela no Page Builder, pode escolher qual plataforma mostrar:

- **All (Padrão)** - Mostra todas as plataformas
- **YouTube** - Só mostra conteúdo do YouTube
- **TikTok** - Só mostra conteúdo do TikTok
- **Instagram** - Só mostra conteúdo do Instagram
- **Facebook** - Só mostra conteúdo do Facebook

### Configuração

1. No Page Builder, ao adicionar componente **Tabelas**
2. Configure: **Filtrar por Plataforma** → Escolha a plataforma
3. Quando a tabela carregar dados, automaticamente enviará `?platform=youtube` (por exemplo)

### Exemplo Prático

```php
// Sua API recebe automaticamente o parâmetro
$platform = $_GET['platform'] ?? null; // Ex: "youtube"

// Use na query
if ($platform) {
    $query .= " AND plataforma = ?";
    $params[] = $platform;
}
```

**Vantagem:** Você pode ter múltiplas tabelas na mesma página, cada uma mostrando uma plataforma diferente!

---

## 🎯 Sistema de Grupos

**Novidade:** Agora você pode ter múltiplos filtros independentes na mesma página!

### Como funciona?

Cada **filtro** e **tabela/card** pode pertencer a um **grupo**. Filtros só afetam componentes do mesmo grupo.

```
Filtro (grupo: "videos") → Só afeta → Tabelas (grupo: "videos")
Filtro (grupo: "stats")  → Só afeta → Cards (grupo: "stats")
```

### Configuração

**No Filtro:**
- Campo: `filter_group`
- Valor: Nome do grupo (ex: "videos", "cards-1", "performance")
- Padrão: "default"

**Na Tabela/Card:**
- Campo: `filter_group`
- Valor: Mesmo nome do grupo do filtro
- Padrão: "default"

### Exemplo Prático

```php
// Filtro para vídeos recentes
Component::render('filtros', [
    'filter_group' => 'videos-recentes',
    'table' => 'canais_youtube'
]);

// Tabela que recebe esse filtro
Component::render('tabelas', [
    'filter_group' => 'videos-recentes',
    'data_source' => 'dynamic',
    'data_source_url' => '/api/videos.php'
]);

// Outro filtro independente
Component::render('filtros', [
    'filter_group' => 'estatisticas',
    'show_date' => 'yes'
]);

// Outro card independente
Component::render('tabelas', [
    'filter_group' => 'estatisticas',
    'data_source' => 'dynamic',
    'data_source_url' => '/api/stats.php'
]);
```

**Resultado:** Você tem 2 filtros na mesma página funcionando independentemente!

---

## 🧪 Páginas de Teste

| URL | Descrição |
|-----|-----------|
| `/exemplo-filtros-completo` | 4 exemplos de configuração de filtros |
| `/exemplo-integracao` | **Integração completa: Filtros + Tabelas funcionando** |
| `/exemplo-multiplos-grupos` | **3 grupos independentes na mesma página** 🔥 |
| `/exemplo-tabelas` | Exemplos de tabelas |

---

## 🎨 Hierarquia de Filtros

```
┌──────────────────────────────────────────┐
│  0️⃣ PLATAFORMA (Pré-filtro fixo)        │  ← CONFIGURADO NA TABELA
│     └─ YouTube / TikTok / Instagram / etc │
│                                          │
│  1️⃣ FILTRO DE CANAL (Dropdown)           │  ← PRIORIDADE MÁXIMA
│     └─ Filtra tudo por canal             │
│                                          │
│  2️⃣ FILTRO DE DATA (Date Range)          │  ← PRIORIDADE SECUNDÁRIA
│     └─ Refina com datas exatas          │
└──────────────────────────────────────────┘
```

**Exemplos:**
- **Só Plataforma:** Mostra todos conteúdos do YouTube (configurado na tabela)
- **Plataforma + Canal:** Mostra conteúdos do canal X no YouTube
- **Plataforma + Data:** Mostra conteúdos do YouTube entre 01/01 e 31/01
- **Plataforma + Canal + Data:** Mostra conteúdos do canal X no YouTube entre 01/01 e 31/01

### 📅 Presets de Data

Os presets facilitam a seleção rápida de períodos comuns:

- ✅ **Últimos 7 dias** - Últimos 7 dias até hoje
- ✅ **Últimos 30 dias** - Últimos 30 dias até hoje
- ✅ **Últimos 90 dias** - Últimos 90 dias até hoje
- ✅ **Este mês** - 01/MESATUAL/ANOATUAL até hoje
- ✅ **Este ano** - 01/01/ANOATUAL até hoje

Quando você clica em um preset, os campos de data são preenchidos automaticamente!

---

## 🚀 Próximos Passos

Agora você pode:
1. ✅ Usar filtros no Page Builder
2. ✅ Criar endpoints customizados
3. ✅ Combinar filtros como quiser
4. ✅ Usar em qualquer página construída

**Teste agora:** http://localhost:5757/aegis/exemplo-integracao
