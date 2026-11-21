<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$systems = systems_with_counts($pdo);

$q = get_string('q');
$selectedSystem = get_int('system');
$systemNames = [];
foreach ($systems as $system) {
    $systemNames[(int) $system['id']] = $system['system_name'];
}
if (!isset($systemNames[$selectedSystem])) {
    $selectedSystem = 0;
}

$pageTitle = ($selectedSystem > 0 ? $systemNames[$selectedSystem] . ' system | ' : '') . 'Anatomy | ' . APP_NAME;
$metaDescription = 'Body parts and organs grouped by body system, with the species that have them and the diseases that affect them.';

$where = ["bp.review_status <> 'draft'"];
$params = [];
if ($q !== '') {
    $like = like_term($q);
    $where[] = '(bp.part_name LIKE :q1 OR bp.description LIKE :q2)';
    $params += ['q1' => $like, 'q2' => $like];
}
if ($selectedSystem > 0) {
    $where[] = 'bp.system_id = :system_id';
    $params['system_id'] = $selectedSystem;
}

$parts = run_query(
    $pdo,
    "SELECT bp.id, bp.part_name, bp.description, bp.uberon_id, bs.system_name,
            (SELECT COUNT(*) FROM animal_body_parts x
               INNER JOIN animals a ON a.id = x.animal_id
              WHERE x.body_part_id = bp.id AND a.review_status <> 'draft') AS animal_count,
            (SELECT COUNT(*) FROM disease_body_parts y
               INNER JOIN diseases d ON d.id = y.disease_id
              WHERE y.body_part_id = bp.id AND d.review_status <> 'draft') AS disease_count
     FROM body_parts bp
     INNER JOIN body_systems bs ON bs.id = bp.system_id
     WHERE " . implode(' AND ', $where) . '
     ORDER BY bs.sort_order, bs.system_name, bp.part_name',
    $params
)->fetchAll();

$grouped = [];
foreach ($parts as $part) {
    $grouped[$part['system_name']][] = $part;
}

// One pill per body system (keeps the search text).
$pills = [[
    'label' => 'All systems',
    'url' => page_url('body-parts.php', ['q' => $q]),
    'active' => $selectedSystem === 0,
    'count' => array_sum(array_map(static fn (array $s): int => (int) $s['part_count'], $systems)),
]];
foreach ($systems as $system) {
    $pills[] = [
        'label' => $system['system_name'],
        'url' => page_url('body-parts.php', ['q' => $q, 'system' => (int) $system['id']]),
        'active' => $selectedSystem === (int) $system['id'],
        'count' => (int) $system['part_count'],
    ];
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Anatomy</li>
        </ol>
    </nav>

    <h1 class="section-heading mb-1">Anatomy</h1>
    <p class="text-muted">Organs and body parts, grouped by body system. Open one to see which species have it, how it differs between species, and which diseases affect it.</p>

    <?= render_pills($pills, 'Filter body parts by body system') ?>

    <form method="get" class="panel p-3 my-3">
        <?php if ($selectedSystem > 0): ?><input type="hidden" name="system" value="<?= $selectedSystem ?>"><?php endif; ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label for="q" class="form-label small mb-1">Search body parts</label>
                <input type="search" id="q" name="q" class="form-control" value="<?= e($q) ?>" maxlength="100" placeholder="e.g. liver, stomach">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-success flex-grow-1" type="submit">Search</button>
                <a class="btn btn-outline-secondary" href="body-parts.php">Clear</a>
            </div>
        </div>
    </form>

    <?php foreach ($grouped as $systemName => $systemParts): ?>
        <section class="mb-5">
            <h2 class="system-heading h4"><?= e($systemName) ?></h2>
            <div class="row g-3">
                <?php foreach ($systemParts as $part): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card entity-card h-100">
                            <div class="card-body">
                                <h3 class="h5 card-title"><?= e($part['part_name']) ?></h3>
                                <p class="small text-muted"><?= e(excerpt($part['description'], 120)) ?></p>
                                <p class="small mb-2">
                                    <span class="chip chip-muted"><?= (int) $part['animal_count'] ?> species</span>
                                    <span class="chip chip-muted"><?= (int) $part['disease_count'] ?> disease<?= (int) $part['disease_count'] === 1 ? '' : 's' ?></span>
                                </p>
                                <a href="body-part-details.php?id=<?= (int) $part['id'] ?>" class="stretched-link small">View details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if (!$grouped): ?>
        <div class="alert alert-warning">No body parts match these filters.</div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
