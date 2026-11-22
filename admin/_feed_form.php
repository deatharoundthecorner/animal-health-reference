<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/feeding.php';

/*
 * Shared create/edit form for feed types.
 * The including page sets $feedId (0 = create a new feed type).
 */

if (!feeding_available($pdo)) {
    admin_header('Feed type', 'feeds');
    echo '<div class="alert alert-info">The feeding guide tables are not installed. Run <code>php tools/upgrade.php</code> on the server.</div>';
    admin_footer();
    exit;
}

$feedId = (int) ($feedId ?? 0);
$editing = $feedId > 0;

$sourceOptions = [];
foreach ($pdo->query('SELECT id, title FROM sources ORDER BY title')->fetchAll() as $row) {
    $sourceOptions[(int) $row['id']] = $row['title'];
}

$input = [
    'feed_name' => '', 'feed_category' => 'green_forage', 'scientific_name' => '', 'local_name_si' => '', 'local_name_ta' => '',
    'dry_matter_percent' => '', 'crude_protein_percent' => '', 'me_mj_per_kg_dm' => '', 'energy_kcal_per_kg' => '',
    'description' => '', 'availability_note' => '', 'cautions' => '', 'review_status' => 'draft',
];
$selectedSources = [];

if ($editing) {
    $existing = run_query($pdo, 'SELECT * FROM feeds WHERE id = :id LIMIT 1', ['id' => $feedId])->fetch();
    if (!$existing) {
        flash_set('warning', 'That feed type no longer exists.');
        header('Location: feeds.php');
        exit;
    }
    foreach ($input as $key => $unused) {
        $input[$key] = (string) ($existing[$key] ?? '');
    }
    $selectedSources = load_links($pdo, 'feed_sources', 'feed_id', $feedId);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    foreach (['feed_name' => 150, 'scientific_name' => 150, 'local_name_si' => 150, 'local_name_ta' => 150,
              'description' => 20000, 'availability_note' => 20000, 'cautions' => 20000] as $key => $max) {
        $input[$key] = post_string($key, $max);
    }
    $input['feed_category'] = post_string('feed_category', 30);
    $input['review_status'] = post_string('review_status', 20);
    foreach (['dry_matter_percent', 'crude_protein_percent', 'me_mj_per_kg_dm', 'energy_kcal_per_kg'] as $key) {
        $input[$key] = post_string($key, 12);
    }

    if ($input['feed_name'] === '') {
        $errors[] = 'Feed name is required.';
    }
    if ($input['description'] === '') {
        $errors[] = 'Description is required.';
    }
    if (!isset(FEED_CATEGORIES[$input['feed_category']])) {
        $errors[] = 'Invalid category.';
    }
    if (!isset(REVIEW_LABELS[$input['review_status']])) {
        $errors[] = 'Invalid review status.';
    }
    $dm = parse_decimal($input['dry_matter_percent'], 'Dry matter %', 0.01, 100, $errors);
    $cp = parse_decimal($input['crude_protein_percent'], 'Crude protein %', 0, 100, $errors);
    $me = parse_decimal($input['me_mj_per_kg_dm'], 'Metabolizable energy (MJ/kg DM)', 0, 30, $errors);
    $kcal = parse_decimal($input['energy_kcal_per_kg'], 'Energy (kcal/kg)', 0, 10000, $errors);

    $selectedSources = array_intersect_key(read_linked('sources'), $sourceOptions);

    if (!$errors) {
        $values = [
            'feed_name' => $input['feed_name'],
            'feed_category' => $input['feed_category'],
            'scientific_name' => $input['scientific_name'] !== '' ? $input['scientific_name'] : null,
            'local_name_si' => $input['local_name_si'] !== '' ? $input['local_name_si'] : null,
            'local_name_ta' => $input['local_name_ta'] !== '' ? $input['local_name_ta'] : null,
            'dry_matter_percent' => $dm,
            'crude_protein_percent' => $cp,
            'me_mj_per_kg_dm' => $me,
            'energy_kcal_per_kg' => $kcal !== null ? (int) round($kcal) : null,
            'description' => $input['description'],
            'availability_note' => $input['availability_note'] !== '' ? $input['availability_note'] : null,
            'cautions' => $input['cautions'] !== '' ? $input['cautions'] : null,
            'review_status' => $input['review_status'],
        ];
        try {
            $pdo->beginTransaction();
            if ($editing) {
                $values['id'] = $feedId;
                run_query(
                    $pdo,
                    'UPDATE feeds SET feed_name = :feed_name, feed_category = :feed_category, scientific_name = :scientific_name,
                        local_name_si = :local_name_si, local_name_ta = :local_name_ta, dry_matter_percent = :dry_matter_percent,
                        crude_protein_percent = :crude_protein_percent, me_mj_per_kg_dm = :me_mj_per_kg_dm,
                        energy_kcal_per_kg = :energy_kcal_per_kg, description = :description,
                        availability_note = :availability_note, cautions = :cautions, review_status = :review_status
                     WHERE id = :id',
                    $values
                );
                $savedId = $feedId;
            } else {
                run_query(
                    $pdo,
                    'INSERT INTO feeds (feed_name, feed_category, scientific_name, local_name_si, local_name_ta, dry_matter_percent,
                        crude_protein_percent, me_mj_per_kg_dm, energy_kcal_per_kg, description, availability_note, cautions, review_status)
                     VALUES (:feed_name, :feed_category, :scientific_name, :local_name_si, :local_name_ta, :dry_matter_percent,
                        :crude_protein_percent, :me_mj_per_kg_dm, :energy_kcal_per_kg, :description, :availability_note, :cautions, :review_status)',
                    $values
                );
                $savedId = (int) $pdo->lastInsertId();
            }
            sync_links($pdo, 'feed_sources', 'feed_id', $savedId, $selectedSources);
            $pdo->commit();

            flash_set('success', 'Feed type saved.');
            header('Location: feeds.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = is_duplicate_error($e)
                ? 'A feed type with that name already exists.'
                : 'The record could not be saved. Check the values and try again.';
        }
    }
}

admin_header($editing ? 'Edit feed type' : 'Add feed type', 'feeds');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0"><?= $editing ? 'Edit feed type' : 'Add feed type' ?></h1>
    <a href="feeds.php" class="btn btn-outline-secondary">Back to feed types</a>
</div>

<?= render_errors($errors) ?>

<div class="alert alert-info small">
    Only enter nutrient values you can cite (add the source below). Leave a value empty if it is unknown: the calculator then shows a dry-matter amount instead of inventing a weight.
    Write the description in your own words and do not paste text from copyrighted books or manuals.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <h2 class="form-section-title mt-0">Identification</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="feed_name" class="form-label">Feed name *</label>
                    <input type="text" id="feed_name" name="feed_name" class="form-control" maxlength="150" value="<?= e($input['feed_name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="feed_category" class="form-label">Category *</label>
                    <select id="feed_category" name="feed_category" class="form-select">
                        <?php foreach (FEED_CATEGORIES as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $input['feed_category'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="scientific_name" class="form-label">Scientific name</label>
                    <input type="text" id="scientific_name" name="scientific_name" class="form-control" maxlength="150" value="<?= e($input['scientific_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label for="local_name_si" class="form-label">Sinhala name</label>
                    <input type="text" id="local_name_si" name="local_name_si" class="form-control" maxlength="150" value="<?= e($input['local_name_si']) ?>">
                </div>
                <div class="col-md-6">
                    <label for="local_name_ta" class="form-label">Tamil name</label>
                    <input type="text" id="local_name_ta" name="local_name_ta" class="form-control" maxlength="150" value="<?= e($input['local_name_ta']) ?>">
                </div>
            </div>

            <h2 class="form-section-title">Nutrient values (optional)</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="dry_matter_percent" class="form-label">Dry matter, % as fed</label>
                    <input type="text" inputmode="decimal" id="dry_matter_percent" name="dry_matter_percent" class="form-control" value="<?= e($input['dry_matter_percent']) ?>" placeholder="e.g. 23.6">
                    <div class="form-text">Needed to turn a dry-matter target into kg of fresh feed.</div>
                </div>
                <div class="col-md-3">
                    <label for="crude_protein_percent" class="form-label">Crude protein, % of dry matter</label>
                    <input type="text" inputmode="decimal" id="crude_protein_percent" name="crude_protein_percent" class="form-control" value="<?= e($input['crude_protein_percent']) ?>" placeholder="e.g. 8.0">
                </div>
                <div class="col-md-3">
                    <label for="me_mj_per_kg_dm" class="form-label">Metabolizable energy, MJ per kg dry matter</label>
                    <input type="text" inputmode="decimal" id="me_mj_per_kg_dm" name="me_mj_per_kg_dm" class="form-control" value="<?= e($input['me_mj_per_kg_dm']) ?>" placeholder="e.g. 7.5">
                    <div class="form-text">For ruminant feeds.</div>
                </div>
                <div class="col-md-3">
                    <label for="energy_kcal_per_kg" class="form-label">Energy, kcal per kg as fed</label>
                    <input type="text" inputmode="numeric" id="energy_kcal_per_kg" name="energy_kcal_per_kg" class="form-control" value="<?= e($input['energy_kcal_per_kg']) ?>" placeholder="e.g. 3800">
                    <div class="form-text">For dog and cat foods.</div>
                </div>
            </div>

            <h2 class="form-section-title">Description</h2>
            <div class="row g-3">
                <div class="col-12">
                    <label for="description" class="form-label">What it is *</label>
                    <textarea id="description" name="description" rows="3" class="form-control" required><?= e($input['description']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="availability_note" class="form-label">Availability in Sri Lanka</label>
                    <textarea id="availability_note" name="availability_note" rows="3" class="form-control"><?= e($input['availability_note']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="cautions" class="form-label">Cautions</label>
                    <textarea id="cautions" name="cautions" rows="3" class="form-control"><?= e($input['cautions']) ?></textarea>
                    <div class="form-text">Species it must not be fed to, limits, spoilage, toxicity.</div>
                </div>
            </div>

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

            <button type="submit" class="btn btn-success mt-4"><?= $editing ? 'Save changes' : 'Create feed type' ?></button>
        </form>
    </div>
</div>
<?php admin_footer();
