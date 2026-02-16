# GUIA PRÁTICO - REFATORAR/CRIAR CRUD

**Use este documento como seu ponto de partida.** Ele contém a ordem exata de ações e checklists para refatorar um CRUD existente ou criar um novo.

---

## 🤖 INSTRUÇÕES CRÍTICAS PARA CLAUDE (LER PRIMEIRO!)

### ⚠️ QUANDO O USUÁRIO PEDIR "CRIAR CRUD DE X" OU SIMILAR

**VOCÊ (CLAUDE) DEVE EXECUTAR ESTA SEQUÊNCIA EXATA:**

```
PASSO 0 (META): INICIALIZAÇÃO OBRIGATÓRIA
─────────────────────────────────────────
[ ] Ler /docs/crud/INICIO-OBRIGATORIO.md COMPLETO
[ ] Anunciar: "Vou seguir guia-pratico.md rigorosamente"
[ ] Abrir este arquivo (guia-pratico.md) e ler COMPLETO
[ ] Confirmar compreensão das 4 flags de controle
[ ] NUNCA começar a codificar antes destes passos

PASSO 1 (META): COLETAR REQUISITOS
─────────────────────────────────────────
[ ] Executar PASSO -2 (6 perguntas obrigatórias)
[ ] Aguardar TODAS as respostas do usuário
[ ] Definir as 4 flags EXPLICITAMENTE:
    - $needs_upload = True/False
    - $needs_ordering = True/False
    - $needs_status = True/False
    - $needs_frontend_display = True/False
[ ] Mostrar flags definidas para o usuário
[ ] NUNCA prosseguir sem definir flags

PASSO 2 (META): EXECUÇÃO RIGOROSA
─────────────────────────────────────────
[ ] Seguir TODOS os passos em ordem (PASSO -1 até PASSO 13)
[ ] PARAR em CADA checkpoint obrigatório
[ ] Verificar flags antes de pular passos "opcionais"
[ ] Reportar progresso: "PASSO X [Y/Z]: ação ✓"
[ ] NUNCA pular passos sem verificar flags

PASSO 3 (META): VALIDAÇÃO FINAL
─────────────────────────────────────────
[ ] Executar: php scripts/validate-crud.php [Controller]
[ ] Score mínimo: 18/20 (90%)
[ ] Se < 90%: corrigir e rodar novamente
[ ] Entregar apenas se validação passar
```

---

### 🚨 REGRAS ABSOLUTAS (NUNCA VIOLAR)

**❌ PROIBIDO:**
1. Começar a codificar SEM ler guia completo
2. Pular PASSO -2 (requisitos)
3. NÃO definir as 4 flags explicitamente
4. Ver "opcional" e pular SEM verificar flag
5. Entregar CRUD sem rodar validate-crud.php

**✅ OBRIGATÓRIO:**
1. Ler INICIO-OBRIGATORIO.md ANTES de tudo
2. Fazer 6 perguntas do PASSO -2
3. Definir e MOSTRAR as 4 flags
4. Verificar flags em CADA checkpoint
5. Rodar validação e atingir 90%+

---

### 📋 CHECKPOINT INICIAL (ANTES DE COMEÇAR)

**ANTES de criar qualquer código, verificar:**

```
[ ] Li /docs/crud/INICIO-OBRIGATORIO.md?
[ ] Li guia-pratico.md COMPLETO?
[ ] Entendi as 4 flags de controle?
[ ] Entendi que "opcional" = verificar flag?
[ ] Vou reportar progresso passo a passo?
```

**SE ALGUM ITEM = "NÃO":**
→ PARE AGORA
→ Leia os documentos faltantes
→ NÃO comece a codificar

---

### 🎯 FLUXO CORRETO RESUMIDO

```
User: "Criar CRUD de banners"
        ↓
Claude: [1] Lê INICIO-OBRIGATORIO.md
        [2] Anuncia: "Seguindo guia rigorosamente"
        [3] Executa PASSO -2 (6 perguntas)
        [4] Define flags explicitamente
        [5] Segue passos -1 até 13
        [6] Valida com script
        [7] Entrega completo
```

**❌ NUNCA:**
```
User: "Criar CRUD de banners"
        ↓
Claude: [X] Já sei fazer, vou criar...  ← ERRADO!
```

---

### ⛔ CONSEQUÊNCIAS DE PULAR PASSOS

Se você (Claude) pular passos ou não seguir o guia:

1. **Usuário terá trabalho manual** (ruim)
2. **CRUD incompleto** (faltará frontend, otimizações, segurança)
3. **Performance ruim** (SELECT *, sem paginação)
4. **Vulnerabilidades** (sem UUID validation, path traversal)
5. **Usuário perderá confiança** em você

**Portanto:** Siga o guia **RIGOROSAMENTE**. Sem exceções.

---

## 📚 SUMÁRIO EXECUTIVO - 24 PASSOS COMPLETOS

### **Fase 1: Planejamento e Preparação**
- **PASSO -2:** Coletar Requisitos (6 perguntas + definição de flags)
- **PASSO -1:** Criar Tabela no Banco (migration SQL)

### **Fase 2: Estrutura Base do CRUD Admin**
- **PASSO 0:** Criar Controller (BaseController + 6 métodos)
- **PASSO 1:** index() + View (listagem)
- **PASSO 2:** create() + View (formulário criação)
- **PASSO 3:** edit() + View (formulário edição)

### **Fase 3: Lógica de Negócio (Métodos Críticos)**
- **PASSO 4:** store() - Criar registro
  - **PASSO 4B:** Upload de arquivos (se $needs_upload = True)
- **PASSO 5:** update() - Atualizar registro
- **PASSO 6:** destroy() - Deletar registro

### **Fase 4: Integração e Configuração**
- **PASSO 7:** Adicionar Rotas (6 rotas RESTful)
- **PASSO 8:** Adicionar Link no Menu Admin

### **Fase 5: Validação e Finalização Admin**
- **PASSO 9:** Testar CRUD Admin Completo
- **PASSO 10:** Validar Segurança e Padrões

### **Fase 6: Frontend Display (Condicional)**
- **PASSO 11:** CRUD Admin Completo + Checkpoint Obrigatório
- **PASSO 11B:** Criar Display Frontend (SE $needs_frontend_display = True)
  - 11B.1: Controller Frontend
  - 11B.2: Partial Reutilizável
  - 11B.3: Preview no Admin
  - 11B.4: SASS Dedicado
  - 11B.5: Testar Preview
  - 11B.6: Documentar Uso

### **Fase 7: Finalização**
- **PASSO 12:** Validação Final com Script
- **PASSO 13:** Entregar e Documentar

**Total:** 13 passos principais + 1 sub-passo (4B) + 6 sub-passos (11B.1-11B.6) = **20-24 passos** (dependendo das flags)

---

## ⚠️ INSTRUÇÕES OBRIGATÓRIAS PARA CLAUDE

**QUANDO O USUÁRIO PEDIR "CRIE CRUD DE X" OU "IMPLEMENTE CRUD DE Y":**

### ❌ NÃO FAÇA ISSO:
- Criar código imediatamente
- "Já sei fazer, não preciso do guia"
- Pular passos para ir mais rápido
- Marcar como completo sem testar

### ✅ FAÇA ISSO (OBRIGATÓRIO):

**1. ANUNCIAR QUE VAI SEGUIR O GUIA:**
```
"Vou seguir o GUIA-PRATICO.md rigorosamente.
Iniciando PASSO -2: Coletar requisitos..."
```

**2. ABRIR CHECKLIST DE EXECUÇÃO:**
```
"Abrindo CHECKLIST-EXECUCAO.md para tracking..."
```

**3. REPORTAR CADA PASSO:**
```
"PASSO 4 [3/9]: Sanitização completa ✓"
"PASSO 4 [6/9]: Logger.audit() adicionado ✓"
```

**4. PARAR EM CADA CHECKPOINT:**
```
"⛔ CHECKPOINT CSRF:
[ ] validateCSRF() como primeira linha? ✓
[ ] Localização correta? ✓
CHECKPOINT APROVADO. Prosseguindo..."
```

**5. ANUNCIAR GATES:**
```
"🔒 GATE PASSO 8: Iniciando validação...
Checkpoint 1/3: Segurança Crítica... ✓
Checkpoint 2/3: Validações e Feedback... ✓
Checkpoint 3/3: Estrutura e Nomenclatura... ✓
GATE APROVADO. Liberado para PASSO 9."
```

**6. RODAR TESTES DO PASSO 12:**
```
"🧪 TESTE 1: Funcionalidade Básica
[ ] GET /admin/banners → 200 OK ✓
[ ] POST create → registro criado ✓
...

🧪 TESTE 2: Segurança
[ ] CSRF bloqueou? ✓
[ ] Rate limit ativo? ✓
..."
```

**7. VALIDAÇÃO AUTOMÁTICA (se disponível):**
```bash
php /scripts/validate-crud.php BannerController
```

### 🚨 IMPORTANTE

**SE VOCÊ PULAR O GUIA:**
- ❌ CRUD estará incompleto
- ❌ Faltará segurança (CSRF, RateLimit, Logger)
- ❌ Usuário terá que debugar e corrigir
- ❌ Perda de confiança

**SE VOCÊ SEGUIR O GUIA:**
- ✅ CRUD 98% correto de primeira
- ✅ Todas camadas de segurança
- ✅ Auditoria completa
- ✅ Código pronto para produção

**O guia existe para VOCÊ não errar. Use-o.**

---

## 📋 ANTES DE COMEÇAR

**⚠️ ABRA AGORA E MANTENHA VISÍVEL:**

```
/docs/crud/CHECKLIST-EXECUCAO.md
```

Você vai marcar cada checkbox conforme avança.

**Só entregue o CRUD se TODOS os checkboxes estiverem marcados ✓**

---

## PASSO -2: FAZER PERGUNTAS E COLETAR REQUISITOS

### ⚠️ VERDADE ABSOLUTA SOBRE CRUD EM AEGIS

**Existe UM único padrão para criar CRUDs:**

1. **Local sempre**: `/admin/controllers/[Recurso]Controller.php`
2. **Métodos sempre**: `index`, `create`, `store`, `edit`, `update`, `destroy` (6 métodos, sempre)
3. **Herança sempre**: `BaseController`
4. **O que muda**: Nome da tabela, campos, validações específicas
5. **O que NÃO muda**: A estrutura dos 6 métodos é IDÊNTICA

**Ponto crítico: O CRUD não sabe (nem precisa saber) ONDE seus dados vão ser exibidos.**

---

### 🎯 O Que um CRUD Faz

✅ Gerenciar dados (create, read, update, delete)
✅ Exibir em admin (`/admin/recurso`)
✅ Fornecer dados para qualquer outra página consumir

❌ Decidir onde exibir
❌ Decidir como exibir (carrossel, lista, grid)
❌ Conhecer quem vai consumir seus dados

---

### 🔄 Como Funciona a Exibição

**Se você quer exibir banners na home:**

```
1. O CRUD (BannerController) gerencia banners
   → /admin/banners (gerenciar)
   → $controller->index() retorna todos os banners

2. A HOME (home.php) quer exibir banners
   → Chama o método que precisa: $controller->index()
   → O CRUD não sabe que é na home
   → O CRUD não sabe se é carrossel, lista ou grid
   → Aquilo é responsabilidade da HOME decidir
```

**Analogia: Restaurante**
- CRUD = cozinha (produz pratos)
- Home = uma mesa (pede prato do restaurante)
- Blog = outra mesa (pede prato do restaurante)
- Relatório = outra mesa (pede prato do restaurante)

**A cozinha NÃO sabe (nem precisa saber) qual mesa vai comer o prato.**

---

### ⚠️ O QUE É UM CRUD

**Um CRUD SEMPRE terá os 6 métodos:**
- `index` (listar todos)
- `create` (exibir formulário de criação)
- `store` (processar POST de criação)
- `edit` (exibir formulário de edição)
- `update` (processar POST de edição)
- `destroy` (processar DELETE)

**Se você pediu "CRUD", vai ter os 6 métodos. Sempre.**

---

### 📋 Perguntas Obrigatórias

```
1️⃣  NOME do CRUD (para identificação):

    ❓ POR QUÊ PERGUNTAR ISSO?
    Este nome será usado em comentários, documentação e para referência.
    Ajuda a diferenciar quando você tem vários CRUDs parecidos.

    📝 COMO RESPONDER:
    - Use nome descritivo que explica O QUE É + ONDE/COMO USA
    - Pode ser composto (2-4 palavras)
    - Pense: "Se eu ver este nome daqui 6 meses, vou lembrar o que é?"

    ✅ EXEMPLOS REAIS:
    - "Banner Hero Homepage" (banner principal que vai na home)
    - "Depoimentos Clientes Rodapé" (depoimentos que aparecem no footer)
    - "Produtos Loja Virtual" (produtos do e-commerce)
    - "Parceiros Logos" (logos de parceiros/clientes)
    - "FAQ Perguntas Frequentes" (seção de dúvidas)
    - "Galeria Fotos Projetos" (galeria de imagens de trabalhos)

    ❌ EVITE:
    - "Banner" (muito genérico, qual banner?)
    - "Dados" (não diz nada)
    - "Conteúdo" (muito vago)

---

2️⃣  O QUE é este recurso (nome técnico)?

    ❓ POR QUÊ PERGUNTAR ISSO?
    Este será o nome técnico usado em:
    - Nome da tabela no banco (ex: banners)
    - Nome do controller (ex: BannerController.php)
    - Rotas (ex: /admin/banners)
    - Variáveis no código (ex: $banner, $banners)

    📝 COMO RESPONDER:
    - UMA palavra, no singular
    - Sem espaços, sem caracteres especiais
    - Primeira letra maiúscula
    - Em português (padrão AEGIS)

    ✅ EXEMPLOS REAIS:
    - Banner
    - Depoimento
    - Produto
    - Categoria
    - Parceiro
    - Servico (sem ç)
    - Artigo
    - Pergunta
    - Galeria

    ❌ EVITE:
    - "Banner Hero" (2 palavras)
    - "banner" (minúscula)
    - "Banners" (plural)
    - "Serviço" (caractere especial ç)

---

3️⃣  QUEM acessa o admin deste CRUD?

    ❓ POR QUÊ PERGUNTAR ISSO?
    Define qual nível de permissão é necessário para gerenciar este recurso.

    📝 OPÇÕES DISPONÍVEIS:

    [ ] Admin geral (Auth::require()) ← PADRÃO - ESCOLHA ESTA 99% DAS VEZES
        → Qualquer usuário logado no admin pode gerenciar
        → Exemplos: Banners, Produtos, Depoimentos, Categorias
        → Usado em: 95% dos CRUDs normais

    [ ] Apenas super admin (Role check)
        → Somente super admins podem gerenciar
        → Exemplos: Usuários do Sistema, Configurações Globais, Logs de Auditoria
        → Usado em: Recursos críticos ou sensíveis

    [ ] Proprietários de conteúdo (Permission check)
        → Usuários só gerenciam o próprio conteúdo
        → Exemplos: Autores veem apenas próprios Artigos, Vendedores veem apenas próprias Vendas
        → Usado em: Sistemas multi-usuário com isolamento de dados

    ✅ COMO RESPONDER:
    - Na dúvida? Escolha "Admin geral"
    - É configuração crítica do sistema? Escolha "Super admin"
    - É conteúdo por usuário? Escolha "Proprietários"

---

4️⃣  QUAIS são os campos necessários?

    ❓ POR QUÊ PERGUNTAR ISSO?
    Define a estrutura da tabela do banco e dos formulários admin.

    📝 COMO RESPONDER:
    Liste TODOS os campos que precisa, indicando:
    - Nome do campo
    - Tipo (ver lista abaixo)
    - Obrigatório ou Opcional

    📋 FORMATO DE RESPOSTA:
    - title (VARCHAR 255, obrigatório) - Título principal
    - subtitle (VARCHAR 255, opcional) - Subtítulo secundário
    - image (VARCHAR 255 path, obrigatório) - Imagem de destaque
    - order (INT, obrigatório) - Ordem de exibição
    - ativo (TINYINT 1/0, obrigatório) - Status ativo/inativo

    💡 TIPOS DE CAMPOS DISPONÍVEIS:

    📝 TEXTO CURTO (VARCHAR 255):
    ✅ title → "Título do Banner Hero"
    ✅ name → "João da Silva"
    ✅ subtitle → "Subtítulo opcional"
    ✅ email → "contato@empresa.com"
    ✅ url → "https://site.com/pagina"
    ✅ slug → "meu-produto-incrivel"

    Quando usar: Textos até 255 caracteres (títulos, nomes, links, emails)

    📝 TEXTO LONGO (TEXT):
    ✅ description → "Esta é uma descrição longa do produto com vários parágrafos..."
    ✅ content → "Conteúdo completo do artigo com formatação..."
    ✅ bio → "Biografia completa do autor..."
    ✅ notes → "Observações internas..."

    Quando usar: Textos longos, parágrafos, descrições detalhadas

    🔢 NÚMEROS INTEIROS (INT):
    ✅ order → 1, 2, 3 (ordem de exibição)
    ✅ quantity → 50 (quantidade em estoque)
    ✅ views → 1523 (contador de visualizações)
    ✅ age → 25 (idade)

    Quando usar: Números sem casas decimais

    🔢 NÚMEROS DECIMAIS (DECIMAL):
    ✅ price → 99.90 (preço de produto)
    ✅ rating → 4.5 (avaliação de 0 a 5)
    ✅ percentage → 15.75 (porcentagem)

    Quando usar: Números com casas decimais (preços, notas, porcentagens)

    📅 DATA E HORA (DATETIME):
    ✅ published_at → "2025-03-15 14:30:00" (quando foi publicado)
    ✅ expires_at → "2025-12-31 23:59:59" (quando expira)
    ✅ event_date → "2025-06-20 19:00:00" (data do evento)

    Quando usar: Datas com horário específico

    📅 APENAS DATA (DATE):
    ✅ birth_date → "1990-05-15" (data de nascimento)
    ✅ deadline → "2025-08-30" (prazo sem hora)

    Quando usar: Datas sem necessidade de horário

    🔘 VERDADEIRO/FALSO (TINYINT 1/0):
    ✅ ativo → 1 (sim, está ativo) ou 0 (não, está inativo)
    ✅ featured → 1 (destaque) ou 0 (não destaque)
    ✅ published → 1 (publicado) ou 0 (rascunho)
    ✅ visible → 1 (visível) ou 0 (oculto)

    Quando usar: Campos de sim/não, ligado/desligado, ativo/inativo

    📁 UPLOAD DE IMAGEM (VARCHAR 255 path):
    ✅ image → "/storage/uploads/banners/banner_123.jpg"
    ✅ avatar → "/storage/uploads/users/avatar_456.png"
    ✅ logo → "/storage/uploads/parceiros/logo_789.webp"
    ✅ thumbnail → "/storage/uploads/produtos/thumb_012.jpg"

    Quando usar: Upload de imagens (JPG, PNG, GIF, WEBP)
    Importante: O campo guarda o CAMINHO do arquivo, não o arquivo

    📁 UPLOAD DE ARQUIVO (VARCHAR 255 path):
    ✅ pdf → "/storage/uploads/documentos/catalogo.pdf"
    ✅ document → "/storage/uploads/contratos/contrato_123.docx"
    ✅ attachment → "/storage/uploads/anexos/arquivo.zip"

    Quando usar: Upload de PDFs, documentos, arquivos em geral
    Importante: O campo guarda o CAMINHO do arquivo, não o arquivo

    🔗 RELACIONAMENTO (CHAR 36 UUID):
    ✅ user_id → "550e8400-e29b-41d4-a716-446655440000" (qual usuário criou)
    ✅ category_id → "6ba7b810-9dad-11d1-80b4-00c04fd430c8" (qual categoria pertence)
    ✅ author_id → "7c9e6679-7425-40de-944b-e07fc1f90ae7" (qual autor escreveu)
    ✅ parent_id → "123e4567-e89b-12d3-a456-426614174000" (categoria pai em hierarquia)

    Quando usar: Relacionamento com outra tabela (Foreign Key)
    Importante: AEGIS usa UUID (36 caracteres), não INT auto increment

    🎨 COR HEXADECIMAL (VARCHAR 7):
    ✅ color → "#FF5733" (cor de fundo)
    ✅ text_color → "#FFFFFF" (cor do texto)
    ✅ border_color → "#000000" (cor da borda)

    Quando usar: Campos de cor com color picker

    🎨 DADOS JSON (TEXT):
    ✅ settings → {"theme": "dark", "notifications": true}
    ✅ metadata → {"views": 100, "shares": 25}

    Quando usar: Dados estruturados complexos, configurações dinâmicas

    🔗 PARES DE CAMPOS (2 campos juntos):

    💡 CTA BUTTON:
    ✅ button_text (VARCHAR 255) → "Saiba Mais"
    ✅ button_url (VARCHAR 255) → "https://site.com/produto"

    Quando usar: Botões de ação que precisam texto E link

    💡 LINK COM TEXTO:
    ✅ link_text (VARCHAR 255) → "Ver Detalhes"
    ✅ link_url (VARCHAR 255) → "/produtos/detalhes"

    Quando usar: Links com texto customizável

    💡 TELEFONE COM LABEL:
    ✅ phone_label (VARCHAR 255) → "WhatsApp"
    ✅ phone_number (VARCHAR 255) → "(11) 98765-4321"

    Quando usar: Telefones com descrição (Comercial, Celular, WhatsApp)

    💡 REDES SOCIAIS:
    ✅ social_name (VARCHAR 255) → "Instagram"
    ✅ social_url (VARCHAR 255) → "https://instagram.com/empresa"

    Quando usar: Links de redes sociais com nome da rede

    ✅ EXEMPLO COMPLETO DE RESPOSTA (Banner):
    - title (VARCHAR 255, obrigatório) - Título principal do banner
    - subtitle (VARCHAR 255, opcional) - Subtítulo complementar
    - image (VARCHAR 255 path, obrigatório) - Imagem de fundo JPG/PNG/WEBP
    - button_text (VARCHAR 255, obrigatório) - Texto do botão CTA
    - button_url (VARCHAR 255, obrigatório) - URL do botão CTA
    - order (INT, obrigatório, default 0) - Ordem de exibição
    - ativo (TINYINT 1/0, obrigatório, default 1) - Status ativo/inativo

    ✅ EXEMPLO COMPLETO DE RESPOSTA (Produto):
    - name (VARCHAR 255, obrigatório) - Nome do produto
    - description (TEXT, obrigatório) - Descrição completa
    - price (DECIMAL 10,2, obrigatório) - Preço em reais
    - image (VARCHAR 255 path, obrigatório) - Foto principal
    - category_id (CHAR 36 UUID, obrigatório) - Categoria do produto
    - quantity (INT, obrigatório, default 0) - Estoque disponível
    - featured (TINYINT 1/0, obrigatório, default 0) - Produto destaque
    - ativo (TINYINT 1/0, obrigatório, default 1) - Status ativo/inativo

---

5️⃣  COMPORTAMENTOS especiais?

    ❓ POR QUÊ PERGUNTAR ISSO?
    Alguns recursos precisam de funcionalidades extras além do CRUD básico.

    📝 COMO RESPONDER:
    Marque SIM ou NÃO para cada comportamento abaixo.

    [ ] Upload de arquivo? (SIM/NÃO)
        ↳ Se SIM: Usar PASSO 4B (validação de upload)
        ↳ Exemplos SIM: Banners (imagem), Documentos (PDF), Produtos (foto)
        ↳ Exemplos NÃO: Categorias (só texto), Configurações (só dados)

    [ ] Ordenação/ranking? (SIM/NÃO)
        ↳ Se SIM: Adicionar campo "order" (INT) na tabela
        ↳ Exemplos SIM: Banners (ordem do carrossel), Depoimentos (ordem de exibição)
        ↳ Exemplos NÃO: Produtos (ordenam por data), Artigos (ordenam por data)

    [ ] Status ativo/inativo? (SIM/NÃO)
        ↳ Se SIM: Adicionar campo "ativo" (TINYINT 1/0) na tabela
        ↳ Exemplos SIM: Quase TODOS os CRUDs (banners, produtos, categorias)
        ↳ Exemplos NÃO: Logs (não tem sentido ativar/desativar log)

    [ ] Datas especiais (published_at, expires_at)? (SIM/NÃO)
        ↳ Se SIM: Adicionar campo(s) DATETIME na tabela
        ↳ Exemplos SIM: Artigos (published_at), Promoções (expires_at)
        ↳ Exemplos NÃO: Categorias, Banners fixos

    [ ] Relacionamentos com outras tabelas? (SIM/NÃO, quais?)
        ↳ Se SIM: Adicionar campo(s) _id (CHAR 36 UUID) na tabela
        ↳ Exemplos SIM:
            - Produtos → category_id (relaciona com categorias)
            - Artigos → author_id (relaciona com usuários)
            - Comentários → post_id (relaciona com posts)
        ↳ Exemplos NÃO: Categorias standalone, Configurações globais

    ✅ EXEMPLO COMPLETO DE RESPOSTA (Banner):
    [X] Upload de arquivo? SIM (imagem JPG/PNG/WEBP, máximo 5MB)
    [X] Ordenação/ranking? SIM (campo "order")
    [X] Status ativo/inativo? SIM (campo "ativo")
    [ ] Datas especiais? NÃO
    [ ] Relacionamentos? NÃO

    ✅ EXEMPLO COMPLETO DE RESPOSTA (Artigo):
    [X] Upload de arquivo? SIM (imagem destaque JPG/PNG, máximo 2MB)
    [ ] Ordenação/ranking? NÃO (ordena por published_at)
    [X] Status ativo/inativo? SIM (campo "ativo")
    [X] Datas especiais? SIM (published_at DATETIME)
    [X] Relacionamentos? SIM (category_id, author_id)

---

6️⃣  Vai ter DISPLAY FRONTEND?

    ❓ POR QUÊ PERGUNTAR ISSO?
    Define se este recurso será exibido no site (frontend) ou é apenas gerenciamento (admin).

    📝 OPÇÕES DISPONÍVEIS:

    [ ] NÃO → Pronto, só existe o CRUD Admin
        ↳ Quando usar: Recursos apenas de gerenciamento interno
        ↳ Exemplos:
            - Usuários do sistema (admin apenas)
            - Configurações globais (admin apenas)
            - Logs de auditoria (admin apenas)
            - Permissões (admin apenas)
        ↳ Resultado: Apenas BannerController com 6 métodos admin

    [ ] SIM → Criar partial reutilizável + preview no admin
        ↳ Quando usar: Recursos que aparecem no site
        ↳ Exemplos:
            - Banners (carrossel na home)
            - Depoimentos (seção de feedback)
            - Produtos (listagem na loja)
            - Parceiros (logos no rodapé)
            - FAQ (página de dúvidas)
        ↳ Resultado:
            1. BannerController (admin CRUD)
            2. FrontendBannerController (fornece dados ativos)
            3. /views/partials/banner-hero.php (componente reutilizável)
            4. /assets/sass/frontend/components/_banner-hero.sass (estilos)
            5. Preview no admin com código para copiar

    ⚠️ NOVA ABORDAGEM AEGIS:
    - NÃO perguntamos mais "onde vai aparecer?" (home, blog, etc)
    - NÃO acoplamos a página específica
    - Criamos PARTIAL GENÉRICA que funciona em qualquer lugar
    - Preview no admin mostra código pronto:
      <?php include __DIR__ . '/partials/banner-hero.php'; ?>
    - Usuário DECIDE onde usar: home, múltiplas páginas, includes, etc

    ✅ EXEMPLO DE RESPOSTA (Banner):
    [X] SIM - Vai aparecer no frontend
        → Criar FrontendBannerController
        → Criar /views/partials/banner-hero.php
        → Criar /assets/sass/frontend/components/_banner-hero.sass
        → Adicionar preview no admin/views/banners/index.php

    ✅ EXEMPLO DE RESPOSTA (Usuários):
    [ ] NÃO - Apenas gerenciamento admin
        → Só criar UserController com CRUD admin
        → Sem display frontend
```

---

### ✅ Exemplo Correto de Planejamento

**Cenário: Criar Banner CRUD**

```
User: Vou criar um CRUD de banners

Eu faço as perguntas:

1. Nome do CRUD? → "Banner Hero"
2. Recurso técnico? → Banner (tabela: banners)
3. Quem acessa? → Todos os admins
4. Quais campos? → title, subtitle, image, cta_button, cta_url, order, ativo
5. Comportamentos? → Upload (imagem JPG/PNG/GIF/WEBP 5MB), ordenação, status
6. Display frontend? → SIM

Meu plano:
✅ 1. Criar tabela: banners
✅ 2. Criar CRUD Admin: BannerController (6 métodos)
✅ 3. Criar views admin: index, create, edit
✅ 4. Adicionar 6 rotas admin
✅ 5. Criar FrontendBannerController (getActive)
✅ 6. Criar partial: banner-hero.php (carrossel reutilizável)
✅ 7. Adicionar preview no admin/views/banners/index.php
✅ 8. Preview mostra código para copiar
✅ 9. Usuário decide onde usar (home, múltiplas páginas, includes)

⚠️ NOVA ABORDAGEM: Não acoplamos a página específica
⚠️ Partial pode ser usada em qualquer lugar
⚠️ Preview no admin + código pronto para copiar
```

---

### 📊 Checklist Antes de Começar

```
OBRIGATÓRIO:
[ ] Responder: Nome do CRUD (identificação)?
[ ] Responder: O que é este recurso (técnico)?
[ ] Responder: Quem acessa admin?
[ ] Responder: Quais campos?
[ ] Responder: Comportamentos especiais?
[ ] Responder: Vai ter display frontend? (SIM/NÃO)

RESULTADO:
[ ] Entender que CRUD Admin é SEMPRE em /admin/controllers/
[ ] Entender que CRUD SEMPRE tem 6 métodos
[ ] Entender que frontend é responsável por exibição
[ ] Entender que CRUD não muda por causa do frontend
```

---

### 🚩 DEFINIR FLAGS DE CONTROLE (CLAUDE: MEMORIZE ESTAS FLAGS)

**Baseado nas respostas acima, definir:**

```python
# VARIÁVEIS DE CONTROLE PARA TODO O PROCESSO

$needs_upload = False           # Pergunta 5: Upload de arquivo?
$needs_ordering = False         # Pergunta 5: Ordenação/ranking?
$needs_status = False          # Pergunta 5: Status ativo/inativo?
$needs_frontend_display = False # Pergunta 6: Display frontend?

# REGRAS DE DEFINIÇÃO:

if resposta_pergunta_5_upload == "SIM":
    $needs_upload = True
    # → EXECUTAR PASSO 4B (obrigatório)

if resposta_pergunta_5_ordering == "SIM":
    $needs_ordering = True
    # → Adicionar campo `order` na tabela

if resposta_pergunta_5_status == "SIM":
    $needs_status = True
    # → Adicionar campo `ativo` na tabela

if resposta_pergunta_6 in ["SIM", "Sim", "sim"] or
   "frontend" in resposta_pergunta_6.lower() or
   "home" in resposta_pergunta_6.lower() or
   "página" in resposta_pergunta_6.lower():
    $needs_frontend_display = True
    # → EXECUTAR PASSO 11B (obrigatório, não opcional)
```

**🚨 IMPORTANTE PARA CLAUDE:**

Quando chegar no:
- **PASSO 4B:** Só executar se `$needs_upload = True`
- **PASSO 11:** Verificar `$needs_frontend_display`
  - Se `True` → PASSO 11B é OBRIGATÓRIO
  - Se `False` → PASSO 11B é opcional (mas perguntar)

**❌ NUNCA:**
- Ver "opcional" e pular sem verificar a flag
- Ignorar que usuário disse "SIM" no PASSO -2

**✅ SEMPRE:**
- Checar a flag antes de decidir
- Se flag = True → Passo é obrigatório
- "Opcional" = "depende da flag", não = "pode pular"

---

### ✍️ AÇÃO OBRIGATÓRIA: DEFINIR AS FLAGS AGORA

**🤖 CLAUDE: Antes de ir para PASSO -1, você DEVE:**

```python
# Baseado nas respostas do usuário acima, definir explicitamente:

print("📋 DEFININDO FLAGS DE CONTROLE:")
print(f"  $needs_upload = {True/False}")  # ← Pergunta 5
print(f"  $needs_ordering = {True/False}")  # ← Pergunta 5
print(f"  $needs_status = {True/False}")  # ← Pergunta 5
print(f"  $needs_frontend_display = {True/False}")  # ← Pergunta 6

# IMPORTANTE: Guardar essas flags para uso nos passos seguintes!
```

**Exemplo:**
```
User respondeu na pergunta 6: "Sim, exibido no frontend"
→ Claude define: $needs_frontend_display = True
→ No PASSO 11, esta flag torna PASSO 11B OBRIGATÓRIO
```

---

### 🚀 Próximo Passo

1. ✅ Responder as 6 perguntas acima
2. ✅ Definir as 4 flags de controle (AÇÃO ACIMA)
3. ➡️ Ir para PASSO -1

**Próximo**: Passo -1

---

## PASSO -1: CRIAR TABELA NO BANCO

### ⚠️ CUIDADO: SQL Reserved Keywords

**NUNCA use nomes de colunas que sejam SQL reserved keywords sem BACKTICKS:**

```sql
❌ ERRADO (vai quebrar):
SELECT * FROM banners WHERE order = 1

✅ CORRETO (sempre com backticks):
SELECT * FROM banners WHERE `order` = 1
```

**Reserved keywords comuns:**
- `order` - ORDER BY
- `group` - GROUP BY
- `key` - PRIMARY KEY
- `value` - valores
- `type` - tipos de dados
- `status` - estado

**Se usar algum desses como coluna, SEMPRE usar backticks em TODAS as queries:**
- SQL: `` `order` ``
- PHP prepared: `?` (o backtick vai na SQL string)

**⛔ CHECKPOINT OBRIGATÓRIO - NÃO PROSSIGA SEM COMPLETAR:**

```sql
-- ✅ CORRETO - backticks em reserved keywords
CREATE TABLE `banners` (
  `order` INT DEFAULT 0,  -- ← backticks
  KEY `idx_order` (`order`)  -- ← backticks
)

SELECT * FROM banners WHERE `order` = ?  -- ← backticks
SELECT * FROM banners ORDER BY `order` ASC  -- ← backticks

-- ❌ ERRADO - sem backticks
CREATE TABLE banners (
  order INT DEFAULT 0  -- ← ERRO! 35% DE CHANCE
)
SELECT * FROM banners WHERE order = ?  -- ← ERRO!
```

**CHECKLIST OBRIGATÓRIO (35% de chance de erro - VERIFICAR AGORA):**
```
[ ] Tenho coluna com reserved keyword (order, group, key, value, type, status)?
[ ] Se SIM: TODAS as ocorrências têm backticks?
[ ] CREATE TABLE: `order` ✓
[ ] SELECT: WHERE `order` = ? ✓
[ ] ORDER BY: ORDER BY `order` ✓
[ ] INDEX: KEY `idx_order` (`order`) ✓

⚠️ SE ALGUMA QUERY NÃO TEM BACKTICKS, ADICIONE AGORA!
⚠️ ERRO DE BACKTICK = SQL SYNTAX ERROR = CÓDIGO QUEBRADO!
```

### SQL Migration

**Crie o arquivo SQL em `/migrations/`**

Exemplo: `/migrations/001_create_banners_table.sql`

```sql
CREATE TABLE IF NOT EXISTS `banners` (
  `id` CHAR(36) PRIMARY KEY COMMENT 'UUID v4',
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(500),
  `image` VARCHAR(255),
  `cta_url` VARCHAR(255) NOT NULL,
  `cta_text` VARCHAR(100),
  `order` INT DEFAULT 0,
  `ativo` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ativo` (`ativo`),
  KEY `idx_order` (`order`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Checklist de SQL

```
[ ] Nenhuma coluna com reserved keyword SEM backticks
[ ] Se usar reserved keyword, documentar que precisa backtick em queries
[ ] Charset: utf8mb4
[ ] Collation: utf8mb4_unicode_ci
[ ] Índices em colunas frequently queried
```

### Executar Migration

```bash
php /scripts/migrate.php
```

OU manualmente no MySQL:

```bash
mysql -u root -p -S /Applications/MAMP/tmp/mysql/mysql.sock < /migrations/001_create_banners_table.sql
```

### Checklist

```
[ ] Arquivo SQL criado em /migrations/
[ ] Tabela criada no banco
[ ] Colunas corretas
[ ] PRIMARY KEY é UUID (CHAR(36))
[ ] Campos obrigatórios SEM NULL
[ ] Campos opcionais COM NULL
[ ] Timestamps: created_at, updated_at
[ ] Índices em: ativo, order, created_at (ou relevantes para query)
[ ] Charset: utf8mb4
[ ] Collation: utf8mb4_unicode_ci
```

---

## PASSO 0: PREPARAÇÃO

### Informações do CRUD
```
[ ] Nome do recurso: _________________________
[ ] Tipo: [ ] Admin [ ] Module [ ] API
[ ] Arquivo atual/novo: _________________________
```

**Se for refatorar:**
```
[ ] Abra o arquivo atual em um editor
[ ] Identifique os 5-6 métodos existentes
```

**Se for criar novo:**
```
[ ] Decida o tipo (Admin/Module/API)
[ ] Crie arquivo vazio
```

---

## PASSO 0.5: VERIFICAR NOMES DE CLASSES (CRÍTICO!)

### ⚠️ IMPORTANTE: Você Está Criando Apenas Admin CRUD

**Este passo é APENAS sobre Admin CRUD em `/admin/controllers/`**

Se no futuro precisar de Frontend Display, será um Passo separado (PASSO 11B).

### Checklist: Antes de Criar a Classe

```
[ ] Pesquisar: o nome desta classe já existe em /admin/controllers/?
    find /admin/controllers -name "*BannerController*" -type f

[ ] Se não existe: usar nome simples
    Correto: BannerController
    Arquivo: /admin/controllers/BannerController.php

[ ] O ARQUIVO deve ter EXATAMENTE o mesmo nome da CLASSE
    Classe: BannerController → Arquivo: BannerController.php

[ ] Confirmado que é um nome ÚNICO em /admin/controllers/?
```

---

## PASSO 0.6: REGRAS OBRIGATÓRIAS PARA VIEWS ADMIN

### ⚠️ CRITICAL: Paths de Includes nas Views

**Views admin ficam em:** `/admin/views/{resource}/`
**Includes admin ficam em:** `/admin/includes/`

**De `admin/views/{resource}/` até `admin/includes/` = 2 NÍVEIS ACIMA**

```php
// ✅ CORRETO (2 níveis: ../.. )
require_once __DIR__ . '/../../includes/_admin-head.php';
require_once __DIR__ . '/../../includes/header.php';

// ❌ ERRADO (3 níveis - vai para raiz!)
require_once __DIR__ . '/../../../includes/_admin-head.php';
```

**Estrutura:**
```
admin/
├── views/
│   └── banners/          ← Você está AQUI
│       ├── index.php
│       ├── create.php
│       └── edit.php
├── includes/             ← Quer chegar AQUI
│   ├── _admin-head.php
│   └── header.php
└── controllers/
```

**De `banners/` até `includes/`:**
1. `../` = sobe para `views/`
2. `../` = sobe para `admin/`
3. `includes/` = entra em `includes/`

**Total: `../../includes/`** (NÃO `../../../`)

### ⚠️ CRITICAL: Variáveis Obrigatórias para Views Admin

**TODAS as views admin que incluem `header.php` precisam de `$user` definida.**

**No Controller, ANTES de `require view.php`:**

```php
// ✅ CORRETO - Sempre definir $user
public function index() {
    Auth::require();
    $user = Auth::user();  // ← OBRIGATÓRIO!

    $items = $this->db()->query("SELECT * FROM items");

    require __DIR__ . '/../views/items/index.php';
}

public function create() {
    Auth::require();
    $user = Auth::user();  // ← OBRIGATÓRIO!

    require __DIR__ . '/../views/items/create.php';
}

public function edit($id) {
    Auth::require();
    $user = Auth::user();  // ← OBRIGATÓRIO!

    $item = $this->db()->selectOne('items', ['id' => $id]);

    require __DIR__ . '/../views/items/edit.php';
}

// ❌ ERRADO - Sem $user
public function index() {
    Auth::require();
    // Faltou: $user = Auth::user();

    require __DIR__ . '/../views/items/index.php';  // ERRO!
}
```

**Por quê?**

`/admin/includes/header.php` usa:
```php
<span>Olá, <?= htmlspecialchars($user['name']) ?></span>
```

Se `$user` não existir = **ErrorException: Undefined variable $user**

### ⚠️ CRITICAL: Logger é OPCIONAL (Não Obrigatório)

**Logger NÃO é estático. Se for usar, precisa getInstance():**

```php
// ✅ CORRETO - Se quiser log de auditoria
Logger::getInstance()->audit('banner_created', Auth::user()['id'], [
    'banner_id' => $id,
    'title' => $title
]);

// ✅ CORRETO - Sem logger (funciona também)
// (simplesmente não adicione Logger)

// ❌ ERRADO - Logger::audit() estático
Logger::audit('banner_created', [...]);  // ERRO: Non-static method
```

**Logger é OPCIONAL. Se não adicionar, CRUD funciona normalmente.**

### Checklist Pré-Views

```
[ ] Paths de include corretos: ../../includes/ (2 níveis)
[ ] $user = Auth::user() em index()
[ ] $user = Auth::user() em create()
[ ] $user = Auth::user() em edit()
[ ] BaseController: extends BaseController
[ ] Database: usar $this->db() (não DB::connect())
[ ] Logger: OPCIONAL - se usar, getInstance()->audit()
```

---

---

## PASSO 0: VALIDAÇÃO DE UUID (OBRIGATÓRIO PARA SEGURANÇA)

### ⚠️ POR QUE ESTE PASSO É CRÍTICO

**Vulnerabilidade sem validação:**
```php
// ❌ INSEGURO - Aceita qualquer string como ID
public function edit($id) {
    $banner = $this->db()->query("SELECT * FROM tbl_banner WHERE id = ?", [$id]);
    // $id pode ser: "'; DROP TABLE--", "../../etc/passwd", "abc123"
}
```

**Impacto:**
- SQL Injection edge cases
- Path traversal attacks
- Database errors
- Logs poluídos com tentativas de ataque

---

### ✅ VALIDAÇÃO OBRIGATÓRIA

**Todo método que recebe `$id` DEVE validar o formato UUID v4:**

```php
public function edit($id) {
    $this->requireAuth();

    // ⛔ CHECKPOINT: UUID VALIDATION - PRIMEIRA COISA APÓS AUTH
    if (!Security::isValidUUID($id)) {
        http_response_code(400);
        die('ID inválido');
    }

    // Agora é seguro usar $id
    $banner = $this->db()->query("SELECT * FROM tbl_banner WHERE id = ?", [$id]);
    // ...
}
```

---

### 📋 APLICAR EM TODOS ESTES MÉTODOS:

**Admin/Module CRUDs:**
- ✅ `edit($id)` - Primeira linha após requireAuth()
- ✅ `update($id)` - Primeira linha após requireAuth()
- ✅ `destroy($id)` - Primeira linha após requireAuth()

**API CRUDs:**
- ✅ `show($id)` - Primeira linha após requireJWT()
- ✅ `update($id)` - Primeira linha após requireJWT()
- ✅ `destroy($id)` - Primeira linha após requireJWT()

---

### 🔐 IMPLEMENTAÇÃO DA FUNÇÃO (Se Não Existir)

**Arquivo:** `/core/Security.php`

```php
public static function isValidUUID($uuid) {
    if (empty($uuid) || !is_string($uuid)) {
        return false;
    }

    // Regex para UUID v4
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    return preg_match($pattern, $uuid) === 1;
}
```

**Teste rápido:**
```php
Security::isValidUUID('123');                                    // false
Security::isValidUUID('abc-def-ghi');                           // false
Security::isValidUUID('550e8400-e29b-41d4-a716-446655440000'); // true ✅
```

---

### ⛔ CHECKPOINT OBRIGATÓRIO

**ANTES de prosseguir para PASSO 1, verificar:**

```
[ ] Security::isValidUUID() existe em /core/Security.php?
[ ] Método edit($id) valida UUID antes de query?
[ ] Método update($id) valida UUID antes de query?
[ ] Método destroy($id) valida UUID antes de query?
[ ] Validação retorna HTTP 400 se inválido?
[ ] Validação está APÓS auth mas ANTES de qualquer query?
```

**❌ SE ALGUM ITEM FOR "NÃO", VOLTE E CORRIJA AGORA!**

**Motivo:** UUID inválido pode causar:
- Erros de database não tratados
- Logs poluídos
- Vetores de ataque
- Experiência ruim do usuário

---

### 📊 EXEMPLO COMPLETO

```php
class BannerController extends BaseController {

    // ✅ index() e create() não precisam (não recebem ID)

    // ✅ edit() - VALIDAÇÃO OBRIGATÓRIA
    public function edit($id) {
        $this->requireAuth();

        if (!Security::isValidUUID($id)) {
            http_response_code(400);
            die('ID inválido');
        }

        $banner = $this->db()->query(
            "SELECT * FROM tbl_banner WHERE id = ?",
            [$id]
        );

        if (empty($banner)) {
            $this->error('Banner não encontrado');
            $this->redirect('/admin/banner');
            return;
        }

        $this->render('banner/edit', ['banner' => $banner[0]]);
    }

    // ✅ update() - VALIDAÇÃO OBRIGATÓRIA
    public function update($id) {
        $this->requireAuth();

        if (!Security::isValidUUID($id)) {
            http_response_code(400);
            die('ID inválido');
        }

        try {
            $this->validateCSRF();
            // ... resto do código
        } catch (Exception $e) {
            // ...
        }
    }

    // ✅ destroy() - VALIDAÇÃO OBRIGATÓRIA
    public function destroy($id) {
        $this->requireAuth();

        if (!Security::isValidUUID($id)) {
            http_response_code(400);
            die('ID inválido');
        }

        try {
            $this->validateCSRF();
            // ... resto do código
        } catch (Exception $e) {
            // ...
        }
    }
}
```

---

### 🚨 AVISOS IMPORTANTES

**❌ NUNCA faça isso:**
```php
// ERRADO 1: Validar depois da query
$banner = $this->db()->query("SELECT * FROM tbl WHERE id = ?", [$id]);
if (!Security::isValidUUID($id)) { ... } // TARDE DEMAIS

// ERRADO 2: Não validar porque "prepared statement já protege"
// Prepared statement previne SQL injection, mas não valida formato
```

**✅ SEMPRE faça isso:**
```php
// CORRETO: Validar ANTES de qualquer operação
if (!Security::isValidUUID($id)) { die(); }
$banner = $this->db()->query(...);
```

---

**Próximo:** PASSO 1 - Escolher Template

---

---

## ⚠️ REGRA DE PERFORMANCE OBRIGATÓRIA: NUNCA USE SELECT *

### 🚨 PROIBIDO EM TODO O CÓDIGO

**❌ NUNCA faça isso:**
```php
// PROIBIDO - Busca TODOS os campos sempre
$banners = $this->db()->query("SELECT * FROM tbl_banner");
$users = $this->db()->query("SELECT * FROM usuarios");
$posts = $this->db()->query("SELECT * FROM posts");
```

**Por que é ruim:**
1. **Performance:** Transfere dados desnecessários (10x mais tráfego)
2. **Memória:** Desperdiça RAM com campos não usados
3. **Cache:** Ineficiência no cache de queries
4. **Escalabilidade:** Problema cresce com volume de dados
5. **Manutenção:** Se adicionar campo BLOB/TEXT, performance degrada

**Impacto real:**
```
Tabela com 1000 registros de 10 campos:
- SELECT * → 500KB transferidos
- SELECT id, name, active → 50KB transferidos
→ 10x DIFERENÇA!

Com 10.000 registros:
- SELECT * → 5MB (timeout em mobile 3G)
- SELECT específico → 500KB (carrega rápido)
```

---

### ✅ SEMPRE ESPECIFIQUE OS CAMPOS

**✅ CORRETO - Liste apenas campos necessários:**

```php
// Admin index: só precisa para listagem
$banners = $this->db()->query(
    "SELECT id, titulo, ativo, `order`
     FROM tbl_banner
     ORDER BY `order` ASC"
);

// Frontend: só precisa para exibição
$bannersAtivos = $this->db()->query(
    "SELECT id, titulo, subtitulo, imagem, cta_button, cta_link
     FROM tbl_banner
     WHERE ativo = 1
     ORDER BY `order` ASC"
);

// Edit: busca todos os campos para edição
$banner = $this->db()->query(
    "SELECT id, titulo, subtitulo, imagem, cta_button, cta_link, `order`, ativo
     FROM tbl_banner
     WHERE id = ?",
    [$id]
);
```

---

### 📋 COMO DEFINIR QUAIS CAMPOS BUSCAR

**Pergunte-se: "Quais campos vou REALMENTE usar?"**

**Admin Index (listagem):**
```php
// Preciso para: exibir tabela, ordenar, filtrar
$fields = "id, titulo, ativo, created_at, `order`";
```

**Admin Edit (formulário):**
```php
// Preciso para: preencher todos os inputs do form
$fields = "id, titulo, subtitulo, descricao, imagem, ativo, `order`";
```

**Frontend Display:**
```php
// Preciso para: renderizar na página
$fields = "id, titulo, subtitulo, imagem, cta_button, cta_link";
// NÃO preciso: created_at, updated_at, ativo (já filtrado no WHERE)
```

**API Response:**
```php
// Retornar apenas campos públicos
$fields = "id, name, email, avatar, created_at";
// NÃO incluir: password, reset_token, session_id
```

---

### ⛔ CHECKPOINT OBRIGATÓRIO: ANTI SELECT *

**ANTES de finalizar qualquer CRUD, verificar:**

```
[ ] ZERO ocorrências de "SELECT *" no código?
[ ] Todos os queries especificam campos explicitamente?
[ ] Index() busca apenas campos para listagem?
[ ] Frontend busca apenas campos para exibição?
[ ] Edit() busca campos necessários para o form?
[ ] API não expõe campos sensíveis (password, tokens)?
```

**❌ SE ENCONTRAR "SELECT *", SUBSTITUIR IMEDIATAMENTE!**

---

### 🎯 MÉTODO DO DB SELECT (AEGIS)

**Se usar `$this->db()->select()` em vez de `query()`:**

```php
// ❌ ERRADO - Busca tudo
$all = $this->db()->select('banners');

// ✅ CORRETO - Especificar campos como 3º parâmetro
// Sintaxe: select(table, where, orderBy, fields)
$banners = $this->db()->selectFields(
    'banners',
    ['ativo' => 1],
    'order ASC',
    ['id', 'titulo', 'imagem']
);
```

**Nota:** Verifique documentação do Database.php para sintaxe exata.

---

### 🚀 GANHO ESPERADO

**Implementando SELECT específico em TODO o CRUD:**
- ✅ 70-90% redução de dados transferidos
- ✅ 50% menos uso de memória
- ✅ 3-5x mais rápido em queries grandes
- ✅ Preparado para escalar para 100k+ registros

---

**Próximo:** PASSO 1 - Escolher Template

---

---

## PASSO 1: ESCOLHER TEMPLATE

**Consulte conforme seu tipo:**

- [ ] **ADMIN** → Abra `/docs/crud/templates/TEMPLATE-CRUD-ADMIN.md`
- [ ] **MODULE** → Abra `/docs/crud/templates/TEMPLATE-CRUD-MODULO.md`
- [ ] **API** → Abra `/docs/crud/templates/TEMPLATE-CRUD-API.md`

**Ação**: Copie a estrutura base do template para seu arquivo.

---

## PASSO 2: IMPLEMENTAR MÉTODO index() COM PAGINAÇÃO OBRIGATÓRIA

### ⚠️ PAGINAÇÃO É OBRIGATÓRIA (NÃO OPCIONAL)

**Por que paginação é obrigatória:**
```
Sem paginação:
- 100 registros: 0.05s ✅
- 1.000 registros: 0.5s ⚠️ (lento)
- 10.000 registros: 5s ❌ (inaceitável)
- 100.000 registros: timeout ❌ (quebra)

Com paginação (50 por página):
- Qualquer quantidade: 0.05s ✅ (sempre rápido)
```

**Exceções (pode não paginar):**
- Dados SEMPRE pequenos (<20 registros): status, categorias fixas
- Frontend display com WHERE ativo=1 AND limit manual

**Regra:** Admin index() **SEMPRE** deve ter paginação.

---

### 📋 IMPLEMENTAÇÃO OBRIGATÓRIA

```php
public function index() {
    $this->requireAuth();
    $user = $this->getUser();

    // [1] PAGINAÇÃO - OBRIGATÓRIA
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = 50; // Ajustar conforme necessidade (20-100)

    if ($page < 1) {
        $page = 1;
    }

    $offset = ($page - 1) * $perPage;

    // [2] CONTAR TOTAL (para calcular páginas)
    $totalResult = $this->db()->query(
        "SELECT COUNT(*) as total FROM tbl_banners"
    );
    $total = $totalResult[0]['total'] ?? 0;
    $totalPages = ceil($total / $perPage);

    // [3] BUSCAR PÁGINA ATUAL (com campos específicos + LIMIT/OFFSET)
    $banners = $this->db()->query(
        "SELECT id, titulo, ativo, `order`, created_at
         FROM tbl_banners
         ORDER BY `order` ASC
         LIMIT ? OFFSET ?",
        [$perPage, $offset]
    );

    // [4] RENDERIZAR com dados de paginação
    $this->render('banners/index', [
        'user' => $user,
        'banners' => $banners,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'total' => $total,
        'perPage' => $perPage
    ]);
}
```

---

### 🎨 VIEW COM PAGINAÇÃO (HTML)

**Arquivo: `/admin/views/banners/index.php`**

```php
<!-- Tabela com dados -->
<table>
    <?php foreach ($banners as $banner): ?>
        <tr>
            <td><?= htmlspecialchars($banner['titulo']) ?></td>
            <!-- ... -->
        </tr>
    <?php endforeach; ?>
</table>

<!-- PAGINAÇÃO -->
<?php if ($totalPages > 1): ?>
    <nav class="pagination">
        <!-- Página anterior -->
        <?php if ($currentPage > 1): ?>
            <a href="?page=<?= $currentPage - 1 ?>" class="page-link">« Anterior</a>
        <?php endif; ?>

        <!-- Números de página -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $currentPage): ?>
                <span class="page-link active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>" class="page-link"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- Próxima página -->
        <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?= $currentPage + 1 ?>" class="page-link">Próxima »</a>
        <?php endif; ?>
    </nav>

    <p class="pagination-info">
        Página <?= $currentPage ?> de <?= $totalPages ?> (<?= $total ?> registros no total)
    </p>
<?php endif; ?>
```

---

### ⚡ OTIMIZAÇÃO: Paginação Inteligente

**Se tiver MUITAS páginas (>100), mostrar resumido:**

```php
<!-- Mostrar: 1 ... 45 46 [47] 48 49 ... 150 -->
<?php
$range = 2; // Páginas antes/depois da atual
$start = max(1, $currentPage - $range);
$end = min($totalPages, $currentPage + $range);
?>

<nav class="pagination">
    <!-- Primeira -->
    <?php if ($currentPage > 1): ?>
        <a href="?page=1">«</a>
        <a href="?page=<?= $currentPage - 1 ?>">‹</a>
    <?php endif; ?>

    <!-- Início -->
    <?php if ($start > 1): ?>
        <a href="?page=1">1</a>
        <?php if ($start > 2): ?><span>...</span><?php endif; ?>
    <?php endif; ?>

    <!-- Range atual -->
    <?php for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i == $currentPage): ?>
            <span class="active"><?= $i ?></span>
        <?php else: ?>
            <a href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <!-- Fim -->
    <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><span>...</span><?php endif; ?>
        <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
    <?php endif; ?>

    <!-- Última -->
    <?php if ($currentPage < $totalPages): ?>
        <a href="?page=<?= $currentPage + 1 ?>">›</a>
        <a href="?page=<?= $totalPages ?>">»</a>
    <?php endif; ?>
</nav>
```

---

### ⛔ CHECKPOINT OBRIGATÓRIO: PAGINAÇÃO

**ANTES de finalizar index(), verificar:**

```
[ ] Variáveis $page e $perPage definidas?
[ ] $page validado (>= 1)?
[ ] Query COUNT(*) para total de registros?
[ ] $totalPages calculado (ceil($total / $perPage))?
[ ] Query principal tem LIMIT ? OFFSET ??
[ ] View recebe: currentPage, totalPages, total?
[ ] View renderiza controles de paginação?
[ ] Testado com >50 registros?
```

**❌ SE ALGUM ITEM FOR "NÃO", VOLTE E IMPLEMENTE!**

---

### 📊 CONFIGURAÇÕES RECOMENDADAS

**Registros por página:**
```php
// Admin listagens: 20-50 registros
$perPage = 50;

// APIs: 10-20 registros (mobile)
$perPage = 20;

// Datatables/exportação: 100-200
$perPage = 100;
```

**Máximo de páginas visíveis:**
```php
// Desktop: 7-9 links
$visiblePages = 7;

// Mobile: 3-5 links
$visiblePages = 3;
```

---

### Checklist de Implementação Completa

```
[ ] Autenticação adicionada
  - Admin/Module: Auth::require() ou $this->requireAuth()
  - API: Auth::requireJWT()

[ ] Paginação implementada (OBRIGATÓRIO)
  - $page = (int) ($_GET['page'] ?? 1)
  - $perPage definido (20-100)
  - $offset calculado

[ ] Database queries
  - COUNT(*) para total
  - SELECT específico (não *) com LIMIT/OFFSET
  - Prepared statements (?, placeholders)
  - ORDER BY apropriado

[ ] Response estruturada
  - Admin/Module: render() com dados de paginação
  - API: json(200, ['data' => ..., 'page' => ..., 'total' => ...])

[ ] View com controles de paginação
  - Links anterior/próxima
  - Números de página
  - Informação "X de Y registros"
```

### Validação Rápida
```
[ ] Não tem SQL injection (sem concatenação de strings)
[ ] Não tem XSS (outputs vão ser sanitizados na view)
[ ] Testado com 100+ registros (performance OK)?
[ ] Paginação funciona (navegar entre páginas)?
```

**Próximo**: Passo 3

---

## PASSO 3: IMPLEMENTAR MÉTODO create() [Admin/Module APENAS]

### Checklist de Implementação
```
[ ] Autenticação adicionada
  - Auth::require() ou $this->requireAuth()

[ ] Dados relacionados buscados (se necessário)
  - Ex: categorias, grupos, tags

[ ] View renderizada com dados
  - Passar array de dados para render/require
```

### Validação Rápida
```
[ ] Nada de database write aqui (apenas leitura)
[ ] View vai ter form com CSRF token
```

**Próximo**: Passo 4

---

## PASSO 4: IMPLEMENTAR MÉTODO store() [CREATE]

### Checklist em Ordem Rigorosa

#### [1] CSRF VALIDATION - PRIMEIRA COISA
```
[ ] Código adicionado:
  - Admin/Module: $this->validateCSRF()
  - API: (não precisa, usa JWT)

[ ] Localização: PRIMEIRA linha do try/catch
[ ] Sem exceção: validação deve der antes de qualquer outra ação
```

**⛔ CHECKPOINT OBRIGATÓRIO - NÃO PROSSIGA SEM COMPLETAR:**

```php
// ✅ CORRETO - CSRF como primeira linha
public function store() {
    $this->requireAuth();
    try {
        $this->validateCSRF(); // ← PRIMEIRA COISA
        // resto do código...
    }
}

// ❌ ERRADO - CSRF depois de outras ações
public function store() {
    $this->requireAuth();
    try {
        $name = $_POST['name'];
        $this->validateCSRF(); // ← TARDE DEMAIS
    }
}
```

**CHECKLIST DE SEGURANÇA (40% de chance de erro - VERIFICAR AGORA):**
```
[ ] Linha 1 do try é $this->validateCSRF()? (NADA ANTES)
[ ] Se for API: posso pular CSRF (usa JWT)
[ ] Se for Admin/Module: CSRF é OBRIGATÓRIO

⚠️ SE ALGUMA RESPOSTA FOR "NÃO", VOLTE E CORRIJA AGORA!
```

#### [2] RATE LIMITING - SEGUNDA COISA
```
[ ] Código adicionado:
  - RateLimiter::check('recurso_create', $ip, 5, 60)
  - RateLimiter::increment('recurso_create', $ip, 60)

[ ] Localização: Logo após CSRF, antes de validações

[ ] Limite correto:
  - store/update/destroy: 5 tentativas em 60s
  - API index: 60 tentativas em 60s

[ ] HTTP 429 retornado se bloqueado
```

**⛔ CHECKPOINT OBRIGATÓRIO - NÃO PROSSIGA SEM COMPLETAR:**

```php
// ✅ CORRETO - check() e increment()
$ip = $_SERVER['REMOTE_ADDR'];
if (!RateLimiter::check('banner_create', $ip, 5, 60)) {
    http_response_code(429);
    die('Muitas requisições. Aguarde 1 minuto.');
}

// ... código create ...

RateLimiter::increment('banner_create', $ip, 60); // ← DEPOIS do sucesso

// ❌ ERRADO - só check, sem increment
if (!RateLimiter::check(...)) { die(); }
// ... código ...
// ESQUECEU increment() ← 20% DE CHANCE DESSE ERRO!
```

**CHECKLIST DE SEGURANÇA (20% de chance de erro - VERIFICAR AGORA):**
```
[ ] RateLimiter::check() existe ANTES de validações?
[ ] HTTP 429 retornado se bloqueado?
[ ] RateLimiter::increment() existe DEPOIS do insert?
[ ] Nome da ação consistente ('banner_create' em ambos)?

⚠️ SE ESQUECEU increment(), VOLTE E ADICIONE AGORA!
```

#### [3] SANITIZAÇÃO - ANTES DE VALIDAR
```
[ ] Todos os inputs foram sanitizados:
  - Strings: Security::sanitize()
  - IDs/UUIDs: VALIDAR não sanitizar
  - Passwords: NÃO sanitizar, hash depois

Exemplo correto:
  $name = Security::sanitize($_POST['name'] ?? '');
  $email = strtolower(Security::sanitize($_POST['email'] ?? ''));
```

---

## PASSO 4B: UPLOAD DE ARQUIVOS (SE HOUVER)

### ⚠️ QUANDO USAR ESTE PASSO

**Use APENAS se seu CRUD tem upload de arquivos (imagem, PDF, etc).**

Se não tem upload, **pule para PASSO 4 item [4] VALIDAÇÕES**.

---

### 📁 Padrão de Diretórios AEGIS

**SEMPRE salvar em:**

```
/storage/uploads/[recurso_minusculo]/
```

**Exemplos:**
- Banners → `/storage/uploads/banners/`
- Blog posts → `/storage/uploads/blog/`
- Avatares de membros → `/storage/uploads/members/`
- Logos → `/storage/uploads/logos/`

**NÃO salvar em:**
- ❌ `/public/uploads/` (inseguro)
- ❌ `/assets/uploads/` (não é asset)
- ❌ `/uploads/` (sem organização)

---

### 🔐 Segurança de Upload (OBRIGATÓRIO)

#### Validações ANTES de Aceitar Upload

```php
// [1] Verificar se arquivo foi enviado
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    throw new Exception('Arquivo não enviado ou erro no upload');
}

// [2] Validar tamanho (exemplo: 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB em bytes
if ($_FILES['image']['size'] > $maxSize) {
    throw new Exception('Arquivo muito grande. Máximo: 5MB');
}

// [3] Validar tipo MIME (CRÍTICO - previne upload de malware)
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimes)) {
    throw new Exception('Tipo de arquivo não permitido. Use: JPG, PNG, GIF, WEBP');
}

// [4] Validar extensão (dupla proteção)
$extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($extension, $allowedExtensions)) {
    throw new Exception('Extensão não permitida');
}
```

---

### 📂 Criar Diretório de Upload (Se Não Existe)

```php
// Diretório base de uploads
$uploadDir = ROOT_PATH . 'storage/uploads/banners/';

// Criar diretório se não existir
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        throw new Exception('Erro ao criar diretório de upload');
    }
}

// Verificar permissões de escrita
if (!is_writable($uploadDir)) {
    throw new Exception('Diretório de upload sem permissão de escrita');
}
```

---

### 🏷️ Gerar Nome Único e Seguro

```php
// NUNCA usar nome original do arquivo (risco de segurança)
// SEMPRE gerar nome único

// [1] Gerar UUID para o nome
$fileId = Security::generateUUID();

// [2] Adicionar timestamp (evita colisões mesmo com UUID)
$timestamp = time();

// [3] Adicionar extensão validada
$fileName = $fileId . '_' . $timestamp . '.' . $extension;

// [4] Path completo
$filePath = $uploadDir . $fileName;

// Exemplo de resultado:
// /storage/uploads/banners/a1b2c3d4-5678-90ab-cdef-1234567890ab_1707847200.jpg
```

---

### 💾 Mover Arquivo com Segurança

```php
// [1] Mover arquivo do temp para destino final
if (!move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
    throw new Exception('Erro ao salvar arquivo');
}

// [2] Definir permissões corretas (OBRIGATÓRIO)
chmod($filePath, 0644);
// 0644 = owner read+write, group read, others read
// Apache consegue ler ✅
// Ninguém consegue executar ✅

// [3] Salvar APENAS o path relativo no banco (NÃO o path absoluto)
$relativeFilePath = '/storage/uploads/banners/' . $fileName;

// Exemplo:
// ✅ CORRETO: /storage/uploads/banners/abc123_1707847200.jpg
// ❌ ERRADO: /Users/fabio/Documents/aegis/storage/uploads/banners/abc123.jpg
```

---

### 🗄️ Salvar Path no Banco (NÃO o Arquivo)

```php
// IMPORTANTE: Salvar apenas o PATH, não o conteúdo do arquivo
$data = [
    'id' => $id,
    'title' => $title,
    'image' => $relativeFilePath,  // ← PATH, não arquivo
    'created_at' => date('Y-m-d H:i:s')
];

$this->db()->insert('banners', $data);
```

---

### 🔄 Update: Deletar Arquivo Antigo

**Se for UPDATE e usuário enviou novo arquivo:**

```php
// [1] Buscar registro atual
$banner = $this->db()->select('banners', ['id' => $id]);

// [2] Se tinha imagem antiga E nova foi enviada
if (!empty($banner[0]['image']) && !empty($_FILES['image']['tmp_name'])) {

    // [3] Deletar arquivo físico antigo
    $oldFilePath = ROOT_PATH . ltrim($banner[0]['image'], '/');

    if (file_exists($oldFilePath)) {
        unlink($oldFilePath);
    }
}

// [4] Processar novo upload (mesmo código do CREATE)
```

---

### 🗑️ Delete: Deletar Arquivo Físico

**No método destroy(), SEMPRE deletar arquivo físico:**

```php
// [1] Buscar registro ANTES de deletar (para ter path do arquivo)
$banner = $this->db()->select('banners', ['id' => $id]);

if (empty($banner)) {
    throw new Exception('Banner não encontrado');
}

// [2] Deletar arquivo físico PRIMEIRO
if (!empty($banner[0]['image'])) {
    $filePath = ROOT_PATH . ltrim($banner[0]['image'], '/');

    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// [3] Deletar registro do banco DEPOIS
$this->db()->delete('banners', ['id' => $id]);

// [4] Logger com snapshot (incluindo path do arquivo deletado)
Logger::getInstance()->audit('DELETE_BANNER', $this->getUser()['id'], [
    'banner_id' => $id,
    'title' => $banner[0]['title'],
    'image_path' => $banner[0]['image'],  // ← IMPORTANTE para auditoria
    'table' => 'banners'
]);
```

---

### ⛔ CHECKPOINT OBRIGATÓRIO - UPLOAD DE ARQUIVOS

**NÃO PROSSIGA SEM COMPLETAR TODOS OS CHECKS:**

```
🔒 SEGURANÇA:
[ ] Validação de tamanho implementada? (max 5MB)
[ ] Validação de MIME type implementada? (finfo_file)
[ ] Validação de extensão implementada? (dupla proteção)
[ ] Nome gerado com UUID + timestamp? (NUNCA nome original)
[ ] Permissões 0644 aplicadas? (chmod após move_uploaded_file)

📁 ESTRUTURA:
[ ] Diretório: /storage/uploads/[recurso]/?
[ ] Diretório criado se não existir? (mkdir 0755)
[ ] Verificação de is_writable()?

💾 BANCO DE DADOS:
[ ] Salvando PATH relativo (não absoluto)?
[ ] Coluna VARCHAR(255) ou TEXT?
[ ] Path começa com /storage/uploads/?

🗑️ LIMPEZA:
[ ] UPDATE deleta arquivo antigo se novo for enviado?
[ ] DELETE deleta arquivo físico + registro?
[ ] Logger de DELETE tem path do arquivo?

⚠️ SE ALGUM CHECK FALHOU, VOLTE E CORRIJA AGORA!
⚠️ UPLOAD SEM VALIDAÇÃO = VULNERABILIDADE CRÍTICA!
```

---

### 📋 Código Completo de Exemplo

```php
// PASSO 4B: Upload de imagem em store()
public function store() {
    $this->requireAuth();

    try {
        // [1] CSRF
        $this->validateCSRF();

        // [2] Rate Limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('banner_create', $ip, 5, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }

        // [3] Sanitização
        $title = Security::sanitize($_POST['title'] ?? '');

        // [4] UPLOAD DE ARQUIVO
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Imagem obrigatória');
        }

        // Validar tamanho (5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            throw new Exception('Imagem muito grande. Máximo: 5MB');
        }

        // Validar MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Tipo de arquivo não permitido');
        }

        // Validar extensão
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Extensão não permitida');
        }

        // Criar diretório se não existir
        $uploadDir = ROOT_PATH . 'storage/uploads/banners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Gerar nome único
        $fileId = Security::generateUUID();
        $fileName = $fileId . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        // Mover arquivo
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            throw new Exception('Erro ao salvar arquivo');
        }

        // Permissões
        chmod($filePath, 0644);

        // ⚡ [4B-EXTRA] OTIMIZAÇÃO DE IMAGEM (OBRIGATÓRIO PARA PERFORMANCE)
        $this->optimizeImage($filePath, $mimeType);

        // Path relativo para o banco
        $relativeFilePath = '/storage/uploads/banners/' . $fileName;

        // [5] Validações de campos
        if (empty($title)) {
            throw new Exception('Título obrigatório');
        }

        // [6] CREATE
        $id = Security::generateUUID();
        $this->db()->insert('banners', [
            'id' => $id,
            'title' => $title,
            'image' => $relativeFilePath,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // [7] Audit Log
        Logger::getInstance()->audit('CREATE_BANNER', $this->getUser()['id'], [
            'banner_id' => $id,
            'title' => $title,
            'image_path' => $relativeFilePath,
            'table' => 'banners'
        ]);

        // [8] Rate Limit Increment
        RateLimiter::increment('banner_create', $ip, 60);

        // [9] Feedback
        $this->success('Banner criado!');
        $this->redirect('/admin/banners');

    } catch (Exception $e) {
        Logger::getInstance()->warning('CREATE_BANNER_FAILED', [
            'reason' => $e->getMessage(),
            'user_id' => $this->getUser()['id']
        ]);
        $this->error($e->getMessage());
        $this->redirect('/admin/banners/create');
    }
}
```

---

---

### ⚡ PASSO 4B-EXTRA: OTIMIZAÇÃO DE IMAGEM (OBRIGATÓRIO)

**⚠️ Por que otimizar imagens:**

```
Sem otimização:
- Upload: JPG 5MB original
- Mobile 3G: 30s de carregamento
- Bounce rate: alto
- 1000 visitas = 5GB tráfego

Com otimização:
- Upload: JPG 5MB → 500KB (90% redução)
- Mobile 3G: 3s de carregamento
- Bounce rate: baixo
- 1000 visitas = 500MB tráfego (10x economia)
```

**Regra:** TODA imagem uploadada DEVE ser otimizada automaticamente.

---

#### 📋 IMPLEMENTAÇÃO: Método optimizeImage()

**Adicionar no Controller (ou criar helper):**

```php
/**
 * Otimizar imagem automaticamente
 *
 * @param string $filePath Caminho completo do arquivo
 * @param string $mimeType Tipo MIME da imagem
 * @return bool
 */
private function optimizeImage($filePath, $mimeType) {
    try {
        // [1] Criar resource baseado no tipo
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filePath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($filePath);
                break;
            default:
                return false; // Tipo não suportado
        }

        if (!$image) {
            return false;
        }

        // [2] Obter dimensões originais
        $width = imagesx($image);
        $height = imagesy($image);

        // [3] Redimensionar se muito grande (max 1920px width)
        $maxWidth = 1920;
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) ($height * ($maxWidth / $width));

            $resized = imagescale($image, $newWidth, $newHeight, IMG_BICUBIC);

            if ($resized) {
                imagedestroy($image);
                $image = $resized;
            }
        }

        // [4] Salvar com compressão otimizada
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                // Quality 85 = ótimo balanço qualidade/tamanho
                imagejpeg($image, $filePath, 85);
                break;
            case 'image/png':
                // Compression 6 = ótimo balanço (0-9)
                imagepng($image, $filePath, 6);
                break;
            case 'image/gif':
                imagegif($image, $filePath);
                break;
            case 'image/webp':
                // Quality 85 para WebP
                imagewebp($image, $filePath, 85);
                break;
        }

        // [5] Liberar memória
        imagedestroy($image);

        // [6] Aplicar permissões novamente
        chmod($filePath, 0644);

        return true;

    } catch (Exception $e) {
        // Log erro mas não quebra o upload
        error_log('Image optimization failed: ' . $e->getMessage());
        return false;
    }
}
```

---

#### 🎨 OTIMIZAÇÃO AVANÇADA: Gerar Versões Responsivas

**Para performance máxima, gerar múltiplas versões:**

```php
/**
 * Gerar versões responsivas da imagem
 *
 * @param string $filePath Caminho original
 * @param string $mimeType Tipo MIME
 */
private function generateResponsiveVersions($filePath, $mimeType) {
    // Carregar imagem original otimizada
    $image = $this->loadImage($filePath, $mimeType);
    if (!$image) return;

    $pathInfo = pathinfo($filePath);
    $dir = $pathInfo['dirname'];
    $filename = $pathInfo['filename'];
    $ext = $pathInfo['extension'];

    // Versões responsivas
    $versions = [
        'mobile'  => ['width' => 768,  'quality' => 80],
        'tablet'  => ['width' => 1200, 'quality' => 82],
        'desktop' => ['width' => 1920, 'quality' => 85]
    ];

    foreach ($versions as $name => $config) {
        $width = imagesx($image);

        if ($width > $config['width']) {
            $resized = imagescale($image, $config['width'], -1, IMG_BICUBIC);

            if ($resized) {
                $newPath = "{$dir}/{$filename}.{$name}.{$ext}";

                switch ($mimeType) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        imagejpeg($resized, $newPath, $config['quality']);
                        break;
                    case 'image/png':
                        imagepng($resized, $newPath, 6);
                        break;
                    case 'image/webp':
                        imagewebp($resized, $newPath, $config['quality']);
                        break;
                }

                imagedestroy($resized);
                chmod($newPath, 0644);
            }
        }
    }

    imagedestroy($image);
}

/**
 * Carregar imagem baseado no tipo MIME
 */
private function loadImage($filePath, $mimeType) {
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            return imagecreatefromjpeg($filePath);
        case 'image/png':
            return imagecreatefrompng($filePath);
        case 'image/gif':
            return imagecreatefromgif($filePath);
        case 'image/webp':
            return imagecreatefromwebp($filePath);
        default:
            return null;
    }
}
```

**Uso no HTML (srcset responsivo):**

```html
<img
    src="/storage/uploads/banners/abc.jpg"
    srcset="
        /storage/uploads/banners/abc.mobile.jpg 768w,
        /storage/uploads/banners/abc.tablet.jpg 1200w,
        /storage/uploads/banners/abc.desktop.jpg 1920w
    "
    sizes="(max-width: 768px) 100vw, (max-width: 1200px) 100vw, 1920px"
    alt="Banner"
    loading="lazy"
>
```

---

#### ⛔ CHECKPOINT: OTIMIZAÇÃO DE IMAGEM

```
[ ] Método optimizeImage() implementado no controller?
[ ] Chamado logo após move_uploaded_file()?
[ ] Redimensiona para max 1920px width?
[ ] Usa quality 85 para JPEG?
[ ] Usa compression 6 para PNG?
[ ] Libera memória com imagedestroy()?
[ ] Mantém permissões 0644 após otimização?
[ ] Testado com imagem 5MB (verifica redução)?
```

**❌ SE ALGUM ITEM FOR "NÃO", VOLTE E IMPLEMENTE!**

---

#### 📊 GANHOS ESPERADOS

**Antes (sem otimização):**
- JPG 5MB original
- Carregamento: 15-30s em 3G
- 1000 visitas = 5GB tráfego

**Depois (com otimização):**
- JPG 500KB (90% redução)
- Carregamento: 2-3s em 3G
- 1000 visitas = 500MB tráfego

**ROI:**
- 10x menos tráfego
- 10x mais rápido
- Melhor SEO (Core Web Vitals)
- Menor custo de storage/bandwidth

---

### ✅ Upload Implementado

**Se você completou este PASSO 4B + 4B-EXTRA:**
- ✅ Upload seguro implementado
- ✅ Validações de MIME + extensão + tamanho
- ✅ Arquivo salvo em `/storage/uploads/[recurso]/`
- ✅ **Imagem otimizada automaticamente (70-90% redução)**
- ✅ Path relativo salvo no banco
- ✅ Permissões corretas (0644)

**Próximo:** Continue para PASSO 4 item [4] VALIDAÇÕES

---

---

## ⛔ CHECKPOINTS FINAIS DE SEGURANÇA E PERFORMANCE

**Execute ANTES de finalizar qualquer CRUD. Use como checklist final.**

---

### 🔐 CHECKPOINT: SEGURANÇA AVANÇADA

```
PROTEÇÕES OBRIGATÓRIAS:
[ ] UUID validation em edit(), update(), destroy()?
[ ] CSRF validation como primeira linha em store/update/destroy?
[ ] Rate limiting com check() E increment()?
[ ] Path traversal protection antes de unlink()?
[ ] Sanitização de TODOS os inputs com Security::sanitize()?
[ ] Prepared statements em 100% das queries?
[ ] Backticks em reserved keywords (order, group, key, etc)?
[ ] Upload: validação MIME + extensão + tamanho?
[ ] Upload: arquivo salvo em /storage/uploads/?
[ ] Upload: permissões 0644 (arquivos) e 0755 (diretórios)?
[ ] Passwords: hash com Security::hashPassword()?
[ ] Audit log em CREATE, UPDATE, DELETE?

VALIDAÇÕES:
[ ] Campos obrigatórios validados (não vazios)?
[ ] Email validado com Security::validateEmail()?
[ ] Senha validada com Security::validatePasswordStrength()?
[ ] UUIDs relacionados validados com Security::isValidUUID()?
[ ] Strings: length máximo validado?
[ ] Números: range validado (min/max)?

EXPOSIÇÃO DE DADOS:
[ ] API não retorna campos sensíveis (password, tokens)?
[ ] Frontend SELECT não busca dados desnecessários?
[ ] Logs não incluem senhas ou dados sensíveis?
```

**Score esperado:** 20/20

---

### ⚡ CHECKPOINT: PERFORMANCE OBRIGATÓRIA

```
QUERIES:
[ ] ZERO ocorrências de "SELECT *"?
[ ] Todos os SELECTs especificam campos explicitamente?
[ ] Index() tem paginação (LIMIT/OFFSET)?
[ ] Paginação: COUNT(*) para total de registros?
[ ] Frontend: WHERE ativo=1 para filtrar inativos?
[ ] Índices apropriados na tabela (ativo, order, created_at)?

IMAGENS:
[ ] Upload: imagem otimizada com optimizeImage()?
[ ] Otimização: redimensiona para max 1920px?
[ ] Otimização: quality 85 (JPEG) ou compression 6 (PNG)?
[ ] Tamanho reduzido em 70-90%?

MEMÓRIA:
[ ] imagedestroy() após processamento de imagem?
[ ] Queries retornam apenas dados necessários?
[ ] Paginação limita resultado a 20-100 registros?

CACHE (OPCIONAL MAS RECOMENDADO):
[ ] Frontend display usa cache (5min)?
[ ] Cache invalidado em store/update/destroy?
[ ] Cache key único por recurso?
```

**Score esperado:** 12/12 obrigatórios + 3/3 opcionais = 15/15

---

### 📊 CHECKPOINT: ESCALABILIDADE

```
ARQUITETURA:
[ ] Separação clara admin/frontend/controllers/views?
[ ] BaseController extendido corretamente?
[ ] Rotas RESTful (GET/POST sem verbos na URL)?
[ ] Storage organizado por recurso (/uploads/banners/)?

PREPARADO PARA CRESCIMENTO:
[ ] Paginação suporta 10k+ registros?
[ ] SELECT específico reduz tráfego em 80%+?
[ ] Imagens otimizadas reduzem bandwidth em 90%+?
[ ] Índices permitem queries rápidas mesmo com volume alto?

FUTURO (não obrigatório agora):
[ ] Pronto para migrar uploads para S3?
[ ] Pronto para adicionar cache Redis?
[ ] Pronto para horizontal scaling (múltiplos servidores)?
```

**Score esperado:** 8/8 obrigatórios

---

### ✅ RESUMO DOS CHECKPOINTS

**ANTES de finalizar o CRUD, garantir:**

1. ✅ **Segurança:** 20/20 (100%)
2. ✅ **Performance:** 12/12 obrigatórios (100%)
3. ✅ **Escalabilidade:** 8/8 (100%)

**Score total esperado:** 40/40 (**100%**)

**Se score < 100%:** Voltar e corrigir itens faltantes!

---

**Próximo:** Continue para PASSO 4 item [4] VALIDAÇÕES

---

### 🔐 PASSO 4C: PROTEÇÃO PATH TRAVERSAL (OBRIGATÓRIO SE TEM UPLOAD)

**⚠️ CRÍTICO:** Se seu CRUD tem upload de arquivos e permite **UPDATE** ou **DELETE**, você **DEVE** proteger contra path traversal.

---

#### ❌ VULNERABILIDADE CRÍTICA

**Código VULNERÁVEL (update ou destroy):**

```php
// ❌ PERIGO - Aceita qualquer path do banco
public function update($id) {
    // ... validações ...

    // Buscar registro antigo
    $existing = $this->db()->query("SELECT * FROM banners WHERE id = ?", [$id])[0];

    // Deletar arquivo antigo
    $oldImage = $existing['image']; // Ex: "/storage/uploads/banners/abc.jpg"
    $fullPath = __DIR__ . '/../../' . $oldImage;

    unlink($fullPath); // ← VULNERÁVEL A PATH TRAVERSAL!

    // Upload novo arquivo...
}
```

**Ataque possível:**

```sql
-- Atacante modifica database diretamente ou explora outra falha:
UPDATE banners SET image = '../../config/database.php' WHERE id = '123';

-- Quando admin editar o banner 123:
unlink(__DIR__ . '/../../../../config/database.php'); ← DELETA CONFIG!
```

**Impacto:**
- ❌ Deletar arquivos críticos do sistema
- ❌ Deletar código fonte (.php)
- ❌ Deletar configurações (database.php, .env)
- ❌ Causar quebra total da aplicação

---

#### ✅ PROTEÇÃO OBRIGATÓRIA

**Adicione ANTES de qualquer unlink():**

```php
public function update($id) {
    $this->requireAuth();

    try {
        $this->validateCSRF();

        if (!Security::isValidUUID($id)) {
            throw new Exception('ID inválido');
        }

        // ... rate limiting, sanitização, validações ...

        // Buscar registro existente
        $existing = $this->db()->query(
            "SELECT * FROM banners WHERE id = ?",
            [$id]
        );

        if (empty($existing)) {
            throw new Exception('Banner não encontrado');
        }

        $oldImage = $existing[0]['image']; // Ex: "/storage/uploads/banners/abc.jpg"

        // ⛔ CHECKPOINT PATH TRAVERSAL - OBRIGATÓRIO
        // [1] Validar que path está dentro de /storage/uploads/
        if (!empty($oldImage) && file_exists(__DIR__ . '/../../' . $oldImage)) {
            $uploadBasePath = realpath(__DIR__ . '/../../storage/uploads/');
            $oldImageFullPath = realpath(__DIR__ . '/../../' . $oldImage);

            // Verificar se arquivo está DENTRO de /storage/uploads/
            if ($oldImageFullPath && strpos($oldImageFullPath, $uploadBasePath) === 0) {
                // SEGURO: Path está dentro de uploads
                unlink($oldImageFullPath);
            } else {
                // ATAQUE DETECTADO: Path fora de uploads
                Logger::getInstance()->critical('PATH_TRAVERSAL_ATTEMPT', [
                    'user_id' => $this->getUser()['id'],
                    'attempted_path' => $oldImage,
                    'resource_id' => $id
                ]);
                throw new Exception('Path inválido detectado');
            }
        }

        // ... upload novo arquivo, update database ...

    } catch (Exception $e) {
        // ...
    }
}
```

---

#### 📋 CHECKLIST PATH TRAVERSAL PROTECTION

**Aplicar em:**
- ✅ `update()` - Antes de deletar arquivo antigo
- ✅ `destroy()` - Antes de deletar arquivo do registro

**Código obrigatório:**
```php
// [1] Verificar se arquivo existe
if (!empty($oldFilePath) && file_exists(__DIR__ . '/../../' . $oldFilePath)) {

    // [2] Resolver paths absolutos
    $uploadBasePath = realpath(__DIR__ . '/../../storage/uploads/');
    $fileFullPath = realpath(__DIR__ . '/../../' . $oldFilePath);

    // [3] Validar que arquivo está DENTRO de /storage/uploads/
    if ($fileFullPath && strpos($fileFullPath, $uploadBasePath) === 0) {
        unlink($fileFullPath); // SEGURO
    } else {
        // Log ataque e rejeitar
        Logger::getInstance()->critical('PATH_TRAVERSAL_ATTEMPT', [...]);
        throw new Exception('Path inválido');
    }
}
```

**Como funciona:**
- `realpath()` resolve `..` e symlinks para path absoluto
- `strpos($fileFullPath, $uploadBasePath) === 0` verifica se arquivo começa com `/storage/uploads/`
- Se arquivo estiver fora → Log + Exception

---

#### ⛔ CHECKPOINT OBRIGATÓRIO

```
[ ] Código tem unlink() ou delete de arquivos?
    └─ SIM → Aplicar proteção path traversal ANTES
    └─ NÃO → Pode pular PASSO 4C

[ ] Validação de path está ANTES do unlink()?
[ ] Usa realpath() para resolver path absoluto?
[ ] Usa strpos() para verificar se está dentro de /storage/uploads/?
[ ] Loga tentativas de path traversal como CRITICAL?
[ ] Throw exception se path for inválido?
```

**❌ SE ALGUM ITEM FOR "NÃO", VOLTE E CORRIJA AGORA!**

---

#### 📊 EXEMPLO COMPLETO: destroy() com Proteção

```php
public function destroy($id) {
    $this->requireAuth();

    try {
        $this->validateCSRF();

        if (!Security::isValidUUID($id)) {
            throw new Exception('ID inválido');
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        if (!RateLimiter::check('banner_delete', $ip, 5, 60)) {
            http_response_code(429);
            die('Muitas requisições');
        }

        // Buscar registro
        $banners = $this->db()->query("SELECT * FROM banners WHERE id = ?", [$id]);

        if (empty($banners)) {
            throw new Exception('Banner não encontrado');
        }

        $banner = $banners[0];
        $imagePath = $banner['image'];

        // ⛔ PATH TRAVERSAL PROTECTION
        if (!empty($imagePath) && file_exists(__DIR__ . '/../../' . $imagePath)) {
            $uploadBasePath = realpath(__DIR__ . '/../../storage/uploads/');
            $imageFullPath = realpath(__DIR__ . '/../../' . $imagePath);

            if ($imageFullPath && strpos($imageFullPath, $uploadBasePath) === 0) {
                unlink($imageFullPath);
            } else {
                Logger::getInstance()->critical('PATH_TRAVERSAL_ATTEMPT_DELETE', [
                    'user_id' => $this->getUser()['id'],
                    'banner_id' => $id,
                    'attempted_path' => $imagePath
                ]);
                throw new Exception('Path inválido detectado');
            }
        }

        // Deletar do banco
        $this->db()->query("DELETE FROM banners WHERE id = ?", [$id]);

        Logger::getInstance()->audit('DELETE_BANNER', $this->getUser()['id'], [
            'banner_id' => $id,
            'image_deleted' => $imagePath
        ]);

        RateLimiter::increment('banner_delete', $ip, 60);

        $this->success('Banner deletado com sucesso!');
        $this->redirect('/admin/banners');

    } catch (Exception $e) {
        Logger::getInstance()->warning('DELETE_BANNER_FAILED', [
            'reason' => $e->getMessage(),
            'banner_id' => $id ?? 'unknown',
            'user_id' => $this->getUser()['id']
        ]);

        $this->error($e->getMessage());
        $this->redirect('/admin/banners');
    }
}
```

---

**Próximo:** Continue para PASSO 4 item [4] VALIDAÇÕES

---

#### [4] VALIDAÇÕES - EM SEQUÊNCIA
```
[ ] Campos obrigatórios
  - if (empty($name)) throw new Exception('...')
  - Fazer para CADA campo obrigatório

[ ] Email (se houver campo email)
  - Security::validateEmail($email)
  - Verificar unicidade: $db->select('table', ['email' => $email])

[ ] Senha (se houver campo password)
  - Security::validatePasswordStrength($password)
  - Mínimo 8 caracteres, maiúscula, minúscula, número, especial

[ ] UUID (se houver IDs relacionados)
  - Security::isValidUUID($categoryId)
  - Verificar existência: $db->select('categories', ['id' => $categoryId])

[ ] Strings (tamanho)
  - strlen($name) >= 3 && strlen($name) <= 255

[ ] Slug (se houver)
  - preg_match('/^[a-z0-9-]+$/', $slug)
  - Verificar unicidade: $db->select('table', ['slug' => $slug])

[ ] Arrays (se houver multi-select)
  - is_array($items)
  - Filtrar cada elemento: Security::isValidUUID($id)
  - Verificar quantity: count($items) > 0
```

#### [5] CREATE - INSERIR NO BD
```
[ ] Gerar UUID para novo ID
  - $id = Security::generateUUID()

[ ] Preparar dados (apenas sanitizados/validados)
  - $data = ['id' => $id, 'name' => $name, 'email' => $email, ...]

[ ] Inserir com prepared statements
  - $db->insert('table', $data)
  - OU $db->query("INSERT INTO ... VALUES (...)", [$bindings])

[ ] Sem concatenação de strings em SQL
```

#### [6] AUDIT LOG - LOGO APÓS CREATE
```
[ ] Logger::getInstance()->audit() adicionado
  - Nome de ação: CREATE_RECURSO (maiúsculas, singular)
  - Exemplo: CREATE_CATEGORY, CREATE_MEMBER, CREATE_POST

[ ] Parâmetros corretos:
  Logger::getInstance()->audit('CREATE_CATEGORY', Auth::userId(), [
    'category_id' => $categoryId,
    'name' => $name,
    'table' => 'categories'
  ]);

[ ] Campos obrigatórios presentes:
  - resource_id ✓
  - table ✓
  - contexto relevante (name, email, etc) ✓

[ ] user_id e ip são automáticos (Logger adiciona)
```

**⛔ CHECKPOINT OBRIGATÓRIO - NÃO PROSSIGA SEM COMPLETAR:**

```php
// ✅ CORRETO - Logger completo
$id = Security::generateUUID();
$this->db()->insert('banners', ['id' => $id, 'title' => $title]);

Logger::getInstance()->audit('CREATE_BANNER', $this->getUser()['id'], [
    'banner_id' => $id,  // ← resource_id
    'title' => $title,    // ← contexto
    'table' => 'banners'  // ← OBRIGATÓRIO
]);

// ❌ ERRADO - esqueceu Logger
$this->db()->insert('banners', ['id' => $id, 'title' => $title]);
// NADA AQUI ← 15% DE CHANCE DESSE ERRO!
```

**CHECKLIST DE AUDITORIA (15% de chance de erro - VERIFICAR AGORA):**
```
[ ] Logger::getInstance()->audit() existe DEPOIS do insert?
[ ] Nome: CREATE_[RECURSO] (maiúsculas, singular)?
[ ] Parâmetro 1: user_id correto?
[ ] Array tem 'resource_id' (banner_id, category_id, etc)?
[ ] Array tem 'table' com nome da tabela?
[ ] Array tem contexto relevante (title, name, etc)?

⚠️ SE LOGGER NÃO EXISTE, VOLTE E ADICIONE AGORA!
⚠️ SEM LOGGER = SEM AUDITORIA = SISTEMA CEGO!
```

#### [7] INCREMENT RATE LIMIT - APÓS LOG
```
[ ] Código adicionado:
  - RateLimiter::increment('recurso_create', $ip, 60)

[ ] Localização: APÓS db->insert() bem-sucedido
```

#### [8] FEEDBACK & REDIRECT
```
[ ] Mensagem de sucesso
  - Admin/Module: $this->success() ou $_SESSION['success']
  - API: json(201, ['success' => true, 'data' => ...])

[ ] Redirect/Response
  - Admin/Module: $this->redirect() ou Core::redirect()
  - API: return $this->json(201, [...]) e exit
```

#### [9] EXCEPTION HANDLING
```
[ ] catch (Exception $e) adicionado

[ ] Logger::getInstance()->warning() para falha
  Logger::getInstance()->warning('CREATE_CATEGORY_FAILED', [
    'reason' => $e->getMessage(),
    'user_id' => Auth::userId()
  ]);

[ ] Feedback de erro
  - Admin/Module: $this->error() ou $_SESSION['error']
  - API: json(400, ['success' => false, 'error' => $e->getMessage()])

[ ] Redirect/Response apropriado
```

### Validação Final de store()
```
SEGURANÇA:
[ ] CSRF validation presente
[ ] RateLimit check + increment
[ ] Prepared statements (sem SQL injection)
[ ] Inputs sanitizados
[ ] Outputs escapados em views

AUDITORIA:
[ ] Logger::audit() presente
[ ] Nome de ação correto (CREATE_*)
[ ] Campos: resource_id, table, contexto

VALIDAÇÃO:
[ ] Empty checks
[ ] Email validation + uniqueness
[ ] UUID validation + existence
[ ] String sizes
[ ] Slug (pattern + uniqueness)
```

**Próximo**: Passo 5

---

## PASSO 5: IMPLEMENTAR MÉTODO edit() [Admin/Module APENAS]

### Checklist de Implementação
```
[ ] Autenticação
  - Auth::require() ou $this->requireAuth()

[ ] UUID validation do parâmetro
  - if (!Security::isValidUUID($id)) throw/redirect

[ ] Database select
  - $record = $db->select('table', ['id' => $id])
  - if (empty($record)) throw/redirect

[ ] Dados relacionados buscados (se necessário)

[ ] View renderizada com dados do registro
```

### Validação Rápida
```
[ ] Não há database write aqui
[ ] UUID validado
[ ] Registro verificado se existe
```

**Próximo**: Passo 6

---

## PASSO 6: IMPLEMENTAR MÉTODO update()

### Checklist em Ordem Rigorosa

**Praticamente idêntico ao store(), com diferenças:**

#### [1] CSRF - PRIMEIRA COISA
```
[ ] Validação adicionada
```

#### [2] UUID VALIDATION - SEGUNDA COISA
```
[ ] if (!Security::isValidUUID($id)) throw new Exception(...)
[ ] Localização: ANTES de qualquer database query
```

#### [3] RATE LIMITING
```
[ ] RateLimiter::check('recurso_update', $ip, 10, 60)
[ ] Limite um pouco mais permissivo (10 vs 5)
```

#### [4] SANITIZAÇÃO
```
[ ] Mesma coisa que em store()
```

#### [5] VALIDAÇÕES
```
[ ] Campos obrigatórios: SIM
[ ] Email: 
  - Security::validateEmail()
  - Uniqueness: WHERE email = ? AND id != ? (EXCLUIR O PRÓPRIO)
[ ] Senha: APENAS se preenchida
[ ] Strings, UUIDs, Slug: mesmo que store()
```

#### [6] UPDATE - NO BD
```
[ ] Preparar dados com APENAS campos que mudam
  - Não incluir 'id', 'created_at'
  - Incluir 'updated_at': date('Y-m-d H:i:s')

[ ] $db->update('table', $data, ['id' => $id])
  - OU $db->query("UPDATE table SET ... WHERE id = ?", [...])

[ ] SEM concatenação de strings
```

#### [7] AUDIT LOG
```
[ ] Logger::getInstance()->audit('UPDATE_RECURSO', Auth::userId(), [
  'resource_id' => $id,
  'fields_updated' => array_keys($data),
  'table' => 'table'
]);

[ ] Incluir QUAIS campos foram alterados (array de chaves)
```

#### [8] INCREMENT RATE LIMIT
```
[ ] RateLimiter::increment('recurso_update', $ip, 60)
```

#### [9] FEEDBACK & REDIRECT
```
[ ] Sucesso com mensagem
[ ] Erro com mensagem e redirect de volta
```

### Validação Final de update()
```
SEGURANÇA:
[ ] CSRF validation
[ ] UUID validation
[ ] RateLimit check + increment
[ ] Prepared statements
[ ] Email uniqueness exclui próprio (id != ?)
[ ] Senha validada apenas se preenchida

AUDITORIA:
[ ] Logger::audit('UPDATE_*')
[ ] Campos alterados inclusos

VALIDAÇÃO:
[ ] Todos os checks como em store()
```

**Próximo**: Passo 7

---

## PASSO 7: IMPLEMENTAR MÉTODO destroy() [DELETE]

### Checklist em Ordem Rigorosa

#### [1] CSRF - PRIMEIRA COISA
```
[ ] Validação adicionada
```

#### [2] UUID VALIDATION
```
[ ] if (!Security::isValidUUID($id)) throw/redirect
```

#### [3] RATE LIMITING
```
[ ] RateLimiter::check('recurso_delete', $ip, 5, 60)
```

#### [4] SELECT & VALIDATE EXISTENCE
```
[ ] $record = $db->select('table', ['id' => $id])
[ ] if (empty($record)) throw/redirect
[ ] ⚠️ IMPORTANTE: guardar $record para audit log depois
```

#### [5] VALIDAÇÕES ADICIONAIS (se houver)
```
[ ] Não pode deletar admin principal?
[ ] Não pode deletar se tem relacionamentos?
[ ] Adicionar checks específicos do recurso
```

#### [6] DELETE - NO BD
```
[ ] $db->delete('table', ['id' => $id])
  - OU $db->query("DELETE FROM table WHERE id = ?", [$id])

[ ] SEM concatenação de strings
```

#### [7] AUDIT LOG - COM SNAPSHOT
```
[ ] Logger::getInstance()->audit('DELETE_RECURSO', Auth::userId(), [
  'resource_id' => $id,
  'name' => $record['name'],        ← dados antes de deletar
  'email' => $record['email'],      ← snapshot completo
  'table' => 'table'
]);

[ ] MUITO IMPORTANTE: incluir dados do registro deletado
   (para poder recuperar/auditar depois)
```

#### [8] INCREMENT RATE LIMIT
```
[ ] RateLimiter::increment('recurso_delete', $ip, 60)
```

#### [9] FEEDBACK & REDIRECT
```
[ ] Sucesso com mensagem
[ ] Erro com mensagem
```

### Validação Final de destroy()
```
SEGURANÇA:
[ ] CSRF validation
[ ] UUID validation
[ ] RateLimit check + increment
[ ] Prepared statements

AUDITORIA:
[ ] Logger::audit('DELETE_*')
[ ] Snapshot de dados do registro deletado

VALIDAÇÃO:
[ ] UUID validado
[ ] Registro verificado se existe
```

**Próximo**: Passo 8

---

## PASSO 8A: CRIAR VIEWS (Admin CRUD)

### 📋 Usar Templates Prontos (RECOMENDADO)

**AEGIS possui templates prontos com padrão correto:**

**Localização:** `/docs/crud/templates/views/`

```
index.template.php   → Listagem com tabela
create.template.php  → Formulário de criação
edit.template.php    → Formulário de edição
```

**Como usar:**

1. **Copiar template para seu CRUD:**
```bash
cp docs/crud/templates/views/index.template.php admin/views/banners/index.php
cp docs/crud/templates/views/create.template.php admin/views/banners/create.php
cp docs/crud/templates/views/edit.template.php admin/views/banners/edit.php
```

2. **Substituir placeholders:**
```
{{RESOURCE_PLURAL}} → "Banners Hero"
{{RESOURCE_SINGULAR}} → "Banner"
{{resource_slug}} → "banners"
{{resource_var_plural}} → $banners
{{resource_var_singular}} → $banner
```

3. **Personalizar campos:**
- Ajustar colunas da tabela (index.php)
- Ajustar campos do formulário (create.php, edit.php)
- Remover campos de exemplo não necessários

**✅ Vantagens dos templates:**
- Padrão AEGIS já aplicado (classes corretas)
- Ícones Lucide incluídos
- Alerts configurados
- CSRF token incluído
- Instruções de uso no final do arquivo
- Exemplos de todos os tipos de campo

**📚 Cada template tem instruções completas no final do arquivo!**

---

### Admin Controllers Usam Sempre `$this->render()`

**Para Admin CRUD, SEMPRE use:**

```php
$this->render('banners/index', ['data' => $data]);
// Procura em: ROOT_PATH . 'admin/views/banners/index.php'
```

### Checklist de Renderização

```
[ ] Admin controller → usar $this->render()

[ ] Path da view correto:
    - render('banners/index') → /admin/views/banners/index.php

[ ] Diretório da view existe?
    mkdir -p /admin/views/banners

[ ] Arquivo .php criado com nome correto (minúsculo)
    index.php (não Index.php)
```

### ⚠️ CRÍTICO: Permissões de Arquivo

**Se criar view com permissões erradas, Apache NÃO consegue ler → ERROR 500 silencioso**

**Sempre que criar arquivo de view:**

```bash
chmod 644 /admin/views/banners/index.php
```

**Permissões corretas:**
- `644` = owner read+write, group read, others read
- Apache consegue ler ✅
- Owner consegue editar ✅

**Se esquecer:**
- Arquivo fica `600` (só owner consegue ler)
- Apache não consegue ler
- Resultado: ERROR 500 "View not found" ou silencioso
- Debug: muito difícil de encontrar

**⛔ CHECKPOINT OBRIGATÓRIO - NÃO PROSSIGA SEM COMPLETAR:**

```bash
# ✅ CORRETO - chmod 644 IMEDIATAMENTE após criar
touch /admin/views/banners/index.php
chmod 644 /admin/views/banners/index.php  # ← OBRIGATÓRIO

# ❌ ERRADO - esqueceu chmod
touch /admin/views/banners/index.php
# NADA ← 30% DE CHANCE DESSE ERRO!
# Resultado: ERROR 500 silencioso
```

**CHECKLIST OBRIGATÓRIO (30% de chance de erro - VERIFICAR AGORA):**
```
[ ] Arquivo view criado?
[ ] CHMOD 644 executado? (USE O COMANDO AGORA)
[ ] Testado no browser: não há erro 500?
[ ] Se erro 500, EXECUTAR: chmod 644 [arquivo]

⚠️ TESTE AGORA: curl http://localhost:5757/aegis/admin/banners
⚠️ SE DER ERRO 500, PRIMEIRA COISA: chmod 644 na view!
```

### Estrutura Básica de View

**Exemplo: /admin/views/banners/index.php**

```php
<?php
// Dados passados via render()
// $banners já está disponível aqui
?>

<div class="container">
    <div class="header">
        <h1>Banners</h1>
        <a href="<?= url('admin/banners/create') ?>" class="btn">+ Novo</a>
    </div>

    <?php if (!empty($banners)): ?>
        <table>
            <?php foreach ($banners as $banner): ?>
                <tr>
                    <td><?= htmlspecialchars($banner['title']) ?></td>
                    <td>
                        <a href="<?= url('admin/banners/' . $banner['id'] . '/edit') ?>">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nenhum banner encontrado. <a href="<?= url('admin/banners/create') ?>">Criar o primeiro?</a></p>
    <?php endif; ?>
</div>
```

---

### Exemplo: create.php

```php
<?php
// Dados: $user (disponível automaticamente)
?>

<div class="container">
    <h1>Novo Banner</h1>
    <p><a href="<?= url('admin/banners') ?>">← Voltar</a></p>

    <form method="POST" action="<?= url('admin/banners') ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

        <label>Título *</label>
        <input type="text" name="title" required maxlength="255">

        <label>Imagem *</label>
        <input type="file" name="image" required accept="image/*">

        <label>
            <input type="checkbox" name="ativo" checked>
            Ativo
        </label>

        <button type="submit">Criar</button>
        <a href="<?= url('admin/banners') ?>">Cancelar</a>
    </form>
</div>
```

---

### Exemplo: edit.php

```php
<?php
// Dados: $banner, $user
?>

<div class="container">
    <h1>Editar Banner</h1>
    <p><a href="<?= url('admin/banners') ?>">← Voltar</a></p>

    <form method="POST" action="<?= url('admin/banners/' . $banner['id']) ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

        <label>Título *</label>
        <input type="text" name="title" value="<?= htmlspecialchars($banner['title']) ?>" required>

        <label>Imagem Atual:</label>
        <img src="<?= htmlspecialchars($banner['image']) ?>" width="200">

        <label>Nova Imagem (deixe vazio para manter)</label>
        <input type="file" name="image" accept="image/*">

        <label>
            <input type="checkbox" name="ativo" <?= $banner['ativo'] ? 'checked' : '' ?>>
            Ativo
        </label>

        <button type="submit">Salvar</button>
        <a href="<?= url('admin/banners') ?>">Cancelar</a>
    </form>
</div>
```

---

### ⚠️ CRÍTICO: CSRF Token

**TODAS as views com `<form method="POST">` DEVEM ter:**

```php
<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
```

**❌ ERRADO:**
- `Security::generateCSRFToken()` (método não existe)
- `htmlspecialchars(Security::generateCSRF())` (desnecessário)
- Esquecer o token (form será bloqueado)

**✅ CORRETO:**
- `Security::generateCSRF()` (sem htmlspecialchars)

---

### Erros Comuns

| Erro | Causa | Solução |
|------|-------|---------|
| 500 ao acessar | Permissão do arquivo | `chmod 644` |
| 500 no create | `generateCSRFToken()` errado | Usar `generateCSRF()` |
| "View not found" | Path incorreto | Verificar `render()` path |
| Variáveis não aparecem | Não passou em `render()` | Passar array `['banners' => $data]` |
| XSS na página | Não sanitizou output | Sempre usar `htmlspecialchars()` |
| CSRF block | Token ausente/errado | Adicionar `Security::generateCSRF()` |

---

## PASSO 8: VERIFICAÇÃO FINAL

### 🔒 GATE DE SEGURANÇA - BLOQUEIO TOTAL ATÉ COMPLETAR

**⛔ VOCÊ NÃO PODE PROSSEGUIR PARA PASSO 9 ATÉ COMPLETAR 100% DESTE CHECKPOINT**

Esta é sua última chance de evitar criar código inseguro. **SE VOCÊ PULAR ESTE PASSO, SEU CRUD ESTARÁ VULNERÁVEL.**

---

### 🚨 CHECKPOINT 1: SEGURANÇA CRÍTICA (OBRIGATÓRIO)

**ESTES 4 CHECKS BLOQUEIAM O PROSSEGUIMENTO. NÃO CONTINUE SE ALGUM ESTIVER FALTANDO.**

```
🔴 CSRF VALIDATION (store/update/destroy):
[ ] store() tem $this->validateCSRF() como PRIMEIRA LINHA?
[ ] update() tem $this->validateCSRF() como PRIMEIRA LINHA?
[ ] destroy() tem $this->validateCSRF() como PRIMEIRA LINHA?
⚠️ SE ALGUM "NÃO": PARE AGORA E ADICIONE!

🔴 RATE LIMITING (store/update/destroy):
[ ] store() tem RateLimiter::check() ANTES de validações?
[ ] store() tem RateLimiter::increment() DEPOIS do insert?
[ ] update() tem check + increment?
[ ] destroy() tem check + increment?
⚠️ SE ALGUM "NÃO": PARE AGORA E ADICIONE!

🔴 AUDIT LOGGING (store/update/destroy):
[ ] store() tem Logger::audit('CREATE_*') DEPOIS do insert?
[ ] update() tem Logger::audit('UPDATE_*') DEPOIS do update?
[ ] destroy() tem Logger::audit('DELETE_*') DEPOIS do delete?
[ ] destroy() Logger tem snapshot de dados deletados?
⚠️ SE ALGUM "NÃO": PARE AGORA E ADICIONE!

🔴 SQL INJECTION PREVENTION (TODOS métodos):
[ ] TODAS queries usam prepared statements (?)?
[ ] NENHUMA query concatena strings com variáveis?
[ ] Se tem reserved keyword: TODAS ocorrências têm backticks?
⚠️ SE ALGUM "NÃO": PARE AGORA E CORRIJA!
```

**❌ SE QUALQUER UM DOS 4 CHECKS ACIMA FALHOU: NÃO PROSSIGA**

**✅ SE TODOS OS 4 CHECKS PASSARAM: CONTINUE PARA CHECKPOINT 2**

---

### 🟡 CHECKPOINT 2: VALIDAÇÕES E FEEDBACK (OBRIGATÓRIO)

```
VALIDAÇÃO (store/update):
[ ] Empty checks para campos obrigatórios?
[ ] Email: Security::validateEmail() + uniqueness?
[ ] Senha: Security::validatePasswordStrength() (create)?
[ ] Senha: validada apenas se preenchida (update)?
[ ] UUID: Security::isValidUUID() + verificação de existência?
[ ] Strings: strlen() min/max verificados?
[ ] Slug: regex pattern + uniqueness?
⚠️ SE ALGUM "NÃO": CORRIJA ANTES DE PROSSEGUIR!

FEEDBACK E EXCEPTION HANDLING:
[ ] Todos métodos têm try/catch?
[ ] catch tem Logger::warning('[ACTION]_FAILED')?
[ ] Sucesso tem mensagem clara?
[ ] Erro tem mensagem clara?
[ ] Redirect/Response apropriado?
⚠️ SE ALGUM "NÃO": ADICIONE ANTES DE PROSSEGUIR!
```

**❌ SE CHECKPOINT 2 FALHOU: NÃO PROSSIGA**

**✅ SE CHECKPOINT 2 PASSOU: CONTINUE PARA CHECKPOINT 3**

---

### 🟢 CHECKPOINT 3: ESTRUTURA E NOMENCLATURA (OBRIGATÓRIO)

```
ESTRUTURA GERAL:
[ ] Arquivo tem EXATAMENTE 6 métodos (index, create, store, edit, update, destroy)?
[ ] Herança correta (BaseController)?
[ ] Auth presente em TODOS métodos?
[ ] Nenhuma função duplicada?

NOMENCLATURA CONSISTENTE:
[ ] Logger actions: CREATE_[RECURSO], UPDATE_[RECURSO], DELETE_[RECURSO]?
[ ] RateLimiter keys: recurso_create, recurso_update, recurso_delete?
[ ] Tudo em MAIÚSCULAS e SINGULAR?
⚠️ SE ALGUM "NÃO": CORRIJA AGORA!
```

**❌ SE CHECKPOINT 3 FALHOU: NÃO PROSSIGA**

**✅ SE CHECKPOINT 3 PASSOU: VOCÊ ESTÁ LIBERADO PARA PASSO 9**

---

### ✅ APROVAÇÃO FINAL

**SE VOCÊ CHEGOU AQUI E TODOS OS 3 CHECKPOINTS PASSARAM:**

```
🎉 SEU CONTROLLER ESTÁ SEGURO E COMPLETO!
👉 PODE PROSSEGUIR PARA PASSO 9 (Adicionar Routes)
```

**SE ALGUM CHECKPOINT FALHOU:**

```
⛔ VOLTE E CORRIJA OS ERROS IMEDIATAMENTE
⛔ NÃO TENTE CONTINUAR SEM 100% DOS CHECKS
⛔ CÓDIGO INCOMPLETO = VULNERABILIDADES = ATAQUE
```

### Teste Rápido (Opcional)
```
[ ] Abra arquivo no editor
[ ] Procure por: 
  - "validateCSRF" → deve estar em store/update/destroy
  - "RateLimiter" → deve estar em store/update/destroy
  - "Logger::audit" → deve estar em store/update/destroy
  - "SELECT" → deve ter ? ou prepared statement

[ ] Se faltar algo: volte ao passo correspondente
```

---

## PASSO 9: ADICIONAR ROUTES

### Onde Adicionar as Rotas

**Se for ADMIN CRUD:**
- Arquivo: `/routes/admin.php`
- Prefixo: `/admin/`

**Se for FRONTEND/PUBLIC:**
- Arquivo: `/routes/public.php`
- Prefixo: `/` (nenhum)

**Se for API:**
- Arquivo: `/routes/api.php`
- Prefixo: `/api/v1/` (versionado)

### Padrão de URLs REST

**Para recurso "banners" (exemplo):**

```php
// INDEX - Listar todos
GET /admin/banners

// CREATE - Formulário criar
GET /admin/banners/create

// STORE - Salvar novo
POST /admin/banners

// EDIT - Formulário editar
GET /admin/banners/:id/edit

// UPDATE - Salvar edição
POST /admin/banners/:id

// DESTROY - Deletar
POST /admin/banners/:id/delete
```

### Implementação Completa de Routes

**Adicione em `/routes/admin.php` (ou public.php):**

```php
// ================================================
// BANNERS - Carrossel de banners rotativos
// ================================================

// INDEX: Listar banners
Router::get('/admin/banners', function() {
    $controller = new BannerController();
    $controller->index();
});

// CREATE: Exibir formulário criar
Router::get('/admin/banners/create', function() {
    $controller = new BannerController();
    $controller->create();
});

// STORE: Processar criação
Router::post('/admin/banners', function() {
    $controller = new BannerController();
    $controller->store();
});

// EDIT: Exibir formulário editar
Router::get('/admin/banners/:id/edit', function($id) {
    $controller = new BannerController();
    $controller->edit($id);
});

// UPDATE: Processar edição
Router::post('/admin/banners/:id', function($id) {
    $controller = new BannerController();
    $controller->update($id);
});

// DESTROY: Processar deleção
Router::post('/admin/banners/:id/delete', function($id) {
    $controller = new BannerController();
    $controller->destroy($id);
});
```

### Checklist de Routes

```
[ ] Arquivo correto (admin.php ou public.php ou api.php)
[ ] 6 rotas adicionadas (index, create, store, edit, update, destroy)
[ ] GET para pages (index, create, edit)
[ ] POST para actions (store, update, destroy)
[ ] :id com parametro em URL (edit, update, destroy)
[ ] Função lambda chama controller correto
[ ] Parametro $id passado onde necessário (edit, update, destroy)
[ ] Nomes de URL consistentes e em inglês
[ ] Prefixo correto (/admin/, /public/, /api/)
```

### Exemplos Reais

**ADMIN CRUD (ex: Categories)**
```php
Router::get('/admin/categories', function() { ... });
Router::get('/admin/categories/create', function() { ... });
Router::post('/admin/categories', function() { ... });
Router::get('/admin/categories/:id/edit', function($id) { ... });
Router::post('/admin/categories/:id', function($id) { ... });
Router::post('/admin/categories/:id/delete', function($id) { ... });
```

**PUBLIC RESOURCE (ex: Blog posts)**
```php
Router::get('/posts', function() { ... });
Router::get('/posts/:slug', function($slug) { ... });
```

**API (ex: Users)**
```php
Router::get('/api/v1/users', function() { ... });
Router::post('/api/v1/users', function() { ... });
Router::get('/api/v1/users/:id', function($id) { ... });
Router::put('/api/v1/users/:id', function($id) { ... });
Router::delete('/api/v1/users/:id', function($id) { ... });
```

---

## PASSO 10: ADICIONAR ROUTES

Já coberto no Passo 9 acima.

---

## PASSO 11: SEU ADMIN CRUD ESTÁ PRONTO! 🎉

### ✅ Parabéns! Você Completou o Admin CRUD

**Neste ponto, você tem:**
- ✅ Controller Admin completo em `/admin/controllers/`
- ✅ 6 métodos funcionando (index, create, store, edit, update, destroy)
- ✅ Views Admin em `/admin/views/`
- ✅ Rotas em `/routes/admin.php`
- ✅ CRUD 100% funcional

**Seu Admin CRUD está COMPLETO e PRONTO para uso.**

---

### ⛔ CHECKPOINT OBRIGATÓRIO - NÃO PROSSIGA SEM COMPLETAR

**🤖 ATENÇÃO CLAUDE: ESTE CHECKPOINT É OBRIGATÓRIO!**

**ANTES de ir para PASSO 12, você DEVE fazer:**

#### 1️⃣ RELER a resposta da PERGUNTA 6️⃣ do PASSO -2:

```
6️⃣ Vai ter DISPLAY FRONTEND?
   Resposta do usuário: _________________
```

#### 2️⃣ VERIFICAR a resposta:

**SE a resposta foi:**
- ✅ "SIM"
- ✅ "Sim, na home"
- ✅ "Sim, na página X"
- ✅ Qualquer variação de "sim" ou menção a frontend/página

**ENTÃO:**
```
🚨 PASSO 11B É OBRIGATÓRIO!
→ NÃO é opcional
→ NÃO pode pular
→ Usuário solicitou frontend no PASSO -2
→ Você DEVE executar PASSO 11B completo (6 sub-passos)
→ IR PARA PASSO 11B AGORA
```

**SE a resposta foi:**
- ❌ "NÃO"
- ❌ "Só admin"
- ❌ "Não precisa"

**ENTÃO:**
```
✅ PASSO 11B é realmente opcional
→ Pode pular para PASSO 12
→ Mas PERGUNTE ao usuário antes:
   "Seu CRUD está pronto! Deseja que eu crie também
   o componente frontend reutilizável com preview
   no admin? (Leva ~2min)"
```

#### 3️⃣ REGRA DE OURO:

```python
# PSEUDOCÓDIGO PARA CLAUDE

resposta_pergunta_6 = obter_resposta_passo_2_pergunta_6()

if "sim" in resposta_pergunta_6.lower() or
   "frontend" in resposta_pergunta_6.lower() or
   "home" in resposta_pergunta_6.lower() or
   "página" in resposta_pergunta_6.lower():

    print("🚨 ALERTA: Usuário solicitou frontend!")
    print("📋 PASSO 11B é OBRIGATÓRIO")
    print("❌ NÃO pode pular")
    goto PASSO_11B

else:
    print("❓ Frontend não foi solicitado no PASSO -2")
    print("🤔 Perguntar ao usuário se quer criar agora")

    if usuario_confirma():
        goto PASSO_11B
    else:
        goto PASSO_12
```

---

### 🚨 AVISOS CRÍTICOS PARA CLAUDE

**❌ NUNCA faça isso:**
- Ver "opcional" e pular automaticamente
- Ignorar a resposta do PASSO -2
- Assumir que pode decidir sozinho

**✅ SEMPRE faça isso:**
- Reler resposta da pergunta 6️⃣
- Se usuário disse "sim" → PASSO 11B é obrigatório
- Executar todos os 6 sub-passos do 11B
- Não pular nenhum sub-passo

---

## PASSO 11B: CRIAR DISPLAY FRONTEND

### ⚠️ QUANDO EXECUTAR ESTE PASSO?

**EXECUTAR SE:**
- ✅ Usuário respondeu "SIM" na pergunta 6️⃣ do PASSO -2
- ✅ Checkpoint acima direcionou para cá
- ✅ Usuário solicitou agora (mesmo tendo dito "não" antes)

**NÃO EXECUTAR SE:**
- ❌ Usuário respondeu "NÃO" na pergunta 6️⃣ E não quer criar agora
- ❌ Recurso é apenas admin (usuários, logs, configurações)

**Este passo é APENAS se você quer exibir os dados em alguma página frontend (home, blog, sidebar, etc).**

**Se não quiser integração frontend, seu CRUD está 100% pronto. Vá para PASSO 12.**

---

### 📋 Nova Abordagem: Partial Reutilizável + Preview no Admin

**Ao invés de perguntar "onde vai exibir?", criamos:**
1. Controller frontend genérico (read-only)
2. Partial reutilizável que pode ser incluída em qualquer lugar
3. Preview no admin mostrando como ficou + código para copiar

**Vantagens:**
- ✅ Código reutilizável (usar em múltiplas páginas)
- ✅ Preview direto no admin (feedback visual)
- ✅ Código pronto para copiar (facilita implementação)
- ✅ Sem acoplamento (não precisa definir página específica)

---

### PASSO 11B.1: Criar Controller Frontend

**Arquivo: `/frontend/controllers/Frontend[Recurso]Controller.php`**

**Exemplo: `FrontendBannerController.php`**

```php
<?php
/**
 * FrontendBannerController
 * Busca dados para exibição frontend (read-only)
 */

class FrontendBannerController extends BaseController {

    /**
     * Buscar registros ativos para exibição
     * @return array Registros ativos ordenados
     */
    public function getActive() {
        try {
            $data = $this->db()->query(
                "SELECT * FROM [tabela] WHERE ativo = 1 ORDER BY `order` ASC"
            );

            return $data ?? [];

        } catch (Exception $e) {
            error_log('Frontend[Recurso]Controller::getActive() ERROR: ' . $e->getMessage());
            return [];
        }
    }
}
```

**Checklist:**
```
[ ] Arquivo em /frontend/controllers/
[ ] Nome: Frontend[Recurso]Controller
[ ] Método getActive() retorna array
[ ] Query busca apenas ativo = 1
[ ] Error handling sem expor erro ao público
[ ] chmod 644
```

---

### PASSO 11B.2: Criar Partial Frontend

**Arquivo: `/frontend/views/partials/[recurso]-display.php`**

**Exemplo: `banner-hero.php` (carrossel)**

```php
<?php
/**
 * Partial: Banner Hero
 * Pode ser incluído em qualquer página
 */

// Buscar banners ativos
$controller = new FrontendBannerController();
$banners = $controller->getActive();

// Se não houver dados, não renderizar
if (empty($banners)) {
    return;
}
?>

<section class="hero-carousel">
    <?php foreach ($banners as $item): ?>
        <div class="slide">
            <h1><?= htmlspecialchars($item['title']) ?></h1>
            <!-- Estrutura HTML aqui -->
        </div>
    <?php endforeach; ?>
</section>

<style>
/* CSS inline ou referência externa */
</style>

<script>
// JavaScript se necessário
</script>
```

**Checklist:**
```
[ ] Arquivo em /frontend/views/partials/
[ ] Nome: [recurso]-display.php
[ ] Instancia Frontend[Recurso]Controller
[ ] Chama getActive()
[ ] Return early se vazio
[ ] htmlspecialchars() em TODAS as saídas
[ ] chmod 644
```

---

### PASSO 11B.3: Adicionar Preview no Admin

**Editar: `/admin/views/[recurso]/index.php`**

**Adicionar ANTES do `</div></body></html>` final:**

```php
        <!-- Preview Frontend -->
        <?php if (!empty($[recurso]s)): ?>
            <hr style="margin: 40px 0; border: none; border-top: 2px solid #ddd;">

            <div style="margin-bottom: 20px;">
                <h2 style="margin-bottom: 10px;">Preview Frontend</h2>
                <p style="color: #6c757d; font-size: 14px;">
                    Veja como os registros ativos aparecem no site
                </p>

                <!-- Código para copiar -->
                <div style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; margin-top: 15px; font-family: 'Courier New', monospace; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <strong style="color: #f8f8f2;">Incluir em qualquer página:</strong>
                        <button onclick="copyCode()" style="background: #44475a; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 11px;">
                            📋 Copiar
                        </button>
                    </div>
                    <code id="include-code" style="display: block;">&lt;?php Core::requireInclude('frontend/views/partials/[recurso]-display.php', true); ?&gt;</code>
                </div>

                <script>
                function copyCode() {
                    const code = document.getElementById('include-code').textContent;
                    navigator.clipboard.writeText(code).then(() => alert('Código copiado!'));
                }
                </script>
            </div>

            <?php
            // Filtrar apenas ativos para preview
            $ativos = array_filter($[recurso]s, function($item) {
                return $item['ativo'] == 1;
            });

            if (!empty($ativos)):
            ?>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <?php Core::requireInclude('frontend/views/partials/[recurso]-display.php', true); ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #6c757d; padding: 40px 0; background: #f8f9fa; border-radius: 8px;">
                    Nenhum registro ativo para preview. Ative pelo menos um para visualizar.
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
```

**Checklist:**
```
[ ] Preview aparece APENAS se houver registros
[ ] Linha separadora (hr) antes do preview
[ ] Box com código para copiar
[ ] Botão "Copiar" funcional (JavaScript)
[ ] Preview renderiza partial real
[ ] Mostra apenas registros ativos
[ ] Mensagem se não houver ativos
```

---

### PASSO 11B.4: Criar SASS do Componente Frontend

**⚠️ OBRIGATÓRIO:** Todo componente frontend precisa do seu próprio arquivo SASS.

**Arquivo: `/assets/sass/frontend/components/_[recurso]-display.sass`**

**Exemplo: `_banner-hero.sass`**

```sass
// ============================================
// COMPONENTE: Banner Hero
// Carrossel de banners principais do site
// ============================================

.c-banner-hero
  position: relative
  width: 100%
  height: 500px
  overflow: hidden
  background: #f5f5f5

  &__slide
    position: absolute
    top: 0
    left: 0
    width: 100%
    height: 100%
    opacity: 0
    transition: opacity 0.5s ease
    background-size: cover
    background-position: center

    &--active
      opacity: 1

  &__content
    position: absolute
    top: 50%
    left: 50px
    transform: translateY(-50%)
    max-width: 600px
    color: white
    z-index: 2

  &__title
    font-size: 48px
    font-weight: 700
    margin-bottom: 20px
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5)

  &__subtitle
    font-size: 24px
    margin-bottom: 30px
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5)

  &__cta
    display: inline-block
    padding: 15px 40px
    background: #6c10b8
    color: white
    text-decoration: none
    border-radius: 4px
    font-weight: 600
    transition: all 0.3s ease

    &:hover
      background: #5a0d9a
      transform: translateY(-2px)
```

**Checklist de Criação:**
```
[ ] Arquivo criado em /assets/sass/frontend/components/
[ ] Nome: _[recurso]-display.sass (underscore no início)
[ ] Nomenclatura BEM: .c-[recurso]__elemento--modificador
[ ] Prefixo 'c-' para components (diferencia de módulos 'm-')
[ ] Responsive se necessário (@media)
[ ] Variáveis do AEGIS ($color-main, $font-title, etc)
```

**Adicionar import no compilador:**

**Arquivo: `/assets/sass/frontend/components/_components.sass`**

```sass
// Components Frontend - Compilador

@use 'model'
@use 'banner-hero'  // ← ADICIONAR ESTA LINHA
```

**Recompilar SASS:**

```bash
npm run sass
# OU usar CodeKit (se configurado)
```

**Verificar:**
```
[ ] CSS compilado sem erros
[ ] Arquivo /assets/css/frontend.css atualizado
[ ] Classes .c-[recurso]__* existem no CSS final
[ ] Tamanho do arquivo aumentou (confirma que foi incluído)
```

**🔧 IMPORTANTE: Remover CSS Inline do Partial**

Após criar e compilar o SASS dedicado, **remova qualquer estilo inline** do arquivo partial:

**ANTES (❌):**
```php
<div class="banner-hero" style="height: 500px; background: #f5f5f5;">
    <h1 style="font-size: 48px; color: white;">...</h1>
</div>
```

**DEPOIS (✅):**
```php
<div class="c-banner-hero">
    <h1 class="c-banner-hero__title">...</h1>
</div>
```

**Por quê?**
- CSS inline dificulta customização
- Não respeita princípio de separação (HTML/CSS)
- Classes SASS já definem todos os estilos
- Mantém código limpo e manutenível

**Checklist:**
```
[ ] Removido todos style="..." do partial
[ ] Substituído por classes BEM (.c-[recurso]__*)
[ ] Testado que visual permanece idêntico
[ ] Partial usa apenas classes, sem estilos inline
```

---

### PASSO 11B.5: Testar Preview no Admin

**Acessar: `http://localhost:5757/aegis/admin/[recurso]`**

**Verificar:**
```
[ ] Se não houver registros → preview não aparece
[ ] Se houver registros inativos → mensagem "nenhum ativo"
[ ] Se houver ativos → preview renderiza corretamente
[ ] Botão "Copiar" funciona
[ ] Código copiado está correto
[ ] Preview tem mesmo visual do frontend
```

---

### PASSO 11B.6: Documentar Uso

**No arquivo `/docs/crud/implementados/[recurso].md`:**

```markdown
## 🎨 Frontend Display

**Controller:** `Frontend[Recurso]Controller`
**Partial:** `/frontend/views/partials/[recurso]-display.php`
**Preview:** Disponível em `/admin/[recurso]` (scroll down)

### Como usar em qualquer página:

```php
<?php Core::requireInclude('frontend/views/partials/[recurso]-display.php', true); ?>
```

### Características:
- Mostra apenas registros ativos (ativo = 1)
- Ordenado por `order` ASC
- Reutilizável em múltiplas páginas
- CSS/JS inline (sem dependências externas)

### Exemplos de uso:
- Home: `/frontend/pages/home.php`
- Sidebar: `/frontend/views/partials/sidebar.php`
- Footer: `/frontend/views/partials/footer.php`
```

---

### ✅ Pronto!

**Seu Admin CRUD + Frontend Display está completo:**

- ✅ Admin CRUD gerencia dados
- ✅ Controller frontend busca dados ativos
- ✅ Partial reutilizável em qualquer lugar
- ✅ Preview no admin com código para copiar
- ✅ Documentação de uso

**Próximo:** PASSO 12 (Validação Automática)
## PASSO 12: VALIDAÇÃO AUTOMÁTICA (RECOMENDADO)

### 🤖 Validador Automático de CRUD

**Antes de rodar testes manuais, use o validador automático:**

```bash
php scripts/validate-crud.php BannerController
```

**O que ele verifica:**
- ✅ Estrutura: 6 métodos, herança BaseController
- ✅ Segurança: CSRF, RateLimit, Auth, Prepared statements
- ✅ Auditoria: Logger.audit(), Logger.warning(), Exception handling
- ✅ Validação: Sanitize, UUID, Empty checks
- ✅ Nomenclatura: Actions maiúsculas, RateLimiter keys consistentes

**Resultado esperado:**
```
═══════════════════════════════════════════════════════════════
SCORE: 15/15 (100%)
═══════════════════════════════════════════════════════════════

✅ CRUD VÁLIDO!

Seu controller passou em todos os checks obrigatórios.
Está pronto para produção.
```

**Se score < 100%:**
- Revise os itens marcados com ❌
- Corrija o código
- Rode o validador novamente

---

## PASSO 13: ENTREGAR

### 🔒 GATE FINAL - TESTES OBRIGATÓRIOS ANTES DE ENTREGAR

**⛔ NÃO ENTREGUE SEM PASSAR NESTES TESTES**

```
🧪 TESTE 1: FUNCIONALIDADE BÁSICA (OBRIGATÓRIO)
[ ] Acessar /admin/[recurso] → lista aparece sem erro 500?
[ ] Acessar /admin/[recurso]/create → form aparece sem erro 500?
[ ] Submeter create → registro criado no banco?
[ ] Acessar /admin/[recurso]/[id]/edit → form aparece com dados?
[ ] Submeter edit → registro atualizado no banco?
[ ] Submeter delete → registro removido do banco?
⚠️ SE ALGUM FALHOU: DEBUGAR E CORRIGIR AGORA!

🔒 TESTE 2: SEGURANÇA (OBRIGATÓRIO)
[ ] Remover CSRF token do form → submit bloqueado com erro?
[ ] Fazer 10 submits rápidos → rate limit bloqueou?
[ ] Verificar banco: tabela logs_audit tem registros CREATE/UPDATE/DELETE?
[ ] SQL Injection: tentar ' OR 1=1 -- em campo → bloqueado?
⚠️ SE ALGUM FALHOU: SEU CRUD ESTÁ VULNERÁVEL! CORRIJA!

📁 TESTE 3: PERMISSÕES (OBRIGATÓRIO - 30% de erro)
[ ] Executar: ls -la /admin/views/[recurso]/*.php
[ ] TODOS arquivos têm permissão 644?
[ ] Se NÃO: chmod 644 /admin/views/[recurso]/*.php
⚠️ PERMISSÕES ERRADAS = ERRO 500 SILENCIOSO!

📊 TESTE 4: LOGS E AUDITORIA (OBRIGATÓRIO)
[ ] Executar: SELECT * FROM logs_audit WHERE action LIKE 'CREATE_%' ORDER BY created_at DESC LIMIT 5
[ ] Logs aparecem com user_id, ip, resource_id, table?
[ ] Logs de DELETE têm snapshot de dados deletados?
⚠️ SE LOGS NÃO APARECEM: Logger não está funcionando!
```

---

### ✅ CHECKLIST FINAL DE ENTREGA

**SÓ MARQUE COMO COMPLETO APÓS PASSAR NOS 4 TESTES ACIMA**

```
[ ] ✅ TESTE 1 passou (funcionalidade)
[ ] ✅ TESTE 2 passou (segurança)
[ ] ✅ TESTE 3 passou (permissões)
[ ] ✅ TESTE 4 passou (auditoria)

ARQUIVOS:
[ ] Arquivo controller salvo
[ ] Arquivo routes modificado
[ ] Views criadas com chmod 644
[ ] Frontend page modificado (se aplicável)

GIT:
[ ] Commit feito
[ ] Mensagem de commit clara
[ ] Testado no browser APÓS commit

DOCUMENTAÇÃO:
[ ] Se comportamento especial: documentado em comentários
[ ] Se API: endpoints documentados
```

---

### 🎉 APROVAÇÃO FINAL

**SE TODOS OS 4 TESTES PASSARAM E CHECKLIST ESTÁ 100%:**

```
✅ SEU CRUD ESTÁ PRONTO PARA PRODUÇÃO!
✅ Segurança: OWASP Top 10 compliant
✅ Auditoria: Todas ações logadas
✅ Performance: Rate limiting ativo
✅ Funcionalidade: Testada e validada

👉 PODE ENTREGAR COM CONFIANÇA!
```

**SE ALGUM TESTE FALHOU:**

```
⛔ NÃO ENTREGUE COM FALHAS!
⛔ VOLTE, CORRIJA E TESTE NOVAMENTE
⛔ CÓDIGO COM BUGS = RETRABALHO = TEMPO PERDIDO
```

---

## REFERÊNCIA RÁPIDA - NOMES DE AÇÃO

**Use exatamente assim:**

| Operação | Nome | Exemplo |
|----------|------|---------|
| Criar | CREATE_RECURSO | CREATE_CATEGORY |
| Atualizar | UPDATE_RECURSO | UPDATE_MEMBER |
| Deletar | DELETE_RECURSO | DELETE_POST |
| Erro de criação | CREATE_RECURSO_FAILED | CREATE_CATEGORY_FAILED |
| Erro de update | UPDATE_RECURSO_FAILED | UPDATE_MEMBER_FAILED |
| Erro de deleção | DELETE_RECURSO_FAILED | DELETE_POST_FAILED |

**SEMPRE singular, SEMPRE maiúsculas.**

---

## REFERÊNCIA RÁPIDA - RATE LIMITS

| Tipo | Limite | Janela |
|------|--------|--------|
| store | 5 | 60s |
| update | 10 | 60s |
| destroy | 5 | 60s |
| API index/show | 60 | 60s |
| login (especial) | 5 | 300s |

---

## REFERÊNCIA RÁPIDA - CONSULTAR SE TIVER DÚVIDA

| Dúvida | Arquivo |
|--------|---------|
| "Como fazer CSRF?" | MASTER-CHECKLIST-SEGURANCA.md seção 1 |
| "Como fazer RateLimit?" | MASTER-CHECKLIST-SEGURANCA.md seção 2 |
| "Como validar email?" | MASTER-CHECKLIST-VALIDACAO.md seção 2 |
| "Como fazer Logger?" | MASTER-CHECKLIST-AUDITORIA.md seção 1-3 |
| "Preciso de exemplo completo?" | TEMPLATE-CRUD-*.md |

---

## PRÓXIMO: FAZER NA PRÁTICA

**Quando estiver pronto, escolha um CRUD dos 31 e execute este guia.**

Eu vou seguir exatamente estes passos.

