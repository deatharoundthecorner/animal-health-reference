<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$metaDescription = 'Animal diseases with their causes, signs, affected species and organs, transmission and prevention.';

$animalOptions = $pdo->query("SELECT id, common_name FROM animals WHERE review_status <> 'draft' ORDER BY common_name")->fetchAll();
$partOptions = $pdo->query("SELECT id, part_name FROM body_parts WHERE review_status <> 'draft' ORDER BY part_name")->fetchAll();
$symptomOptions = $pdo->query('SELECT id, symptom_name FROM symptoms ORDER BY symptom_name')->fetchAll();

$q = get_string('q');
$pathogen = get_string('pathogen');
if (!isset(PATHOGEN_TYPES[$pathogen])) {
    $pathogen = '';
}
$zoonotic = get_string('zoonotic') === '1';
$woah = get_string('woah') === '1';
$animalId = get_int('animal');
$partId = get_int('part');
$symptomId = get_int('symptom');

$causeCounts = count_diseases_by_cause($pdo);
$collections = count_disease_collections($pdo);

$heading = 'Diseases';
if ($zoonotic && !$woah && $pathogen === '') {
    $heading = 'Zoonotic diseases';
} elseif ($woah && !$zoonotic && $pathogen === '') {
    $heading = 'WOAH-listed diseases';
} elseif ($pathogen !== '' && !$zoonotic && !$woah) {
    $heading = PATHOGEN_TYPES[$pathogen] . ' diseases';
}
$pageTitle = $heading . ' | ' . APP_NAME;

$where = ["d.review_status <> 'draft'"];
$params = [];

if ($q !== '') {
    $like = like_term($q);
    $where[] = '(d.disease_name LIKE :q1 OR d.pathogen_name LIKE :q2 OR d.summary LIKE :q3)';
    $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
}
if ($pathogen !== '') {
    $where[] = 'd.pathogen_type = :pathogen';
    $params['pathogen'] = $pathogen;
}
if ($zoonotic) {
    $where[] = 'd.is_zoonotic = 1';
}
if ($woah) {
    $where[] = 'd.woah_listed = 1';
}
if ($animalId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM animal_diseases f1 WHERE f1.disease_id = d.id AND f1.animal_id = :animal_id)';
    $params['animal_id'] = $animalId;
}
if ($partId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM disease_body_parts f2 WHERE f2.disease_id = d.id AND f2.body_part_id = :part_id)';
    $params['part_id'] = $partId;
}
if ($symptomId > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM disease_symptoms f3 WHERE f3.disease_id = d.id AND f3.symptom_id = :symptom_id)';
    $params['symptom_id'] = $symptomId;
}
$whereSql = implode(' AND ', $where);

$total = (int) run_query($pdo, "SELECT COUNT(*) FROM diseases d WHERE {$whereSql}", $params)->fetchColumn();
$perPage = 12;
$pager = paginate($total, $perPage, get_int('page'));

$diseases = run_query(
    $pdo,
    "SELECT d.id, d.disease_name, d.pathogen_type, d.pathogen_name, d.is_zoonotic, d.woah_listed, d.summary,
            (SELECT GROUP_CONCAT(a.common_name ORDER BY a.common_name SEPARATOR ', ')
               FROM animal_diseases ad
               INNER JOIN animals a ON a.id = ad.animal_id
              WHERE ad.disease_id = d.id AND a.review_status <> 'draft') AS species_list
     FROM diseases d
     WHERE {$whereSql}
     ORDER BY d.disease_name ASC
     LIMIT :limit OFFSET :offset",
    $params + ['limit' => $perPage, 'offset' => $pager['offset']]
)->fetchAll();

$filters = [
    'q' => $q, 'pathogen' => $pathogen, 'zoonotic' => $zoonotic ? '1' : '', 'woah' => $woah ? '1' : '',
    'animal' => $animalId, 'part' => $partId, 'symptom' => $symptomId,
];

// Cause pills switch only the cause; the other filters are kept.
$withoutCause = $filters;
unset($withoutCause['pathogen']);
$causePills = [[
    'label' => 'All causes',
    'url' => page_url('diseases.php', $withoutCause),
    'active' => $pathogen === '',
    'count' => array_sum($causeCounts),
]];
foreach (PATHOGEN_TYPES as $type => $label) {
    if ($causeCounts[$type] > 0) {
        $causePills[] = [
            'label' => $label,
            'url' => page_url('diseases.php', $withoutCause + ['pathogen' => $type]),
            'active' => $pathogen === $type,
            'count' => $causeCounts[$type],
        ];
    }
}

// Collection pills switch on and off independently.
$collectionPills = [
    [
        'label' => 'Zoonotic',
        'url' => page_url('diseases.php', array_merge($filters, ['zoonotic' => $zoonotic ? '' : '1'])),
        'active' => $zoonotic,
        'count' => $collections['zoonotic'],
    ],
    [
        'label' => 'WOAH-listed',
        'url' => page_url('diseases.php', array_merge($filters, ['woah' => $woah ? '' : '1'])),
        'active' => $woah,
        'count' => $collections['woah'],
    ],
];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Diseases</li>
        </ol>
    </nav>

    <h1 class="section-heading mb-1"><?= e($heading) ?></h1>
    <p class="text-muted">Reference entries for animal diseases. For several signs at once, try the <a href="symptoms.php">symptom lookup</a>.</p>

    <?= render_pills($causePills, 'Filter diseases by cause') ?>
    <?= render_pills($collectionPills, 'Filter diseases by collection') ?>

    <form method="get" class="panel p-3 my-3">
        <?php if ($pathogen !== ''): ?><input type="hidden" name="pathogen" value="<?= e($pathogen) ?>"><?php endif; ?>
        <?php if ($zoonotic): ?><input type="hidden" name="zoonotic" value="1"><?php endif; ?>
        <?php if ($woah): ?><input type="hidden" name="woah" value="1"><?php endif; ?>
        <div class="row g-2">
            <div class="col-md-3">
                <label for="q" class="form-label small mb-1">Name, pathogen or description</label>
                <input type="search" id="q" name="q" class="form-control" value="<?= e($q) ?>" maxlength="100" placeholder="e.g. parvovirus, tick">
            </div>
            <div class="col-md-3">
                <label for="animal" class="form-label small mb-1">Species affected</label>
                <select id="animal" name="animal" class="form-select">
                    <option value="0">Any species</option>
                    <?php foreach ($animalOptions as $option): ?>
                        <option value="<?= (int) $option['id'] ?>" <?= $animalId === (int) $option['id'] ? 'selected' : '' ?>><?= e($option['common_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="part" class="form-label small mb-1">Body part affected</label>
                <select id="part" name="part" class="form-select">
                    <option value="0">Any body part</option>
                    <?php foreach ($partOptions as $option): ?>
                        <option value="<?= (int) $option['id'] ?>" <?= $partId === (int) $option['id'] ? 'selected' : '' ?>><?= e($option['part_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="symptom" class="form-label small mb-1">Sign / symptom</label>
                <select id="symptom" name="symptom" class="form-select">
                    <option value="0">Any sign</option>
                    <?php foreach ($symptomOptions as $option): ?>
                        <option value="<?= (int) $option['id'] ?>" <?= $symptomId === (int) $option['id'] ? 'selected' : '' ?>><?= e($option['symptom_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-success flex-grow-1" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="diseases.php" title="Clear all filters">Clear</a>
            </div>
        </div>
    </form>

    <p class="text-muted small"><?= $total ?> disease<?= $total === 1 ? '' : 's' ?> found</p>

    <div class="table-responsive panel p-2">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Disease</th><th>Cause</th><th>Species</th><th>Flags</th></tr>
            </thead>
            <tbody>
                <?php foreach ($diseases as $disease): ?>
                    <tr>
                        <td>
                            <a class="fw-semibold" href="disease-details.php?id=<?= (int) $disease['id'] ?>"><?= e($disease['disease_name']) ?></a>
                            <div class="small text-muted"><?= e(excerpt($disease['summary'], 110)) ?></div>
                        </td>
                        <td class="text-nowrap">
                            <?= e(pathogen_label($disease['pathogen_type'])) ?>
                        </td>
                        <td class="small"><?= e((string) $disease['species_list']) ?></td>
                        <td><?= disease_flags($disease) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$diseases): ?>
                    <tr><td colspan="4" class="text-center py-4">No diseases match these filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= render_pagination($pager['page'], $pager['totalPages'], $filters, 'Disease pagination') ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
