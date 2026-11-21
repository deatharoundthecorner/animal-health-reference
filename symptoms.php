<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Symptom lookup | ' . APP_NAME;
$metaDescription = 'Select clinical signs to see which recorded diseases are associated with them. A reference aid, not a diagnostic tool.';

$animalOptions = $pdo->query("SELECT id, common_name FROM animals WHERE review_status <> 'draft' ORDER BY common_name")->fetchAll();
$allSymptoms = $pdo->query('SELECT id, symptom_name FROM symptoms ORDER BY symptom_name')->fetchAll();

$animalId = get_int('animal');

$selected = [];
if (isset($_GET['s']) && is_array($_GET['s'])) {
    foreach (array_slice($_GET['s'], 0, 25) as $value) {
        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            $selected[(int) $value] = (int) $value;
        }
    }
}
$selected = array_values($selected);

$results = [];
if ($selected) {
    $in = [];
    $params = [];
    foreach ($selected as $i => $symptomId) {
        $in[] = ':s' . $i;
        $params['s' . $i] = $symptomId;
    }
    $animalFilter = '';
    if ($animalId > 0) {
        $animalFilter = ' AND EXISTS (SELECT 1 FROM animal_diseases ad WHERE ad.disease_id = d.id AND ad.animal_id = :animal_id)';
        $params['animal_id'] = $animalId;
    }

    $results = run_query(
        $pdo,
        "SELECT d.id, d.disease_name, d.pathogen_type, d.is_zoonotic, d.woah_listed,
                COUNT(DISTINCT ds.symptom_id) AS matches,
                GROUP_CONCAT(DISTINCT s.symptom_name ORDER BY s.symptom_name SEPARATOR '||') AS matched,
                (SELECT COUNT(*) FROM disease_symptoms t WHERE t.disease_id = d.id) AS total_signs
         FROM diseases d
         INNER JOIN disease_symptoms ds ON ds.disease_id = d.id
         INNER JOIN symptoms s ON s.id = ds.symptom_id
         WHERE d.review_status <> 'draft'
           AND ds.symptom_id IN (" . implode(',', $in) . "){$animalFilter}
         GROUP BY d.id
         ORDER BY matches DESC, d.disease_name ASC
         LIMIT 50",
        $params
    )->fetchAll();
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <h1 class="section-heading mb-1">Symptom lookup</h1>
    <p class="text-muted">Select the clinical signs you are interested in (and optionally a species) to see which recorded diseases are associated with them, ranked by how many of the selected signs they share.</p>

    <div class="alert alert-warning">
        <strong>This is a reference aid, not a diagnostic tool.</strong>
        Many diseases share the same signs, the database is incomplete, and a ranking by matching signs says nothing about how likely a disease is. A veterinarian must assess any sick animal.
    </div>

    <form method="get" class="panel p-4 mb-4">
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label for="animal" class="form-label">Species (optional)</label>
                <select id="animal" name="animal" class="form-select">
                    <option value="0">Any species</option>
                    <?php foreach ($animalOptions as $option): ?>
                        <option value="<?= (int) $option['id'] ?>" <?= $animalId === (int) $option['id'] ? 'selected' : '' ?>><?= e($option['common_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <fieldset>
            <legend class="form-label fs-6">Signs and symptoms</legend>
            <div class="symptom-grid">
                <?php foreach ($allSymptoms as $symptom): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="s[]" value="<?= (int) $symptom['id'] ?>" id="s<?= (int) $symptom['id'] ?>" <?= in_array((int) $symptom['id'], $selected, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="s<?= (int) $symptom['id'] ?>"><?= e($symptom['symptom_name']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-success" type="submit">Look up diseases</button>
            <a class="btn btn-outline-secondary" href="symptoms.php">Clear</a>
        </div>
    </form>

    <?php if ($selected): ?>
        <h2 class="h4 section-heading"><?= count($results) ?> disease<?= count($results) === 1 ? '' : 's' ?> associated with the selected signs</h2>

        <?php if ($results): ?>
            <div class="row g-3">
                <?php foreach ($results as $disease): ?>
                    <?php $percent = (int) round(((int) $disease['matches'] / count($selected)) * 100); ?>
                    <div class="col-md-6">
                        <div class="card entity-card h-100">
                            <div class="card-body">
                                <div class="mb-1">
                                    <span class="badge text-bg-light border"><?= e(pathogen_label($disease['pathogen_type'])) ?></span>
                                    <?= disease_flags($disease) ?>
                                </div>
                                <h3 class="h5"><a href="disease-details.php?id=<?= (int) $disease['id'] ?>"><?= e($disease['disease_name']) ?></a></h3>
                                <p class="small mb-1">Matches <strong><?= (int) $disease['matches'] ?></strong> of <?= count($selected) ?> selected sign<?= count($selected) === 1 ? '' : 's' ?>
                                    <span class="text-muted">(the entry lists <?= (int) $disease['total_signs'] ?> in total)</span></p>
                                <div class="match-bar mb-2" role="img" aria-label="<?= $percent ?> percent of selected signs matched"><div style="width: <?= $percent ?>%"></div></div>
                                <?php foreach (explode('||', (string) $disease['matched']) as $name): ?>
                                    <span class="chip"><?= e($name) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No recorded disease matches these signs<?= $animalId > 0 ? ' for the chosen species' : '' ?>. This does not mean that no disease is present.</div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
