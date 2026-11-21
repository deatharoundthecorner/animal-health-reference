<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = get_int('id');
if ($id <= 0) {
    header('Location: body-parts.php');
    exit;
}

$part = run_query(
    $pdo,
    "SELECT bp.*, bs.system_name
     FROM body_parts bp
     INNER JOIN body_systems bs ON bs.id = bp.system_id
     WHERE bp.id = :id AND bp.review_status <> 'draft'
     LIMIT 1",
    ['id' => $id]
)->fetch();

if (!$part) {
    http_response_code(404);
    $pageTitle = 'Body part not found | ' . APP_NAME;
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<main id="main" class="container py-5"><div class="alert alert-danger">Body part not found.</div><a href="body-parts.php" class="btn btn-success">Back to anatomy</a></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$animals = run_query(
    $pdo,
    "SELECT a.id, a.common_name, a.scientific_name, a.animal_type, abp.notes
     FROM animal_body_parts abp
     INNER JOIN animals a ON a.id = abp.animal_id
     WHERE abp.body_part_id = :id AND a.review_status <> 'draft'
     ORDER BY a.common_name ASC",
    ['id' => $id]
)->fetchAll();

$withNotes = array_values(array_filter($animals, static fn (array $a): bool => !empty($a['notes'])));
$withoutNotes = array_values(array_filter($animals, static fn (array $a): bool => empty($a['notes'])));

$diseases = run_query(
    $pdo,
    "SELECT d.id, d.disease_name, d.pathogen_type, d.is_zoonotic, d.woah_listed, dbp.notes
     FROM disease_body_parts dbp
     INNER JOIN diseases d ON d.id = dbp.disease_id
     WHERE dbp.body_part_id = :id AND d.review_status <> 'draft'
     ORDER BY d.disease_name ASC",
    ['id' => $id]
)->fetchAll();

$sources = fetch_sources($pdo, 'body_part', $id);
$ontologyUrl = ontology_url($part['uberon_id']);

$pageTitle = $part['part_name'] . ' | Anatomy | ' . APP_NAME;
$metaDescription = excerpt((string) $part['description'], 160);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="body-parts.php">Anatomy</a></li>
            <li class="breadcrumb-item"><a href="body-parts.php?system=<?= (int) $part['system_id'] ?>"><?= e($part['system_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($part['part_name']) ?></li>
        </ol>
    </nav>

    <div class="details-box p-4 p-lg-5 mb-4">
        <div class="mb-2">
            <span class="badge text-bg-light border"><?= e($part['system_name']) ?> system</span>
            <?= review_badge($part['review_status']) ?>
        </div>
        <h1 class="section-heading h2"><?= e($part['part_name']) ?></h1>
        <p class="mb-2"><?= nl2br(e($part['description'])) ?></p>
        <?php if ($ontologyUrl !== null): ?>
            <p class="small text-muted mb-0">
                Ontology term:
                <a href="<?= e($ontologyUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($part['uberon_id']) ?></a> (Uberon)
            </p>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="panel p-4 mb-4">
                <h2 class="h5 section-heading">How it differs between species</h2>
                <?php if ($withNotes): ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Species</th><th>Notes</th></tr></thead>
                            <tbody>
                                <?php foreach ($withNotes as $animal): ?>
                                    <tr>
                                        <td class="text-nowrap"><a href="animal-details.php?id=<?= (int) $animal['id'] ?>"><?= e($animal['common_name']) ?></a></td>
                                        <td class="small"><?= e($animal['notes']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No species-specific notes have been added for this body part yet.</p>
                <?php endif; ?>
            </div>

            <div class="panel p-4">
                <h2 class="h5 section-heading">Species with this body part</h2>
                <?php if ($animals): ?>
                    <?php foreach (array_merge($withNotes, $withoutNotes) as $animal): ?>
                        <a class="chip" href="animal-details.php?id=<?= (int) $animal['id'] ?>"><?= e($animal['common_name']) ?></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No species have been linked to this body part yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel p-4 mb-4">
                <h2 class="h5 section-heading">Diseases that affect it</h2>
                <?php if ($diseases): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($diseases as $disease): ?>
                            <li class="mb-3">
                                <a href="disease-details.php?id=<?= (int) $disease['id'] ?>"><?= e($disease['disease_name']) ?></a>
                                <?= disease_flags($disease) ?>
                                <?php if (!empty($disease['notes'])): ?>
                                    <div class="small text-muted"><?= e($disease['notes']) ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No diseases have been linked to this body part yet.</p>
                <?php endif; ?>
            </div>

            <div class="panel p-4">
                <h2 class="h5 section-heading">Sources</h2>
                <?= render_sources($sources) ?>
            </div>
        </div>
    </div>

    <a href="body-parts.php" class="btn btn-success mt-4">Back to anatomy</a>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
