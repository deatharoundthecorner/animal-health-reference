<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';
$notice = isset($_GET['expired']) ? 'Your session expired. Please sign in again.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mb_substr(trim((string) ($_POST['username'] ?? '')), 0, 100);
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_valid()) {
        $error = 'The form expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        // Always run password_verify so the response time does not reveal whether the username exists.
        $hash = $admin['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $valid = password_verify($password, $hash);

        if ($admin && $valid) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['last_activity'] = time();
            unset($_SESSION['csrf_token']);

            header('Location: dashboard.php');
            exit;
        }

        usleep(500000); // slow down password guessing
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin login | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root { --dark-green: #1b4332; --forest-green: #2d6a4f; }
        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at 20% 15%, rgba(82, 183, 136, 0.35), transparent 30%),
                radial-gradient(circle at 85% 85%, rgba(127, 85, 57, 0.25), transparent 30%),
                linear-gradient(120deg, #f4fff8, #eef4ef);
            display: grid;
            place-items: center;
        }
        .login-card { border: 0; border-radius: 1rem; box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12); overflow: hidden; }
        .login-header { background: linear-gradient(135deg, var(--dark-green), var(--forest-green)); }
        .btn-login { background-color: var(--dark-green); border-color: var(--dark-green); }
        .btn-login:hover { background-color: #163628; border-color: #163628; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-7 col-lg-5">
                <div class="card login-card">
                    <div class="card-header login-header text-white p-4 text-center">
                        <h1 class="h4 mb-1">Admin portal</h1>
                        <small><?= e(APP_NAME) ?></small>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <?php if ($notice !== ''): ?>
                            <div class="alert alert-info" role="status"><?= e($notice) ?></div>
                        <?php endif; ?>
                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="post" novalidate>
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                            </div>
                            <button type="submit" class="btn btn-login text-white w-100 py-2">Sign in</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="../index.php" class="text-decoration-none">Back to the site</a>
                        </div>
                    </div>
                </div>
                <p class="text-center mt-3 text-muted small mb-0">No account yet? Run <code>php tools/create-admin.php admin</code> on the server.</p>
            </div>
        </div>
    </div>
</body>
</html>
