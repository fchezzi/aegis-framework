/**
 * Profile.js
 * Gerenciamento de perfil do usuário (avatar + senha)
 */

(function() {
    'use strict';

    // ============================================
    // AVATAR UPLOAD
    // ============================================

    const avatarInput = document.getElementById('avatar-input');
    const avatarForm = document.getElementById('avatar-form');
    const avatarImg = document.getElementById('avatar-img');
    const btnSaveAvatar = document.getElementById('btn-save-avatar');
    let selectedFile = null;

    if (avatarInput && avatarForm) {

        // Preview da imagem selecionada
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (!file) {
                return;
            }

            // Validar tipo
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('Apenas arquivos JPG, PNG ou WEBP são permitidos', 'error');
                avatarInput.value = '';
                return;
            }

            // Validar tamanho (2MB)
            const maxSize = 2 * 1024 * 1024; // 2MB em bytes
            if (file.size > maxSize) {
                showMessage('A imagem deve ter no máximo 2MB', 'error');
                avatarInput.value = '';
                return;
            }

            // Preview
            const reader = new FileReader();
            reader.onload = function(event) {
                // Se já existe img, atualiza src
                if (avatarImg) {
                    avatarImg.src = event.target.result;
                } else {
                    // Se não existe (tinha placeholder), cria img
                    const avatarPreview = document.querySelector('.avatar-preview');
                    avatarPreview.innerHTML = '<img src="' + event.target.result + '" alt="Avatar" id="avatar-img">';
                }

                // Mostra botão de salvar
                btnSaveAvatar.style.display = 'inline-flex';
                selectedFile = file;
            };
            reader.readAsDataURL(file);
        });

        // Submit do formulário de avatar
        avatarForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!selectedFile) {
                showMessage('Selecione uma imagem primeiro', 'error');
                return;
            }

            const formData = new FormData(avatarForm);
            const submitBtn = avatarForm.querySelector('button[type="submit"]');

            // Loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');

            // AJAX request
            const baseUrl = window.location.pathname.includes('/futebol-energia') ? '/futebol-energia' : '';
            fetch(baseUrl + '/profile/avatar', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');

                    // Esconde botão de salvar
                    btnSaveAvatar.style.display = 'none';
                    selectedFile = null;

                    // Atualiza ícones do Lucide (se o avatar mudou de placeholder para img)
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                } else {
                    showMessage(data.error || 'Erro ao atualizar avatar', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showMessage('Erro ao conectar com o servidor', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
            });
        });
    }

    // ============================================
    // PASSWORD FORM
    // ============================================

    const passwordForm = document.getElementById('password-form');

    if (passwordForm) {
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

            const formData = new FormData(passwordForm);
            const submitBtn = passwordForm.querySelector('button[type="submit"]');

            // Loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');

            // AJAX request
            const baseUrl = window.location.pathname.includes('/futebol-energia') ? '/futebol-energia' : '';
            fetch(baseUrl + '/profile/password', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');

                    // Limpar formulário
                    passwordForm.reset();
                } else {
                    showMessage(data.error || 'Erro ao atualizar senha', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showMessage('Erro ao atualizar senha: ' + error.message, 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
            });
        });
    }

    // ============================================
    // HELPER: Show Message
    // ============================================

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
        if (profileSection) {
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
    }

})();
