<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_simple_list.php';

render_simple_list($pdo, [
    'key' => 'symptoms',
    'title' => 'Signs and symptoms',
    'singular' => 'symptom',
    'table' => 'symptoms',
    'name_column' => 'symptom_name',
    'max_length' => 150,
    'usage_subquery' => 'SELECT COUNT(*) FROM disease_symptoms x WHERE x.symptom_id = t.id',
    'usage_label' => 'Diseases',
    'block_delete' => false,
    'delete_confirm' => 'Delete this symptom? It will be removed from every disease that lists it.',
    'help' => 'Signs are normally added from the disease form. Use this page to rename a sign everywhere or to remove ones you no longer need. Deleting a sign unlinks it from all diseases.',
]);
