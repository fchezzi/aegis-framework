# 🔄 Migração: Sistema Multi-Plataforma

## O que vai mudar?

### ANTES (estrutura antiga)
```
canais_youtube
├── id
├── nome
├── url
├── descricao
└── ativo
```

### DEPOIS (estrutura nova)
```
canais
├── id
├── nome
├── plataforma ← NOVO! (youtube/tiktok/instagram/facebook)
├── url
├── descricao
└── ativo

conteudos ← NOVA TABELA!
├── id
├── canal_id
├── titulo
├── tipo (video/short/reel/post)
├── data_publicacao
├── views
├── likes
└── ...
```

---

## 🚀 Como executar a migração

### Opção 1: Pelo Supabase Dashboard

1. Acesse: https://supabase.com/dashboard
2. Entre no seu projeto
3. Menu lateral: **SQL Editor**
4. Clique em **New query**
5. Cole o conteúdo de `.claude/migration-plataformas.sql`
6. Clique em **Run** (ou Ctrl+Enter)

### Opção 2: Por outro cliente SQL

1. Abra seu cliente SQL (phpMyAdmin, DBeaver, etc)
2. Conecte no banco de dados
3. Abra o arquivo `.claude/migration-plataformas.sql`
4. Execute o script completo

### Opção 3: Linha de comando (se tiver mysql-client)

```bash
mysql -h SEU_HOST -u SEU_USER -p SEU_DATABASE < .claude/migration-plataformas.sql
```

---

## ✅ O que o script faz?

1. **Cria tabela `canais`** (unificada para todas plataformas)
2. **Migra dados** de `canais_youtube` → `canais` (com `plataforma = 'youtube'`)
3. **Cria tabela `conteudos`** (para vídeos, posts, reels, etc)
4. **Mantém `canais_youtube`** intacta (você pode deletar depois)

---

## 🔍 Verificação

Depois de executar, rode estas queries para verificar:

```sql
-- Ver canais migrados
SELECT * FROM canais;

-- Contar por plataforma
SELECT plataforma, COUNT(*) as total
FROM canais
GROUP BY plataforma;
```

Você deve ver todos os seus canais com `plataforma = 'youtube'`.

---

## 📝 Próximos passos

Após migração bem-sucedida:

1. ✅ Testar filtros no Page Builder
2. ✅ Adicionar canais de outras plataformas (TikTok, Instagram, etc)
3. ✅ Criar endpoint `/api/conteudos.php` para popular a tabela `conteudos`
4. ✅ (Opcional) Remover `canais_youtube` antiga

---

## ⚠️ Importante

- **Backup:** O script não deleta nada! `canais_youtube` continua intacta
- **Seguro:** Só migra dados que ainda não existem em `canais` (evita duplicatas)
- **Reversível:** Se der problema, é só deletar a tabela `canais` e recomeçar

---

## 🆘 Problemas?

**Erro: "Table 'canais' already exists"**
- Normal se executar 2x. O script é idempotente (pode rodar várias vezes sem problema)

**Erro: "Duplicate entry for key 'PRIMARY'"**
- Significa que os dados já foram migrados. Ignore este erro.

**Dados não aparecem:**
- Verifique: `SELECT * FROM canais WHERE plataforma = 'youtube';`
- Se vazio, rode novamente o `INSERT INTO canais...`
