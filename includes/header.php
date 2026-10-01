<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$activePage = $activePage ?? '';
$flash = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123d35">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/site.css')) ?>" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/components.css')) ?>" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/qr.css')) ?>" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/local-images.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg site-nav">
    <div class="container">
        <a class="navbar-brand" href="<?= e(app_url()) ?>"><span class="brand-mark"><i class="fa-solid fa-location-dot"></i></span><span>AR <strong>TOURISM</strong></span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="<?= e(app_url()) ?>">Explore</a>
                <a class="nav-link" href="<?= e(app_url('destinations.php')) ?>">Destinations</a>
                <a class="nav-link" href="<?= e(app_url('attractions.php')) ?>">Attractions</a>
                <a class="nav-link nav-link-ar" href="<?= e(app_url('ar.php')) ?>"><i class="fa-solid fa-camera me-2"></i>Start AR</a>
            </div>
        </div>
    </div>
</nav>
<?php if ($flash): ?>
<div class="container pt-3"><div class="alert alert-<?= e($flash['type']) ?> mb-0" role="status"><?= e($flash['message']) ?></div></div>
<?php endif; ?>
<main>
