<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$pageTitle = 'Admin dashboard';
$counts = [];
foreach (['destinations', 'attractions', 'ar_posters', 'ar_hotspots', 'categories'] as $table) {
    $counts[$table] = (int) db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}
$recent = db()->query('SELECT name, updated_at FROM attractions ORDER BY updated_at DESC LIMIT 5')->fetchAll();
$activity = db()->query('SELECT action, description, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 6')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<section class="container py-5"><div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><p class="eyebrow eyebrow-dark">CONTENT STUDIO</p><h1 class="h2 mb-1">Good to see you, <?= e($admin['username']) ?>.</h1><p class="text-secondary mb-0">Your tourism content at a glance.</p></div><form method="post" action="<?= e(app_url('admin/logout.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign out</button></form></div>
<div class="row g-3 mb-5"><?php foreach (['destinations' => 'Destinations', 'attractions' => 'Attractions', 'ar_posters' => 'AR posters', 'ar_hotspots' => 'Hotspots', 'categories' => 'Categories'] as $key => $label): ?><div class="col-6 col-lg"><div class="stat-panel"><span><?= e($label) ?></span><strong><?= $counts[$key] ?></strong></div></div><?php endforeach; ?></div>
<div class="row g-4"><div class="col-lg-7"><div class="admin-section"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Recently updated attractions</h2><a href="<?= e(app_url('admin/attractions.php')) ?>">Manage</a></div><?php if (!$recent): ?><p class="text-secondary mb-0">No attractions yet. Add your first place to visit.</p><?php else: ?><div class="list-group list-group-flush"><?php foreach ($recent as $item): ?><div class="list-group-item d-flex justify-content-between px-0"><strong><?= e($item['name']) ?></strong><span class="text-secondary small"><?= e(date('M j, Y', strtotime($item['updated_at']))) ?></span></div><?php endforeach; ?></div><?php endif; ?></div></div><div class="col-lg-5"><div class="admin-section"><h2 class="h5 mb-3">Recent activity</h2><?php if (!$activity): ?><p class="text-secondary mb-0">Activity will appear here as content is managed.</p><?php else: ?><ul class="activity-list"><?php foreach ($activity as $item): ?><li><strong><?= e(str_replace('_', ' ', $item['action'])) ?></strong><span><?= e($item['description']) ?></span><small><?= e(date('M j, H:i', strtotime($item['created_at']))) ?></small></li><?php endforeach; ?></ul><?php endif; ?></div></div></div>
<div class="admin-section mt-4"><h2 class="h5 mb-3">Next steps</h2><div class="d-flex flex-wrap gap-2"><a class="btn btn-success" href="<?= e(app_url('admin/destinations.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Manage destinations</a><a class="btn btn-outline-success" href="<?= e(app_url('admin/attractions.php')) ?>">Manage attractions</a><a class="btn btn-outline-success" href="<?= e(app_url('admin/ar-posters.php')) ?>">AR posters</a></div></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
