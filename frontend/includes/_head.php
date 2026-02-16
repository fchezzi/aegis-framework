<!-- charset -->
<meta charset="UTF-8">
<!-- autor -->
<meta name="author" content="Sociaholic Mídias">
<!-- viewport -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- edge -->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<!-- favicon frontend -->
<?php
$faviconPath = Settings::get('favicon');
if (empty($faviconPath)) {
    $faviconPath = '/assets/img/favicon.svg'; // Fallback
} else {
    $faviconPath = '/storage/' . $faviconPath;
}
?>
<link rel="shortcut icon" href="<?= url($faviconPath) ?>" />

<?php
// Renderizar SEO tags se $page existir
if (isset($page) && !empty($page)) {
    echo SEO::render($page);
    echo SEO::renderJsonLD($page);
}
?>

<!-- css frontend -->
<link rel="stylesheet" type="text/css" href="<?= url('/assets/css/frontend.css') ?>" >
<!-- lucide icons -->
<script src="https://unpkg.com/lucide@latest"></script>
