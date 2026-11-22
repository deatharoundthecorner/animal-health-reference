<?php
declare(strict_types=1);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/feeding.php';

$pageTitle = 'Feeding calculator | ' . APP_NAME;
$metaDescription = 'How much to feed: choose an animal, its life stage and its body weight to see the feed types and daily amounts, from published guidelines.';

if (!feeding_available($pdo)) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<main id="main" class="container py-5"><div class="alert alert-info">The feeding guide is not installed on this site yet. An administrator can add it with <code>php tools/upgrade.php</code>.</div></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

/** "61%" / "12.5%" or a dash. */
function share_label(?float $share): string
{
    return $share === null ? '—' : rtrim(rtrim(number_format($share, 1, '.', ''), '0'), '.') . '%';
}

$animals = $pdo->query(
    "SELECT DISTINCT a.id, a.common_name, a.animal_type
     FROM animals a
     INNER JOIN feeding_plans p ON p.animal_id = a.id
     WHERE a.review_status <> 'draft' AND p.review_status <> 'draft'
     ORDER BY a.animal_type, a.common_name"
)->fetchAll();

$animalId = get_int('animal');
$animal = null;
foreach ($animals as $candidate) {
    if ((int) $candidate['id'] === $animalId) {
        $animal = $candidate;
    }
}
if ($animal === null) {
    $animalId = 0;
}

$plans = $animal !== null ? feeding_plans_for_animal($pdo, $animalId) : [];
$stages = [];
foreach ($plans as $plan) {
    $stages[$plan['life_stage']] = true;
}
$stages = array_keys($stages);

$stage = get_string('stage', 120);
if (!in_array($stage, $stages, true)) {
    $stage = count($stages) === 1 ? $stages[0] : '';
}

// Body weight
$weightRaw = str_replace(',', '.', get_string('weight', 20));
$weight = null;
$weightError = '';
if ($weightRaw !== '') {
    if (!is_numeric($weightRaw)) {
        $weightError = 'Please enter the weight as a number of kilograms.';
    } elseif ((float) $weightRaw < 0.05 || (float) $weightRaw > 1500) {
        $weightError = 'Please enter a weight between 0.05 kg and 1500 kg.';
    } else {
        $weight = (float) $weightRaw;
    }
}

// Optional: energy of the user's commercial food, from its package label
$kcalRaw = str_replace(',', '.', get_string('kcal', 12));
$kcalDiet = get_string('kcal_diet', 100);
$energyOverride = null;
$kcalError = '';
if ($kcalRaw !== '') {
    if (is_numeric($kcalRaw) && (float) $kcalRaw >= 300 && (float) $kcalRaw <= 6000) {
        $energyOverride = ['diet' => $kcalDiet, 'kcal' => (float) $kcalRaw];
    } else {
        $kcalError = 'The energy value should be between 300 and 6000 kcal per kg (it is printed on the package, sometimes as kcal per 100 g: multiply by 10).';
    }
}

// Foods to avoid for the chosen animal
$unsafeFoods = [];
if ($animal !== null) {
    $unsafeFoods = run_query(
        $pdo,
        "SELECT u.food_name, u.severity, u.reason, s.title AS source_title, s.url AS source_url
         FROM animal_unsafe_foods u
         LEFT JOIN sources s ON s.id = u.source_id
         WHERE u.animal_id = :id
         ORDER BY FIELD(u.severity, 'toxic', 'avoid', 'limit'), u.food_name",
        ['id' => $animalId]
    )->fetchAll();
}

// Calculation
$results = [];
if ($animal !== null && $stage !== '' && $weight !== null) {
    foreach ($plans as $plan) {
        if ($plan['life_stage'] !== $stage) {
            continue;
        }
        $entry = ['plan' => $plan, 'applies' => feeding_plan_applies($plan, $weight), 'target' => null, 'diets' => [], 'sources' => fetch_sources($pdo, 'plan', (int) $plan['id'])];
        if ($entry['applies']) {
            $points = $plan['method'] === 'table' ? feeding_points_for_plan($pdo, (int) $plan['id']) : [];
            $entry['target'] = feeding_plan_target($plan, $points, $weight);
            if ($entry['target']['ok']) {
                $entry['diets'] = feeding_build_diets($plan, $entry['target'], feeding_items_for_plan($pdo, (int) $plan['id']), $energyOverride);
            }
        }
        $results[] = $entry;
    }
}

$kindLabels = ['companion' => 'Companion animals', 'livestock' => 'Livestock', 'wildlife' => 'Wildlife', 'other' => 'Other animals'];
$groupedAnimals = [];
foreach ($animals as $a) {
    $groupedAnimals[$a['animal_type']][] = $a;
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main" class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Feeding calculator</li>
        </ol>
    </nav>

    <h1 class="section-heading mb-1">Feeding calculator</h1>
    <p class="text-muted">See which feeds to give and how much, by animal, life stage and body weight. The amounts come from published veterinary and animal-nutrition guidelines, and change with the weight of the animal and with the type of feed.</p>

    <div class="alert alert-warning small">
        <strong>A starting point, not a prescription.</strong>
        Every animal is different (health, age, pregnancy, work, heat). Weigh your animal regularly and adjust by about 10% to keep it in good body condition. A sick, pregnant, nursing or very young animal needs advice from a veterinarian. Entries marked “Not yet expert-reviewed” have not been checked by a qualified person.
    </div>

    <form method="get" class="panel p-4 my-4" id="feedingForm">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="animal" class="form-label fw-semibold">1. Animal</label>
                <select id="animal" name="animal" class="form-select" data-autosubmit>
                    <option value="0">Choose an animal…</option>
                    <?php foreach ($groupedAnimals as $kind => $list): ?>
                        <optgroup label="<?= e($kindLabels[$kind] ?? $kind) ?>">
                            <?php foreach ($list as $option): ?>
                                <option value="<?= (int) $option['id'] ?>" <?= $animalId === (int) $option['id'] ? 'selected' : '' ?>><?= e($option['common_name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="stage" class="form-label fw-semibold">2. Life stage or purpose</label>
                <select id="stage" name="stage" class="form-select" <?= $animal === null ? 'disabled' : '' ?>>
                    <?php if ($animal === null): ?>
                        <option value="">Choose an animal first</option>
                    <?php else: ?>
                        <?php if (count($stages) > 1): ?><option value="">Choose…</option><?php endif; ?>
                        <?php foreach ($stages as $s): ?>
                            <option value="<?= e($s) ?>" <?= $stage === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="weight" class="form-label fw-semibold">3. Weight (kg)</label>
                <input type="text" inputmode="decimal" id="weight" name="weight" class="form-control" value="<?= e($weightRaw) ?>" placeholder="e.g. 12" maxlength="8" autocomplete="off">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100">Calculate</button>
            </div>
        </div>
        <?php if ($weightError !== ''): ?><div class="text-danger small mt-2"><?= e($weightError) ?></div><?php endif; ?>
        <?php if ($animal !== null && $stage === '' && $weight !== null): ?><div class="text-danger small mt-2">Please choose a life stage.</div><?php endif; ?>
        <div class="form-text mt-2">Use the animal's current weight in kilograms. If you cannot weigh it, ask a veterinarian or livestock officer to estimate it.</div>
    </form>

    <?php if ($animal === null): ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="panel p-4 h-100">
                    <h2 class="h5 section-heading">Animals in the guide</h2>
                    <div class="row">
                        <?php foreach ($groupedAnimals as $kind => $list): ?>
                            <div class="col-sm-6 mb-3">
                                <h3 class="h6"><?= e($kindLabels[$kind] ?? $kind) ?></h3>
                                <?php foreach ($list as $option): ?>
                                    <a class="chip" href="<?= e(page_url('feeding.php', ['animal' => (int) $option['id'], 'weight' => $weightRaw])) ?>"><?= e($option['common_name']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="small text-muted mb-0">Looking for a feed type instead? See the <a href="feeds.php">feed types guide</a>.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="panel p-4 h-100">
                    <h2 class="h5 section-heading">How the amounts are worked out</h2>
                    <ul class="small mb-0">
                        <li><strong>Dogs and cats:</strong> daily energy from body weight and life stage, then converted to grams of the food you choose (dry food, wet food or home-cooked).</li>
                        <li><strong>Cattle, goats, sheep, horses:</strong> a percentage of body weight as dry matter, then converted to the weight of fresh grass, leaves or concentrate.</li>
                        <li><strong>Pigs, chickens, rabbits:</strong> amounts per day from published tables or per kg of body weight.</li>
                    </ul>
                </div>
            </div>
        </div>
    <?php else: ?>

        <?php if ($kcalError !== ''): ?><div class="alert alert-danger"><?= e($kcalError) ?></div><?php endif; ?>

        <?php foreach ($results as $entry): ?>
            <?php $plan = $entry['plan']; $target = $entry['target']; ?>
            <section class="mb-5">
                <h2 class="h4 section-heading"><?= e($animal['common_name']) ?> · <?= e($plan['life_stage']) ?> · <?= e(rtrim(rtrim(number_format((float) $weight, 2, '.', ''), '0'), '.')) ?> kg</h2>
                <p class="mb-2"><?= review_badge($plan['review_status']) ?></p>

                <?php if (!$entry['applies']): ?>
                    <div class="alert alert-warning">
                        This plan is meant for animals of
                        <strong><?= e(trim(($plan['min_weight_kg'] !== null ? rtrim(rtrim(number_format((float) $plan['min_weight_kg'], 2, '.', ''), '0'), '.') : '0') . ' to ' . ($plan['max_weight_kg'] !== null ? rtrim(rtrim(number_format((float) $plan['max_weight_kg'], 2, '.', ''), '0'), '.') : 'any weight'))) ?> kg</strong>.
                        Your animal's weight is outside that range, so no amounts are shown. Check the weight and life stage, or ask a veterinarian.
                    </div>
                <?php elseif (!$target['ok']): ?>
                    <div class="alert alert-warning"><?= e($target['error'] ?? 'No result.') ?></div>
                <?php else: ?>
                    <div class="panel p-4 mb-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <?php if ($target['kind'] === 'energy'): ?>
                                    <div class="text-muted small">Daily energy need</div>
                                    <div class="display-6 fw-bold text-success"><?= e(feeding_format_kcal_range($target['min'], $target['max'])) ?> kcal</div>
                                    <div class="small text-muted">= <?= e(rtrim(rtrim(number_format((float) ($plan['amount_typical'] ?? $plan['amount_min']), 2, '.', ''), '0'), '.')) ?> × RER, where RER (resting energy requirement) = 70 × <?= e(rtrim(rtrim(number_format($weight, 2, '.', ''), '0'), '.')) ?><sup>0.75</sup> = <?= e(number_format((float) $target['rer'], 0, '.', ',')) ?> kcal</div>
                                <?php elseif ($target['kind'] === 'dm'): ?>
                                    <div class="text-muted small">Daily dry matter (the feed weight with the water taken out)</div>
                                    <div class="display-6 fw-bold text-success"><?= e(feeding_format_kg_range($target['min'], $target['max'])) ?></div>
                                    <div class="small text-muted">typical <?= e(feeding_format_kg($target['typical'])) ?> · <?= e(rtrim(rtrim(number_format((float) $plan['amount_min'], 2, '.', ''), '0'), '.')) ?>–<?= e(rtrim(rtrim(number_format((float) $plan['amount_max'], 2, '.', ''), '0'), '.')) ?>% of body weight. Fresh feeds contain water, so the amounts below are the weights <em>as fed</em>.</div>
                                <?php else: ?>
                                    <div class="text-muted small">Total feed per day</div>
                                    <div class="display-6 fw-bold text-success"><?= e(feeding_format_range($target['min'], $target['max'])) ?></div>
                                    <?php if ($target['min'] !== $target['max']): ?><div class="small text-muted">typical <?= e(feeding_format_grams($target['typical'])) ?></div><?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="text-muted small">Meals per day</div>
                                <div class="h3 mb-0"><?= (int) $plan['meals_per_day'] ?></div>
                            </div>
                        </div>
                    </div>

                    <h3 class="h5">Choose a type of feed</h3>
                    <p class="small text-muted">The amount changes with the type of feed. Each option below would meet the same daily target.</p>

                    <div class="row g-3">
                        <?php foreach ($entry['diets'] as $diet): ?>
                            <?php
                            $hasComplete = false;
                            foreach ($diet['items'] as $row) {
                                if ($row['feed_category'] === 'complete_feed' && $target['kind'] === 'energy') {
                                    $hasComplete = true;
                                }
                            }
                            $shareHeading = $target['kind'] === 'energy' ? 'Share of energy' : ($target['kind'] === 'dm' ? 'Share of dry matter' : 'Share');
                            ?>
                            <div class="<?= count($entry['diets']) > 1 && $target['kind'] === 'energy' ? 'col-lg-6' : 'col-12' ?>">
                                <div class="card entity-card h-100">
                                    <div class="card-body">
                                        <h4 class="h6 fw-bold text-success"><?= e($diet['name']) ?></h4>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-2">
                                                <thead>
                                                    <tr><th>Feed</th><th><?= e($shareHeading) ?></th><th>Amount per day (as fed)</th><th>Per meal (<?= (int) $plan['meals_per_day'] ?>×)</th></tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($diet['items'] as $row): ?>
                                                        <tr>
                                                            <td>
                                                                <a href="feed-details.php?id=<?= (int) $row['feed_id'] ?>"><?= e($row['feed_name']) ?></a>
                                                                <?php if ($row['notes'] !== ''): ?><div class="small text-muted"><?= e($row['notes']) ?></div><?php endif; ?>
                                                                <?php if ($row['override_used']): ?><div class="small text-success">using your label value: <?= e(number_format((float) $row['kcal_per_kg_used'], 0, '.', ',')) ?> kcal/kg</div>
                                                                <?php elseif ($row['kcal_per_kg_used'] !== null): ?><div class="small text-muted">assumes <?= e(number_format((float) $row['kcal_per_kg_used'], 0, '.', ',')) ?> kcal per kg</div><?php endif; ?>
                                                            </td>
                                                            <td><?= e(share_label($row['share'])) ?></td>
                                                            <td class="fw-semibold">
                                                                <?php if ($row['mode'] === 'amount'): ?>
                                                                    <?= e(feeding_format_range((float) $row['min_g'], (float) $row['max_g'])) ?>
                                                                    <?php if (feeding_round_grams((float) $row['min_g']) !== feeding_round_grams((float) $row['max_g'])): ?>
                                                                        <div class="small text-muted fw-normal">typical <?= e(feeding_format_grams((float) $row['typical_g'])) ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if ($row['dm_kg_typical'] !== null): ?><div class="small text-muted fw-normal">dry matter <?= e(feeding_format_kg((float) $row['dm_kg_typical'])) ?></div><?php endif; ?>
                                                                <?php elseif ($row['mode'] === 'fixed'): ?>
                                                                    <?= e(feeding_format_range((float) $row['min_g'], (float) $row['max_g'])) ?>
                                                                    <div class="small text-muted fw-normal">fixed amount per animal</div>
                                                                <?php elseif ($row['mode'] === 'dm_only'): ?>
                                                                    <span class="fw-normal">dry matter <?= e(feeding_format_kg((float) $row['dm_kg_typical'])) ?></span>
                                                                    <div class="small text-muted fw-normal">weigh it as fed using your feed's dry-matter %</div>
                                                                <?php elseif ($row['mode'] === 'free'): ?>
                                                                    <span class="fw-normal">As much as the animal wants (free choice)</span>
                                                                <?php else: ?>
                                                                    <span class="fw-normal text-muted">needs an energy value</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($row['typical_g'] !== null): ?>
                                                                    <?= e(feeding_format_range((float) $row['per_meal_min_g'], (float) $row['per_meal_max_g'])) ?>
                                                                <?php else: ?>—<?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <?php if ($diet['cp_percent'] !== null): ?>
                                            <p class="small mb-2">
                                                Ration quality: about <strong><?= e(number_format((float) $diet['cp_percent'], 1)) ?>%</strong> crude protein
                                                <?php if ($diet['me_mj_per_kg_dm'] !== null): ?>and <strong><?= e(number_format((float) $diet['me_mj_per_kg_dm'], 1)) ?> MJ</strong> metabolizable energy per kg of dry matter<?php endif; ?>.
                                            </p>
                                        <?php endif; ?>
                                        <?php foreach ($diet['warnings'] as $warning): ?>
                                            <div class="alert alert-warning small py-2"><?= e($warning) ?></div>
                                        <?php endforeach; ?>

                                        <?php if ($hasComplete): ?>
                                            <form method="get" class="row g-2 align-items-end border-top pt-2 mt-2">
                                                <input type="hidden" name="animal" value="<?= $animalId ?>">
                                                <input type="hidden" name="stage" value="<?= e($stage) ?>">
                                                <input type="hidden" name="weight" value="<?= e($weightRaw) ?>">
                                                <input type="hidden" name="kcal_diet" value="<?= e($diet['name']) ?>">
                                                <div class="col">
                                                    <label class="form-label small mb-1" for="kcal-<?= e(md5($diet['name'])) ?>">Your product's energy (kcal per kg, from the package)</label>
                                                    <input type="text" inputmode="decimal" class="form-control form-control-sm" id="kcal-<?= e(md5($diet['name'])) ?>" name="kcal" placeholder="e.g. 3600" maxlength="8" value="<?= $energyOverride !== null && $energyOverride['diet'] === $diet['name'] ? e($kcalRaw) : '' ?>">
                                                </div>
                                                <div class="col-auto"><button class="btn btn-sm btn-outline-success" type="submit">Recalculate</button></div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($plan['notes'])): ?>
                        <div class="panel p-4 mt-3">
                            <h3 class="h6 section-heading">Notes and how this was worked out</h3>
                            <p class="small mb-0"><?= nl2br(e($plan['notes'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="panel p-4 mt-3">
                        <h3 class="h6 section-heading">Sources</h3>
                        <?= render_sources($entry['sources']) ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <?php if ($stage === '' && $weight === null): ?>
            <div class="alert alert-info">Choose the life stage and enter the weight to see the amounts.</div>
        <?php endif; ?>

        <?php if ($unsafeFoods): ?>
            <section class="mb-5">
                <h2 class="h4 section-heading">Foods to avoid or limit: <?= e($animal['common_name']) ?></h2>
                <div class="table-responsive panel p-2">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Food</th><th>Risk</th><th>Why</th></tr></thead>
                        <tbody>
                            <?php foreach ($unsafeFoods as $food): ?>
                                <?php $badge = match ($food['severity']) { 'toxic' => 'text-bg-danger', 'avoid' => 'text-bg-warning', default => 'text-bg-secondary' }; ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($food['food_name']) ?></td>
                                    <td><span class="badge <?= $badge ?>"><?= e(UNSAFE_SEVERITIES[$food['severity']] ?? $food['severity']) ?></span></td>
                                    <td class="small">
                                        <?= e($food['reason']) ?>
                                        <?php $url = safe_url($food['source_url']); ?>
                                        <?php if ($url !== null): ?><div><a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($food['source_title']) ?></a></div><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-2">If an animal has eaten something toxic, contact a veterinarian immediately.</p>
            </section>
        <?php endif; ?>

        <p><a href="animal-details.php?id=<?= $animalId ?>" class="btn btn-outline-success">About the <?= e(strtolower($animal['common_name'])) ?></a></p>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
