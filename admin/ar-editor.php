<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$posterId = filter_var($_GET['poster'] ?? null, FILTER_VALIDATE_INT);
$statement = db()->prepare('SELECT * FROM ar_posters WHERE id = ?');
$statement->execute([$posterId]);
$poster = $statement->fetch();
if (!$poster) {
    http_response_code(404);
    exit('Poster not found.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        echo json_encode(['error' => 'Session expired. Refresh the editor and try again.']);
        exit;
    }
    $payload = json_decode(file_get_contents('php://input'), true);
    $hotspots = $payload['hotspots'] ?? null;
    if (!is_array($hotspots) || count($hotspots) > 50) {
        http_response_code(400);
        echo json_encode(['error' => 'Provide up to 50 hotspots.']);
        exit;
    }
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM ar_hotspots WHERE poster_id = ?')->execute([$posterId]);
        $insert = $pdo->prepare('INSERT INTO ar_hotspots (poster_id, attraction_id, label, x, y, width, height, z_index, video_url, content_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($hotspots as $index => $hotspot) {
            $label = trim((string) ($hotspot['label'] ?? ''));
            $x = filter_var($hotspot['x'] ?? null, FILTER_VALIDATE_FLOAT);
            $y = filter_var($hotspot['y'] ?? null, FILTER_VALIDATE_FLOAT);
            $width = filter_var($hotspot['width'] ?? null, FILTER_VALIDATE_FLOAT);
            $height = filter_var($hotspot['height'] ?? null, FILTER_VALIDATE_FLOAT);
            $attractionId = ($hotspot['attraction_id'] ?? '') === '' ? null : filter_var($hotspot['attraction_id'], FILTER_VALIDATE_INT);
            $contentType = (string) ($hotspot['content_type'] ?? 'information');
            $videoUrl = trim((string) ($hotspot['video_url'] ?? ''));
            if ($label === '' || strlen($label) > 120 || $x === false || $y === false || $width === false || $height === false || $x < 0 || $y < 0 || $width <= 0 || $height <= 0 || $x + $width > 1.000001 || $y + $height > 1.000001 || !in_array($contentType, ['information', 'video', 'image'], true)) {
                throw new RuntimeException('A hotspot has invalid text, type, or normalized coordinates.');
            }
            if ($attractionId !== null && $attractionId === false) {
                throw new RuntimeException('Choose a valid attraction.');
            }
            if ($videoUrl !== '' && (!filter_var($videoUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($videoUrl, PHP_URL_SCHEME), ['http', 'https'], true))) {
                throw new RuntimeException('Enter a valid HTTPS or HTTP video URL.');
            }
            $insert->execute([$posterId, $attractionId, $label, $x, $y, $width, $height, $index + 1, $videoUrl ?: null, $contentType]);
        }
        $pdo->commit();
        record_activity('ar_hotspots_saved', 'Hotspots updated for poster #' . $posterId);
        $statement = $pdo->prepare('SELECT h.id, h.attraction_id, h.label, h.x, h.y, h.width, h.height, h.video_url, h.content_type FROM ar_hotspots h WHERE h.poster_id = ? ORDER BY h.z_index');
        $statement->execute([$posterId]);
        echo json_encode(['status' => 'saved', 'hotspots' => $statement->fetchAll()]);
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        http_response_code(422);
        echo json_encode(['error' => $exception instanceof RuntimeException ? $exception->getMessage() : 'The layout could not be saved.']);
    }
    exit;
}
$statement = db()->prepare('SELECT h.id, h.attraction_id, h.label, h.x, h.y, h.width, h.height, h.video_url, h.content_type FROM ar_hotspots h WHERE h.poster_id = ? ORDER BY h.z_index');
$statement->execute([$posterId]);
$hotspots = $statement->fetchAll();
$attractions = db()->query("SELECT id, name FROM attractions WHERE status = 'active' ORDER BY name")->fetchAll();
$dimensions = getimagesize(dirname(__DIR__) . '/' . $poster['poster_image']);
$config = [
    'csrf' => csrf_token(),
    'hotspots' => $hotspots,
    'saveUrl' => app_url('admin/ar-editor.php?poster=' . $posterId),
    'compileUrl' => app_url('admin/save-target.php?poster=' . $posterId),
];
$pageTitle = 'Poster hotspot editor';
require __DIR__ . '/../includes/header.php';
?>
<section class="container-fluid px-lg-5 py-4" data-ar-editor><div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"><div><p class="eyebrow eyebrow-dark mb-1">AR POSTER EDITOR</p><h1 class="h3 mb-0"><?= e($poster['name']) ?></h1></div><a class="btn btn-outline-secondary" href="<?= e(app_url('admin/ar-posters.php')) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Posters</a></div><div class="row g-4 align-items-start"><div class="col-xl-8"><div class="poster-canvas-wrap"><div class="poster-board" data-board><img data-poster-image src="<?= e(app_url($poster['poster_image'])) ?>" alt="Poster being edited"><span class="poster-empty-hint">Select a hotspot to move or resize. Click the poster to add one.</span></div></div></div><aside class="col-xl-4"><div class="admin-section"><div class="d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Hotspots</h2><span class="status-badge status-<?= e($poster['target_status']) ?>" data-target-status><?= e(strtoupper(str_replace('_', ' ', $poster['target_status']))) ?></span></div><p class="small text-secondary mt-2">Positions are stored as normalized coordinates from 0 to 1.</p><div class="d-flex gap-2 mb-3"><button class="btn btn-outline-success" type="button" data-add><i class="fa-solid fa-plus me-1"></i>Add</button><button class="btn btn-outline-danger" type="button" data-delete><i class="fa-solid fa-trash me-1"></i>Delete selected</button></div><form data-inspector hidden><label class="form-label" for="hotspot-label">Hotspot label</label><input class="form-control mb-3" name="label" id="hotspot-label" maxlength="120"><label class="form-label" for="hotspot-attraction">Attraction</label><select class="form-select mb-3" name="attraction_id" id="hotspot-attraction"><option value="">No attraction linked</option><?php foreach ($attractions as $attraction): ?><option value="<?= (int) $attraction['id'] ?>"><?= e($attraction['name']) ?></option><?php endforeach; ?></select><label class="form-label" for="hotspot-type">Content type</label><select class="form-select mb-3" name="content_type" id="hotspot-type"><option value="information">Information card</option><option value="video">Video</option><option value="image">Image</option></select><label class="form-label" for="hotspot-video">Video URL</label><input class="form-control mb-3" name="video_url" id="hotspot-video" type="url" placeholder="https://youtu.be/…"></form><hr><div class="d-flex flex-wrap gap-2"><button class="btn btn-success" type="button" data-save><i class="fa-solid fa-floppy-disk me-2"></i>Save hotspots</button><button class="btn btn-outline-success" type="button" data-compile><i class="fa-solid fa-cubes me-2"></i>Compile target</button></div><p class="small mt-3 mb-0" aria-live="polite" data-editor-status></p></div></aside></div><script type="application/json" data-config><?= json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script></section>
<script type="module" src="<?= e(app_url('assets/js/ar-editor.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
