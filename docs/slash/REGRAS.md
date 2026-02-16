# REGRAS AEGIS - Ler SEMPRE Antes de Qualquer Ação

> 12 regras invioláveis que previnem 95% dos erros. Não pule nenhuma.

---

## TOP 12 REGRAS INVIOLÁVEIS

### 1. Database Connection
```php
$db = DB::connect();  // ✅ CERTO
$db = DB::getInstance();  // ❌ ERRADO
```

### 2. Tabela de Admins
```sql
REFERENCES users(id)   -- ✅ CERTO (tabela se chama "users")
REFERENCES admins(id)  -- ❌ ERRADO (não existe)
```

### 3. IDs são UUID
```php
$id = Core::generateUUID();  // ✅ CERTO
$id = AUTO_INCREMENT         // ❌ ERRADO (nunca usar)
```

### 4. SQL Injection - Prepared Statements
```php
// ❌ ERRADO - Vulnerável
$query = "SELECT * FROM users WHERE email = '$email'";

// ✅ CERTO - Prepared statement
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### 5. CSRF em Forms POST
```php
// No form:
<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

// No controller:
Security::validateCSRF($_POST['csrf_token']);  // ✅ COM parâmetro
```

### 6. Sanitizar Inputs
```php
$nome = Security::sanitize($_POST['nome']);  // ✅ SEMPRE
echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');  // ✅ Output
```

### 7. Auth em Controllers Admin
```php
public function index() {
    Auth::require();  // ✅ PRIMEIRA LINHA, SEMPRE
    // resto do código
}
```

### 8. Router é Estático
```php
Router::get('/path', ...);   // ✅ CERTO
$router->add('/path', ...);  // ❌ ERRADO
```

### 9. Upload de Arquivos
```php
$result = Upload::image($_FILES['foto'], 'destino');  // ✅ CERTO

if ($result['success']) {
    $path = $result['path'];      // ✅ 'path' não 'file'
} else {
    $erro = $result['message'];   // ✅ 'message' não 'error'
}

// Deletar arquivo
Upload::delete($path);  // ✅ CERTO (não unlink)
```

### 10. Módulos ≠ Páginas
```
Módulos → module.json é fonte de verdade
Páginas → tabela pages é fonte de verdade

NUNCA misturar os dois
```

### 11. Ordem de Rotas
```php
// ✅ CERTO: Específicas ANTES de genéricas
Router::get('/blog', ...);           // 1. Fixo
Router::get('/blog/:slug', ...);     // 2. Com param
Router::get('/:slug', ...);          // 3. Genérico (ÚLTIMO)

// ❌ ERRADO: Genérico primeiro captura tudo
Router::get('/:slug', ...);          // Captura /blog!
Router::get('/blog', ...);           // Nunca executa
```

### 12. SASS Puro (não SCSS)
```sass
// ✅ CERTO - .sass com 2 tabs indentação
.container
  display: flex
  padding: 20px

  .item
    color: red
    font-size: 16px

// ❌ ERRADO - SCSS com chaves
.container {
  display: flex;
}
```

---

## CHECKLIST ANTES DE MODIFICAR CÓDIGO

```
□ DB::connect() (não getInstance)
□ REFERENCES users(id) (não admins)
□ Core::generateUUID() para IDs
□ Prepared statements (não concatenar SQL)
□ Security::validateCSRF($_POST['csrf_token'])
□ Security::sanitize() em inputs
□ Auth::require() primeira linha admin
□ Upload::image() e Upload::delete()
□ Router::get() estático
□ Router order: específicas ANTES
□ SASS .sass com 2 tabs (não SCSS)
□ module.json para módulos
```

---

## QUANDO ERRO ACONTECER

**PARE IMEDIATAMENTE e siga este fluxo:**

1. **LER** `ERRO-PROTOCOL.md` (protocolo obrigatório)
2. **VERIFICAR** `memory/known-issues.md` (5 problemas conhecidos)
3. **REPORTAR** se não encontrar solução
4. **AGUARDAR** decisão do usuário

**NÃO:**
- ❌ Continuar modificando código
- ❌ Criar arquivos de debug
- ❌ Tentar "outra abordagem"
- ❌ Assumir que consertou

---

## PADRÕES DE CÓDIGO PRONTOS

**Para código completo copiar/colar, consultar:**
- `memory/code-patterns.md` - Controller CRUD, Upload, SASS, Router
- `memory/module-guide.md` - Criar módulos (checklist + templates)

---

**Versão:** 3.0.0
**Data:** 2026-02-14
**Linhas:** 100 (reduzido de 318)
