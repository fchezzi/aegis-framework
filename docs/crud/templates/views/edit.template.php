<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '/../../../includes/_admin-head.php';
	?>
	<title>Editar {{RESOURCE_SINGULAR}} - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<div class="m-pagebase">

		<a href="<?= url('/admin/{{resource_slug}}') ?>" class="m-pagebase__back">← Voltar para {{RESOURCE_PLURAL}}</a>

		<div class="m-pagebase__header">
			<h1>Editar {{RESOURCE_SINGULAR}}</h1>
		</div>

		<?php if (isset($_SESSION['error'])): ?>
			<div class="alert alert--error"><?= htmlspecialchars($_SESSION['error']) ?></div>
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

		<div class="m-pagebase__form-container">
			<form method="POST" action="<?= url('/admin/{{resource_slug}}/' . htmlspecialchars(${{resource_var_singular}}['id'])) ?>" enctype="multipart/form-data" class="m-pagebase__form">
				<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

				<!-- PERSONALIZAR CAMPOS AQUI -->

				<!-- EXEMPLO: Campo de texto obrigatório -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Título *</label>
					<input type="text" name="title" value="<?= htmlspecialchars(${{resource_var_singular}}['title']) ?>" required maxlength="255" class="m-pagebase__form-input" placeholder="Digite o título">
					<p class="m-pagebase__form-help">Título principal do registro (máximo 255 caracteres)</p>
				</div>

				<!-- EXEMPLO: Campo de texto opcional -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Subtítulo</label>
					<input type="text" name="subtitle" value="<?= htmlspecialchars(${{resource_var_singular}}['subtitle'] ?? '') ?>" maxlength="255" class="m-pagebase__form-input" placeholder="Digite o subtítulo (opcional)">
					<p class="m-pagebase__form-help">Texto secundário opcional</p>
				</div>

				<!-- EXEMPLO: Textarea -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Descrição</label>
					<textarea name="description" maxlength="1000" class="m-pagebase__form-textarea" placeholder="Digite a descrição"><?= htmlspecialchars(${{resource_var_singular}}['description'] ?? '') ?></textarea>
					<p class="m-pagebase__form-help">Descrição detalhada (máximo 1000 caracteres)</p>
				</div>

				<!-- EXEMPLO: Upload de imagem (EDIÇÃO) -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Nova Imagem</label>
					<input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="m-pagebase__form-file">
					<p class="m-pagebase__form-help">JPG, PNG, GIF ou WEBP - Máximo 5MB (deixe vazio para manter a imagem atual)</p>

					<!-- Preview da imagem atual -->
					<div class="m-pagebase__form-preview">
						<strong>Imagem Atual:</strong>
						<img src="<?= url(htmlspecialchars(${{resource_var_singular}}['image'])) ?>" alt="Imagem atual" class="m-pagebase__form-preview-img">
					</div>
				</div>

				<!-- EXEMPLO: Campo numérico -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-label">Ordem de Exibição *</label>
					<input type="number" name="order" value="<?= htmlspecialchars(${{resource_var_singular}}['order']) ?>" min="0" required class="m-pagebase__form-input">
					<p class="m-pagebase__form-help">Menor número aparece primeiro (0 = primeiro)</p>
				</div>

				<!-- EXEMPLO: Checkbox -->
				<div class="m-pagebase__form-group">
					<label class="m-pagebase__form-checkbox">
						<input type="checkbox" name="ativo" <?= ${{resource_var_singular}}['ativo'] ? 'checked' : '' ?>>
						<span>Registro ativo (visível no site)</span>
					</label>
				</div>

				<!-- Botões de ação -->
				<div class="m-pagebase__form-actions">
					<button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">Salvar Alterações</button>
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
   {{resource_var_singular}} = Variável PHP singular (ex: $banner, $category)

2. Personalizar campos do formulário:
   - Remover campos de exemplo não necessários
   - Adicionar novos campos conforme a tabela do banco
   - Ajustar tipos (text, textarea, number, file, select, checkbox)

3. DIFERENÇAS entre CREATE e EDIT:

   ✅ EDIT tem value preenchido:
   value="<?= htmlspecialchars(${{resource_var_singular}}['campo']) ?>"

   ✅ EDIT usa operador null coalescing para campos opcionais:
   value="<?= htmlspecialchars(${{resource_var_singular}}['campo'] ?? '') ?>"

   ✅ EDIT tem checkbox com checked condicional:
   <?= ${{resource_var_singular}}['ativo'] ? 'checked' : '' ?>

   ✅ EDIT tem preview de arquivo atual (imagem, PDF, etc):
   <div class="m-pagebase__form-preview">...</div>

   ✅ EDIT tem upload OPCIONAL (não é required):
   <input type="file" name="image">  <!-- SEM required -->

   ✅ EDIT tem action com ID:
   action="<?= url('/admin/{{resource_slug}}/' . ${{resource_var_singular}}['id']) ?>"

4. Preview de arquivos (exemplos):

   🖼️ IMAGEM:
   <div class="m-pagebase__form-preview">
     <strong>Imagem Atual:</strong>
     <img src="<?= url(${{resource_var_singular}}['image']) ?>" class="m-pagebase__form-preview-img">
   </div>

   📄 PDF/ARQUIVO:
   <div class="m-pagebase__form-file-current">
     <strong>Arquivo Atual:</strong>
     <a href="<?= url(${{resource_var_singular}}['file']) ?>" target="_blank">
       <i data-lucide="download"></i> Baixar arquivo
     </a>
   </div>

5. Select com valor selecionado:
   <select name="campo" class="m-pagebase__form-select">
     <option value="">Selecione...</option>
     <option value="1" <?= ${{resource_var_singular}}['campo'] == '1' ? 'selected' : '' ?>>Opção 1</option>
     <option value="2" <?= ${{resource_var_singular}}['campo'] == '2' ? 'selected' : '' ?>>Opção 2</option>
   </select>

6. Textarea com conteúdo:
   <textarea name="campo" class="m-pagebase__form-textarea"><?= htmlspecialchars(${{resource_var_singular}}['campo'] ?? '') ?></textarea>

IMPORTANTE:
===========
- SEMPRE usar htmlspecialchars() nos values
- SEMPRE usar operador ?? '' para campos nullable
- SEMPRE usar ternário para checkboxes (? 'checked' : '')
- UPLOAD em EDIT é OPCIONAL (usuário pode manter arquivo atual)
- PREVIEW do arquivo atual ajuda usuário a decidir se troca
- Action do form DEVE incluir o ID do registro

PADRÃO AEGIS:
=============
- Mesmas classes do create.template.php
- Adicionar preview de arquivos quando aplicável
- Botão "Salvar Alterações" ao invés de "Criar"
- Validações backend são as mesmas
-->
