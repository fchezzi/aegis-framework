<?php
/**
 * Exibição de Resultados - Tela para OBS
 * Mostra resultado real + palpites + quem acertou de UM jogo finalizado
 */

// Habilitar compressão de saída
if (!ob_get_level()) ob_start('ob_gzhandler');

$db = DB::connect();

// Define qual jogo está sendo exibido
$jogo_id_selecionado = $_GET['jogo_id'] ?? null;

// CACHE de 30 segundos por jogo
$cache_key = 'exibicao_resultados_' . ($jogo_id_selecionado ?? 'default');
$cached_data = SimpleCache::get($cache_key);

if ($cached_data !== null) {
    extract($cached_data);
} else {
    // Buscar jogos finalizados (que têm resultado e estão marcados para exibir)
    $jogos_finalizados_raw = $db->query("
        SELECT
            j.id,
            j.gols_mandante_real,
            j.gols_visitante_real,
            j.data_jogo,
            j.campeonato,
            j.rodada,
            tm.nome as time_mandante_nome,
            tm.sigla as time_mandante_sigla,
            tm.escudo_url as time_mandante_escudo,
            tv.nome as time_visitante_nome,
            tv.sigla as time_visitante_sigla,
            tv.escudo_url as time_visitante_escudo
        FROM tbl_jogos_palpites j
        LEFT JOIN tbl_times tm ON j.time_mandante_id = tm.id
        LEFT JOIN tbl_times tv ON j.time_visitante_id = tv.id
        WHERE j.exibir_resultado = true
          AND j.gols_mandante_real IS NOT NULL
          AND j.gols_visitante_real IS NOT NULL
        ORDER BY j.data_jogo DESC
    ");

    // Transformar em estrutura nested (compatível com arrumar-resultados.php)
    $jogos_finalizados = [];
    foreach ($jogos_finalizados_raw as $jogo_raw) {
        $jogos_finalizados[] = [
            'id' => $jogo_raw['id'],
            'gols_mandante_real' => $jogo_raw['gols_mandante_real'],
            'gols_visitante_real' => $jogo_raw['gols_visitante_real'],
            'data_jogo' => $jogo_raw['data_jogo'],
            'campeonato' => $jogo_raw['campeonato'],
            'rodada' => $jogo_raw['rodada'],
            'time_mandante' => [
                'nome' => $jogo_raw['time_mandante_nome'],
                'sigla' => $jogo_raw['time_mandante_sigla'],
                'escudo_url' => $jogo_raw['time_mandante_escudo']
            ],
            'time_visitante' => [
                'nome' => $jogo_raw['time_visitante_nome'],
                'sigla' => $jogo_raw['time_visitante_sigla'],
                'escudo_url' => $jogo_raw['time_visitante_escudo']
            ]
        ];
    }

    // Buscar jogo completo
    $jogo_atual = null;
    if ($jogo_id_selecionado) {
        $jogo_id_safe = Security::sanitize($jogo_id_selecionado);
        $jogo_completo_array = $db->query("
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
            WHERE j.id = '$jogo_id_safe'
        ");

        if (!empty($jogo_completo_array)) {
            $jogo_raw = $jogo_completo_array[0];
            $jogo_atual = [
                'id' => $jogo_raw['id'],
                'gols_mandante_real' => $jogo_raw['gols_mandante_real'],
                'gols_visitante_real' => $jogo_raw['gols_visitante_real'],
                'data_jogo' => $jogo_raw['data_jogo'],
                'campeonato' => $jogo_raw['campeonato'],
                'rodada' => $jogo_raw['rodada'],
                'time_mandante' => [
                    'nome' => $jogo_raw['time_mandante_nome'],
                    'sigla' => $jogo_raw['time_mandante_sigla'],
                    'escudo_url' => $jogo_raw['time_mandante_escudo']
                ],
                'time_visitante' => [
                    'nome' => $jogo_raw['time_visitante_nome'],
                    'sigla' => $jogo_raw['time_visitante_sigla'],
                    'escudo_url' => $jogo_raw['time_visitante_escudo']
                ]
            ];
        }
    } else {
        // Se não tem nada selecionado, pega o primeiro da lista
        if (!empty($jogos_finalizados)) {
            $jogo_atual = $jogos_finalizados[0];
        }
    }

    // Buscar TODAS as palpiteiras ativas
    $todas_palpiteiras = $db->query("SELECT * FROM tbl_palpiteiros WHERE ativo = true ORDER BY ordem ASC");

    // Preparar palpites para o jogo atual
    $palpites_dados = [];
    if ($jogo_atual) {
        $jogo_id_safe = Security::sanitize($jogo_atual['id']);
        $palpites_array = $db->query("
            SELECT
                p.*,
                pa.nome as palpiteiro_nome,
                pa.foto_url as palpiteiro_foto
            FROM tbl_palpites p
            JOIN tbl_palpiteiros pa ON p.palpiteiro_id = pa.id
            WHERE p.jogo_id = '$jogo_id_safe'
            ORDER BY pa.ordem ASC
        ");

        // Indexar palpites por palpiteiro_id
        foreach ($palpites_array as $p) {
            $palpites_dados[$p['palpiteiro_id']] = $p;
        }
    }

    // Salvar no cache
    SimpleCache::set($cache_key, compact('jogos_finalizados', 'jogo_atual', 'todas_palpiteiras', 'palpites_dados'), 30);
}

// Preparar palpites com cálculo de acertos (estrutura nested)
$palpites = [];
if ($jogo_atual) {
    // Criar array final com todas as palpiteiras
    foreach ($todas_palpiteiras as $palpiteira) {
        if (isset($palpites_dados[$palpiteira['id']])) {
            $palpite = $palpites_dados[$palpiteira['id']];

            $acertou_placar = false;
            $acertou_resultado = false;

            // Verifica se acertou placar exato (3 pontos)
            if ($palpite['gols_mandante'] == $jogo_atual['gols_mandante_real'] &&
                $palpite['gols_visitante'] == $jogo_atual['gols_visitante_real']) {
                $acertou_placar = true;
            }

            // Verifica se acertou resultado (1 ponto)
            if (!$acertou_placar) {
                $resultado_real = '';
                $resultado_palpite = '';

                if ($jogo_atual['gols_mandante_real'] > $jogo_atual['gols_visitante_real']) {
                    $resultado_real = 'mandante';
                } elseif ($jogo_atual['gols_mandante_real'] < $jogo_atual['gols_visitante_real']) {
                    $resultado_real = 'visitante';
                } else {
                    $resultado_real = 'empate';
                }

                if ($palpite['gols_mandante'] > $palpite['gols_visitante']) {
                    $resultado_palpite = 'mandante';
                } elseif ($palpite['gols_mandante'] < $palpite['gols_visitante']) {
                    $resultado_palpite = 'visitante';
                } else {
                    $resultado_palpite = 'empate';
                }

                if ($resultado_real === $resultado_palpite) {
                    $acertou_resultado = true;
                }
            }

            // Estrutura NESTED (compatível com arrumar-resultados.php)
            $palpites[] = [
                'palpiteiro' => [
                    'nome' => $palpiteira['nome'],
                    'foto_url' => !empty($palpiteira['foto_url']) ? $palpiteira['foto_url'] : ''
                ],
                'gols_mandante' => $palpite['gols_mandante'],
                'gols_visitante' => $palpite['gols_visitante'],
                'acertou_placar' => $acertou_placar,
                'acertou_resultado' => $acertou_resultado
            ];
        } else {
            // Não tem palpite (estrutura nested)
            $palpites[] = [
                'palpiteiro' => [
                    'nome' => $palpiteira['nome'],
                    'foto_url' => !empty($palpiteira['foto_url']) ? $palpiteira['foto_url'] : ''
                ],
                'gols_mandante' => null,
                'gols_visitante' => null,
                'acertou_placar' => false,
                'acertou_resultado' => false
            ];
        }
    }
}

// Helper function url() se não existir
if (!function_exists('url')) {
    function url($path = '') {
        return APP_URL . $path;
    }
}

// Helper function e() se não existir
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
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

    <!-- css files -->
    <link rel="stylesheet" type="text/css"  href="<?= url('/modules/palpites/assets/css/m-palpites.css') ?>" >

    <title>Energia 97</title>

    <style>
      /* Degradê azul quando acerta o placar exato (3 pontos) */
      .m-damas__palpite--palpite.acertou-placar {
        background: linear-gradient(135deg, #0056b3 0%, #007bff 100%) !important;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
      }

      /* Degradê laranja quando acerta o resultado (1 ponto) */
      .m-damas__palpite--palpite.acertou-resultado {
        background: linear-gradient(135deg, #ff6600 0%, #ffa500 100%) !important;
        box-shadow: 0 4px 15px rgba(255, 165, 0, 0.4);
      }
    </style>

  </head>

<body class="m-damas__palpite">
    <!-- Navegação entre telas -->
    <div class="m-damas__nav">
      <a href="<?= url('/palpites/exibicao-palpites') ?>" class="m-damas__nav-btn" title="Palpites">
        <i data-lucide="tv"></i>
      </a>
      <a href="<?= url('/palpites/exibicao-resultados') ?>" class="m-damas__nav-btn m-damas__nav-btn--active" title="Resultados">
        <i data-lucide="bar-chart-2"></i>
      </a>
      <a href="<?= url('/palpites/exibicao-ranking') ?>" class="m-damas__nav-btn" title="Ranking">
        <i data-lucide="trophy"></i>
      </a>
    </div>

    <?php if (!empty($jogos_finalizados)): ?>
    <div class="m-damas__palpite--outrosjogos">
        <?php foreach ($jogos_finalizados as $jogo): ?>
        <a href="?jogo_id=<?= e($jogo['id']) ?>" class="m-damas__palpite--outroscontent" style="text-decoration: none; color: inherit;">
          <div class="m-damas__palpite--outrostime">
            <?php if (!empty($jogo['time_mandante']['escudo_url'])): ?>
            <img src="<?= Upload::url($jogo['time_mandante']['escudo_url']) ?>" alt="<?= e($jogo['time_mandante']['nome']) ?>">
            <?php endif; ?>
          </div>
          <div class="m-damas__palpite--outrosplacar">
            <h2><?= e($jogo['gols_mandante_real']) ?></h2>
            <h3>x</h3>
            <h2><?= e($jogo['gols_visitante_real']) ?></h2>
          </div>
          <div class="m-damas__palpite--outrostime">
            <?php if (!empty($jogo['time_visitante']['escudo_url'])): ?>
            <img src="<?= Upload::url($jogo['time_visitante']['escudo_url']) ?>" alt="<?= e($jogo['time_visitante']['nome']) ?>">
            <?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <main>
      <?php if (!$jogo_atual): ?>
        <div style="color: white; text-align: center; padding: 50px; font-size: 24px;">
          Nenhum jogo selecionado para exibição. Selecione um jogo na lista de jogos.
        </div>
      <?php else: ?>

      <div class="m-damas__palpite--top">
        <div class="m-damas__palpite--time">&nbsp</div>
        <?php foreach ($palpites as $palpite): ?>
        <div class="m-damas__palpite--player">
          <?php if (!empty($palpite['palpiteiro']['foto_url'])): ?>
          <img src="<?= Upload::url($palpite['palpiteiro']['foto_url']) ?>" alt="<?= e($palpite['palpiteiro']['nome']) ?>">
          <?php endif; ?>
          <h5><?= e(strtolower($palpite['palpiteiro']['nome'])) ?></h5>
        </div>
        <?php endforeach; ?>
        <div class="m-damas__palpite--time">&nbsp</div>
      </div>

      <div class="m-damas__palpite--bottom">
        <div class="m-damas__palpite--time">
          &nbsp;
        </div>
        <?php foreach ($palpites as $palpite): ?>
        <div class="m-damas__palpite--palpite <?= $palpite['acertou_placar'] ? 'acertou-placar' : ($palpite['acertou_resultado'] ? 'acertou-resultado' : '') ?>">
          <h2><?= $palpite['gols_mandante'] !== null ? e($palpite['gols_mandante']) : '&nbsp;' ?></h2>
          <h3>X</h3>
          <h2><?= $palpite['gols_visitante'] !== null ? e($palpite['gols_visitante']) : '&nbsp;' ?></h2>
        </div>
        <?php endforeach; ?>
        <div class="m-damas__palpite--time">
          &nbsp;
        </div>
      </div>

      <div class="m-damas__palpite--finalscore">
        <div class="m-damas__palpite--finaltime">
          <?php if (!empty($jogo_atual['time_mandante']['escudo_url'])): ?>
          <img src="<?= Upload::url($jogo_atual['time_mandante']['escudo_url']) ?>" alt="<?= e($jogo_atual['time_mandante']['nome']) ?>">
          <?php endif; ?>
        </div>
        <div class="m-damas__palpite--finalplacar">
          <h2><?= e($jogo_atual['gols_mandante_real']) ?></h2>
          <h3>x</h3>
          <h2><?= e($jogo_atual['gols_visitante_real']) ?></h2>
        </div>
        <div class="m-damas__palpite--finaltime">
          <?php if (!empty($jogo_atual['time_visitante']['escudo_url'])): ?>
          <img src="<?= Upload::url($jogo_atual['time_visitante']['escudo_url']) ?>" alt="<?= e($jogo_atual['time_visitante']['nome']) ?>">
          <?php endif; ?>
        </div>
      </div>

      <?php endif; ?>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      lucide.createIcons();
    </script>
</body>
</html>
