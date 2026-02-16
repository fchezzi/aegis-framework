<?php
/**
 * Tela de Exibição para OBS
 * Mostra ranking + jogo ativo + palpites ao vivo
 */

$db = DB::connect();

// Buscar ranking - ORDENADO: 1º pontos, 2º exatos
$ranking = $db->query("
    SELECT * FROM vw_ranking_palpiteiros
    ORDER BY total_pontos DESC, placares_exatos DESC
    LIMIT 10
");

// Buscar jogo ativo mais recente
$jogo_ativo = $db->query("
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
    ORDER BY j.data_jogo DESC
    LIMIT 1
");

$jogo = !empty($jogo_ativo) ? $jogo_ativo[0] : null;

// Se tem jogo, buscar palpites
$palpites = [];
if ($jogo) {
    $jogo_id_safe = Security::sanitize($jogo['id']);
    $palpites = $db->query("
        SELECT
            p.*,
            pa.nome as palpiteiro_nome,
            pa.foto_url as palpiteiro_foto
        FROM tbl_palpites p
        JOIN tbl_palpiteiros pa ON p.palpiteiro_id = pa.id
        WHERE p.jogo_id = '$jogo_id_safe' AND pa.ativo = true
        ORDER BY pa.ordem ASC, pa.nome ASC
    ");
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palpites - Ao Vivo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            font-size: 48px;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
        }

        /* RANKING */
        .ranking {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
        }

        .ranking h2 {
            margin-bottom: 15px;
            font-size: 24px;
        }

        .ranking-item {
            background: rgba(255,255,255,0.15);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ranking-item.top3 {
            background: linear-gradient(90deg, rgba(255,215,0,0.3) 0%, rgba(255,255,255,0.15) 100%);
        }

        .ranking-pos {
            font-size: 20px;
            font-weight: bold;
            width: 30px;
        }

        .ranking-nome {
            flex: 1;
            font-weight: 600;
        }

        .ranking-pontos {
            font-size: 22px;
            font-weight: bold;
            color: #ffd700;
        }

        /* JOGO */
        .jogo-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 30px;
        }

        .jogo-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .jogo-header h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .jogo-vs {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            margin: 20px 0;
        }

        .jogo-time {
            text-align: center;
            flex: 1;
            max-width: 200px;
        }

        .jogo-time img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .jogo-time h3 {
            font-size: 28px;
            font-weight: bold;
        }

        .vs {
            font-size: 40px;
            font-weight: bold;
            color: rgba(255,255,255,0.6);
        }

        /* PALPITES */
        .palpites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }

        .palpite-card {
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }

        .palpite-card:hover {
            transform: scale(1.05);
        }

        .palpite-nome {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .palpite-placar {
            font-size: 36px;
            font-weight: bold;
            color: #ffd700;
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            font-size: 24px;
            color: rgba(255,255,255,0.7);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); color: #ffed4e; }
        }
    </style>
    <script>
        // ✅ OTIMIZADO: Atualizar apenas dados via AJAX (sem recarregar página)
        let lastUpdate = Date.now();

        async function atualizarDados() {
            try {
                const response = await fetch('<?= url('/palpites/api/updates') ?>');
                const data = await response.json();

                if (data.success) {
                    // Atualizar ranking
                    if (data.ranking) {
                        atualizarRanking(data.ranking);
                    }

                    // Atualizar palpites
                    if (data.palpites) {
                        atualizarPalpites(data.palpites);
                    }

                    lastUpdate = Date.now();
                }
            } catch (error) {
                console.error('Erro ao atualizar:', error);
            }
        }

        function atualizarRanking(ranking) {
            ranking.forEach((r, index) => {
                const element = document.querySelector(`.ranking-item[data-id="${r.palpiteiro_id}"]`);
                if (element) {
                    const pontosEl = element.querySelector('.ranking-pontos');
                    if (pontosEl && pontosEl.textContent !== r.total_pontos.toString()) {
                        pontosEl.textContent = r.total_pontos;
                        pontosEl.style.animation = 'pulse 0.5s';
                    }
                }
            });
        }

        function atualizarPalpites(palpites) {
            palpites.forEach(p => {
                const element = document.querySelector(`.palpite-card[data-id="${p.palpiteiro_id}"]`);
                if (element) {
                    const placarEl = element.querySelector('.palpite-placar');
                    const novoPlacar = `${p.gols_mandante} x ${p.gols_visitante}`;
                    if (placarEl && placarEl.textContent.trim() !== novoPlacar) {
                        placarEl.textContent = novoPlacar;
                        placarEl.style.animation = 'pulse 0.5s';
                    }
                }
            });
        }

        // Atualizar a cada 10 segundos (ao invés de 5)
        setInterval(atualizarDados, 10000);

        // Primeira atualização após 10s
        setTimeout(atualizarDados, 10000);
    </script>
</head>
<body>
    <div class="container">
        <h1>⚽ PALPITES AO VIVO</h1>

        <div class="grid">
            <!-- RANKING -->
            <div class="ranking">
                <h2>🏆 Ranking</h2>
                <?php
                $pos = 1;
                foreach ($ranking as $r):
                    $isTop3 = $pos <= 3;
                ?>
                    <div class="ranking-item <?= $isTop3 ? 'top3' : '' ?>" data-id="<?= $r['palpiteiro_id'] ?>">
                        <span class="ranking-pos"><?= $pos++ ?>º</span>
                        <span class="ranking-nome"><?= htmlspecialchars($r['palpiteiro_nome']) ?></span>
                        <span class="ranking-pontos"><?= $r['total_pontos'] ?? 0 ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- JOGO ATIVO -->
            <div class="jogo-section">
                <?php if ($jogo): ?>
                    <div class="jogo-header">
                        <h2><?= htmlspecialchars($jogo['campeonato']) ?></h2>
                        <?php if ($jogo['rodada']): ?>
                            <p><?= htmlspecialchars($jogo['rodada']) ?></p>
                        <?php endif; ?>
                        <p><?= date('d/m/Y', strtotime($jogo['data_jogo'])) ?></p>
                    </div>

                    <div class="jogo-vs">
                        <div class="jogo-time">
                            <?php if ($jogo['time_mandante_escudo']): ?>
                                <img src="<?= htmlspecialchars($jogo['time_mandante_escudo']) ?>" alt="<?= htmlspecialchars($jogo['time_mandante_nome']) ?>">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($jogo['time_mandante_sigla']) ?></h3>
                        </div>

                        <div class="vs">VS</div>

                        <div class="jogo-time">
                            <?php if ($jogo['time_visitante_escudo']): ?>
                                <img src="<?= htmlspecialchars($jogo['time_visitante_escudo']) ?>" alt="<?= htmlspecialchars($jogo['time_visitante_nome']) ?>">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($jogo['time_visitante_sigla']) ?></h3>
                        </div>
                    </div>

                    <?php if (!empty($palpites)): ?>
                        <h3 style="text-align: center; margin: 30px 0 20px 0; font-size: 24px;">
                            📝 Palpites dos Apresentadores
                        </h3>
                        <div class="palpites-grid">
                            <?php foreach ($palpites as $p): ?>
                                <div class="palpite-card" data-id="<?= $p['palpiteiro_id'] ?>">
                                    <div class="palpite-nome">
                                        <?= htmlspecialchars($p['palpiteiro_nome']) ?>
                                    </div>
                                    <div class="palpite-placar">
                                        <?= $p['gols_mandante'] ?> x <?= $p['gols_visitante'] ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            Aguardando palpites...
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        Nenhum jogo ativo no momento
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
