# 🚀 Templates AEGIS Framework

**Propósito:** Acelerar desenvolvimento com processos padronizados

---

## 📋 Templates Disponíveis

### 1. CRUD Completo (`crud-template.md`)
**Tempo:** 30-60 min (vs 2h sem template)
**Quando usar:** Criar CRUD de qualquer entidade
**Inclui:**
- Tabela SQL com índices
- Controller com CRUD completo
- View com formulário + listagem
- Validações de segurança
- Paginação

**Exemplo:**
```bash
Criar CRUD de "Produtos":
- Seguir template
- Substituir {Nome} → Produto
- Tempo: ~45 min
- Resultado: CRUD funcional e seguro
```

---

### 2. Feature Nova (`feature-template.md`)
**Tempo:** 1-4h (depende da complexidade)
**Quando usar:** Implementar feature que NÃO é CRUD
**Inclui:**
- Planejamento (5-10 min)
- Database schema (se necessário)
- Core class (se necessário)
- Controller específico
- Frontend
- Checklists de segurança e performance
- Documentação

**Exemplo:**
```bash
Sistema de Notificações:
- Seguir template
- Criar tabela + Core class + Controller
- Tempo: ~3h
- Resultado: Feature completa documentada
```

---

### 3. File Upload Seguro (`file-upload-template.md`)
**Tempo:** 30-60 min
**Quando usar:** Implementar upload de arquivos
**Inclui:**
- MIME validation (whitelist)
- Extension validation
- Size validation
- Dimension validation (imagens)
- Name sanitization
- Storage seguro
- .htaccess de proteção
- Redimensionamento (imagens)

**Exemplo:**
```bash
Upload de documentos PDF:
- Seguir template
- Validar MIME application/pdf
- Tamanho máximo 10MB
- Tempo: ~40 min
- Resultado: Upload SEGURO ✅
```

---

### 4. Módulo Novo (`module-template.md`)
**Tempo:** 2-4h
**Quando usar:** Criar módulo instalável
**Inclui:**
- info.json (metadados)
- install.sql
- uninstall.sql
- Estrutura de pastas
- Controller + Views
- README

**Exemplo:**
```bash
Módulo de Relatórios:
- Seguir template
- Criar estrutura completa
- Tempo: ~3h
- Resultado: Módulo instalável via admin
```

---

## 🔧 Workflows Disponíveis

### 1. Security Checklist (`../workflows/security-checklist.md`)
**Quando usar:** ANTES de commit/deploy
**Inclui:**
- OWASP Top 10 checklist
- SQL Injection verification
- XSS prevention check
- CSRF validation
- File upload security
- Config exposure check

**Uso:**
```bash
Antes de commit:
1. Ler security-checklist.md
2. Verificar TODOS os itens
3. Só commitar se 100% ✅
```

---

### 2. Performance Checklist (`../workflows/performance-checklist.md`)
**Quando usar:** Features pesadas, antes de deploy
**Inclui:**
- Database optimization (índices, N+1)
- Cache strategies
- Frontend optimization
- Medição de performance
- Quick wins

**Uso:**
```bash
Feature lenta?
1. Ler performance-checklist.md
2. Medir ANTES
3. Otimizar
4. Medir DEPOIS
5. Documentar impacto
```

---

## 🎯 Fluxo de Uso

### Criar novo CRUD:
```
1. Ler .claude/templates/crud-template.md
2. Seguir passo a passo
3. Ler ../workflows/security-checklist.md
4. Verificar tudo OK
5. Commit
```

### Criar nova Feature:
```
1. Ler .claude/templates/feature-template.md
2. Planejar (5-10 min)
3. Implementar
4. Ler ../workflows/security-checklist.md
5. Ler ../workflows/performance-checklist.md (se pesada)
6. Commit
```

### Upload de arquivo:
```
1. Ler .claude/templates/file-upload-template.md
2. NUNCA pular validações
3. Ler ../workflows/security-checklist.md
4. Testar exploits
5. Commit
```

---

## 📊 Ganho de Tempo

| Tarefa | Sem Template | Com Template | Economia |
|--------|--------------|--------------|----------|
| CRUD completo | 2h | 45 min | 62% |
| Feature média | 5h | 3h | 40% |
| File upload | 1.5h | 40 min | 56% |
| Módulo novo | 6h | 3h | 50% |

**Total economizado:** ~50% do tempo ✅

---

## ✅ Checklist de Uso

Ao usar template:

- [ ] Li o template inteiro ANTES de começar
- [ ] Substituí TODOS os placeholders ({Nome}, {nome}, etc)
- [ ] Segui TODOS os passos
- [ ] Rodei checklists de segurança
- [ ] Testei manualmente
- [ ] Atualizei documentação (memória Claude)

---

## 🆘 Troubleshooting

**Problema:** "Não sei qual template usar"
**Solução:** Ver `.claude/START_HERE.md` → seção "Templates e Workflows"

**Problema:** "Template muito genérico"
**Solução:** Adaptar conforme necessidade. Templates são BASE, não camisa de força.

**Problema:** "Esqueci de usar checklist"
**Solução:** Usar ANTES de commit. Se esqueceu, rodar agora e corrigir problemas.

---

**Versão:** 1.0.0
**Criado em:** 2025-01-20
**Mantido por:** Claude Code
**Propósito:** Acelerar desenvolvimento mantendo qualidade
