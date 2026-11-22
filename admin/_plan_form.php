<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/feeding.php';

/*
 * Shared create/edit form for feeding plans.
 * The including page sets $planId (0 = create a new plan).
 */

if (!feeding_available($pdo)) {
    admin_header('Feeding plan', 'plans');
    echo '<div class="alert alert-info">The feeding guide tables are not installed. Run <code>php tools/upgrade.php</code> on the server.</div>';
    admin_footer();
    exit;
}

$planId = (int) ($planId ?? 0);
$editing = $planId > 0;

const PLAN_MAX_ITEM_ROWS = 40;

$animalOptions = [];
foreach ($pdo->query('SELECT id, common_name FROM animals ORDER BY common_name')->fetchAll() as $row) {
    $animalOptions[(int) $row['id']] = $row['common_name'];
}
$feedOptions = [];
$feedMeta = [];
foreach ($pdo->query('SELECT id, feed_name, dry_matter_percent, energy_kcal_per_kg FROM feeds ORDER BY feed_name')->fetchAll() as $row) {
    $feedOptions[(int) $row['id']] = $row['feed_name'];
    $feedMeta[(int) $row['id']] = ['dm' => $row['dry_matter_percent'], 'kcal' => $row['energy_kcal_per_kg']];
}
$sourceOptions = [];
foreach ($pdo->query('SELECT id, title FROM sources ORDER BY title')->fetchAll() as $row) {
    $sourceOptions[(int) $row['id']] = $row['title'];
}
$knownStages = $pdo->query('SELECT DISTINCT life_stage FROM feeding_plans ORDER BY life_stage')->fetchAll(PDO::FETCH_COLUMN);

$num = static fn ($v): string => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');

$input = [
    'animal_id' => '', 'life_stage' => '', 'method' => 'energy', 'amount_min' => '', 'amount_max' => '', 'amount_typical' => '',
    'min_weight_kg' => '', 'max_weight_kg' => '', 'meals_per_day' => '2', 'target_cp_min' => '', 'notes' => '',
    'sort_order' => '0', 'review_status' => 'draft',
];
$itemRows = [];
$pointsText = '';
$selectedSources = [];

if ($editing) {
    $existing = run_query($pdo, 'SELECT * FROM feeding_plans WHERE id = :id LIMIT 1', ['id' => $planId])->fetch();
    if (!$existing) {
        flash_set('warning', 'That feeding plan no longer exists.');
        header('Location: feeding-plans.php');
        exit;
    }
    foreach ($input as $key => $unused) {
        $value = $existing[$key] ?? '';
        $input[$key] = in_array($key, ['amount_min', 'amount_max', 'amount_typical', 'min_weight_kg', 'max_weight_kg', 'target_cp_min'], true) ? $num($value) : (string) $value;
    }
    foreach (run_query(
        $pdo,
        'SELECT diet_name, feed_id, share_percent, fixed_min_g, fixed_max_g, notes FROM feeding_plan_items WHERE plan_id = :id ORDER BY sort_order, diet_name, feed_id',
        ['id' => $planId]
    )->fetchAll() as $row) {
        $itemRows[] = [
            'diet_name' => (string) $row['diet_name'], 'feed_id' => (string) $row['feed_id'], 'share_percent' => $num($row['share_percent']),
            'fixed_min_g' => $num($row['fixed_min_g']), 'fixed_max_g' => $num($row['fixed_max_g']), 'notes' => (string) ($row['notes'] ?? ''),
        ];
    }
    $lines = [];
    foreach (feeding_points_for_plan($pdo, $planId) as $point) {
        $lines[] = $num($point['weight_kg']) . ', ' . $num($point['amount_g']);
    }
    $pointsText = implode("\n", $lines);
    $selectedSources = load_links($pdo, 'feeding_plan_sources', 'plan_id', $planId);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $input['animal_id'] = (string) post_int('animal_id');
    $input['life_stage'] = post_string('life_stage', 120);
    $input['method'] = post_string('method', 20);
    $input['notes'] = post_string('notes', 20000);
    $input['review_status'] = post_string('review_status', 20);
    $input['meals_per_day'] = post_string('meals_per_day', 3);
    $input['sort_order'] = post_string('sort_order', 6);
    foreach (['amount_min', 'amount_max', 'amount_typical', 'min_weight_kg', 'max_weight_kg', 'target_cp_min'] as $key) {
        $input[$key] = post_string($key, 12);
    }
    $pointsText = post_string('points_text', 4000);

    if (!isset($animalOptions[(int) $input['animal_id']])) {
        $errors[] = 'Please choose an animal.';
    }
    if ($input['life_stage'] === '') {
        $errors[] = 'Life stage or purpose is required.';
    }
    if (!isset(FEEDING_METHODS[$input['method']])) {
        $errors[] = 'Invalid calculation method.';
    }
    if (!isset(REVIEW_LABELS[$input['review_status']])) {
        $errors[] = 'Invalid review status.';
    }

    // Amounts
    $method = $input['method'];
    $maxAmount = match ($method) { 'energy' => 10.0, 'pct_dm' => 10.0, 'pct_asfed' => 20.0, default => 100000.0 };
    $amountMin = $amountMax = $amountTypical = null;
    if ($method !== 'table') {
        $amountMin = parse_decimal($input['amount_min'], 'Minimum amount', 0, $maxAmount, $errors);
        $amountMax = parse_decimal($input['amount_max'], 'Maximum amount', 0, $maxAmount, $errors);
        $amountTypical = parse_decimal($input['amount_typical'], 'Typical amount', 0, $maxAmount, $errors);
        if ($amountMin === null || $amountMax === null) {
            $errors[] = 'Minimum and maximum amount are required (use the same number twice for a single value).';
        } elseif ($amountMin > $amountMax) {
            $errors[] = 'Minimum amount cannot be larger than the maximum.';
        } elseif ($amountTypical !== null && ($amountTypical < $amountMin || $amountTypical > $amountMax)) {
            $errors[] = 'Typical amount must lie between the minimum and the maximum.';
        }
    }

    $minWeight = parse_decimal($input['min_weight_kg'], 'Minimum body weight', 0, 1500, $errors);
    $maxWeight = parse_decimal($input['max_weight_kg'], 'Maximum body weight', 0, 1500, $errors);
    if ($minWeight !== null && $maxWeight !== null && $minWeight > $maxWeight) {
        $errors[] = 'Minimum body weight cannot be larger than the maximum.';
    }
    $targetCp = parse_decimal($input['target_cp_min'], 'Target crude protein', 0, 100, $errors);

    $meals = ctype_digit($input['meals_per_day']) ? (int) $input['meals_per_day'] : 0;
    if ($meals < 1 || $meals > 12) {
        $errors[] = 'Meals per day must be between 1 and 12.';
        $meals = 2;
    }
    $sortOrder = preg_match('/^-?\d{1,5}$/', $input['sort_order']) ? (int) $input['sort_order'] : 0;

    // Weight/amount table (method "table")
    $points = [];
    if ($method === 'table') {
        foreach (preg_split('/\R/', $pointsText) ?: [] as $lineNo => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (!preg_match('/^(\d+(?:[.,]\d+)?)\s*[,;\t ]\s*(\d+(?:[.,]\d+)?)$/', $line, $m)) {
                $errors[] = 'Table line ' . ($lineNo + 1) . ' should look like "13, 500" (body weight in kg, then grams per day).';
                continue;
            }
            $w = (float) str_replace(',', '.', $m[1]);
            $g = (float) str_replace(',', '.', $m[2]);
            if ($w <= 0 || $w > 1500 || $g <= 0 || $g > 100000) {
                $errors[] = 'Table line ' . ($lineNo + 1) . ' has a weight or amount out of range.';
                continue;
            }
            $key = number_format($w, 3, '.', '');
            if (isset($points[$key])) {
                $errors[] = 'Table line ' . ($lineNo + 1) . ': the weight ' . $num($w) . ' kg appears twice.';
                continue;
            }
            $points[$key] = ['weight' => $w, 'grams' => $g];
        }
        if (count($points) < 2) {
            $errors[] = 'A table plan needs at least two weight/amount lines.';
        } elseif (count($points) > 100) {
            $errors[] = 'A table plan can have at most 100 lines.';
        } else {
            ksort($points, SORT_NUMERIC);
            $weights = array_column($points, 'weight');
            $minWeight ??= min($weights);
            $maxWeight ??= max($weights);
        }
    }

    // Diet items
    $itemsIn = $_POST['items'] ?? [];
    $itemRows = [];
    $items = [];
    if (is_array($itemsIn)) {
        $n = 0;
        foreach ($itemsIn as $rowIn) {
            if (!is_array($rowIn) || ++$n > PLAN_MAX_ITEM_ROWS) {
                continue;
            }
            $row = [];
            foreach (['diet_name' => 100, 'feed_id' => 10, 'share_percent' => 10, 'fixed_min_g' => 10, 'fixed_max_g' => 10, 'notes' => 500] as $field => $max) {
                $row[$field] = isset($rowIn[$field]) && is_string($rowIn[$field]) ? mb_substr(trim($rowIn[$field]), 0, $max) : '';
            }
            if ($row['feed_id'] === '' && $row['diet_name'] === '' && $row['share_percent'] === '' && $row['fixed_min_g'] === '' && $row['fixed_max_g'] === '' && $row['notes'] === '') {
                continue; // an empty row
            }
            $itemRows[] = $row;
            $label = 'Diet row ' . $n;
            $feedId = ctype_digit($row['feed_id']) ? (int) $row['feed_id'] : 0;
            if (!isset($feedOptions[$feedId])) {
                $errors[] = $label . ': please choose a feed.';
                continue;
            }
            $share = parse_decimal($row['share_percent'], $label . ' share', 0, 100, $errors);
            $fixedMin = parse_decimal($row['fixed_min_g'], $label . ' fixed minimum', 0, 100000, $errors);
            $fixedMax = parse_decimal($row['fixed_max_g'], $label . ' fixed maximum', 0, 100000, $errors);
            if (($fixedMin === null) !== ($fixedMax === null)) {
                $errors[] = $label . ': give both a fixed minimum and a fixed maximum, or neither.';
            } elseif ($fixedMin !== null && $fixedMax !== null && $fixedMin > $fixedMax) {
                $errors[] = $label . ': the fixed minimum cannot be larger than the maximum.';
            }
            if ($share !== null && $fixedMin !== null) {
                $errors[] = $label . ': use either a share or a fixed amount, not both.';
            }
            if ($method === 'energy' && $share !== null && ($feedMeta[$feedId]['kcal'] ?? null) === null) {
                $errors[] = $label . ': "' . $feedOptions[$feedId] . '" has no energy value (kcal per kg), which an energy plan needs. Add it on the feed type first.';
            }
            $items[] = [
                'diet' => $row['diet_name'] !== '' ? $row['diet_name'] : 'Standard ration',
                'feed_id' => $feedId, 'share' => $share, 'fixed_min' => $fixedMin, 'fixed_max' => $fixedMax,
                'notes' => $row['notes'] !== '' ? $row['notes'] : null,
            ];
        }
    }
    if (!$items && !$errors) {
        $errors[] = 'Add at least one feed to the plan.';
    }
    // Duplicates and share totals per diet option
    $seen = [];
    $shareTotals = [];
    foreach ($items as $item) {
        $key = mb_strtolower($item['diet']) . '#' . $item['feed_id'];
        if (isset($seen[$key])) {
            $errors[] = 'The feed "' . $feedOptions[$item['feed_id']] . '" is listed twice in the diet "' . $item['diet'] . '".';
        }
        $seen[$key] = true;
        if ($item['share'] !== null) {
            $shareTotals[$item['diet']] = ($shareTotals[$item['diet']] ?? 0.0) + $item['share'];
        }
    }
    foreach ($shareTotals as $diet => $total) {
        if (abs($total - 100.0) > 0.5) {
            $errors[] = 'The shares in the diet "' . $diet . '" add up to ' . $num($total) . '%, but they must add up to 100%.';
        }
    }

    $selectedSources = array_intersect_key(read_linked('sources'), $sourceOptions);

    if (!$errors) {
        $values = [
            'animal_id' => (int) $input['animal_id'],
            'life_stage' => $input['life_stage'],
            'method' => $method,
            'amount_min' => $amountMin,
            'amount_max' => $amountMax,
            'amount_typical' => $amountTypical,
            'min_weight_kg' => $minWeight,
            'max_weight_kg' => $maxWeight,
            'meals_per_day' => $meals,
            'target_cp_min' => $targetCp,
            'notes' => $input['notes'] !== '' ? $input['notes'] : null,
            'sort_order' => $sortOrder,
            'review_status' => $input['review_status'],
        ];
        try {
            $pdo->beginTransaction();
            if ($editing) {
                $values['id'] = $planId;
                run_query(
                    $pdo,
                    'UPDATE feeding_plans SET animal_id = :animal_id, life_stage = :life_stage, method = :method,
                        amount_min = :amount_min, amount_max = :amount_max, amount_typical = :amount_typical,
                        min_weight_kg = :min_weight_kg, max_weight_kg = :max_weight_kg, meals_per_day = :meals_per_day,
                        target_cp_min = :target_cp_min, notes = :notes, sort_order = :sort_order, review_status = :review_status
                     WHERE id = :id',
                    $values
                );
                $savedId = $planId;
            } else {
                run_query(
                    $pdo,
                    'INSERT INTO feeding_plans (animal_id, life_stage, method, amount_min, amount_max, amount_typical, min_weight_kg,
                        max_weight_kg, meals_per_day, target_cp_min, notes, sort_order, review_status)
                     VALUES (:animal_id, :life_stage, :method, :amount_min, :amount_max, :amount_typical, :min_weight_kg,
                        :max_weight_kg, :meals_per_day, :target_cp_min, :notes, :sort_order, :review_status)',
                    $values
                );
                $savedId = (int) $pdo->lastInsertId();
            }

            run_query($pdo, 'DELETE FROM feeding_plan_items WHERE plan_id = :id', ['id' => $savedId]);
            $insertItem = $pdo->prepare(
                'INSERT INTO feeding_plan_items (plan_id, diet_name, feed_id, share_percent, fixed_min_g, fixed_max_g, notes, sort_order)
                 VALUES (:plan_id, :diet_name, :feed_id, :share, :fixed_min, :fixed_max, :notes, :sort_order)'
            );
            foreach ($items as $index => $item) {
                $insertItem->execute([
                    'plan_id' => $savedId, 'diet_name' => $item['diet'], 'feed_id' => $item['feed_id'], 'share' => $item['share'],
                    'fixed_min' => $item['fixed_min'], 'fixed_max' => $item['fixed_max'], 'notes' => $item['notes'], 'sort_order' => $index + 1,
                ]);
            }

            run_query($pdo, 'DELETE FROM feeding_plan_points WHERE plan_id = :id', ['id' => $savedId]);
            if ($method === 'table') {
                $insertPoint = $pdo->prepare('INSERT INTO feeding_plan_points (plan_id, weight_kg, amount_g) VALUES (:plan_id, :weight, :grams)');
                foreach ($points as $point) {
                    $insertPoint->execute(['plan_id' => $savedId, 'weight' => $point['weight'], 'grams' => $point['grams']]);
                }
            }

            sync_links($pdo, 'feeding_plan_sources', 'plan_id', $savedId, $selectedSources);
            $pdo->commit();

            flash_set('success', 'Feeding plan saved.');
            header('Location: feeding-plans.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'The plan could not be saved. Check the values and try again.';
        }
    }
}

// Rows shown in the form: the existing ones plus a few empty ones.
$rowCount = min(PLAN_MAX_ITEM_ROWS, max(8, count($itemRows) + 3));
while (count($itemRows) < $rowCount) {
    $itemRows[] = ['diet_name' => '', 'feed_id' => '', 'share_percent' => '', 'fixed_min_g' => '', 'fixed_max_g' => '', 'notes' => ''];
}
$dietNames = array_values(array_unique(array_filter(array_column($itemRows, 'diet_name'))));

admin_header($editing ? 'Edit feeding plan' : 'Add feeding plan', 'plans');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0"><?= $editing ? 'Edit feeding plan' : 'Add feeding plan' ?></h1>
    <a href="feeding-plans.php" class="btn btn-outline-secondary">Back to feeding plans</a>
</div>

<?= render_errors($errors) ?>

<div class="alert alert-info small">
    <strong>How a plan works.</strong> A plan gives the <em>total daily amount</em> for one animal at one life stage, scaled by body weight. One or more <em>diet options</em> (for example dry food, wet food, home-cooked) then say which feeds make up that total.
    <ul class="mb-0 mt-1">
        <li><strong>Energy:</strong> amounts are multiples of RER (70 × kg<sup>0.75</sup> kcal/day). Feed energy comes from “kcal per kg” on the feed type.</li>
        <li><strong>Dry matter / as fed:</strong> amounts are % of body weight per day. Dry-matter amounts are converted to fresh weight with the feed's dry-matter %.</li>
        <li><strong>Fixed:</strong> grams per animal per day, whatever the weight. <strong>Table:</strong> grams per day by body weight, interpolated.</li>
    </ul>
    Cite a source for every number and do not copy text from copyrighted manuals. Avoid giving advice beyond what the sources support.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post">
            <?= csrf_field() ?>

            <h2 class="form-section-title mt-0">Who and when</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="animal_id" class="form-label">Animal *</label>
                    <select id="animal_id" name="animal_id" class="form-select" required>
                        <option value="">Select animal</option>
                        <?php foreach ($animalOptions as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $input['animal_id'] === (string) $id ? 'selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="life_stage" class="form-label">Life stage or purpose *</label>
                    <input type="text" id="life_stage" name="life_stage" class="form-control" maxlength="120" list="stageList" value="<?= e($input['life_stage']) ?>" placeholder="e.g. Adult - neutered or spayed" required>
                    <datalist id="stageList">
                        <?php foreach ($knownStages as $stage): ?><option value="<?= e((string) $stage) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label for="sort_order" class="form-label">Sort order</label>
                    <input type="text" inputmode="numeric" id="sort_order" name="sort_order" class="form-control" maxlength="6" value="<?= e($input['sort_order']) ?>">
                </div>
                <div class="col-md-3">
                    <label for="min_weight_kg" class="form-label">Applies from (kg)</label>
                    <input type="text" inputmode="decimal" id="min_weight_kg" name="min_weight_kg" class="form-control" value="<?= e($input['min_weight_kg']) ?>" placeholder="empty = no minimum">
                </div>
                <div class="col-md-3">
                    <label for="max_weight_kg" class="form-label">Applies up to (kg)</label>
                    <input type="text" inputmode="decimal" id="max_weight_kg" name="max_weight_kg" class="form-control" value="<?= e($input['max_weight_kg']) ?>" placeholder="empty = no maximum">
                </div>
                <div class="col-md-3">
                    <label for="meals_per_day" class="form-label">Meals per day</label>
                    <input type="text" inputmode="numeric" id="meals_per_day" name="meals_per_day" class="form-control" maxlength="2" value="<?= e($input['meals_per_day']) ?>">
                </div>
            </div>

            <h2 class="form-section-title">Daily amount</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="method" class="form-label">Method *</label>
                    <select id="method" name="method" class="form-select">
                        <?php foreach (FEEDING_METHODS as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $input['method'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="amount_min" class="form-label">Minimum</label>
                    <input type="text" inputmode="decimal" id="amount_min" name="amount_min" class="form-control" value="<?= e($input['amount_min']) ?>">
                </div>
                <div class="col-md-2">
                    <label for="amount_max" class="form-label">Maximum</label>
                    <input type="text" inputmode="decimal" id="amount_max" name="amount_max" class="form-control" value="<?= e($input['amount_max']) ?>">
                </div>
                <div class="col-md-2">
                    <label for="amount_typical" class="form-label">Typical (optional)</label>
                    <input type="text" inputmode="decimal" id="amount_typical" name="amount_typical" class="form-control" value="<?= e($input['amount_typical']) ?>">
                </div>
                <div class="col-md-2">
                    <label for="target_cp_min" class="form-label">Target protein % (DM)</label>
                    <input type="text" inputmode="decimal" id="target_cp_min" name="target_cp_min" class="form-control" value="<?= e($input['target_cp_min']) ?>">
                    <div class="form-text">Warns if the ration is below it.</div>
                </div>
                <div class="col-12">
                    <label for="points_text" class="form-label">Weight table (only for method “Table”)</label>
                    <textarea id="points_text" name="points_text" rows="4" class="form-control font-monospace" placeholder="One line per point: body weight in kg, grams per day&#10;13, 500&#10;110, 2500"><?= e($pointsText) ?></textarea>
                    <div class="form-text">Amounts between two lines are interpolated in a straight line; weights outside the table get no result.</div>
                </div>
            </div>

            <h2 class="form-section-title">Diet options and feeds</h2>
            <p class="small text-muted">
                Rows with the same diet option name belong together; the <strong>shares</strong> of one diet add up to 100%. Leave the share empty for “free choice” (for example hay), or give a <strong>fixed amount</strong> (grams per animal per day, not scaled by weight) for items like a mineral mix. Empty rows are ignored.
            </p>
            <datalist id="dietList">
                <?php foreach ($dietNames as $diet): ?><option value="<?= e($diet) ?>"><?php endforeach; ?>
            </datalist>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th style="min-width:12rem">Diet option</th><th style="min-width:14rem">Feed</th><th style="width:7rem">Share %</th><th style="width:7rem">Fixed min g</th><th style="width:7rem">Fixed max g</th><th style="min-width:14rem">Note</th></tr></thead>
                    <tbody>
                        <?php foreach ($itemRows as $i => $row): ?>
                            <tr>
                                <td><input type="text" class="form-control form-control-sm" name="items[<?= $i ?>][diet_name]" list="dietList" maxlength="100" value="<?= e($row['diet_name']) ?>" aria-label="Diet option name, row <?= $i + 1 ?>"></td>
                                <td>
                                    <select class="form-select form-select-sm" name="items[<?= $i ?>][feed_id]" aria-label="Feed, row <?= $i + 1 ?>">
                                        <option value="">—</option>
                                        <?php foreach ($feedOptions as $fid => $fname): ?>
                                            <option value="<?= $fid ?>" <?= $row['feed_id'] === (string) $fid ? 'selected' : '' ?>><?= e($fname) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" inputmode="decimal" class="form-control form-control-sm" name="items[<?= $i ?>][share_percent]" maxlength="10" value="<?= e($row['share_percent']) ?>" aria-label="Share percent, row <?= $i + 1 ?>"></td>
                                <td><input type="text" inputmode="decimal" class="form-control form-control-sm" name="items[<?= $i ?>][fixed_min_g]" maxlength="10" value="<?= e($row['fixed_min_g']) ?>" aria-label="Fixed minimum grams, row <?= $i + 1 ?>"></td>
                                <td><input type="text" inputmode="decimal" class="form-control form-control-sm" name="items[<?= $i ?>][fixed_max_g]" maxlength="10" value="<?= e($row['fixed_max_g']) ?>" aria-label="Fixed maximum grams, row <?= $i + 1 ?>"></td>
                                <td><input type="text" class="form-control form-control-sm" name="items[<?= $i ?>][notes]" maxlength="500" value="<?= e($row['notes']) ?>" aria-label="Note, row <?= $i + 1 ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted">Need more rows? Save, then edit the plan again: three empty rows are added each time.</p>

            <h2 class="form-section-title">Notes</h2>
            <label for="notes" class="form-label">How this was worked out, and cautions</label>
            <textarea id="notes" name="notes" rows="4" class="form-control"><?= e($input['notes']) ?></textarea>
            <div class="form-text">Say which source each number comes from, and when the plan does not apply (pregnancy, illness, hard work).</div>

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
                    <div class="form-text">Draft plans are hidden from the public. Choose “Expert-reviewed” only after a veterinarian or animal nutritionist has checked the numbers against the sources.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-success mt-4"><?= $editing ? 'Save changes' : 'Create feeding plan' ?></button>
        </form>
    </div>
</div>
<?php admin_footer();
