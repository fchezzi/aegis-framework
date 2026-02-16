<?php
/**
 * Settings Controller
 * Gerenciamento de configurações do sistema
 */

class SettingsController {

    /**
     * Exibir formulário de configurações
     */
    public function index() {
        Auth::require();

        // Obter todas as configurações
        $settings = Settings::all();

        require_once ROOT_PATH . 'admin/views/settings.php';
    }

    /**
     * Salvar configurações
     */
    public function update() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        // Obter dados do form
        $adminEmail = Security::sanitize($_POST['admin_email'] ?? '');
        $adminName = Security::sanitize($_POST['admin_name'] ?? 'AEGIS');
        $maintenanceMode = isset($_POST['maintenance_mode']) ? true : false;
        $timezone = Security::sanitize($_POST['timezone'] ?? 'America/Sao_Paulo');

        // Integrações e APIs
        $tinymceApiKey = Security::sanitize($_POST['tinymce_api_key'] ?? 'no-api-key');
        $gtmId = Security::sanitize($_POST['gtm_id'] ?? '');
        $uptimeRobotApiKey = Security::sanitize($_POST['uptime_robot_api_key'] ?? '');

        // FTP Configuration
        $ftpHost = Security::sanitize($_POST['ftp_host'] ?? '');
        $ftpPort = (int)($_POST['ftp_port'] ?? 21);
        $ftpUsername = Security::sanitize($_POST['ftp_username'] ?? '');
        $ftpPassword = $_POST['ftp_password'] ?? ''; // Não sanitizar (pode ter caracteres especiais)
        $ftpRemotePath = Security::sanitize($_POST['ftp_remote_path'] ?? '/public_html');

        // SMTP Configuration
        $smtpHost = Security::sanitize($_POST['smtp_host'] ?? 'smtp.gmail.com');
        $smtpPort = (int)($_POST['smtp_port'] ?? 587);
        $smtpUsername = Security::sanitize($_POST['smtp_username'] ?? '');
        $smtpPassword = $_POST['smtp_password'] ?? ''; // Não sanitizar (pode ter caracteres especiais)
        $smtpFromEmail = Security::sanitize($_POST['smtp_from_email'] ?? '');
        $smtpFromName = Security::sanitize($_POST['smtp_from_name'] ?? 'AEGIS');
        $smtpEncryption = Security::sanitize($_POST['smtp_encryption'] ?? 'tls');

        // SMTP Alertas Técnicos
        $alertSmtpHost = Security::sanitize($_POST['alert_smtp_host'] ?? '');
        $alertSmtpPort = (int)($_POST['alert_smtp_port'] ?? 587);
        $alertSmtpUsername = Security::sanitize($_POST['alert_smtp_username'] ?? '');
        $alertSmtpPassword = $_POST['alert_smtp_password'] ?? '';
        $alertSmtpFromEmail = Security::sanitize($_POST['alert_smtp_from_email'] ?? '');
        $alertSmtpFromName = Security::sanitize($_POST['alert_smtp_from_name'] ?? 'AEGIS Alertas');
        $alertSmtpEncryption = Security::sanitize($_POST['alert_smtp_encryption'] ?? 'tls');

        // RD Station Configuration
        $rdstationEnabled = isset($_POST['rdstation_enabled']) ? true : false;
        $rdstationApiKey = Security::sanitize($_POST['rdstation_api_key'] ?? '');

        // Tema (cores e fontes)
        $themeColorMain = Security::sanitize($_POST['theme_color_main'] ?? '#6c10b8');
        $themeColorSecond = Security::sanitize($_POST['theme_color_second'] ?? '#FAEA26');
        $themeColorThird = Security::sanitize($_POST['theme_color_third'] ?? '#1D4872');
        $themeColorFour = Security::sanitize($_POST['theme_color_four'] ?? '#A39D8F');
        $themeColorFive = Security::sanitize($_POST['theme_color_five'] ?? '#A39D8F');

        // 🔧 Fontes Frontend: Sanitizar family names (sem aspas, apenas nome da família)
        $fontTitle = Security::sanitize($_POST['font_title'] ?? 'system-ui');
        $fontText = Security::sanitize($_POST['font_text'] ?? 'system-ui');

        // 🎨 Members - Cores
        $membersColorMain = Security::sanitize($_POST['members_color_main'] ?? '#6c10b8');
        $membersColorSecond = Security::sanitize($_POST['members_color_second'] ?? '#FAEA26');
        $membersColorThird = Security::sanitize($_POST['members_color_third'] ?? '#1D4872');
        $membersColorFour = Security::sanitize($_POST['members_color_four'] ?? '#A39D8F');
        $membersColorFive = Security::sanitize($_POST['members_color_five'] ?? '#A39D8F');

        // 🔧 Members - Fontes
        $membersFontTitle = Security::sanitize($_POST['members_font_title'] ?? 'system-ui');
        $membersFontText = Security::sanitize($_POST['members_font_text'] ?? 'system-ui');

        // 🎨 Admin - Cores
        $adminColorMain = Security::sanitize($_POST['admin_color_main'] ?? '#6c10b8');
        $adminColorSecond = Security::sanitize($_POST['admin_color_second'] ?? '#FAEA26');
        $adminColorThird = Security::sanitize($_POST['admin_color_third'] ?? '#1D4872');
        $adminColorFour = Security::sanitize($_POST['admin_color_four'] ?? '#A39D8F');
        $adminColorFive = Security::sanitize($_POST['admin_color_five'] ?? '#A39D8F');

        // 🔧 Admin - Fontes
        $adminFontTitle = Security::sanitize($_POST['admin_font_title'] ?? 'system-ui');
        $adminFontText = Security::sanitize($_POST['admin_font_text'] ?? 'system-ui');

        // 🎛️ Dashboard Colors
        $dashBgHeader = trim($_POST['dash_bg_header'] ?? 'linear-gradient(135deg, #160f47 0%, #5b1d5c 100%)');
        $dashBgHeaderDark = trim($_POST['dash_bg_header_dark'] ?? 'linear-gradient(45deg, #E5E6E7 0%, #BBBDBF 100%)');
        $dashBgMain = Security::sanitize($_POST['dash_bg_main'] ?? '#160f47');
        $dashBgMainDark = Security::sanitize($_POST['dash_bg_main_dark'] ?? '#E5E6E7');
        $dashBgBread = Security::sanitize($_POST['dash_bg_bread'] ?? '#282542');
        $dashBgBreadDark = Security::sanitize($_POST['dash_bg_bread_dark'] ?? '#F0F0F0');
        $dashBgAside = Security::sanitize($_POST['dash_bg_aside'] ?? '#0056ff');
        $dashBgLogo = Security::sanitize($_POST['dash_bg_logo'] ?? '#0056ff');
        $dashBgLogoDark = Security::sanitize($_POST['dash_bg_logo_dark'] ?? '#ffffff');

        // 📊 PageSpeed Insights
        $pagespeedEnabled = isset($_POST['pagespeed_enabled']) ? 1 : 0;
        $pagespeedApiKey = Security::sanitize($_POST['pagespeed_api_key'] ?? '');
        $pagespeedAutoEnabled = isset($_POST['pagespeed_auto_enabled']) ? 1 : 0;
        $pagespeedFrequency = Security::sanitize($_POST['pagespeed_frequency'] ?? 'daily');
        $pagespeedTime = Security::sanitize($_POST['pagespeed_time'] ?? '03:00');
        $pagespeedStrategyMobile = isset($_POST['pagespeed_strategy_mobile']) ? 1 : 0;
        $pagespeedStrategyDesktop = isset($_POST['pagespeed_strategy_desktop']) ? 1 : 0;
        $pagespeedAlertThreshold = (int) ($_POST['pagespeed_alert_threshold'] ?? 70);
        $pagespeedAlertEmail = Security::sanitize($_POST['pagespeed_alert_email'] ?? '');

        // Gerar webhook secret se não existir
        $currentSettings = Settings::all();
        $pagespeedWebhookSecret = $currentSettings['pagespeed_webhook_secret'] ?? Security::generateUUID();

        // 🖼️ Upload de Logo (se enviado)
        $siteLogo = null;
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            // Deletar logo antiga ANTES do upload (mesmo nome = dash-logo.svg)
            $currentSettings = Settings::all();
            if (!empty($currentSettings['site_logo'])) {
                $oldLogoPath = ROOT_PATH . 'storage/' . $currentSettings['site_logo'];
                if (file_exists($oldLogoPath)) {
                    @unlink($oldLogoPath);
                }
            }

            // Fazer upload usando classe Upload (aceita: JPG, PNG, GIF, WEBP, SVG)
            $uploadResult = Upload::uploadFile($_FILES['site_logo'], 'logos', 1, 'dash-logo', false); // máx 1MB, nome fixo, sem subpastas

            if ($uploadResult['success']) {
                $siteLogo = $uploadResult['path'];
            } else {
                $_SESSION['error'] = 'Erro no upload da logo: ' . $uploadResult['error'];
                Core::redirect('/admin/settings');
                return;
            }
        }

        // 🎯 Upload de Favicon Frontend (se enviado)
        $favicon = null;
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            // Deletar favicon antigo
            $currentSettings = Settings::all();
            if (!empty($currentSettings['favicon'])) {
                $oldFaviconPath = ROOT_PATH . 'storage/' . $currentSettings['favicon'];
                if (file_exists($oldFaviconPath)) {
                    @unlink($oldFaviconPath);
                }
            }

            // Upload novo (ICO, PNG, SVG - máx 500KB)
            $uploadResult = Upload::uploadFile($_FILES['favicon'], 'favicon', 0.5, 'frontend-favicon', false);

            if ($uploadResult['success']) {
                $favicon = $uploadResult['path'];
            } else {
                $_SESSION['error'] = 'Erro no upload do favicon frontend: ' . $uploadResult['error'];
                Core::redirect('/admin/settings');
                return;
            }
        }

        // 🎯 Upload de Favicon Members (se enviado)
        $membersFavicon = null;
        if (isset($_FILES['members_favicon']) && $_FILES['members_favicon']['error'] === UPLOAD_ERR_OK) {
            // Deletar favicon antigo
            $currentSettings = Settings::all();
            if (!empty($currentSettings['members_favicon'])) {
                $oldFaviconPath = ROOT_PATH . 'storage/' . $currentSettings['members_favicon'];
                if (file_exists($oldFaviconPath)) {
                    @unlink($oldFaviconPath);
                }
            }

            // Upload novo (ICO, PNG, SVG - máx 500KB)
            $uploadResult = Upload::uploadFile($_FILES['members_favicon'], 'favicon', 0.5, 'members-favicon', false);

            if ($uploadResult['success']) {
                $membersFavicon = $uploadResult['path'];
            } else {
                $_SESSION['error'] = 'Erro no upload do favicon members: ' . $uploadResult['error'];
                Core::redirect('/admin/settings');
                return;
            }
        }

        // 🎯 Upload de Favicon Admin (se enviado)
        $adminFavicon = null;
        if (isset($_FILES['admin_favicon']) && $_FILES['admin_favicon']['error'] === UPLOAD_ERR_OK) {
            // Deletar favicon antigo
            $currentSettings = Settings::all();
            if (!empty($currentSettings['admin_favicon'])) {
                $oldFaviconPath = ROOT_PATH . 'storage/' . $currentSettings['admin_favicon'];
                if (file_exists($oldFaviconPath)) {
                    @unlink($oldFaviconPath);
                }
            }

            // Upload novo (ICO, PNG, SVG - máx 500KB)
            $uploadResult = Upload::uploadFile($_FILES['admin_favicon'], 'favicon', 0.5, 'admin-favicon', false);

            if ($uploadResult['success']) {
                $adminFavicon = $uploadResult['path'];
            } else {
                $_SESSION['error'] = 'Erro no upload do favicon admin: ' . $uploadResult['error'];
                Core::redirect('/admin/settings');
                return;
            }
        }

        // 📊 Upload de Google Service Account JSON (se enviado)
        if (isset($_FILES['gsc_credentials']) && $_FILES['gsc_credentials']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['gsc_credentials'];

            // Validar tamanho (máx 100KB - arquivos JSON são pequenos)
            if ($file['size'] > 100 * 1024) {
                $_SESSION['error'] = 'Arquivo JSON muito grande. Máximo: 100KB';
                Core::redirect('/admin/settings');
                return;
            }

            // Validar extensão
            $fileName = $file['name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if ($fileExt !== 'json') {
                $_SESSION['error'] = 'Apenas arquivos .json são permitidos';
                Core::redirect('/admin/settings');
                return;
            }

            // Ler e validar JSON
            $jsonContent = file_get_contents($file['tmp_name']);
            $jsonData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $_SESSION['error'] = 'Arquivo JSON inválido: ' . json_last_error_msg();
                Core::redirect('/admin/settings');
                return;
            }

            // Validar estrutura do JSON (deve ser Service Account)
            $requiredFields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email'];
            foreach ($requiredFields as $field) {
                if (!isset($jsonData[$field])) {
                    $_SESSION['error'] = 'JSON inválido: campo "' . $field . '" não encontrado. Certifique-se de fazer upload do arquivo Service Account correto.';
                    Core::redirect('/admin/settings');
                    return;
                }
            }

            // Validar que é Service Account
            if ($jsonData['type'] !== 'service_account') {
                $_SESSION['error'] = 'Arquivo não é uma Service Account válida. Type: ' . $jsonData['type'];
                Core::redirect('/admin/settings');
                return;
            }

            // Salvar em /config/google-service-account.json
            $configPath = ROOT_PATH . 'config';
            $destPath = $configPath . '/google-service-account.json';

            // Criar pasta /config se não existir
            if (!is_dir($configPath)) {
                mkdir($configPath, 0755, true);
            }

            // Remover arquivo antigo se existir
            if (file_exists($destPath)) {
                @unlink($destPath);
            }

            // Salvar novo arquivo
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                // Ajustar permissões (apenas leitura para owner)
                chmod($destPath, 0600);
                $_SESSION['success'] = 'Credenciais do Google Search Console configuradas com sucesso! Service Account: ' . $jsonData['client_email'];
            } else {
                $_SESSION['error'] = 'Erro ao salvar arquivo JSON. Verifique permissões da pasta /config';
                Core::redirect('/admin/settings');
                return;
            }
        }

        // Validar email
        if (!empty($adminEmail) && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email do administrador inválido';
            Core::redirect('/admin/settings');
            return;
        }

        // Validar SMTP
        if (!empty($smtpUsername) && !filter_var($smtpUsername, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Usuário SMTP inválido (deve ser um email)';
            Core::redirect('/admin/settings');
            return;
        }

        if (!empty($smtpFromEmail) && !filter_var($smtpFromEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email remetente SMTP inválido';
            Core::redirect('/admin/settings');
            return;
        }

        // Validar SMTP Alertas
        if (!empty($alertSmtpUsername) && !filter_var($alertSmtpUsername, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Usuário SMTP de alertas inválido (deve ser um email)';
            Core::redirect('/admin/settings');
            return;
        }

        if (!empty($alertSmtpFromEmail) && !filter_var($alertSmtpFromEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email remetente SMTP de alertas inválido';
            Core::redirect('/admin/settings');
            return;
        }

        if (!empty($smtpHost) && ($smtpPort < 1 || $smtpPort > 65535)) {
            $_SESSION['error'] = 'Porta SMTP inválida (deve estar entre 1 e 65535)';
            Core::redirect('/admin/settings');
            return;
        }

        if (!empty($smtpHost) && !empty($smtpEncryption) && !in_array($smtpEncryption, ['tls', 'ssl'])) {
            $_SESSION['error'] = 'Tipo de criptografia inválido (deve ser tls ou ssl)';
            Core::redirect('/admin/settings');
            return;
        }

        // Validar cores (hex)
        if (!preg_match('/^#[a-f0-9]{6}$/i', $themeColorMain)) {
            $_SESSION['error'] = 'Cor principal inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $themeColorSecond)) {
            $_SESSION['error'] = 'Cor secundária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $themeColorThird)) {
            $_SESSION['error'] = 'Cor terciária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $themeColorFour)) {
            $_SESSION['error'] = 'Cor quaternária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $themeColorFive)) {
            $_SESSION['error'] = 'Cor quinária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }

        // Validar cores Members (hex)
        if (!preg_match('/^#[a-f0-9]{6}$/i', $membersColorMain)) {
            $_SESSION['error'] = 'Members - Cor principal inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $membersColorSecond)) {
            $_SESSION['error'] = 'Members - Cor secundária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $membersColorThird)) {
            $_SESSION['error'] = 'Members - Cor terciária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $membersColorFour)) {
            $_SESSION['error'] = 'Members - Cor quaternária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $membersColorFive)) {
            $_SESSION['error'] = 'Members - Cor quinária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }

        // Validar cores Admin (hex)
        if (!preg_match('/^#[a-f0-9]{6}$/i', $adminColorMain)) {
            $_SESSION['error'] = 'Admin - Cor principal inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $adminColorSecond)) {
            $_SESSION['error'] = 'Admin - Cor secundária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $adminColorThird)) {
            $_SESSION['error'] = 'Admin - Cor terciária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $adminColorFour)) {
            $_SESSION['error'] = 'Admin - Cor quaternária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $adminColorFive)) {
            $_SESSION['error'] = 'Admin - Cor quinária inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }

        // Validar cores de dashboard (cores sólidas apenas, gradientes são texto livre)
        if (!preg_match('/^#[a-f0-9]{6}$/i', $dashBgMain)) {
            $_SESSION['error'] = 'Dashboard BG Main inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $dashBgMainDark)) {
            $_SESSION['error'] = 'Dashboard BG Main Dark inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $dashBgBread)) {
            $_SESSION['error'] = 'Dashboard BG Breadcrumb inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $dashBgBreadDark)) {
            $_SESSION['error'] = 'Dashboard BG Breadcrumb Dark inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $dashBgAside)) {
            $_SESSION['error'] = 'Dashboard BG Aside inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $dashBgLogo)) {
            $_SESSION['error'] = 'Logo Color inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $dashBgLogoDark)) {
            $_SESSION['error'] = 'Logo Color Dark inválida (use formato #RRGGBB)';
            Core::redirect('/admin/settings');
            return;
        }

        // Validar PageSpeed
        if ($pagespeedEnabled && empty($pagespeedApiKey)) {
            $_SESSION['error'] = 'PageSpeed habilitado mas API Key não fornecida';
            Core::redirect('/admin/settings');
            return;
        }
        if (!empty($pagespeedAlertEmail) && !filter_var($pagespeedAlertEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email de alerta PageSpeed inválido';
            Core::redirect('/admin/settings');
            return;
        }
        if ($pagespeedAlertThreshold < 0 || $pagespeedAlertThreshold > 100) {
            $_SESSION['error'] = 'Threshold de alerta PageSpeed deve estar entre 0-100';
            Core::redirect('/admin/settings');
            return;
        }

        // Atualizar configurações
        $settingsData = [
            'admin_email' => $adminEmail,
            'maintenance_mode' => $maintenanceMode,
            'timezone' => $timezone,
            'tinymce_api_key' => $tinymceApiKey,
            'gtm_id' => $gtmId,
            'uptime_robot_api_key' => $uptimeRobotApiKey,
            'ftp_host' => $ftpHost,
            'ftp_port' => $ftpPort,
            'ftp_username' => $ftpUsername,
            'ftp_password' => $ftpPassword,
            'ftp_remote_path' => $ftpRemotePath,
            'theme_color_main' => $themeColorMain,
            'theme_color_second' => $themeColorSecond,
            'theme_color_third' => $themeColorThird,
            'theme_color_four' => $themeColorFour,
            'theme_color_five' => $themeColorFive,
            'font_title' => $fontTitle,
            'font_text' => $fontText,
            'members_color_main' => $membersColorMain,
            'members_color_second' => $membersColorSecond,
            'members_color_third' => $membersColorThird,
            'members_color_four' => $membersColorFour,
            'members_color_five' => $membersColorFive,
            'members_font_title' => $membersFontTitle,
            'members_font_text' => $membersFontText,
            'admin_color_main' => $adminColorMain,
            'admin_color_second' => $adminColorSecond,
            'admin_color_third' => $adminColorThird,
            'admin_color_four' => $adminColorFour,
            'admin_color_five' => $adminColorFive,
            'admin_font_title' => $adminFontTitle,
            'admin_font_text' => $adminFontText,
            'dash_bg_header' => $dashBgHeader,
            'dash_bg_header_dark' => $dashBgHeaderDark,
            'dash_bg_main' => $dashBgMain,
            'dash_bg_main_dark' => $dashBgMainDark,
            'dash_bg_bread' => $dashBgBread,
            'dash_bg_bread_dark' => $dashBgBreadDark,
            'dash_bg_aside' => $dashBgAside,
            'dash_bg_logo' => $dashBgLogo,
            'dash_bg_logo_dark' => $dashBgLogoDark,
            'alert_smtp_host' => $alertSmtpHost,
            'alert_smtp_port' => $alertSmtpPort,
            'alert_smtp_username' => $alertSmtpUsername,
            'alert_smtp_password' => $alertSmtpPassword,
            'alert_smtp_from_email' => $alertSmtpFromEmail,
            'alert_smtp_from_name' => $alertSmtpFromName,
            'alert_smtp_encryption' => $alertSmtpEncryption,
            'pagespeed_enabled' => $pagespeedEnabled,
            'pagespeed_api_key' => $pagespeedApiKey,
            'pagespeed_auto_enabled' => $pagespeedAutoEnabled,
            'pagespeed_frequency' => $pagespeedFrequency,
            'pagespeed_time' => $pagespeedTime,
            'pagespeed_strategy_mobile' => $pagespeedStrategyMobile,
            'pagespeed_strategy_desktop' => $pagespeedStrategyDesktop,
            'pagespeed_alert_threshold' => $pagespeedAlertThreshold,
            'pagespeed_alert_email' => $pagespeedAlertEmail,
            'pagespeed_webhook_secret' => $pagespeedWebhookSecret
        ];

        // Adicionar logo se foi feito upload
        if ($siteLogo !== null) {
            $settingsData['site_logo'] = $siteLogo;
        }

        // Adicionar favicons se foram feitos uploads
        if ($favicon !== null) {
            $settingsData['favicon'] = $favicon;
        }
        if ($membersFavicon !== null) {
            $settingsData['members_favicon'] = $membersFavicon;
        }
        if ($adminFavicon !== null) {
            $settingsData['admin_favicon'] = $adminFavicon;
        }

        Settings::updateMultiple($settingsData);

        // 📝 Atualizar _config.php com nome do painel admin
        $this->updateConfigFile('ADMIN_NAME', $adminName);

        // 🔑 Atualizar _config.php com nova API key
        $this->updateConfigFile('TINYMCE_API_KEY', $tinymceApiKey);

        // 📧 Atualizar _config.php com configurações SMTP
        $this->updateConfigFile('SMTP_HOST', $smtpHost);
        $this->updateConfigFile('SMTP_PORT', $smtpPort);
        $this->updateConfigFile('SMTP_USERNAME', $smtpUsername);
        $this->updateConfigFile('SMTP_PASSWORD', $smtpPassword);
        $this->updateConfigFile('SMTP_FROM_EMAIL', $smtpFromEmail);
        $this->updateConfigFile('SMTP_FROM_NAME', $smtpFromName);
        $this->updateConfigFile('SMTP_ENCRYPTION', $smtpEncryption);

        // 📊 Atualizar _config.php com configurações RD Station
        $this->updateConfigFile('RDSTATION_ENABLED', $rdstationEnabled);
        $this->updateConfigFile('RDSTATION_API_KEY', $rdstationApiKey);

        // 📊 Atualizar _config.php com configurações UptimeRobot
        $this->updateConfigFile('UPTIME_ROBOT_API_KEY', $uptimeRobotApiKey);

        // 🎨 Atualizar arquivo SASS Frontend com cores, fontes e dashboard colors
        $this->updateSassVariables(
            $themeColorMain,
            $themeColorSecond,
            $themeColorThird,
            $themeColorFour,
            $themeColorFive,
            $fontTitle,
            $fontText,
            $dashBgHeader,
            $dashBgHeaderDark,
            $dashBgMain,
            $dashBgMainDark,
            $dashBgBread,
            $dashBgBreadDark,
            $dashBgAside,
            $dashBgLogo,
            $dashBgLogoDark
        );

        // 🎨 Atualizar arquivo SASS Members com cores e fontes
        $this->updateMembersSassVariables(
            $membersColorMain,
            $membersColorSecond,
            $membersColorThird,
            $membersColorFour,
            $membersColorFive,
            $membersFontTitle,
            $membersFontText,
            $dashBgMain,
            $dashBgMainDark,
            $dashBgBread,
            $dashBgBreadDark,
            $dashBgAside,
            $dashBgLogo,
            $dashBgLogoDark,
            $dashBgHeader,
            $dashBgHeaderDark
        );

        // 🎨 Atualizar arquivo SASS Admin com cores e fontes
        $this->updateAdminSassVariables(
            $adminColorMain,
            $adminColorSecond,
            $adminColorThird,
            $adminColorFour,
            $adminColorFive,
            $adminFontTitle,
            $adminFontText
        );

        $_SESSION['success'] = 'Configurações atualizadas com sucesso!';
        Core::redirect('/admin/settings');
    }

    /**
     * Atualizar define() no _config.php
     */
    private function updateConfigFile($key, $value) {
        $configFile = ROOT_PATH . '_config.php';

        if (!file_exists($configFile)) {
            error_log("Config file not found: {$configFile}");
            return false;
        }

        // Ler arquivo atual
        $content = file_get_contents($configFile);

        // BACKUP AUTOMÁTICO antes de modificar
        $backupFile = $configFile . '.backup.' . date('Ymd_His');
        copy($configFile, $backupFile);

        // Escapar valor para PHP (adicionar aspas apenas para strings)
        if (is_bool($value)) {
            $escapedValue = $value ? 'true' : 'false';
        } elseif (is_int($value)) {
            $escapedValue = $value;
        } else {
            // String: adicionar aspas simples e escapar aspas internas
            $escapedValue = "'" . str_replace("'", "\\'", $value) . "'";
        }

        // Atualizar define existente
        $pattern = "/define\\('{$key}',\\s*[^)]+\\);/";
        $replacement = "define('{$key}', {$escapedValue});";

        if (preg_match($pattern, $content)) {
            // Define já existe, substituir
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // Define não existe, adicionar no final do arquivo
            $content = rtrim($content) . "\n\n" . $replacement . "\n";
        }

        // Salvar arquivo
        $result = file_put_contents($configFile, $content);

        if ($result === false) {
            error_log("Failed to update config file: {$configFile}");
            return false;
        }

        return true;
    }

    /**
     * Atualizar arquivo _b-variables.sass com cores, fontes e dashboard colors
     */
    private function updateSassVariables(
        $colorMain,
        $colorSecond,
        $colorThird,
        $colorFour,
        $colorFive,
        $fontPrimary,
        $fontSecondary,
        $dashBgHeader = null,
        $dashBgHeaderDark = null,
        $dashBgMain = null,
        $dashBgMainDark = null,
        $dashBgBread = null,
        $dashBgBreadDark = null,
        $dashBgAside = null,
        $dashBgLogo = null,
        $dashBgLogoDark = null
    ) {
        $sassFile = ROOT_PATH . 'assets/sass/frontend/base/_b-variables.sass';

        if (!file_exists($sassFile)) {
            error_log("SASS variables file not found: {$sassFile}");
            return false;
        }

        // Ler arquivo atual
        $content = file_get_contents($sassFile);

        // BACKUP AUTOMÁTICO antes de modificar
        $backupFile = $sassFile . '.backup.' . date('Ymd_His');
        copy($sassFile, $backupFile);

        // Atualizar cores
        $content = preg_replace(
            '/\\$color-main:\\s*#[a-f0-9]{6}/i',
            '$color-main: ' . $colorMain,
            $content
        );
        $content = preg_replace(
            '/\\$color-second:\\s*#[a-f0-9]{6}/i',
            '$color-second: ' . $colorSecond,
            $content
        );
        $content = preg_replace(
            '/\\$color-third:\\s*#[a-f0-9]{6}/i',
            '$color-third: ' . $colorThird,
            $content
        );
        $content = preg_replace(
            '/\\$color-four:\\s*#[a-f0-9]{6}/i',
            '$color-four: ' . $colorFour,
            $content
        );
        $content = preg_replace(
            '/\\$color-five:\\s*#[a-f0-9]{6}/i',
            '$color-five: ' . $colorFive,
            $content
        );

        // Atualizar fontes
        $content = preg_replace(
            '/\\$font-title:\\s*[^\\n]+/i',
            '$font-title: ' . $fontPrimary,
            $content
        );
        $content = preg_replace(
            '/\\$font-text:\\s*[^\\n]+/i',
            '$font-text: ' . $fontSecondary,
            $content
        );

        // Atualizar Dashboard Colors (se fornecidos)
        if ($dashBgHeader !== null) {
            $content = preg_replace(
                '/\\$bgdashheader:\\s*.+/i',
                '$bgdashheader:' . $dashBgHeader,
                $content
            );
        }
        if ($dashBgHeaderDark !== null) {
            $content = preg_replace(
                '/\\$bgdashheaderdark:\\s*.+/i',
                '$bgdashheaderdark:' . $dashBgHeaderDark,
                $content
            );
        }
        if ($dashBgMain !== null) {
            $content = preg_replace(
                '/\\$bgdashmain:\\s*#[a-f0-9]{6}/i',
                '$bgdashmain: ' . $dashBgMain,
                $content
            );
        }
        if ($dashBgMainDark !== null) {
            $content = preg_replace(
                '/\\$bgdashmaindark:\\s*#[a-f0-9]{6}/i',
                '$bgdashmaindark: ' . $dashBgMainDark,
                $content
            );
        }
        if ($dashBgBread !== null) {
            $content = preg_replace(
                '/\\$bgdashbread:\\s*#[a-f0-9]{6}/i',
                '$bgdashbread: ' . $dashBgBread,
                $content
            );
        }
        if ($dashBgBreadDark !== null) {
            $content = preg_replace(
                '/\\$bgdashbreaddark:\\s*#[a-f0-9]{6}/i',
                '$bgdashbreaddark: ' . $dashBgBreadDark,
                $content
            );
        }
        if ($dashBgAside !== null) {
            $content = preg_replace(
                '/\\$bgdashaside:\\s*#[a-f0-9]{6}/i',
                '$bgdashaside: ' . $dashBgAside,
                $content
            );
        }
        if ($dashBgLogo !== null) {
            $content = preg_replace(
                '/\\$bgdashlogo:\\s*#[a-f0-9]{6}/i',
                '$bgdashlogo: ' . $dashBgLogo,
                $content
            );
        }
        if ($dashBgLogoDark !== null) {
            $content = preg_replace(
                '/\\$bgdashlogodark:\\s*.+/i',
                '$bgdashlogodark: ' . $dashBgLogoDark,
                $content
            );
        }

        // Salvar arquivo
        $result = file_put_contents($sassFile, $content);

        if ($result === false) {
            error_log("Failed to update SASS variables file: {$sassFile}");
            return false;
        }

        return true;
    }

    /**
     * Atualizar arquivo members/_variables.sass com cores e fontes
     */
    private function updateMembersSassVariables(
        $colorMain,
        $colorSecond,
        $colorThird,
        $colorFour,
        $colorFive,
        $fontTitle,
        $fontText,
        $bgDashMain,
        $bgDashMainDark,
        $bgDashBread,
        $bgDashBreadDark,
        $bgDashAside,
        $bgDashLogo,
        $bgDashLogoDark,
        $bgDashHeader,
        $bgDashHeaderDark
    ) {
        $sassFile = ROOT_PATH . 'assets/sass/members/base/_variables.sass';

        if (!file_exists($sassFile)) {
            error_log("Members SASS variables file not found: {$sassFile}");
            return false;
        }

        // Ler arquivo atual
        $content = file_get_contents($sassFile);

        // BACKUP AUTOMÁTICO antes de modificar
        $backupFile = $sassFile . '.backup.' . date('Ymd_His');
        copy($sassFile, $backupFile);

        // Atualizar cores
        $content = preg_replace(
            '/\\$color-main:\\s*#[a-f0-9]{6}/i',
            '$color-main: ' . $colorMain,
            $content
        );
        $content = preg_replace(
            '/\\$color-second:\\s*#[a-f0-9]{6}/i',
            '$color-second: ' . $colorSecond,
            $content
        );
        $content = preg_replace(
            '/\\$color-third:\\s*#[a-f0-9]{6}/i',
            '$color-third: ' . $colorThird,
            $content
        );
        $content = preg_replace(
            '/\\$color-four:\\s*#[a-f0-9]{6}/i',
            '$color-four: ' . $colorFour,
            $content
        );
        $content = preg_replace(
            '/\\$color-five:\\s*#[a-f0-9]{6}/i',
            '$color-five: ' . $colorFive,
            $content
        );

        // Atualizar fontes
        $content = preg_replace(
            '/\\$font-title:\\s*[^\\n]+/i',
            '$font-title: ' . $fontTitle,
            $content
        );
        $content = preg_replace(
            '/\\$font-text:\\s*[^\\n]+/i',
            '$font-text: ' . $fontText,
            $content
        );

        // Atualizar backgrounds do dashboard
        $content = preg_replace(
            '/\\$bgdashmain:\\s*#[a-f0-9]{6}/i',
            '$bgdashmain: ' . $bgDashMain,
            $content
        );
        $content = preg_replace(
            '/\\$bgdashmaindark:\\s*#[a-f0-9]{6}/i',
            '$bgdashmaindark: ' . $bgDashMainDark,
            $content
        );
        $content = preg_replace(
            '/\\$bgdashbread:\\s*#[a-f0-9]{6}/i',
            '$bgdashbread: ' . $bgDashBread,
            $content
        );
        $content = preg_replace(
            '/\\$bgdashbreaddark:\\s*#[a-f0-9]{6}/i',
            '$bgdashbreaddark: ' . $bgDashBreadDark,
            $content
        );
        $content = preg_replace(
            '/\\$bgdashaside:\\s*#[a-f0-9]{6}/i',
            '$bgdashaside: ' . $bgDashAside,
            $content
        );
        $content = preg_replace(
            '/\\$bgdashlogo:\\s*#[a-f0-9]{6}/i',
            '$bgdashlogo: ' . $bgDashLogo,
            $content
        );
        $content = preg_replace(
            '/\\$bgdashlogodark:\\s*#[a-f0-9]{6}/i',
            '$bgdashlogodark: ' . $bgDashLogoDark,
            $content
        );
        $content = preg_replace(
            '/\\$bgdashheader:\\s*linear-gradient\\([^)]+\\)/i',
            '$bgdashheader: ' . $bgDashHeader,
            $content
        );
        $content = preg_replace(
            '/\\$bgdashheaderdark:\\s*linear-gradient\\([^)]+\\)/i',
            '$bgdashheaderdark: ' . $bgDashHeaderDark,
            $content
        );

        // Salvar arquivo
        $result = file_put_contents($sassFile, $content);

        if ($result === false) {
            error_log("Failed to update Members SASS variables file: {$sassFile}");
            return false;
        }

        return true;
    }

    /**
     * Atualizar arquivo admin/_variables.sass com cores e fontes
     */
    private function updateAdminSassVariables(
        $colorMain,
        $colorSecond,
        $colorThird,
        $colorFour,
        $colorFive,
        $fontTitle,
        $fontText
    ) {
        $sassFile = ROOT_PATH . 'assets/sass/admin/base/_variables.sass';

        if (!file_exists($sassFile)) {
            error_log("Admin SASS variables file not found: {$sassFile}");
            return false;
        }

        // Ler arquivo atual
        $content = file_get_contents($sassFile);

        // BACKUP AUTOMÁTICO antes de modificar
        $backupFile = $sassFile . '.backup.' . date('Ymd_His');
        copy($sassFile, $backupFile);

        // Atualizar cores
        $content = preg_replace(
            '/\\$color-main:\\s*#[a-f0-9]{6}/i',
            '$color-main: ' . $colorMain,
            $content
        );
        $content = preg_replace(
            '/\\$color-second:\\s*#[a-f0-9]{6}/i',
            '$color-second: ' . $colorSecond,
            $content
        );
        $content = preg_replace(
            '/\\$color-third:\\s*#[a-f0-9]{6}/i',
            '$color-third: ' . $colorThird,
            $content
        );
        $content = preg_replace(
            '/\\$color-four:\\s*#[a-f0-9]{6}/i',
            '$color-four: ' . $colorFour,
            $content
        );
        $content = preg_replace(
            '/\\$color-five:\\s*#[a-f0-9]{6}/i',
            '$color-five: ' . $colorFive,
            $content
        );

        // Atualizar fontes
        $content = preg_replace(
            '/\\$font-title:\\s*[^\\n]+/i',
            '$font-title: ' . $fontTitle,
            $content
        );
        $content = preg_replace(
            '/\\$font-text:\\s*[^\\n]+/i',
            '$font-text: ' . $fontText,
            $content
        );

        // Salvar arquivo
        $result = file_put_contents($sassFile, $content);

        if ($result === false) {
            error_log("Failed to update Admin SASS variables file: {$sassFile}");
            return false;
        }

        return true;
    }

    /**
     * Testar SMTP de Alertas
     */
    public function testAlertSmtp() {
        header('Content-Type: application/json');

        // Verificar autenticação
        if (!Auth::check()) {
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        // Verificar se Email class existe
        if (!class_exists('Email')) {
            echo json_encode(['success' => false, 'message' => 'Classe Email não encontrada']);
            return;
        }

        // Buscar configurações
        $alertEmail = Settings::get('admin_email');
        $alertSmtpHost = Settings::get('alert_smtp_host');

        if (empty($alertEmail)) {
            echo json_encode(['success' => false, 'message' => 'Configure o Email do Administrador primeiro']);
            return;
        }

        if (empty($alertSmtpHost)) {
            echo json_encode(['success' => false, 'message' => 'Configure o SMTP de Alertas primeiro']);
            return;
        }

        // Enviar email de teste
        $subject = '[TESTE] SMTP de Alertas - AEGIS Framework';
        $body = '<div style="font-family: Arial, sans-serif; padding: 20px;">';
        $body .= '<h2 style="color: #3498db;">✅ Teste de SMTP de Alertas</h2>';
        $body .= '<p>Este é um email de teste para validar as configurações de SMTP de Alertas Técnicos.</p>';
        $body .= '<p><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p>';
        $body .= '<p><strong>Servidor:</strong> ' . htmlspecialchars($alertSmtpHost) . '</p>';
        $body .= '<hr>';
        $body .= '<p style="color: #666; font-size: 12px;">Se você recebeu este email, suas configurações estão corretas!</p>';
        $body .= '</div>';

        try {
            $result = Email::sendAlert($alertEmail, $subject, $body);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Email de teste enviado com sucesso para ' . $alertEmail]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Falha ao enviar email. Verifique os logs do servidor.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
    }

    /**
     * Testar SMTP Cliente
     */
    public function testClientSmtp() {
        header('Content-Type: application/json');

        // Verificar autenticação
        if (!Auth::check()) {
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        // Verificar se Email class existe
        if (!class_exists('Email')) {
            echo json_encode(['success' => false, 'message' => 'Classe Email não encontrada']);
            return;
        }

        // Buscar configurações
        $adminEmail = Settings::get('admin_email');

        if (empty($adminEmail)) {
            echo json_encode(['success' => false, 'message' => 'Configure o Email do Administrador primeiro']);
            return;
        }

        // Verificar se SMTP está configurado
        if (!defined('SMTP_HOST') || empty(SMTP_HOST)) {
            echo json_encode(['success' => false, 'message' => 'SMTP Cliente não está configurado. Configure primeiro e salve.']);
            return;
        }

        // Enviar email de teste
        $subject = '[TESTE] SMTP Cliente - AEGIS Framework';
        $body = '<div style="font-family: Arial, sans-serif; padding: 20px;">';
        $body .= '<h2 style="color: #3498db;">✅ Teste de SMTP Cliente</h2>';
        $body .= '<p>Este é um email de teste para validar as configurações de SMTP do Cliente.</p>';
        $body .= '<p><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p>';
        $body .= '<p><strong>Servidor:</strong> ' . htmlspecialchars(SMTP_HOST) . '</p>';
        $body .= '<p><strong>Porta:</strong> ' . SMTP_PORT . '</p>';
        $body .= '<hr>';
        $body .= '<p style="color: #666; font-size: 12px;">Se você recebeu este email, suas configurações estão corretas!</p>';
        $body .= '</div>';

        try {
            $result = Email::send($adminEmail, $subject, $body);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Email de teste enviado com sucesso para ' . $adminEmail]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Falha ao enviar email. Verifique os logs do servidor.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload de fonte WOFF2
     */
    public function uploadFont() {
        Auth::require();

        try {
            Security::validateCSRF($_POST['csrf_token']);

            // Validar se arquivo foi enviado
            if (!isset($_FILES['font_file']) || $_FILES['font_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Nenhum arquivo de fonte foi enviado');
            }

            // Nome customizado (opcional)
            $customName = !empty($_POST['custom_name']) ? Security::sanitize($_POST['custom_name']) : null;

            // Upload via classe Fonts
            $result = Fonts::upload($_FILES['font_file'], $customName);

            if (!$result['success']) {
                throw new Exception($result['error']);
            }

            $_SESSION['success'] = 'Fonte "' . $result['name'] . '" enviada com sucesso!';
            Core::redirect('/admin/settings');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/settings');
        }
    }

    /**
     * Deletar fonte
     */
    public function deleteFont($id) {
        Auth::require();

        try {
            Security::validateCSRF($_POST['csrf_token']);

            // Buscar fonte antes de deletar (para pegar nome)
            $font = Fonts::find($id);

            if (!$font) {
                throw new Exception('Fonte não encontrada');
            }

            // Deletar via classe Fonts
            $result = Fonts::delete($id);

            if (!$result['success']) {
                throw new Exception($result['error']);
            }

            $_SESSION['success'] = 'Fonte "' . $font['family'] . ' ' . $font['weight'] . '" deletada com sucesso!';
            Core::redirect('/admin/settings');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/settings');
        }
    }

    /**
     * Testar conexão FTP
     */
    public function testFtp() {
        header('Content-Type: application/json');

        // Verificar autenticação
        if (!Auth::check()) {
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        // Buscar configurações FTP
        $ftpHost = Settings::get('ftp_host');
        $ftpPort = Settings::get('ftp_port') ?: 21;
        $ftpUsername = Settings::get('ftp_username');
        $ftpPassword = Settings::get('ftp_password');
        $ftpRemotePath = Settings::get('ftp_remote_path') ?: '/';

        // Validar campos obrigatórios
        if (empty($ftpHost)) {
            echo json_encode(['success' => false, 'message' => 'Configure o FTP Host primeiro']);
            return;
        }
        if (empty($ftpUsername)) {
            echo json_encode(['success' => false, 'message' => 'Configure o FTP Username primeiro']);
            return;
        }
        if (empty($ftpPassword)) {
            echo json_encode(['success' => false, 'message' => 'Configure o FTP Password primeiro']);
            return;
        }

        // Tentar conexão FTP
        try {
            $conn = ftp_connect($ftpHost, $ftpPort, 10);

            if (!$conn) {
                echo json_encode(['success' => false, 'message' => 'Falha ao conectar em ' . $ftpHost . ':' . $ftpPort]);
                return;
            }

            $login = @ftp_login($conn, $ftpUsername, $ftpPassword);

            if (!$login) {
                ftp_close($conn);
                echo json_encode(['success' => false, 'message' => 'Falha na autenticação. Verifique usuário e senha.']);
                return;
            }

            // Testar acesso ao diretório remoto
            $files = @ftp_nlist($conn, $ftpRemotePath);

            if ($files === false) {
                ftp_close($conn);
                echo json_encode(['success' => false, 'message' => 'Conectado, mas não foi possível acessar ' . $ftpRemotePath]);
                return;
            }

            ftp_close($conn);

            $fileCount = count($files);
            echo json_encode([
                'success' => true,
                'message' => 'Conexão FTP bem-sucedida! Encontrados ' . $fileCount . ' itens em ' . $ftpRemotePath
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
    }

    /**
     * Remover credenciais Google Search Console
     */
    public function removeGscCredentials() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        $credentialsPath = ROOT_PATH . 'config/google-service-account.json';

        if (file_exists($credentialsPath)) {
            if (unlink($credentialsPath)) {
                $_SESSION['success'] = 'Credenciais do Google Search Console removidas com sucesso.';
            } else {
                $_SESSION['error'] = 'Erro ao remover credenciais. Verifique permissões do arquivo.';
            }
        } else {
            $_SESSION['error'] = 'Arquivo de credenciais não encontrado.';
        }

        header('Location: ' . url('/admin/settings'));
        exit;
    }
}
