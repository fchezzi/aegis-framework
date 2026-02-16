<?php
    /**
     * Exibição de Palpites - Tela para OBS
     * Mostra APENAS os palpites dos apresentadores para jogos ATIVOS
     * NÃO mostra resultado real
     */

    // Habilitar compressão de saída
    if (!ob_get_level()) ob_start('ob_gzhandler');

    $db = DB::connect();

    // CACHE de 30 segundos para tela de exibição
    $cache_key = 'exibicao_palpites_data';
    $cached_data = SimpleCache::get($cache_key);

    if ($cached_data !== null) {
        extract($cached_data);
    } else {
        // Buscar TODOS os jogos ATIVOS
        $jogos_ativos = $db->query("
            SELECT
                j.*,
                tm.nome as time_mandante_nome,
                tm.sigla as time_mandante_sigla,
                tm.escudo_url as time_mandante_escudo,
                tv.nome as time_visitante_nome,
                tv.sigla as time_visitante_sigla,
                tv.escudo_url as time_visitante_escudo
            FROM tbl_jogos_palpites j
            LEFT JOIN tbl_times tm ON j.time_mandante_id = tm.id
            LEFT JOIN tbl_times tv ON j.time_visitante_id = tv.id
            WHERE j.ativo = true
            ORDER BY j.data_jogo ASC
        ");

        // Buscar TODAS as palpiteiras ativas ordenadas pela ordem de exibição
        $todas_palpiteiras = $db->query("SELECT * FROM tbl_palpiteiros WHERE ativo = true ORDER BY ordem ASC");

        // Buscar TODOS os palpites de TODOS os jogos ativos de uma vez (otimização)
        $palpites_por_jogo = [];
        if (!empty($jogos_ativos)) {
            $jogo_ids = array_column($jogos_ativos, 'id');
            $jogo_ids_placeholders = "'" . implode("','", array_map(function($id) {
                return Security::sanitize($id);
            }, $jogo_ids)) . "'";

            $todos_palpites = $db->query("
                SELECT
                    p.*,
                    pa.nome as palpiteiro_nome
                FROM tbl_palpites p
                JOIN tbl_palpiteiros pa ON p.palpiteiro_id = pa.id
                WHERE p.jogo_id IN ($jogo_ids_placeholders)
                ORDER BY pa.ordem ASC
            ");

            // Indexar palpites por jogo_id
            foreach ($todos_palpites as $p) {
                if (!isset($palpites_por_jogo[$p['jogo_id']])) {
                    $palpites_por_jogo[$p['jogo_id']] = [];
                }
                $palpites_por_jogo[$p['jogo_id']][] = $p;
            }
        }

        // Salvar no cache por 30 segundos
        SimpleCache::set($cache_key, compact('jogos_ativos', 'todas_palpiteiras', 'palpites_por_jogo'), 30);
    }

    // Preparar jogos com palpites
    $jogos_com_palpites = [];
    foreach ($jogos_ativos as $jogo) {
        $palpites_array = $palpites_por_jogo[$jogo['id']] ?? [];

        // Indexar palpites por palpiteiro_id
        $palpites_dados = [];
        foreach ($palpites_array as $p) {
            $palpites_dados[$p['palpiteiro_id']] = $p;
        }

        // Criar array final: todas as palpiteiras com ou sem palpite
        $palpites = [];
        foreach ($todas_palpiteiras as $palpiteira) {
            if (isset($palpites_dados[$palpiteira['id']])) {
                // Tem palpite - mesclar dados da palpiteira com dados do palpite
                $palpite_data = $palpites_dados[$palpiteira['id']];
                $palpites[] = [
                    'palpiteiro_id' => $palpiteira['id'],
                    'nome' => $palpiteira['nome'],
                    'foto_url' => $palpiteira['foto_url'],
                    'gols_mandante' => $palpite_data['gols_mandante'],
                    'gols_visitante' => $palpite_data['gols_visitante']
                ];
            } else {
                // Não tem palpite, cria vazio
                $palpites[] = [
                    'palpiteiro_id' => $palpiteira['id'],
                    'nome' => $palpiteira['nome'],
                    'foto_url' => $palpiteira['foto_url'],
                    'gols_mandante' => null,
                    'gols_visitante' => null
                ];
            }
        }

        $jogos_com_palpites[] = [
            'jogo' => $jogo,
            'palpites' => $palpites
        ];
    }
?>

<!DOCTYPE html>
<html lang="pt-BR">

  <head>

    <!-- include - gtm-head -->
    <?php Core::requireInclude('frontend/includes/_gtm-head.php', true); ?>

    <!-- include - head -->
    <?php Core::requireInclude('frontend/includes/_head.php', true); ?>

    <meta name="keywords" content="inserir,as,palavras,chave">
    <meta name="description" content="inserir o meta keywords">

    <!-- css files -->
    <link rel="stylesheet" type="text/css"  href="<?= url('/modules/palpites/assets/css/m-palpites.css') ?>" >		

    <title>Energia 97</title>

  </head>
  

	<body class="m-damas__palpite">

		<!-- include - gtm-body -->
		<?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>

		<!-- Navegação entre telas -->
		<div class="m-damas__nav">
			<a href="<?= url('/palpites/exibicao-palpites') ?>" class="m-damas__nav-btn m-damas__nav-btn--active" title="Palpites">
				<i data-lucide="tv"></i>
			</a>
			<a href="<?= url('/palpites/exibicao-resultados') ?>" class="m-damas__nav-btn" title="Resultados">
				<i data-lucide="bar-chart-2"></i>
			</a>
			<a href="<?= url('/palpites/exibicao-ranking') ?>" class="m-damas__nav-btn" title="Ranking">
				<i data-lucide="trophy"></i>
			</a>
		</div>

		<main>

			<?php if (empty($jogos_com_palpites)): ?>
				<div style="color: white; text-align: center; padding: 50px; font-size: 24px;">
					Nenhum jogo ativo. Ative um jogo na lista de jogos.
				</div>
			<?php else: ?>		
			
				<!-- TOP: Fotos das palpiteiras (SEMPRE UMA VEZ SÓ) -->
				<div class="m-damas__palpite--top">
					<div class="m-damas__palpite--time">&nbsp</div>
					<?php
					// Usa as palpiteiras do primeiro jogo (todas têm as mesmas palpiteiras)
					$palpites_primeira = $jogos_com_palpites[0]['palpites'];
					foreach ($palpites_primeira as $palpite):
					?>
					<div class="m-damas__palpite--player">
						<?php if (!empty($palpite['foto_url'])): ?>
						<img src="<?= Upload::url($palpite['foto_url']) ?>" alt="<?= htmlspecialchars($palpite['nome']) ?>">
						<?php endif; ?>
						<h5><?= htmlspecialchars($palpite['nome']) ?></h5>
					</div>
					<?php endforeach; ?>
					<div class="m-damas__palpite--time">&nbsp</div>
				</div>	

				<!-- BOTTOM: Repete para cada jogo ativo -->
				<?php foreach ($jogos_com_palpites as $item): ?>
					<?php $jogo = $item['jogo']; ?>
					<?php $palpites = $item['palpites']; ?>


					<div class="m-damas__palpite--bottom">

						<div class="m-damas__palpite--time">
							<?php if (!empty($jogo['time_mandante_escudo'])): ?>
							<img src="<?= Upload::url($jogo['time_mandante_escudo']) ?>" alt="<?= htmlspecialchars($jogo['time_mandante_nome']) ?>">
							<?php endif; ?>
						</div>

						<?php foreach ($palpites as $palpite): ?>
						<div class="m-damas__palpite--palpite">
							<h2><?= $palpite['gols_mandante'] ?></h2>
							<h3>X</h3>
							<h2><?= $palpite['gols_visitante'] ?></h2>
						</div>
						<?php endforeach; ?>

						<div class="m-damas__palpite--time">
							<?php if (!empty($jogo['time_visitante_escudo'])): ?>
							<img src="<?= Upload::url($jogo['time_visitante_escudo']) ?>" alt="<?= htmlspecialchars($jogo['time_visitante_nome']) ?>">
							<?php endif; ?>
						</div>

					</div>

				<?php endforeach; ?>

			<?php endif; ?>

		</main>

		<script>
			lucide.createIcons();
		</script>	

	</body>

</html>
