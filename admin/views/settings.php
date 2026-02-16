<?php
	Auth::require();
	$user = Auth::user();
	$success = $_SESSION['success'] ?? '';
	$error = $_SESSION['error'] ?? '';
	unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="pt-BR">

	<head>
		<?php
		$loadAdminJs = true; // Precisa do admin.js
		require_once __DIR__ . '/../includes/_admin-head.php';
		?>
		<title>Configurações - <?= ADMIN_NAME ?></title>
	</head>

	<body>

		<?php require_once __DIR__ . '/../../admin/includes/header.php'; ?>

		<main class="m-pagebase m-settingsbg">

			<!-- Título e Salvar -->
			<div class="m-pagebase__header">
				<h1>Configurações do Sistema</h1>
				<div class="m-pagebase__header-actions">
					<button type="submit" form="settings-form" class="m-pagebase__btn m-pagebase__btn--widthauto">
						<i data-lucide="save"></i> Salvar
					</button>
					<a href="<?= url('/admin') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--builder">
						<i data-lucide="x"></i> Cancelar
					</a>
				</div>
			</div>

			<!-- Mensagens do Sistemas -->
			<?php if ($success): ?>
				<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
			<?php endif; ?>
			<?php if ($error): ?>
				<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
			<?php endif; ?>

			<!-- Conteúdo principal -->
			<div class="m-pagebase__widthsettings">

				<form id="settings-form" method="POST" action="<?= url('/admin/settings') ?>" enctype="multipart/form-data" class="m-pagebase__form">	

					<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

					<!-- Conteúdo principal - grid-->
					<div class="m-settings__grid">

						<!-- Coluna 1 -->
						<div class="m-settings__column">

							<h2 class="m-settings__subtitle">sistema</h2>			
							
							<!-- configurações gerais -->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="settings"></i><span>Configurações Gerais</span>
								</div>

								<div class="m-settings__form-group">
									<label for="admin_name" class="m-pagebase__form-label">Nome do Painel Admin</label>
									<input type="text" id="admin_name" name="admin_name" value="<?= htmlspecialchars(ADMIN_NAME) ?>" placeholder="AEGIS" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Nome que aparece em todos os títulos e cabeçalhos do painel administrativo</div>
								</div>

								<div class="m-settings__form-group">
									<label for="timezone" class="m-pagebase__form-label">Fuso Horário</label>
									<select id="timezone" name="timezone" class="m-pagebase__form-select">
										<option value="America/Sao_Paulo" <?= ($settings['timezone'] ?? 'America/Sao_Paulo') === 'America/Sao_Paulo' ? 'selected' : '' ?>>America/Sao_Paulo (GMT-3)</option>
										<option value="America/Manaus" <?= ($settings['timezone'] ?? '') === 'America/Manaus' ? 'selected' : '' ?>>America/Manaus (GMT-4)</option>
										<option value="America/Rio_Branco" <?= ($settings['timezone'] ?? '') === 'America/Rio_Branco' ? 'selected' : '' ?>>America/Rio_Branco (GMT-5)</option>
										<option value="America/Noronha" <?= ($settings['timezone'] ?? '') === 'America/Noronha' ? 'selected' : '' ?>>America/Noronha (GMT-2)</option>
										<option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC (GMT+0)</option>
									</select>
									<div class="m-settings__form-help">
										Fuso horário usado em logs, datas e horários do sistema
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="site_logo" class="m-pagebase__form-label">Logo do Site</label>
									<input type="file" id="site_logo" name="site_logo" accept="image/*" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Logo exibida no cabeçalho do site (SVG, PNG, JPG, WEBP - máx 1MB) | Recomendado: SVG em texto (não comprimido)</div>
									<?php if (!empty($settings['site_logo'])): ?>
										<div class="m-settings__logo-preview">
											<strong>Logo atual:</strong><br>
											<img src="<?= url('/storage/' . $settings['site_logo']) ?>" alt="Logo">
										</div>
									<?php endif; ?>
								</div>

							</div>									

							<!-- email para notificações -->
							<div class="m-settings__section">
								<div class="m-settings__section-title">
									<i data-lucide="mail"></i><span>Email e Notificações</span>
								</div>
								<div class="m-settings__form-group">
									<label for="admin_email" class="m-pagebase__form-label">Email do Administrador *</label>
									<input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($settings['admin_email'] ?? 'dev@sociaholic.com.br') ?>" required placeholder="dev@sociaholic.com.br" class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Usado para receber alertas críticos do sistema (includes não encontrados, erros, etc)
									</div>
								</div>
							</div>

							<!-- alertas técnicos -->
							<div class="m-settings__section">
							
								<div class="m-settings__section-title">
									<i data-lucide="bell"></i><span>SMTP para Alertas Técnicos</span>
								</div>

								<div class="m-settings__form-group">
									<label for="alert_smtp_host" class="m-pagebase__form-label">Servidor SMTP</label>
									<input type="text" id="alert_smtp_host" name="alert_smtp_host" value="<?= htmlspecialchars($settings['alert_smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com" class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Servidor SMTP para envio de alertas técnicos
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="alert_smtp_port" class="m-pagebase__form-label">Porta SMTP</label>
									<input type="number" id="alert_smtp_port" name="alert_smtp_port" value="<?= htmlspecialchars($settings['alert_smtp_port'] ?? '587') ?>" placeholder="587" min="1" max="65535" class="m-pagebase__form-input">
									<div class="m-settings__form-help">587 para TLS, 465 para SSL</div>
								</div>

								<div class="m-settings__form-group">
									<label for="alert_smtp_username" class="m-pagebase__form-label">Usuário SMTP</label>
									<input type="email" id="alert_smtp_username" name="alert_smtp_username" value="<?= htmlspecialchars($settings['alert_smtp_username'] ?? '') ?>" placeholder="alerts@suaempresa.com" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Email usado para autenticação SMTP</div>
								</div>

								<div class="m-settings__form-group">
									<label for="alert_smtp_password" class="m-pagebase__form-label">Senha SMTP</label>
									<input type="password" id="alert_smtp_password" name="alert_smtp_password" value="<?= htmlspecialchars($settings['alert_smtp_password'] ?? '') ?>" placeholder="App Password" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Senha ou App Password do email de alertas</div>
								</div>

								<div class="m-settings__form-group">
									<label for="alert_smtp_from_email" class="m-pagebase__form-label">Email Remetente</label>
									<input type="email" id="alert_smtp_from_email" name="alert_smtp_from_email" value="<?= htmlspecialchars($settings['alert_smtp_from_email'] ?? '') ?>" placeholder="alerts@suaempresa.com" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Email que aparece como remetente dos alertas</div>
								</div>

								<div class="m-settings__form-group">
									<label for="alert_smtp_from_name" class="m-pagebase__form-label">Nome Remetente</label>
									<input type="text" id="alert_smtp_from_name" name="alert_smtp_from_name" value="<?= htmlspecialchars($settings['alert_smtp_from_name'] ?? '') ?>" placeholder="AEGIS Alertas" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Nome que aparece como remetente</div>
								</div>

								<div class="m-settings__form-group">
									<label for="alert_smtp_encryption" class="m-pagebase__form-label">Criptografia</label>
									<select id="alert_smtp_encryption" name="alert_smtp_encryption" class="m-pagebase__form-select">
										<option value="" <?= empty($settings['alert_smtp_encryption']) ? 'selected' : '' ?>>-- Selecione --</option>
										<option value="tls" <?= ($settings['alert_smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (porta 587)</option>
										<option value="ssl" <?= ($settings['alert_smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (porta 465)</option>
									</select>
									<div class="m-settings__form-help">Tipo de criptografia SMTP</div>
								</div>

								<div class="m-settings__form-group">
									<button type="button" id="test-alert-smtp" class="m-pagebase__btn m-pagebase__btn--widthauto" style="background: #3498db; margin-top: 20px;" data-url="<?= url('/admin/settings/test-alert-smtp') ?>">
										<i data-lucide="mail"></i> Testar SMTP de Alertas
									</button>
									<div id="test-alert-result" style="margin-top: 10px;"></div>
								</div>
							</div>

							<!-- Acesso FTP -->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="server"></i><span>Acesso FTP</span>
								</div>

								<div class="m-settings__form-group">
									<label for="ftp_host" class="m-pagebase__form-label">FTP Host</label>
									<input type="text" id="ftp_host" name="ftp_host" value="<?= htmlspecialchars($settings['ftp_host'] ?? '') ?>" placeholder="ftp.seuservidor.com.br" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Endereço do servidor FTP</div>
								</div>

								<div class="m-settings__form-group">
									<label for="ftp_port" class="m-pagebase__form-label">FTP Port</label>
									<input type="number" id="ftp_port" name="ftp_port" value="<?= htmlspecialchars($settings['ftp_port'] ?? '21') ?>" placeholder="21" min="1" max="65535" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Porta do servidor FTP (padrão: 21)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="ftp_username" class="m-pagebase__form-label">FTP Username</label>
									<input type="text" id="ftp_username" name="ftp_username" value="<?= htmlspecialchars($settings['ftp_username'] ?? '') ?>" placeholder="usuario@seuservidor.com.br" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Usuário de acesso FTP</div>
								</div>

								<div class="m-settings__form-group">
									<label for="ftp_password" class="m-pagebase__form-label">FTP Password</label>
									<input type="password" id="ftp_password" name="ftp_password" value="<?= htmlspecialchars($settings['ftp_password'] ?? '') ?>" placeholder="••••••••" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Senha de acesso FTP (armazenada de forma segura)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="ftp_remote_path" class="m-pagebase__form-label">FTP Remote Path</label>
									<input type="text" id="ftp_remote_path" name="ftp_remote_path" value="<?= htmlspecialchars($settings['ftp_remote_path'] ?? '/public_html') ?>" placeholder="/public_html" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Caminho remoto no servidor (ex: /public_html, /www, /httpdocs)</div>
								</div>

								<div class="m-settings__form-group">
									<button type="button" id="test-ftp" class="m-pagebase__btn m-pagebase__btn--widthauto" style="background: #e67e22; margin-top: 20px;" data-url="<?= url('/admin/settings/test-ftp') ?>">
										<i data-lucide="server"></i> Testar Conexão FTP
									</button>
									<div id="test-ftp-result" style="margin-top: 10px;"></div>
								</div>

							</div>
				
						</div>

						<!-- Coluna 2 -->
						<div class="m-settings__column">

							<h2 class="m-settings__subtitle">frontend</h2>

							<!-- Tema - Cores -->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="palette"></i><span>Tema - Cores</span>
								</div>

								<div class="m-settings__form-group">
									<label for="theme_color_main" class="m-pagebase__form-label">Cor Principal</label>
									<div class="m-settings__color-group">
											<input type="color" id="theme_color_main_picker" value="<?= htmlspecialchars($settings['theme_color_main'] ?? '#6c10b8') ?>" class="m-settings__color-picker">
											<input type="text" id="theme_color_main" name="theme_color_main" value="<?= htmlspecialchars($settings['theme_color_main'] ?? '#6c10b8') ?>" placeholder="#6c10b8" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor principal da marca (usada em botões, títulos, etc)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="theme_color_second" class="m-pagebase__form-label">Cor Secundária</label>
									<div class="m-settings__color-group">
											<input type="color" id="theme_color_second_picker" value="<?= htmlspecialchars($settings['theme_color_second'] ?? '#FAEA26') ?>" class="m-settings__color-picker">
											<input type="text" id="theme_color_second" name="theme_color_second" value="<?= htmlspecialchars($settings['theme_color_second'] ?? '#FAEA26') ?>" placeholder="#FAEA26" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor secundária da marca (destaques, elementos)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="theme_color_third" class="m-pagebase__form-label">Cor Terciária</label>
									<div class="m-settings__color-group">
											<input type="color" id="theme_color_third_picker" value="<?= htmlspecialchars($settings['theme_color_third'] ?? '#1D4872') ?>" class="m-settings__color-picker">
											<input type="text" id="theme_color_third" name="theme_color_third" value="<?= htmlspecialchars($settings['theme_color_third'] ?? '#1D4872') ?>" placeholder="#1D4872" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor terciária da marca (fundos, seções)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="theme_color_four" class="m-pagebase__form-label">Cor Quaternária</label>
									<div class="m-settings__color-group">
											<input type="color" id="theme_color_four_picker" value="<?= htmlspecialchars($settings['theme_color_four'] ?? '#A39D8F') ?>" class="m-settings__color-picker">
											<input type="text" id="theme_color_four" name="theme_color_four" value="<?= htmlspecialchars($settings['theme_color_four'] ?? '#A39D8F') ?>" placeholder="#A39D8F" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor quaternária da marca (opcional)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="theme_color_five" class="m-pagebase__form-label">Cor Quinária</label>
									<div class="m-settings__color-group">
											<input type="color" id="theme_color_five_picker" value="<?= htmlspecialchars($settings['theme_color_five'] ?? '#A39D8F') ?>" class="m-settings__color-picker">
											<input type="text" id="theme_color_five" name="theme_color_five" value="<?= htmlspecialchars($settings['theme_color_five'] ?? '#A39D8F') ?>" placeholder="#A39D8F" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor quinária da marca (opcional)</div>											
								</div>

							</div>

							<!-- Tema - Fontes -->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="palette"></i><span>Tema - Fontes</span>
								</div>									

								<?php
								// Carregar famílias de fontes disponíveis
								$fontFamilies = Fonts::getFamilies();
								?>

								<!-- Botão Gerenciar Fontes -->
								<div class="m-settings__form-group">
									<a href="<?= url('/admin/fonts') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto" style="display: inline-flex; align-items: center; gap: 0.5rem;">
										<i data-lucide="type"></i> Gerenciar Fontes
									</a>
									<div class="m-settings__form-help" style="margin-top: 0.5rem;">
										Faça upload, visualize e remova fontes WOFF2 customizadas
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="font_title" class="m-pagebase__form-label">Font Title</label>
									<select id="font_title" name="font_title" class="m-pagebase__form-input">
										<option value="system-ui" <?= ($settings['font_title'] ?? 'system-ui') === 'system-ui' ? 'selected' : '' ?>>System UI (Padrão do navegador)</option>
										<?php foreach ($fontFamilies as $family): ?>
											<option value="<?= htmlspecialchars($family) ?>" <?= ($settings['font_title'] ?? '') === $family ? 'selected' : '' ?>>
												<?= htmlspecialchars($family) ?>
											</option>
										<?php endforeach; ?>
									</select>
									<div class="m-settings__form-help">Fonte usada em títulos (h1, h2, h3, h4, h5, h6)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="font_text" class="m-pagebase__form-label">Font Text</label>
									<select id="font_text" name="font_text" class="m-pagebase__form-input">
										<option value="system-ui" <?= ($settings['font_text'] ?? 'system-ui') === 'system-ui' ? 'selected' : '' ?>>System UI (Padrão do navegador)</option>
										<?php foreach ($fontFamilies as $family): ?>
											<option value="<?= htmlspecialchars($family) ?>" <?= ($settings['font_text'] ?? '') === $family ? 'selected' : '' ?>>
												<?= htmlspecialchars($family) ?>
											</option>
										<?php endforeach; ?>
									</select>
									<div class="m-settings__form-help">
										Fonte usada em textos (p, a, ul, li, button, label, table)
									</div>
								</div>

							</div>

							<!-- Favicon Frontend -->
							<div class="m-settings__section">
								<div class="m-settings__section-title">
									<i data-lucide="image"></i><span>Favicon</span>
								</div>
								<div class="m-settings__form-group">
									<label for="favicon" class="m-pagebase__form-label">Favicon do Site (Público)</label>
									<input type="file" id="favicon" name="favicon"
									       accept=".ico,.png,.svg,image/x-icon,image/png,image/svg+xml"
									       class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Ícone exibido na aba do navegador para páginas públicas (ICO, PNG ou SVG - máx 500KB)<br>
										Recomendado: 32x32px ou 64x64px
									</div>
									<?php if (!empty($settings['favicon'])): ?>
										<div class="m-settings__favicon-preview" style="margin-top: 10px;">
											<strong>Favicon atual:</strong><br>
											<img src="<?= url('/storage/' . $settings['favicon']) ?>"
											     alt="Favicon Frontend"
											     style="width: 32px; height: 32px; border: 1px solid #ddd; padding: 4px; background: white;">
										</div>
									<?php endif; ?>
								</div>
							</div>

							<h2 class="m-settings__subtitle">members</h2>

							<!-- Tema - Cores -->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="palette"></i><span>Tema - Cores</span>
								</div>

								<div class="m-settings__form-group">
									<label for="members_color_main" class="m-pagebase__form-label">Cor Principal</label>
									<div class="m-settings__color-group">
											<input type="color" id="members_color_main_picker" value="<?= htmlspecialchars($settings['members_color_main'] ?? '#6c10b8') ?>" class="m-settings__color-picker">
											<input type="text" id="members_color_main" name="members_color_main" value="<?= htmlspecialchars($settings['members_color_main'] ?? '#6c10b8') ?>" placeholder="#6c10b8" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">
										Cor principal da marca (usada em botões, títulos, etc)
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="members_color_second" class="m-pagebase__form-label">Cor Secundária</label>
									<div class="m-settings__color-group">
											<input type="color" id="members_color_second_picker" value="<?= htmlspecialchars($settings['members_color_second'] ?? '#FAEA26') ?>" class="m-settings__color-picker">
											<input type="text" id="members_color_second" name="members_color_second" value="<?= htmlspecialchars($settings['members_color_second'] ?? '#FAEA26') ?>" placeholder="#FAEA26" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">
										Cor secundária da marca (destaques, elementos)
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="members_color_third" class="m-pagebase__form-label">Cor Terciária</label>
									<div class="m-settings__color-group">
											<input type="color" id="members_color_third_picker" value="<?= htmlspecialchars($settings['members_color_third'] ?? '#1D4872') ?>" class="m-settings__color-picker">
											<input type="text" id="members_color_third" name="members_color_third" value="<?= htmlspecialchars($settings['members_color_third'] ?? '#1D4872') ?>" placeholder="#1D4872" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">
										Cor terciária da marca (fundos, seções)
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="members_color_four" class="m-pagebase__form-label">Cor Quaternária</label>
									<div class="m-settings__color-group">
											<input type="color" id="members_color_four_picker" value="<?= htmlspecialchars($settings['members_color_four'] ?? '#A39D8F') ?>" class="m-settings__color-picker">
											<input type="text" id="members_color_four" name="members_color_four" value="<?= htmlspecialchars($settings['members_color_four'] ?? '#A39D8F') ?>" placeholder="#A39D8F" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">
										Cor quaternária da marca (opcional)
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="members_color_five" class="m-pagebase__form-label">Cor Quinária</label>
									<div class="m-settings__color-group">
										<input type="color" id="members_color_five_picker" value="<?= htmlspecialchars($settings['members_color_five'] ?? '#A39D8F') ?>" class="m-settings__color-picker">
										<input type="text" id="members_color_five" name="members_color_five" value="<?= htmlspecialchars($settings['members_color_five'] ?? '#A39D8F') ?>" placeholder="#A39D8F" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor quinária da marca (opcional)</div>
								</div>

								<p><br>Cores do Dashboard de members<br><br></p>

								<div class="m-settings__form-group">
									<label for="dash_bg_main" class="m-pagebase__form-label">
										Background Main (Light Mode)
									</label>
									<div class="m-settings__color-group">
										<input type="color" id="dash_bg_main_picker" value="<?= htmlspecialchars($settings['dash_bg_main'] ?? '#160f47') ?>" class="m-settings__color-picker">
										<input type="text" id="dash_bg_main" name="dash_bg_main" value="<?= htmlspecialchars($settings['dash_bg_main'] ?? '#160f47') ?>" placeholder="#160f47" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor de fundo principal do dashboard</div>
								</div>

								<div class="m-settings__form-group">
									<label for="dash_bg_main_dark" class="m-pagebase__form-label">Background Main (Dark Mode)</label>
									<div class="m-settings__color-group">
											<input type="color" id="dash_bg_main_dark_picker" value="<?= htmlspecialchars($settings['dash_bg_main_dark'] ?? '#E5E6E7') ?>" class="m-settings__color-picker">
											<input type="text" id="dash_bg_main_dark" name="dash_bg_main_dark" value="<?= htmlspecialchars($settings['dash_bg_main_dark'] ?? '#E5E6E7') ?>" placeholder="#E5E6E7" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor de fundo principal do dashboard em dark mode</div>
								</div>

								<div class="m-settings__form-group">
									<label for="dash_bg_bread" class="m-pagebase__form-label">Background Breadcrumb (Light Mode)</label>
									<div class="m-settings__color-group">
											<input type="color" id="dash_bg_bread_picker" value="<?= htmlspecialchars($settings['dash_bg_bread'] ?? '#282542') ?>" class="m-settings__color-picker">
											<input type="text" id="dash_bg_bread" name="dash_bg_bread" value="<?= htmlspecialchars($settings['dash_bg_bread'] ?? '#282542') ?>" placeholder="#282542" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor de fundo do breadcrumb do dashboard</div>
								</div>

								<div class="m-settings__form-group">
									<label for="dash_bg_bread_dark" class="m-pagebase__form-label">Background Breadcrumb (Dark Mode)</label>
									<div class="m-settings__color-group">
											<input type="color" id="dash_bg_bread_dark_picker" value="<?= htmlspecialchars($settings['dash_bg_bread_dark'] ?? '#F0F0F0') ?>" class="m-settings__color-picker">
											<input type="text" id="dash_bg_bread_dark" name="dash_bg_bread_dark" value="<?= htmlspecialchars($settings['dash_bg_bread_dark'] ?? '#F0F0F0') ?>" placeholder="#F0F0F0" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor de fundo do breadcrumb em dark mode</div>
								</div>

								<div class="m-settings__form-group">
									<label for="dash_bg_aside" class="m-pagebase__form-label">Background Aside/Sidebar</label>
									<div class="m-settings__color-group">
											<input type="color" id="dash_bg_aside_picker" value="<?= htmlspecialchars($settings['dash_bg_aside'] ?? '#0056ff') ?>" class="m-settings__color-picker">
											<input type="text" id="dash_bg_aside" name="dash_bg_aside" value="<?= htmlspecialchars($settings['dash_bg_aside'] ?? '#0056ff') ?>" placeholder="#0056ff" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor de fundo do menu lateral (sidebar/aside)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="dash_bg_logo" class="m-pagebase__form-label">Logo Color (Light Mode)</label>
									<div class="m-settings__color-group">
											<input type="color" id="dash_bg_logo_picker" value="<?= htmlspecialchars($settings['dash_bg_logo'] ?? '#0056ff') ?>" class="m-settings__color-picker">
											<input type="text" id="dash_bg_logo" name="dash_bg_logo" value="<?= htmlspecialchars($settings['dash_bg_logo'] ?? '#0056ff') ?>" placeholder="#0056ff" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor da logo no header (light mode)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="dash_bg_logo_dark" class="m-pagebase__form-label">Logo Color (Dark Mode)</label>
									<div class="m-settings__color-group">
											<input type="color" id="dash_bg_logo_dark_picker" value="<?= htmlspecialchars($settings['dash_bg_logo_dark'] ?? '#ffffff') ?>" class="m-settings__color-picker">
											<input type="text" id="dash_bg_logo_dark" name="dash_bg_logo_dark" value="<?= htmlspecialchars($settings['dash_bg_logo_dark'] ?? '#ffffff') ?>" placeholder="#ffffff" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor da logo no header (dark mode)</div>
								</div>

							</div>

							<!-- Tema - Fontes -->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="palette"></i><span>Tema - Fontes</span>
								</div>

								<!-- Botão Gerenciar Fontes -->
								<div class="m-settings__form-group">
									<a href="<?= url('/admin/fonts') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto" style="display: inline-flex; align-items: center; gap: 0.5rem;">
										<i data-lucide="type"></i> Gerenciar Fontes
									</a>
									<div class="m-settings__form-help" style="margin-top: 0.5rem;">
										Faça upload, visualize e remova fontes WOFF2 customizadas
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="members_font_title" class="m-pagebase__form-label">Font Title</label>
									<select id="members_font_title" name="members_font_title" class="m-pagebase__form-input">
										<option value="system-ui" <?= ($settings['members_font_title'] ?? 'system-ui') === 'system-ui' ? 'selected' : '' ?>>System UI (Padrão do navegador)</option>
										<?php foreach ($fontFamilies as $family): ?>
											<option value="<?= htmlspecialchars($family) ?>" <?= ($settings['members_font_title'] ?? '') === $family ? 'selected' : '' ?>>
												<?= htmlspecialchars($family) ?>
											</option>
										<?php endforeach; ?>
									</select>
									<div class="m-settings__form-help">Fonte usada em títulos (h1, h2, h3, h4, h5, h6)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="members_font_text" class="m-pagebase__form-label">Font Text</label>
									<select id="members_font_text" name="members_font_text" class="m-pagebase__form-input">
										<option value="system-ui" <?= ($settings['members_font_text'] ?? 'system-ui') === 'system-ui' ? 'selected' : '' ?>>System UI (Padrão do navegador)</option>
										<?php foreach ($fontFamilies as $family): ?>
											<option value="<?= htmlspecialchars($family) ?>" <?= ($settings['members_font_text'] ?? '') === $family ? 'selected' : '' ?>>
												<?= htmlspecialchars($family) ?>
											</option>
										<?php endforeach; ?>
									</select>
									<div class="m-settings__form-help">
										Fonte usada em textos (p, a, ul, li, button, label, table)
									</div>
								</div>

							</div>

							<!-- Favicon Members -->
							<div class="m-settings__section">
								<div class="m-settings__section-title">
									<i data-lucide="image"></i><span>Favicon</span>
								</div>
								<div class="m-settings__form-group">
									<label for="members_favicon" class="m-pagebase__form-label">Favicon - Área de Membros</label>
									<input type="file" id="members_favicon" name="members_favicon"
									       accept=".ico,.png,.svg,image/x-icon,image/png,image/svg+xml"
									       class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Ícone exibido na aba do navegador para área de membros (ICO, PNG ou SVG - máx 500KB)<br>
										Recomendado: 32x32px ou 64x64px
									</div>
									<?php if (!empty($settings['members_favicon'])): ?>
										<div class="m-settings__favicon-preview" style="margin-top: 10px;">
											<strong>Favicon atual:</strong><br>
											<img src="<?= url('/storage/' . $settings['members_favicon']) ?>"
											     alt="Favicon Members"
											     style="width: 32px; height: 32px; border: 1px solid #ddd; padding: 4px; background: white;">
										</div>
									<?php endif; ?>
								</div>
							</div>

							<h2 class="m-settings__subtitle">admin</h2>

							<!-- Tema - Cores -->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="palette"></i><span>Tema - Cores</span>
								</div>

								<div class="m-settings__form-group">
									<label for="admin_color_main" class="m-pagebase__form-label">Cor Principal</label>
									<div class="m-settings__color-group">
											<input type="color" id="admin_color_main_picker" value="<?= htmlspecialchars($settings['admin_color_main'] ?? '#6c10b8') ?>" class="m-settings__color-picker">
											<input type="text" id="admin_color_main" name="admin_color_main" value="<?= htmlspecialchars($settings['admin_color_main'] ?? '#6c10b8') ?>" placeholder="#6c10b8" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">
										Cor principal da marca (usada em botões, títulos, etc)
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="admin_color_second" class="m-pagebase__form-label">Cor Secundária</label>
									<div class="m-settings__color-group">
											<input type="color" id="admin_color_second_picker" value="<?= htmlspecialchars($settings['admin_color_second'] ?? '#FAEA26') ?>" class="m-settings__color-picker">
											<input type="text" id="admin_color_second" name="admin_color_second" value="<?= htmlspecialchars($settings['admin_color_second'] ?? '#FAEA26') ?>" placeholder="#FAEA26" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor secundária da marca (destaques, elementos)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="admin_color_third" class="m-pagebase__form-label">Cor Terciária</label>
									<div class="m-settings__color-group">
											<input type="color" id="admin_color_third_picker" value="<?= htmlspecialchars($settings['admin_color_third'] ?? '#1D4872') ?>" class="m-settings__color-picker">
											<input type="text" id="admin_color_third" name="admin_color_third" value="<?= htmlspecialchars($settings['admin_color_third'] ?? '#1D4872') ?>" placeholder="#1D4872" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor terciária da marca (fundos, seções)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="admin_color_four" class="m-pagebase__form-label">Cor Quaternária</label>
									<div class="m-settings__color-group">
											<input type="color" id="admin_color_four_picker" value="<?= htmlspecialchars($settings['admin_color_four'] ?? '#A39D8F') ?>" class="m-settings__color-picker">
											<input type="text" id="admin_color_four" name="admin_color_four" value="<?= htmlspecialchars($settings['admin_color_four'] ?? '#A39D8F') ?>" placeholder="#A39D8F" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor quaternária da marca (opcional)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="admin_color_five" class="m-pagebase__form-label">Cor Quinária</label>
									<div class="m-settings__color-group">
											<input type="color" id="admin_color_five_picker" value="<?= htmlspecialchars($settings['admin_color_five'] ?? '#A39D8F') ?>" class="m-settings__color-picker">
											<input type="text" id="admin_color_five" name="admin_color_five" value="<?= htmlspecialchars($settings['admin_color_five'] ?? '#A39D8F') ?>" placeholder="#A39D8F" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" class="m-pagebase__form-input m-settings__color-input">
									</div>
									<div class="m-settings__form-help">Cor quinária da marca (opcionala)</div>
								</div>

							</div>

							<!-- Tema - Fontes -->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="palette"></i><span>Tema - Fontes</span>
								</div>

								<!-- Botão Gerenciar Fontes -->
								<div class="m-settings__form-group">
									<a href="<?= url('/admin/fonts') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto" style="display: inline-flex; align-items: center; gap: 0.5rem;">
										<i data-lucide="type"></i> Gerenciar Fontes
									</a>
									<div class="m-settings__form-help" style="margin-top: 0.5rem;">
										Faça upload, visualize e remova fontes WOFF2 customizadas
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="admin_font_title" class="m-pagebase__form-label">Font Title</label>
									<select id="admin_font_title" name="admin_font_title" class="m-pagebase__form-input">
										<option value="system-ui" <?= ($settings['admin_font_title'] ?? 'system-ui') === 'system-ui' ? 'selected' : '' ?>>System UI (Padrão do navegador)</option>
										<?php foreach ($fontFamilies as $family): ?>
											<option value="<?= htmlspecialchars($family) ?>" <?= ($settings['admin_font_title'] ?? '') === $family ? 'selected' : '' ?>>
												<?= htmlspecialchars($family) ?>
											</option>
										<?php endforeach; ?>
									</select>
									<div class="m-settings__form-help">Fonte usada em títulos (h1, h2, h3, h4, h5, h6)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="admin_font_text" class="m-pagebase__form-label">Font Text</label>
									<select id="admin_font_text" name="admin_font_text" class="m-pagebase__form-input">
										<option value="system-ui" <?= ($settings['admin_font_text'] ?? 'system-ui') === 'system-ui' ? 'selected' : '' ?>>System UI (Padrão do navegador)</option>
										<?php foreach ($fontFamilies as $family): ?>
											<option value="<?= htmlspecialchars($family) ?>" <?= ($settings['admin_font_text'] ?? '') === $family ? 'selected' : '' ?>>
												<?= htmlspecialchars($family) ?>
											</option>
										<?php endforeach; ?>
									</select>
									<div class="m-settings__form-help">
										Fonte usada em textos (p, a, ul, li, button, label, table)
									</div>
								</div>
							</div>

							<!-- Favicon Admin -->
							<div class="m-settings__section">
								<div class="m-settings__section-title">
									<i data-lucide="image"></i><span>Favicon</span>
								</div>
								<div class="m-settings__form-group">
									<label for="admin_favicon" class="m-pagebase__form-label">Favicon - Painel Admin</label>
									<input type="file" id="admin_favicon" name="admin_favicon"
									       accept=".ico,.png,.svg,image/x-icon,image/png,image/svg+xml"
									       class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Ícone exibido na aba do navegador para painel administrativo (ICO, PNG ou SVG - máx 500KB)<br>
										Recomendado: 32x32px ou 64x64px
									</div>
									<?php if (!empty($settings['admin_favicon'])): ?>
										<div class="m-settings__favicon-preview" style="margin-top: 10px;">
											<strong>Favicon atual:</strong><br>
											<img src="<?= url('/storage/' . $settings['admin_favicon']) ?>"
											     alt="Favicon Admin"
											     style="width: 32px; height: 32px; border: 1px solid #ddd; padding: 4px; background: white;">
										</div>
									<?php endif; ?>
								</div>
							</div>

						</div>

						<!-- Coluna 3 -->
						<div class="m-settings__column">

							<h2 class="m-settings__subtitle">SMTP para Comunicação Projeto</h2>			

							<!-- SMTP - comunicação do projeto-->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="server"></i>
									<span>SMTP & Email (PHPMailer)</span>
								</div>

								<div class="m-settings__form-group">
									<label for="smtp_host" class="m-pagebase__form-label">Servidor SMTP</label>
									<input type="text" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars(defined('SMTP_HOST') ? SMTP_HOST : '') ?>" placeholder="smtp.gmail.com" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Endereço do servidor SMTP (ex: smtp.gmail.com, smtp.office365.com)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="smtp_port" class="m-pagebase__form-label">Porta SMTP</label>
									<input type="number" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars(defined('SMTP_PORT') ? SMTP_PORT : '') ?>" placeholder="587" min="1" max="65535" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Porta do servidor SMTP (587 para TLS, 465 para SSL)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="smtp_username" class="m-pagebase__form-label">Usuário SMTP</label>
									<input type="email" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars(defined('SMTP_USERNAME') ? SMTP_USERNAME : '') ?>" placeholder="seu-email@gmail.com" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Email completo usado para autenticação SMTP</div>
								</div>

								<div class="m-settings__form-group">
									<label for="smtp_password" class="m-pagebase__form-label">Senha SMTP</label>
									<input type="password" id="smtp_password" name="smtp_password" value="<?= htmlspecialchars(defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '') ?>" placeholder="App Password do Gmail" class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Senha do email ou App Password.
										<a href="https://myaccount.google.com/apppasswords" target="_blank">Gmail: Gerar App Password</a>
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="smtp_from_email" class="m-pagebase__form-label">Email Remetente</label>
									<input type="email" id="smtp_from_email" name="smtp_from_email" value="<?= htmlspecialchars(defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '') ?>" placeholder="noreply@seudominio.com" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Email que aparecerá como remetente</div>
								</div>

								<div class="m-settings__form-group">
									<label for="smtp_from_name" class="m-pagebase__form-label">Nome Remetente</label>
									<input type="text" id="smtp_from_name" name="smtp_from_name" value="<?= htmlspecialchars(defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : '') ?>" placeholder="Instituto Atualli" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Nome que aparecerá como remetente</div>
								</div>

								<div class="m-settings__form-group">
									<label for="smtp_encryption" class="m-pagebase__form-label">Tipo de Criptografia</label>
									<select id="smtp_encryption" name="smtp_encryption" class="m-pagebase__form-select">
										<option value="" <?= !defined('SMTP_ENCRYPTION') || SMTP_ENCRYPTION === '' ? 'selected' : '' ?>>-- Selecione --</option>
										<option value="tls" <?= (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'tls') ? 'selected' : '' ?>>TLS (porta 587)</option>
										<option value="ssl" <?= (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'ssl') ? 'selected' : '' ?>>SSL (porta 465)</option>
									</select>
									<div class="m-settings__form-help">TLS é recomendado para a maioria dos servidores</div>
								</div>

								<div class="m-settings__form-group">
									<button type="button" id="test-client-smtp" class="m-pagebase__btn m-pagebase__btn--widthauto" style="background: #3498db; margin-top: 20px;" data-url="<?= url('/admin/settings/test-client-smtp') ?>">
											<i data-lucide="mail"></i> Testar SMTP Cliente
									</button>
									<div id="test-client-result" style="margin-top: 10px;"></div>
								</div>

							</div>

							<!-- Integrações e API -->
							<h2 class="m-settings__subtitle">Integrações e API</h2>			

							<!-- RD Station -->
							<div class="m-settings__section">

								<div class="m-settings__section-title">
									<i data-lucide="trending-up"></i>
									<span>RD Station Marketing</span>
								</div>

								<div class="m-settings__form-group">
									<label class="m-pagebase__form-checkbox">
										<input type="checkbox" id="rdstation_enabled" name="rdstation_enabled" value="1" <?= (defined('RDSTATION_ENABLED') && RDSTATION_ENABLED) ? 'checked' : '' ?>>
										Habilitar RD Station
									</label>
									<div class="m-settings__form-help">
										Ativa o envio de leads capturados para o RD Station Marketing
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="rdstation_api_key" class="m-pagebase__form-label">API Key (Token Público)</label>
									<input type="text" id="rdstation_api_key" name="rdstation_api_key" value="<?= htmlspecialchars(defined('RDSTATION_API_KEY') ? RDSTATION_API_KEY : '') ?>" placeholder="ec7ec89963b10f2f5139fad15c28fd72" class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Token de integração do RD Station.
										<a href="https://app.rdstation.com.br/integracoes/tokens" target="_blank">Obter token aqui</a>
									</div>
								</div>
								
							</div>

							<!-- Tiny MCE -->
							<div class="m-settings__section">
								<div class="m-settings__section-title">
									<i data-lucide="plug"></i><span>Tiny MCE</span>
								</div>
								<div class="m-settings__form-group">
									<label for="tinymce_api_key" class="m-pagebase__form-label">TinyMCE API Key (Editor de Texto Rico)</label>
									<input type="text" id="tinymce_api_key" name="tinymce_api_key" value="<?= htmlspecialchars($settings['tinymce_api_key'] ?? defined('TINYMCE_API_KEY') ? TINYMCE_API_KEY : '') ?>" placeholder="no-api-key" class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Usado pelo módulo Blog para editor WYSIWYG.
										<a href="https://www.tiny.cloud/auth/signup/" target="_blank">Obtenha gratuitamente aqui</a>
										(plano free: 1.000 carregamentos/mês)
									</div>
								</div>
							</div>

							<!-- Google Tag Manager -->
							<div class="m-settings__section">
								<div class="m-settings__section-title">
									<i data-lucide="monitor"></i><span>Google Tag Manager</span>
								</div>
								<div class="m-settings__form-group">
									<label for="gtm_id" class="m-pagebase__form-label">GTM Container ID</label>
									<input type="text" id="gtm_id" name="gtm_id" value="<?= htmlspecialchars($settings['gtm_id'] ?? '') ?>" placeholder="GTM-XXXXXXX" class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										ID do container do Google Tag Manager (ex: GTM-W8DBMTXX).<br>
										Será inserido automaticamente em <code>_gtm-head.php</code> e <code>_gtm-body.php</code>
									</div>
								</div>
							</div>

							<!-- UptimeRobot -->
							<div class="m-settings__section">
								<div class="m-settings__section-title">
									<i data-lucide="activity"></i><span>UptimeRobot</span>
								</div>
								<div class="m-settings__form-group">
									<label for="uptime_robot_api_key" class="m-pagebase__form-label">API Key</label>
									<input type="text" id="uptime_robot_api_key" name="uptime_robot_api_key" value="<?= htmlspecialchars($settings['uptime_robot_api_key'] ?? '') ?>" placeholder="u123456-abcdef..." class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Chave de API do UptimeRobot (acesse: Dashboard → Integrations & API → API).<br>
										Permite monitorar uptime, downtime e tempo de resposta do site.
									</div>
								</div>
							</div>

							<!-- Google Search Console -->
							<div class="m-settings__section">
								<div class="m-settings__section-title">
									<i data-lucide="bar-chart-3"></i><span>Google Search Console</span>
								</div>

								<?php
								$gscCredentialsPath = ROOT_PATH . 'config/google-service-account.json';
								$gscHasCredentials = file_exists($gscCredentialsPath);
								?>

								<div class="m-settings__form-group">
									<?php if ($gscHasCredentials): ?>
										<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
											<strong style="color: #155724;">✓ Credenciais configuradas</strong>
											<p style="margin: 5px 0 0 0; color: #155724; font-size: 13px;">
												Arquivo <code>google-service-account.json</code> encontrado.<br>
												A integração com Search Console API está ativa.
											</p>
										</div>
										<button type="button" class="m-pagebase__btn m-pagebase__btn--widthauto" style="background: #dc3545;" onclick="if(confirm('Tem certeza que deseja remover as credenciais do Google Search Console?')) { document.getElementById('remove-gsc-form').submit(); }">
											<i data-lucide="trash-2"></i> Remover Credenciais
										</button>
										<form id="remove-gsc-form" method="POST" action="<?= url('/admin/settings/remove-gsc-credentials') ?>" style="display: none;">
											<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
										</form>
									<?php else: ?>
										<label for="gsc_credentials" class="m-pagebase__form-label">Service Account Credentials (JSON)</label>
										<input type="file" id="gsc_credentials" name="gsc_credentials" accept=".json,application/json" class="m-pagebase__form-input">
										<div class="m-settings__form-help">
											Arquivo JSON com credenciais da Service Account do Google Cloud.<br>
											<strong>Passos:</strong>
											<ol style="margin: 10px 0; padding-left: 20px; font-size: 13px;">
												<li>Acesse <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a></li>
												<li>Crie ou selecione um projeto</li>
												<li>Ative a "Google Search Console API"</li>
												<li>Vá em "Credentials" → "Create Credentials" → "Service Account"</li>
												<li>Crie a Service Account e baixe o arquivo JSON</li>
												<li>Faça upload do arquivo aqui</li>
												<li>Adicione o email da Service Account como Owner no Search Console</li>
											</ol>
											<strong>Segurança:</strong> O arquivo será salvo em <code>/config/google-service-account.json</code> (fora do Git)
										</div>
									<?php endif; ?>
								</div>
							</div>

							<!-- PageSpeed Insights -->
							<div class="m-settings__section" id="pagespeed">

								<div class="m-settings__section-title">
									<i data-lucide="gauge"></i>
									<span>Google PageSpeed Insights</span>
								</div>

								<div class="m-settings__form-group">
									<label class="m-pagebase__form-checkbox">
										<input type="checkbox" id="pagespeed_enabled" name="pagespeed_enabled" value="1" <?= isset($settings['pagespeed_enabled']) && $settings['pagespeed_enabled'] ? 'checked' : '' ?>>
										Habilitar PageSpeed Insights
									</label>
									<div class="m-settings__form-help">
										Ativa a análise de performance das páginas do site usando Google PageSpeed API v5
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="pagespeed_api_key" class="m-pagebase__form-label">Google API Key</label>
									<input type="password" id="pagespeed_api_key" name="pagespeed_api_key" value="<?= htmlspecialchars($settings['pagespeed_api_key'] ?? '') ?>" placeholder="AIzaSy..." class="m-pagebase__form-input">
									<div class="m-settings__form-help">
										Chave de API do Google Cloud com PageSpeed Insights API habilitada.
										<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Obter API Key aqui</a>
									</div>
								</div>

								<div class="m-settings__form-group">
									<label class="m-pagebase__form-checkbox">
										<input type="checkbox" id="pagespeed_auto_enabled" name="pagespeed_auto_enabled" value="1" <?= isset($settings['pagespeed_auto_enabled']) && $settings['pagespeed_auto_enabled'] ? 'checked' : '' ?>>
										Habilitar Análise Automática
									</label>
									<div class="m-settings__form-help">
										Executa análises periódicas automaticamente via n8n
									</div>
								</div>

								<div class="m-settings__form-group">
									<label for="pagespeed_frequency" class="m-pagebase__form-label">Frequência de Análise</label>
									<select id="pagespeed_frequency" name="pagespeed_frequency" class="m-pagebase__form-select">
										<option value="daily" <?= isset($settings['pagespeed_frequency']) && $settings['pagespeed_frequency'] === 'daily' ? 'selected' : '' ?>>Diária</option>
										<option value="weekly" <?= isset($settings['pagespeed_frequency']) && $settings['pagespeed_frequency'] === 'weekly' ? 'selected' : '' ?>>Semanal</option>
										<option value="monthly" <?= isset($settings['pagespeed_frequency']) && $settings['pagespeed_frequency'] === 'monthly' ? 'selected' : '' ?>>Mensal</option>
									</select>
									<div class="m-settings__form-help">Com que frequência rodar análises automáticas</div>
								</div>

								<div class="m-settings__form-group">
									<label for="pagespeed_time" class="m-pagebase__form-label">Horário da Análise</label>
									<input type="time" id="pagespeed_time" name="pagespeed_time" value="<?= htmlspecialchars($settings['pagespeed_time'] ?? '03:00') ?>" class="m-pagebase__form-input" style="max-width: 200px;">
									<div class="m-settings__form-help">Horário para executar análises automáticas (recomendado: madrugada)</div>
								</div>

								<div class="m-settings__form-group">
									<label class="m-pagebase__form-label">Estratégias de Análise</label>
									<div style="display: flex; gap: 20px; margin-top: 10px;">
										<label class="m-pagebase__form-checkbox">
											<input type="checkbox" name="pagespeed_strategy_mobile" value="1" <?= isset($settings['pagespeed_strategy_mobile']) && $settings['pagespeed_strategy_mobile'] ? 'checked' : '' ?>>
											Mobile
										</label>
										<label class="m-pagebase__form-checkbox">
											<input type="checkbox" name="pagespeed_strategy_desktop" value="1" <?= isset($settings['pagespeed_strategy_desktop']) && $settings['pagespeed_strategy_desktop'] ? 'checked' : '' ?>>
											Desktop
										</label>
									</div>
									<div class="m-settings__form-help">Selecione quais dispositivos analisar (recomendado: ambos)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="pagespeed_alert_threshold" class="m-pagebase__form-label">Threshold de Alerta (Score)</label>
									<input type="number" id="pagespeed_alert_threshold" name="pagespeed_alert_threshold" value="<?= htmlspecialchars($settings['pagespeed_alert_threshold'] ?? '70') ?>" min="0" max="100" class="m-pagebase__form-input" style="max-width: 150px;">
									<div class="m-settings__form-help">Enviar alerta se performance score cair abaixo deste valor (0-100)</div>
								</div>

								<div class="m-settings__form-group">
									<label for="pagespeed_alert_email" class="m-pagebase__form-label">Email para Alertas</label>
									<input type="email" id="pagespeed_alert_email" name="pagespeed_alert_email" value="<?= htmlspecialchars($settings['pagespeed_alert_email'] ?? '') ?>" placeholder="dev@seudominio.com" class="m-pagebase__form-input">
									<div class="m-settings__form-help">Email que receberá notificações de performance baixa</div>
								</div>

								<div class="m-settings__form-group">
									<div style="background: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 3px solid #3498db;">
										<strong>Webhook Secret:</strong>
										<code style="background: #fff; padding: 4px 8px; border-radius: 3px; font-family: monospace; font-size: 12px; display: inline-block; margin-left: 10px;">
											<?= htmlspecialchars($settings['pagespeed_webhook_secret'] ?? 'Será gerado ao salvar') ?>
										</code>
										<div style="margin-top: 10px; font-size: 13px; color: #666;">
											Use este secret no webhook do n8n para autenticação segura
										</div>
									</div>
								</div>

							</div>

						</div>

					</div>

					<div class="m-pagebase__form-actions">
						<button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
							<i data-lucide="save"></i> Salvar Configurações
						</button>
						<a href="<?= url('/admin') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--builder">
							<i data-lucide="x"></i> Cancelar
						</a>
					</div>

				</form>

			</div>

		</main>

		<script src="https://unpkg.com/lucide@latest"></script>
		<script>
			lucide.createIcons();
		</script>
		<script src="<?= url('/assets/js/admin-settings.js') ?>"></script>

	</body>
	
</html>
