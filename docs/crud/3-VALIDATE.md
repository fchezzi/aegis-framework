# ✅ VALIDAÇÃO - CRUD AEGIS

**Este documento:** Como validar o CRUD criado.

**Quando usar:** Após completar PASSO 13 do 2-GUIDE.md

---

## 🎯 Script de Validação Automática

### Executar

```bash
php scripts/validate-crud.php [ControllerName]
```

**Exemplos:**
```bash
php scripts/validate-crud.php BannerHeroController
php scripts/validate-crud.php ProductController
php scripts/validate-crud.php CategoryController
```

---

## 📊 Interpretando o Score

### Score 100% (20/20)

```
✅ CRUD VÁLIDO!

Seu controller passou em todos os checks obrigatórios.
Está pronto para produção.
```

**Ação:** Entregar imediatamente.

---

### Score 90-99% (18-19/20)

```
⚠️  CRUD QUASE COMPLETO

Faltam alguns elementos não-críticos.
Revise os itens marcados com ❌ acima.
```

**Ação:** Revisar avisos, corrigir se possível, entregar.

---

### Score < 90% (< 18/20)

```
❌ CRUD INCOMPLETO

Faltam elementos CRÍTICOS de segurança ou estrutura.
Revise o GUIA-PRATICO.md e corrija os itens marcados com ❌.

→ Adicione validateCSRF() em store(), update(), destroy()
→ Adicione RateLimiter::check() em store(), update(), destroy()
→ Substitua SELECT * por campos específicos
```

**Ação:** Corrigir problemas, rodar novamente, atingir 90%+.

---

## 🔍 O Que o Script Valida

### 1. Estrutura Básica
- ✅ Herda de BaseController
- ✅ Tem 6 métodos (index, create, store, edit, update, destroy)

### 2. Segurança Crítica
- ✅ CSRF validation (`validateCSRF()`)
- ✅ Rate limiting check (`RateLimiter::check()`)
- ✅ Rate limiting increment (`RateLimiter::increment()`)
- ✅ Sanitização de inputs (`Security::sanitize()`)

### 3. Auditoria
- ✅ Logger (`Logger::getInstance()->audit()`)

### 4. Validações
- ✅ UUID validation (`Security::isValidUUID()`)
- ✅ Empty checks (`empty()`)

### 5. Nomenclatura
- ✅ Actions em maiúsculas (`CREATE_*`, `UPDATE_*`, `DELETE_*`)
- ✅ RateLimiter keys consistentes (`recurso_create`, etc)

### 6. Performance Obrigatória
- ❌ **NÃO** usa `SELECT *`
- ✅ Paginação no index() (`LIMIT`, `OFFSET`)
- ✅ Otimização de imagem (se tem upload)

### 7. Segurança Avançada
- ✅ Path traversal protection (se tem `unlink()`)
- ✅ UUID validation em edit/update/destroy

---

## ⚠️ Limitações do Script

**O que o script NÃO faz:**

❌ Executar o código (só analisa texto)
❌ Testar se funciona de verdade
❌ Detectar bugs lógicos
❌ Validar performance real
❌ Testar vulnerabilidades reais

**Validação manual ainda é necessária:**
- Testar CRUD funcionando
- Verificar queries no database
- Testar upload de arquivos
- Testar paginação com 100+ registros

---

## 🔧 Erros Comuns e Soluções

### Erro: "Não usa SELECT *"

**Problema:**
```php
$banners = $this->db()->query("SELECT * FROM tbl_banner");
```

**Solução:**
```php
$banners = $this->db()->query(
    "SELECT id, titulo, ativo, `order` FROM tbl_banner"
);
```

---

### Erro: "Sem paginação"

**Problema:**
```php
$registros = $this->db()->query("SELECT * FROM tbl_recurso");
```

**Solução:**
```php
$page = (int) ($_GET['page'] ?? 1);
$perPage = 50;
$offset = ($page - 1) * $perPage;

$registros = $this->db()->query(
    "SELECT id, nome FROM tbl_recurso LIMIT ? OFFSET ?",
    [$perPage, $offset]
);
```

---

### Erro: "Sem UUID validation"

**Problema:**
```php
public function edit($id) {
    $this->requireAuth();
    $registro = $this->db()->query("SELECT * FROM tbl WHERE id = ?", [$id]);
    // ...
}
```

**Solução:**
```php
public function edit($id) {
    $this->requireAuth();

    if (!Security::isValidUUID($id)) {
        http_response_code(400);
        die('ID inválido');
    }

    $registro = $this->db()->query("SELECT * FROM tbl WHERE id = ?", [$id]);
    // ...
}
```

---

### Erro: "Sem path traversal protection"

**Problema:**
```php
$oldImage = $existing['imagem'];
$fullPath = __DIR__ . '/../../' . $oldImage;
unlink($fullPath); // ❌ PERIGOSO
```

**Solução:**
```php
$oldImage = $existing['imagem'];

if (!empty($oldImage) && file_exists(__DIR__ . '/../../' . $oldImage)) {
    $uploadBasePath = realpath(__DIR__ . '/../../storage/uploads/');
    $oldImageFullPath = realpath(__DIR__ . '/../../' . $oldImage);

    if ($oldImageFullPath && strpos($oldImageFullPath, $uploadBasePath) === 0) {
        unlink($oldImageFullPath); // ✅ SEGURO
    } else {
        Logger::getInstance()->critical('PATH_TRAVERSAL_ATTEMPT', [...]);
        throw new Exception('Path inválido');
    }
}
```

---

### Erro: "Sem otimização de imagem"

**Problema:**
```php
move_uploaded_file($_FILES['imagem']['tmp_name'], $filePath);
// Sem otimização
```

**Solução:**
```php
move_uploaded_file($_FILES['imagem']['tmp_name'], $filePath);

// Otimizar
$this->optimizeImage($filePath, $mimeType);
```

---

## 📈 Processo de Validação Iterativa

**1. Primeira validação:**
```bash
php scripts/validate-crud.php RecursoController
# Score: 14/20 (70%) ❌
```

**2. Corrigir erros críticos:**
- Adicionar UUID validation
- Adicionar paginação
- Trocar SELECT *

**3. Segunda validação:**
```bash
php scripts/validate-crud.php RecursoController
# Score: 18/20 (90%) ✅
```

**4. Corrigir avisos (opcional):**
- Adicionar path protection
- Adicionar otimização de imagem

**5. Validação final:**
```bash
php scripts/validate-crud.php RecursoController
# Score: 20/20 (100%) ✅
```

**6. Entregar!**

---

## ✅ Checklist Manual Complementar

**Além do script, testar manualmente:**

### Funcionalidade Básica
```
[ ] Acessar /admin/recurso (index)
[ ] Clicar em "Novo" (create)
[ ] Preencher form e salvar (store)
[ ] Listar registro criado (index)
[ ] Clicar em "Editar" (edit)
[ ] Modificar dados e salvar (update)
[ ] Deletar registro (destroy)
```

### Paginação
```
[ ] Criar 60+ registros
[ ] Verificar navegação entre páginas
[ ] Verificar contadores "X de Y"
```

### Upload (se aplicável)
```
[ ] Upload de imagem JPG
[ ] Upload de imagem PNG
[ ] Rejeitar arquivo muito grande (>5MB)
[ ] Rejeitar tipo não permitido (.txt)
[ ] Verificar otimização (tamanho reduzido)
```

### Frontend Display (se aplicável)
```
[ ] Preview aparece no admin
[ ] Display funciona em página real
[ ] Apenas registros ativos aparecem
[ ] Ordenação correta
```

### Segurança
```
[ ] CSRF: tentar POST sem token → bloqueado
[ ] Rate Limit: fazer 6 requests rápidos → bloqueado
[ ] UUID: tentar ID inválido → erro 400
[ ] Upload: tentar .php → rejeitado
```

---

## 🎯 Score Mínimo Aceitável

**Para produção:**
- **Mínimo:** 18/20 (90%)
- **Ideal:** 20/20 (100%)

**Não entregar com score < 90%**

---

## 📞 Se Tiver Problemas

**Script não roda:**
```bash
# Verificar se PHP CLI está disponível
php -v

# Verificar caminho do script
ls -la scripts/validate-crud.php
```

**Controller não encontrado:**
```bash
# Verificar se arquivo existe
ls -la admin/controllers/RecursoController.php

# Nome correto (case-sensitive)
php scripts/validate-crud.php RecursoController  # ✅
php scripts/validate-crud.php recursocontroller  # ❌
```

**Score baixo inesperado:**
- Reler mensagens de erro do script
- Verificar exemplos neste documento
- Comparar com 2-GUIDE.md
- Corrigir um item por vez
- Validar novamente

---

**Fim da documentação de validação.**

**Voltar:** `/docs/crud/2-GUIDE.md` para corrigir problemas

**Próximo:** Entregar CRUD completo!
