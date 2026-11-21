<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

/*
 * One confirm-then-delete page for animals, diseases, body parts and sources.
 * GET shows what will be removed; POST (with a CSRF token) performs the delete.
 */

$types = [
    'animal' => ['table' => 'animals', 'name' => 'common_name', 'back' => 'animals.php', 'label' => 'animal'],
    'disease' => ['table' => 'diseases', 'name' => 'disease_name', 'back' => 'diseases.php', 'label' => 'disease'],
    'body_part' => ['table' => 'body_parts', 'name' => 'part_name', 'back' => 'body-parts.php', 'label' => 'body part'],
    'source' => ['table' => 'sources', 'name' => 'title', 'back' => 'sources.php', 'label' => 'source'],
];

$typeKey = (string) ($_GET['type'] ?? '');
$id = get_int('id');
if (!isset($types[$typeKey]) || $id <= 0) {
    header('Location: dashboard.php');
    exit;
}
$type = $types[$typeKey];

$record = run_query(
    $pdo,
    "SELECT * FROM {$type['table']} WHERE id = :id LIMIT 1",
    ['id' => $id]
)->fetch();

if (!$record) {
    flash_set('warning', 'That record no longer exists.');
    header('Location: ' . $type['back']);
    exit;
}
$name = (string) $record[$type['name']];

// What else disappears together with the record (foreign keys cascade).
$impactQueries = [
    'animal' => [
        'body parts linked to it' => 'SELECT COUNT(*) FROM animal_body_parts WHERE animal_id = :id',
        'disease links' => 'SELECT COUNT(*) FROM animal_diseases WHERE animal_id = :id',
        'source links' => 'SELECT COUNT(*) FROM animal_sources WHERE animal_id = :id',
    ],
    'disease' => [
        'species links' => 'SELECT COUNT(*) FROM animal_diseases WHERE disease_id = :id',
        'body part links' => 'SELECT COUNT(*) FROM disease_body_parts WHERE disease_id = :id',
        'symptom links' => 'SELECT COUNT(*) FROM disease_symptoms WHERE disease_id = :id',
        'source links' => 'SELECT COUNT(*) FROM disease_sources WHERE disease_id = :id',
    ],
    'body_part' => [
        'species links' => 'SELECT COUNT(*) FROM animal_body_parts WHERE body_part_id = :id',
        'disease links' => 'SELECT COUNT(*) FROM disease_body_parts WHERE body_part_id = :id',
        'source links' => 'SELECT COUNT(*) FROM body_part_sources WHERE body_part_id = :id',
    ],
    'source' => [
        'animal citations' => 'SELECT COUNT(*) FROM animal_sources WHERE source_id = :id',
        'disease citations' => 'SELECT COUNT(*) FROM disease_sources WHERE source_id = :id',
        'body part citations' => 'SELECT COUNT(*) FROM body_part_sources WHERE source_id = :id',
    ],
];
$impact = [];
foreach ($impactQueries[$typeKey] as $label => $sql) {
    $n = (int) run_query($pdo, $sql, ['id' => $id])->fetchColumn();
    if ($n > 0) {
        $impact[$label] = $n;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    run_query($pdo, "DELETE FROM {$type['table']} WHERE id = :id", ['id' => $id]);
    if ($typeKey === 'animal') {
        delete_uploaded_image($record['image'] ?? null);
    }
    flash_set('success', 'Deleted ' . $type['label'] . ' “' . $name . '”.');
    header('Location: ' . $type['back']);
    exit;
}

admin_header('Delete ' . $type['label']);
?>
<div class="card border-0 shadow-sm mx-auto" style="max-width: 600px;">
    <div class="card-body p-4">
        <h1 class="h4 text-danger mb-3">Delete <?= e($type['label']) ?></h1>
        <p>Are you sure you want to delete <strong><?= e($name) ?></strong>?</p>
        <?php if ($impact): ?>
            <p class="mb-1">This will also remove:</p>
            <ul>
                <?php foreach ($impact as $label => $n): ?>
                    <li><?= $n ?> <?= e($label) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p class="text-muted small">This cannot be undone.</p>

        <form method="post" class="d-flex gap-2">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger">Yes, delete</button>
            <a href="<?= e($type['back']) ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php admin_footer();
