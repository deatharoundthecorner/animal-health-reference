<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/feeding.php';

if (!feeding_available($pdo)) {
    admin_header('Feeding plans', 'plans');
    echo '<div class="alert alert-info">The feeding guide tables are not installed. Run <code>php tools/upgrade.php</code> on the server.</div>';
    admin_footer();
    exit;
}

$animalFilter = get_int('animal');
$status = get_string('status');
if (!isset(REVIEW_LABELS[$status])) {
    $status = '';
}

$animalOptions = $pdo->query('SELECT id, common_name FROM animals ORDER BY common_name')->fetchAll();

$where = ['1 = 1'];
$params = [];
if ($animalFilter > 0) {
    $where[] = 'p.animal_id = :animal_id';
    $params['animal_id'] = $animalFilter;
}
if ($status !== '') {
    $where[] = 'p.review_status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$total = (int) run_query($pdo, "SELECT COUNT(*) FROM feeding_plans p WHERE {$whereSql}", $params)->fetchColumn();
$perPage = 25;
$pager = paginate($total, $perPage, get_int('page'));

$plans = run_query(
    $pdo,
    "SELECT p.*, a.common_name,
            (SELECT COUNT(DISTINCT i.diet_name) FROM feeding_plan_items i WHERE i.plan_id = p.id) AS diet_count,
            (SELECT COUNT(*) FROM feeding_plan_points x WHERE x.plan_id = p.id) AS point_count,
            (SELECT COUNT(*) FROM feeding_plan_sources s WHERE s.plan_id = p.id) AS source_count
     FROM feeding_plans p
     INNER JOIN animals a ON a.id = p.animal_id
     WHERE {$whereSql}
     ORDER BY a.common_name, p.sort_order, p.id
     LIMIT :limit OFFSET :offset",
    $params + ['limit' => $perPage, 'offset' => $pager['offset']]
)->fetchAll();

$num = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');

/** A short description of how a plan's amount is defined. */
function plan_amount_summary(array $plan, callable $num, int $pointCount): string
{
    $range = $plan['amount_min'] !== null && $plan['amount_max'] !== null
        ? ((float) $plan['amount_min'] === (float) $plan['amount_max'] ? $num($plan['amount_min']) : $num($plan['amount_min']) . '–' . $num($plan['amount_max']))
        : '?';
    return match ($plan['method']) {
        'energy' => $range . ' × RER',
        'pct_dm' => $range . '% of body weight as dry matter',
        'pct_asfed' => $range . '% of body weight as fed',
        'fixed' => $range . ' g per animal per day',
        'table' => $pointCount . ' weight/amount points',
        default => '?',
    };
}

admin_header('Feeding plans', 'plans');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Feeding plans</h1>
    <a href="add-feeding-plan.php" class="btn btn-success">+ Add feeding plan</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="animal" class="form-select" aria-label="Animal">
            <option value="0">Any animal</option>
            <?php foreach ($animalOptions as $option): ?>
                <option value="<?= (int) $option['id'] ?>" <?= $animalFilter === (int) $option['id'] ? 'selected' : '' ?>><?= e($option['common_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select" aria-label="Review status">
            <option value="">Any review status</option>
            <?php foreach (REVIEW_LABELS as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-success" type="submit">Filter</button> <a href="feeding-plans.php" class="btn btn-outline-secondary">Clear</a></div>
</form>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Animal</th><th>Life stage</th><th>Amount</th><th>Weight band (kg)</th><th>Diets</th><th>Sources</th><th>Review</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><strong><?= e($plan['common_name']) ?></strong></td>
                        <td><?= e($plan['life_stage']) ?></td>
                        <td class="small"><?= e(plan_amount_summary($plan, $num, (int) $plan['point_count'])) ?></td>
                        <td class="small"><?= $plan['min_weight_kg'] !== null || $plan['max_weight_kg'] !== null ? e(($plan['min_weight_kg'] !== null ? $num($plan['min_weight_kg']) : '0') . '–' . ($plan['max_weight_kg'] !== null ? $num($plan['max_weight_kg']) : 'any')) : 'any' ?></td>
                        <td><?= (int) $plan['diet_count'] === 0 ? '<span class="badge text-bg-warning">none</span>' : (int) $plan['diet_count'] ?></td>
                        <td><?= (int) $plan['source_count'] === 0 ? '<span class="badge text-bg-warning">none</span>' : (int) $plan['source_count'] ?></td>
                        <td><?= review_badge($plan['review_status']) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($plan['review_status'] !== 'draft'): ?>
                                <a href="../<?= e(page_url('feeding.php', ['animal' => (int) $plan['animal_id'], 'stage' => $plan['life_stage']])) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Try it</a>
                            <?php endif; ?>
                            <a href="edit-feeding-plan.php?id=<?= (int) $plan['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="delete.php?type=plan&amp;id=<?= (int) $plan['id'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$plans): ?>
                    <tr><td colspan="8" class="text-center py-4">No feeding plans found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= render_pagination($pager['page'], $pager['totalPages'], ['animal' => $animalFilter, 'status' => $status], 'Plan pagination') ?>
<?php admin_footer();
