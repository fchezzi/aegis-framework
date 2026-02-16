<?php
// Pegar usuário logado (admin ou member)
$user = Auth::user() ?? MemberAuth::member() ?? null;

// Verificar se foi submetido o formulário
$leadSuccess = isset($_GET['lead']) && $_GET['lead'] === 'success';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>

<html lang="pt-br">

  <head>

    <!-- include - gtm-head -->
    <?php Core::requireInclude('frontend/includes/_gtm-head.php', true); ?>

    <!-- include - head -->
    <?php Core::requireInclude('frontend/includes/_head.php', true); ?>

    <!-- keywords and description -->
    <meta name="keywords" content="<?= htmlspecialchars($artigo['titulo']) ?>, artigos científicos, cirurgia da coluna">
    <meta name="description" content="<?= htmlspecialchars(substr($artigo['introducao'], 0, 160)) ?>">

    <title><?= htmlspecialchars($artigo['titulo']) ?> - Instituto Atualli</title>

	</head>

	<body>

    <!-- include - gtm-body -->
    <?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>

    <!-- include - whatsapp -->
		<?php $mywhatsdivid = "whats-div-home";$mywhatsid = "whats-home"; ?>
		<div class="m-whatsapp" id="<?php echo $mywhatsdivid;?>" onclick="window.open('https://api.whatsapp.com/send?phone=5511996546105&text=Ol%C3%A1!%20Estava%20navegando%20no%20site%20do%20Instituto%20Atualli%20e%20gostaria%20de%20mais%20informa%C3%A7%C3%B5es', '_blank')"><span class="icon-whatsapp" id="<?php echo $mywhatsid;?>"></span></div>

    <!-- include - header -->
    <?php Core::requireInclude('frontend/includes/_dash-header.php', true); ?>




    <!-- artigo section:begin -->
		<div class="blog-section padding-tb">
			<div class="container"><br><br><br><br><br><br>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('/artigos') ?>">Artigos</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($artigo['titulo']) ?></li>
          </ol>
        </nav>

        <div class="row">
          <!-- Conteúdo Principal -->
          <div class="col-lg-8">

            <!-- Imagem Destaque -->
            <?php if ($artigo['imagem']): ?>
            <div class="mb-4">
              <img src="<?= url('/storage/uploads/' . $artigo['imagem']) ?>" alt="<?= htmlspecialchars($artigo['titulo']) ?>" class="img-fluid rounded" style="width: 100%; max-height: 400px; object-fit: cover;">
            </div>
            <?php endif; ?>

            <!-- Título e Meta -->
            <h1 class="m-artigo__title"><?= htmlspecialchars($artigo['titulo']) ?></h1>

            <div class="meta-post mb-4" style="color: #666; font-size: 14px;">
              <span><i class="icofont-user-alt-7"></i> <strong><?= htmlspecialchars($artigo['autor']) ?></strong></span> |
              <span><i class="icofont-calendar"></i> <?= date('d/m/Y', strtotime($artigo['data_artigo'])) ?></span> |
              <span><i class="icofont-eye"></i> <?= $artigo['views'] ?> visualizações</span>
            </div>

            <hr>

            <!-- Introdução -->
            <div class="mb-5" style="font-size: 16px; line-height: 1.8; text-align: justify;">
              <?= nl2br(htmlspecialchars($artigo['introducao'])) ?>
            </div>

            <!-- Link Externo (se houver) -->
            <?php if (!empty($artigo['link_externo'])): ?>
            <div class="mb-5">
              <a href="<?= htmlspecialchars($artigo['link_externo']) ?>" target="_blank" class="btn btn-outline-primary btn-lg w-100">
                <i class="icofont-external-link"></i> Ver Artigo no Site Original
              </a>
            </div>
            <?php endif; ?>

          </div>

          <!-- Sidebar com Formulário -->
          <div class="col-lg-4">

            <!-- Informações Adicionais -->
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="m-artigo__info-title">Sobre este artigo</h5>
                <ul style="font-size: 14px; padding-left: 20px;">
                  <li>Publicado em <?= date('d/m/Y', strtotime($artigo['data_artigo'])) ?></li>
                  <li>Autor: <?= htmlspecialchars($artigo['autor']) ?></li>
                  <li><?= $artigo['views'] ?> visualizações</li>
                </ul>
              </div>
            </div>

            <div class="card shadow-sm mt-3" style="position: sticky; top: 100px;">
              <div class="card-body">

                <?php if ($leadSuccess): ?>
                  <!-- Mensagem de Sucesso -->
                  <div class="alert alert-success">
                    <h5><i class="icofont-check-circled"></i> Solicitação Enviada com Sucesso!</h5>
                    <?php if (!empty($artigo['arquivo_pdf'])): ?>
                      <p><strong>O artigo foi enviado para o seu email!</strong></p>
                      <p>Verifique sua caixa de entrada (e também a pasta de spam/lixo eletrônico).</p>
                      <p style="margin-top: 15px; font-size: 13px; color: #666;">
                        <i class="icofont-info-circle"></i> O PDF estará anexado no email. Caso não receba em alguns minutos, entre em contato conosco.
                      </p>
                    <?php else: ?>
                      <p>Em breve você receberá mais informações no seu email.</p>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <!-- Formulário de Solicitação -->
                  <h4 class="m-artigo__form-title">Solicitar Artigo Completo</h4>
                  <p style="font-size: 14px; color: #666;">Preencha o formulário abaixo para receber acesso ao artigo completo:</p>

                  <?php if ($error): ?>
                    <div class="alert alert-danger"><?= nl2br(htmlspecialchars($error)) ?></div>
                  <?php endif; ?>

                  <form method="POST" action="<?= url('/artigos/' . $artigo['slug'] . '/solicitar') ?>">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                    <div class="form-group mb-3">
                      <label for="nome"><strong>Nome Completo *</strong></label>
                      <input type="text" class="form-control" id="nome" name="nome" required placeholder="Seu nome completo">
                    </div>

                    <div class="form-group mb-3">
                      <label for="email"><strong>Email *</strong></label>
                      <input type="email" class="form-control" id="email" name="email" required placeholder="seu@email.com">
                    </div>

                    <div class="form-group mb-3">
                      <label for="whatsapp"><strong>WhatsApp *</strong></label>
                      <input type="tel" class="form-control" id="whatsapp" name="whatsapp" required placeholder="(11) 99999-9999">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block w-100">
                      <i class="icofont-download"></i> Solicitar Artigo
                    </button>
                  </form>
                <?php endif; ?>

              </div>
            </div>

          </div>
        </div>

			</div>
		</div>
		<!-- artigo section:end -->




    <!-- include - footer -->
    <?php Core::requireInclude('frontend/includes/_footer.php', true); ?>

		<script src="<?= url('/assets/js/jquery.js') ?>"></script>
		<script src="<?= url('/assets/js/bootstrap.min.js') ?>"></script>
		<script src="<?= url('/assets/js/swiper.min.js') ?>"></script>
		<script src="<?= url('/assets/js/progress.js') ?>"></script>
		<script src="<?= url('/assets/js/lightcase.js') ?>"></script>
		<script src="<?= url('/assets/js/counter-up.js') ?>"></script>
		<script src="<?= url('/assets/js/isotope.pkgd.js') ?>"></script>
		<script src="<?= url('/assets/js/functions.js') ?>"></script>

    <!-- Máscara para WhatsApp -->
    <script>
    document.getElementById('whatsapp').addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 11) value = value.substr(0, 11);

      if (value.length > 6) {
        value = '(' + value.substr(0, 2) + ') ' + value.substr(2, 5) + '-' + value.substr(7);
      } else if (value.length > 2) {
        value = '(' + value.substr(0, 2) + ') ' + value.substr(2);
      }

      e.target.value = value;
    });
    </script>

	</body>
</html>
