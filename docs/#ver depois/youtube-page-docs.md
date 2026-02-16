# 📺 Documentação: Página YouTube

> **Atualizado:** 2025-12-11 | **Versão:** 9.0.4

---

## 📋 VISÃO GERAL

Página dinâmica que exibe dados de vídeos do YouTube sincronizados via n8n.

**URL:** `/youtube`
**Autenticação:** Pública (adaptativa)
**Componentes:** Filtros + Tabelas (Page Builder)

---

## 🗂️ ESTRUTURA DE ARQUIVOS

```
frontend/pages/youtube.php        # Template da página
api/youtube-data.php              # Endpoint de dados (com filtros)
api/sync-youtube.php              # Sincronização com n8n
database/mysql-schema.sql         # Schema com tbl_youtube
_config.php                       # AEGIS_API_TOKEN configurado aqui
```

---

## 🔐 CONFIGURAÇÃO CRÍTICA: Token de API

### ⚠️ IMPORTANTE

O token de autenticação para `sync-youtube.php` está configurado em `_config.php`:

```php
// API Security Token (for sync endpoints like sync-youtube.php)
// ⚠️ IMPORTANTE: Altere este token em produção! Use um token único e complexo.
define('AEGIS_API_TOKEN', 'KBHhyVˆ&gvbt5F$d');
```

### ✅ Checklist de Deploy

Antes de colocar em produção:

1. **Altere o token** em `_config.php`
   - Use um gerador: https://www.uuidgenerator.net/
   - Mínimo 32 caracteres, alfanumérico + símbolos

2. **Atualize o n8n** com o novo token
   - Workflow: Editar nó HTTP Request
   - Header: `Authorization: Bearer SEU_NOVO_TOKEN`
   - Ou JSON body: `"token": "SEU_NOVO_TOKEN"`

3. **Teste o endpoint**
   ```bash
   curl -X POST http://seusite.com/api/sync-youtube.php \
     -H "Content-Type: application/json" \
     -d '{"token":"SEU_NOVO_TOKEN","data":[...]}'
   ```

### 🚨 Segurança

- **NUNCA** versione `_config.php` com token real
- Use `.gitignore` para proteger: `_config.php`
- Crie `_config.example.php` versionável com token fake

---

## 🗄️ BANCO DE DADOS

### Tabela: `tbl_youtube`

**Localização:** `database/mysql-schema.sql` (linhas 214-268)

**Estrutura:**
- **25+ colunas** de métricas
- **6 índices** otimizados
- **UUID** como primary key
- **video_id** único (previne duplicatas)

**Campos importantes:**
- `video_id` → ID único do YouTube
- `video_views`, `video_likes`, `video_comments` → Métricas
- `encerrado` → Flag: se 1, vídeo NÃO é atualizado no sync
- `created_at`, `updated_at` → Auditoria

---

## 🔄 SINCRONIZAÇÃO (n8n → MySQL)

### Endpoint: `/api/sync-youtube.php`

**Método:** POST
**Content-Type:** application/json

**Request:**
```json
{
  "token": "SEU_TOKEN_AQUI",
  "data": [
    {
      "video_id": "dQw4w9WgXcQ",
      "video_title": "Never Gonna Give You Up",
      "video_views": 1234567890,
      "video_published": "2009-10-25",
      ...
    }
  ]
}
```

**Response (sucesso):**
```json
{
  "success": true,
  "stats": {
    "inserted": 5,
    "updated": 10,
    "skipped": 2,
    "errors": 0,
    "total": 17
  }
}
```

### Lógica de Processamento

1. **Valida token** → Rejeita se inválido
2. Para cada vídeo:
   - **Valida data** (formato + checkdate)
   - **Busca no banco** por `video_id`
   - Se existe:
     - **Verifica flag `encerrado`** → Se 1, pula
     - Se 0 → **UPDATE**
   - Se não existe → **INSERT** com UUID novo
3. Retorna estatísticas

### 🛡️ Proteções

- Token obrigatório
- Validação de JSON
- Validação de data (formato + checkdate)
- Limite implícito (array size)
- Flag `encerrado` previne sobrescrita

---

## 🎨 COMPONENTES PAGE BUILDER

### Componente: Filtros

**Campos principais:**
- `filter_group` → Nome do grupo (ex: "videos")
- `show_select` → Exibir dropdown? (yes/no)
- `table` → Tabela fonte (ex: canais_youtube)
- `show_date` → Exibir filtro de data? (yes/no)

**Evento:** Dispara `aegisFilterApplied` quando aplicado

### Componente: Tabelas

**Campos principais:**
- `filter_group` → Mesmo grupo do filtro
- `data_source` → "dynamic"
- `data_source_url` → "/api/youtube-data.php"
- `pagination` → "yes" (recomendado)

**Integração:** Escuta `aegisFilterApplied` e recarrega dados

---

## 📊 API DE DADOS: `/api/youtube-data.php`

**Método:** GET
**Parâmetros:**
- `select` → Filtro dropdown (ex: nome do canal)
- `date_start` → Data início (YYYY-MM-DD)
- `date_end` → Data fim (YYYY-MM-DD)

**Exemplo:**
```
/api/youtube-data.php?select=Energia97&date_start=2025-01-01&date_end=2025-12-31
```

**Response:**
```json
[
  ["Título do Vídeo", "Energia 97", "2025-12-01", "1.234.567", "https://..."],
  ["Outro Vídeo", "Energia 97", "2025-12-02", "987.654", "https://..."]
]
```

**Limites:**
- Máximo 1000 registros por request
- Ordenação: `data_publicacao DESC`

---

## 🔍 AUTENTICAÇÃO DA PÁGINA

**Status:** Pública com conteúdo adaptativo

```php
// frontend/pages/youtube.php (linha 3)
$user = Auth::user() ?? MemberAuth::member() ?? null;
```

**Comportamento:**
- Se usuário logado → `$user` preenchido (pode personalizar conteúdo)
- Se não logado → `$user = null` (página ainda carrega)

**NÃO HÁ** `Auth::require()` ou `MemberAuth::require()` → Página é pública.

---

## 🗑️ ARQUIVOS ARQUIVADOS

Scripts temporários movidos para `_archived_scripts/`:

- `create-tbl-youtube.php` → Substituído pelo schema oficial
- `add-columns-youtube.php` → Colunas já no schema
- `import-csv.php` → Substituído por sync-youtube.php
- `check-youtube-table.php` → Debug temporário
- Outros 6 scripts de debug/teste

**Pode deletar?** Sim, se:
- Banco funciona corretamente
- Página `/youtube` carrega
- Sync com n8n funciona

---

## ✅ CORREÇÕES APLICADAS (2025-12-11)

### 1. Token de API ✅
- **Antes:** Hardcoded em `sync-youtube.php`
- **Depois:** Configurado em `_config.php` (AEGIS_API_TOKEN)
- **Segurança:** Validação obrigatória, erro se não configurado

### 2. Schema MySQL ✅
- **Antes:** Tabela não estava no schema oficial
- **Depois:** Adicionada em `mysql-schema.sql` (linhas 214-268)
- **Deploy:** Novos ambientes criam tabela automaticamente

### 3. Script de Debug ✅
- **Antes:** 48 linhas de console.log na página
- **Depois:** Removido (produção limpa)

### 4. Arquivos Temporários ✅
- **Antes:** 11 scripts PHP no root
- **Depois:** Movidos para `_archived_scripts/` com README

### 5. Validação de Data ✅
- **Antes:** Conversão sem validação
- **Depois:** `checkdate()` + validação de formato
- **Proteção:** Rejeita datas inválidas (ex: 2025-13-40)

### 6. Warning PHP 8.5 ✅
- **Antes:** `PDO::MYSQL_ATTR_INIT_COMMAND` (deprecated)
- **Depois:** Compatibilidade retroativa (8.5+ e versões anteriores)

---

## 🚀 TROUBLESHOOTING

### Erro: "Token inválido ou ausente"
- Verifique `_config.php` (AEGIS_API_TOKEN definido?)
- Verifique n8n (token correto no request?)

### Erro: "Data inválida"
- n8n está enviando data em formato errado?
- Esperado: YYYY-MM-DD ou string parseable por `strtotime()`

### Página não carrega dados
- Verifique `api/youtube-data.php` funciona diretamente
- Verifique filtros têm mesmo `filter_group` da tabela
- Console do browser: erros de JavaScript?

### Sync não atualiza vídeos
- Verifique flag `encerrado = 1` no banco
- Proteção: vídeos marcados como "encerrados" não são atualizados

---

## 📞 SUPORTE

**Documentação completa:** `.claude/`
**Comando inicial:** `/aegis`
**Guia de filtros:** `.claude/filtros-guia.md`

---

**Última revisão:** Guardião (2025-12-11)
**Versão AEGIS:** 9.0.4
