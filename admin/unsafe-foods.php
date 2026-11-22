<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/feeding.php';

if (!feeding_available($pdo)) {
    admin_header('Foods to avoid', 'unsafe');
    echo '<div class="alert alert-info">The feeding guide tables are not installed. Run <code>php tools/upgrade.php</code> on the server.</div>';
    admin_footer();
    exit;
}

$animalOptions = [];
foreach ($pdo->query('SELECT id, common_name FROM animals ORDER BY common_name')->fetchAll() as $row) {
    $animalOptions[(int) $row['id']] = $row['common_name'];
}
$sourceOptions = [];
foreach ($pdo->query('SELECT id, title FROM sources ORDER BY title')->fetchAll() as $row) {
    $sourceOptions[(int) $row['id']] = $row['title'];
}

$errors = [];
$form = ['animal_id' => '', 'food_name' => '', 'severity' => 'avoid', 'reason' => '', 'source_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = post_string('action', 10);
    $id = post_int('id');

    if ($action === 'delete') {
        if ($id > 0) {
            run_query($pdo, 'DELETE FROM animal_unsafe_foods WHERE id = :id', ['id' => $id]);
            flash_set('success', 'Entry deleted.');
        }
        header('Location: ' . basename($_SERVER['PHP_SELF']) . ($_GET ? '?' . http_build_query($_GET) : ''));
        exit;
    }

    $form = [
        'animal_id' => (string) post_int('animal_id'),
        'food_name' => post_string('food_name', 150),
        'severity' => post_string('severity', 10),
        'reason' => post_string('reason', 5000),
        'source_id' => (string) post_int('source_id'),
    ];
    if (!in_array($action, ['add', 'edit'], true)) {
        $errors[] = 'Unknown action.';
    }
    if (!isset($animalOptions[(int) $form['animal_id']])) {
        $errors[] = 'Please choose an animal.';
    }
    if ($form['food_name'] === '') {
        $errors[] = 'Food name is required.';
    }
    if ($form['reason'] === '') {
        $errors[] = 'Please say why this food is a problem.';
    }
    if (!isset(UNSAFE_SEVERITIES[$form['severity']])) {
        $errors[] = 'Invalid risk level.';
    }
    $sourceId = (int) $form['source_id'];
    if ($sourceId !== 0 && !isset($sourceOptions[$sourceId])) {
        $errors[] = 'Unknown source.';
    }
    if ($action === 'edit' && $id <= 0) {
        $errors[] = 'Invalid entry.';
    }

    if (!$errors) {
        $values = [
            'animal_id' => (int) $form['animal_id'], 'food_name' => $form['food_name'], 'severity' => $form['severity'],
            'reason' => $form['reason'], 'source_id' => $sourceId > 0 ? $sourceId : null,
        ];
        try {
            if ($action === 'add') {
                run_query($pdo, 'INSERT INTO animal_unsafe_foods (animal_id, food_name, severity, reason, source_id) VALUES (:animal_id, :food_name, :severity, :reason, :source_id)', $values);
            } else {
                $values['id'] = $id;
                run_query($pdo, 'UPDATE animal_unsafe_foods SET animal_id = :animal_id, food_name = :food_name, severity = :severity, reason = :reason, source_id = :source_id WHERE id = :id', $values);
            }
            flash_set('success', 'Entry saved.');
            header('Location: unsafe-foods.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = is_duplicate_error($e) ? 'That food is already listed for this animal.' : 'The entry could not be saved.';
        }
    }
}

$filterAnimal = get_int('animal');
$where = $filterAnimal > 0 ? 'WHERE u.animal_id = :animal_id' : '';
$params = $filterAnimal > 0 ? ['animal_id' => $filterAnimal] : [];
$rows = run_query(
    $pdo,
    "SELECT u.*, a.common_name, s.title AS source_title
     FROM animal_unsafe_foods u
     INNER JOIN animals a ON a.id = u.animal_id
     LEFT JOIN sources s ON s.id = u.source_id
     {$where}
     ORDER BY a.common_name, FIELD(u.severity, 'toxic', 'avoid', 'limit'), u.food_name",
    $params
)->fetchAll();

$animalSelect = static function (string $name, string $selected, string $id) use ($animalOptions): string {
    $html = '<select class="form-select" name="' . e($name) . '" id="' . e($id) . '" required><option value="">Select animal</option>';
    foreach ($animalOptions as $value => $label) {
        $html .= '<option value="' . $value . '"' . ($selected === (string) $value ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $html . '</select>';
};
$severitySelect = static function (string $name, string $selected, string $id): string {
    $html = '<select class="form-select" name="' . e($name) . '" id="' . e($id) . '">';
    foreach (UNSAFE_SEVERITIES as $value => $label) {
        $html .= '<option value="' . e($value) . '"' . ($selected === $value ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $html . '</select>';
};
$sourceSelect = static function (string $name, string $selected, string $id) use ($sourceOptions): string {
    $html = '<select class="form-select" name="' . e($name) . '" id="' . e($id) . '"><option value="">No source</option>';
    foreach ($sourceOptions as $value => $label) {
        $html .= '<option value="' . $value . '"' . ($selected === (string) $value ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $html . '</select>';
};

admin_header('Foods to avoid', 'unsafe');
?>
<h1 class="h3 mb-3">Foods to avoid</h1>

<?= render_errors($errors) ?>

<div class="alert alert-info small">
    Foods that are toxic, unsafe or need care for a species. They appear on the animal's page and under the feeding calculator results. Give the reason in your own words and cite a source where you can.
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h2 class="h5">Add an entry</h2>
        <form method="post" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="col-md-3"><label for="n-animal" class="form-label small mb-1">Animal *</label><?= $animalSelect('animal_id', $form['animal_id'], 'n-animal') ?></div>
            <div class="col-md-3"><label for="n-food" class="form-label small mb-1">Food *</label><input type="text" id="n-food" name="food_name" class="form-control" maxlength="150" value="<?= e($form['food_name']) ?>" required></div>
            <div class="col-md-3"><label for="n-sev" class="form-label small mb-1">Risk</label><?= $severitySelect('severity', $form['severity'], 'n-sev') ?></div>
            <div class="col-md-3"><label for="n-src" class="form-label small mb-1">Source</label><?= $sourceSelect('source_id', $form['source_id'], 'n-src') ?></div>
            <div class="col-12"><label for="n-reason" class="form-label small mb-1">Why? *</label><textarea id="n-reason" name="reason" rows="2" class="form-control" required><?= e($form['reason']) ?></textarea></div>
            <div class="col-12"><button type="submit" class="btn btn-success">Add entry</button></div>
        </form>
    </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="animal" class="form-select" aria-label="Animal">
            <option value="0">All animals</option>
            <?php foreach ($animalOptions as $value => $label): ?>
                <option value="<?= $value ?>" <?= $filterAnimal === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-success" type="submit">Filter</button> <a href="unsafe-foods.php" class="btn btn-outline-secondary">Clear</a></div>
</form>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Animal</th><th>Food</th><th>Risk</th><th>Why</th><th>Source</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $badge = match ($row['severity']) { 'toxic' => 'text-bg-danger', 'avoid' => 'text-bg-warning', default => 'text-bg-secondary' }; ?>
                    <tr>
                        <td><?= e($row['common_name']) ?></td>
                        <td><strong><?= e($row['food_name']) ?></strong></td>
                        <td><span class="badge <?= $badge ?>"><?= e(UNSAFE_SEVERITIES[$row['severity']] ?? $row['severity']) ?></span></td>
                        <td class="small"><?= e(excerpt($row['reason'], 120)) ?></td>
                        <td class="small"><?= $row['source_title'] !== null ? e(excerpt($row['source_title'], 50)) : '<span class="badge text-bg-warning">none</span>' ?></td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUnsafe<?= (int) $row['id'] ?>">Edit</button>
                            <form method="post" class="d-inline" data-confirm="Delete this entry?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="text-center py-4">No entries yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($rows as $row): ?>
    <div class="modal fade" id="editUnsafe<?= (int) $row['id'] ?>" tabindex="-1" aria-labelledby="editUnsafeLabel<?= (int) $row['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editUnsafeLabel<?= (int) $row['id'] ?>">Edit entry</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-4"><label class="form-label small mb-1" for="ea<?= (int) $row['id'] ?>">Animal *</label><?= $animalSelect('animal_id', (string) $row['animal_id'], 'ea' . (int) $row['id']) ?></div>
                            <div class="col-md-5"><label class="form-label small mb-1" for="ef<?= (int) $row['id'] ?>">Food *</label><input type="text" class="form-control" id="ef<?= (int) $row['id'] ?>" name="food_name" maxlength="150" value="<?= e($row['food_name']) ?>" required></div>
                            <div class="col-md-3"><label class="form-label small mb-1" for="es<?= (int) $row['id'] ?>">Risk</label><?= $severitySelect('severity', $row['severity'], 'es' . (int) $row['id']) ?></div>
                            <div class="col-12"><label class="form-label small mb-1" for="er<?= (int) $row['id'] ?>">Why? *</label><textarea class="form-control" id="er<?= (int) $row['id'] ?>" name="reason" rows="3" required><?= e($row['reason']) ?></textarea></div>
                            <div class="col-12"><label class="form-label small mb-1" for="eso<?= (int) $row['id'] ?>">Source</label><?= $sourceSelect('source_id', (string) ($row['source_id'] ?? ''), 'eso' . (int) $row['id']) ?></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php admin_footer();
