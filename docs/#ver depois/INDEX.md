# 📚 AEGIS - Índice de Documentação

**Última atualização:** 2026-01-27
**Propósito:** Ponto de entrada para toda documentação

> **NOVO (27/01):** Módulo Artigos + Email/RD Station + Admin Settings
> Ver: `docs/CHANGELOG-2026-01-27.md` e `modules/artigos/README.md`

---

## 🚀 INÍCIO RÁPIDO

### Primeira vez na sessão?
1. **REGRAS.md** (10 regras + 5 erros proibidos) - 5min
2. **docs/_state.md** (estado atual do projeto) - 1min
3. **Pronto!** Use este índice conforme necessidade

### Comando inicial:
```
/aegis
```
Carrega automaticamente: REGRAS → _state → .aegis-version

---

## 📖 DOCUMENTAÇÃO POR CATEGORIA

### 🏗️ Core (Essenciais)

| Arquivo | Quando Usar | Tempo |
|---------|-------------|-------|
| **setup-novo-projeto.md** | 🆕 Configurar novo projeto AEGIS do zero | 15min |
| **REGRAS.md** | Sempre antes de codar | 5min |
| **ERRO-PROTOCOL.md** | Quando algo der errado | 2min |
| **QUICK_REFERENCE.md** | Referência rápida de classes (20 classes!) | Consulta |
| **REFACTORING-GUIDE.md** | Mexer em classes core (JWT, Queue, Cache, etc) | 10min |

### 🗺️ Navegação

| Arquivo | Quando Usar |
|---------|-------------|
| **routing.md** | Rotas, base path, helpers |
| **permissions.md** | Auth vs MemberAuth, permissões |
| **modules.md** | Sistema de módulos |
| **crud.md** | Padrão rápido para CRUDs |
| **problemas-conhecidos.md** | Problemas comuns (UUID, FK, etc) |

### 📦 Específicos do Projeto

| Arquivo | Quando Usar |
|---------|-------------|
| **youtube-page-docs.md** | Página YouTube + sync n8n + AEGIS_API_TOKEN |
| **data-sources-guide.md** | Criar fontes de dados customizáveis |
| **filtros-guia.md** | Sistema de filtros + Page Builder |
| **MIGRAÇÃO-PLATAFORMAS.md** | Migração multi-plataforma |

---

## 💾 MEMORY/ (Contexto Persistente)

| Arquivo | Quando Usar | Linhas |
|---------|-------------|--------|
| **known-issues.md** | Problemas recorrentes e soluções | 702 |
| **module-patterns.md** | Criar módulos (patterns completos) | 702 |
| **roadmap.json** | Ver próximas tarefas (se existir) | - |

---

## 🎯 TEMPLATES/ (Acelerar Desenvolvimento)

| Template | Quando Usar | Economia |
|----------|-------------|----------|
| **README.md** | Ver resumo dos templates | - |
| **crud-template.md** | Criar CRUD completo | 62% tempo |
| **admin-controller-template.md** | CRUD no admin | 50% tempo |
| **feature-template.md** | Feature nova (não-CRUD) | 40% tempo |
| **file-upload-template.md** | Upload seguro (7 camadas) | 56% tempo |
| **module-template.md** | Módulo instalável | 50% tempo |
| **module-planning-template.md** | Planejar módulo | - |

---

## 🤖 COMMANDS/ (Slash Commands)

| Command | O que faz |
|---------|-----------|
| `/aegis` | Carrega contexto AEGIS Framework |
| `/futebolenergia` | Carrega contexto Futebol Energia |

---

## 📊 ESTRUTURA DO PROJETO

### docs/ (Documentação Projeto)
- **_state.md** - Estado atual (versão, última sessão, avisos)
- **aegis-*.md** - Documentação completa (21 arquivos)
- **SECURITY-*.md** - Padrões e auditoria de segurança
- **CHANGELOG-*.md** - Histórico de mudanças

### .claude/ (Documentação Claude)
- **Raiz:** Guias, referências, padrões
- **memory/:** Problemas conhecidos, patterns de módulos
- **templates/:** Templates de desenvolvimento
- **commands/:** Slash commands

---

## 🎯 QUANDO USAR O QUÊ

### Criar novo recurso?
```
→ Ler templates/README.md
→ Escolher template apropriado
→ Seguir passo a passo
```

### Criar módulo?
```
→ Ler memory/module-patterns.md
→ Usar templates/module-template.md
→ Seguir checklist completo
```

### Deu erro?
```
→ PARAR imediatamente
→ Ler ERRO-PROTOCOL.md
→ Verificar memory/known-issues.md
→ Reportar → Aguardar
```

### Dúvida sobre classe?
```
→ Consultar QUICK_REFERENCE.md (18 classes)
→ Se for classe do refactoring: REFACTORING-GUIDE.md
```

### Esqueceu algo?
```
→ REGRAS.md (sempre!)
```

---

## 📊 VERSIONAMENTO (Fonte Única)

**Fonte de verdade:** `.aegis-version` (atualizada automaticamente)

**Sincronizado automaticamente pelo `Version::bump()`:**
- ✅ `.aegis-version` (fonte)
- ✅ `storage/versions.json` (histórico estruturado)
- ✅ `CHANGELOG.md` (changelog formatado)
- ✅ `docs/_state.md` (estado do projeto)

**Como fazer bump:**
```php
Version::bump('patch', 'Descrição', [arquivos]);  // 17.0.0 → 17.0.1
Version::bump('minor', 'Nova feature', [arquivos]); // 17.0.0 → 17.1.0
Version::bump('major', 'Breaking change', [arquivos]); // 17.0.0 → 18.0.0
```

**Auto-bump:** Sistema detecta mudanças e faz bump automático (1x por dia)

---

## 🔍 BUSCA RÁPIDA

| Preciso... | Ver... |
|------------|--------|
| 10 regras invioláveis | REGRAS.md |
| Criar CRUD | templates/crud-template.md |
| Upload seguro | templates/file-upload-template.md |
| Problema comum | memory/known-issues.md |
| Referência de classe | QUICK_REFERENCE.md |
| Rotas | routing.md |
| Permissões | permissions.md |
| Erro | ERRO-PROTOCOL.md |
| Módulo | memory/module-patterns.md |
| YouTube + n8n | youtube-page-docs.md |
| Data sources | data-sources-guide.md |
| Filtros | filtros-guia.md |
| Módulo Artigos | modules/artigos/README.md |
| Email (PHPMailer) | QUICK_REFERENCE.md (linha ~451) |
| RD Station | QUICK_REFERENCE.md (linha ~475) |
| Changelog 27/01 | docs/CHANGELOG-2026-01-27.md |

---

## 📦 ARQUIVOS MENOS USADOS

Leia se necessário:
- `create-metricas-canais.sql` - SQL específico
- `migration-*.sql` - Migrações de banco
- `*.html` - Versões HTML de documentos .md

---

## ✅ REGRA DE OURO

**Antes de qualquer ação no código:**
1. Ler **REGRAS.md**
2. Consultar **este INDEX.md** para saber onde buscar info
3. Ler arquivo específico conforme necessidade
4. Codar com segurança!

---

**Mantido por:** Claude Code
**Versão:** 1.0.0
