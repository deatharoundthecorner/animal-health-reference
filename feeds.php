<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/feeding.php';

$pageTitle = 'Feed types guide | ' . APP_NAME;
$metaDescription = 'Feeds for farm and companion animals in Sri Lanka: fodder grasses, tree leaves, concentrates, pet foods and supplements, with nutrient values, availability and cautions.';

if (!feeding_available($pdo)) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<main id="main" class="container py-5"><div class="alert alert-info">The feeding guide is not installed on this site yet. An administrator can add it with <code>php tools/upgrade.php</code>.</div></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$q = get_string('q');
$category = get_string('category');
if (!isset(FEED_CATEGORIES[$category])) {
    $category = '';
}

$counts = count_feeds_by_category($pdo);

$where = ["f.review_status <> 'draft'"];
$params = [];
if ($category !== '') {
    $where[] = 'f.feed_category = :category';
    $params['category'] = $category;
}
if ($q !== '') {
    $like = like_term($q);
    $where[] = '(f.feed_name LIKE :q1 OR f.scientific_name LIKE :q2 OR f.local_name_si LIKE :q3 OR f.local_name_ta LIKE :q4 OR f.description LIKE :q5)';
    $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like];
}

$feeds = run_query(
    $pdo,
    "SELECT f.*,
            (SELECT COUNT(DISTINCT i.plan_id)
               FROM feeding_plan_items i
               INNER JOIN feeding_plans p ON p.id = i.plan_id
              WHERE i.feed_id = f.id AND p.review_status <> 'draft') AS plan_count
     FROM feeds f
     WHERE " . implode(' AND ', $where) . "
     ORDER BY FIELD(f.feed_category, 'green_forage', 'dry_forage', 'concentrate', 'complete_feed', 'kitchen_food', 'supplement'), f.feed_name",
    $params
)->fetchAll();

$grouped = [];
foreach ($feeds as $feed) {
    $grouped[$feed['feed_category']][] = $feed;
}

$pills = [[
    'label' => 'All feeds',
    'url' => page_url('feeds.php', ['q' => $q]),
    'active' => $category === '',
    'count' => array_sum($counts),
]];
foreach (FEED_CATEGORIES as $key => $label) {
    if (($counts[$key] ?? 0) > 0) {
        $pills[] = ['label' => $label, 'url' => page_url('feeds.php', ['q' => $q, 'category' => $key]), 'active' => $category === $key, 'count' => $counts[$key]];
    }
}

/** A short line of nutrient facts for a card. */
function feed_facts(array $feed): string
{
    $facts = [];
    if ($feed['dry_matter_percent'] !== null) {
        $facts[] = 'dry matter ' . rtrim(rtrim(number_format((float) $feed['dry_matter_percent'], 1, '.', ''), '0'), '.') . '%';
    }
    if ($feed['crude_protein_percent'] !== null) {
        $facts[] = 'protein ' . rtrim(rtrim(number_format((float) $feed['crude_protein_percent'], 1, '.', ''), '0'), '.') . '% of DM';
    }
    if ($feed['me_mj_per_kg_dm'] !== null) {
        $facts[] = rtrim(rtrim(number_format((float) $feed['me_mj_per_kg_dm'], 1, '.', ''), '0'), '.') . ' MJ/kg DM';
    }
    if ($feed['energy_kcal_per_kg'] !== null) {
        $facts[] = 'about ' . number_format((int) $feed['energy_kcal_per_kg'], 0, '.', ',') . ' kcal/kg';
    }
    return implode(' · ', $facts);
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Feed types</li>
        </ol>
    </nav>

    <h1 class="section-heading mb-1">Feed types guide</h1>
    <p class="text-muted">What each feed is, what it contains, where it is available in Sri Lanka, and what to watch out for. To find out how much to give, use the <a href="feeding.php">feeding calculator</a>.</p>

    <?= render_pills($pills, 'Filter feeds by category') ?>

    <form method="get" class="panel p-3 my-3">
        <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label for="q" class="form-label small mb-1">Search feeds</label>
                <input type="search" id="q" name="q" class="form-control" value="<?= e($q) ?>" maxlength="100" placeholder="e.g. Gliricidia, rice, pellets">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-success flex-grow-1" type="submit">Search</button>
                <a class="btn btn-outline-secondary" href="feeds.php">Clear</a>
            </div>
        </div>
    </form>

    <?php foreach ($grouped as $key => $list): ?>
        <section class="mb-5">
            <h2 class="system-heading h4"><?= e(FEED_CATEGORIES[$key] ?? $key) ?></h2>
            <div class="row g-3">
                <?php foreach ($list as $feed): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card entity-card h-100">
                            <div class="card-body">
                                <h3 class="h5 card-title mb-1"><?= e($feed['feed_name']) ?></h3>
                                <?php if (!empty($feed['scientific_name'])): ?><p class="fst-italic text-muted small mb-1"><?= e($feed['scientific_name']) ?></p><?php endif; ?>
                                <?php $facts = feed_facts($feed); ?>
                                <?php if ($facts !== ''): ?><p class="small mb-2"><span class="chip chip-muted"><?= e($facts) ?></span></p><?php endif; ?>
                                <p class="small text-muted"><?= e(excerpt($feed['description'], 150)) ?></p>
                                <?php if ((int) $feed['plan_count'] > 0): ?><p class="small mb-2">Used in <?= (int) $feed['plan_count'] ?> feeding plan<?= (int) $feed['plan_count'] === 1 ? '' : 's' ?></p><?php endif; ?>
                                <a href="feed-details.php?id=<?= (int) $feed['id'] ?>" class="stretched-link small">View details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if (!$grouped): ?>
        <div class="alert alert-warning">No feeds match these filters.</div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
