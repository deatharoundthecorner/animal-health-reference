<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

/*
 * Shared create/edit form for body parts.
 * The including page sets $partId (0 = create a new body part).
 */

$partId = (int) ($partId ?? 0);
$editing = $partId > 0;

$systems = $pdo->query('SELECT id, system_name FROM body_systems ORDER BY sort_order, system_name')->fetchAll();

$sourceOptions = [];
foreach ($pdo->query('SELECT id, title FROM sources ORDER BY title')->fetchAll() as $row) {
    $sourceOptions[(int) $row['id']] = $row['title'];
}

$input = ['part_name' => '', 'system_id' => '', 'uberon_id' => '', 'description' => '', 'review_status' => 'draft'];
$selectedSources = [];
$usage = ['animals' => 0, 'diseases' => 0];

if ($editing) {
    $existing = run_query($pdo, 'SELECT * FROM body_parts WHERE id = :id LIMIT 1', ['id' => $partId])->fetch();
    if (!$existing) {
        flash_set('warning', 'That body part no longer exists.');
        header('Location: body-parts.php');
        exit;
    }
    foreach ($input as $key => $unused) {
        $input[$key] = (string) ($existing[$key] ?? '');
    }
    $selectedSources = load_links($pdo, 'body_part_sources', 'body_part_id', $partId);
    $usage['animals'] = (int) run_query($pdo, 'SELECT COUNT(*) FROM animal_body_parts WHERE body_part_id = :id', ['id' => $partId])->fetchColumn();
    $usage['diseases'] = (int) run_query($pdo, 'SELECT COUNT(*) FROM disease_body_parts WHERE body_part_id = :id', ['id' => $partId])->fetchColumn();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $input['part_name'] = post_string('part_name', 150);
    $input['system_id'] = (string) post_int('system_id');
    $input['uberon_id'] = strtoupper(post_string('uberon_id', 20));
    $input['description'] = post_string('description', 20000);
    $input['review_status'] = post_string('review_status', 20);

    if ($input['part_name'] === '') {
        $errors[] = 'Name is required.';
    }
    if ($input['description'] === '') {
        $errors[] = 'Description is required.';
    }
    if (!in_array($input['system_id'], array_map(static fn ($s) => (string) $s['id'], $systems), true)) {
        $errors[] = 'Please choose a body system.';
    }
    if ($input['uberon_id'] !== '' && !preg_match('/^UBERON:\d+$/', $input['uberon_id'])) {
        $errors[] = 'Uberon id must look like UBERON:0000948 (or be left empty).';
    }
    if (!isset(REVIEW_LABELS[$input['review_status']])) {
        $errors[] = 'Invalid review status.';
    }
    $selectedSources = array_intersect_key(read_linked('sources'), $sourceOptions);

    if (!$errors) {
        $values = [
            'part_name' => $input['part_name'],
            'system_id' => (int) $input['system_id'],
            'uberon_id' => $input['uberon_id'] !== '' ? $input['uberon_id'] : null,
            'description' => $input['description'],
            'review_status' => $input['review_status'],
        ];
        try {
            $pdo->beginTransaction();
            if ($editing) {
                $values['id'] = $partId;
                run_query(
                    $pdo,
                    'UPDATE body_parts SET part_name = :part_name, system_id = :system_id, uberon_id = :uberon_id,
                        description = :description, review_status = :review_status WHERE id = :id',
                    $values
                );
                $savedId = $partId;
            } else {
                run_query(
                    $pdo,
                    'INSERT INTO body_parts (part_name, system_id, uberon_id, description, review_status)
                     VALUES (:part_name, :system_id, :uberon_id, :description, :review_status)',
                    $values
                );
                $savedId = (int) $pdo->lastInsertId();
            }
            sync_links($pdo, 'body_part_sources', 'body_part_id', $savedId, $selectedSources);
            $pdo->commit();

            flash_set('success', 'Body part saved.');
            header('Location: body-parts.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = is_duplicate_error($e)
                ? 'A body part with that name or Uberon id already exists.'
                : 'The record could not be saved. Check the values and try again.';
        }
    }
}

admin_header($editing ? 'Edit body part' : 'Add body part', 'body-parts');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0"><?= $editing ? 'Edit body part' : 'Add body part' ?></h1>
    <a href="body-parts.php" class="btn btn-outline-secondary">Back to body parts</a>
</div>

<?= render_errors($errors) ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-5">
                    <label for="part_name" class="form-label">Name *</label>
                    <input type="text" id="part_name" name="part_name" class="form-control" maxlength="150" value="<?= e($input['part_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="system_id" class="form-label">Body system *</label>
                    <select id="system_id" name="system_id" class="form-select" required>
                        <option value="">Select system</option>
                        <?php foreach ($systems as $system): ?>
                            <option value="<?= (int) $system['id'] ?>" <?= $input['system_id'] === (string) $system['id'] ? 'selected' : '' ?>><?= e($system['system_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="uberon_id" class="form-label">Uberon id</label>
                    <input type="text" id="uberon_id" name="uberon_id" class="form-control" maxlength="20" value="<?= e($input['uberon_id']) ?>" placeholder="e.g. UBERON:0000948">
                    <div class="form-text"><a href="https://www.ebi.ac.uk/ols4/ontologies/uberon" target="_blank" rel="noopener noreferrer">Search Uberon</a></div>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description *</label>
                    <textarea id="description" name="description" rows="4" class="form-control" required><?= e($input['description']) ?></textarea>
                    <div class="form-text">Write in your own words. Species-specific differences belong in the note on each animal’s anatomy list.</div>
                </div>
            </div>

            <h2 class="form-section-title">Sources</h2>
            <p class="small text-muted">Add new sources on the <a href="sources.php">Sources page</a>.</p>
            <?= render_link_picker('source', 'sources', 'source_notes', $sourceOptions, $selectedSources, false) ?>

            <?php if ($editing): ?>
                <p class="small text-muted mt-3 mb-0">Used by <?= $usage['animals'] ?> animal(s) and <?= $usage['diseases'] ?> disease(s). Those links are managed on the animal and disease pages.</p>
            <?php endif; ?>

            <h2 class="form-section-title">Publishing</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="review_status" class="form-label">Review status</label>
                    <select id="review_status" name="review_status" class="form-select">
                        <?php foreach (REVIEW_LABELS as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $input['review_status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-success mt-4"><?= $editing ? 'Save changes' : 'Create body part' ?></button>
        </form>
    </div>
</div>
<?php admin_footer();
