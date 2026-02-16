<?php
// Pegar usuário logado (admin ou member)
$user = Auth::user() ?? MemberAuth::member() ?? null;

// Definir título e slug da página
// Quando criado via admin, Profile e profile são substituídos
// Quando carregado via rota, $pageTitle e $pageSlug são definidos
$title = isset($pageTitle) ? $pageTitle : 'Profile';
$slug = isset($pageSlug) ? $pageSlug : 'profile';
?>

<!DOCTYPE html>

<html lang="pt-br">

  <head>

    <!-- include - gtm-head -->
    <?php Core::requireInclude('frontend/includes/_gtm-head.php', true); ?>

    <!-- include - head -->
    <?php Core::requireInclude('frontend/includes/_head.php', true); ?>

    <meta name="keywords" content="inserir,as,palavras,chave">
    <meta name="description" content="inserir o meta keywords">

    <title><?= htmlspecialchars($title) ?> - Energia 97</title>

	</head>

	<body>

    <!-- include - gtm-body -->
    <?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>

		<!-- carrega light mode -->
		<script>
			// Carregar tema ANTES de renderizar (padrão: dark)
			const savedTheme = localStorage.getItem('theme');
			if (savedTheme !== 'light') {
			document.body.classList.add('dark');
			}
		</script>

    <!-- include - header -->
    <?php Core::requireInclude('frontend/includes/_dash-header.php', true); ?>

		<!-- breadcrumb -->
		<?= Core::renderBreadcrumb([
			['Home', '/'],
			['Dashboard', '/dashboard'],
			[htmlspecialchars($title)]
		]) ?>


		<!-- MAIN CONTAINER -->
		<main class="l-main">

			<!-- include - aside -->
			<?php Core::requireInclude('frontend/includes/_aside.php', true); ?>

			<!-- CONTENT -->
			<div class="l-content">

				<?php
				// Renderizar blocos do Page Builder
				echo PageBuilder::render($slug);
				?>

				<!-- PROFILE SECTION -->
				<section class="profile-section">

					<!-- Mensagens de feedback -->
					<?php if (isset($_SESSION['success'])): ?>
						<div class="alert alert-success">
							<?= htmlspecialchars($_SESSION['success']) ?>
						</div>
						<?php unset($_SESSION['success']); ?>
					<?php endif; ?>

					<?php if (isset($_SESSION['error'])): ?>
						<div class="alert alert-error">
							<?= htmlspecialchars($_SESSION['error']) ?>
						</div>
						<?php unset($_SESSION['error']); ?>
					<?php endif; ?>

					<div class="profile-grid">

						<!-- CARD 1: Informações do Perfil -->
						<div class="profile-card">
							<div class="profile-card-header">
								<h3>Informações do Perfil</h3>
								<p>Seus dados cadastrados</p>
							</div>

							<div class="profile-card-body">
								<div class="profile-info">
									<div class="info-item">
										<label>Nome</label>
										<p><?= htmlspecialchars($member['name'] ?? 'Não informado') ?></p>
									</div>

									<div class="info-item">
										<label>Email</label>
										<p><?= htmlspecialchars($member['email'] ?? 'Não informado') ?></p>
									</div>
								</div>
							</div>
						</div>

						<!-- CARD 2: Avatar -->
						<div class="profile-card">
							<div class="profile-card-header">
								<h3>Foto de Perfil</h3>
								<p>Atualize sua foto de perfil</p>
							</div>

							<div class="profile-card-body">
								<div class="avatar-section">
									<div class="avatar-preview">
										<?php if (!empty($member['avatar'])): ?>
											<img src="<?= htmlspecialchars($member['avatar']) ?>" alt="Avatar" id="avatar-img">
										<?php else: ?>
											<div class="avatar-placeholder">
												<i data-lucide="user"></i>
											</div>
										<?php endif; ?>
									</div>

									<form id="avatar-form" enctype="multipart/form-data">
										<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

										<div class="form-group">
											<label for="avatar-input" class="btn-upload">
												<span>Escolher Imagem</span>
											</label>
											<input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" style="display: none;">
										</div>

										<button type="submit" class="btn-primary" id="btn-save-avatar" style="display: none;">
											<i data-lucide="save"></i>
											<span>Salvar Foto</span>
										</button>

										<div class="upload-info">
											<p>JPG, PNG ou WEBP. Máximo 2MB.</p>
										</div>
									</form>
								</div>
							</div>
						</div>

						<!-- CARD 2: Senha -->
						<div class="profile-card">
							<div class="profile-card-header">
								<h3>Alterar Senha</h3>
								<p>Mantenha sua conta segura</p>
							</div>

							<div class="profile-card-body">
								<form id="password-form">
									<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

									<div class="form-group">
										<label for="current-password">Senha Atual</label>
										<input
											type="password"
											id="current-password"
											name="current_password"
											class="form-control"
											required
											autocomplete="current-password"
										>
									</div>

									<div class="form-group">
										<label for="new-password">Nova Senha</label>
										<input
											type="password"
											id="new-password"
											name="new_password"
											class="form-control"
											required
											minlength="8"
											autocomplete="new-password"
										>
										<small class="form-help">Mínimo 8 caracteres</small>
									</div>

									<div class="form-group">
										<label for="confirm-password">Confirmar Nova Senha</label>
										<input
											type="password"
											id="confirm-password"
											name="confirm_password"
											class="form-control"
											required
											minlength="8"
											autocomplete="new-password"
										>
									</div>

									<button type="submit" class="btn-primary">
										<span>Atualizar Senha</span>
									</button>
								</form>
							</div>
						</div>

					</div>
				</section>

			</div>

		</main>

	</body>

	<!-- Theme Toggle JS -->
	<script src="<?= url('/assets/js/theme-toggle-min.js') ?>"></script>

	<!-- Dashboard JS -->
	<script src="<?= url('/assets/js/dashboard-min.js') ?>"></script>

	<!-- Profile JS -->
	<script src="<?= url('/assets/js/profile-min.js') ?>"></script>

	<?php // Core::requireInclude('frontend/includes/_footer.php', true); ?>

	<script>lucide.createIcons();</script>

</html>
