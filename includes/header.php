<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
if (!isset($metaDescription)) {
    $metaDescription = 'Open reference for animal anatomy, diseases and species information, for biologists, veterinarians and students.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a class="visually-hidden-focusable position-absolute p-2 bg-white" href="#main">Skip to content</a>
