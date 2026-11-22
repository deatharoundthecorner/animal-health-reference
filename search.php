<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/feeding.php';

$q = get_string('q');
$tooShort = $q !== '' && mb_strlen($q) < 2;
$pageTitle = ($q !== '' ? 'Search: ' . $q : 'Search') . ' | ' . APP_NAME;

$animals = $diseases = $parts = $symptoms = $feeds = [];
$limit = 20;

if ($q !== '' && !$tooShort) {
    $like = like_term($q);

    $animals = run_query(
        $pdo,
        "SELECT id, common_name, scientific_name, animal_type
         FROM animals
         WHERE review_status <> 'draft'
           AND (common_name LIKE :a1 OR scientific_name LIKE :a2 OR family_name LIKE :a3 OR order_name LIKE :a4 OR description LIKE :a5)
         ORDER BY common_name ASC
         LIMIT :limit",
        ['a1' => $like, 'a2' => $like, 'a3' => $like, 'a4' => $like, 'a5' => $like, 'limit' => $limit]
    )->fetchAll();

    $diseases = run_query(
        $pdo,
        "SELECT id, disease_name, pathogen_type, pathogen_name, is_zoonotic, woah_listed, summary
         FROM diseases
         WHERE review_status <> 'draft'
           AND (disease_name LIKE :d1 OR pathogen_name LIKE :d2 OR summary LIKE :d3)
         ORDER BY disease_name ASC
         LIMIT :limit",
        ['d1' => $like, 'd2' => $like, 'd3' => $like, 'limit' => $limit]
    )->fetchAll();

    $parts = run_query(
        $pdo,
        "SELECT bp.id, bp.part_name, bp.description, bs.system_name
         FROM body_parts bp
         INNER JOIN body_systems bs ON bs.id = bp.system_id
         WHERE bp.review_status <> 'draft'
           AND (bp.part_name LIKE :b1 OR bp.description LIKE :b2)
         ORDER BY bp.part_name ASC
         LIMIT :limit",
        ['b1' => $like, 'b2' => $like, 'limit' => $limit]
    )->fetchAll();

    $symptoms = run_query(
        $pdo,
        "SELECT s.id, s.symptom_name,
                (SELECT COUNT(*) FROM disease_symptoms ds
                   INNER JOIN diseases d ON d.id = ds.disease_id
                  WHERE ds.symptom_id = s.id AND d.review_status <> 'draft') AS disease_count
         FROM symptoms s
         WHERE s.symptom_name LIKE :s1
         ORDER BY s.symptom_name ASC
         LIMIT :limit",
        ['s1' => $like, 'limit' => $limit]
    )->fetchAll();

    if (feeding_available($pdo)) {
        $feeds = run_query(
            $pdo,
            "SELECT id, feed_name, scientific_name, feed_category, description
             FROM feeds
             WHERE review_status <> 'draft'
               AND (feed_name LIKE :f1 OR scientific_name LIKE :f2 OR local_name_si LIKE :f3 OR local_name_ta LIKE :f4 OR description LIKE :f5)
             ORDER BY feed_name ASC
             LIMIT :limit",
            ['f1' => $like, 'f2' => $like, 'f3' => $like, 'f4' => $like, 'f5' => $like, 'limit' => $limit]
        )->fetchAll();
    }
}

$totalResults = count($animals) + count($diseases) + count($parts) + count($symptoms) + count($feeds);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <h1 class="section-heading mb-3">Search</h1>

    <form method="get" class="panel p-3 mb-4" role="search">
        <div class="row g-2">
            <div class="col">
                <label for="q" class="visually-hidden">Search term</label>
                <input type="search" id="q" name="q" class="form-control form-control-lg" value="<?= e($q) ?>" maxlength="100" placeholder="Search animals, diseases, organs and signs…" autofocus>
            </div>
            <div class="col-auto">
                <button class="btn btn-success btn-lg" type="submit">Search</button>
            </div>
        </div>
    </form>

    <?php if ($tooShort): ?>
        <div class="alert alert-warning">Please type at least 2 characters.</div>
    <?php elseif ($q === ''): ?>
        <p class="text-muted">Search across animals, diseases, body parts and signs. Try “parvovirus”, “rumen”, “horse” or “fever”.</p>
    <?php elseif ($totalResults === 0): ?>
        <div class="alert alert-info">No results for “<?= e($q) ?>”. Try a shorter or different term.</div>
    <?php else: ?>
        <p class="text-muted"><?= $totalResults ?> result<?= $totalResults === 1 ? '' : 's' ?> for “<?= e($q) ?>” (showing up to <?= $limit ?> per section)</p>

        <?php if ($diseases): ?>
            <section class="mb-4">
                <h2 class="h4 section-heading">Diseases <span class="badge text-bg-secondary"><?= count($diseases) ?></span></h2>
                <div class="list-group">
                    <?php foreach ($diseases as $disease): ?>
                        <a class="list-group-item list-group-item-action" href="disease-details.php?id=<?= (int) $disease['id'] ?>">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <strong><?= e($disease['disease_name']) ?></strong>
                                <span><?= e(pathogen_label($disease['pathogen_type'])) ?> <?= disease_flags($disease) ?></span>
                            </div>
                            <div class="small text-muted"><?= e(excerpt($disease['summary'], 150)) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($animals): ?>
            <section class="mb-4">
                <h2 class="h4 section-heading">Animals <span class="badge text-bg-secondary"><?= count($animals) ?></span></h2>
                <div class="list-group">
                    <?php foreach ($animals as $animal): ?>
                        <a class="list-group-item list-group-item-action" href="animal-details.php?id=<?= (int) $animal['id'] ?>">
                            <strong><?= e($animal['common_name']) ?></strong>
                            <span class="fst-italic text-muted ms-2"><?= e($animal['scientific_name']) ?></span>
                            <span class="badge text-bg-light border ms-2"><?= e(ANIMAL_TYPES[$animal['animal_type']] ?? $animal['animal_type']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($parts): ?>
            <section class="mb-4">
                <h2 class="h4 section-heading">Body parts <span class="badge text-bg-secondary"><?= count($parts) ?></span></h2>
                <div class="list-group">
                    <?php foreach ($parts as $part): ?>
                        <a class="list-group-item list-group-item-action" href="body-part-details.php?id=<?= (int) $part['id'] ?>">
                            <strong><?= e($part['part_name']) ?></strong>
                            <span class="text-muted small ms-2"><?= e($part['system_name']) ?></span>
                            <div class="small text-muted"><?= e(excerpt($part['description'], 130)) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($feeds): ?>
            <section class="mb-4">
                <h2 class="h4 section-heading">Feeds <span class="badge text-bg-secondary"><?= count($feeds) ?></span></h2>
                <div class="list-group">
                    <?php foreach ($feeds as $feed): ?>
                        <a class="list-group-item list-group-item-action" href="feed-details.php?id=<?= (int) $feed['id'] ?>">
                            <strong><?= e($feed['feed_name']) ?></strong>
                            <span class="badge text-bg-light border ms-2"><?= e(FEED_CATEGORIES[$feed['feed_category']] ?? $feed['feed_category']) ?></span>
                            <div class="small text-muted"><?= e(excerpt($feed['description'], 130)) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($symptoms): ?>
            <section class="mb-4">
                <h2 class="h4 section-heading">Signs and symptoms <span class="badge text-bg-secondary"><?= count($symptoms) ?></span></h2>
                <div class="list-group">
                    <?php foreach ($symptoms as $symptom): ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between" href="diseases.php?symptom=<?= (int) $symptom['id'] ?>">
                            <span><?= e($symptom['symptom_name']) ?></span>
                            <span class="text-muted small"><?= (int) $symptom['disease_count'] ?> disease<?= (int) $symptom['disease_count'] === 1 ? '' : 's' ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
