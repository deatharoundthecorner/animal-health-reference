<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$categories = $pdo->query('SELECT id, category_name FROM categories ORDER BY category_name ASC')->fetchAll();

$q = get_string('q');
$selectedCategory = get_int('category');
$selectedType = get_string('type');
if (!isset(ANIMAL_TYPES[$selectedType])) {
    $selectedType = '';
}
$threatenedOnly = get_string('threatened') === '1';

$kindCounts = count_animals_by_kind($pdo);
$threatenedCount = count_threatened_animals($pdo);
$kindLabels = ['companion' => 'Companion animals', 'livestock' => 'Livestock', 'wildlife' => 'Wildlife', 'other' => 'Other animals'];

$heading = $threatenedOnly ? 'Endangered animals' : ($selectedType !== '' ? $kindLabels[$selectedType] : 'Animals');
$pageTitle = $heading . ' | ' . APP_NAME;

$where = ["a.review_status <> 'draft'"];
$params = [];

if ($q !== '') {
    $like = like_term($q);
    $where[] = '(a.common_name LIKE :q1 OR a.scientific_name LIKE :q2 OR a.family_name LIKE :q3 OR a.order_name LIKE :q4)';
    $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
}
if ($selectedCategory > 0) {
    $where[] = 'a.category_id = :category_id';
    $params['category_id'] = $selectedCategory;
}
if ($selectedType !== '') {
    $where[] = 'a.animal_type = :animal_type';
    $params['animal_type'] = $selectedType;
}
if ($threatenedOnly) {
    $where[] = 'a.conservation_status LIKE :threatened';
    $params['threatened'] = '%Endangered%';
}
$whereSql = implode(' AND ', $where);

$total = (int) run_query($pdo, "SELECT COUNT(*) FROM animals a WHERE {$whereSql}", $params)->fetchColumn();
$perPage = 12;
$pager = paginate($total, $perPage, get_int('page'));

$animals = run_query(
    $pdo,
    "SELECT a.id, a.common_name, a.scientific_name, a.animal_type, a.conservation_status, a.image, c.category_name,
            (SELECT COUNT(*) FROM animal_body_parts x
               INNER JOIN body_parts bp ON bp.id = x.body_part_id
              WHERE x.animal_id = a.id AND bp.review_status <> 'draft') AS part_count,
            (SELECT COUNT(*) FROM animal_diseases y
               INNER JOIN diseases d ON d.id = y.disease_id
              WHERE y.animal_id = a.id AND d.review_status <> 'draft') AS disease_count
     FROM animals a
     INNER JOIN categories c ON c.id = a.category_id
     WHERE {$whereSql}
     ORDER BY a.common_name ASC
     LIMIT :limit OFFSET :offset",
    $params + ['limit' => $perPage, 'offset' => $pager['offset']]
)->fetchAll();

// Quick-filter pills keep the search text and group, and switch the kind.
$keep = ['q' => $q, 'category' => $selectedCategory];
$pills = [[
    'label' => 'All animals',
    'url' => page_url('animals.php', $keep),
    'active' => $selectedType === '' && !$threatenedOnly,
    'count' => array_sum($kindCounts),
]];
foreach ($kindLabels as $kind => $label) {
    if ($kindCounts[$kind] > 0) {
        $pills[] = [
            'label' => $label,
            'url' => page_url('animals.php', $keep + ['type' => $kind]),
            'active' => $selectedType === $kind && !$threatenedOnly,
            'count' => $kindCounts[$kind],
        ];
    }
}
$pills[] = [
    'label' => 'Endangered',
    'url' => page_url('animals.php', $keep + ['threatened' => 1]),
    'active' => $threatenedOnly,
    'count' => $threatenedCount,
];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Animals</li>
        </ol>
    </nav>

    <h1 class="section-heading mb-3"><?= e($heading) ?></h1>
    <?= render_pills($pills, 'Filter animals by kind') ?>

    <?php if ($threatenedOnly): ?>
        <p class="text-muted small mb-3">
            Conservation statuses change as species are reassessed. Always check the current
            <a href="https://www.iucnredlist.org/" target="_blank" rel="noopener noreferrer">IUCN Red List</a> before relying on a status shown here.
        </p>
    <?php endif; ?>

    <form method="get" class="panel p-3 my-3">
        <?php if ($selectedType !== ''): ?><input type="hidden" name="type" value="<?= e($selectedType) ?>"><?php endif; ?>
        <?php if ($threatenedOnly): ?><input type="hidden" name="threatened" value="1"><?php endif; ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label for="q" class="form-label small mb-1">Name, order or family</label>
                <input type="search" id="q" name="q" class="form-control" value="<?= e($q) ?>" maxlength="100" placeholder="e.g. dog, Bovidae">
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label small mb-1">Group</label>
                <select id="category" name="category" class="form-select">
                    <option value="0">All groups</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $selectedCategory === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-success flex-grow-1" type="submit">Search</button>
                <a class="btn btn-outline-secondary" href="animals.php" title="Clear all filters">Clear</a>
            </div>
        </div>
    </form>

    <p class="text-muted small"><?= $total ?> animal<?= $total === 1 ? '' : 's' ?> found</p>

    <div class="row g-4">
        <?php foreach ($animals as $animal): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card animal-card h-100">
                    <img src="<?= e(animal_image($animal['image'])) ?>" class="animal-thumb" alt="<?= e($animal['common_name']) ?>" loading="lazy">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge text-bg-light border"><?= e(ANIMAL_TYPES[$animal['animal_type']] ?? $animal['animal_type']) ?></span>
                            <?= conservation_badge($animal['conservation_status']) ?>
                        </div>
                        <h2 class="h5 card-title mb-1"><?= e($animal['common_name']) ?></h2>
                        <p class="text-muted fst-italic mb-2"><?= e($animal['scientific_name']) ?></p>
                        <p class="small text-muted mb-3">
                            <?= e($animal['category_name']) ?> ·
                            <?= (int) $animal['part_count'] ?> body parts ·
                            <?= (int) $animal['disease_count'] ?> disease<?= (int) $animal['disease_count'] === 1 ? '' : 's' ?>
                        </p>
                        <a href="animal-details.php?id=<?= (int) $animal['id'] ?>" class="btn btn-outline-success mt-auto stretched-link">View details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$animals): ?>
            <div class="col-12"><div class="alert alert-warning mb-0">No animals match these filters.</div></div>
        <?php endif; ?>
    </div>

    <?= render_pagination($pager['page'], $pager['totalPages'], ['q' => $q, 'category' => $selectedCategory, 'type' => $selectedType, 'threatened' => $threatenedOnly ? '1' : ''], 'Animal pagination') ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
