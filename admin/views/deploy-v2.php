<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../includes/_admin-head.php';
	?>
	<title>Deploy - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php
  $user = Auth::user();
  require_once __DIR__ . '/../includes/header.php';
  ?>

  <main class="m-pagebase">

    <div class="m-pagebase__header">
      <h1>Deploy</h1>
      <a href="<?= url('/admin') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
        <i data-lucide="arrow-left"></i> Voltar
      </a>
    </div>

    <?php if ($message): ?>
      <div class="alert alert--<?= $messageType ?>">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <!-- AVISOS -->
    <div class="deploy-info-cards">
      <div class="deploy-info-card deploy-info-card--warning">
        <div class="deploy-info-card__header">
          <i data-lucide="alert-triangle"></i>
          <h3>Atenção</h3>
        </div>
        <ul class="deploy-info-card__list">
          <li><strong>Com banco:</strong> servidor novo ou sobrescrever completo</li>
          <li><strong>Sem banco:</strong> atualizar apenas código</li>
          <li><strong>Backup:</strong> sempre faça antes de importar</li>
        </ul>
      </div>

      <div class="deploy-info-card deploy-info-card--info">
        <div class="deploy-info-card__header">
          <i data-lucide="package"></i>
          <h3>O que será incluído</h3>
        </div>
        <ul class="deploy-info-card__list">
          <li><i data-lucide="check"></i> Código completo do framework</li>
          <li><i data-lucide="check"></i> Estrutura de pastas</li>
          <li><i data-lucide="check"></i> Arquivo de instruções</li>
          <li><i data-lucide="database"></i> Banco de dados (opcional)</li>
          <li><i data-lucide="image"></i> Uploads/imagens (opcional)</li>
        </ul>
      </div>
    </div>

    <!-- GERAR PACOTE -->
    <div class="m-pagebase__card deploy-generator">
      <div class="deploy-generator__header">
        <div class="deploy-generator__icon">
          <i data-lucide="rocket"></i>
        </div>
        <div>
          <h2>Gerar Pacote de Deploy</h2>          
        </div>
      </div>

      <form method="post" class="deploy-form">
        <div class="deploy-form__row">
          <div class="deploy-form__field">
            <label class="deploy-form__label">
              <i data-lucide="server"></i>
              Ambiente de destino
            </label>
            <div class="deploy-select-wrapper">
              <i data-lucide="globe" class="deploy-select-icon"></i>
              <select name="ambiente" class="deploy-form__select" required>
                <option value="producao">Produção</option>
                <option value="homologacao">Homologação</option>
                <option value="teste">Teste</option>
              </select>
            </div>
          </div>
        </div>

        <div class="deploy-form__options">
          <label class="deploy-option">
            <div class="deploy-option__checkbox">
              <input type="checkbox" name="incluir_banco" value="1" checked>
              <span class="deploy-option__checkmark"></span>
            </div>
            <div class="deploy-option__content">
              <div class="deploy-option__icon">
                <i data-lucide="database"></i>
              </div>
              <div class="deploy-option__text">
                <strong>Incluir Banco de Dados</strong>
                <span>Exporta dump completo do MySQL</span>
              </div>
            </div>
          </label>

          <label class="deploy-option">
            <div class="deploy-option__checkbox">
              <input type="checkbox" name="incluir_uploads" value="1">
              <span class="deploy-option__checkmark"></span>
            </div>
            <div class="deploy-option__content">
              <div class="deploy-option__icon">
                <i data-lucide="image"></i>
              </div>
              <div class="deploy-option__text">
                <strong>Incluir Uploads</strong>
                <span>Imagens e arquivos do storage/uploads</span>
              </div>
            </div>
          </label>
        </div>

        <div class="deploy-form__actions">
          <button type="submit" name="action" value="generate_package_v2" class="deploy-form__submit">
            <i data-lucide="rocket"></i>
            <span>Gerar Pacote de Deploy</span>
          </button>
        </div>
      </form>
    </div>

    <!-- PACOTES GERADOS -->
    <?php if (!empty($packagesV2)): ?>
    <div class="m-pagebase__card">
      <div class="deploy-packages__header">
        <div class="deploy-packages__title">
          <i data-lucide="archive"></i>
          <h2>Pacotes Gerados</h2>
        </div>
        <span class="deploy-packages__count"><?= count($packagesV2) ?> pacote(s)</span>
      </div>

      <div class="deploy-packages">
        <?php foreach ($packagesV2 as $package): ?>
          <div class="deploy-package">
            <div class="deploy-package__info">
              <div class="deploy-package__name"><?= htmlspecialchars($package['name']) ?></div>
              <div class="deploy-package__meta">
                <span><i data-lucide="hard-drive"></i> <?= round($package['size'] / 1024 / 1024, 2) ?> MB</span>
                <span><i data-lucide="calendar"></i> <?= htmlspecialchars($package['date']) ?></span>
              </div>
            </div>
            <a href="<?= url('/deploys/' . $package['name']) ?>" download class="deploy-package__download">
              <i data-lucide="download"></i>
              Download
            </a>
          </div>
        <?php endforeach; ?>
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
