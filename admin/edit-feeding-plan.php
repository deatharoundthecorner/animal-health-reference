<?php
declare(strict_types=1);

$planId = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;
if ($planId <= 0) {
    header('Location: feeding-plans.php');
    exit;
}
require __DIR__ . '/_plan_form.php';
