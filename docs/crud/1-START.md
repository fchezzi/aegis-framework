# 🚀 INÍCIO - CRIAR CRUD AEGIS

**Você está aqui porque:** Vai criar um CRUD completo.

**Tempo estimado:** 30-60 minutos (dependendo da complexidade)

---

## 🎯 O QUE VOCÊ VAI CRIAR

Um CRUD (Create, Read, Update, Delete) completo com:

✅ **Segurança:** CSRF, Rate Limiting, UUID validation, Path traversal protection
✅ **Performance:** Paginação, SELECT específico, Imagens otimizadas
✅ **Escalabilidade:** Preparado para 100k+ registros
✅ **Auditoria:** Logs completos de todas operações
✅ **Frontend:** Display opcional (se necessário)

**Score esperado:** 90-100% na validação automática

---

## 📋 PASSO -2: COLETAR REQUISITOS

**Preciso de 6 informações antes de começar:**

### **1️⃣ Nome do CRUD**
Exemplo: "Banner Hero", "Produtos", "Categorias"

### **2️⃣ Recurso técnico**
Nome da classe e tabela.

**Exemplos:**
- "BannerHero" → Classe: `BannerHeroController`, Tabela: `tbl_banner_hero`
- "Product" → Classe: `ProductController`, Tabela: `tbl_product`
- "Category" → Classe: `CategoryController`, Tabela: `tbl_category`

### **3️⃣ Quem acessa?**
Quem pode gerenciar este recurso?

**Exemplos:**
- "Admin geral" (todos admins)
- "Módulo específico" (apenas módulo X)
- "Super admin apenas"

### **4️⃣ Quais campos?**
Liste todos os campos da tabela (além de id, created_at, updated_at que são padrão).

**Exemplos:**
- `titulo, subtitulo, imagem, cta_button, cta_link, order, ativo`
- `nome, descricao, preco, estoque, categoria_id, imagem, ativo`
- `nome, slug, parent_id, order, ativo`

### **5️⃣ Comportamentos especiais?**

Marque SIM ou NÃO para cada:

**a) Upload de arquivo?**
- [ ] SIM (especificar: imagem? PDF? qual campo?)
- [ ] NÃO

**b) Ordenação/ranking?**
- [ ] SIM (campo `order` para ordenar registros)
- [ ] NÃO

**c) Status ativo/inativo?**
- [ ] SIM (campo `ativo` para ativar/desativar)
- [ ] NÃO

**d) Relacionamentos?**
- [ ] SIM (especificar: pertence a quê? ex: categoria_id)
- [ ] NÃO

**e) Datas especiais?**
- [ ] SIM (especificar: publicado_em, expira_em, etc)
- [ ] NÃO

### **6️⃣ Vai ter display no frontend?**

**Este recurso será exibido para visitantes do site?**

**Exemplos de SIM:**
- "Sim, na página inicial" → Banners hero
- "Sim, na loja virtual" → Produtos
- "Sim, no blog" → Posts
- "Exibido no site" → Qualquer display público

**Exemplos de NÃO:**
- "Não, só gerenciamento admin" → Usuários, logs
- "Apenas admin" → Configurações
- "Interno" → Backups, relatórios

**Responda:** SIM ou NÃO (ou descreva onde será exibido)

---

## 🚩 DEFINIÇÃO AUTOMÁTICA DE FLAGS

**Com base nas suas respostas acima, vou definir 4 flags de controle:**

```python
$needs_upload = False              # Pergunta 5a: Upload?
$needs_ordering = False            # Pergunta 5b: Ordenação?
$needs_status = False              # Pergunta 5c: Status ativo/inativo?
$needs_frontend_display = False    # Pergunta 6: Display frontend?
```

### **Como as flags são definidas:**

**Flag 1: `$needs_upload`**
```
SE resposta 5a = "SIM":
    $needs_upload = True
    → EXECUTAREI: PASSO 4B (upload de arquivos) - OBRIGATÓRIO
```

**Flag 2: `$needs_ordering`**
```
SE resposta 5b = "SIM":
    $needs_ordering = True
    → ADICIONAREI: campo `order` INT na tabela
    → ADICIONAREI: ORDER BY `order` nas queries
```

**Flag 3: `$needs_status`**
```
SE resposta 5c = "SIM":
    $needs_status = True
    → ADICIONAREI: campo `ativo` TINYINT(1) na tabela
    → ADICIONAREI: WHERE ativo=1 nas queries frontend
```

**Flag 4: `$needs_frontend_display`**
```
SE resposta 6 contém:
   - "SIM" ou "Sim" ou "sim"
   - "frontend"
   - "home" ou "página" ou "site"
   - "público" ou "visitantes"
   - "loja" ou "blog"
ENTÃO:
    $needs_frontend_display = True
    → EXECUTAREI: PASSO 11B (frontend display) - OBRIGATÓRIO
    → CRIAREI: Controller frontend
    → CRIAREI: Partial reutilizável
    → CRIAREI: SASS dedicado
    → CRIAREI: Preview no admin
```

---

## ⚠️ REGRA CRÍTICA: "OPCIONAL" vs "OBRIGATÓRIO"

**Você precisa entender isto:**

No guia técnico (2-GUIDE.md), alguns passos estão marcados como "OPCIONAL".

**MAS:** Se a flag correspondente = True, o passo vira **OBRIGATÓRIO**.

**Exemplo:**

```
PASSO 11B: CRIAR DISPLAY FRONTEND (OPCIONAL)

SE $needs_frontend_display = True:
    → Este passo é OBRIGATÓRIO
    → Devo executar todos os 6 sub-passos

SE $needs_frontend_display = False:
    → Este passo é realmente opcional
    → Posso perguntar se quer criar mesmo assim
```

**Tradução:** "Opcional" significa "depende da flag", NÃO "pode pular sempre".

---

## ✅ APÓS COLETAR REQUISITOS

**Vou anunciar as flags definidas:**

```
📋 FLAGS DEFINIDAS (baseado nas suas respostas):

$needs_upload = True              ✅ Upload de imagem solicitado
$needs_ordering = True            ✅ Ordenação solicitada
$needs_status = True              ✅ Status ativo/inativo solicitado
$needs_frontend_display = True    ✅ Display frontend solicitado

Iniciando implementação conforme 2-GUIDE.md...
```

---

## 🚀 PRÓXIMO PASSO

Após definir as flags, vou:

1. Ler `/docs/crud/2-GUIDE.md` COMPLETO
2. Seguir TODOS os passos em ordem (-1 até 13)
3. Parar em CADA checkpoint obrigatório
4. Verificar flags antes de pular qualquer passo "opcional"
5. Executar validação automática ao final
6. Entregar CRUD completo com score 90%+

---

## 📊 CHECKLIST INICIAL

**Antes de prosseguir, confirmar:**

```
[ ] Respondi as 6 perguntas acima?
[ ] Entendi o sistema de 4 flags?
[ ] Entendi que "opcional" = verificar flag?
[ ] Pronto para seguir 2-GUIDE.md rigorosamente?
```

**SE TODOS ✅ → Vou para 2-GUIDE.md**

**SE ALGUM ❌ → Releia este arquivo**

---

**Criado:** 2026-02-14
**Versão:** 1.0
**Próximo:** `/docs/crud/2-GUIDE.md`
