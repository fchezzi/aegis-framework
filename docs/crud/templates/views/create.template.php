<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '/../../../includes/_admin-head.php';
	?>
	<title>Novo {{RESOURCE_SINGULAR}} - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<div class="m-pagebase">

		<a href="<?= url('/admin/{{resource_slug}}') ?>" class="m-pagebase__back">← Voltar para {{RESOURCE_PLURAL}}</a>

		<div class="m-pagebase__header">
			<h1>Novo {{RESOURCE_SINGULAR}}</h1>
		</div>

		<?php if (isset($_SESSION['error'])): ?>
			<div class="alert alert--error"><?= htmlspecialchars($_SESSION['error']) ?></div>
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

		<div class="m-pagebase__form-container">
			<form method="POST" action="<?= url('/admin/{{resource_slug}}') ?>" enctype="multipart/form-data" class="m-pagebase__form">
				<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

				<!-- PERSONALIZAR CAMPOS AQUI -->

				<!-- EXEMPLO: Campo de texto obrigatório -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Título *</label>
					<input type="text" name="title" required maxlength="255" class="m-pagebase__form-input" placeholder="Digite o título">
					<p class="m-pagebase__form-help">Título principal do registro (máximo 255 caracteres)</p>
				</div>

				<!-- EXEMPLO: Campo de texto opcional -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Subtítulo</label>
					<input type="text" name="subtitle" maxlength="255" class="m-pagebase__form-input" placeholder="Digite o subtítulo (opcional)">
					<p class="m-pagebase__form-help">Texto secundário opcional</p>
				</div>

				<!-- EXEMPLO: Textarea -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Descrição</label>
					<textarea name="description" maxlength="1000" class="m-pagebase__form-textarea" placeholder="Digite a descrição"></textarea>
					<p class="m-pagebase__form-help">Descrição detalhada (máximo 1000 caracteres)</p>
				</div>

				<!-- EXEMPLO: Upload de imagem -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Imagem *</label>
					<input type="file" name="image" required accept="image/jpeg,image/png,image/gif,image/webp" class="m-pagebase__form-file">
					<p class="m-pagebase__form-help">JPG, PNG, GIF ou WEBP - Máximo 5MB</p>
				</div>

				<!-- EXEMPLO: Campo numérico -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Ordem de Exibição *</label>
					<input type="number" name="order" value="0" min="0" required class="m-pagebase__form-input">
					<p class="m-pagebase__form-help">Menor número aparece primeiro (0 = primeiro)</p>
				</div>

				<!-- EXEMPLO: Checkbox -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-checkbox">
						<input type="checkbox" name="ativo" checked>
						<span>Registro ativo (visível no site)</span>
					</label>
				</div>

				<!-- Botões de ação -->
				<div class="m-pagebase__form-actions">
					<button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">Criar {{RESOURCE_SINGULAR}}</button>
					<a href="<?= url('/admin/{{resource_slug}}') ?>" class="m-pagebase__btn-secondary m-pagebase__btn--widthauto">Cancelar</a>
				</div>

			</form>
		</div>

	</div>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>

</body>
</html>

<!--
INSTRUÇÕES DE USO:
==================

1. Substituir placeholders:
   {{RESOURCE_PLURAL}} = Nome plural do recurso (ex: "Banners Hero", "Categorias")
   {{RESOURCE_SINGULAR}} = Nome singular (ex: "Banner", "Categoria")
   {{resource_slug}} = Slug da rota (ex: "banners", "categories")

2. Personalizar campos do formulário:
   - Remover campos de exemplo não necessários
   - Adicionar novos campos conforme a tabela do banco
   - Ajustar tipos (text, textarea, number, file, select, checkbox)

3. Tipos de campos disponíveis:

   📝 TEXTO:
   <input type="text" name="campo" class="m-pagebase__form-input">

   📝 TEXTAREA:
   <textarea name="campo" class="m-pagebase__form-textarea"></textarea>

   🔢 NÚMERO:
   <input type="number" name="campo" class="m-pagebase__form-input">

   📧 EMAIL:
   <input type="email" name="campo" class="m-pagebase__form-input">

   📁 UPLOAD:
   <input type="file" name="campo" class="m-pagebase__form-file">

   📅 DATA:
   <input type="date" name="campo" class="m-pagebase__form-input">

   🔘 CHECKBOX:
   <label class="m-pagebase__form-checkbox">
     <input type="checkbox" name="campo">
     <span>Texto do checkbox</span>
   </label>

   📋 SELECT:
   <select name="campo" class="m-pagebase__form-select">
     <option value="">Selecione...</option>
     <option value="1">Opção 1</option>
   </select>

4. Validações importantes:
   - required = campo obrigatório
   - maxlength = limite de caracteres
   - min/max = valores numéricos
   - accept = tipos de arquivo permitidos
   - pattern = validação regex

5. Classes CSS do AEGIS:
   - m-pagebase__form-group = wrapper do campo
   - m-pagebase__form-label = label do campo
   - m-pagebase__form-input = campo de texto/número/email/data
   - m-pagebase__form-textarea = campo de texto longo
   - m-pagebase__form-file = upload de arquivo
   - m-pagebase__form-select = dropdown
   - m-pagebase__form-checkbox = checkbox com label
   - m-pagebase__form-help = texto de ajuda abaixo do campo
   - m-pagebase__form-actions = container dos botões

PADRÃO AEGIS:
=============
- SEMPRE usar enctype="multipart/form-data" se tiver upload
- SEMPRE incluir csrf_token
- SEMPRE usar classes m-pagebase__form-*
- SEMPRE incluir texto de ajuda (.form-help)
- SEMPRE ter botão Cancelar que volta para listagem
- NUNCA usar CSS inline nos campos
- SEMPRE validar no backend (frontend é apenas UX)
-->
