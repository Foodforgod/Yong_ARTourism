<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Discover more';
$activePage = 'home';
$destinations = [];
$attractions = [];
$databaseReady = true;
try {
    $destinations = db()->query("SELECT id, name, slug, short_description, cover_image, location FROM destinations WHERE status = 'active' ORDER BY featured DESC, name LIMIT 4")->fetchAll();
    $attractions = db()->query("SELECT a.name, a.slug, a.short_description, a.main_image, d.name AS destination_name FROM attractions a JOIN destinations d ON d.id = a.destination_id WHERE a.status = 'active' AND d.status = 'active' ORDER BY a.display_order, a.name LIMIT 3")->fetchAll();
} catch (Throwable) {
    $databaseReady = false;
    $destinations = [
        ['name' => 'Old Quarter', 'slug' => 'old-quarter', 'short_description' => 'Historic streets, independent makers, and local stories.', 'cover_image' => 'assets/images/old-quarter.jpg', 'location' => 'Central District'],
        ['name' => 'Green Valley', 'slug' => 'green-valley', 'short_description' => 'Open trails and quiet viewpoints in a greener landscape.', 'cover_image' => 'assets/images/green-valley.jpg', 'location' => 'North Region'],
        ['name' => 'Makers Village', 'slug' => 'makers-village', 'short_description' => 'Meet local makers and discover living traditions.', 'cover_image' => 'assets/images/makers-village.jpg', 'location' => 'East Region'],
    ];
    $attractions = [
        ['name' => 'Founders House', 'slug' => 'founders-house', 'short_description' => 'A restored home with stories from the district’s early days.', 'main_image' => 'assets/images/founders-house.jpg', 'destination_name' => 'Old Quarter'],
        ['name' => 'Cloudline Lookout', 'slug' => 'cloudline-lookout', 'short_description' => 'A broad valley view reached by a gentle ridge trail.', 'main_image' => 'assets/images/cloudline-lookout.jpg', 'destination_name' => 'Green Valley'],
        ['name' => 'Harbor Market', 'slug' => 'harbor-market', 'short_description' => 'Seasonal produce and coastal cooking in a lively market.', 'main_image' => 'assets/images/harbor-market.jpg', 'destination_name' => 'Coastal Table'],
    ];
}
require __DIR__ . '/includes/header.php';
?>
<section class="hero-section">
    <div class="hero-shade"></div>
    <div class="container hero-content">
        <p class="eyebrow"><span></span> A new way to find your way in</p>
        <h1>Travel deeper.<br><em>See the story.</em></h1>
        <p class="hero-copy">Meet the places, people, and traditions behind every destination. Scan a local poster to bring its stories into view.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
            <a class="btn btn-lime btn-lg" href="<?= e(app_url('ar.php')) ?>"><i class="fa-solid fa-camera me-2"></i>Start AR experience</a>
            <a class="btn btn-outline-light btn-lg" href="<?= e(app_url('destinations.php')) ?>">Explore destinations <i class="fa-solid fa-arrow-right ms-2"></i></a>
        </div>
        <div class="hero-note"><i class="fa-solid fa-compass"></i><span>Local places, told by the people who know them</span></div>
    </div>
    <div class="hero-index"><span>01</span><span class="index-line"></span><span>EXPLORE</span></div>
</section>
<?php if (!$databaseReady): ?>
<div class="setup-strip"><div class="container"><i class="fa-solid fa-circle-info me-2"></i>Showing preview destinations. Import <code>database/database.sql</code> and configure your database to enable live content. <a href="<?= e(app_url('README.md')) ?>">Setup guide</a></div></div>
<?php endif; ?>
<section class="section-block destinations-section">
    <div class="container">
        <div class="section-heading d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div><p class="eyebrow eyebrow-dark">CURATED FOR THE CURIOUS</p><h2>Find your next <em>somewhere.</em></h2></div>
            <a class="text-link" href="<?= e(app_url('destinations.php')) ?>">All destinations <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="row g-4 mt-2">
            <?php foreach ($destinations as $index => $destination): ?>
            <div class="col-md-6 col-xl-4">
                <a class="destination-card" href="<?= e(app_url('destination.php?slug=' . urlencode($destination['slug']))) ?>">
                    <div class="destination-image"><img src="<?= e($destination['cover_image'] ?: '') ?>" alt="View of <?= e($destination['name']) ?>" loading="lazy"><span class="card-number">0<?= $index + 1 ?></span><span class="card-pin"><i class="fa-solid fa-arrow-up-right-from-square"></i></span></div>
                    <div class="destination-meta"><span><i class="fa-solid fa-location-dot"></i> <?= e($destination['location'] ?: 'Discover nearby') ?></span><span>DESTINATION</span></div>
                    <h3><?= e($destination['name']) ?></h3><p><?= e($destination['short_description']) ?></p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="story-band">
    <div class="container story-inner">
        <div class="story-copy"><p class="eyebrow">POINT. SCAN. DISCOVER.</p><h2>Let the poster<br>tell you <em>more.</em></h2><p>Bring a printed tourism poster to life with nearby stories, attraction details, and useful links, right in your mobile browser.</p><a class="btn btn-lime" href="<?= e(app_url('ar.php')) ?>">Enter AR mode <i class="fa-solid fa-arrow-right ms-2"></i></a></div>
        <div class="scan-visual" aria-hidden="true"><div class="scan-frame"><div class="scan-corner top-left"></div><div class="scan-corner top-right"></div><div class="scan-corner bottom-left"></div><div class="scan-corner bottom-right"></div><i class="fa-solid fa-landmark"></i><span>HERITAGE DISTRICT</span><div class="scan-beam"></div></div><div class="scan-label"><i class="fa-solid fa-camera"></i> AR IMAGE TRACKING</div></div>
    </div>
</section>
<section class="section-block attractions-section">
    <div class="container">
        <div class="section-heading"><p class="eyebrow eyebrow-dark">A CLOSER LOOK</p><h2>Worth the <em>detour.</em></h2></div>
        <div class="row g-4 mt-2">
            <?php foreach ($attractions as $attraction): ?>
            <div class="col-md-6 col-lg-4"><a class="attraction-card" href="<?= e(app_url('attraction.php?slug=' . urlencode($attraction['slug']))) ?>"><img src="<?= e($attraction['main_image'] ?: '') ?>" alt="<?= e($attraction['name']) ?>" loading="lazy"><div class="attraction-caption"><span><?= e($attraction['destination_name']) ?></span><h3><?= e($attraction['name']) ?></h3><p><?= e($attraction['short_description']) ?></p></div></a></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="closing-band"><div class="container closing-inner"><div><p class="eyebrow">YOUR NEXT STORY IS OUT THERE</p><h2>Go beyond the postcard.</h2></div><a href="<?= e(app_url('ar.php')) ?>" class="btn btn-lime btn-lg"><i class="fa-solid fa-vr-cardboard me-2"></i>Start AR</a></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
