<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = get_int('id');
if ($id <= 0) {
    header('Location: diseases.php');
    exit;
}

$disease = run_query(
    $pdo,
    "SELECT * FROM diseases WHERE id = :id AND review_status <> 'draft' LIMIT 1",
    ['id' => $id]
)->fetch();

if (!$disease) {
    http_response_code(404);
    $pageTitle = 'Disease not found | ' . APP_NAME;
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<main id="main" class="container py-5"><div class="alert alert-danger">Disease record not found.</div><a href="diseases.php" class="btn btn-success">Back to diseases</a></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$symptoms = run_query(
    $pdo,
    'SELECT s.id, s.symptom_name
     FROM disease_symptoms ds
     INNER JOIN symptoms s ON s.id = ds.symptom_id
     WHERE ds.disease_id = :id
     ORDER BY s.symptom_name ASC',
    ['id' => $id]
)->fetchAll();

$animals = run_query(
    $pdo,
    "SELECT a.id, a.common_name, a.animal_type, ad.notes
     FROM animal_diseases ad
     INNER JOIN animals a ON a.id = ad.animal_id
     WHERE ad.disease_id = :id AND a.review_status <> 'draft'
     ORDER BY a.common_name ASC",
    ['id' => $id]
)->fetchAll();

$bodyParts = run_query(
    $pdo,
    "SELECT bp.id, bp.part_name, bs.system_name, dbp.notes
     FROM disease_body_parts dbp
     INNER JOIN body_parts bp ON bp.id = dbp.body_part_id
     INNER JOIN body_systems bs ON bs.id = bp.system_id
     WHERE dbp.disease_id = :id AND bp.review_status <> 'draft'
     ORDER BY bs.sort_order, bp.part_name",
    ['id' => $id]
)->fetchAll();

$sources = fetch_sources($pdo, 'disease', $id);
$ontologyUrl = ontology_url($disease['disease_ontology_id']);

$pageTitle = $disease['disease_name'] . ' | Diseases | ' . APP_NAME;
$metaDescription = excerpt((string) $disease['summary'], 160);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="diseases.php">Diseases</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($disease['disease_name']) ?></li>
        </ol>
    </nav>

    <div class="details-box p-4 p-lg-5 mb-4">
        <div class="mb-2">
            <span class="badge text-bg-light border"><?= e(pathogen_label($disease['pathogen_type'])) ?></span>
            <?= disease_flags($disease) ?>
            <?= review_badge($disease['review_status']) ?>
        </div>
        <h1 class="section-heading h2 mb-1"><?= e($disease['disease_name']) ?></h1>
        <?php if (!empty($disease['pathogen_name'])): ?>
            <p class="fst-italic text-muted mb-3"><?= e($disease['pathogen_name']) ?></p>
        <?php endif; ?>
        <p class="mb-2"><?= nl2br(e($disease['summary'])) ?></p>
        <?php if ($ontologyUrl !== null): ?>
            <p class="small text-muted mb-0">Ontology term: <a href="<?= e($ontologyUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($disease['disease_ontology_id']) ?></a> (Disease Ontology)</p>
        <?php endif; ?>
    </div>

    <div class="alert alert-warning small">
        <strong>For reference only.</strong> Signs overlap between many diseases, and this page cannot be used to diagnose or treat an animal.
        <?= $disease['review_status'] === 'unreviewed' ? 'This entry has not yet been checked by a qualified reviewer; verify it against the sources below.' : '' ?>
        <?= !empty($disease['woah_listed']) ? 'This disease is listed by WOAH and is usually reportable to the veterinary authorities; check your local rules.' : '' ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="panel p-4 mb-4">
                <h2 class="h5 section-heading">Signs and symptoms</h2>
                <?php if ($symptoms): ?>
                    <div>
                        <?php foreach ($symptoms as $symptom): ?>
                            <a class="chip" href="diseases.php?symptom=<?= (int) $symptom['id'] ?>" title="Other diseases with this sign"><?= e($symptom['symptom_name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Select a sign to see other diseases that share it.</p>
                <?php else: ?>
                    <p class="text-muted mb-0">No signs have been recorded yet.</p>
                <?php endif; ?>
            </div>

            <div class="panel p-4 mb-4">
                <h2 class="h5 section-heading">Transmission</h2>
                <p class="mb-0"><?= !empty($disease['transmission']) ? nl2br(e($disease['transmission'])) : '<span class="text-muted">Not recorded yet.</span>' ?></p>
            </div>

            <div class="panel p-4">
                <h2 class="h5 section-heading">Prevention and control</h2>
                <p class="mb-0"><?= !empty($disease['prevention']) ? nl2br(e($disease['prevention'])) : '<span class="text-muted">Not recorded yet.</span>' ?></p>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel p-4 mb-4">
                <h2 class="h5 section-heading">Species affected</h2>
                <?php if ($animals): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($animals as $animal): ?>
                            <li class="mb-2">
                                <a href="animal-details.php?id=<?= (int) $animal['id'] ?>"><?= e($animal['common_name']) ?></a>
                                <?php if (!empty($animal['notes'])): ?>
                                    <div class="small text-muted"><?= e($animal['notes']) ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No species have been linked yet.</p>
                <?php endif; ?>
            </div>

            <div class="panel p-4 mb-4">
                <h2 class="h5 section-heading">Body parts affected</h2>
                <?php if ($bodyParts): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($bodyParts as $part): ?>
                            <li class="mb-2">
                                <a href="body-part-details.php?id=<?= (int) $part['id'] ?>"><?= e($part['part_name']) ?></a>
                                <span class="small text-muted">(<?= e($part['system_name']) ?>)</span>
                                <?php if (!empty($part['notes'])): ?>
                                    <div class="small text-muted"><?= e($part['notes']) ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No body parts have been linked yet.</p>
                <?php endif; ?>
            </div>

            <div class="panel p-4">
                <h2 class="h5 section-heading">Sources</h2>
                <?= render_sources($sources) ?>
            </div>
        </div>
    </div>

    <a href="diseases.php" class="btn btn-success mt-4">Back to diseases</a>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
