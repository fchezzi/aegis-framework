<?php
/**
 * Include: dash-header
 */
?>
<?php
// Pegar usuário logado baseado no contexto
if (!isset($user)) {
	// Se estiver em área admin, usar admin
	// Caso contrário, usar member (área pública/dashboard)
	$isAdminArea = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') !== false;

	if ($isAdminArea) {
		$user = Auth::user() ?? null;
	} else {
		$user = MemberAuth::member() ?? null;
	}
}
?>

<!-- HEADER DASHBOARD -->
<header class="l-header">
	<div class="m-header">

		<!-- Brand -->
		<a href="<?= url('/') ?>" class="m-header__brand">
			<?php
			$siteName = Settings::get('site_name') ?? 'AEGIS Framework';
			$siteLogo = Settings::get('site_logo');

			// Carregar SVG inline para permitir controle via CSS (color)
			if (!empty($siteLogo)) {
				$svgPath = ROOT_PATH . 'storage/' . $siteLogo;
				if (file_exists($svgPath) && pathinfo($svgPath, PATHINFO_EXTENSION) === 'svg') {
					$svg = file_get_contents($svgPath);
					// Remover XML declaration
					$svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
					// Adicionar fill="currentColor" para permitir controle via CSS
					$svg = preg_replace('/<path(?!\s+fill=)/', '<path fill="currentColor"', $svg);
					$svg = preg_replace('/<polygon(?!\s+fill=)/', '<polygon fill="currentColor"', $svg);
					// Adicionar aria-label para acessibilidade
					$svg = preg_replace('/<svg/', '<svg aria-label="' . htmlspecialchars($siteName, ENT_QUOTES) . '"', $svg, 1);
					echo $svg;
				} else {
					// Fallback: imagem externa
					echo '<img src="' . url('/assets/img/logo.svg') . '" alt="' . htmlspecialchars($siteName) . '">';
				}
			} else {
				// Fallback: logo padrão
				echo '<img src="' . url('/assets/img/logo.svg') . '" alt="' . htmlspecialchars($siteName) . '">';
			}
			?>
		</a>

		<!-- Actions -->
		<ul class="m-header__actions">
			<li class="theme-toggle-item">
				<a href="javascript:void(0);" class="theme-toggle">
					<i data-lucide="moon" class="dark-mode"></i>
					<i data-lucide="sun" class="light-mode"></i>
				</a>
			</li>
			<li class="user-profile-item">
				<a href="javascript:void(0);" class="user-profile user-toggle">
					<div class="avatar-container">
						<?php
						$avatarUrl = !empty($user['avatar'])
							? url($user['avatar'])
							: url('/assets/img/avatar/default.jpeg');
						?>
						<img src="<?= $avatarUrl ?>" alt="User" class="avatar">
					</div>
				</a>

				<div class="m-header__dropdown">
					<div class="dropdown-header">
						<span class="user-name"><?= htmlspecialchars($user['name'] ?? 'Usuário') ?></span>
					</div>
					<div class="dropdown-item">
						<a href="<?= url('/profile') ?>">
							<i data-lucide="user"></i>
							<span>Profile</span>
						</a>
					</div>
					<div class="dropdown-item">
						<a href="<?= url('/logout') ?>">
							<i data-lucide="log-out"></i>
							<span>Logout</span>
						</a>
					</div>
				</div>
			</li>
		</ul>

	</div>
</header>
