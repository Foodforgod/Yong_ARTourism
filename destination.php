<?php
require_once __DIR__ . '/includes/functions.php';
$slug = trim((string) ($_GET['slug'] ?? ''));
$statement = db()->prepare("SELECT d.*, c.name AS category_name FROM destinations d LEFT JOIN categories c ON c.id = d.category_id WHERE d.slug = ? AND d.status = 'active' LIMIT 1");
$statement->execute([$slug]);
$destination = $statement->fetch();
if (!$destination) {
    http_response_code(404);
}
$pageTitle = $destination ? $destination['name'] : 'Destination not found';
$attractions = [];
if ($destination) {
    $statement = db()->prepare("SELECT * FROM attractions WHERE destination_id = ? AND status = 'active' ORDER BY display_order, name");
    $statement->execute([$destination['id']]);
    $attractions = $statement->fetchAll();
}
require __DIR__ . '/includes/header.php';
?>
<section class="container py-5"><?php if (!$destination): ?><h1 class="h2">Destination not found</h1><a href="<?= e(app_url('destinations.php')) ?>">Browse destinations</a><?php else: ?><p class="eyebrow eyebrow-dark"><?= e($destination['category_name'] ?? 'DESTINATION') ?> · <?= e($destination['location'] ?? '') ?></p><h1 class="display-5 fw-bold"><?= e($destination['name']) ?></h1><p class="lead col-lg-8"><?= e($destination['short_description']) ?></p><?php if ($destination['cover_image']): ?><img class="detail-cover" src="<?= e($destination['cover_image']) ?>" alt="<?= e($destination['name']) ?>" loading="lazy"><?php endif; ?><div class="row g-5 mt-2"><article class="col-lg-7"><p class="detail-copy"><?= nl2br(e($destination['description'] ?? '')) ?></p><?php if ($destination['map_url']): ?><a class="btn btn-outline-success mt-2" href="<?= e($destination['map_url']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-map-location-dot me-2"></i>View map</a><?php endif; ?></article><div class="col-lg-5"><h2 class="h4 mb-3">Places to explore</h2><?php foreach ($attractions as $attraction): ?><a class="detail-attraction" href="<?= e(app_url('attraction.php?slug=' . urlencode($attraction['slug']))) ?>"><strong><?= e($attraction['name']) ?></strong><span><?= e($attraction['short_description']) ?></span></a><?php endforeach; ?><?php if (!$attractions): ?><p class="text-secondary">Attractions for this destination are coming soon.</p><?php endif; ?></div></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
