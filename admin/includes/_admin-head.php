<!-- charset -->
<meta charset="UTF-8">
<!-- viewport -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- favicon admin -->
<?php
$adminFaviconPath = Settings::get('admin_favicon');
if (empty($adminFaviconPath)) {
    $adminFaviconPath = '/assets/img/favicon.svg'; // Fallback
} else {
    $adminFaviconPath = '/storage/' . $adminFaviconPath;
}
?>
<link rel="shortcut icon" href="<?= url($adminFaviconPath) ?>" />
<!-- css admin -->
<link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
<!-- js admin (se necessário) -->
<?php if (isset($loadAdminJs) && $loadAdminJs): ?>
<script src="<?= url('/assets/js/admin.js') ?>"></script>
<?php endif; ?>
