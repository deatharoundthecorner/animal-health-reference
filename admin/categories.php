<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_simple_list.php';

render_simple_list($pdo, [
    'key' => 'categories',
    'title' => 'Animal groups',
    'singular' => 'group',
    'table' => 'categories',
    'name_column' => 'category_name',
    'max_length' => 100,
    'usage_subquery' => 'SELECT COUNT(*) FROM animals x WHERE x.category_id = t.id',
    'usage_label' => 'Animals',
    'block_delete' => true,
    'delete_confirm' => 'Delete this group?',
    'help' => 'Groups are broad classes such as Mammals or Birds. A group that still has animals cannot be deleted.',
]);
