<?php
/**
 * Include: dash-aside
 */
?>
<!-- SIDEBAR -->
<aside class="l-sidebar" id="sidebar">
	<nav class="m-sidebar">

		<?php $currentUrl = $_SERVER['REQUEST_URI']; ?>
		<!-- Menu -->
		<ul class="m-sidebar__menu">
			<?php require ROOT_PATH . 'frontend/includes/_menu-dinamico.php'; ?>
		</ul>

	</nav>
</aside>