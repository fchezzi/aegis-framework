<?php
/**
 * Exibição de Ranking - Tela para OBS
 * Mostra ranking geral dos palpiteiros com pontuação
 */

// Habilitar compressão de saída
if (!ob_get_level()) ob_start('ob_gzhandler');

$db = DB::connect();

// CACHE de 60 segundos para ranking (atualiza menos frequentemente)
$cache_key = 'exibicao_ranking_data';
$cached_data = SimpleCache::get($cache_key);

if ($cached_data !== null) {
    extract($cached_data);
} else {
    // Buscar ranking completo - ORDENADO: 1º pontos, 2º exatos
    $ranking = $db->query("
        SELECT * FROM vw_ranking_palpiteiros
        ORDER BY total_pontos DESC, placares_exatos DESC
    ");

    // Buscar fotos dos palpiteiros
    $palpiteiros_raw = $db->query("SELECT id, nome, foto_url FROM tbl_palpiteiros");

    // Indexar palpiteiros por ID com estrutura nested
    $palpiteiros_index = [];
    foreach ($palpiteiros_raw as $p) {
        $palpiteiros_index[$p['id']] = [
            'nome' => $p['nome'],
            'foto_url' => $p['foto_url']
        ];
    }

    // Salvar no cache por 60 segundos
    SimpleCache::set($cache_key, compact('ranking', 'palpiteiros_index'), 60);
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

    <title>Damas em Campo - Ranking</title>

    <style>
      /* Previne layout shift enquanto carrega */
      img {
        image-rendering: crisp-edges;
      }
    </style>

  </head>

  <body class="m-damas__ranking">

    <!-- Navegação entre telas -->
    <div class="m-damas__nav">
      <a href="<?= url('/palpites/exibicao-palpites') ?>" class="m-damas__nav-btn" title="Palpites">
        <i data-lucide="tv"></i>
      </a>
      <a href="<?= url('/palpites/exibicao-resultados') ?>" class="m-damas__nav-btn" title="Resultados">
        <i data-lucide="bar-chart-2"></i>
      </a>
      <a href="<?= url('/palpites/exibicao-ranking') ?>" class="m-damas__nav-btn m-damas__nav-btn--active" title="Ranking">
        <i data-lucide="trophy"></i>
      </a>
    </div>

    <main>

      <?php if (!empty($ranking)): ?>
        <div class="m-damas__ranking--table">

          <!-- Header da tabela -->
          <div class="m-damas__ranking--row m-damas__ranking--row--header">
            <div class="m-damas__ranking--player">
              <span class="m-damas__ranking--header-text">Palpiteira</span>
            </div>
            <div class="m-damas__ranking--points">
              <span class="m-damas__ranking--header-text">Pontos</span>
            </div>
            <div class="m-damas__ranking--stats">
              <div class="m-damas__ranking--stat">
                <span class="m-damas__ranking--header-text">Exatos</span>
              </div>
              <div class="m-damas__ranking--stat">
                <span class="m-damas__ranking--header-text">Certos</span>
              </div>
              <div class="m-damas__ranking--stat">
                <span class="m-damas__ranking--header-text">Erros</span>
              </div>
            </div>
          </div>

          <!-- Linhas do ranking -->
          <?php foreach ($ranking as $index => $palpiteira): ?>
            <?php
              $foto_url = $palpiteiros_index[$palpiteira['palpiteiro_id']]['foto_url'] ?? null;
            ?>
            <div class="m-damas__ranking--row">

              <!-- Player -->
              <div class="m-damas__ranking--player">
                <?php if ($foto_url): ?>
                  <img src="<?= Upload::url($foto_url) ?>" alt="<?= e($palpiteira['palpiteiro_nome']) ?>">
                <?php endif; ?>
                <h3><?= e(strtolower($palpiteira['palpiteiro_nome'])) ?></h3>
              </div>

              <!-- Pontos -->
              <div class="m-damas__ranking--points">
                <?= e($palpiteira['total_pontos']) ?>
              </div>

              <!-- Estatísticas -->
              <div class="m-damas__ranking--stats">
                <div class="m-damas__ranking--stat">
                  <?= e($palpiteira['placares_exatos']) ?>
                </div>
                <div class="m-damas__ranking--stat">
                  <?= e($palpiteira['resultados_certos']) ?>
                </div>
                <div class="m-damas__ranking--stat">
                  <?= e($palpiteira['erros']) ?>
                </div>
              </div>

            </div>
          <?php endforeach; ?>

        </div>
      <?php else: ?>
        <div style="color: white; text-align: center; padding: 50px; font-size: 24px;">
          Nenhum dado de ranking disponível ainda.
        </div>
      <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      lucide.createIcons();
    </script>
  </body>

</html>
