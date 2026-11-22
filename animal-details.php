<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = get_int('id');
if ($id <= 0) {
    header('Location: animals.php');
    exit;
}

$animal = run_query(
    $pdo,
    "SELECT a.*, c.category_name
     FROM animals a
     INNER JOIN categories c ON c.id = a.category_id
     WHERE a.id = :id AND a.review_status <> 'draft'
     LIMIT 1",
    ['id' => $id]
)->fetch();

if (!$animal) {
    http_response_code(404);
    $pageTitle = 'Animal not found | ' . APP_NAME;
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<main id="main" class="container py-5"><div class="alert alert-danger">Animal record not found.</div><a href="animals.php" class="btn btn-success">Back to animals</a></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$bodyParts = run_query(
    $pdo,
    "SELECT bp.id, bp.part_name, bs.system_name, abp.notes
     FROM animal_body_parts abp
     INNER JOIN body_parts bp ON bp.id = abp.body_part_id
     INNER JOIN body_systems bs ON bs.id = bp.system_id
     WHERE abp.animal_id = :id AND bp.review_status <> 'draft'
     ORDER BY bs.sort_order, bs.system_name, bp.part_name",
    ['id' => $id]
)->fetchAll();

$partsBySystem = [];
foreach ($bodyParts as $part) {
    $partsBySystem[$part['system_name']][] = $part;
}

$diseases = run_query(
    $pdo,
    "SELECT d.id, d.disease_name, d.pathogen_type, d.is_zoonotic, d.woah_listed, d.review_status, ad.notes
     FROM animal_diseases ad
     INNER JOIN diseases d ON d.id = ad.disease_id
     WHERE ad.animal_id = :id AND d.review_status <> 'draft'
     ORDER BY d.disease_name ASC",
    ['id' => $id]
)->fetchAll();

$sources = fetch_sources($pdo, 'animal', $id);

// Feeding guide (only when the feeding tables are installed)
require_once __DIR__ . '/includes/feeding.php';
$feedingOn = feeding_available($pdo);
$feedingPlans = [];
$unsafeFoods = [];
if ($feedingOn) {
    $feedingPlans = feeding_plans_for_animal($pdo, $id);
    $unsafeFoods = run_query(
        $pdo,
        "SELECT food_name, severity, reason FROM animal_unsafe_foods WHERE animal_id = :id ORDER BY FIELD(severity, 'toxic', 'avoid', 'limit'), food_name",
        ['id' => $id]
    )->fetchAll();
}
$showFeeding = $feedingOn && ($feedingPlans || $unsafeFoods);

$lineage = array_filter([
    $animal['class_name'] ?? '',
    $animal['order_name'] ?? '',
    $animal['family_name'] ?? '',
]);

$pageTitle = $animal['common_name'] . ' | ' . APP_NAME;
$metaDescription = excerpt((string) $animal['description'], 160);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="animals.php">Animals</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($animal['common_name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4 align-items-start">
        <div class="col-lg-5">
            <img src="<?= e(animal_image($animal['image'])) ?>" alt="<?= e($animal['common_name']) ?>" class="details-image">
        </div>
        <div class="col-lg-7">
            <div class="details-box p-4 p-lg-5">
                <div class="mb-2">
                    <span class="badge text-bg-light border"><?= e(ANIMAL_TYPES[$animal['animal_type']] ?? $animal['animal_type']) ?></span>
                    <?= conservation_badge($animal['conservation_status']) ?>
                    <?= review_badge($animal['review_status']) ?>
                </div>
                <h1 class="section-heading h2 mb-1"><?= e($animal['common_name']) ?></h1>
                <p class="fst-italic text-muted mb-1"><?= e($animal['scientific_name']) ?> <span class="small">(<?= e($animal['taxon_rank']) ?>)</span></p>
                <?php if ($lineage): ?>
                    <p class="lineage mb-3">
                        <?php foreach ($lineage as $rank): ?><span><?= e($rank) ?></span><?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <p><?= nl2br(e($animal['description'])) ?></p>

                <dl class="row fact-list small mb-0">
                    <dt class="col-sm-4">Group</dt><dd class="col-sm-8"><?= e($animal['category_name']) ?></dd>
                    <?php if ($animal['habitat'] !== ''): ?><dt class="col-sm-4">Habitat</dt><dd class="col-sm-8"><?= e($animal['habitat']) ?></dd><?php endif; ?>
                    <?php if ($animal['diet'] !== ''): ?><dt class="col-sm-4">Diet</dt><dd class="col-sm-8"><?= e($animal['diet']) ?></dd><?php endif; ?>
                    <?php if ($animal['country'] !== ''): ?><dt class="col-sm-4">Range</dt><dd class="col-sm-8"><?= e($animal['country']) ?></dd><?php endif; ?>
                    <?php if ($animal['lifespan'] !== ''): ?><dt class="col-sm-4">Lifespan</dt><dd class="col-sm-8"><?= e($animal['lifespan']) ?></dd><?php endif; ?>
                    <?php $ncbi = ncbi_taxon_url($animal['ncbi_taxon_id'] !== null ? (int) $animal['ncbi_taxon_id'] : null); ?>
                    <?php if ($ncbi !== null): ?>
                        <dt class="col-sm-4">NCBI Taxonomy</dt>
                        <dd class="col-sm-8"><a href="<?= e($ncbi) ?>" target="_blank" rel="noopener noreferrer">taxid <?= (int) $animal['ncbi_taxon_id'] ?></a></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <div class="panel p-3 p-lg-4 mt-4">
        <ul class="nav nav-tabs" id="animalTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-anatomy" data-bs-toggle="tab" data-bs-target="#pane-anatomy" type="button" role="tab" aria-controls="pane-anatomy" aria-selected="true">
                    Anatomy <span class="badge text-bg-secondary"><?= count($bodyParts) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-diseases" data-bs-toggle="tab" data-bs-target="#pane-diseases" type="button" role="tab" aria-controls="pane-diseases" aria-selected="false">
                    Diseases <span class="badge text-bg-secondary"><?= count($diseases) ?></span>
                </button>
            </li>
            <?php if ($showFeeding): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-feeding" data-bs-toggle="tab" data-bs-target="#pane-feeding" type="button" role="tab" aria-controls="pane-feeding" aria-selected="false">
                        Feeding <span class="badge text-bg-secondary"><?= count($feedingPlans) ?></span>
                    </button>
                </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-sources" data-bs-toggle="tab" data-bs-target="#pane-sources" type="button" role="tab" aria-controls="pane-sources" aria-selected="false">
                    Sources <span class="badge text-bg-secondary"><?= count($sources) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content pt-4">
            <div class="tab-pane fade show active" id="pane-anatomy" role="tabpanel" aria-labelledby="tab-anatomy" tabindex="0">
                <?php if ($partsBySystem): ?>
                    <?php foreach ($partsBySystem as $systemName => $parts): ?>
                        <h2 class="system-heading h5"><?= e($systemName) ?></h2>
                        <div class="list-group mb-4">
                            <?php foreach ($parts as $part): ?>
                                <a href="body-part-details.php?id=<?= (int) $part['id'] ?>" class="list-group-item list-group-item-action">
                                    <strong><?= e($part['part_name']) ?></strong>
                                    <?php if (!empty($part['notes'])): ?>
                                        <div class="small text-muted"><?= e($part['notes']) ?></div>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Anatomy has not been added for this animal yet.</p>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="pane-diseases" role="tabpanel" aria-labelledby="tab-diseases" tabindex="0">
                <?php if ($diseases): ?>
                    <div class="alert alert-warning small">The list below is for reference and is not complete. It must not be used to diagnose an animal.</div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr><th>Disease</th><th>Cause</th><th>Flags</th><th>Notes for this species</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diseases as $disease): ?>
                                    <tr>
                                        <td>
                                            <a href="disease-details.php?id=<?= (int) $disease['id'] ?>"><?= e($disease['disease_name']) ?></a>
                                            <?php if ($disease['review_status'] === 'reviewed'): ?><span class="badge text-bg-success">Reviewed</span><?php endif; ?>
                                        </td>
                                        <td><?= e(pathogen_label($disease['pathogen_type'])) ?></td>
                                        <td><?= disease_flags($disease) ?></td>
                                        <td class="small"><?= e((string) $disease['notes']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No diseases have been linked to this animal yet.</p>
                <?php endif; ?>
            </div>

            <?php if ($showFeeding): ?>
                <div class="tab-pane fade" id="pane-feeding" role="tabpanel" aria-labelledby="tab-feeding" tabindex="0">
                    <?php if ($feedingPlans): ?>
                        <p>How much to feed depends on the animal's life stage and body weight. Choose a life stage to open the calculator:</p>
                        <div class="mb-4">
                            <?php foreach ($feedingPlans as $plan): ?>
                                <a class="chip" href="<?= e(page_url('feeding.php', ['animal' => $id, 'stage' => $plan['life_stage']])) ?>"><?= e($plan['life_stage']) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <p class="small text-muted">Amounts are starting points from published guidelines and have not all been reviewed by a veterinarian or animal nutritionist. See the <a href="feeds.php">feed types guide</a> for what each feed contains.</p>
                    <?php endif; ?>
                    <?php if ($unsafeFoods): ?>
                        <h2 class="h6 section-heading mt-3">Foods to avoid or limit</h2>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($unsafeFoods as $food): ?>
                                <?php $badge = match ($food['severity']) { 'toxic' => 'text-bg-danger', 'avoid' => 'text-bg-warning', default => 'text-bg-secondary' }; ?>
                                <li class="mb-2">
                                    <span class="badge <?= $badge ?>"><?= e(UNSAFE_SEVERITIES[$food['severity']] ?? $food['severity']) ?></span>
                                    <strong><?= e($food['food_name']) ?></strong>
                                    <div class="small text-muted"><?= e($food['reason']) ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="tab-pane fade" id="pane-sources" role="tabpanel" aria-labelledby="tab-sources" tabindex="0">
                <?= render_sources($sources) ?>
            </div>
        </div>
    </div>

    <a href="animals.php" class="btn btn-success mt-4">Back to animals</a>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
