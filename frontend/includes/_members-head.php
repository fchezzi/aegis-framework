<!-- charset -->
<meta charset="UTF-8">
<!-- autor -->
<meta name="author" content="Sociaholic Mídias">
<!-- viewport -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- edge -->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<!-- favicon members -->
<?php
$membersFaviconPath = Settings::get('members_favicon');
if (empty($membersFaviconPath)) {
    $membersFaviconPath = '/assets/img/favicon.svg'; // Fallback
} else {
    $membersFaviconPath = '/storage/' . $membersFaviconPath;
}
?>
<link rel="shortcut icon" href="<?= url($membersFaviconPath) ?>" />

<?php
// Renderizar SEO tags se $page existir
if (isset($page) && !empty($page)) {
    echo SEO::render($page);
    echo SEO::renderJsonLD($page);
}
?>

<!-- css members -->
<link rel="stylesheet" type="text/css" href="<?= url('/assets/css/members.css') ?>">
<!-- lucide icons -->
<script src="https://unpkg.com/lucide@latest"></script>
