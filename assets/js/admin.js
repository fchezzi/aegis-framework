/**
 * AEGIS Admin JavaScript
 */

// Confirmação de deleção
document.addEventListener('DOMContentLoaded', function() {
    // Selecionar todos os botões de deletar
    const deleteButtons = document.querySelectorAll('button[data-confirm-delete]');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-delete');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
});
