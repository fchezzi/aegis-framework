# 📊 Tabela youtube_extra - Documentação

## 📋 Resumo

**Tabela:** `youtube_extra`
**Propósito:** Armazenar métricas diárias de canais do YouTube
**Criada:** 2025-12-12
**Tipo:** Dados complementares (não sync automático)

---

## 🎯 Objetivo

Esta tabela armazena **métricas de CANAIS** (não vídeos), diferente da `tbl_youtube` que armazena métricas de vídeos individuais.

**Diferença:**
- `tbl_youtube` → Métricas de **VÍDEOS** (video_id, views, likes, etc)
- `youtube_extra` → Métricas de **CANAIS** (canal_id, inscritos, espectadores únicos)

---

## 📊 Estrutura

### MySQL
```sql
CREATE TABLE youtube_extra (
    id VARCHAR(36) PRIMARY KEY,
    canal_id VARCHAR(36) NOT NULL,
    data DATE NOT NULL,
    inscritos INT DEFAULT 0,
    espectadores_unicos INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (canal_id) REFERENCES canais(id) ON DELETE CASCADE,
    UNIQUE KEY unique_canal_data (canal_id, data)
);
```

### Supabase (PostgreSQL)
```sql
CREATE TABLE youtube_extra (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    canal_id UUID NOT NULL,
    data DATE NOT NULL,
    inscritos INTEGER DEFAULT 0,
    espectadores_unicos INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_youtube_extra_canal FOREIGN KEY (canal_id) REFERENCES canais(id) ON DELETE CASCADE,
    CONSTRAINT unique_canal_data UNIQUE (canal_id, data)
);
```

---

## 📥 Fonte de Dados

**Importação manual via CSV:**
- Interface: `/admin/import-csv.php`
- API: `/admin/api/import-csv.php`
- Função: `importYoutubeExtra()`

**Validações aplicadas:**
- `canal_id` obrigatório (deve existir na tabela `canais`)
- `data` obrigatória (formato YYYY-MM-DD)
- Duplicatas são atualizadas (UPDATE ao invés de INSERT)

---

## 🔗 Relacionamentos

```
youtube_extra (métricas de canal)
    ↓ FK
canais (lista de canais)
    ↓ 1:N
tbl_youtube (vídeos do canal)
```

**Query exemplo (unificar dados):**
```sql
SELECT
    c.nome as canal,
    ye.data,
    ye.inscritos,
    ye.espectadores_unicos,
    COUNT(v.id) as total_videos,
    SUM(v.video_views) as total_views
FROM youtube_extra ye
LEFT JOIN canais c ON c.id = ye.canal_id
LEFT JOIN tbl_youtube v ON v.video_show = c.nome
    AND DATE(v.video_published) = ye.data
GROUP BY ye.id, c.nome, ye.data, ye.inscritos, ye.espectadores_unicos;
```

---

## 📁 Arquivos Relacionados

**Migrations:**
- `database/migrations/add_youtube_extra_table.sql` (MySQL)
- `database/migrations/add_youtube_extra_table_supabase.sql` (Supabase)
- `database/migrations/rollback_youtube_extra_table.sql` (Rollback)

**Schemas:**
- `database/mysql-schema.sql` (linhas finais)
- `database/supabase-schema.sql` (linhas finais)

**Código:**
- `admin/import-csv.php` (interface)
- `admin/api/import-csv.php` (processamento)
- `admin/api/process-csv.php` (parsing CSV)

---

## 🚀 Como Usar

### 1. Executar Migration (Instalações Existentes)

**MySQL:**
```bash
mysql -u root -p aegis_test < database/migrations/add_youtube_extra_table.sql
```

**Supabase:**
1. Acesse dashboard Supabase → SQL Editor
2. Execute o conteúdo de `add_youtube_extra_table_supabase.sql`

### 2. Importar Dados via CSV

**Formato do CSV:**
```csv
canal_id,data,inscritos,espectadores_unicos
7a0ac346-...,2025-12-01,150000,45000
7a0ac346-...,2025-12-02,151000,46000
```

**Passos:**
1. Acesse `/admin/import-csv.php`
2. Selecione "youtube_extra (Métricas de Canais)"
3. Faça upload do CSV
4. Revise preview
5. Confirme importação

---

## ⚠️ Importante

**Unique Constraint:**
- Não pode ter duplicata de `canal_id + data`
- Se importar linha duplicada → **UPDATE** ao invés de INSERT

**Foreign Key:**
- `canal_id` DEVE existir na tabela `canais`
- Se deletar canal → deleta todas métricas (CASCADE)

**Validações:**
- Data formato YYYY-MM-DD obrigatório
- Canal deve existir
- Valores numéricos default 0

---

## 🔄 Rollback

**Se precisar remover a tabela:**

```sql
-- ATENÇÃO: Deleta TODOS os dados!
DROP TABLE IF EXISTS youtube_extra;
```

Ou execute:
```bash
mysql -u root -p aegis_test < database/migrations/rollback_youtube_extra_table.sql
```

---

## 📊 Status Atual

- ✅ Schema MySQL documentado
- ✅ Schema Supabase documentado
- ✅ Migrations criadas
- ✅ Rollback criado
- ✅ Importação CSV funcionando
- ⚠️ Dados **não são exibidos** em nenhuma página ainda
- 💡 Oportunidade: Criar dashboard de métricas de canais

---

## 🎯 Próximos Passos Sugeridos

1. ✅ **Documentação completa** (FEITO!)
2. 📊 Criar página `/admin/canais/metricas` para visualizar dados
3. 📈 Criar gráficos de evolução (inscritos ao longo do tempo)
4. 🔄 Integrar com sistema de filtros existente
5. 📱 Adicionar ao dashboard principal (cards com métricas)

---

**Data:** 2025-12-12
**Versão AEGIS:** 11.0.0
**Autor:** Guardião 🛡️
