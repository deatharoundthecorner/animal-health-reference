<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$q = get_string('q');
$status = get_string('status');
if (!isset(REVIEW_LABELS[$status])) {
    $status = '';
}
$pathogen = get_string('pathogen');
if (!isset(PATHOGEN_TYPES[$pathogen])) {
    $pathogen = '';
}

$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $like = like_term($q);
    $where[] = '(d.disease_name LIKE :q1 OR d.pathogen_name LIKE :q2)';
    $params += ['q1' => $like, 'q2' => $like];
}
if ($status !== '') {
    $where[] = 'd.review_status = :status';
    $params['status'] = $status;
}
if ($pathogen !== '') {
    $where[] = 'd.pathogen_type = :pathogen';
    $params['pathogen'] = $pathogen;
}
$whereSql = implode(' AND ', $where);

$total = (int) run_query($pdo, "SELECT COUNT(*) FROM diseases d WHERE {$whereSql}", $params)->fetchColumn();
$perPage = 25;
$pager = paginate($total, $perPage, get_int('page'));

$diseases = run_query(
    $pdo,
    "SELECT d.id, d.disease_name, d.pathogen_type, d.is_zoonotic, d.woah_listed, d.review_status,
            (SELECT COUNT(*) FROM animal_diseases x WHERE x.disease_id = d.id) AS species_count,
            (SELECT COUNT(*) FROM disease_sources y WHERE y.disease_id = d.id) AS source_count
     FROM diseases d
     WHERE {$whereSql}
     ORDER BY d.disease_name ASC
     LIMIT :limit OFFSET :offset",
    $params + ['limit' => $perPage, 'offset' => $pager['offset']]
)->fetchAll();

admin_header('Diseases', 'diseases');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Diseases</h1>
    <a href="add-disease.php" class="btn btn-success">+ Add disease</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-4"><input type="search" name="q" class="form-control" placeholder="Search by name or pathogen" value="<?= e($q) ?>" maxlength="100"></div>
    <div class="col-md-3">
        <select name="pathogen" class="form-select" aria-label="Cause">
            <option value="">Any cause</option>
            <?php foreach (PATHOGEN_TYPES as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $pathogen === $value ? 'selected' : '' ?>><?= e($label) ?></option>
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
    <div class="col-auto"><button class="btn btn-outline-success" type="submit">Filter</button> <a href="diseases.php" class="btn btn-outline-secondary">Clear</a></div>
</form>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Disease</th><th>Cause</th><th>Flags</th><th>Species</th><th>Sources</th><th>Review</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($diseases as $disease): ?>
                    <tr>
                        <td><strong><?= e($disease['disease_name']) ?></strong></td>
                        <td><?= e(pathogen_label($disease['pathogen_type'])) ?></td>
                        <td><?= disease_flags($disease) ?></td>
                        <td><?= (int) $disease['species_count'] ?></td>
                        <td>
                            <?php if ((int) $disease['source_count'] === 0): ?>
                                <span class="badge text-bg-warning">none</span>
                            <?php else: ?>
                                <?= (int) $disease['source_count'] ?>
                            <?php endif; ?>
                        </td>
                        <td><?= review_badge($disease['review_status']) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($disease['review_status'] !== 'draft'): ?>
                                <a href="../disease-details.php?id=<?= (int) $disease['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">View</a>
                            <?php endif; ?>
                            <a href="edit-disease.php?id=<?= (int) $disease['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="delete.php?type=disease&amp;id=<?= (int) $disease['id'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$diseases): ?>
                    <tr><td colspan="7" class="text-center py-4">No diseases found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= render_pagination($pager['page'], $pager['totalPages'], ['q' => $q, 'status' => $status, 'pathogen' => $pathogen], 'Disease pagination') ?>
<?php admin_footer();
