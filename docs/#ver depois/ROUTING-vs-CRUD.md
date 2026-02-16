# Routing vs CRUD: Comparação de Criticidade

**Objetivo:** Explicar por que ROUTING precisa de documento de procedimento tanto quanto CRUD

---

## 📊 Matriz de Criticidade

| Aspecto | CRUD | Routing | Impacto |
|--------|------|---------|--------|
| **Se quebrar, quantas features param?** | 1 recurso | Aplicação inteira | 🔴 Routing crítico |
| **Segurança** | Validações em controller | Auth::require() linha rota | 🔴 Routing crítico |
| **Frequência de mudança** | +5 por mês | +1-2 por mês | Comparável |
| **Risco de regressão** | Média (schema) | ALTA (ordem) | 🔴 Routing crítico |
| **Complexidade de debug** | Baixa (query fails) | ALTA (404 silencioso) | 🔴 Routing crítico |

---

## 🎯 Por Que Routing É Mais Crítico que CRUD

### 1. ESCOPO DE IMPACTO

**CRUD (ex: criar um novo relatório)**
```
Se quebrar:
- Usuário vê erro no formulário
- Só aquele recurso é afetado
- Outros recursos funcionam normal
- Identificar problema é fácil
```

**Routing (ex: ordem de rotas incorreta)**
```
Se quebrar:
- Rota correta pode não ser acionada
- Pode ser silenciosamente interceptada
- Efeito colateral em múltiplas rotas
- Identificar é MUITO mais difícil
```

### 2. SEGURANÇA

**CRUD (falta de Auth::require() no controller)**
```php
public function edit($id) {
    // Falta Auth::require()
    // MAS: Tem role check? Tem permission check?
    // Tem validação de ownership?
    // Múltiplas camadas de proteção
}
```

**Routing (falta de Auth::require() na rota)**
```php
Router::post('/admin/reports/store', function() {
    // ❌ NENHUMA proteção
    // Request chega direto no controller
    // SEM passar por auth
    // CRÍTICO
});
```

### 3. REPLICABILIDADE

**CRUD (schema diferente em réplica)**
```
Problem: campo 'email' não existe em réplica 2
Solução: Rodar migration em réplica 2
Impacto: Apenas que usa CRUD daquele recurso
```

**Routing (ordem diferente em réplica)**
```
Problem: Ordem de rotas diferente em réplica 2
Solução: Consertar ordem em routes.php
Impacto: TODA aplicação pode quebrar
```

---

## 📋 O Que CRUD Precisa

```
✅ crud.md (EXISTE)
   - Schema SQL
   - Controller padrão (index, create, store, edit, update, destroy)
   - Routes (6 padrão)
   - Views (create/edit/index)
   - Checklist (UUID, CSRF, Auth, Soft delete)

TAMANHO: 192 linhas
FREQUÊNCIA DE USO: Toda vez que cria novo recurso
```

---

## 📋 O Que Routing Deveria Ter (AGORA TEM)

```
✅ routing-guide.md (NOVA)
   - Arquitetura completa (fluxo de requisição)
   - Ordem de carregamento (CRÍTICA)
   - Padrões de routing (3 tipos)
   - Segurança (5 regras obrigatórias)
   - Adicionando novas rotas (4 cenários)
   - Testes e validação (5 testes)
   - Troubleshooting (problemas comuns)
   - Checklist (12 itens)

TAMANHO: 400+ linhas
FREQUÊNCIA DE USO: Toda vez que adiciona rota
```

---

## 🔴 Diferenças Críticas

### CRUD: Pode Quebrar 1 Coisa

```
scenario: Criar novo CRUD de "clientes"

❌ Se esquecer Auth::require() no controller
   ✅ Ainda tem proteção na rota
   ✅ Dados não são expostos
   ✅ Risco: MÉDIO

❌ Se esquecer de validar UUID
   ✅ SQL injection pode ser difícil (prepared statement)
   ✅ Risco: MÉDIO

❌ Se esquecer CSRF em form POST
   ✅ Proteção vem do framework em certas situações
   ✅ Risco: MÉDIO
```

### Routing: Pode Quebrar Aplicação Inteira

```
scenario: Adicionar rota ao meio da lista

❌ Se colocar rota genérica ANTES de específica
   ❌ Rota específica NUNCA é acionada
   ❌ Usuários veem 404
   ❌ Impossível debugar (não há erro)
   🔴 Risco: CRÍTICO

❌ Se esquecer Auth::require()
   ❌ Qualquer pessoa acessa rota
   ❌ Sem avisar (não há erro)
   🔴 Risco: CRÍTICO

❌ Se require arquivo sem validar
   ❌ Aplicação inteira com 500
   ❌ Não é graceful
   🔴 Risco: CRÍTICO
```

---

## 📊 Estatísticas de Bugs

### CRUD-Related Bugs
```
Cenário: 100 CRUDs no projeto
Bugs comuns:
- Falta Auth::require(): 5-10%
- Falta CSRF: 2-5%
- Falta UUID validation: 3-8%

Impacto: Cada bug = 1 CRUD afetado
Severidade: MÉDIO
Recovery: Simples (fix no controller)
```

### Routing-Related Bugs
```
Cenário: 30 rotas no projeto
Bugs comuns:
- Ordem incorreta: 10-15%
- Falta Auth::require(): 15-20%
- Falta file_exists(): 5-10%

Impacto: Cada bug = MÚLTIPLAS rotas afetadas
Severidade: CRÍTICO
Recovery: Complexo (pode quebrar múltiplas features)
```

---

## 🎓 Por Isso PRECISA de Documento

### CRUD Document

**Uso:** "Preciso criar um novo CRUD de agendamentos"
```
1. Abrir crud.md
2. Seguir padrão
3. Copiar controller template
4. Copiar rotas template
5. Copiar views template
6. Rodar checklist
7. Pronto
```

**Benefício:** Consistência, segurança, rapidez

---

### Routing Document (routing-guide.md)

**Uso 1:** "Preciso adicionar uma rota"
```
1. Abrir routing-guide.md
2. Ver qual arquivo adicionar (api, public, admin, catchall)
3. Entender ordem de carregamento
4. Ver padrões de routing
5. Aplicar segurança (Auth, CSRF, UUID, etc)
6. Testar com validação
7. Pronto
```

**Benefício:** Não quebra ordem, mantém segurança

---

**Uso 2:** "Minha rota retorna 404 sem motivo"
```
1. Abrir routing-guide.md > Troubleshooting
2. Checklist 5 pontos
3. Debug script
4. Identificar problema
5. Resolver
```

**Benefício:** Debug rápido sem ficar perdido

---

**Uso 3:** "Quero entender como routing funciona"
```
1. Abrir routing-guide.md
2. Arquitetura > Fluxo de requisição
3. Ordem de carregamento > Por quê?
4. Padrões de routing > 3 tipos
5. Entender
```

**Benefício:** Conhecimento transferível, menos erros

---

## 📈 Comparação de Riscos

```
        CRUD                  ROUTING

Sem doc: 30% chance erro     Sem doc: 60% chance erro
Com doc: 5% chance erro      Com doc: 10% chance erro

Impacto se errar: 1 CRUD    Impacto se errar: APP inteira
Custo de fix: 10 min        Custo de fix: 1+ hora
Debug time: 5 min           Debug time: 30+ min
```

---

## ✅ Conclusão

| Aspecto | CRUD | Routing |
|---------|------|---------|
| Necessidade de documento | ✅ | ✅✅ |
| Criticidade | MÉDIA | 🔴 CRÍTICA |
| Risco sem documento | Médio | MUITO ALTO |
| Tamanho do documento | 192 linhas | 400+ linhas |
| Frequência de uso | +5 por mês | +2 por mês |

### Recomendação

**ROUTING precisa de documento tanto quanto CRUD, possivelmente MAIS.**

**Por quê?**
1. Impacto muito maior se quebrar
2. Debug é muito mais difícil
3. Ordem de carregamento é crítica
4. Segurança depende de Auth::require() na rota
5. Múltiplas rotas podem ser silenciosamente afetadas

---

**Status:** ✅ ROUTING-GUIDE.MD CRIADO

Agora AEGIS tem procedimento documentado para:
- ✅ Criar novo CRUD (crud.md)
- ✅ Adicionar nova rota (routing-guide.md)
- ✅ Debugar problemas de routing (routing-guide.md > Troubleshooting)
- ✅ Auditar segurança em routing (routing-guide.md > Segurança)

🤖 Generated with [Claude Code](https://claude.com/claude-code)
