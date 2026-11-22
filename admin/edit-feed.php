<?php
declare(strict_types=1);

$feedId = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;
if ($feedId <= 0) {
    header('Location: feeds.php');
    exit;
}
require __DIR__ . '/_feed_form.php';
