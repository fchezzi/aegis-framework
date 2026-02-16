<?php
// Pegar usuário logado (admin ou member)
$user = Auth::user() ?? MemberAuth::member() ?? null;
?>

<!DOCTYPE html>

<html lang="pt-br">

  <head>

    <!-- include - gtm-head -->
    <?php Core::requireInclude('frontend/includes/_gtm-head.php', true); ?>

    <!-- include - head -->
    <?php Core::requireInclude('frontend/includes/_head.php', true); ?>

    <!-- Estilos da busca (temporário até CodeKit compilar) -->
    <style>
      .m-artigos-search{max-width:600px;margin:30px auto 40px;padding:0 15px}
      .m-artigos-search__wrapper{position:relative;display:flex;align-items:center}
      .m-artigos-search__input{width:100%;padding:14px 50px 14px 20px;font-size:16px;border:2px solid #e9ecef;border-radius:50px;outline:none;transition:all .3s ease;background:#fff}
      .m-artigos-search__input:focus{border-color:#2d808c;box-shadow:0 0 0 3px rgba(45,128,140,.1)}
      .m-artigos-search__input::placeholder{color:#adb5bd}
      .m-artigos-search__icon{position:absolute;right:18px;color:#adb5bd;pointer-events:none;font-size:20px}
      .m-artigos-search__clear{position:absolute;right:50px;background:#dee2e6;border:none;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .3s ease;color:#fff;font-size:14px;padding:0}
      .m-artigos-search__clear:hover{background:#adb5bd;transform:scale(1.1)}
      .m-artigos-search__filters{display:flex;align-items:center;justify-content:center;gap:10px;margin-top:20px;flex-wrap:wrap}
      .m-artigos-search__filters-label{font-size:14px;font-weight:600;color:#495057}
      .m-artigos-search__filter-btn{padding:8px 20px;font-size:14px;font-weight:500;border:2px solid #e9ecef;border-radius:25px;background:#fff;color:#495057;cursor:pointer;transition:all .3s ease}
      .m-artigos-search__filter-btn:hover{border-color:#2d808c;color:#2d808c;transform:translateY(-2px)}
      .m-artigos-search__filter-btn--active{background:#2d808c;border-color:#2d808c;color:#fff}
      .m-artigos-search__filter-btn--active:hover{background:#236d77;border-color:#236d77}
      .m-artigos-search__status{text-align:center;margin-top:12px;font-size:14px;min-height:20px;font-weight:500}
      .m-artigos-search__status--loading{color:#2d808c}
      .m-artigos-search__status--success{color:#28a745}
      .m-artigos-search__status--error{color:#dc3545}
      @media(max-width:768px){.m-artigos-search{max-width:100%;margin:20px auto 30px}.m-artigos-search__input{font-size:14px;padding:12px 50px 12px 16px}.m-artigos-search__filters{gap:8px}.m-artigos-search__filter-btn{padding:6px 16px;font-size:13px}}
    </style>

    <!-- keywords and description -->
    <meta name="keywords" content="Instituto Atualli, artigos científicos, coluna, cirurgia da coluna">
    <meta name="description" content="Artigos científicos sobre cirurgia da coluna vertebral">

    <title>Artigos Científicos - Instituto Atualli</title>

	</head>

	<body>

    <!-- include - gtm-body -->
    <?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>

    <!-- include - whatsapp -->
		<?php $mywhatsdivid = "whats-div-home";$mywhatsid = "whats-home"; ?>
		<div class="m-whatsapp" id="<?php echo $mywhatsdivid;?>" onclick="window.open('https://api.whatsapp.com/send?phone=5511996546105&text=Ol%C3%A1!%20Estava%20navegando%20no%20site%20do%20Instituto%20Atualli%20e%20gostaria%20de%20mais%20informa%C3%A7%C3%B5es', '_blank')"><span class="icon-whatsapp" id="<?php echo $mywhatsid;?>"></span></div>

    <!-- include - header -->
    <?php Core::requireInclude('frontend/includes/_dash-header.php', true); ?>




    <!-- artigos section:begin -->
		<div class="blog-section padding-tb section-bg">
			<div class="container"><br><br><br><br><br><br>
				<div class="section-header text-center">
					<span class="subtitle">publicações científicas</span>
					<h2 class="title">Artigos Científicos</h2>

					<!-- Barra de Busca -->
					<div class="m-artigos-search">
						<div class="m-artigos-search__wrapper">
							<input
								type="text"
								id="artigoSearchInput"
								class="m-artigos-search__input"
								placeholder="Buscar por título, autor ou tema..."
								autocomplete="off"
							>
							<button type="button" id="clearSearchBtn" class="m-artigos-search__clear" style="display: none;">
								<i class="icofont-close"></i>
							</button>
							<div class="m-artigos-search__icon">
								<i class="icofont-search-1"></i>
							</div>
						</div>

						<!-- Filtros por ano -->
						<div class="m-artigos-search__filters">
							<span class="m-artigos-search__filters-label">Filtrar por ano:</span>
							<button type="button" class="m-artigos-search__filter-btn m-artigos-search__filter-btn--active" data-year="">
								Todos
							</button>
							<button type="button" class="m-artigos-search__filter-btn" data-year="2025">
								2025
							</button>
							<button type="button" class="m-artigos-search__filter-btn" data-year="2026">
								2026
							</button>
						</div>

						<div id="searchStatus" class="m-artigos-search__status"></div>
					</div>
				</div>
				<div class="section-wrapper">
					<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 justify-content-center g-4" id="artigosContainer">



 				<?php if (empty($artigos)): ?>
            <p>Nenhum artigo publicado.</p>
        		<?php else: ?>

      <?php foreach ($artigos as $artigo): ?>

								<div class="col">
									<div class="post-item">
										<div class="post-inner">


											<style>
												.post-thum__a{
													width: 100%;
													aspect-ratio: 16/9;
												}
												.post-thumb__image{
													width: 100%;
													height: 100%;
													overflow: hidden;
													object-fit: cover;
												}
												.post-thum__h4{
													font-size: 20px;
													height:48px;
													overflow: hidden;
												}
												.post-thum__p{
													height: 70px;
													overflow: hidden;
												}
												.meta-post__autor {
													color: #666;
													font-size: 14px;
													margin-top: 5px;
												}
											</style>

											<div class="post-thumb">
												<a href="<?= url('/artigos/' . $artigo['slug']) ?>" class="post-thum__a">
													<img src="<?= url('/storage/uploads/' . $artigo['imagem']) ?>" alt="<?= htmlspecialchars($artigo['titulo']) ?>" class="post-thumb__image">
												</a>
											</div>
											<div class="post-content">
												<a href="<?= url('/artigos/' . $artigo['slug']) ?>">
													<h4 class="post-thum__h4"><?= htmlspecialchars($artigo['titulo']) ?></h4>
												</a>
												<div class="meta-post">
													<ul class="lab-ul">
														<li><i class="icofont-calendar"></i> <?= date('d/m/Y', strtotime($artigo['data_artigo'])) ?></li>
													</ul>
													<p class="meta-post__autor"><i class="icofont-user-alt-7"></i> <?= htmlspecialchars($artigo['autor']) ?></p>
												</div>
											</div>
											<div class="post-footer">
												<div class="pf-left">
													<a href="<?= url('/artigos/' . $artigo['slug']) ?>" class="lab-btn-text">Prévia do Artigo <i class="icofont-external-link"></i></a>
												</div>
											</div>
										</div>
									</div>
								</div>

     					<?php endforeach; ?>
        <?php endif; ?>


					</div>

          <!-- Paginação -->
          <?php if ($totalPages > 1): ?>
          <div class="text-center mt-5">
            <nav>
              <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                  <li class="page-item">
                    <a class="page-link" href="<?= url('/artigos?page=' . ($page - 1)) ?>">Anterior</a>
                  </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('/artigos?page=' . $i) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                  <li class="page-item">
                    <a class="page-link" href="<?= url('/artigos?page=' . ($page + 1)) ?>">Próxima</a>
                  </li>
                <?php endif; ?>
              </ul>
            </nav>
          </div>
          <?php endif; ?>

				</div>
			</div>
		</div>
		<!-- artigos section:end -->









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

		<!-- Busca AJAX de Artigos -->
		<script>
		(function() {
			const searchInput = document.getElementById('artigoSearchInput');
			const clearBtn = document.getElementById('clearSearchBtn');
			const searchStatus = document.getElementById('searchStatus');
			const container = document.getElementById('artigosContainer');
			const filterBtns = document.querySelectorAll('.m-artigos-search__filter-btn');
			const baseUrl = '<?= url('/') ?>';
			const csrfToken = '<?= Security::generateCSRF() ?>';

			let debounceTimer;
			let isSearching = false;
			let originalContent = container.innerHTML; // Backup do conteúdo original
			let activeYear = null; // null = todos os anos (padrão)

			// Função de debounce (aguarda 300ms após parar de digitar)
			function debounce(func, delay) {
				return function() {
					clearTimeout(debounceTimer);
					debounceTimer = setTimeout(func, delay);
				};
			}

			// Função para buscar artigos via AJAX
			function buscarArtigos(query) {
				// Se não há query e não há filtro de ano, restaurar original
				if ((query.length < 2 || !query) && activeYear === null) {
					restaurarConteudoOriginal();
					return;
				}

				isSearching = true;
				searchStatus.textContent = 'Buscando...';
				searchStatus.className = 'm-artigos-search__status m-artigos-search__status--loading';

				// Montar body da requisição
				let body = 'csrf_token=' + encodeURIComponent(csrfToken);
				if (query && query.length >= 2) {
					body += '&query=' + encodeURIComponent(query);
				}
				if (activeYear !== null) {
					body += '&year=' + encodeURIComponent(activeYear);
				}

				// Requisição AJAX
				fetch(baseUrl + '/artigos/buscar', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: body
				})
				.then(response => response.json())
				.then(data => {
					isSearching = false;

					if (data.success) {
						renderResultados(data.artigos, query);

						// Mensagem de status
						let statusMsg = data.count + ' resultado(s) encontrado(s)';
						if (data.year) {
							statusMsg += ' em ' + data.year;
						}
						searchStatus.textContent = statusMsg;
						searchStatus.className = 'm-artigos-search__status m-artigos-search__status--success';
					} else {
						searchStatus.textContent = data.message || 'Erro na busca';
						searchStatus.className = 'm-artigos-search__status m-artigos-search__status--error';
					}
				})
				.catch(error => {
					isSearching = false;
					console.error('Erro na busca:', error);
					searchStatus.textContent = 'Erro ao buscar artigos. Tente novamente.';
					searchStatus.className = 'm-artigos-search__status m-artigos-search__status--error';
				});
			}

			// Renderizar resultados
			function renderResultados(artigos, query) {
				if (artigos.length === 0) {
					container.innerHTML = '<div class="col-12"><div class="alert alert-info text-center"><p>😕 Nenhum artigo encontrado para "<strong>' + escapeHtml(query) + '</strong>"</p><p>Tente buscar por outros termos.</p></div></div>';
					return;
				}

				let html = '';
				artigos.forEach(function(artigo) {
					html += renderCard(artigo);
				});
				container.innerHTML = html;
			}

			// Renderizar card individual
			function renderCard(artigo) {
				const dataFormatada = formatarData(artigo.data_artigo);
				const imagemUrl = baseUrl + '/storage/uploads/' + artigo.imagem;
				const artigoUrl = baseUrl + '/artigos/' + artigo.slug;

				return `
					<div class="col">
						<div class="post-item">
							<div class="post-inner">
								<div class="post-thumb">
									<a href="${artigoUrl}" class="post-thum__a">
										<img src="${imagemUrl}" alt="${escapeHtml(artigo.titulo)}" class="post-thumb__image">
									</a>
								</div>
								<div class="post-content">
									<a href="${artigoUrl}">
										<h4 class="post-thum__h4">${escapeHtml(artigo.titulo)}</h4>
									</a>
									<div class="meta-post">
										<ul class="lab-ul">
											<li><i class="icofont-calendar"></i> ${dataFormatada}</li>
										</ul>
										<p class="meta-post__autor"><i class="icofont-user-alt-7"></i> ${escapeHtml(artigo.autor)}</p>
									</div>
								</div>
								<div class="post-footer">
									<div class="pf-left">
										<a href="${artigoUrl}" class="lab-btn-text">Prévia do Artigo <i class="icofont-external-link"></i></a>
									</div>
								</div>
							</div>
						</div>
					</div>
				`;
			}

			// Restaurar conteúdo original
			function restaurarConteudoOriginal() {
				container.innerHTML = originalContent;
				searchStatus.textContent = '';
				searchStatus.className = 'm-artigos-search__status';
			}

			// Formatar data (YYYY-MM-DD -> DD/MM/YYYY)
			function formatarData(dataString) {
				const data = new Date(dataString);
				const dia = String(data.getDate()).padStart(2, '0');
				const mes = String(data.getMonth() + 1).padStart(2, '0');
				const ano = data.getFullYear();
				return dia + '/' + mes + '/' + ano;
			}

			// Escapar HTML (prevenir XSS)
			function escapeHtml(text) {
				const div = document.createElement('div');
				div.textContent = text;
				return div.innerHTML;
			}

			// Event listeners
			searchInput.addEventListener('input', debounce(function() {
				const query = searchInput.value.trim();

				// Mostrar/esconder botão limpar
				if (query.length > 0) {
					clearBtn.style.display = 'block';
				} else {
					clearBtn.style.display = 'none';
				}

				buscarArtigos(query);
			}, 300));

			// Botão limpar
			clearBtn.addEventListener('click', function() {
				searchInput.value = '';
				clearBtn.style.display = 'none';
				restaurarConteudoOriginal();
			});

			// Enter key
			searchInput.addEventListener('keydown', function(e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					const query = searchInput.value.trim();
					buscarArtigos(query);
				}
			});

			// Botões de filtro por ano
			filterBtns.forEach(function(btn) {
				btn.addEventListener('click', function() {
					// Remover classe active de todos
					filterBtns.forEach(function(b) {
						b.classList.remove('m-artigos-search__filter-btn--active');
					});

					// Adicionar active no clicado
					this.classList.add('m-artigos-search__filter-btn--active');

					// Pegar ano (string vazia = null = todos)
					const year = this.getAttribute('data-year');
					activeYear = year === '' ? null : parseInt(year);

					// Buscar com novo filtro
					const query = searchInput.value.trim();
					buscarArtigos(query);
				});
			});
		})();
		</script>

	</body>
</html>
