<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_simple_list.php';

render_simple_list($pdo, [
    'key' => 'body-systems',
    'title' => 'Body systems',
    'singular' => 'body system',
    'table' => 'body_systems',
    'name_column' => 'system_name',
    'sort_column' => 'sort_order',
    'max_length' => 100,
    'usage_subquery' => 'SELECT COUNT(*) FROM body_parts x WHERE x.system_id = t.id',
    'usage_label' => 'Body parts',
    'block_delete' => true,
    'delete_confirm' => 'Delete this body system?',
    'help' => 'Body systems (cardiovascular, digestive, …) organise the anatomy pages. The sort order controls how they are listed. A system that still has body parts cannot be deleted.',
]);
