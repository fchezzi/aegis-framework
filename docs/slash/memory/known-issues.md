# 🐛 Problemas Conhecidos e Soluções

> **Quando usar:** Primeiro passo ao encontrar erro (ERRO-PROTOCOL.md passo 0). Verificar se já tem solução pronta antes de reportar.

---

## 1. Edit Tool SEMPRE Falha (Tabs vs Spaces)

**Problema:**
- Tool Edit falha com erro "String not found"
- Causa: diferenças invisíveis de indentação (tabs vs spaces)

**❌ NÃO FAZER:**
```
- Tentar Edit múltiplas vezes com strings diferentes
- Usar sed complexo
- Pedir ao usuário para verificar
```

**✅ SOLUÇÃO:**
```
- Usar Write para reescrever arquivo completo
- Ler arquivo com Read primeiro
- Modificar conteúdo
- Escrever com Write
```

**Tempo economizado:** 10-15 min por tentativa

---

## 2. Duplicatas no Menu (Insert Duplicado)

**Problema:**
- Ao criar item de menu, cria 2 itens idênticos
- Usuário clica apenas 1 vez
- Lock e JavaScript anti-duplicate já existem

**Causa Raiz:**
- Problema intermitente/browser que resubmete form

**❌ NÃO FAZER:**
```
- Pedir debug ao usuário
- Verificar se clicou 2 vezes (ele sempre clica 1 vez)
- Modificar JavaScript ou Lock
```

**✅ SOLUÇÃO:**
```php
// MenuController.php linha 119-132
// Verificar ANTES de inserir se já existe
$whereCheck = ['label' => $label, 'type' => $type];
if ($type === 'page' && $pageSlug) {
    $whereCheck['page_slug'] = $pageSlug;
}
$existing = $db->select('menu_items', $whereCheck);
if (!empty($existing)) {
    throw new Exception('Item de menu já existe');
}
```

**Arquivo:** `admin/controllers/MenuController.php:119-132`

---

## 3. Páginas/Módulos Públicos Não Aparecem no Menu

**Problema:**
- Página/módulo marcado como `is_public = 1`
- Item de menu criado
- Menu NÃO aparece para usuários não logados

**Causa Raiz:**
- MenuBuilder verifica `permission_type` do menu item ANTES de verificar `is_public` da página
- Se menu item tem `permission_type = 'authenticated'`, bloqueia antes de checar is_public

**❌ NÃO FAZER:**
```
- Pedir SQL para verificar is_public no banco
- Modificar apenas parte do MenuBuilder
- Usar Edit/sed para pequenos fixes
```

**✅ SOLUÇÃO:**
```php
// MenuBuilder.php - Verificar is_public PRIMEIRO no loop (linhas 146-168)

// 1. Verificar se página/módulo é PÚBLICO
if ($item['type'] === 'page' && !empty($item['page_slug'])) {
    if (isset($pagesBySlug[$item['page_slug']])) {
        $page = $pagesBySlug[$item['page_slug']];
        if (isset($page['is_public']) && $page['is_public'] == 1) {
            $canAccess = true; // ✅ Acesso garantido
        }
    }
}

if (!$canAccess && $item['type'] === 'module' && !empty($item['module_name'])) {
    if (isset($pagesByModuleName[$item['module_name']])) {
        $page = $pagesByModuleName[$item['module_name']];
        if (isset($page['is_public']) && $page['is_public'] == 1) {
            $canAccess = true; // ✅ Acesso garantido
        }
    }
}

// 2. SÓ DEPOIS verificar permission_type do menu
if (!$canAccess) {
    switch ($item['permission_type']) { ... }
}
```

**Arquivo:** `core/MenuBuilder.php:146-197`

**Pré-fetch:** Linha 101 deve ser apenas `if (!empty($page['module_name']))` sem verificar is_virtual

---

## 4. Páginas Públicas Redirecionam para Login

**Problema:**
- Página com `is_public = 1` no banco
- Ao acessar sem login → redireciona para login

**Causa Raiz:**
- routes.php verifica login ANTES de verificar is_public

**✅ SOLUÇÃO:**
```php
// routes.php linha 759-775
// Verificar is_public ANTES de exigir login

$page = $pages[0];

// ✅ VERIFICAR SE PÁGINA É PÚBLICA (is_public = 1)
if (($page['is_public'] ?? 0) == 1) {
    // Página pública → carregar SEM verificação de login
    $member = null;
    require_once $pageFile; // ou dashboard.php
    return;
}

// ✅ PÁGINA PRIVADA → Verificar login
if (!MemberAuth::check()) {
    Core::redirect('/login');
    return;
}
```

**Arquivo:** `routes.php:759-775`

---

## 5. Sistema SEM Members - Páginas/Módulos Devem Ser Públicos Automaticamente

**Contexto:**
- Sistema instalado com `ENABLE_MEMBERS = false`
- Não existe sistema de login de members (usuários do site)
- Apenas admins logam (via /admin)
- Logo, TODAS as páginas e módulos públicos devem ser acessíveis

**Problema ANTES da correção:**
- Ao criar página/módulo, campo `is_public` ficava 0 (privado)
- Checkbox "Página Pública" NÃO aparecia no form (pois membersEnabled = false)
- Controller salvava `is_public = 0` por padrão
- Páginas ficavam INACESSÍVEIS (nem pra público, nem pra members - que não existem!)

**✅ SOLUÇÃO:**

```php
// PagesController.php - store() e update() (linhas 77-81 e 191-195)

// Se sistema SEM members → sempre público
// Se sistema COM members → respeita checkbox do admin
$isPublic = Core::membersEnabled()
    ? (isset($_POST['is_public']) ? 1 : 0)  // COM members: respeita checkbox
    : 1;  // SEM members: SEMPRE público
```

```php
// ModulesController.php - index() e togglePublic() (linhas 46-47 e 114-116)

// Default ao listar
$isPublic = Core::membersEnabled() ? 0 : 1;

// Ao salvar configuração de módulo
$isPublic = Core::membersEnabled()
    ? (in_array($moduleName, $publicModules) ? 1 : 0)
    : 1;
```

**Arquivos modificados:**
- `admin/controllers/PagesController.php:77-81,191-195`
- `admin/controllers/ModulesController.php:46-47,114-116`

**Comportamento correto:**
- ✅ Sistema SEM members → `is_public = 1` sempre (automático)
- ✅ Sistema COM members → `is_public` respeitado conforme checkbox do admin

---

## 📚 Como Usar Este Documento

**Fluxo recomendado (ERRO-PROTOCOL.md passo 0):**

1. **Erro aconteceu** → Ler este documento PRIMEIRO
2. **Encontrou o problema aqui?** → Aplicar solução e continuar
3. **Não encontrou?** → Seguir ERRO-PROTOCOL.md (passos 1-5)

**O que NÃO fazer:**
- ❌ Reportar erro sem verificar este documento antes
- ❌ Tentar "consertar sozinho" sem verificar known issues
- ❌ Criar novo arquivo de debug

**O que fazer:**
- ✅ Buscar pelo sintoma neste documento (Ctrl+F)
- ✅ Aplicar solução se encontrar
- ✅ Reportar se não encontrar (seguindo protocolo)

---

**Versão:** 2.0.0
**Data:** 2026-02-14
**Changelog:** Removidos problemas 6-12 (já corrigidos, históricos). Mantidos apenas 5 problemas ativos. Reduzido de 594 → 217 linhas.
