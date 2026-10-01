<?php
require_once __DIR__ . '/includes/functions.php';
function youtube_embed(string $url): ?string
{
    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');
    $videoId = '';
    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        $videoId = trim($parts['path'] ?? '', '/');
    } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
        parse_str($parts['query'] ?? '', $query);
        $videoId = (string) ($query['v'] ?? '');
        if ($videoId === '' && preg_match('#^/(?:embed|shorts)/([^/]+)#', $parts['path'] ?? '', $matches)) {
            $videoId = $matches[1];
        }
    }
    return preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) ? 'https://www.youtube-nocookie.com/embed/' . $videoId : null;
}
$statement = db()->prepare("SELECT a.*, d.name AS destination_name, d.slug AS destination_slug, c.name AS category_name FROM attractions a JOIN destinations d ON d.id = a.destination_id LEFT JOIN categories c ON c.id = a.category_id WHERE a.slug = ? AND a.status = 'active' AND d.status = 'active' LIMIT 1");
$statement->execute([trim((string) ($_GET['slug'] ?? ''))]);
$attraction = $statement->fetch();
if (!$attraction) {
    http_response_code(404);
}
$images = [];
if ($attraction) {
    $statement = db()->prepare('SELECT image_path, alt_text FROM attraction_images WHERE attraction_id = ? ORDER BY display_order, id');
    $statement->execute([$attraction['id']]);
    $images = $statement->fetchAll();
}
$pageTitle = $attraction ? $attraction['name'] : 'Attraction not found';
$videoEmbed = $attraction && $attraction['youtube_url'] ? youtube_embed($attraction['youtube_url']) : null;
require __DIR__ . '/includes/header.php';
?>
<section class="container py-5"><?php if (!$attraction): ?><h1 class="h2">Attraction not found</h1><a href="<?= e(app_url('attractions.php')) ?>">Browse attractions</a><?php else: ?><p class="eyebrow eyebrow-dark"><a href="<?= e(app_url('destination.php?slug=' . urlencode($attraction['destination_slug']))) ?>"><?= e($attraction['destination_name']) ?></a> · <?= e($attraction['category_name'] ?? 'ATTRACTION') ?></p><h1 class="display-5 fw-bold"><?= e($attraction['name']) ?></h1><p class="lead col-lg-8"><?= e($attraction['short_description']) ?></p><?php if ($attraction['main_image']): ?><img class="detail-cover" src="<?= e($attraction['main_image']) ?>" alt="<?= e($attraction['name']) ?>" loading="lazy"><?php endif; ?><div class="row g-5 mt-2"><article class="col-lg-7"><p class="detail-copy"><?= nl2br(e($attraction['description'] ?? '')) ?></p><?php if ($attraction['opening_hours']): ?><p><strong>Opening hours:</strong> <?= e($attraction['opening_hours']) ?></p><?php endif; ?><?php if ($attraction['entry_information']): ?><p><strong>Entry:</strong> <?= e($attraction['entry_information']) ?></p><?php endif; ?><div class="d-flex gap-2 flex-wrap mt-4"><?php if ($attraction['map_url']): ?><a class="btn btn-success" href="<?= e($attraction['map_url']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-map-location-dot me-2"></i>View on map</a><?php endif; ?><?php if ($attraction['website_url'] && filter_var($attraction['website_url'], FILTER_VALIDATE_URL)): ?><a class="btn btn-outline-success" href="<?= e($attraction['website_url']) ?>" target="_blank" rel="noopener noreferrer">Official website</a><?php endif; ?></div></article><div class="col-lg-5"><?php if ($videoEmbed): ?><div class="ratio ratio-16x9"><iframe src="<?= e($videoEmbed) ?>" title="Video about <?= e($attraction['name']) ?>" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div><?php endif; ?><?php foreach ($images as $image): ?><img class="gallery-image mt-3" src="<?= e($image['image_path']) ?>" alt="<?= e($image['alt_text']) ?>" loading="lazy"><?php endforeach; ?></div></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
