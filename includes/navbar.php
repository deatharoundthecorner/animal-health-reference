<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// Counts for the menus (only when a database connection is available).
$haveDb = isset($pdo) && $pdo instanceof PDO;
$navKinds = $haveDb ? count_animals_by_kind($pdo) : [];
$navCauses = $haveDb ? count_diseases_by_cause($pdo) : [];
$navCollections = $haveDb ? count_disease_collections($pdo) : ['zoonotic' => 0, 'woah' => 0];
$navThreatened = $haveDb ? count_threatened_animals($pdo) : 0;
$navSystems = $haveDb ? systems_with_counts($pdo) : [];

require_once __DIR__ . '/feeding.php';
$navFeeding = $haveDb && feeding_available($pdo);
$navFeedCounts = $navFeeding ? count_feeds_by_category($pdo) : [];

$kindMenuLabels = ['companion' => 'Companion animals', 'livestock' => 'Livestock', 'wildlife' => 'Wildlife', 'other' => 'Other animals'];

$isAnimals = in_array($currentPage, ['animals.php', 'animal-details.php'], true);
$isAnatomy = in_array($currentPage, ['body-parts.php', 'body-part-details.php'], true);
$isDiseases = in_array($currentPage, ['diseases.php', 'disease-details.php'], true);
$isFeeding = in_array($currentPage, ['feeding.php', 'feeds.php', 'feed-details.php'], true);
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-main py-3 sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Animal Health Reference</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $isAnimals ? 'active' : '' ?>" href="animals.php" role="button" data-bs-toggle="dropdown" aria-expanded="false">Animals</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="animals.php">All animals</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($kindMenuLabels as $kind => $label): ?>
                            <?php if (($navKinds[$kind] ?? 0) > 0): ?>
                                <li><a class="dropdown-item" href="<?= e(page_url('animals.php', ['type' => $kind])) ?>"><?= e($label) ?> <span class="count"><?= (int) $navKinds[$kind] ?></span></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="animals.php?threatened=1">Endangered animals <span class="count"><?= $navThreatened ?></span></a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $isAnatomy ? 'active' : '' ?>" href="body-parts.php" role="button" data-bs-toggle="dropdown" aria-expanded="false">Anatomy</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="body-parts.php">All body parts</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">By body system</h6></li>
                        <?php foreach ($navSystems as $system): ?>
                            <li><a class="dropdown-item" href="<?= e(page_url('body-parts.php', ['system' => (int) $system['id']])) ?>"><?= e($system['system_name']) ?> <span class="count"><?= (int) $system['part_count'] ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $isDiseases ? 'active' : '' ?>" href="diseases.php" role="button" data-bs-toggle="dropdown" aria-expanded="false">Diseases</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="diseases.php">All diseases</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">By cause</h6></li>
                        <?php foreach (PATHOGEN_TYPES as $type => $label): ?>
                            <?php if (($navCauses[$type] ?? 0) > 0): ?>
                                <li><a class="dropdown-item" href="<?= e(page_url('diseases.php', ['pathogen' => $type])) ?>"><?= e($label) ?> <span class="count"><?= (int) $navCauses[$type] ?></span></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Collections</h6></li>
                        <li><a class="dropdown-item" href="diseases.php?zoonotic=1">Zoonotic (spread to people) <span class="count"><?= $navCollections['zoonotic'] ?></span></a></li>
                        <li><a class="dropdown-item" href="diseases.php?woah=1">WOAH-listed (reportable) <span class="count"><?= $navCollections['woah'] ?></span></a></li>
                    </ul>
                </li>

                <?php if ($navFeeding): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $isFeeding ? 'active' : '' ?>" href="feeding.php" role="button" data-bs-toggle="dropdown" aria-expanded="false">Feeding</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="feeding.php">Feeding calculator</a></li>
                            <li><a class="dropdown-item" href="feeds.php">All feed types</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Feed types</h6></li>
                            <?php foreach (FEED_CATEGORIES as $key => $label): ?>
                                <?php if (($navFeedCounts[$key] ?? 0) > 0): ?>
                                    <li><a class="dropdown-item" href="<?= e(page_url('feeds.php', ['category' => $key])) ?>"><?= e($label) ?> <span class="count"><?= (int) $navFeedCounts[$key] ?></span></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'symptoms.php' ? 'active' : '' ?>" href="symptoms.php" title="Find diseases by clinical signs">Symptoms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="about.php">About</a>
                </li>
            </ul>
            <form class="d-flex me-lg-3 mb-2 mb-lg-0" role="search" action="search.php" method="get">
                <input class="form-control form-control-sm me-2" style="min-width: 9rem; max-width: 14rem;" type="search" name="q" placeholder="Search…" aria-label="Search animals, diseases and organs" maxlength="100" value="<?= $currentPage === 'search.php' ? e(get_string('q')) : '' ?>">
                <button class="btn btn-sm btn-light" type="submit">Search</button>
            </form>
            <a class="btn btn-sm btn-outline-light" href="admin/login.php">Admin</a>
        </div>
    </div>
</nav>
