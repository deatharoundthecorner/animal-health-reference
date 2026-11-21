<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

/*
 * Shared create/edit form for animals.
 * The including page sets $animalId (0 = create a new animal).
 */

$animalId = (int) ($animalId ?? 0);
$editing = $animalId > 0;

$categories = $pdo->query('SELECT id, category_name FROM categories ORDER BY category_name ASC')->fetchAll();

$partOptions = [];
foreach ($pdo->query(
    'SELECT bp.id, bp.part_name, bs.system_name
     FROM body_parts bp INNER JOIN body_systems bs ON bs.id = bp.system_id
     ORDER BY bs.sort_order, bs.system_name, bp.part_name'
)->fetchAll() as $row) {
    $partOptions[(int) $row['id']] = $row['system_name'] . ' — ' . $row['part_name'];
}

$sourceOptions = [];
foreach ($pdo->query('SELECT id, title FROM sources ORDER BY title ASC')->fetchAll() as $row) {
    $sourceOptions[(int) $row['id']] = $row['title'];
}

$input = [
    'common_name' => '', 'scientific_name' => '', 'taxon_rank' => 'species', 'ncbi_taxon_id' => '',
    'category_id' => '', 'animal_type' => 'other', 'class_name' => '', 'order_name' => '', 'family_name' => '',
    'habitat' => '', 'diet' => '', 'country' => '', 'lifespan' => '', 'conservation_status' => '',
    'description' => '', 'review_status' => 'draft',
];
$existing = null;
$selectedParts = [];
$selectedSources = [];
$linkedDiseases = [];

if ($editing) {
    $existing = run_query($pdo, 'SELECT * FROM animals WHERE id = :id LIMIT 1', ['id' => $animalId])->fetch();
    if (!$existing) {
        flash_set('warning', 'That animal no longer exists.');
        header('Location: animals.php');
        exit;
    }
    foreach ($input as $key => $unused) {
        $input[$key] = (string) ($existing[$key] ?? '');
    }
    $selectedParts = load_links($pdo, 'animal_body_parts', 'animal_id', $animalId);
    $selectedSources = load_links($pdo, 'animal_sources', 'animal_id', $animalId);
    $linkedDiseases = run_query(
        $pdo,
        'SELECT d.id, d.disease_name FROM animal_diseases ad INNER JOIN diseases d ON d.id = ad.disease_id WHERE ad.animal_id = :id ORDER BY d.disease_name',
        ['id' => $animalId]
    )->fetchAll();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $lengths = [
        'common_name' => 150, 'scientific_name' => 200, 'class_name' => 100, 'order_name' => 100,
        'family_name' => 100, 'habitat' => 255, 'diet' => 255, 'country' => 255, 'lifespan' => 100,
        'conservation_status' => 100, 'description' => 20000,
    ];
    foreach ($lengths as $key => $max) {
        $input[$key] = post_string($key, $max);
    }
    $input['taxon_rank'] = post_string('taxon_rank', 20);
    $input['ncbi_taxon_id'] = post_string('ncbi_taxon_id', 12);
    $input['category_id'] = (string) post_int('category_id');
    $input['animal_type'] = post_string('animal_type', 20);
    $input['review_status'] = post_string('review_status', 20);

    foreach (['common_name' => 'Common name', 'scientific_name' => 'Scientific name', 'description' => 'Description'] as $key => $label) {
        if ($input[$key] === '') {
            $errors[] = $label . ' is required.';
        }
    }
    if (!in_array($input['category_id'], array_map(static fn ($c) => (string) $c['id'], $categories), true)) {
        $errors[] = 'Please choose a group.';
    }
    if (!in_array($input['taxon_rank'], TAXON_RANKS, true)) {
        $errors[] = 'Invalid taxonomic rank.';
    }
    if (!isset(ANIMAL_TYPES[$input['animal_type']])) {
        $errors[] = 'Invalid kind of animal.';
    }
    if (!isset(REVIEW_LABELS[$input['review_status']])) {
        $errors[] = 'Invalid review status.';
    }
    $ncbiId = null;
    if ($input['ncbi_taxon_id'] !== '') {
        if (!ctype_digit($input['ncbi_taxon_id']) || (int) $input['ncbi_taxon_id'] < 1 || (int) $input['ncbi_taxon_id'] > 4294967295) {
            $errors[] = 'NCBI taxon id must be a positive whole number.';
        } else {
            $ncbiId = (int) $input['ncbi_taxon_id'];
        }
    }

    $selectedParts = array_intersect_key(read_linked('parts', 'part_notes'), $partOptions);
    $selectedSources = array_intersect_key(read_linked('sources'), $sourceOptions);

    $newImage = store_uploaded_image($_FILES['image'] ?? [], $errors);
    $oldImage = $existing['image'] ?? null;
    $removeImage = $editing && post_bool('remove_image') === 1 && $newImage === null;

    if (!$errors) {
        $image = $newImage ?? ($removeImage ? null : $oldImage);
        $values = [
            'common_name' => $input['common_name'],
            'scientific_name' => $input['scientific_name'],
            'taxon_rank' => $input['taxon_rank'],
            'ncbi_taxon_id' => $ncbiId,
            'category_id' => (int) $input['category_id'],
            'animal_type' => $input['animal_type'],
            'class_name' => $input['class_name'] !== '' ? $input['class_name'] : null,
            'order_name' => $input['order_name'] !== '' ? $input['order_name'] : null,
            'family_name' => $input['family_name'] !== '' ? $input['family_name'] : null,
            'habitat' => $input['habitat'],
            'diet' => $input['diet'],
            'country' => $input['country'],
            'lifespan' => $input['lifespan'],
            'conservation_status' => $input['conservation_status'],
            'description' => $input['description'],
            'image' => $image,
            'review_status' => $input['review_status'],
        ];

        try {
            $pdo->beginTransaction();
            if ($editing) {
                $values['id'] = $animalId;
                run_query(
                    $pdo,
                    'UPDATE animals SET common_name = :common_name, scientific_name = :scientific_name, taxon_rank = :taxon_rank,
                        ncbi_taxon_id = :ncbi_taxon_id, category_id = :category_id, animal_type = :animal_type,
                        class_name = :class_name, order_name = :order_name, family_name = :family_name,
                        habitat = :habitat, diet = :diet, country = :country, lifespan = :lifespan,
                        conservation_status = :conservation_status, description = :description,
                        image = :image, review_status = :review_status
                     WHERE id = :id',
                    $values
                );
                $savedId = $animalId;
            } else {
                run_query(
                    $pdo,
                    'INSERT INTO animals (common_name, scientific_name, taxon_rank, ncbi_taxon_id, category_id, animal_type,
                        class_name, order_name, family_name, habitat, diet, country, lifespan, conservation_status,
                        description, image, review_status)
                     VALUES (:common_name, :scientific_name, :taxon_rank, :ncbi_taxon_id, :category_id, :animal_type,
                        :class_name, :order_name, :family_name, :habitat, :diet, :country, :lifespan, :conservation_status,
                        :description, :image, :review_status)',
                    $values
                );
                $savedId = (int) $pdo->lastInsertId();
            }
            sync_links($pdo, 'animal_body_parts', 'animal_id', $savedId, $selectedParts);
            sync_links($pdo, 'animal_sources', 'animal_id', $savedId, $selectedSources);
            $pdo->commit();

            if ($editing && ($newImage !== null || $removeImage)) {
                delete_uploaded_image($oldImage);
            }
            flash_set('success', 'Animal saved.');
            header('Location: animals.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            delete_uploaded_image($newImage);
            $errors[] = is_duplicate_error($e)
                ? 'Another animal already uses that NCBI taxon id.'
                : 'The record could not be saved. Check the values and try again.';
        }
    } else {
        delete_uploaded_image($newImage);
    }
}

$currentImage = $existing['image'] ?? null;

admin_header($editing ? 'Edit animal' : 'Add animal', 'animals');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0"><?= $editing ? 'Edit animal' : 'Add animal' ?></h1>
    <a href="animals.php" class="btn btn-outline-secondary">Back to animals</a>
</div>

<?= render_errors($errors) ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <h2 class="form-section-title mt-0">Identification</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="common_name" class="form-label">Common name *</label>
                    <input type="text" id="common_name" name="common_name" class="form-control" maxlength="150" value="<?= e($input['common_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="scientific_name" class="form-label">Scientific name *</label>
                    <div class="input-group">
                        <input type="text" id="scientific_name" name="scientific_name" class="form-control" maxlength="200" value="<?= e($input['scientific_name']) ?>" required>
                        <?php if (!$editing): ?>
                            <button type="button" id="autoFetchBtn" class="btn btn-outline-info" title="Prefill from the public iNaturalist API">Auto fetch</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="taxon_rank" class="form-label">Taxonomic rank</label>
                    <select id="taxon_rank" name="taxon_rank" class="form-select">
                        <?php foreach (TAXON_RANKS as $rank): ?>
                            <option value="<?= e($rank) ?>" <?= $input['taxon_rank'] === $rank ? 'selected' : '' ?>><?= e($rank) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="ncbi_taxon_id" class="form-label">NCBI taxon id</label>
                    <input type="text" inputmode="numeric" id="ncbi_taxon_id" name="ncbi_taxon_id" class="form-control" maxlength="10" value="<?= e($input['ncbi_taxon_id']) ?>" placeholder="e.g. 9615">
                    <div class="form-text"><a href="https://www.ncbi.nlm.nih.gov/taxonomy" target="_blank" rel="noopener noreferrer">Look it up</a></div>
                </div>
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Group *</label>
                    <select id="category_id" name="category_id" class="form-select" required>
                        <option value="">Select group</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= $input['category_id'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="animal_type" class="form-label">Kind of animal</label>
                    <select id="animal_type" name="animal_type" class="form-select">
                        <?php foreach (ANIMAL_TYPES as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $input['animal_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h2 class="form-section-title">Taxonomy</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="class_name" class="form-label">Class</label>
                    <input type="text" id="class_name" name="class_name" class="form-control" maxlength="100" value="<?= e($input['class_name']) ?>" placeholder="e.g. Mammalia">
                </div>
                <div class="col-md-4">
                    <label for="order_name" class="form-label">Order</label>
                    <input type="text" id="order_name" name="order_name" class="form-control" maxlength="100" value="<?= e($input['order_name']) ?>" placeholder="e.g. Carnivora">
                </div>
                <div class="col-md-4">
                    <label for="family_name" class="form-label">Family</label>
                    <input type="text" id="family_name" name="family_name" class="form-control" maxlength="100" value="<?= e($input['family_name']) ?>" placeholder="e.g. Canidae">
                </div>
            </div>

            <h2 class="form-section-title">Biology and status</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="habitat" class="form-label">Habitat</label>
                    <input type="text" id="habitat" name="habitat" class="form-control" maxlength="255" value="<?= e($input['habitat']) ?>">
                </div>
                <div class="col-md-6">
                    <label for="diet" class="form-label">Diet</label>
                    <input type="text" id="diet" name="diet" class="form-control" maxlength="255" value="<?= e($input['diet']) ?>">
                </div>
                <div class="col-md-4">
                    <label for="country" class="form-label">Range / country</label>
                    <input type="text" id="country" name="country" class="form-control" maxlength="255" value="<?= e($input['country']) ?>">
                </div>
                <div class="col-md-4">
                    <label for="lifespan" class="form-label">Lifespan</label>
                    <input type="text" id="lifespan" name="lifespan" class="form-control" maxlength="100" value="<?= e($input['lifespan']) ?>">
                </div>
                <div class="col-md-4">
                    <label for="conservation_status" class="form-label">Conservation status</label>
                    <input type="text" id="conservation_status" name="conservation_status" class="form-control" maxlength="100" value="<?= e($input['conservation_status']) ?>" placeholder="e.g. Endangered, Least Concern">
                    <div class="form-text">Check the current <a href="https://www.iucnredlist.org/" target="_blank" rel="noopener noreferrer">IUCN Red List</a>; statuses change.</div>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description *</label>
                    <textarea id="description" name="description" rows="5" class="form-control" required><?= e($input['description']) ?></textarea>
                    <div class="form-text">Write in your own words. Do not paste text from copyrighted books or manuals.</div>
                </div>
            </div>
            <div id="apiResult" class="d-none" role="status"></div>

            <h2 class="form-section-title">Image</h2>
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label for="image" class="form-label">Photo (JPG, PNG or WEBP, max 2 MB)</label>
                    <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <div class="form-text">Only upload images you have the right to publish, and record the credit in the description or sources.</div>
                </div>
                <?php if ($currentImage): ?>
                    <div class="col-md-6">
                        <img src="<?= e(animal_image($currentImage, '../')) ?>" alt="Current image" class="img-fluid rounded border" style="max-height:140px">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                            <label class="form-check-label" for="remove_image">Remove current image</label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <h2 class="form-section-title">Anatomy</h2>
            <p class="small text-muted">Tick the body parts this animal has. Use the note for species-specific facts, such as “Four-compartment stomach”.</p>
            <?= render_link_picker('part', 'parts', 'part_notes', $partOptions, $selectedParts) ?>

            <h2 class="form-section-title">Sources</h2>
            <p class="small text-muted">Cite where the information comes from. Add new sources on the <a href="sources.php">Sources page</a>.</p>
            <?= render_link_picker('source', 'sources', 'source_notes', $sourceOptions, $selectedSources, false) ?>

            <?php if ($editing): ?>
                <h2 class="form-section-title">Diseases</h2>
                <p class="small text-muted">Disease links are managed from each disease’s own page.</p>
                <?php foreach ($linkedDiseases as $disease): ?>
                    <a class="chip text-decoration-none" href="edit-disease.php?id=<?= (int) $disease['id'] ?>"><?= e($disease['disease_name']) ?></a>
                <?php endforeach; ?>
                <?php if (!$linkedDiseases): ?><span class="text-muted small">No diseases linked yet.</span><?php endif; ?>
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
                    <div class="form-text">Draft records are hidden from the public. Choose “Expert-reviewed” only after a qualified person has checked the record.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-success mt-4"><?= $editing ? 'Save changes' : 'Create animal' ?></button>
        </form>
    </div>
</div>
<?php admin_footer();
