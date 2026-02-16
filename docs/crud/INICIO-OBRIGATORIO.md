# 🚀 INÍCIO OBRIGATÓRIO - CRIAR CRUD AEGIS

**📍 VOCÊ ESTÁ AQUI PORQUE:** O usuário pediu para criar um CRUD.

**⚠️ ESTE ARQUIVO DEVE SER LIDO PRIMEIRO, ANTES DE QUALQUER CÓDIGO.**

---

## 🎯 SUA MISSÃO

Criar um CRUD completo, seguro, performático e escalável seguindo o **guia-pratico.md** rigorosamente.

**NÃO** é:
- ❌ Criar código rápido
- ❌ "Já sei fazer, vou direto"
- ❌ Pular passos para agilizar
- ❌ Ignorar checkpoints

**É:**
- ✅ Seguir guia passo a passo
- ✅ Parar em cada checkpoint
- ✅ Validar com script
- ✅ Entregar 100% completo

---

## 📚 DOCUMENTOS QUE VOCÊ DEVE LER (NESTA ORDEM)

### **1. ESTE ARQUIVO (você está lendo agora)**
- ✅ Entender a missão
- ✅ Entender o fluxo obrigatório
- ✅ Entender as flags de controle

### **2. /docs/crud/guia-pratico.md (PRÓXIMO)**
- ✅ Ler COMPLETO (não apenas skimming)
- ✅ Entender os 24 passos
- ✅ Memorizar os checkpoints obrigatórios
- ✅ Entender que "opcional" = verificar flag

### **3. /docs/crud/INSTRUCOES-CLAUDE.md (REFERÊNCIA)**
- ✅ Entender o sistema de flags
- ✅ Entender quando PASSO 11B é obrigatório
- ✅ Entender exemplos de flags

### **4. /docs/crud/EXEMPLO-FLAGS.md (SE TIVER DÚVIDA)**
- ✅ Ver exemplo prático de flags
- ✅ Ver cenário Banner Hero completo

---

## 🚩 SISTEMA DE FLAGS: O QUE É E POR QUE É CRÍTICO

### **O PROBLEMA QUE AS FLAGS RESOLVEM**

Você (Claude) tende a interpretar "opcional" como "pode pular".

**Exemplo real que aconteceu:**
```
User no PASSO -2: "Criar CRUD de banners exibido no frontend"
                            ↓
Claude define: $needs_frontend_display = True
                            ↓
Claude chega no PASSO 11B: "CRIAR DISPLAY FRONTEND (OPCIONAL)"
                            ↓
Claude pensa: "É opcional, vou pular"  ← ❌ ERRADO!
                            ↓
Resultado: Frontend NÃO criado, usuário teve que pedir manualmente
```

**Com flags (comportamento CORRETO):**
```
User no PASSO -2: "Criar CRUD de banners exibido no frontend"
                            ↓
Claude define: $needs_frontend_display = True
Claude mostra: "📋 FLAGS: $needs_frontend_display = True"
                            ↓
Claude chega no PASSO 11B: "CRIAR DISPLAY FRONTEND (OPCIONAL)"
                            ↓
Claude verifica flag: True
Claude pensa: "Flag=True, é OBRIGATÓRIO para este CRUD"  ← ✅ CORRETO!
                            ↓
Claude executa: PASSO 11B completo (6 sub-passos)
                            ↓
Resultado: Frontend criado automaticamente ✅
```

---

## 🎓 AS 4 FLAGS DE CONTROLE

Você DEVE definir estas 4 variáveis logo após PASSO -2:

```python
# FLAG 1: Upload de arquivo?
$needs_upload = False
# SE usuário respondeu "SIM" na pergunta 5 (upload):
#   → $needs_upload = True
#   → EXECUTAR PASSO 4B (obrigatório)

# FLAG 2: Ordenação/ranking?
$needs_ordering = False
# SE usuário respondeu "SIM" na pergunta 5 (ordenação):
#   → $needs_ordering = True
#   → Adicionar campo `order` na tabela

# FLAG 3: Status ativo/inativo?
$needs_status = False
# SE usuário respondeu "SIM" na pergunta 5 (status):
#   → $needs_status = True
#   → Adicionar campo `ativo` na tabela

# FLAG 4: Display no frontend?
$needs_frontend_display = False
# SE usuário respondeu na pergunta 6:
#   - "SIM" ou "Sim" ou "sim"
#   - "frontend" na resposta
#   - "home" na resposta
#   - "página" na resposta
#   - "site" na resposta
#   - "público" na resposta
#   → $needs_frontend_display = True
#   → EXECUTAR PASSO 11B (obrigatório, não opcional!)
```

---

## 📋 FLUXO OBRIGATÓRIO: PASSO A PASSO

### **PASSO 0: PREPARAÇÃO (VOCÊ ESTÁ AQUI)**

```
[✅] Ler INICIO-OBRIGATORIO.md (este arquivo)
[ ] Ler guia-pratico.md COMPLETO
[ ] Ler INSTRUCOES-CLAUDE.md
[ ] Entender sistema de flags
[ ] Confirmar: "Pronto para começar"
```

**➡️ PRÓXIMO:** Ler guia-pratico.md

---

### **PASSO -2: COLETAR REQUISITOS**

```
[ ] Fazer 6 perguntas ao usuário
[ ] Aguardar TODAS as respostas
[ ] Definir as 4 flags baseado nas respostas
[ ] MOSTRAR as flags para o usuário:
    "📋 FLAGS DEFINIDAS:
     - $needs_upload = True
     - $needs_ordering = True
     - $needs_status = True
     - $needs_frontend_display = True"
```

**⚠️ CRÍTICO:** NÃO prossiga sem definir E MOSTRAR as flags!

**➡️ PRÓXIMO:** PASSO -1 (Criar tabela)

---

### **PASSO -1 ATÉ PASSO 10: EXECUTAR CRUD**

```
[ ] Seguir cada passo em ordem
[ ] Parar em CADA checkpoint
[ ] Verificar flags quando aparecer "opcional"
[ ] Reportar progresso: "PASSO 4 [3/9]: Sanitização ✓"
[ ] NUNCA pular sem verificar flag
```

**Checkpoints críticos:**
- ⛔ PASSO 0: UUID validation
- ⛔ PASSO 4: CSRF + Rate Limiting
- ⛔ PASSO 4B: Upload (se $needs_upload = True)
- ⛔ PASSO 4C: Path traversal (se tem unlink)

**➡️ PRÓXIMO:** PASSO 11 (Checkpoint FRONTEND)

---

### **PASSO 11: CHECKPOINT FRONTEND (CRÍTICO!)**

```
⛔ PARE AQUI - VERIFICAÇÃO OBRIGATÓRIA

[ ] Reler resposta da pergunta 6 do PASSO -2
[ ] Verificar valor de $needs_frontend_display
[ ] Aplicar lógica:

    if $needs_frontend_display == True:
        print("🚨 PASSO 11B é OBRIGATÓRIO")
        print("Usuário pediu frontend no PASSO -2")
        goto PASSO_11B
        executar TODOS os 6 sub-passos:
            - 11B.1: Controller frontend
            - 11B.2: Partial reutilizável
            - 11B.3: Preview no admin
            - 11B.4: SASS dedicado
            - 11B.5: Testar preview
            - 11B.6: Documentar uso
    else:
        print("❓ Flag = False")
        perguntar_usuario("Deseja criar frontend display?")
        if usuario_diz_sim():
            goto PASSO_11B
        else:
            goto PASSO_12
```

**⚠️ ESTE É O PONTO ONDE VOCÊ MAIS ERRA!**

Memorize: **Flag = True → "Opcional" vira OBRIGATÓRIO**

**➡️ PRÓXIMO:** PASSO 12 (Validação)

---

### **PASSO 12: VALIDAÇÃO COM SCRIPT**

```
[ ] Executar: php scripts/validate-crud.php [Controller]
[ ] Verificar score
[ ] Score mínimo aceitável: 18/20 (90%)
[ ] Se < 90%: corrigir itens faltantes
[ ] Rodar novamente até atingir 90%+
```

**Score esperado ideal:** 20/20 (100%)

**➡️ PRÓXIMO:** PASSO 13 (Entregar)

---

### **PASSO 13: ENTREGAR**

```
[ ] CRUD completo criado
[ ] Validação passou (90%+)
[ ] Todos os checkpoints OK
[ ] Flags verificadas
[ ] Frontend criado (se necessário)
[ ] Documentação incluída
```

**✅ MISSÃO CUMPRIDA!**

---

## ⚠️ ERROS COMUNS E COMO EVITAR

### **ERRO 1: Não definir flags explicitamente**

❌ **ERRADO:**
```
Claude faz perguntas → Guarda mentalmente → Segue para código
```

✅ **CORRETO:**
```
Claude faz perguntas → Define flags → MOSTRA para usuário → Segue
```

---

### **ERRO 2: Ver "opcional" e pular sem verificar**

❌ **ERRADO:**
```
PASSO 11B (OPCIONAL) → "É opcional, vou pular" → PASSO 12
```

✅ **CORRETO:**
```
PASSO 11B (OPCIONAL) → Verificar $needs_frontend_display → Decidir baseado na flag
```

---

### **ERRO 3: Não ler guia completo**

❌ **ERRADO:**
```
User: "Criar CRUD" → Claude: "Já sei, vou criar..." → Código incompleto
```

✅ **CORRETO:**
```
User: "Criar CRUD" → Claude lê guia → Segue rigorosamente → Código completo
```

---

### **ERRO 4: Não rodar validação**

❌ **ERRADO:**
```
CRUD criado → Entregar → (não validou, tem erros)
```

✅ **CORRETO:**
```
CRUD criado → Rodar validate-crud.php → Corrigir erros → Atingir 90%+ → Entregar
```

---

## 🎯 CHECKLIST FINAL ANTES DE COMEÇAR

**ANTES de criar qualquer código, confirme:**

```
[ ] Li INICIO-OBRIGATORIO.md completo?
[ ] Vou ler guia-pratico.md COMPLETO agora?
[ ] Entendi as 4 flags de controle?
[ ] Entendi que "opcional" = verificar flag?
[ ] Vou fazer as 6 perguntas do PASSO -2?
[ ] Vou definir e MOSTRAR as flags?
[ ] Vou parar em CADA checkpoint?
[ ] Vou rodar validate-crud.php no final?
[ ] Vou atingir mínimo 90% de score?
```

**SE TODOS ✅ → Pode começar**

**SE ALGUM ❌ → Releia este arquivo**

---

## 🚀 PRÓXIMO PASSO

**Agora:**
1. ✅ Você leu INICIO-OBRIGATORIO.md
2. ➡️ Leia `/docs/crud/guia-pratico.md` COMPLETO
3. ➡️ Anuncie para o usuário: "Li guia completo. Iniciando PASSO -2..."
4. ➡️ Execute as 6 perguntas
5. ➡️ Defina e MOSTRE as flags
6. ➡️ Siga passos rigorosamente

---

## 💬 MENSAGEM PARA O USUÁRIO (COPIE E COLE)

Quando estiver pronto para começar, anuncie:

```
✅ Documentação lida:
   - INICIO-OBRIGATORIO.md
   - guia-pratico.md completo
   - INSTRUCOES-CLAUDE.md

📋 Entendi:
   - Sistema de 4 flags
   - 24 passos a seguir
   - Checkpoints obrigatórios
   - "Opcional" = verificar flag

🚀 Iniciando criação do CRUD seguindo guia rigorosamente.

PASSO -2: Coletar Requisitos
─────────────────────────────

Preciso de 6 informações para começar:

1️⃣ Nome do CRUD (ex: "Banner Hero", "Categorias")
2️⃣ Recurso técnico (ex: "BannerHero" → tabela: tbl_banner_hero)
3️⃣ Quem acessa? (ex: "Admin geral", "Módulo X")
4️⃣ Quais campos? (ex: titulo, subtitulo, imagem, ativo, order)
5️⃣ Comportamentos especiais?
   - Upload de arquivo? (SIM/NÃO)
   - Ordenação/ranking? (SIM/NÃO)
   - Status ativo/inativo? (SIM/NÃO)
6️⃣ Vai ter display no frontend? (SIM/NÃO ou descrever)
```

---

**Boa sorte! Siga o guia e você criará um CRUD perfeito. 🎯**

---

**Criado:** 2026-02-14
**Versão:** 1.0
**Propósito:** Garantir que Claude siga guia-pratico.md rigorosamente
