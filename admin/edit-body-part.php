<?php
declare(strict_types=1);

$partId = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;
if ($partId <= 0) {
    header('Location: body-parts.php');
    exit;
}
require __DIR__ . '/_body_part_form.php';
