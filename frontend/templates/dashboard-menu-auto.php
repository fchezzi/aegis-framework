<?php
/**
 * Template: Dashboard com Menu Automático
 * Use este arquivo como referência para adicionar menu automático
 */

// Pegar usuário logado (admin ou member)
$user = Auth::user() ?? MemberAuth::member() ?? null;
?>

<!-- Exemplo de integração do MenuBuilder no sidebar -->

<!-- SIDEBAR -->
<aside class="l-sidebar" id="sidebar">
	<nav class="m-sidebar">

		<!-- Menu Automático (via MenuBuilder) -->
		<ul class="m-sidebar__menu">
			<?php
			// Pegar ID do member logado (ou null se for admin/público)
			$member = MemberAuth::member();
			$memberId = $member ? $member['id'] : null;

			// Renderizar menu dinâmico
			echo MenuBuilder::render($memberId);
			?>
		</ul>

	</nav>
</aside>

<!--
INSTRUÇÕES:

1. Substitua o conteúdo da tag <ul class="m-sidebar__menu"> no seu dashboard.php
2. Cole o código acima (linhas 16-25)
3. Delete todos os itens de menu manuais (li class="menu-item")
4. Pronto! O menu será gerado automaticamente do banco de dados

O MenuBuilder renderiza:
- Itens raiz como menu principal
- Subitens como submenu/accordion
- Filtra por permissões do member logado
- Adiciona classe "menu-item--active" baseado na URL atual
-->
