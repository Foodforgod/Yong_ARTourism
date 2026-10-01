<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Destinations';
$search = trim((string) ($_GET['q'] ?? ''));
$sql = "SELECT d.*, c.name AS category_name FROM destinations d LEFT JOIN categories c ON c.id = d.category_id WHERE d.status = 'active'";
$parameters = [];
if ($search !== '') {
    $sql .= ' AND (d.name LIKE ? OR d.short_description LIKE ? OR d.location LIKE ? OR c.name LIKE ?)';
    $term = '%' . $search . '%';
    $parameters = [$term, $term, $term, $term];
}
$sql .= ' ORDER BY d.featured DESC, d.name';
$statement = db()->prepare($sql);
$statement->execute($parameters);
$destinations = $statement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="container py-5"><p class="eyebrow eyebrow-dark">GO A LITTLE FURTHER</p><div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4"><h1 class="display-6 fw-bold mb-0">Destinations</h1><form method="get" class="d-flex gap-2"><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search places" aria-label="Search destinations"><button class="btn btn-success" type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button></form></div><div class="row g-4"><?php foreach ($destinations as $index => $destination): ?><div class="col-md-6 col-xl-4"><a class="destination-card" href="<?= e(app_url('destination.php?slug=' . urlencode($destination['slug']))) ?>"><div class="destination-image"><img src="<?= e($destination['cover_image'] ?: '') ?>" alt="<?= e($destination['name']) ?>" loading="lazy"><span class="card-number">0<?= $index + 1 ?></span></div><div class="destination-meta"><span><?= e($destination['location'] ?: 'Explore') ?></span><span><?= e($destination['category_name'] ?? 'DESTINATION') ?></span></div><h2 class="h4"><?= e($destination['name']) ?></h2><p><?= e($destination['short_description']) ?></p></a></div><?php endforeach; ?><?php if (!$destinations): ?><div class="col-12"><p class="text-secondary">No destinations matched your search.</p></div><?php endif; ?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
