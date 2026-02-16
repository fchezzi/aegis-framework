# 🔐 Sistema de Permissões AEGIS

> **Quando usar este doc:** Tarefas envolvendo MemberAuth, grupos, permissões de acesso, ENABLE_MEMBERS

> **Para Auth admin:** Ver REGRAS.md #6 (Auth::require() obrigatório)

---

## 📊 Visão Geral: Dois Sistemas

| Tipo | Classe | Tabela | Login | Documentação |
|------|--------|--------|-------|--------------|
| **Admin** | `Auth` | `users` | `/admin/login` | REGRAS.md #6 |
| **Member** | `MemberAuth` | `members` | `/login` | Este documento |

**IMPORTANTE:** `users` é a tabela de admins (REGRAS.md #2)

---

## 👥 MemberAuth (Frontend/Members)

### API Completa

```php
MemberAuth::require();     // Exige login, redireciona para /login
MemberAuth::check();       // bool - está logado?
MemberAuth::member();      // ['id', 'email', 'name', 'group_id'] ou null
MemberAuth::login($email, $pass);
MemberAuth::logout();
```

### Exemplo: Página Protegida

```php
// frontend/pages/dashboard.php
<?php
MemberAuth::require();  // Bloqueia se não estiver logado

$member = MemberAuth::member();
?>
<h1>Bem-vindo, <?= htmlspecialchars($member['name']) ?>!</h1>
```

### Exemplo: Controller Frontend

```php
class ProfileController {
    public function edit() {
        MemberAuth::require();

        $member = MemberAuth::member();
        $memberId = $member['id'];

        // código...
    }
}
```

---

## 🔑 Sistema de Permissões Granulares

**Pré-requisito:** `ENABLE_MEMBERS = true` em `_config.php`

### API de Permissões

```php
// Verificação
Permission::canAccess($memberId, $pageId);
Permission::getAccessiblePages($memberId);

// Conceder
Permission::grantIndividual($memberId, $pageId);
Permission::grantGroup($groupId, $pageId);

// Remover
Permission::removeIndividual($memberId, $pageId);
Permission::removeGroup($groupId, $pageId);
```

### Ordem de Precedência (CRÍTICO)

**Verificação acontece NESTA ordem:**

1. ✅ **is_public = 1** na tabela `pages` → TODOS acessam
2. ✅ **"public": true** em `module.json` → TODOS acessam
3. ❌ **Member NÃO logado** → Bloqueia
4. ✅ **Permissão individual** (`member_permissions`) → Libera
5. ✅ **Permissão de grupo** (`group_permissions`) → Libera
6. ❌ **Nenhum match** → Bloqueia

**Regra de ouro:** Público > Individual > Grupo > Bloquear

---

## 🎯 Grupos de Permissão

### Estrutura de Tabelas

```
groups (id, name, description)
  └─ members (id, email, group_id) FK
  └─ group_permissions (group_id, page_id)
```

### Fluxo Completo: Criar Grupo "Editores"

```sql
-- 1. Criar grupo
INSERT INTO groups (id, name, description)
VALUES (Core::generateUUID(), 'Editores', 'Podem editar artigos');

-- 2. Conceder permissão ao grupo
INSERT INTO group_permissions (group_id, page_id)
SELECT
    (SELECT id FROM groups WHERE name = 'Editores'),
    (SELECT id FROM pages WHERE slug = 'artigos');

-- 3. Adicionar member ao grupo
UPDATE members
SET group_id = (SELECT id FROM groups WHERE name = 'Editores')
WHERE email = 'editor@exemplo.com';
```

**Resultado:** Todos members do grupo "Editores" acessam página "artigos"

### Via PHP (Código)

```php
$db = DB::connect();

// 1. Criar grupo
$groupId = Core::generateUUID();
$db->insert('groups', [
    'id' => $groupId,
    'name' => 'Editores',
    'description' => 'Podem editar artigos'
]);

// 2. Conceder permissão
$pageId = $db->select('pages', ['slug' => 'artigos'], 1)['id'];
Permission::grantGroup($groupId, $pageId);

// 3. Adicionar member
$db->update('members', ['group_id' => $groupId], ['email' => 'editor@exemplo.com']);
```

---

## 🌍 Páginas Públicas vs Privadas

### Tornar Público (2 formas)

**Forma 1: Página estática (tabela pages)**
```sql
UPDATE pages SET is_public = 1 WHERE slug = 'sobre';
```

**Forma 2: Módulo (module.json)**
```json
{
  "name": "Blog",
  "public": true
}
```

**REGRA #9:** Módulos → `module.json`. Páginas → tabela `pages`. NUNCA misturar.

---

## 🚫 Sistema SEM Members (ENABLE_MEMBERS = false)

**Comportamento:**
```php
define('ENABLE_MEMBERS', false);
```

- ❌ `/login` não funciona
- ✅ TODO frontend é público automaticamente
- ✅ MemberAuth::require() não bloqueia nada
- ✅ Apenas admins fazem login (`/admin`)
- ✅ Funciona como site institucional

**Quando usar:** Sites corporativos, portfólios, landing pages (sem área de membros)

---

## 🔍 Integração com MenuBuilder

**MenuBuilder.php verifica automaticamente:**

```php
// Pseudocódigo
if ($page['is_public'] == 1) {
    return true;  // Mostrar para todos
}

if ($module['public'] == true) {
    return true;  // Mostrar para todos
}

if (!MemberAuth::check()) {
    return false;  // Esconder se não logado
}

if (Permission::canAccess($memberId, $pageId)) {
    return true;  // Verificou permissão
}

return false;  // Bloquear
```

**Resultado:** Menu se adapta automaticamente ao contexto do usuário

---

## 🛠️ Troubleshooting

### "Member tem permissão mas não acessa"

```php
// Debug checklist
var_dump(MemberAuth::check());  // true?
var_dump(MemberAuth::member()['id']);  // UUID correto?

$db = DB::connect();
$perm = $db->select('member_permissions', [
    'member_id' => $memberId,
    'page_id' => $pageId
]);
var_dump($perm);  // array não vazio?
```

### "ENABLE_MEMBERS = false mas pede login"

**Causa:** `MemberAuth::require()` hardcoded em página que deveria ser pública

**Solução:** Remover `MemberAuth::require()` dessa página

---

## 📋 Checklist de Uso

**Criar sistema de permissões:**
```
□ Verificar ENABLE_MEMBERS = true em _config.php
□ Criar grupos via SQL ou painel admin
□ Atribuir members aos grupos (UPDATE members SET group_id)
□ Conceder permissões (Permission::grantGroup ou SQL)
```

**Proteger página frontend:**
```
□ Adicionar MemberAuth::require() no topo da página
□ OU configurar permissão via grupo/individual
```

**Tornar conteúdo público:**
```
□ Páginas: UPDATE pages SET is_public = 1
□ Módulos: "public": true em module.json
```

---

## 📚 Referências Cruzadas

- **Auth admin:** REGRAS.md #6
- **Módulos vs Páginas:** REGRAS.md #9
- **Tabela users (não admins):** REGRAS.md #2
- **Páginas públicas no menu:** known-issues.md #3

---

**Versão:** 3.0.0
**Data:** 2026-02-14
**Changelog:** Removidas redundâncias com REGRAS.md e known-issues.md, focado em MemberAuth e sistema de permissões granulares
