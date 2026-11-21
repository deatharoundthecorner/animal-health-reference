<?php
declare(strict_types=1);

$animalId = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;
if ($animalId <= 0) {
    header('Location: animals.php');
    exit;
}
require __DIR__ . '/_animal_form.php';
