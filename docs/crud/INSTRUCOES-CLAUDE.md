# 🤖 INSTRUÇÕES DIRETAS PARA CLAUDE

## ⚠️ LEIA ISTO ANTES DE CRIAR QUALQUER CRUD

---

## 🚀 PRIMEIRA AÇÃO OBRIGATÓRIA

**QUANDO usuário pedir "criar CRUD de X":**

```
1. LER /docs/crud/INICIO-OBRIGATORIO.md (PRIMEIRO!)
2. LER /docs/crud/guia-pratico.md (COMPLETO!)
3. DEPOIS ler este arquivo (contexto adicional)
```

**NÃO comece a codificar sem ler os 3 arquivos nesta ordem.**

---

## 🚨 REGRA ABSOLUTA: "OPCIONAL" ≠ "PODE PULAR"

### ❌ **NÃO faça isso:**

```
User: "Criar CRUD de banners exibido no frontend"

Claude:
- Cria CRUD admin ✅
- Chega no PASSO 11
- Vê "PASSO 11B (OPCIONAL)"
- Pensa: "É opcional, vou pular"
- Vai direto para PASSO 12 ❌

RESULTADO: ❌ ERRADO - Usuário pediu frontend!
```

### ✅ **FAÇA isso:**

```
User: "Criar CRUD de banners exibido no frontend"

Claude:
- PASSO -2: Pergunta 6 → "Sim, exibido no frontend"
- Define: $needs_frontend_display = True
- Cria CRUD admin ✅
- Chega no PASSO 11
- Vê checkpoint obrigatório
- Verifica flag = True
- Pensa: "Usuário pediu frontend, é obrigatório"
- Executa PASSO 11B completo ✅

RESULTADO: ✅ CORRETO - Frontend criado automaticamente
```

---

## 📋 CHECKLIST OBRIGATÓRIO PARA VOCÊ (CLAUDE)

### **Ao iniciar criação de CRUD:**

```
[ ] Fazer todas as 6 perguntas do PASSO -2
[ ] Anotar resposta da pergunta 6 (Display frontend?)
[ ] Definir flag $needs_frontend_display baseado na resposta
[ ] Guardar essa flag para usar no PASSO 11
```

### **Ao chegar no PASSO 11:**

```
⛔ PARE AQUI - CHECKPOINT OBRIGATÓRIO

[ ] Reler resposta da pergunta 6 do PASSO -2
[ ] Verificar valor da flag $needs_frontend_display
[ ] Aplicar lógica:

    if $needs_frontend_display == True:
        ❌ NÃO pular para PASSO 12
        ✅ Executar PASSO 11B (OBRIGATÓRIO)
        ✅ Fazer todos os 6 sub-passos:
            - 11B.1: Controller frontend
            - 11B.2: Partial reutilizável
            - 11B.3: Preview no admin
            - 11B.4: SASS
            - 11B.5: Testes
            - 11B.6: Documentação

    else:
        ❓ Perguntar ao usuário:
           "Deseja criar frontend display?"

        if usuário diz SIM:
            ✅ Executar PASSO 11B
        else:
            ✅ Ir para PASSO 12
```

---

## 🎯 QUANDO A FLAG É TRUE?

```python
# Flag vira True SE a resposta contém:

$needs_frontend_display = (
    "sim" in resposta.lower() or
    "yes" in resposta.lower() or
    "frontend" in resposta.lower() or
    "home" in resposta.lower() or
    "página" in resposta.lower() or
    "site" in resposta.lower() or
    "público" in resposta.lower()
)

# Exemplos que ativam a flag:
"Sim" → True ✅
"Sim, na home" → True ✅
"Exibido no frontend" → True ✅
"Vai aparecer no site" → True ✅
"Página sobre" → True ✅

# Exemplos que NÃO ativam:
"Não" → False ✅
"Só admin" → False ✅
"Apenas gerenciamento" → False ✅
```

---

## 🚩 SISTEMA DE 4 FLAGS

```python
# Você deve manter 4 flags durante todo o processo:

$needs_upload = False
$needs_ordering = False
$needs_status = False
$needs_frontend_display = False

# REGRAS DE USO:

# Flag 1: Upload
if pergunta_5_upload == "SIM":
    $needs_upload = True
    # → Executar PASSO 4B ao criar store()

# Flag 2: Ordenação
if pergunta_5_ordering == "SIM":
    $needs_ordering = True
    # → Adicionar campo `order` na tabela
    # → Adicionar ORDER BY `order` nas queries

# Flag 3: Status
if pergunta_5_status == "SIM":
    $needs_status = True
    # → Adicionar campo `ativo` na tabela
    # → Adicionar WHERE ativo=1 nas queries frontend

# Flag 4: Frontend Display
if pergunta_6 tem palavras-chave:
    $needs_frontend_display = True
    # → Executar PASSO 11B no final
```

---

## 🔴 ERROS COMUNS E COMO EVITAR

### **ERRO 1: Ver "opcional" e pular sem verificar**

```
❌ ERRADO:
"PASSO 11B (OPCIONAL)" → Vou pular

✅ CORRETO:
"PASSO 11B (OPCIONAL)" → Verificar flag primeiro
if flag == True → É obrigatório
```

### **ERRO 2: Não reler o PASSO -2**

```
❌ ERRADO:
Não lembro o que usuário respondeu → Vou pular

✅ CORRETO:
Reler pergunta 6 → Verificar resposta → Decidir baseado nisso
```

### **ERRO 3: Não definir as flags**

```
❌ ERRADO:
Perguntei mas não guardei as respostas

✅ CORRETO:
Definir as 4 flags logo após PASSO -2
Consultar essas flags nos passos seguintes
```

### **ERRO 4: Pular sub-passos do 11B**

```
❌ ERRADO:
Fazer só 11B.1 e 11B.2 → Parar

✅ CORRETO:
Fazer TODOS os 6 sub-passos:
- 11B.1 ✅
- 11B.2 ✅
- 11B.3 ✅
- 11B.4 ✅
- 11B.5 ✅
- 11B.6 ✅
```

---

## 📖 INTERPRETAÇÃO DE "OPCIONAL"

### ❌ **Interpretação ERRADA:**

```
"Opcional" = Posso escolher não fazer
"Opcional" = Depende do meu critério
"Opcional" = Pular se quiser agilizar
```

### ✅ **Interpretação CORRETA:**

```
"Opcional" = Depende da flag
"Opcional" = Verificar resposta do usuário
"Opcional" = Se flag=True, vira obrigatório
"Opcional" = Sempre verificar antes de decidir
```

---

## 🎓 FLUXO COMPLETO CORRETO

```
1. PASSO -2: Coletar Requisitos
   └─ Fazer 6 perguntas
   └─ Definir 4 flags
   └─ Guardar respostas

2. PASSO -1 até PASSO 10: Criar CRUD
   └─ Consultar flags quando necessário
   └─ PASSO 4B: Só se $needs_upload = True

3. PASSO 11: CRUD Admin Completo
   └─ ⛔ PARAR AQUI - CHECKPOINT
   └─ Verificar $needs_frontend_display
   └─ if True:
          └─ EXECUTAR PASSO 11B (6 sub-passos)
      else:
          └─ Perguntar ao usuário

4. PASSO 12: Validação
5. PASSO 13: Entregar
```

---

## 🚀 EXEMPLO PASSO A PASSO

### **Usuário diz:**
> "Criar CRUD de banners hero que vão aparecer na home"

### **Você deve fazer:**

```
PASSO -2:
✅ Pergunta 1: Nome? → "Banner Hero"
✅ Pergunta 2: Técnico? → "BannerHero"
✅ Pergunta 3: Quem? → "Admin geral"
✅ Pergunta 4: Campos? → [lista campos]
✅ Pergunta 5: Comportamentos?
   → Upload: SIM ($needs_upload = True)
   → Ordenação: SIM ($needs_ordering = True)
   → Status: SIM ($needs_status = True)
✅ Pergunta 6: Frontend?
   → Usuário disse "aparecer na home"
   → 🚨 $needs_frontend_display = True

PASSO -1 até PASSO 10:
✅ Criar tabela
✅ Criar controller admin
✅ Criar views admin
✅ Adicionar rotas
✅ PASSO 4B executado (flag upload=True)

PASSO 11:
⛔ CHECKPOINT!
✅ Verificar $needs_frontend_display
✅ Flag = True
✅ "Usuário pediu frontend no PASSO -2"
✅ "PASSO 11B é OBRIGATÓRIO"
✅ Executar 11B.1: Controller frontend
✅ Executar 11B.2: Partial
✅ Executar 11B.3: Preview
✅ Executar 11B.4: SASS
✅ Executar 11B.5: Testes
✅ Executar 11B.6: Documentação

PASSO 12:
✅ Validação

PASSO 13:
✅ Entregar completo
```

---

## ✅ CHECKLIST FINAL PARA VOCÊ (CLAUDE)

```
ANTES de pular PASSO 11B, verificar:

[ ] Reli a resposta da pergunta 6 do PASSO -2?
[ ] Verifiquei o valor da flag $needs_frontend_display?
[ ] Se flag=True, executei PASSO 11B completo?
[ ] Se flag=False, perguntei ao usuário antes de pular?
[ ] Se executei 11B, fiz TODOS os 6 sub-passos?
[ ] Não pulei nada por assumir que é "opcional"?

SE TODOS ✅ → Pode ir para PASSO 12
SE ALGUM ❌ → VOLTE e corrija
```

---

## 🎯 RESUMO EM 3 REGRAS

### **REGRA 1:**
Sempre definir flags no PASSO -2 baseado nas respostas

### **REGRA 2:**
"Opcional" = Verificar flag, não = pular automaticamente

### **REGRA 3:**
Se flag=True, passo "opcional" vira obrigatório

---

## 💬 SE TIVER DÚVIDA, PERGUNTE

```
❓ "Usuário quer frontend?" → Verifique flag
❓ "Devo fazer PASSO 11B?" → Verifique flag
❓ "É opcional?" → Sim, mas verifique flag primeiro
❓ "Posso pular?" → Só se flag=False E usuário confirmar
```

---

**IMPORTANTE:**

Estas instruções substituem qualquer interpretação implícita de "opcional".
Sempre que ver "opcional" no guia, lembre-se:
1. Verificar a flag correspondente
2. Se flag=True → É obrigatório
3. Se flag=False → Perguntar ao usuário

**Não decida sozinho. Verifique as flags.**

---

**Criado:** 2026-02-14
**Para:** Claude (Anthropic)
**Contexto:** Melhorias no guia-pratico.md
**Versão:** 2.0
