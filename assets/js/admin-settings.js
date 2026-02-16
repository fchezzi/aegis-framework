/**
 * AEGIS Admin Settings - Color Pickers & SMTP Tests
 */

document.addEventListener('DOMContentLoaded', function() {

    // ===============================================
    // SINCRONIZAÇÃO COLOR PICKERS
    // ===============================================

    const colorPairs = [
        ['theme_color_main', 'theme_color_main_picker'],
        ['theme_color_second', 'theme_color_second_picker'],
        ['theme_color_third', 'theme_color_third_picker'],
        ['theme_color_four', 'theme_color_four_picker'],
        ['theme_color_five', 'theme_color_five_picker'],
        ['members_color_main', 'members_color_main_picker'],
        ['members_color_second', 'members_color_second_picker'],
        ['members_color_third', 'members_color_third_picker'],
        ['members_color_four', 'members_color_four_picker'],
        ['members_color_five', 'members_color_five_picker'],
        ['admin_color_main', 'admin_color_main_picker'],
        ['admin_color_second', 'admin_color_second_picker'],
        ['admin_color_third', 'admin_color_third_picker'],
        ['admin_color_four', 'admin_color_four_picker'],
        ['admin_color_five', 'admin_color_five_picker'],
        ['dash_bg_main', 'dash_bg_main_picker'],
        ['dash_bg_main_dark', 'dash_bg_main_dark_picker'],
        ['dash_bg_bread', 'dash_bg_bread_picker'],
        ['dash_bg_bread_dark', 'dash_bg_bread_dark_picker'],
        ['dash_bg_aside', 'dash_bg_aside_picker'],
        ['dash_bg_logo', 'dash_bg_logo_picker'],
        ['dash_bg_logo_dark', 'dash_bg_logo_dark_picker']
    ];

    colorPairs.forEach(([textId, pickerId]) => {
        const picker = document.getElementById(pickerId);
        const text = document.getElementById(textId);

        if (picker && text) {
            // Picker atualiza text
            picker.addEventListener('input', function() {
                text.value = this.value.toUpperCase();
            });

            // Text atualiza picker (apenas se hex válido)
            text.addEventListener('input', function() {
                if (/^#[a-fA-F0-9]{6}$/.test(this.value)) {
                    picker.value = this.value;
                }
            });
        }
    });

    // ===============================================
    // TESTE SMTP ALERTAS
    // ===============================================

    const testAlertBtn = document.getElementById('test-alert-smtp');
    const testAlertResult = document.getElementById('test-alert-result');

    if (testAlertBtn && testAlertResult) {
        testAlertBtn.addEventListener('click', async function() {
            const btn = this;
            const url = btn.getAttribute('data-url');

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader"></i> Enviando...';
            lucide.createIcons();
            testAlertResult.innerHTML = '';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    testAlertResult.innerHTML = '<div style="background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #28a745; font-size: 14px; line-height: 1.5;">✅ ' + data.message + '</div>';
                } else {
                    testAlertResult.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #dc3545; font-size: 14px; line-height: 1.5;">❌ ' + data.message + '</div>';
                }
            } catch (error) {
                testAlertResult.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #dc3545; font-size: 14px; line-height: 1.5;">❌ Erro ao testar: ' + error.message + '</div>';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="mail"></i> Testar SMTP de Alertas';
                lucide.createIcons();
            }
        });
    }

    // ===============================================
    // TESTE SMTP CLIENTE
    // ===============================================

    const testClientBtn = document.getElementById('test-client-smtp');
    const testClientResult = document.getElementById('test-client-result');

    if (testClientBtn && testClientResult) {
        testClientBtn.addEventListener('click', async function() {
            const btn = this;
            const url = btn.getAttribute('data-url');

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader"></i> Enviando...';
            lucide.createIcons();
            testClientResult.innerHTML = '';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    testClientResult.innerHTML = '<div style="background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #28a745; font-size: 14px; line-height: 1.5;">✅ ' + data.message + '</div>';
                } else {
                    testClientResult.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #dc3545; font-size: 14px; line-height: 1.5;">❌ ' + data.message + '</div>';
                }
            } catch (error) {
                testClientResult.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #dc3545; font-size: 14px; line-height: 1.5;">❌ Erro ao testar: ' + error.message + '</div>';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="mail"></i> Testar SMTP Cliente';
                lucide.createIcons();
            }
        });
    }

    // ===============================================
    // TESTE FTP
    // ===============================================

    const testFtpBtn = document.getElementById('test-ftp');
    const testFtpResult = document.getElementById('test-ftp-result');

    if (testFtpBtn && testFtpResult) {
        testFtpBtn.addEventListener('click', async function() {
            const btn = this;
            const url = btn.getAttribute('data-url');

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader"></i> Conectando...';
            lucide.createIcons();
            testFtpResult.innerHTML = '';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    testFtpResult.innerHTML = '<div style="background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #28a745; font-size: 14px; line-height: 1.5;">✅ ' + data.message + '</div>';
                } else {
                    testFtpResult.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #dc3545; font-size: 14px; line-height: 1.5;">❌ ' + data.message + '</div>';
                }
            } catch (error) {
                testFtpResult.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 6px; border-left: 4px solid #dc3545; font-size: 14px; line-height: 1.5;">❌ Erro ao testar: ' + error.message + '</div>';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="server"></i> Testar Conexão FTP';
                lucide.createIcons();
            }
        });
    }
});
