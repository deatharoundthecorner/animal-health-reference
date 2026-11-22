<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/feeding.php';

if (!feeding_available($pdo)) {
    admin_header('Feed types', 'feeds');
    echo '<div class="alert alert-info">The feeding guide tables are not installed. Run <code>php tools/upgrade.php</code> on the server.</div>';
    admin_footer();
    exit;
}

$q = get_string('q');
$category = get_string('category');
if (!isset(FEED_CATEGORIES[$category])) {
    $category = '';
}
$status = get_string('status');
if (!isset(REVIEW_LABELS[$status])) {
    $status = '';
}

$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $like = like_term($q);
    $where[] = '(f.feed_name LIKE :q1 OR f.scientific_name LIKE :q2)';
    $params += ['q1' => $like, 'q2' => $like];
}
if ($category !== '') {
    $where[] = 'f.feed_category = :category';
    $params['category'] = $category;
}
if ($status !== '') {
    $where[] = 'f.review_status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$total = (int) run_query($pdo, "SELECT COUNT(*) FROM feeds f WHERE {$whereSql}", $params)->fetchColumn();
$perPage = 25;
$pager = paginate($total, $perPage, get_int('page'));

$feeds = run_query(
    $pdo,
    "SELECT f.id, f.feed_name, f.feed_category, f.dry_matter_percent, f.crude_protein_percent, f.energy_kcal_per_kg, f.review_status,
            (SELECT COUNT(DISTINCT i.plan_id) FROM feeding_plan_items i WHERE i.feed_id = f.id) AS plan_count,
            (SELECT COUNT(*) FROM feed_sources s WHERE s.feed_id = f.id) AS source_count
     FROM feeds f
     WHERE {$whereSql}
     ORDER BY FIELD(f.feed_category, 'green_forage', 'dry_forage', 'concentrate', 'complete_feed', 'kitchen_food', 'supplement'), f.feed_name
     LIMIT :limit OFFSET :offset",
    $params + ['limit' => $perPage, 'offset' => $pager['offset']]
)->fetchAll();

admin_header('Feed types', 'feeds');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Feed types</h1>
    <a href="add-feed.php" class="btn btn-success">+ Add feed type</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><input type="search" name="q" class="form-control" placeholder="Search by name" value="<?= e($q) ?>" maxlength="100"></div>
    <div class="col-md-3">
        <select name="category" class="form-select" aria-label="Category">
            <option value="">Any category</option>
            <?php foreach (FEED_CATEGORIES as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $category === $value ? 'selected' : '' ?>><?= e($label) ?></option>
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
    <div class="col-auto"><button class="btn btn-outline-success" type="submit">Filter</button> <a href="feeds.php" class="btn btn-outline-secondary">Clear</a></div>
</form>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Feed</th><th>Category</th><th>DM %</th><th>CP % DM</th><th>kcal/kg</th><th>Plans</th><th>Sources</th><th>Review</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($feeds as $feed): ?>
                    <tr>
                        <td><strong><?= e($feed['feed_name']) ?></strong></td>
                        <td><?= e(FEED_CATEGORIES[$feed['feed_category']] ?? $feed['feed_category']) ?></td>
                        <td><?= $feed['dry_matter_percent'] !== null ? e(rtrim(rtrim(number_format((float) $feed['dry_matter_percent'], 2, '.', ''), '0'), '.')) : '—' ?></td>
                        <td><?= $feed['crude_protein_percent'] !== null ? e(rtrim(rtrim(number_format((float) $feed['crude_protein_percent'], 2, '.', ''), '0'), '.')) : '—' ?></td>
                        <td><?= $feed['energy_kcal_per_kg'] !== null ? (int) $feed['energy_kcal_per_kg'] : '—' ?></td>
                        <td><?= (int) $feed['plan_count'] ?></td>
                        <td><?= (int) $feed['source_count'] === 0 ? '<span class="badge text-bg-warning">none</span>' : (int) $feed['source_count'] ?></td>
                        <td><?= review_badge($feed['review_status']) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($feed['review_status'] !== 'draft'): ?>
                                <a href="../feed-details.php?id=<?= (int) $feed['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">View</a>
                            <?php endif; ?>
                            <a href="edit-feed.php?id=<?= (int) $feed['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="delete.php?type=feed&amp;id=<?= (int) $feed['id'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$feeds): ?>
                    <tr><td colspan="9" class="text-center py-4">No feed types found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= render_pagination($pager['page'], $pager['totalPages'], ['q' => $q, 'category' => $category, 'status' => $status], 'Feed pagination') ?>
<?php admin_footer();
