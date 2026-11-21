<?php
declare(strict_types=1);

$diseaseId = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;
if ($diseaseId <= 0) {
    header('Location: diseases.php');
    exit;
}
require __DIR__ . '/_disease_form.php';
