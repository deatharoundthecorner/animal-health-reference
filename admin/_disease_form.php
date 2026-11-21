<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

/*
 * Shared create/edit form for diseases.
 * The including page sets $diseaseId (0 = create a new disease).
 */

$diseaseId = (int) ($diseaseId ?? 0);
$editing = $diseaseId > 0;

$animalOptions = [];
foreach ($pdo->query('SELECT id, common_name FROM animals ORDER BY common_name')->fetchAll() as $row) {
    $animalOptions[(int) $row['id']] = $row['common_name'];
}

$partOptions = [];
foreach ($pdo->query(
    'SELECT bp.id, bp.part_name, bs.system_name
     FROM body_parts bp INNER JOIN body_systems bs ON bs.id = bp.system_id
     ORDER BY bs.sort_order, bs.system_name, bp.part_name'
)->fetchAll() as $row) {
    $partOptions[(int) $row['id']] = $row['system_name'] . ' — ' . $row['part_name'];
}

$sourceOptions = [];
foreach ($pdo->query('SELECT id, title FROM sources ORDER BY title')->fetchAll() as $row) {
    $sourceOptions[(int) $row['id']] = $row['title'];
}

$allSymptoms = $pdo->query('SELECT symptom_name FROM symptoms ORDER BY symptom_name')->fetchAll(PDO::FETCH_COLUMN);

$input = [
    'disease_name' => '', 'pathogen_type' => 'virus', 'pathogen_name' => '', 'disease_ontology_id' => '',
    'is_zoonotic' => '0', 'woah_listed' => '0', 'summary' => '', 'transmission' => '', 'prevention' => '',
    'review_status' => 'draft',
];
$symptomsText = '';
$selectedAnimals = [];
$selectedParts = [];
$selectedSources = [];

if ($editing) {
    $existing = run_query($pdo, 'SELECT * FROM diseases WHERE id = :id LIMIT 1', ['id' => $diseaseId])->fetch();
    if (!$existing) {
        flash_set('warning', 'That disease no longer exists.');
        header('Location: diseases.php');
        exit;
    }
    foreach ($input as $key => $unused) {
        $input[$key] = (string) ($existing[$key] ?? '');
    }
    $symptomNames = run_query(
        $pdo,
        'SELECT s.symptom_name FROM disease_symptoms ds INNER JOIN symptoms s ON s.id = ds.symptom_id WHERE ds.disease_id = :id ORDER BY s.symptom_name',
        ['id' => $diseaseId]
    )->fetchAll(PDO::FETCH_COLUMN);
    $symptomsText = implode(', ', $symptomNames);
    $selectedAnimals = load_links($pdo, 'animal_diseases', 'disease_id', $diseaseId);
    $selectedParts = load_links($pdo, 'disease_body_parts', 'disease_id', $diseaseId);
    $selectedSources = load_links($pdo, 'disease_sources', 'disease_id', $diseaseId);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $input['disease_name'] = post_string('disease_name', 200);
    $input['pathogen_type'] = post_string('pathogen_type', 20);
    $input['pathogen_name'] = post_string('pathogen_name', 255);
    $input['disease_ontology_id'] = strtoupper(post_string('disease_ontology_id', 20));
    $input['is_zoonotic'] = (string) post_bool('is_zoonotic');
    $input['woah_listed'] = (string) post_bool('woah_listed');
    $input['summary'] = post_string('summary', 20000);
    $input['transmission'] = post_string('transmission', 20000);
    $input['prevention'] = post_string('prevention', 20000);
    $input['review_status'] = post_string('review_status', 20);
    $symptomsText = post_string('symptoms_text', 4000);

    if ($input['disease_name'] === '') {
        $errors[] = 'Disease name is required.';
    }
    if ($input['summary'] === '') {
        $errors[] = 'Summary is required.';
    }
    if (!isset(PATHOGEN_TYPES[$input['pathogen_type']])) {
        $errors[] = 'Invalid cause.';
    }
    if (!isset(REVIEW_LABELS[$input['review_status']])) {
        $errors[] = 'Invalid review status.';
    }
    if ($input['disease_ontology_id'] !== '' && !preg_match('/^DOID:\d+$/', $input['disease_ontology_id'])) {
        $errors[] = 'Disease Ontology id must look like DOID:11260 (or be left empty).';
    }

    $selectedAnimals = array_intersect_key(read_linked('animals', 'animal_notes'), $animalOptions);
    $selectedParts = array_intersect_key(read_linked('parts', 'part_notes'), $partOptions);
    $selectedSources = array_intersect_key(read_linked('sources'), $sourceOptions);

    if (!$errors) {
        $values = [
            'disease_name' => $input['disease_name'],
            'pathogen_type' => $input['pathogen_type'],
            'pathogen_name' => $input['pathogen_name'] !== '' ? $input['pathogen_name'] : null,
            'disease_ontology_id' => $input['disease_ontology_id'] !== '' ? $input['disease_ontology_id'] : null,
            'is_zoonotic' => (int) $input['is_zoonotic'],
            'woah_listed' => (int) $input['woah_listed'],
            'summary' => $input['summary'],
            'transmission' => $input['transmission'] !== '' ? $input['transmission'] : null,
            'prevention' => $input['prevention'] !== '' ? $input['prevention'] : null,
            'review_status' => $input['review_status'],
        ];

        try {
            $pdo->beginTransaction();
            if ($editing) {
                $values['id'] = $diseaseId;
                run_query(
                    $pdo,
                    'UPDATE diseases SET disease_name = :disease_name, pathogen_type = :pathogen_type, pathogen_name = :pathogen_name,
                        disease_ontology_id = :disease_ontology_id, is_zoonotic = :is_zoonotic, woah_listed = :woah_listed,
                        summary = :summary, transmission = :transmission, prevention = :prevention, review_status = :review_status
                     WHERE id = :id',
                    $values
                );
                $savedId = $diseaseId;
            } else {
                run_query(
                    $pdo,
                    'INSERT INTO diseases (disease_name, pathogen_type, pathogen_name, disease_ontology_id, is_zoonotic, woah_listed,
                        summary, transmission, prevention, review_status)
                     VALUES (:disease_name, :pathogen_type, :pathogen_name, :disease_ontology_id, :is_zoonotic, :woah_listed,
                        :summary, :transmission, :prevention, :review_status)',
                    $values
                );
                $savedId = (int) $pdo->lastInsertId();
            }

            sync_links($pdo, 'disease_symptoms', 'disease_id', $savedId, symptom_ids_for_names($pdo, parse_tag_list($symptomsText)));
            sync_links($pdo, 'animal_diseases', 'disease_id', $savedId, $selectedAnimals);
            sync_links($pdo, 'disease_body_parts', 'disease_id', $savedId, $selectedParts);
            sync_links($pdo, 'disease_sources', 'disease_id', $savedId, $selectedSources);
            $pdo->commit();

            flash_set('success', 'Disease saved.');
            header('Location: diseases.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = is_duplicate_error($e)
                ? 'A disease with that name already exists.'
                : 'The record could not be saved. Check the values and try again.';
        }
    }
}

admin_header($editing ? 'Edit disease' : 'Add disease', 'diseases');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0"><?= $editing ? 'Edit disease' : 'Add disease' ?></h1>
    <a href="diseases.php" class="btn btn-outline-secondary">Back to diseases</a>
</div>

<?= render_errors($errors) ?>

<div class="alert alert-info small">
    Write in your own words and cite your sources. Do <strong>not</strong> paste text from copyrighted books or manuals (for example the Merck Veterinary Manual); link to them as a source instead. Avoid treatment and dosage advice.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <h2 class="form-section-title mt-0">Identification</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="disease_name" class="form-label">Disease name *</label>
                    <input type="text" id="disease_name" name="disease_name" class="form-control" maxlength="200" value="<?= e($input['disease_name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="pathogen_type" class="form-label">Cause *</label>
                    <select id="pathogen_type" name="pathogen_type" class="form-select">
                        <?php foreach (PATHOGEN_TYPES as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $input['pathogen_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="disease_ontology_id" class="form-label">Disease Ontology id</label>
                    <input type="text" id="disease_ontology_id" name="disease_ontology_id" class="form-control" maxlength="20" value="<?= e($input['disease_ontology_id']) ?>" placeholder="e.g. DOID:11260">
                    <div class="form-text"><a href="https://www.ebi.ac.uk/ols4/ontologies/doid" target="_blank" rel="noopener noreferrer">Search the ontology</a>. Leave empty if there is no matching term.</div>
                </div>
                <div class="col-md-12">
                    <label for="pathogen_name" class="form-label">Pathogen / agent</label>
                    <input type="text" id="pathogen_name" name="pathogen_name" class="form-control" maxlength="255" value="<?= e($input['pathogen_name']) ?>" placeholder="e.g. Canine parvovirus 2 (CPV-2)">
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_zoonotic" name="is_zoonotic" value="1" <?= $input['is_zoonotic'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_zoonotic">Zoonotic (can infect people)</label>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="woah_listed" name="woah_listed" value="1" <?= $input['woah_listed'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="woah_listed">Listed by WOAH (usually reportable)</label>
                    </div>
                </div>
            </div>

            <h2 class="form-section-title">Description</h2>
            <div class="row g-3">
                <div class="col-12">
                    <label for="summary" class="form-label">Summary *</label>
                    <textarea id="summary" name="summary" rows="4" class="form-control" required><?= e($input['summary']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="transmission" class="form-label">Transmission</label>
                    <textarea id="transmission" name="transmission" rows="4" class="form-control"><?= e($input['transmission']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="prevention" class="form-label">Prevention and control</label>
                    <textarea id="prevention" name="prevention" rows="4" class="form-control"><?= e($input['prevention']) ?></textarea>
                </div>
            </div>

            <h2 class="form-section-title">Signs and symptoms</h2>
            <label for="symptoms_text" class="form-label">Separate with commas. New signs are added automatically.</label>
            <textarea id="symptoms_text" name="symptoms_text" rows="2" class="form-control mb-2"><?= e($symptomsText) ?></textarea>
            <details>
                <summary class="small text-muted">Existing signs (click to add)</summary>
                <div class="mt-2">
                    <?php foreach ($allSymptoms as $name): ?>
                        <span class="chip" data-append-to="symptoms_text"><?= e($name) ?></span>
                    <?php endforeach; ?>
                </div>
            </details>

            <h2 class="form-section-title">Species affected</h2>
            <p class="small text-muted">Tick the species and add a note where the disease behaves differently in that species.</p>
            <?= render_link_picker('animal', 'animals', 'animal_notes', $animalOptions, $selectedAnimals) ?>

            <h2 class="form-section-title">Body parts affected</h2>
            <?= render_link_picker('part', 'parts', 'part_notes', $partOptions, $selectedParts) ?>

            <h2 class="form-section-title">Sources</h2>
            <p class="small text-muted">Add new sources on the <a href="sources.php">Sources page</a>.</p>
            <?= render_link_picker('source', 'sources', 'source_notes', $sourceOptions, $selectedSources, false) ?>

            <h2 class="form-section-title">Publishing</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="review_status" class="form-label">Review status</label>
                    <select id="review_status" name="review_status" class="form-select">
                        <?php foreach (REVIEW_LABELS as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $input['review_status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Draft records are hidden from the public. Choose “Expert-reviewed” only after a qualified person has checked the record.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-success mt-4"><?= $editing ? 'Save changes' : 'Create disease' ?></button>
        </form>
    </div>
</div>
<?php admin_footer();
