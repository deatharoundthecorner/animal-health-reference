<?php
declare(strict_types=1);

/*
 * Shared admin page layout and flash messages.
 */

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $flash) {
        $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info';
        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
            . e($flash['message'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/** @param list<string> $errors */
function render_errors(array $errors): string
{
    if (!$errors) {
        return '';
    }
    $html = '<div class="alert alert-danger"><ul class="mb-0">';
    foreach ($errors as $error) {
        $html .= '<li>' . e($error) . '</li>';
    }
    return $html . '</ul></div>';
}

function admin_header(string $title, string $active = ''): void
{
    $links = [
        'dashboard' => ['dashboard.php', 'Dashboard'],
        'animals' => ['animals.php', 'Animals'],
        'diseases' => ['diseases.php', 'Diseases'],
        'body-parts' => ['body-parts.php', 'Body parts'],
        'symptoms' => ['symptoms.php', 'Symptoms'],
        'sources' => ['sources.php', 'Sources'],
        'categories' => ['categories.php', 'Groups'],
        'body-systems' => ['body-systems.php', 'Body systems'],
    ];
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> | Admin | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark topbar py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-semibold" href="dashboard.php">Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($links as $key => [$href, $label]): ?>
                    <li class="nav-item"><a class="nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <span class="text-white-50 small me-3">Signed in as <?= e((string) ($_SESSION['admin_username'] ?? '')) ?></span>
            <a href="../index.php" class="btn btn-sm btn-outline-light me-2" target="_blank" rel="noopener">View site</a>
            <a href="logout.php" class="btn btn-sm btn-light">Logout</a>
        </div>
    </div>
</nav>
<main class="container-fluid px-4 py-4">
    <?= flash_render() ?>
<?php
}

function admin_footer(): void
{
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
<?php
}
