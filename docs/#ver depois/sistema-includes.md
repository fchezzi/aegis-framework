# Sistema de Includes - AEGIS Framework

## Visão Geral

Sistema completo de gerenciamento de includes reutilizáveis para o frontend, com interface administrativa, controle de versão via backup, marcação de criticidade e validação automática.

---

## 1. Estrutura de Arquivos

### 1.1 Diretório
```
frontend/includes/
├── _header.php           (crítico)
├── _footer.php           (crítico)
├── _aside.php
├── _menu-dinamico.php
├── _dash-header.php
├── _dash-footer.php
├── _dash-aside.php
└── *.php.backup          (backups automáticos)
```

**Convenção:**
- Todos os includes começam com `_` (underscore)
- Extensão: `.php`
- Backups: `{arquivo}.php.backup`

### 1.2 Admin
```
admin/
├── controllers/
│   └── IncludesController.php
└── views/includes/
    ├── index.php          (listagem)
    ├── create.php         (criar)
    └── edit.php           (editar)
```

---

## 2. Formato dos Arquivos

### 2.1 Estrutura Base
```php
<?php
/**
 * Include: nome-descritivo
 */
?>
<div>Conteúdo HTML</div>
```

### 2.2 Include Crítico
```php
<?php
/**
 * Include: header
 * @critical: true
 */
?>
<header>Conteúdo do header</header>
```

### 2.3 Include com PHP Inline
```php
<?php
/**
 * Include: menu-dinamico
 */

$items = Database::query("SELECT * FROM menu_items");
?>
<nav>
    <?php foreach ($items as $item): ?>
        <a href="<?= $item['url'] ?>"><?= $item['title'] ?></a>
    <?php endforeach; ?>
</nav>
```

---

## 3. Sistema de Criticidade

### 3.1 O que é um Include Crítico?

Include marcado como essencial para o funcionamento do sistema. Não pode ser deletado e tem prioridade na listagem.

### 3.2 Como Marcar

**Via Interface:**
1. Acesse `/admin/includes`
2. Edite o include desejado
3. Marque checkbox "Marcar como crítico"
4. Salve

**Manualmente no arquivo:**
```php
<?php
/**
 * Include: nome
 * @critical: true    ← Adicionar esta linha
 */
?>
```

### 3.3 Detecção

O sistema detecta includes críticos de duas formas:

1. **Tag @critical no comentário** (método preferido)
   ```php
   if (preg_match('/@critical\s*:\s*true/i', $content)) {
       return true;
   }
   ```

2. **Array hardcoded** (fallback/compatibilidade)
   ```php
   private $protectedIncludes = ['_header.php', '_footer.php'];
   ```

### 3.4 Efeitos
- ✅ Badge roxo "CRÍTICO" na listagem
- ✅ Aparece primeiro (ordenação prioritária)
- ✅ Botão "Deletar" removido
- ✅ Aviso especial na edição
- ❌ Não pode ser deletado via interface
- ❌ Não pode ser deletado via API

---

## 4. Controller - IncludesController

### 4.1 Propriedades
```php
private $includesDir = ROOT_PATH . 'frontend/includes/';
private $protectedIncludes = ['_header.php', '_footer.php'];
```

### 4.2 Métodos Públicos

#### index()
Lista todos os includes com paginação e ordenação.

**Parâmetros GET:**
- `page` - Número da página (padrão: 1)
- `sort` - Coluna para ordenar (name|description|size|modified)
- `order` - Direção (asc|desc)

**Retorna:**
```php
$includes = [
    [
        'name' => '_header.php',
        'description' => 'Header do site',
        'size' => '2.5 KB',
        'size_bytes' => 2560,
        'modified' => '07/02/2026 10:30',
        'modified_timestamp' => 1738930200,
        'is_protected' => true,
        'has_backup' => true
    ],
    // ...
];

$pagination = [
    'current' => 1,
    'total' => 3,
    'per_page' => 15,
    'total_items' => 42
];
```

#### create()
Exibe formulário de criação.

#### store()
Salva novo include.

**POST:**
- `name` - Nome sem underscore e .php
- `code` - Código HTML/PHP
- `is_critical` - 1 se marcado
- `csrf_token` - Token CSRF

**Validações:**
1. Nome obrigatório
2. Formato: `[a-z0-9-]+`
3. Não pode existir arquivo com mesmo nome
4. Validação de sintaxe PHP

**Processo:**
1. Sanitizar nome
2. Adicionar `_` e `.php`
3. Validar sintaxe via `php -l`
4. Criar arquivo com comentário
5. Redirect para listagem

#### edit($name)
Exibe formulário de edição.

**Parâmetro:**
- `$name` - Nome do arquivo (ex: `_header.php`)

**Retorna:**
- `$code` - Conteúdo do arquivo
- `$isProtected` - Se é crítico
- `$hasBackup` - Se tem backup

#### update($name)
Atualiza include existente.

**POST:**
- `code` - Código atualizado
- `is_critical` - 1 se marcado
- `csrf_token` - Token CSRF

**Processo:**
1. Criar backup
2. Processar flag @critical
3. Validar sintaxe
4. Salvar

#### destroy($name)
Deleta include (se não for crítico).

**Validações:**
1. Não pode ser crítico
2. Não pode estar em uso

**Verifica uso em:**
- `frontend/templates/*.php`
- `frontend/pages/*.php`

#### restore($name)
Restaura backup.

**Processo:**
1. Verificar se existe `.backup`
2. Copiar backup sobre arquivo atual
3. Redirect para edição

### 4.3 Métodos Privados

#### processIsCriticalFlag($code, $filename, $isCritical)
Adiciona ou remove tag @critical do código.

**Retorna:** String com código processado

**Lógica:**
1. Se tem comentário Include:
   - Remove @critical existente
   - Adiciona novo se `$isCritical === true`
   - Adiciona `?>` se seguido de HTML

2. Se não tem comentário e `$isCritical`:
   - Cria comentário completo
   - Adiciona @critical
   - Adiciona `?>` se necessário

3. Se não é crítico e não tem comentário:
   - Retorna código original

#### isCriticalInclude($filePath)
Verifica se include é crítico.

**Retorna:** Boolean

**Ordem de verificação:**
1. Tag @critical no arquivo
2. Array $protectedIncludes (fallback)

#### isIncludeInUse($filename)
Verifica se include está sendo usado.

**Retorna:** Array de strings

**Exemplo:**
```php
[
    'Template: home.php',
    'Página: sobre.php'
]
```

#### getFileDescription($filePath)
Extrai descrição do comentário Include.

**Regex:**
```php
preg_match('/\*\s*Include:\s*(.+?)\n/', $content, $matches)
```

#### formatBytes($bytes)
Formata bytes para leitura humana.

**Exemplos:**
- `1024` → `1 KB`
- `1048576` → `1 MB`
- `512` → `512 bytes`

#### getPhpPath()
Detecta caminho do executável PHP.

**Ordem de detecção:**
1. `PHP_BINARY` (se definido)
2. MAMP: `/Applications/MAMP/bin/php/phpX.X.X/bin/php`
3. Fallback: `php` (no PATH)

---

## 5. Views

### 5.1 index.php (Listagem)

**Helpers:**
```php
function getSortUrl($column, $currentSort, $currentOrder)
function getSortIcon($column, $currentSort, $currentOrder)
function getPaginationUrl($page, $currentSort, $currentOrder)
```

**Estrutura:**
- Header com título e botão "Novo Include"
- Alerts (success/error)
- Empty state (se vazio)
- Tabela com ordenação
- Paginação (se > 15 itens)

**Colunas:**
- Nome
- Descrição
- Como Usar (copiável)
- Tamanho
- Modificado
- Status (badges)
- Ações

### 5.2 create.php (Criar)

**Campos:**
- Nome (text input)
- Código (textarea)
- Marcar como crítico (checkbox)

**Validação HTML5:**
```html
<input
    type="text"
    pattern="[a-zA-Z0-9-]+"
    title="Apenas letras, números e hífen"
    required
>
```

### 5.3 edit.php (Editar)

**Campos:**
- Código (textarea grande)
- Marcar como crítico (checkbox, pré-marcado se protegido)

**Botões:**
- Salvar Alterações
- Cancelar
- Restaurar Backup (se existe)

**Avisos:**
- Warning se crítico
- Info sobre backup

---

## 6. Rotas

```php
// Listagem
GET /admin/includes
→ IncludesController@index

// Criar
GET /admin/includes/create
→ IncludesController@create

POST /admin/includes
→ IncludesController@store

// Editar
GET /admin/includes/{name}/edit
→ IncludesController@edit

POST /admin/includes/{name}
→ IncludesController@update

// Deletar
POST /admin/includes/{name}/delete
→ IncludesController@destroy

// Restaurar
POST /admin/includes/{name}/restore
→ IncludesController@restore
```

---

## 7. SASS

### 7.1 Arquivo: _m-includes.sass

**Principais classes:**

```sass
.m-includes
  // Container principal

.m-includes__table
  // Tabela de listagem

.m-includes__code-copy
  // Código clicável para copiar
  cursor: pointer
  user-select: all
  &:hover
    background: #e3f2fd

.m-includes__badge
  // Badge genérico
  &--critical
    background: #9b59b6  // Roxo
  &--backup
    background: #3498db  // Azul

.m-includes__copied-feedback
  // Toast de "Código copiado!"
  position: fixed
  animation: slideIn 0.3s

.m-includes__form-checkbox
  // Checkbox customizado
  input[type="checkbox"]
    accent-color: #9b59b6
```

---

## 8. JavaScript

### 8.1 Copy to Clipboard

**Função:**
```javascript
function copyToClipboard(element) {
    const text = element.textContent;
    navigator.clipboard.writeText(text).then(() => {
        const feedback = document.createElement('div');
        feedback.className = 'm-includes__copied-feedback';
        feedback.textContent = '✓ Código copiado!';
        document.body.appendChild(feedback);

        setTimeout(() => {
            feedback.remove();
        }, 2000);
    }).catch(err => {
        alert('Erro ao copiar: ' + err);
    });
}
```

**HTML:**
```html
<code
    class="m-includes__code-copy"
    onclick="copyToClipboard(this)"
    title="Clique para copiar"
>
    Core::requireInclude('frontend/includes/_header.php', true);
</code>
```

### 8.2 Confirmação de Delete

**HTML:**
```html
<button
    type="submit"
    data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar o include:&#10;_header.php&#10;&#10;Esta ação NÃO pode ser desfeita!"
>
    Deletar
</button>
```

**JavaScript (admin.js):**
```javascript
document.querySelectorAll('[data-confirm-delete]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirmDelete)) {
            e.preventDefault();
        }
    });
});
```

---

## 9. Segurança

### 9.1 CSRF Protection
Todos os formulários POST incluem:
```php
<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
```

Validação no controller:
```php
Security::validateCSRF($_POST['csrf_token']);
```

### 9.2 Sanitização
```php
// Nome é sanitizado
$name = Security::sanitize($_POST['name'] ?? '');

// Código NÃO é sanitizado (preservar PHP)
$code = $_POST['code'] ?? '';
```

### 9.3 Validação de Nome
```php
if (!preg_match('/^[a-z0-9-]+$/i', $name)) {
    $_SESSION['error'] = 'Nome inválido';
    return;
}
```

### 9.4 Validação de Sintaxe
```php
$tempFile = tempnam(sys_get_temp_dir(), 'validate_');
file_put_contents($tempFile, $code);

$phpPath = $this->getPhpPath();
exec(
    escapeshellarg($phpPath) . ' -l ' . escapeshellarg($tempFile) . ' 2>&1',
    $output,
    $returnCode
);

unlink($tempFile);

if ($returnCode !== 0) {
    $_SESSION['error'] = 'Erro de sintaxe: ' . implode('<br>', $output);
    return;
}
```

### 9.5 Proteção contra Exclusão
```php
// Verificar se é crítico
if ($this->isCriticalInclude($filePath)) {
    $_SESSION['error'] = 'Este include é crítico e não pode ser deletado!';
    return;
}

// Verificar se está em uso
$usage = $this->isIncludeInUse($name);
if (!empty($usage)) {
    $_SESSION['error'] = 'Include em uso: ' . implode(', ', $usage);
    return;
}
```

---

## 10. Uso no Frontend

### 10.1 Chamar Include
```php
Core::requireInclude('frontend/includes/_header.php', true);
```

**Parâmetros:**
- `$path` - Caminho relativo a ROOT_PATH
- `$required` - Se true, gera erro fatal se não encontrar

### 10.2 Include Condicional
```php
if ($showAside) {
    Core::requireInclude('frontend/includes/_aside.php', false);
}
```

### 10.3 Em Templates
```php
<?php
/**
 * Template: Home
 */

Core::requireInclude('frontend/includes/_header.php', true);
?>

<main>
    <h1>Conteúdo</h1>
</main>

<?php
Core::requireInclude('frontend/includes/_footer.php', true);
?>
```

---

## 11. Fluxos de Uso

### 11.1 Criar Novo Include

1. Acesse `/admin/includes`
2. Clique "Novo Include"
3. Preencha:
   - Nome: `navigation`
   - Código: `<nav>...</nav>`
   - Marque "crítico" se aplicável
4. Clique "Criar Include"
5. Arquivo criado: `_navigation.php`

### 11.2 Editar Include

1. Acesse `/admin/includes`
2. Clique "Editar" no include desejado
3. Modifique código
4. Marque/desmarque "crítico"
5. Clique "Salvar"
6. Backup criado automaticamente

### 11.3 Restaurar Backup

1. Acesse edição do include
2. Se aparecer badge "BACKUP"
3. Clique "Restaurar Backup"
4. Confirme
5. Arquivo restaurado do `.backup`

### 11.4 Deletar Include

1. Acesse `/admin/includes`
2. Se NÃO for crítico, botão "Deletar" aparece
3. Clique "Deletar"
4. Confirme no alert
5. Sistema verifica se está em uso
6. Se não estiver, deleta arquivo e backup

---

## 12. Troubleshooting

### 12.1 Erro de Sintaxe ao Salvar

**Problema:**
```
Erro de sintaxe no código: Parse error: unexpected token "<"
```

**Causa:**
Código tem HTML sem tag `?>` para fechar PHP.

**Solução:**
O sistema adiciona `?>` automaticamente. Se erro persistir:
1. Verifique se há `<?php` sem fechar
2. Valide código localmente: `php -l arquivo.php`

### 12.2 Include não Aparece na Listagem

**Causa possível:**
- Nome não começa com `_`
- Extensão não é `.php`
- Arquivo fora de `frontend/includes/`

**Solução:**
Renomear arquivo para `_nome.php` e mover para diretório correto.

### 12.3 Não Consegue Deletar

**Causa possível:**
- Include marcado como crítico
- Include está em uso

**Solução:**
1. Remova marcação de crítico
2. Remova referências em templates/páginas
3. Tente novamente

### 12.4 Badge "CRÍTICO" não Aparece

**Causa possível:**
Tag @critical não detectada.

**Solução:**
Edite arquivo manualmente e verifique formato:
```php
<?php
/**
 * Include: nome
 * @critical: true    ← Deve ser exatamente assim
 */
?>
```

---

## 13. Boas Práticas

### 13.1 Nomenclatura
- **Use nomes descritivos:** `_navigation.php`, `_user-menu.php`
- **Evite abreviações:** `_nav.php` ❌ → `_navigation.php` ✅
- **Use hífen, não underscore:** `_menu-principal.php` ✅

### 13.2 Organização
- Agrupe includes por funcionalidade
- Use descrições claras no comentário
- Marque como crítico apenas o essencial

### 13.3 Código
- Mantenha includes pequenos e focados
- Evite lógica complexa em includes
- Prefira passar variáveis do template

### 13.4 Segurança
- Sempre escape outputs: `<?= htmlspecialchars($var) ?>`
- Valide inputs antes de usar
- Use prepared statements para queries

---

## 14. API Reference

### Core::requireInclude()

```php
/**
 * Requer um arquivo include
 *
 * @param string $path Caminho relativo a ROOT_PATH
 * @param bool $required Se true, gera erro fatal se não encontrar
 * @return void
 */
public static function requireInclude($path, $required = false)
```

**Exemplo:**
```php
Core::requireInclude('frontend/includes/_header.php', true);
```

---

## 15. Changelog

### v1.0.0 - 07/02/2026
- ✅ Sistema completo de CRUD
- ✅ Marcação de criticidade via interface
- ✅ Backup automático
- ✅ Validação de sintaxe
- ✅ Paginação e ordenação
- ✅ Copy to clipboard
- ✅ Verificação de uso

---

**Framework AEGIS v15.2.4**
**Desenvolvido com Claude Code**
