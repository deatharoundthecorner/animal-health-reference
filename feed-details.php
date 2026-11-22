<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/feeding.php';

if (!feeding_available($pdo)) {
    header('Location: feeds.php');
    exit;
}

$id = get_int('id');
if ($id <= 0) {
    header('Location: feeds.php');
    exit;
}

$feed = run_query($pdo, "SELECT * FROM feeds WHERE id = :id AND review_status <> 'draft' LIMIT 1", ['id' => $id])->fetch();

if (!$feed) {
    http_response_code(404);
    $pageTitle = 'Feed not found | ' . APP_NAME;
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<main id="main" class="container py-5"><div class="alert alert-danger">Feed not found.</div><a href="feeds.php" class="btn btn-success">Back to feed types</a></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$plans = run_query(
    $pdo,
    "SELECT DISTINCT p.id, p.life_stage, a.id AS animal_id, a.common_name
     FROM feeding_plan_items i
     INNER JOIN feeding_plans p ON p.id = i.plan_id
     INNER JOIN animals a ON a.id = p.animal_id
     WHERE i.feed_id = :id AND p.review_status <> 'draft' AND a.review_status <> 'draft'
     ORDER BY a.common_name, p.sort_order, p.life_stage",
    ['id' => $id]
)->fetchAll();

$sources = fetch_sources($pdo, 'feed', $id);

$num = static fn ($v, int $d = 1): string => rtrim(rtrim(number_format((float) $v, $d, '.', ''), '0'), '.');

$pageTitle = $feed['feed_name'] . ' | Feed types | ' . APP_NAME;
$metaDescription = excerpt((string) $feed['description'], 160);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="feeds.php">Feed types</a></li>
            <li class="breadcrumb-item"><a href="<?= e(page_url('feeds.php', ['category' => $feed['feed_category']])) ?>"><?= e(FEED_CATEGORIES[$feed['feed_category']] ?? $feed['feed_category']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($feed['feed_name']) ?></li>
        </ol>
    </nav>

    <div class="details-box p-4 p-lg-5 mb-4">
        <div class="mb-2">
            <span class="badge text-bg-light border"><?= e(FEED_CATEGORIES[$feed['feed_category']] ?? $feed['feed_category']) ?></span>
            <?= review_badge($feed['review_status']) ?>
        </div>
        <h1 class="section-heading h2 mb-1"><?= e($feed['feed_name']) ?></h1>
        <?php if (!empty($feed['scientific_name'])): ?><p class="fst-italic text-muted mb-1"><?= e($feed['scientific_name']) ?></p><?php endif; ?>
        <?php if (!empty($feed['local_name_si']) || !empty($feed['local_name_ta'])): ?>
            <p class="mb-2">
                <?php if (!empty($feed['local_name_si'])): ?><span class="chip">Sinhala: <?= e($feed['local_name_si']) ?></span><?php endif; ?>
                <?php if (!empty($feed['local_name_ta'])): ?><span class="chip">Tamil: <?= e($feed['local_name_ta']) ?></span><?php endif; ?>
            </p>
        <?php endif; ?>
        <p class="mb-0"><?= nl2br(e($feed['description'])) ?></p>
    </div>

    <?php if (!empty($feed['cautions'])): ?>
        <div class="alert alert-warning"><strong>Cautions.</strong> <?= nl2br(e($feed['cautions'])) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <?php $hasNutrients = $feed['dry_matter_percent'] !== null || $feed['crude_protein_percent'] !== null || $feed['me_mj_per_kg_dm'] !== null || $feed['energy_kcal_per_kg'] !== null; ?>
            <div class="panel p-4 mb-4">
                <h2 class="h5 section-heading">Nutrient values</h2>
                <?php if ($hasNutrients): ?>
                    <dl class="row fact-list mb-0">
                        <?php if ($feed['dry_matter_percent'] !== null): ?>
                            <dt class="col-sm-5">Dry matter (as fed)</dt><dd class="col-sm-7"><?= e($num($feed['dry_matter_percent'])) ?>% <span class="small text-muted">(the rest is water)</span></dd>
                        <?php endif; ?>
                        <?php if ($feed['crude_protein_percent'] !== null): ?>
                            <dt class="col-sm-5">Crude protein</dt><dd class="col-sm-7"><?= e($num($feed['crude_protein_percent'])) ?>% of dry matter</dd>
                        <?php endif; ?>
                        <?php if ($feed['me_mj_per_kg_dm'] !== null): ?>
                            <dt class="col-sm-5">Metabolizable energy</dt><dd class="col-sm-7"><?= e($num($feed['me_mj_per_kg_dm'])) ?> MJ per kg of dry matter</dd>
                        <?php endif; ?>
                        <?php if ($feed['energy_kcal_per_kg'] !== null): ?>
                            <dt class="col-sm-5">Energy (as fed)</dt><dd class="col-sm-7">about <?= e(number_format((int) $feed['energy_kcal_per_kg'], 0, '.', ',')) ?> kcal per kg <span class="small text-muted">(check the package of a manufactured food)</span></dd>
                        <?php endif; ?>
                    </dl>
                <?php else: ?>
                    <p class="text-muted mb-0">No nutrient values have been recorded for this feed. Composition varies with the source; ask the supplier for an analysis.</p>
                <?php endif; ?>
            </div>

            <div class="panel p-4 mb-4">
                <h2 class="h5 section-heading">Availability in Sri Lanka</h2>
                <p class="mb-0"><?= !empty($feed['availability_note']) ? nl2br(e($feed['availability_note'])) : '<span class="text-muted">Not recorded yet.</span>' ?></p>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel p-4 mb-4">
                <h2 class="h5 section-heading">Used in these feeding plans</h2>
                <?php if ($plans): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($plans as $plan): ?>
                            <li class="mb-2">
                                <a href="<?= e(page_url('feeding.php', ['animal' => (int) $plan['animal_id'], 'stage' => $plan['life_stage']])) ?>"><?= e($plan['common_name']) ?></a>
                                <span class="small text-muted">· <?= e($plan['life_stage']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">This feed is not part of any feeding plan yet.</p>
                <?php endif; ?>
            </div>

            <div class="panel p-4">
                <h2 class="h5 section-heading">Sources</h2>
                <?= render_sources($sources) ?>
            </div>
        </div>
    </div>

    <a href="feeds.php" class="btn btn-success mt-4">Back to feed types</a>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
