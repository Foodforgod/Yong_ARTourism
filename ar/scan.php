<?php
require_once __DIR__ . '/../includes/functions.php';
$posterId = filter_var($_GET['poster'] ?? null, FILTER_VALIDATE_INT);
$statement = db()->prepare("SELECT * FROM ar_posters WHERE id = ? AND status = 'active' AND target_status = 'ready' AND target_file IS NOT NULL");
$statement->execute([$posterId]);
$poster = $statement->fetch();
if (!$poster || !is_file(dirname(__DIR__) . '/' . $poster['target_file'])) {
    http_response_code(404);
    exit('AR tracking is not ready for this poster. Please ask an administrator to compile the target.');
}
$statement = db()->prepare("SELECT h.id, h.label, h.x, h.y, h.width, h.height, h.z_index, h.video_url, h.content_type, a.name AS attraction_name, a.slug AS attraction_slug, a.short_description, a.main_image, a.map_url FROM ar_hotspots h LEFT JOIN attractions a ON a.id = h.attraction_id AND a.status = 'active' WHERE h.poster_id = ? AND h.status = 'active' ORDER BY h.z_index");
$statement->execute([$posterId]);
$hotspots = $statement->fetchAll();
if (!$hotspots) {
    http_response_code(404);
    exit('No AR attractions have been configured for this poster.');
}
foreach ($hotspots as &$hotspot) {
    $parts = parse_url($hotspot['video_url'] ?? '');
    $host = strtolower($parts['host'] ?? '');
    parse_str($parts['query'] ?? '', $query);
    $videoId = '';
    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        $videoId = trim($parts['path'] ?? '', '/');
    } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
        $videoId = (string) ($query['v'] ?? '');
        if ($videoId === '' && preg_match('#^/(?:embed|shorts)/([^/]+)#', $parts['path'] ?? '', $matches)) {
            $videoId = $matches[1];
        }
    }
    $hotspot['youtube_id'] = preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) ? $videoId : null;
}
unset($hotspot);
$dimensions = getimagesize(dirname(__DIR__) . '/' . $poster['poster_image']);
$config = [
    'targetUrl' => app_url($poster['target_file']),
    'baseUrl' => app_url(),
    'posterWidth' => $dimensions[0] ?: 1,
    'posterHeight' => $dimensions[1] ?: 1,
    'hotspots' => $hotspots,
];
$pageTitle = 'Scan ' . $poster['name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="ar-scanner" data-ar-scan><div class="scanner-top"><a href="<?= e(app_url('ar.php')) ?>" aria-label="Back to AR experiences"><i class="fa-solid fa-arrow-left"></i></a><strong>AR TOURISM</strong><span class="tracking-chip" data-tracking-status aria-live="polite">READY TO SCAN</span></div><div class="camera-stage" data-camera-view><div class="camera-placeholder"><i class="fa-solid fa-camera"></i><p>Camera preview appears here</p></div><div class="scan-guide" data-scan-help><span>Point your camera at the poster</span></div></div><div class="scanner-controls"><p class="poster-scan-name"><?= e($poster['name']) ?></p><p class="small mb-3">Keep the full poster in view and move slowly.</p><button class="btn btn-lime btn-lg" type="button" data-start><i class="fa-solid fa-camera me-2"></i>Start scanning</button><button class="btn btn-outline-light btn-lg" type="button" data-stop hidden>Stop camera</button></div><article class="ar-info-card" data-info-card hidden><button class="info-close" type="button" data-close-card aria-label="Close attraction information"><i class="fa-solid fa-xmark"></i></button><img data-info-image alt="" hidden><div class="ar-info-copy"><span class="eyebrow eyebrow-dark">ON THIS POSTER</span><h2 class="h5" data-info-title></h2><p data-info-description></p><div class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-outline-success" data-map-link target="_blank" rel="noopener noreferrer" hidden><i class="fa-solid fa-map-location-dot me-1"></i>Map</a><button class="btn btn-sm btn-outline-success" data-video-button type="button" hidden><i class="fa-solid fa-play me-1"></i>Video</button><a class="btn btn-sm btn-success" data-details-link hidden>More details</a></div></div></article><dialog class="video-dialog" data-video-dialog><button class="btn btn-sm btn-light mb-2" type="button" data-video-close>Close video</button><div class="ratio ratio-16x9"><iframe data-video-frame title="Attraction video" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe></div></dialog><div class="scanner-bottom"><span>Powered by MindAR image tracking</span><button type="button" class="sound-button" data-mute aria-label="Mute video" aria-pressed="true"><i class="fa-solid fa-volume-xmark"></i></button></div><script type="application/json" data-config><?= json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script></section>
<script type="importmap">{"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js","three/addons/":"https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"}}</script>
<script type="module" src="<?= e(app_url('assets/js/ar-scan.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
