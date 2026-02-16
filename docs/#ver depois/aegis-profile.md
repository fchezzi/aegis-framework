# AEGIS Framework - Profile Module

## Visão Geral

O módulo Profile permite que members gerenciem seu próprio perfil de forma segura, podendo atualizar avatar e senha. Nome e email são exibidos mas não podem ser editados pelo próprio usuário (apenas via admin).

---

## 🏗️ Arquitetura

### Componentes

```
frontend/
├── controllers/
│   └── ProfileController.php       # Lógica de negócio
├── pages/
│   └── profile.php                 # Interface visual

assets/
├── sass/modules/
│   └── _m-profile.sass             # Estilos
└── js/
    └── profile.js                  # Interatividade

routes/
└── public.php                      # Rotas /profile
```

---

## 🎯 Features

### 1. Visualização de Informações
- Nome (read-only)
- Email (read-only)
- Avatar atual

### 2. Upload de Avatar
- Tipos suportados: JPG, PNG, WEBP
- Tamanho máximo: 2MB
- Preview em tempo real
- Validação client-side e server-side
- Upload via AJAX

### 3. Alteração de Senha
- Requer senha atual
- Validação de força
- Confirmação obrigatória
- Hash automático (bcrypt)
- Regeneração de sessão

---

## 📋 Rotas

### GET /profile
Renderiza a página de perfil do member logado.

**Middleware**: Requer `MemberAuth::check()`

**Controller**: `ProfileController::index()`

**Response**: HTML (profile.php)

---

### POST /profile/avatar

Upload e atualização de avatar.

**Middleware**: Requer `MemberAuth::check()`

**Controller**: `ProfileController::updateAvatar()`

**Request**:
```
Content-Type: multipart/form-data

csrf_token: string (required)
avatar: file (required, image/jpeg|png|webp, max 2MB)
```

**Response Success** (200):
```json
{
  "success": true,
  "message": "Avatar atualizado com sucesso!",
  "avatar_url": "/storage/uploads/members/avatars/uuid.jpg"
}
```

**Response Error** (400):
```json
{
  "success": false,
  "error": "Apenas arquivos JPG, PNG ou WEBP são permitidos"
}
```

**Validações**:
- CSRF token válido
- Member autenticado
- Arquivo de imagem válido
- Tamanho <= 2MB
- MIME type permitido

---

### POST /profile/password

Alteração de senha do member.

**Middleware**: Requer `MemberAuth::check()`

**Controller**: `ProfileController::updatePassword()`

**Request**:
```
Content-Type: multipart/form-data

csrf_token: string (required)
current_password: string (required)
new_password: string (required, min 8 chars)
confirm_password: string (required, must match new_password)
```

**Response Success** (200):
```json
{
  "success": true,
  "message": "Senha atualizada com sucesso!"
}
```

**Response Error** (400):
```json
{
  "success": false,
  "error": "Senha atual incorreta"
}
```

**Validações**:
- CSRF token válido
- Member autenticado
- Senha atual correta (bcrypt verify)
- Nova senha >= 8 caracteres
- Nova senha passa em `Security::validatePasswordStrength()`
- Confirmação coincide com nova senha

**Efeitos Colaterais**:
- Atualiza senha no banco (hash bcrypt)
- Regenera session ID (`session_regenerate_id(true)`)

---

## 🔐 Segurança

### CSRF Protection
Todos os formulários incluem token CSRF:
```php
<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
```

Validação server-side:
```php
Security::validateCSRF($_POST['csrf_token'] ?? '');
```

### Autorização
- Usuário só pode editar próprio perfil
- ID sempre vem de `MemberAuth::member()['id']`
- Nunca aceita ID via POST/GET

### Upload Seguro
- Validação via `Upload::image()`
- Salvamento em `/storage/uploads/members/avatars/`
- Nome único (UUID)
- MIME type verification

### Senha
- Hash bcrypt via `Security::hashPassword()`
- Verificação com `Security::verifyPassword()`
- Força validada com `Security::validatePasswordStrength()`
- Session regeneration após alteração

---

## 💻 ProfileController

### Métodos Públicos

#### `index()`
Renderiza página de perfil.

```php
public function index(): void
```

**Fluxo**:
1. Verifica `MemberAuth::check()`
2. Busca dados do member: `MemberAuth::member()`
3. Renderiza `profile.php`

---

#### `updateAvatar()`
Processa upload de avatar.

```php
public function updateAvatar(): void
```

**Fluxo**:
1. Valida CSRF
2. Obtém member ID da sessão
3. Valida e faz upload via `Upload::image()`
4. Atualiza banco via `MemberAuth::updateMember()`
5. Retorna JSON com URL do avatar

**Exceções**:
- `Exception` - Erro de validação ou upload

---

#### `updatePassword()`
Processa alteração de senha.

```php
public function updatePassword(): void
```

**Fluxo**:
1. Valida CSRF
2. Obtém member ID da sessão
3. Busca hash atual do banco
4. Verifica senha atual com `Security::verifyPassword()`
5. Valida força da nova senha
6. Atualiza via `MemberAuth::updateMember()`
7. Regenera sessão
8. Retorna JSON de sucesso

**Exceções**:
- `Exception` - Erro de validação
- `Throwable` - Erro interno

---

### Métodos Privados

#### `jsonSuccess($data, $statusCode = 200)`
Retorna resposta JSON de sucesso.

```php
private function jsonSuccess(array $data, int $statusCode = 200): void
```

**Exemplo**:
```php
$this->jsonSuccess(['message' => 'Sucesso!']);
// {"success": true, "message": "Sucesso!"}
```

---

#### `jsonError($message, $statusCode = 400)`
Retorna resposta JSON de erro.

```php
private function jsonError(string $message, int $statusCode = 400): void
```

**Exemplo**:
```php
$this->jsonError('Email inválido');
// {"success": false, "error": "Email inválido"}
```

---

## 🎨 Interface (profile.php)

### Estrutura HTML

```html
<section class="profile-section">

  <!-- Mensagens de feedback -->
  <div class="alert alert-success">...</div>
  <div class="alert alert-error">...</div>

  <div class="profile-grid">

    <!-- Card 1: Informações -->
    <div class="profile-card">
      <div class="profile-card-header">
        <h3>Informações do Perfil</h3>
        <p>Seus dados cadastrados</p>
      </div>
      <div class="profile-card-body">
        <div class="profile-info">
          <div class="info-item">
            <label>Nome</label>
            <p><?= $member['name'] ?></p>
          </div>
          <div class="info-item">
            <label>Email</label>
            <p><?= $member['email'] ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 2: Avatar -->
    <div class="profile-card">
      <div class="profile-card-header">
        <h3>Foto de Perfil</h3>
        <p>Atualize sua foto de perfil</p>
      </div>
      <div class="profile-card-body">
        <div class="avatar-section">
          <div class="avatar-preview">
            <!-- Imagem ou placeholder -->
          </div>
          <form id="avatar-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token">
            <input type="file" id="avatar-input" name="avatar">
            <label for="avatar-input" class="btn-upload">
              <span>Escolher Imagem</span>
            </label>
            <button type="submit" class="btn-primary">
              <span>Salvar Foto</span>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Card 3: Senha -->
    <div class="profile-card">
      <div class="profile-card-header">
        <h3>Alterar Senha</h3>
        <p>Mantenha sua conta segura</p>
      </div>
      <div class="profile-card-body">
        <form id="password-form">
          <input type="hidden" name="csrf_token">

          <div class="form-group">
            <label for="current-password">Senha Atual</label>
            <input type="password" id="current-password" name="current_password" class="form-control" required>
          </div>

          <div class="form-group">
            <label for="new-password">Nova Senha</label>
            <input type="password" id="new-password" name="new_password" class="form-control" required minlength="8">
            <small class="form-help">Mínimo 8 caracteres</small>
          </div>

          <div class="form-group">
            <label for="confirm-password">Confirmar Nova Senha</label>
            <input type="password" id="confirm-password" name="confirm_password" class="form-control" required minlength="8">
          </div>

          <button type="submit" class="btn-primary">
            <span>Atualizar Senha</span>
          </button>
        </form>
      </div>
    </div>

  </div>
</section>
```

---

## 🎨 Estilos (SASS)

### Classes Principais

#### `.profile-section`
Container principal com padding vertical.

#### `.profile-grid`
Grid responsivo para os 3 cards.

```sass
display: grid
grid-template-columns: repeat(auto-fit, minmax(350px, 1fr))
gap: 24px

+responsive(768px)
  grid-template-columns: 1fr
```

#### `.profile-card`
Card individual com background dark/light.

```sass
background: white
border: 1px solid var(--border-color)
border-radius: 12px

body.dark &
  background: rgba(0, 0, 0, 0.2)
```

#### `.profile-card-header`
Cabeçalho do card com título e subtítulo.

```sass
h3
  font-size: 20px
  letter-spacing: 0.25px
  color: #e7515a
  font-family: 'inter' !important

  body:not(.dark) &
    color: #0056ff

p
  font-size: 14px
  color: #999
```

#### `.info-item`
Item de informação read-only.

```sass
label
  font-size: 12px
  font-weight: 600
  text-transform: uppercase
  letter-spacing: 0.5px
  color: #999

p
  font-size: 14px
  padding: 15px 20px
  background: rgba(255, 255, 255, 0.05)
  border-radius: 6px

  body:not(.dark) &
    background: #f5f5f5
    color: #333
```

#### `.avatar-preview`
Preview circular do avatar.

```sass
width: 120px
height: 120px
border-radius: 50%
border: 3px solid var(--border-color)
```

#### `.avatar-placeholder`
Placeholder com gradient quando não há avatar.

```sass
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)
```

#### `.btn-upload`
Botão estilizado como label para input file.

```sass
background: #5b1d5c
padding: 15px 20px
border-radius: 6px

body:not(.dark) &
  background: #0056ff

&:hover
  background: #764ba2
```

#### `.form-control`
Inputs de formulário.

```sass
padding: 15px 20px
border: 1px solid transparent
border-radius: 6px !important
background: rgba(255, 255, 255, 0.05)

body:not(.dark) &
  background: #f5f5f5

&:focus
  border-color: #5b1d5c
```

#### `.btn-primary`
Botão de ação primária.

```sass
padding: 15px 20px
background: #5b1d5c
width: 100%
box-shadow: none !important
transform: none !important

body:not(.dark) &
  background: #0056ff

&:hover
  background: #764ba2 !important
```

#### `.alert`
Mensagens de feedback.

```sass
.alert-success
  background: #d4edda
  color: #155724

  body.dark &
    background: rgba(40, 167, 69, 0.2)
    color: #8bff8b

.alert-error
  background: #f8d7da
  color: #721c24

  body.dark &
    background: rgba(220, 53, 69, 0.2)
    color: #ff9999
```

---

## ⚡ JavaScript (profile.js)

### Avatar Upload

```javascript
// Preview da imagem
avatarInput.addEventListener('change', function(e) {
  const file = e.target.files[0];

  // Validar tipo
  const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
  if (!allowedTypes.includes(file.type)) {
    showMessage('Apenas arquivos JPG, PNG ou WEBP são permitidos', 'error');
    return;
  }

  // Validar tamanho (2MB)
  const maxSize = 2 * 1024 * 1024;
  if (file.size > maxSize) {
    showMessage('A imagem deve ter no máximo 2MB', 'error');
    return;
  }

  // Preview com FileReader
  const reader = new FileReader();
  reader.onload = function(event) {
    avatarImg.src = event.target.result;
    btnSaveAvatar.style.display = 'inline-flex';
  };
  reader.readAsDataURL(file);
});

// Submit AJAX
avatarForm.addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(avatarForm);
  const baseUrl = window.location.pathname.includes('/futebol-energia')
    ? '/futebol-energia' : '';

  fetch(baseUrl + '/profile/avatar', {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showMessage(data.message, 'success');
      btnSaveAvatar.style.display = 'none';
    } else {
      showMessage(data.error, 'error');
    }
  });
});
```

### Password Form

```javascript
passwordForm.addEventListener('submit', function(e) {
  e.preventDefault();

  const currentPassword = document.getElementById('current-password').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;

  // Validação client-side
  if (!currentPassword || !newPassword || !confirmPassword) {
    showMessage('Preencha todos os campos', 'error');
    return;
  }

  if (newPassword.length < 8) {
    showMessage('A nova senha deve ter no mínimo 8 caracteres', 'error');
    return;
  }

  if (newPassword !== confirmPassword) {
    showMessage('As senhas não coincidem', 'error');
    return;
  }

  // Submit AJAX
  const formData = new FormData(passwordForm);
  const baseUrl = window.location.pathname.includes('/futebol-energia')
    ? '/futebol-energia' : '';

  fetch(baseUrl + '/profile/password', {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showMessage(data.message, 'success');
      passwordForm.reset();
    } else {
      showMessage(data.error, 'error');
    }
  });
});
```

### Helper: showMessage()

```javascript
function showMessage(message, type) {
  // Remove mensagens anteriores
  const existingAlerts = document.querySelectorAll('.alert');
  existingAlerts.forEach(alert => alert.remove());

  // Cria nova mensagem
  const alertDiv = document.createElement('div');
  alertDiv.className = 'alert alert-' + type;
  alertDiv.textContent = message;

  // Insere no início da profile-section
  const profileSection = document.querySelector('.profile-section');
  profileSection.insertBefore(alertDiv, profileSection.firstChild);

  // Auto-remove após 5 segundos
  setTimeout(() => {
    alertDiv.style.transition = 'opacity 0.3s ease';
    alertDiv.style.opacity = '0';
    setTimeout(() => alertDiv.remove(), 300);
  }, 5000);

  // Scroll suave até a mensagem
  alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
```

---

## 🧪 Testes

### Teste Manual - Avatar

1. Acessar `/profile`
2. Clicar em "Escolher Imagem"
3. Selecionar imagem JPG (válida)
4. **Espera**: Preview aparecer
5. Clicar em "Salvar Foto"
6. **Espera**: Mensagem "Avatar atualizado com sucesso!"
7. **Espera**: Avatar atualizado no header

### Teste Manual - Senha

1. Preencher "Senha Atual" (correta)
2. Preencher "Nova Senha" (min 8 chars)
3. Preencher "Confirmar Nova Senha" (igual)
4. Clicar em "Atualizar Senha"
5. **Espera**: Mensagem "Senha atualizada com sucesso!"
6. **Espera**: Formulário limpo
7. Fazer logout e login com nova senha
8. **Espera**: Login bem-sucedido

### Testes de Validação

#### Avatar
- [ ] Arquivo maior que 2MB → erro
- [ ] Arquivo não-imagem (PDF, TXT) → erro
- [ ] Arquivo GIF → erro (não permitido)
- [ ] CSRF token inválido → erro 400

#### Senha
- [ ] Senha atual incorreta → "Senha atual incorreta"
- [ ] Nova senha < 8 chars → erro client-side
- [ ] Confirmação diferente → "As senhas não coincidem"
- [ ] CSRF token inválido → erro 400
- [ ] Usuário não autenticado → redirect `/login`

---

## 🔧 Troubleshooting

### Erro: "Senha atual incorreta" (mas senha está correta)

**Causa**: Senha no banco não está em formato bcrypt.

**Solução**:
1. Acessar `/admin/members`
2. Editar o member
3. Redefinir senha
4. Salvar
5. Testar novamente

---

### Avatar não aparece após upload

**Verificar**:
1. Permissões da pasta `/storage/uploads/members/avatars/` (775)
2. Campo `avatar` na tabela `members` foi atualizado
3. Console do navegador para erros JavaScript
4. Network tab para verificar response do POST

---

### Formulário não submete

**Verificar**:
1. Console do navegador para erros JavaScript
2. Arquivo `profile-min.js` foi gerado pelo CodeKit
3. CSRF token está sendo gerado (`view-source` e procurar `csrf_token`)

---

## 📚 Dependências

### Classes PHP
- `MemberAuth` - Autenticação e atualização de members
- `Security` - CSRF, validação de senha, hash
- `Upload` - Upload seguro de arquivos
- `DB` - Conexão e queries
- `Core` - Redirect e helpers

### JavaScript
- `FileReader` API (preview de imagens)
- `FormData` API (upload)
- `Fetch` API (AJAX)

### CSS
- Lucide Icons (ícones SVG)
- CSS Variables (`--border-color`, `--card-bg`)

---

## 🎯 Boas Práticas

### Sempre
✅ Validar CSRF em todos os POSTs
✅ Usar `MemberAuth::member()['id']` para ID do usuário
✅ Validar inputs client-side E server-side
✅ Hash senhas com bcrypt
✅ Regenerar sessão após alteração de senha
✅ Mensagens de erro claras e específicas

### Nunca
❌ Aceitar ID de usuário via POST/GET
❌ Armazenar senhas em plain text
❌ Confiar apenas em validação client-side
❌ Exibir stack traces para usuário
❌ Logar senhas (mesmo em desenvolvimento)

---

**Versão**: 1.0
**Data**: 23/01/2026
**Autor**: Claude Code + Fábio Chezzi
