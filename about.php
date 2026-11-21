<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About & sources | ' . APP_NAME;
$metaDescription = 'What this open reference contains, how reliable it is, where the data comes from and how to contribute.';

$review = [];
foreach (['animals' => 'Animals', 'diseases' => 'Diseases', 'body_parts' => 'Body parts'] as $table => $label) {
    $row = $pdo->query(
        "SELECT SUM(review_status = 'reviewed') AS reviewed,
                SUM(review_status = 'unreviewed') AS unreviewed
         FROM {$table}"
    )->fetch();
    $review[$label] = ['reviewed' => (int) $row['reviewed'], 'unreviewed' => (int) $row['unreviewed']];
}

$sources = $pdo->query(
    'SELECT title, publisher, url, license, source_type
     FROM sources
     ORDER BY FIELD(source_type, \'ontology\', \'database\', \'manual\', \'guideline\', \'article\', \'other\'), title ASC'
)->fetchAll();

$sourceGroups = [];
$typeLabels = [
    'ontology' => 'Ontologies and identifiers',
    'database' => 'Databases and organizations',
    'manual' => 'Veterinary manuals',
    'guideline' => 'Guidelines',
    'article' => 'Journal articles',
    'other' => 'Other',
];
foreach ($sources as $source) {
    $sourceGroups[$source['source_type']][] = $source;
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <h1 class="section-heading mb-4">About &amp; sources</h1>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="panel p-4 mb-4">
                <h2 class="h4 section-heading">What this is</h2>
                <p>An open-source reference for biologists, veterinarians and students. It links <strong>animals</strong> to their <strong>body parts</strong> and to the <strong>diseases</strong> that affect them, so you can move between all three: from a species to its organs, from an organ to the diseases that attack it, or from a set of signs to the diseases that share them.</p>
                <p class="mb-0">It is a reference, not a clinical decision tool. It does not give treatment or dosage advice.</p>
            </div>

            <div class="panel p-4 mb-4">
                <h2 class="h4 section-heading">How reliable is it?</h2>
                <p>Every animal, disease and body-part record carries a review status:</p>
                <ul>
                    <li><?= review_badge('reviewed') ?> a qualified reviewer has checked the record against its cited sources.</li>
                    <li><?= review_badge('unreviewed') ?> the record is a starting point. It was drafted from general knowledge and linked to references, but nobody qualified has verified it yet.</li>
                    <li><?= review_badge('draft') ?> work in progress, visible only to administrators.</li>
                </ul>
                <div class="table-responsive">
                    <table class="table table-sm w-auto">
                        <thead><tr><th>Records</th><th>Reviewed</th><th>Not yet reviewed</th></tr></thead>
                        <tbody>
                            <?php foreach ($review as $label => $counts): ?>
                                <tr><td><?= e($label) ?></td><td><?= $counts['reviewed'] ?></td><td><?= $counts['unreviewed'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="mb-0"><strong>The sample data shipped with this project has not been expert-reviewed.</strong> If you are a veterinarian or biologist, your corrections are the most valuable contribution you can make.</p>
            </div>

            <div class="panel p-4 mb-4">
                <h2 class="h4 section-heading">Known limitations</h2>
                <ul class="mb-0">
                    <li>Coverage is small. Domestic mammals and poultry are the most complete; most wild species have little or no anatomy or disease data yet.</li>
                    <li>Anatomy currently uses vertebrate terms. Invertebrates (for example insects) and fish-specific structures such as gills are not covered yet.</li>
                    <li>Conservation statuses change when species are reassessed. Check the <a href="https://www.iucnredlist.org/" target="_blank" rel="noopener noreferrer">IUCN Red List</a> for the current status.</li>
                    <li>Reporting duties for notifiable diseases differ by country. “WOAH-listed” is a hint, not legal advice.</li>
                </ul>
            </div>

            <div class="panel p-4 mb-4">
                <h2 class="h4 section-heading">Contributing</h2>
                <p>Corrections and additions are welcome. Veterinarians and biologists can propose fixes to any record; developers can improve the code or add data importers. See <code>CONTRIBUTING.md</code> in the project folder for the guidelines, including the rules for citing sources and for what may not be copied (for example, text from commercial manuals).</p>
                <p class="mb-0">Code is released under the MIT license. The original data and text are released under CC BY 4.0. Third-party sources keep their own terms, listed on the right.</p>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel p-4" id="sources">
                <h2 class="h4 section-heading">Sources and licenses</h2>
                <p class="small text-muted">Pages cite the sources below. Where a source is marked “link only”, its text is not reproduced here; follow the link to read it.</p>
                <?php foreach ($sourceGroups as $type => $group): ?>
                    <h3 class="h6 mt-3"><?= e($typeLabels[$type] ?? $type) ?></h3>
                    <ul class="list-unstyled small source-list">
                        <?php foreach ($group as $source): ?>
                            <?php $url = safe_url($source['url']); ?>
                            <li class="mb-2">
                                <?php if ($url !== null): ?>
                                    <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($source['title']) ?></a>
                                <?php else: ?>
                                    <?= e($source['title']) ?>
                                <?php endif; ?>
                                <?php $meta = array_filter([$source['publisher'], $source['license']]); ?>
                                <?php if ($meta): ?><div class="text-muted"><?= e(implode(' · ', $meta)) ?></div><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
