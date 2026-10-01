<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Attractions';
$search = trim((string) ($_GET['q'] ?? ''));
$sql = "SELECT a.*, d.name AS destination_name, d.slug AS destination_slug, c.name AS category_name FROM attractions a JOIN destinations d ON d.id = a.destination_id LEFT JOIN categories c ON c.id = a.category_id WHERE a.status = 'active' AND d.status = 'active'";
$parameters = [];
if ($search !== '') {
    $sql .= ' AND (a.name LIKE ? OR a.short_description LIKE ? OR d.name LIKE ? OR c.name LIKE ?)';
    $term = '%' . $search . '%';
    $parameters = [$term, $term, $term, $term];
}
$sql .= ' ORDER BY a.display_order, a.name';
$statement = db()->prepare($sql);
$statement->execute($parameters);
$attractions = $statement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="container py-5"><p class="eyebrow eyebrow-dark">SMALL PLACES, BIG STORIES</p><div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4"><h1 class="display-6 fw-bold mb-0">Attractions</h1><form method="get" class="d-flex gap-2"><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search attractions" aria-label="Search attractions"><button class="btn btn-success" type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button></form></div><div class="row g-4"><?php foreach ($attractions as $attraction): ?><div class="col-md-6 col-lg-4"><a class="attraction-card" href="<?= e(app_url('attraction.php?slug=' . urlencode($attraction['slug']))) ?>"><img src="<?= e($attraction['main_image'] ?: '') ?>" alt="<?= e($attraction['name']) ?>" loading="lazy"><div class="attraction-caption"><span><?= e($attraction['destination_name']) ?> · <?= e($attraction['category_name'] ?? 'Attraction') ?></span><h2><?= e($attraction['name']) ?></h2><p><?= e($attraction['short_description']) ?></p></div></a></div><?php endforeach; ?><?php if (!$attractions): ?><div class="col-12"><p class="text-secondary">No attractions matched your search.</p></div><?php endif; ?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
