<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$kindCounts = count_animals_by_kind($pdo);
$causeCounts = count_diseases_by_cause($pdo);
$collections = count_disease_collections($pdo);
$threatened = count_threatened_animals($pdo);
$systems = systems_with_counts($pdo);

$totals = [
    'animals' => array_sum($kindCounts),
    'diseases' => array_sum($causeCounts),
    'body_parts' => array_sum(array_map(static fn (array $s): int => (int) $s['part_count'], $systems)),
];

$kindLabels = ['companion' => 'Companion animals', 'livestock' => 'Livestock', 'wildlife' => 'Wildlife', 'other' => 'Other animals'];

$pageTitle = APP_NAME;

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<header class="hero-section">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-9">
                <h1 class="display-5 fw-bold">Animal anatomy and disease reference for biologists and veterinarians</h1>
                <p class="lead mt-3 mb-4">Look up a species, see its body parts, and find the diseases that affect it. Every entry links to its sources, and records show whether an expert has reviewed them.</p>

                <form action="search.php" method="get" class="row g-2 mb-3" role="search">
                    <div class="col-md-8 col-lg-7">
                        <label for="homeSearch" class="visually-hidden">Search</label>
                        <input id="homeSearch" type="search" name="q" class="form-control form-control-lg" placeholder="Try “parvovirus”, “rumen” or “horse”…" maxlength="100" required minlength="2">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-light btn-lg" type="submit">Search</button>
                    </div>
                </form>
                <p class="small mb-0 text-white-50"><?= $totals['animals'] ?> animals · <?= $totals['body_parts'] ?> body parts · <?= $totals['diseases'] ?> diseases</p>
            </div>
        </div>
    </div>
</header>

<main id="main">
    <section class="py-5">
        <div class="container">
            <h2 class="section-heading h3 mb-3">Start with what you know</h2>
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3">
                    <a class="explore-card" href="animals.php">
                        <h3 class="h5">I know the animal</h3>
                        <p class="small text-muted mb-0">Open a species to see its taxonomy, body parts and the diseases that affect it.</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a class="explore-card" href="body-parts.php">
                        <h3 class="h5">I know the organ</h3>
                        <p class="small text-muted mb-0">See which species have it, how it differs between them and which diseases affect it.</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a class="explore-card" href="diseases.php">
                        <h3 class="h5">I know the disease</h3>
                        <p class="small text-muted mb-0">Cause, signs, affected species and organs, transmission and prevention.</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a class="explore-card" href="symptoms.php">
                        <h3 class="h5">I have clinical signs</h3>
                        <p class="small text-muted mb-0">Pick the signs you see and compare the recorded diseases that share them.</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            <h2 class="section-heading h3 mb-3">Browse by…</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="browse-panel">
                        <h3>Kind of animal</h3>
                        <div class="list-group list-group-flush pb-2">
                            <?php foreach ($kindLabels as $kind => $label): ?>
                                <?php if ($kindCounts[$kind] > 0): ?>
                                    <a class="list-group-item" href="<?= e(page_url('animals.php', ['type' => $kind])) ?>"><span><?= e($label) ?></span><span class="count"><?= $kindCounts[$kind] ?></span></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <a class="list-group-item" href="animals.php?threatened=1"><span>Endangered animals</span><span class="count"><?= $threatened ?></span></a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="browse-panel">
                        <h3>Body system</h3>
                        <div class="list-group list-group-flush pb-2">
                            <?php foreach ($systems as $system): ?>
                                <a class="list-group-item" href="<?= e(page_url('body-parts.php', ['system' => (int) $system['id']])) ?>"><span><?= e($system['system_name']) ?></span><span class="count"><?= (int) $system['part_count'] ?></span></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="browse-panel">
                        <h3>Cause of disease</h3>
                        <div class="list-group list-group-flush pb-2">
                            <?php foreach (PATHOGEN_TYPES as $type => $label): ?>
                                <?php if ($causeCounts[$type] > 0): ?>
                                    <a class="list-group-item" href="<?= e(page_url('diseases.php', ['pathogen' => $type])) ?>"><span><?= e($label) ?></span><span class="count"><?= $causeCounts[$type] ?></span></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="browse-panel">
                        <h3>Special collections</h3>
                        <div class="list-group list-group-flush pb-2">
                            <a class="list-group-item" href="diseases.php?zoonotic=1"><span>Zoonotic diseases</span><span class="count"><?= $collections['zoonotic'] ?></span></a>
                            <a class="list-group-item" href="diseases.php?woah=1"><span>WOAH-listed diseases</span><span class="count"><?= $collections['woah'] ?></span></a>
                            <a class="list-group-item" href="animals.php?threatened=1"><span>Endangered animals</span><span class="count"><?= $threatened ?></span></a>
                        </div>
                        <p class="small text-muted px-3 pb-3 mb-0">Zoonotic diseases can spread between animals and people. WOAH-listed diseases are usually reportable to the veterinary authorities.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            <div class="panel p-4 p-lg-5">
                <h2 class="section-heading h4">How reliable is the information?</h2>
                <p class="mb-2">Each record shows a review badge:</p>
                <ul class="mb-3">
                    <li><?= review_badge('reviewed') ?> checked by a qualified reviewer against the cited sources.</li>
                    <li><?= review_badge('unreviewed') ?> published as a starting point; verify it against the sources before relying on it.</li>
                </ul>
                <p class="mb-0">Entries cite their sources, and anatomy, disease and taxonomy records use public identifiers (Uberon, Disease Ontology, NCBI Taxonomy) so the data stays compatible with other biological databases. <a href="about.php">Read more about the data and how to contribute</a>.</p>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
