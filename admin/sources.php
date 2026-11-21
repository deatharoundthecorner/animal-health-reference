<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

const SOURCE_TYPES = [
    'database' => 'Database or organization',
    'ontology' => 'Ontology or identifier set',
    'manual' => 'Veterinary manual / textbook',
    'guideline' => 'Guideline',
    'article' => 'Journal article',
    'other' => 'Other',
];

$errors = [];
$formInput = ['title' => '', 'publisher' => '', 'url' => '', 'license' => '', 'source_type' => 'database'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = post_string('action', 10);
    $id = post_int('id');

    $formInput = [
        'title' => post_string('title', 255),
        'publisher' => post_string('publisher', 255),
        'url' => post_string('url', 500),
        'license' => post_string('license', 150),
        'source_type' => post_string('source_type', 20),
    ];

    if ($formInput['title'] === '') {
        $errors[] = 'Title is required.';
    }
    if ($formInput['url'] !== '' && safe_url($formInput['url']) === null) {
        $errors[] = 'The link must start with http:// or https://.';
    }
    if (!isset(SOURCE_TYPES[$formInput['source_type']])) {
        $errors[] = 'Invalid source type.';
    }
    if (!in_array($action, ['add', 'edit'], true)) {
        $errors[] = 'Unknown action.';
    }
    if ($action === 'edit' && $id <= 0) {
        $errors[] = 'Invalid source.';
    }

    if (!$errors) {
        $values = [
            'title' => $formInput['title'],
            'publisher' => $formInput['publisher'] !== '' ? $formInput['publisher'] : null,
            'url' => $formInput['url'] !== '' ? $formInput['url'] : null,
            'license' => $formInput['license'] !== '' ? $formInput['license'] : null,
            'source_type' => $formInput['source_type'],
        ];
        try {
            if ($action === 'add') {
                run_query($pdo, 'INSERT INTO sources (title, publisher, url, license, source_type) VALUES (:title, :publisher, :url, :license, :source_type)', $values);
            } else {
                $values['id'] = $id;
                run_query($pdo, 'UPDATE sources SET title = :title, publisher = :publisher, url = :url, license = :license, source_type = :source_type WHERE id = :id', $values);
            }
            flash_set('success', 'Source saved.');
            header('Location: sources.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = is_duplicate_error($e) ? 'A source with that title already exists.' : 'The source could not be saved.';
        }
    }
}

$sources = $pdo->query(
    'SELECT s.*,
            (SELECT COUNT(*) FROM animal_sources x WHERE x.source_id = s.id)
          + (SELECT COUNT(*) FROM disease_sources y WHERE y.source_id = s.id)
          + (SELECT COUNT(*) FROM body_part_sources z WHERE z.source_id = s.id) AS citations
     FROM sources s
     ORDER BY s.title ASC'
)->fetchAll();

admin_header('Sources', 'sources');
?>
<h1 class="h3 mb-3">Sources</h1>

<?= render_errors($errors) ?>

<div class="alert alert-info small">
    Sources are the citations shown on animal, disease and body-part pages. Record the license so reuse stays legal:
    link to commercial or copyrighted material (for example veterinary manuals) but do not copy its text.
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h2 class="h5">Add a source</h2>
        <form method="post" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="col-md-6">
                <label for="new-title" class="form-label small mb-1">Title *</label>
                <input type="text" id="new-title" name="title" class="form-control" maxlength="255" value="<?= e($formInput['title']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="new-publisher" class="form-label small mb-1">Publisher / author</label>
                <input type="text" id="new-publisher" name="publisher" class="form-control" maxlength="255" value="<?= e($formInput['publisher']) ?>">
            </div>
            <div class="col-md-6">
                <label for="new-url" class="form-label small mb-1">Link (https://…)</label>
                <input type="url" id="new-url" name="url" class="form-control" maxlength="500" value="<?= e($formInput['url']) ?>">
            </div>
            <div class="col-md-3">
                <label for="new-license" class="form-label small mb-1">License</label>
                <input type="text" id="new-license" name="license" class="form-control" maxlength="150" placeholder="e.g. CC BY 4.0, link only" value="<?= e($formInput['license']) ?>">
            </div>
            <div class="col-md-3">
                <label for="new-type" class="form-label small mb-1">Type</label>
                <select id="new-type" name="source_type" class="form-select">
                    <?php foreach (SOURCE_TYPES as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $formInput['source_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12"><button type="submit" class="btn btn-success">Add source</button></div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Title</th><th>Type</th><th>License</th><th>Cited</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($sources as $source): ?>
                    <?php $url = safe_url($source['url']); ?>
                    <tr>
                        <td>
                            <?php if ($url !== null): ?>
                                <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($source['title']) ?></a>
                            <?php else: ?>
                                <?= e($source['title']) ?>
                            <?php endif; ?>
                            <?php if (!empty($source['publisher'])): ?><div class="small text-muted"><?= e($source['publisher']) ?></div><?php endif; ?>
                        </td>
                        <td><?= e(SOURCE_TYPES[$source['source_type']] ?? $source['source_type']) ?></td>
                        <td class="small"><?= e((string) $source['license']) ?></td>
                        <td><?= (int) $source['citations'] ?></td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSource<?= (int) $source['id'] ?>">Edit</button>
                            <a href="delete.php?type=source&amp;id=<?= (int) $source['id'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$sources): ?>
                    <tr><td colspan="5" class="text-center py-4">No sources yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($sources as $source): ?>
    <div class="modal fade" id="editSource<?= (int) $source['id'] ?>" tabindex="-1" aria-labelledby="editSourceLabel<?= (int) $source['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= (int) $source['id'] ?>">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editSourceLabel<?= (int) $source['id'] ?>">Edit source</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label class="form-label small mb-1" for="t<?= (int) $source['id'] ?>">Title *</label>
                                <input type="text" id="t<?= (int) $source['id'] ?>" name="title" class="form-control" maxlength="255" value="<?= e($source['title']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1" for="ty<?= (int) $source['id'] ?>">Type</label>
                                <select id="ty<?= (int) $source['id'] ?>" name="source_type" class="form-select">
                                    <?php foreach (SOURCE_TYPES as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $source['source_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1" for="p<?= (int) $source['id'] ?>">Publisher / author</label>
                                <input type="text" id="p<?= (int) $source['id'] ?>" name="publisher" class="form-control" maxlength="255" value="<?= e((string) $source['publisher']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1" for="l<?= (int) $source['id'] ?>">License</label>
                                <input type="text" id="l<?= (int) $source['id'] ?>" name="license" class="form-control" maxlength="150" value="<?= e((string) $source['license']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1" for="u<?= (int) $source['id'] ?>">Link</label>
                                <input type="url" id="u<?= (int) $source['id'] ?>" name="url" class="form-control" maxlength="500" value="<?= e((string) $source['url']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php admin_footer();
