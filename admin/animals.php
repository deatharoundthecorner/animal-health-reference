<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$q = get_string('q');
$status = get_string('status');
if (!isset(REVIEW_LABELS[$status])) {
    $status = '';
}

$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $like = like_term($q);
    $where[] = '(a.common_name LIKE :q1 OR a.scientific_name LIKE :q2)';
    $params += ['q1' => $like, 'q2' => $like];
}
if ($status !== '') {
    $where[] = 'a.review_status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$total = (int) run_query($pdo, "SELECT COUNT(*) FROM animals a WHERE {$whereSql}", $params)->fetchColumn();
$perPage = 25;
$pager = paginate($total, $perPage, get_int('page'));

$animals = run_query(
    $pdo,
    "SELECT a.id, a.common_name, a.scientific_name, a.animal_type, a.conservation_status, a.image, a.review_status, c.category_name
     FROM animals a
     INNER JOIN categories c ON c.id = a.category_id
     WHERE {$whereSql}
     ORDER BY a.common_name ASC
     LIMIT :limit OFFSET :offset",
    $params + ['limit' => $perPage, 'offset' => $pager['offset']]
)->fetchAll();

admin_header('Animals', 'animals');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Animals</h1>
    <a href="add-animal.php" class="btn btn-success">+ Add animal</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-4"><input type="search" name="q" class="form-control" placeholder="Search by name" value="<?= e($q) ?>" maxlength="100"></div>
    <div class="col-md-3">
        <select name="status" class="form-select" aria-label="Review status">
            <option value="">Any review status</option>
            <?php foreach (REVIEW_LABELS as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-success" type="submit">Filter</button> <a href="animals.php" class="btn btn-outline-secondary">Clear</a></div>
</form>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th></th><th>Name</th><th>Kind</th><th>Group</th><th>Conservation</th><th>Review</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($animals as $animal): ?>
                    <tr>
                        <td><img class="thumb" src="<?= e(animal_image($animal['image'], '../')) ?>" alt=""></td>
                        <td>
                            <strong><?= e($animal['common_name']) ?></strong>
                            <div class="small text-muted fst-italic"><?= e($animal['scientific_name']) ?></div>
                        </td>
                        <td><?= e(ANIMAL_TYPES[$animal['animal_type']] ?? $animal['animal_type']) ?></td>
                        <td><?= e($animal['category_name']) ?></td>
                        <td><?= conservation_badge($animal['conservation_status']) ?></td>
                        <td><?= review_badge($animal['review_status']) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($animal['review_status'] !== 'draft'): ?>
                                <a href="../animal-details.php?id=<?= (int) $animal['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">View</a>
                            <?php endif; ?>
                            <a href="edit-animal.php?id=<?= (int) $animal['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="delete.php?type=animal&amp;id=<?= (int) $animal['id'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$animals): ?>
                    <tr><td colspan="7" class="text-center py-4">No animals found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= render_pagination($pager['page'], $pager['totalPages'], ['q' => $q, 'status' => $status], 'Animal pagination') ?>
<?php admin_footer();
