<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$count = static fn (string $sql): int => (int) $pdo->query($sql)->fetchColumn();

$totals = [
    'Animals' => $count('SELECT COUNT(*) FROM animals'),
    'Diseases' => $count('SELECT COUNT(*) FROM diseases'),
    'Body parts' => $count('SELECT COUNT(*) FROM body_parts'),
    'Sources' => $count('SELECT COUNT(*) FROM sources'),
];

$reviewRows = [];
foreach (['animals' => 'Animals', 'diseases' => 'Diseases', 'body_parts' => 'Body parts'] as $table => $label) {
    $row = $pdo->query(
        "SELECT SUM(review_status = 'draft') AS draft,
                SUM(review_status = 'unreviewed') AS unreviewed,
                SUM(review_status = 'reviewed') AS reviewed
         FROM {$table}"
    )->fetch();
    $reviewRows[$label] = array_map('intval', $row);
}

$gaps = [
    'Diseases with no source' => $count('SELECT COUNT(*) FROM diseases d WHERE NOT EXISTS (SELECT 1 FROM disease_sources x WHERE x.disease_id = d.id)'),
    'Body parts with no source' => $count('SELECT COUNT(*) FROM body_parts b WHERE NOT EXISTS (SELECT 1 FROM body_part_sources x WHERE x.body_part_id = b.id)'),
    'Animals with no source' => $count('SELECT COUNT(*) FROM animals a WHERE NOT EXISTS (SELECT 1 FROM animal_sources x WHERE x.animal_id = a.id)'),
    'Animals with no anatomy' => $count('SELECT COUNT(*) FROM animals a WHERE NOT EXISTS (SELECT 1 FROM animal_body_parts x WHERE x.animal_id = a.id)'),
    'Animals with no disease' => $count('SELECT COUNT(*) FROM animals a WHERE NOT EXISTS (SELECT 1 FROM animal_diseases x WHERE x.animal_id = a.id)'),
];

$needsReview = $pdo->query(
    "(SELECT 'animal' AS kind, id, common_name AS name, review_status, updated_at FROM animals WHERE review_status <> 'reviewed')
     UNION ALL
     (SELECT 'disease', id, disease_name, review_status, updated_at FROM diseases WHERE review_status <> 'reviewed')
     UNION ALL
     (SELECT 'body part', id, part_name, review_status, updated_at FROM body_parts WHERE review_status <> 'reviewed')
     ORDER BY (review_status = 'draft') DESC, updated_at DESC
     LIMIT 10"
)->fetchAll();

$editLinks = ['animal' => 'edit-animal.php', 'disease' => 'edit-disease.php', 'body part' => 'edit-body-part.php'];

admin_header('Dashboard', 'dashboard');
?>
<div class="row g-3 mb-4">
    <?php $i = 1; foreach ($totals as $label => $value): ?>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card stat-<?= $i++ ?> h-100">
                <div class="card-body">
                    <h2 class="h6 text-white-50"><?= e($label) ?></h2>
                    <p class="display-6 mb-0"><?= $value ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><a href="add-animal.php" class="btn btn-success w-100 py-3">Add animal</a></div>
    <div class="col-6 col-xl-3"><a href="add-disease.php" class="btn btn-success w-100 py-3">Add disease</a></div>
    <div class="col-6 col-xl-3"><a href="add-body-part.php" class="btn btn-success w-100 py-3">Add body part</a></div>
    <div class="col-6 col-xl-3"><a href="sources.php" class="btn btn-outline-success w-100 py-3">Manage sources</a></div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Review progress</h2></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th></th><th>Draft</th><th>Unreviewed</th><th>Reviewed</th></tr></thead>
                    <tbody>
                        <?php foreach ($reviewRows as $label => $row): ?>
                            <tr><td><?= e($label) ?></td><td><?= $row['draft'] ?></td><td><?= $row['unreviewed'] ?></td><td><?= $row['reviewed'] ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-body small text-muted">Draft records are hidden from the public. Mark a record “Expert-reviewed” only after a qualified person has checked it against its sources.</div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Gaps to fill</h2></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($gaps as $label => $value): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= e($label) ?>
                        <span class="badge <?= $value > 0 ? 'text-bg-warning' : 'text-bg-success' ?>"><?= $value ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Waiting for review</h2></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($needsReview as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <a href="<?= e($editLinks[$item['kind']]) ?>?id=<?= (int) $item['id'] ?>"><?= e($item['name']) ?></a>
                            <span class="text-muted small">(<?= e($item['kind']) ?>)</span>
                        </span>
                        <?= review_badge($item['review_status']) ?>
                    </li>
                <?php endforeach; ?>
                <?php if (!$needsReview): ?>
                    <li class="list-group-item text-muted">Everything has been reviewed.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
<?php admin_footer();
