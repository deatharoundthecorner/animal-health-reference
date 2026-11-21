<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$systems = $pdo->query('SELECT id, system_name FROM body_systems ORDER BY sort_order, system_name')->fetchAll();

$q = get_string('q');
$systemId = get_int('system');
$status = get_string('status');
if (!isset(REVIEW_LABELS[$status])) {
    $status = '';
}

$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[] = 'bp.part_name LIKE :q1';
    $params['q1'] = like_term($q);
}
if ($systemId > 0) {
    $where[] = 'bp.system_id = :system_id';
    $params['system_id'] = $systemId;
}
if ($status !== '') {
    $where[] = 'bp.review_status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$total = (int) run_query($pdo, "SELECT COUNT(*) FROM body_parts bp WHERE {$whereSql}", $params)->fetchColumn();
$perPage = 25;
$pager = paginate($total, $perPage, get_int('page'));

$parts = run_query(
    $pdo,
    "SELECT bp.id, bp.part_name, bp.uberon_id, bp.review_status, bs.system_name,
            (SELECT COUNT(*) FROM animal_body_parts x WHERE x.body_part_id = bp.id) AS animal_count,
            (SELECT COUNT(*) FROM disease_body_parts y WHERE y.body_part_id = bp.id) AS disease_count,
            (SELECT COUNT(*) FROM body_part_sources z WHERE z.body_part_id = bp.id) AS source_count
     FROM body_parts bp
     INNER JOIN body_systems bs ON bs.id = bp.system_id
     WHERE {$whereSql}
     ORDER BY bs.sort_order, bs.system_name, bp.part_name
     LIMIT :limit OFFSET :offset",
    $params + ['limit' => $perPage, 'offset' => $pager['offset']]
)->fetchAll();

admin_header('Body parts', 'body-parts');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Body parts</h1>
    <a href="add-body-part.php" class="btn btn-success">+ Add body part</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><input type="search" name="q" class="form-control" placeholder="Search by name" value="<?= e($q) ?>" maxlength="100"></div>
    <div class="col-md-3">
        <select name="system" class="form-select" aria-label="Body system">
            <option value="0">Any body system</option>
            <?php foreach ($systems as $system): ?>
                <option value="<?= (int) $system['id'] ?>" <?= $systemId === (int) $system['id'] ? 'selected' : '' ?>><?= e($system['system_name']) ?></option>
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
    <div class="col-auto"><button class="btn btn-outline-success" type="submit">Filter</button> <a href="body-parts.php" class="btn btn-outline-secondary">Clear</a></div>
</form>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Body part</th><th>System</th><th>Uberon</th><th>Species</th><th>Diseases</th><th>Sources</th><th>Review</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($parts as $part): ?>
                    <tr>
                        <td><strong><?= e($part['part_name']) ?></strong></td>
                        <td><?= e($part['system_name']) ?></td>
                        <td class="small"><?= e((string) $part['uberon_id']) ?></td>
                        <td><?= (int) $part['animal_count'] ?></td>
                        <td><?= (int) $part['disease_count'] ?></td>
                        <td><?= (int) $part['source_count'] === 0 ? '<span class="badge text-bg-warning">none</span>' : (int) $part['source_count'] ?></td>
                        <td><?= review_badge($part['review_status']) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($part['review_status'] !== 'draft'): ?>
                                <a href="../body-part-details.php?id=<?= (int) $part['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">View</a>
                            <?php endif; ?>
                            <a href="edit-body-part.php?id=<?= (int) $part['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="delete.php?type=body_part&amp;id=<?= (int) $part['id'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$parts): ?>
                    <tr><td colspan="8" class="text-center py-4">No body parts found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= render_pagination($pager['page'], $pager['totalPages'], ['q' => $q, 'system' => $systemId, 'status' => $status], 'Body part pagination') ?>
<?php admin_footer();
