<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Editar Página - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/../../includes/header.php'; ?>

  <main class="m-pagebase">

    <div class="m-pagebase__header">
      <h1>Editar Página</h1>
      <a href="<?= url('/admin/pages') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
        <i data-lucide="arrow-left"></i> Voltar
      </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert--error">
        <?= htmlspecialchars($_SESSION['error']) ?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="m-pagebase__form-container">
      <div class="m-pagebase__info">
        <strong>Slug atual:</strong> <code class="m-pagebase__code">/<?= htmlspecialchars($page['slug']) ?></code><br>
        <strong>Arquivo:</strong> <code class="m-pagebase__code">frontend/pages/<?= htmlspecialchars($page['slug']) ?>.php</code>
      </div>

      <form method="POST" action="<?= url('/admin/pages/' . $page['id']) ?>" class="m-pagebase__form" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

        <div class="m-pagebase__form-group">
          <label for="title" class="m-pagebase__form-label">Título da Página *</label>
          <input
            type="text"
            id="title"
            name="title"
            value="<?= htmlspecialchars($page['title']) ?>"
            required
            class="m-pagebase__form-input"
          >
          <div class="m-pagebase__form-help">Se mudar o título, o slug e arquivo serão renomeados automaticamente.</div>
        </div>

        <div class="m-pagebase__form-group">
          <label for="description" class="m-pagebase__form-label">Descrição (opcional)</label>
          <input
            type="text"
            id="description"
            name="description"
            value="<?= htmlspecialchars($page['description'] ?? '') ?>"
            placeholder="Breve descrição da página"
            class="m-pagebase__form-input"
          >
          <div class="m-pagebase__form-help">Usado para SEO e listagens</div>
        </div>

        <!-- SEO Configuration -->
        <h2 class="m-pagebase__section-title m-pagebase__section-title--spaced">Configuração SEO</h2>

        <div class="m-pagebase__seo-widget">

          <!-- Score SEO -->
          <div class="m-pagebase__seo-score-box">
            <div class="m-pagebase__seo-score-label">Score SEO</div>
            <div class="m-pagebase__seo-score-value" id="seo-score">0</div>
            <div class="m-pagebase__seo-score-grade" id="seo-grade">F</div>
            <div class="m-pagebase__seo-score-desc" id="seo-desc">Crítico! SEO precisa ser configurado.</div>
          </div>

          <!-- Card: Básico -->
          <div class="m-pagebase__seo-card m-pagebase__seo-card--primary">
            <span class="m-pagebase__seo-card-title">🎯 SEO Básico (Obrigatório)</span>

          <div class="m-pagebase__form-group">
            <label for="seo_title" class="m-pagebase__form-label">SEO Title *</label>
            <input
              type="text"
              id="seo_title"
              name="seo_title"
              maxlength="70"
              placeholder=""
              value="<?= htmlspecialchars($page['seo_title'] ?? '') ?>"
              class="m-pagebase__form-input seo-realtime"
              data-seo-field="title"
            >
            <div class="m-pagebase__form-help">
              <span class="seo-counter" data-counter="title">0/70 caracteres</span>
              <span class="seo-status" data-status="title"></span>
            </div>
          </div>

          <div class="m-pagebase__form-group">
            <label for="seo_description" class="m-pagebase__form-label">SEO Description *</label>
            <textarea
              id="seo_description"
              name="seo_description"
              maxlength="160"
              placeholder=""
              class="m-pagebase__form-textarea seo-realtime"
              data-seo-field="description"
              rows="3"
            ><?= htmlspecialchars($page['seo_description'] ?? '') ?></textarea>
            <div class="m-pagebase__form-help">
              <span class="seo-counter" data-counter="description">0/160 caracteres</span>
              <span class="seo-status" data-status="description"></span>
            </div>
          </div>

          <div class="m-pagebase__form-group">
            <label for="seo_robots" class="m-pagebase__form-label">Robots (Indexação)</label>
            <select id="seo_robots" name="seo_robots" class="m-pagebase__form-select">
              <option value="index,follow" <?= ($page['seo_robots'] ?? 'index,follow') === 'index,follow' ? 'selected' : '' ?>>Index, Follow (Padrão - indexar e seguir links)</option>
              <option value="noindex,follow" <?= ($page['seo_robots'] ?? 'index,follow') === 'noindex,follow' ? 'selected' : '' ?>>NoIndex, Follow (Não indexar, mas seguir links)</option>
              <option value="noindex,nofollow" <?= ($page['seo_robots'] ?? 'index,follow') === 'noindex,nofollow' ? 'selected' : '' ?>>NoIndex, NoFollow (Não indexar nem seguir links)</option>
            </select>
            <div class="m-pagebase__form-help">Controla como o Google indexa esta página</div>
          </div>

          <div class="m-pagebase__form-group">
            <label for="seo_canonical_url" class="m-pagebase__form-label">Canonical URL</label>
            <input
              type="url"
              id="seo_canonical_url"
              name="seo_canonical_url"
              placeholder=""
              value="<?= htmlspecialchars($page['seo_canonical_url'] ?? '') ?>"
              class="m-pagebase__form-input"
            >
            <div class="m-pagebase__form-help">URL canônica para evitar conteúdo duplicado. Deixe vazio para usar a URL atual.</div>
          </div>

          </div>

          <!-- Card: Open Graph -->
          <div class="m-pagebase__seo-card">
            <span class="m-pagebase__seo-card-title">📱 Open Graph (Facebook, WhatsApp, LinkedIn)</span>

          <div class="m-pagebase__form-group">
            <label for="seo_og_type" class="m-pagebase__form-label">OG Type</label>
            <select id="seo_og_type" name="seo_og_type" class="m-pagebase__form-select">
              <option value="website" <?= ($page['seo_og_type'] ?? 'website') === 'website' ? 'selected' : '' ?>>Website (Padrão)</option>
              <option value="article" <?= ($page['seo_og_type'] ?? 'website') === 'article' ? 'selected' : '' ?>>Article (Artigo/Blog)</option>
            </select>
          </div>

          <div class="m-pagebase__form-group">
            <label for="seo_og_title" class="m-pagebase__form-label">OG Title</label>
            <input
              type="text"
              id="seo_og_title"
              name="seo_og_title"
              maxlength="95"
              placeholder="Título para compartilhamento em redes sociais (máx. 95 caracteres)"
              value="<?= htmlspecialchars($page['seo_og_title'] ?? '') ?>"
              class="m-pagebase__form-input"
            >
            <div class="m-pagebase__form-help">Deixe vazio para usar o SEO Title</div>
          </div>

          <div class="m-pagebase__form-group">
            <label for="seo_og_description" class="m-pagebase__form-label">OG Description</label>
            <textarea
              id="seo_og_description"
              name="seo_og_description"
              placeholder="Descrição para compartilhamento em redes sociais"
              class="m-pagebase__form-textarea"
              rows="3"
            ><?= htmlspecialchars($page['seo_og_description'] ?? '') ?></textarea>
            <div class="m-pagebase__form-help">Deixe vazio para usar o SEO Description</div>
          </div>

          <div class="m-pagebase__form-group">
            <label for="seo_og_image" class="m-pagebase__form-label">OG Image</label>

            <?php if (!empty($page['seo_og_image'])): ?>
              <div class="m-pagebase__form-file-current">
                <strong>Imagem atual:</strong><br>
                <img src="<?= url($page['seo_og_image']) ?>" alt="OG Image" style="max-width: 300px; margin-top: 10px; border-radius: 4px;">
                <div style="margin-top: 8px; font-size: 13px; color: #666;">
                  <?= htmlspecialchars($page['seo_og_image']) ?>
                </div>
              </div>
            <?php endif; ?>

            <input
              type="file"
              id="seo_og_image"
              name="seo_og_image"
              accept="image/jpeg,image/jpg,image/png,image/webp"
              class="m-pagebase__form-file"
              style="margin-top: 15px;"
            >
            <input type="hidden" id="seo_og_image_current" value="<?= htmlspecialchars($page['seo_og_image'] ?? '') ?>">
            <div class="m-pagebase__form-help">
              <strong>Recomendado:</strong> 1200x630px (proporção 1.9:1)<br>
              <strong>Mínimo:</strong> 600x315px | <strong>Máximo:</strong> 2MB<br>
              <strong>Formatos:</strong> JPG, PNG, WebP<br>
              <?php if (!empty($page['seo_og_image'])): ?>
                <em>Envie uma nova imagem para substituir a atual</em>
              <?php endif; ?>
            </div>
            <div id="og-image-preview" class="m-pagebase__form-preview" style="display:none;">
              <strong>Nova imagem:</strong>
              <img id="og-image-preview-img" class="m-pagebase__form-preview-img" alt="Preview OG Image">
              <div id="og-image-dimensions"></div>
            </div>
          </div>

          </div>

          <!-- Card: Twitter -->
          <div class="m-pagebase__seo-card">
            <span class="m-pagebase__seo-card-title">🐦 Twitter Card (X)</span>

          <div class="m-pagebase__form-group">
            <label for="seo_twitter_card" class="m-pagebase__form-label">Twitter Card Type</label>
            <select id="seo_twitter_card" name="seo_twitter_card" class="m-pagebase__form-select">
              <option value="summary" <?= ($page['seo_twitter_card'] ?? 'summary') === 'summary' ? 'selected' : '' ?>>Summary (Padrão - card pequeno)</option>
              <option value="summary_large_image" <?= ($page['seo_twitter_card'] ?? 'summary') === 'summary_large_image' ? 'selected' : '' ?>>Summary Large Image (Card com imagem grande)</option>
            </select>
          </div>

          <div class="m-pagebase__form-group">
            <label for="seo_twitter_title" class="m-pagebase__form-label">Twitter Title</label>
            <input
              type="text"
              id="seo_twitter_title"
              name="seo_twitter_title"
              maxlength="70"
              placeholder="Título para Twitter/X (máx. 70 caracteres)"
              value="<?= htmlspecialchars($page['seo_twitter_title'] ?? '') ?>"
              class="m-pagebase__form-input"
            >
            <div class="m-pagebase__form-help">Deixe vazio para usar o SEO Title</div>
          </div>

          <div class="m-pagebase__form-group">
            <label for="seo_twitter_description" class="m-pagebase__form-label">Twitter Description</label>
            <textarea
              id="seo_twitter_description"
              name="seo_twitter_description"
              maxlength="200"
              placeholder=""
              class="m-pagebase__form-textarea"
              rows="3"
            ><?= htmlspecialchars($page['seo_twitter_description'] ?? '') ?></textarea>
            <div class="m-pagebase__form-help">Deixe vazio para usar o SEO Description</div>
          </div>

          </div>

        </div>

        <div class="m-pagebase__form-group">
          <label class="m-pagebase__form-checkbox">
            <input type="checkbox" name="ativo" <?= $page['ativo'] ? 'checked' : '' ?>>
            Página ativa
          </label>
          <div class="m-pagebase__form-help">Desmarque para ocultar a página do site</div>
        </div>

        <div class="m-pagebase__form-group">
          <label for="type" class="m-pagebase__form-label">Tipo de Página *</label>
          <select id="type" name="type" required class="m-pagebase__form-select">
            <option value="custom" <?= ($page['type'] ?? 'custom') === 'custom' ? 'selected' : '' ?>>Custom (Específica do projeto)</option>
            <option value="core" <?= ($page['type'] ?? 'custom') === 'core' ? 'selected' : '' ?>>Core (Padrão do AEGIS)</option>
          </select>
          <div class="m-pagebase__form-help"><strong>Core:</strong> páginas essenciais do framework (não podem ser deletadas). <strong>Custom:</strong> páginas específicas deste projeto.</div>
        </div>

        <div class="m-pagebase__form-group">
          <label for="scope" class="m-pagebase__form-label">Scope da Página *</label>
          <select id="scope" name="scope" required class="m-pagebase__form-select">
            <option value="frontend" <?= ($page['scope'] ?? 'frontend') === 'frontend' ? 'selected' : '' ?>>Frontend (Páginas públicas do site)</option>
            <option value="members" <?= ($page['scope'] ?? 'frontend') === 'members' ? 'selected' : '' ?>>Members (Área de membros)</option>
            <option value="admin" <?= ($page['scope'] ?? 'frontend') === 'admin' ? 'selected' : '' ?>>Admin (Páginas administrativas)</option>
          </select>
          <div class="m-pagebase__form-help"><strong>Frontend:</strong> páginas em /frontend/pages/. <strong>Members:</strong> área de membros. <strong>Admin:</strong> páginas em /admin/pages/.</div>
        </div>

        <?php if (Core::membersEnabled()): ?>
        <div class="m-pagebase__form-group">
          <label class="m-pagebase__form-checkbox">
            <input type="checkbox" name="is_public" value="1" <?= ($page['is_public'] ?? 0) ? 'checked' : '' ?>>
            Página Pública (acessível sem login)
          </label>
          <div class="m-pagebase__form-help">Marque para permitir acesso sem autenticação. Desmarcado = exige login e permissões de grupo.</div>
        </div>
        <?php endif; ?>

        <?php if (Core::membersEnabled() && !empty($groups)): ?>
        <div class="m-pagebase__form-group">
          <label class="m-pagebase__form-label">Grupos com Acesso</label>
          <?php foreach ($groups as $group): ?>
            <label class="m-pagebase__form-checkbox">
              <input type="checkbox" name="group_ids[]" value="<?= $group['id'] ?>"
                <?= in_array($group['id'], $pageGroups) ? 'checked' : '' ?>>
              <?= htmlspecialchars($group['name']) ?>
            </label>
          <?php endforeach; ?>
          <div class="m-pagebase__form-help">Selecione os grupos que podem acessar esta página</div>
        </div>
        <?php endif; ?>

        <div class="m-pagebase__form-actions">
          <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
            <i data-lucide="save"></i> Salvar Alterações
          </button>
          <a href="<?= url('/admin/pages') ?>" class="m-pagebase__btn-secondary">
            <i data-lucide="x"></i> Cancelar
          </a>
        </div>
      </form>
    </div>

  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();

    // Preview de imagem OG
    (function() {
      const fileInput = document.getElementById('seo_og_image');
      const preview = document.getElementById('og-image-preview');
      const previewImg = document.getElementById('og-image-preview-img');
      const dimensions = document.getElementById('og-image-dimensions');

      if (fileInput) {
        fileInput.addEventListener('change', function(e) {
          const file = e.target.files[0];

          if (file) {
            // Validar tipo
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
              alert('Formato inválido. Use JPG, PNG ou WebP.');
              fileInput.value = '';
              preview.style.display = 'none';
              return;
            }

            // Validar tamanho (2MB)
            if (file.size > 2 * 1024 * 1024) {
              alert('Imagem muito grande. Máximo: 2MB.');
              fileInput.value = '';
              preview.style.display = 'none';
              return;
            }

            // Mostrar preview
            const reader = new FileReader();
            reader.onload = function(event) {
              previewImg.src = event.target.result;
              preview.style.display = 'block';

              // Obter dimensões
              const img = new Image();
              img.onload = function() {
                const width = this.width;
                const height = this.height;
                const ratio = (width / height).toFixed(2);
                const idealRatio = (1200 / 630).toFixed(2);

                let status = '';
                if (width === 1200 && height === 630) {
                  status = '<span style="color: #27ae60;">✅ Perfeito!</span>';
                } else if (width >= 600 && height >= 315) {
                  if (Math.abs(ratio - idealRatio) < 0.3) {
                    status = '<span style="color: #f39c12;">✓ Bom (proporção correta)</span>';
                  } else {
                    status = '<span style="color: #e74c3c;">⚠️ Proporção não ideal</span>';
                  }
                } else {
                  status = '<span style="color: #e74c3c;">❌ Muito pequeno!</span>';
                }

                dimensions.innerHTML = `<strong>Dimensões:</strong> ${width}x${height}px (proporção ${ratio}:1) ${status}`;
              };
              img.src = event.target.result;
            };
            reader.readAsDataURL(file);
          } else {
            preview.style.display = 'none';
          }
        });
      }
    })();

    // SEO Real-time Analyzer
    (function() {
      const titleInput = document.querySelector('[data-seo-field="title"]');
      const descInput = document.querySelector('[data-seo-field="description"]');
      const scoreEl = document.getElementById('seo-score');
      const gradeEl = document.getElementById('seo-grade');
      const descEl = document.getElementById('seo-desc');

      function updateCounter(field, value) {
        const counter = document.querySelector(`[data-counter="${field}"]`);
        const status = document.querySelector(`[data-status="${field}"]`);
        const length = value.length;
        const maxLength = field === 'title' ? 70 : 160;

        if (counter) {
          counter.textContent = `${length}/${maxLength} caracteres`;
        }

        if (status) {
          if (field === 'title') {
            if (length === 0) {
              status.textContent = '⚠️ Campo obrigatório';
              status.style.color = '#e74c3c';
            } else if (length >= 50 && length <= 60) {
              status.textContent = '✅ Perfeito!';
              status.style.color = '#27ae60';
            } else if (length >= 40 && length <= 70) {
              status.textContent = '✓ Bom';
              status.style.color = '#f39c12';
            } else {
              status.textContent = '⚠️ Fora do ideal (50-60 chars)';
              status.style.color = '#e74c3c';
            }
          } else if (field === 'description') {
            if (length === 0) {
              status.textContent = '⚠️ Campo obrigatório';
              status.style.color = '#e74c3c';
            } else if (length >= 150 && length <= 160) {
              status.textContent = '✅ Perfeito!';
              status.style.color = '#27ae60';
            } else if (length >= 120 && length <= 160) {
              status.textContent = '✓ Bom';
              status.style.color = '#f39c12';
            } else {
              status.textContent = '⚠️ Fora do ideal (150-160 chars)';
              status.style.color = '#e74c3c';
            }
          }
        }
      }

      function calculateScore() {
        let score = 0;
        const titleLen = titleInput.value.length;
        const descLen = descInput.value.length;
        const hasSeoBasic = titleLen > 0 && descLen > 0;

        // Title (30 pontos)
        if (titleLen >= 50 && titleLen <= 60) {
          score += 30;
        } else if (titleLen >= 40 && titleLen <= 70) {
          score += 20;
        } else if (titleLen > 0) {
          score += 10;
        }

        // Description (30 pontos)
        if (descLen >= 150 && descLen <= 160) {
          score += 30;
        } else if (descLen >= 120 && descLen <= 160) {
          score += 20;
        } else if (descLen > 0) {
          score += 10;
        }

        // OG Fields (20 pontos)
        const ogTitle = document.getElementById('seo_og_title').value;
        const ogDesc = document.getElementById('seo_og_description').value;
        if (ogTitle && ogDesc) {
          score += 20;
        } else if (hasSeoBasic) {
          score += 15; // Usa fallback do SEO básico
        } else if (ogTitle || ogDesc) {
          score += 10;
        }

        // Twitter Fields (10 pontos)
        const twitterTitle = document.getElementById('seo_twitter_title').value;
        const twitterDesc = document.getElementById('seo_twitter_description').value;
        if (twitterTitle && twitterDesc) {
          score += 10;
        } else if (hasSeoBasic) {
          score += 7; // Usa fallback do SEO básico
        } else if (twitterTitle || twitterDesc) {
          score += 5;
        }

        // Canonical (5 pontos)
        const canonical = document.getElementById('seo_canonical_url').value;
        const slug = document.querySelector('[name="slug"]')?.value || '';
        if (canonical) {
          score += 5;
        } else if (slug) {
          score += 3; // Usa fallback automático
        }

        // OG Image (5 pontos)
        const ogImageFile = document.getElementById('seo_og_image').value;
        const ogImageCurrent = document.getElementById('seo_og_image_current')?.value || '';
        if (ogImageFile || ogImageCurrent) {
          score += 5;
        }

        return Math.min(100, score);
      }

      function updateScore() {
        const score = calculateScore();
        let grade, desc, color;

        if (score >= 90) {
          grade = 'A+';
          desc = 'Excelente! SEO otimizado.';
          color = '#27ae60';
        } else if (score >= 80) {
          grade = 'A';
          desc = 'Muito bom! Pequenos ajustes podem melhorar.';
          color = '#27ae60';
        } else if (score >= 70) {
          grade = 'B';
          desc = 'Bom, mas há espaço para melhorias.';
          color = '#f39c12';
        } else if (score >= 60) {
          grade = 'C';
          desc = 'Regular. Recomenda-se otimizar.';
          color = '#f39c12';
        } else if (score >= 50) {
          grade = 'D';
          desc = 'Abaixo do ideal. Precisa de atenção.';
          color = '#e74c3c';
        } else {
          grade = 'F';
          desc = 'Crítico! SEO precisa ser configurado.';
          color = '#e74c3c';
        }

        scoreEl.textContent = score;
        gradeEl.textContent = grade;
        descEl.textContent = desc;
        scoreEl.style.color = color;
        gradeEl.style.color = color;
      }

      // Listeners
      if (titleInput) {
        titleInput.addEventListener('input', function() {
          updateCounter('title', this.value);
          updateScore();
        });
      }

      if (descInput) {
        descInput.addEventListener('input', function() {
          updateCounter('description', this.value);
          updateScore();
        });
      }

      // Listener em todos os campos SEO
      document.querySelectorAll('[id^="seo_"]').forEach(function(field) {
        field.addEventListener('input', updateScore);
        field.addEventListener('change', updateScore);
      });

      // Inicializar com valores existentes
      if (titleInput) updateCounter('title', titleInput.value);
      if (descInput) updateCounter('description', descInput.value);
      updateScore();
    })();
  </script>

</body>
</html>
