<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploads.php';
$admin = require_admin();
$pageTitle = 'AR posters';
$error = '';
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT) ?: null;
$poster = [];
if ($editId) {
    $statement = db()->prepare('SELECT * FROM ar_posters WHERE id = ?');
    $statement->execute([$editId]);
    $poster = $statement->fetch() ?: [];
    if (!$poster) {
        $editId = null;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $editId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $statement = $editId ? db()->prepare('SELECT * FROM ar_posters WHERE id = ?') : null;
    if ($statement) {
        $statement->execute([$editId]);
        $poster = $statement->fetch() ?: [];
    }
    try {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'draft');
        if ($name === '' || !in_array($status, ['draft', 'active', 'inactive'], true)) {
            throw new RuntimeException('Enter a name and choose a valid status.');
        }
        $newImage = store_image_upload($_FILES['poster_image'] ?? [], 'poster', 400, 300);
        $imagePath = $newImage ?: ($poster['poster_image'] ?? null);
        if (!$imagePath) {
            throw new RuntimeException('Choose a poster image to upload.');
        }
        if ($editId) {
            $targetStatus = $newImage ? 'not_compiled' : ($poster['target_status'] ?? 'not_compiled');
            $targetFile = $newImage ? null : ($poster['target_file'] ?? null);
            db()->prepare('UPDATE ar_posters SET name = ?, description = ?, poster_image = ?, target_status = ?, target_file = ?, status = ? WHERE id = ?')
                ->execute([$name, $description, $imagePath, $targetStatus, $targetFile, $status, $editId]);
            if ($newImage && !empty($poster['target_file'])) {
                $oldTarget = dirname(__DIR__) . '/' . $poster['target_file'];
                if (str_starts_with($poster['target_file'], 'assets/ar-targets/') && is_file($oldTarget)) {
                    unlink($oldTarget);
                }
            }
            record_activity('ar_poster_updated', 'AR poster #' . $editId . ' updated');
        } else {
            db()->prepare('INSERT INTO ar_posters (name, description, poster_image, status) VALUES (?, ?, ?, ?)')->execute([$name, $description, $imagePath, $status]);
            $editId = (int) db()->lastInsertId();
            record_activity('ar_poster_created', 'AR poster #' . $editId . ' created');
        }
        set_flash('success', 'Poster saved.');
        header('Location: ' . app_url('admin/ar-posters.php'));
        exit;
    } catch (Throwable $exception) {
        $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'The poster could not be saved. Check that its slug and image are valid.';
        $poster = array_merge($poster, $_POST, ['name' => $_POST['name'] ?? '']);
    }
}
$posters = db()->query('SELECT p.*, (SELECT COUNT(*) FROM ar_hotspots h WHERE h.poster_id = p.id) AS hotspot_count FROM ar_posters p ORDER BY p.updated_at DESC')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<section class="container py-5"><div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4"><div><p class="eyebrow eyebrow-dark">AR MANAGEMENT</p><h1 class="h2 mb-0">Tourism posters</h1></div><a class="btn btn-outline-secondary" href="<?= e(app_url('admin/index.php')) ?>">Dashboard</a></div><div class="row g-4"><div class="col-lg-7"><div class="row g-3"><?php foreach ($posters as $item): ?><div class="col-md-6"><article class="poster-admin-card"><img src="<?= e(app_url($item['poster_image'])) ?>" alt="<?= e($item['name']) ?>"><div class="poster-admin-body"><div class="d-flex justify-content-between gap-2"><h2 class="h5"><?= e($item['name']) ?></h2><span class="status-badge status-<?= e($item['target_status']) ?>"><?= e(str_replace('_', ' ', $item['target_status'])) ?></span></div><p class="small text-secondary"><?= (int) $item['hotspot_count'] ?> hotspots · <?= e($item['status']) ?></p><div class="d-flex gap-2"><a class="btn btn-sm btn-success" href="<?= e(app_url('admin/ar-editor.php?poster=' . (int) $item['id'])) ?>">Edit hotspots</a><a class="btn btn-sm btn-outline-secondary" href="?edit=<?= (int) $item['id'] ?>">Edit poster</a></div></div></article></div><?php endforeach; ?><?php if (!$posters): ?><div class="col-12"><div class="admin-section text-secondary">No posters yet. Upload the first poster to create its image target.</div></div><?php endif; ?></div></div><div class="col-lg-5"><div class="admin-section"><h2 class="h5 mb-3"><?= $editId ? 'Edit poster' : 'Add poster' ?></h2><?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) ($poster['id'] ?? $editId ?? 0) ?>"><label class="form-label" for="name">Poster name</label><input class="form-control mb-3" id="name" name="name" value="<?= e($poster['name'] ?? '') ?>" required><label class="form-label" for="description">Description</label><textarea class="form-control mb-3" id="description" name="description" rows="3"><?= e($poster['description'] ?? '') ?></textarea><label class="form-label" for="poster_image">Poster image (JPEG, PNG, WebP; max 8 MB)</label><input class="form-control mb-3" id="poster_image" name="poster_image" type="file" accept="image/jpeg,image/png,image/webp" <?= empty($poster['poster_image']) ? 'required' : '' ?>><?php if (!empty($poster['poster_image'])): ?><img class="poster-form-preview mb-3" src="<?= e(app_url($poster['poster_image'])) ?>" alt="Current poster"><?php endif; ?><label class="form-label" for="status">Publishing status</label><select class="form-select mb-3" id="status" name="status"><?php foreach (['draft', 'active', 'inactive'] as $status): ?><option value="<?= e($status) ?>" <?= ($poster['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select><button class="btn btn-success" type="submit">Save poster</button><?php if ($editId): ?><a class="btn btn-outline-secondary ms-2" href="<?= e(app_url('admin/ar-posters.php')) ?>">Cancel</a><?php endif; ?></form><p class="form-text mt-3 mb-0">Replacing an image clears the previous target so it must be compiled again.</p></div></div></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
