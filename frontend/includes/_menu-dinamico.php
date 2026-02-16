<?php
/**
 * Menu Dinâmico
 * Renderiza menu baseado em MenuBuilder com permissões
 */

// Pegar ID do member logado (null se não estiver logado)
$member = MemberAuth::member();
$memberId = $member ? $member['id'] : null;

// Renderizar menu
echo MenuBuilder::render($memberId);
?>
