<?php
declare(strict_types=1);

/*
 * A small reusable "list of names" manager (add / rename / delete) used for
 * groups, body systems and symptoms.
 *
 * Config keys:
 *   key, title, singular            navigation key and wording
 *   table, name_column, max_length  where the names live (code constants, never user input)
 *   sort_column                     optional integer column used to order the list
 *   usage_subquery                  SQL returning how many records use a row (alias t = the row)
 *   usage_label                     column heading for that count
 *   block_delete                    true = refuse to delete rows that are in use
 *   delete_confirm                  text for the browser confirmation
 *   help                            optional help text shown above the form
 *
 * @param array<string, mixed> $cfg
 */
function render_simple_list(PDO $pdo, array $cfg): void
{
    $table = (string) $cfg['table'];
    $nameColumn = (string) $cfg['name_column'];
    $sortColumn = $cfg['sort_column'] ?? null;
    $maxLength = (int) $cfg['max_length'];
    $self = basename($_SERVER['PHP_SELF']);
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf();
        $action = post_string('action', 10);
        $id = post_int('id');
        $name = post_string('name', $maxLength);
        $sort = post_int('sort_order');

        if ($action === 'add' || $action === 'edit') {
            if ($name === '') {
                $errors[] = ucfirst((string) $cfg['singular']) . ' name is required.';
            } elseif ($action === 'edit' && $id <= 0) {
                $errors[] = 'Invalid ' . $cfg['singular'] . '.';
            } else {
                try {
                    if ($action === 'add') {
                        $sql = "INSERT INTO {$table} ({$nameColumn}" . ($sortColumn ? ", {$sortColumn}" : '') . ') VALUES (:name' . ($sortColumn ? ', :sort' : '') . ')';
                        $params = ['name' => $name] + ($sortColumn ? ['sort' => $sort] : []);
                    } else {
                        $sql = "UPDATE {$table} SET {$nameColumn} = :name" . ($sortColumn ? ", {$sortColumn} = :sort" : '') . ' WHERE id = :id';
                        $params = ['name' => $name, 'id' => $id] + ($sortColumn ? ['sort' => $sort] : []);
                    }
                    run_query($pdo, $sql, $params);
                    flash_set('success', ucfirst((string) $cfg['singular']) . ' saved.');
                    header('Location: ' . $self);
                    exit;
                } catch (PDOException $e) {
                    $errors[] = is_duplicate_error($e) ? 'That name already exists.' : 'Could not save the ' . $cfg['singular'] . '.';
                }
            }
        } elseif ($action === 'delete' && $id > 0) {
            $usage = (int) run_query($pdo, "SELECT ({$cfg['usage_subquery']}) FROM {$table} t WHERE t.id = :id", ['id' => $id])->fetchColumn();
            if ($usage > 0 && !empty($cfg['block_delete'])) {
                $errors[] = 'Cannot delete this ' . $cfg['singular'] . ' because it is still in use (' . $usage . ').';
            } else {
                run_query($pdo, "DELETE FROM {$table} WHERE id = :id", ['id' => $id]);
                flash_set('success', ucfirst((string) $cfg['singular']) . ' deleted.');
                header('Location: ' . $self);
                exit;
            }
        }
    }

    $rows = $pdo->query(
        "SELECT t.id, t.{$nameColumn} AS name" . ($sortColumn ? ", t.{$sortColumn} AS sort_order" : '') . ", ({$cfg['usage_subquery']}) AS usage_count
         FROM {$table} t
         ORDER BY " . ($sortColumn ? "t.{$sortColumn}, " : '') . "t.{$nameColumn}"
    )->fetchAll();

    admin_header((string) $cfg['title'], (string) $cfg['key']);
    ?>
    <h1 class="h3 mb-3"><?= e((string) $cfg['title']) ?></h1>

    <?= render_errors($errors) ?>

    <?php if (!empty($cfg['help'])): ?>
        <div class="alert alert-info small"><?= e((string) $cfg['help']) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h5">Add <?= e((string) $cfg['singular']) ?></h2>
            <form method="post" class="row g-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div class="col-md-<?= $sortColumn ? '6' : '9' ?>">
                    <input type="text" name="name" class="form-control" maxlength="<?= $maxLength ?>" placeholder="Name" aria-label="Name" required>
                </div>
                <?php if ($sortColumn): ?>
                    <div class="col-md-3">
                        <input type="number" name="sort_order" class="form-control" min="0" max="65535" placeholder="Sort order" aria-label="Sort order" value="0">
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100">Add</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <?php if ($sortColumn): ?><th>Order</th><?php endif; ?>
                        <th><?= e((string) $cfg['usage_label']) ?></th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $inUse = (int) $row['usage_count'] > 0; ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <?php if ($sortColumn): ?><td><?= (int) $row['sort_order'] ?></td><?php endif; ?>
                            <td><?= (int) $row['usage_count'] ?></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= (int) $row['id'] ?>">Edit</button>
                                <form method="post" class="d-inline" data-confirm="<?= e((string) $cfg['delete_confirm']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" <?= $inUse && !empty($cfg['block_delete']) ? 'disabled title="In use"' : '' ?>>Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr><td colspan="4" class="text-center py-4">Nothing here yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php foreach ($rows as $row): ?>
        <div class="modal fade" id="edit<?= (int) $row['id'] ?>" tabindex="-1" aria-labelledby="editLabel<?= (int) $row['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="editLabel<?= (int) $row['id'] ?>">Edit <?= e((string) $cfg['singular']) ?></h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label" for="name<?= (int) $row['id'] ?>">Name</label>
                            <input type="text" id="name<?= (int) $row['id'] ?>" name="name" class="form-control mb-2" maxlength="<?= $maxLength ?>" value="<?= e($row['name']) ?>" required>
                            <?php if ($sortColumn): ?>
                                <label class="form-label" for="sort<?= (int) $row['id'] ?>">Sort order</label>
                                <input type="number" id="sort<?= (int) $row['id'] ?>" name="sort_order" class="form-control" min="0" max="65535" value="<?= (int) $row['sort_order'] ?>">
                            <?php endif; ?>
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
    <?php
    admin_footer();
}
