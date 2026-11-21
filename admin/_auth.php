<?php
declare(strict_types=1);

/*
 * Include at the top of every admin page that requires a signed-in administrator.
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/_forms.php';

const ADMIN_IDLE_TIMEOUT = 7200; // seconds

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > ADMIN_IDLE_TIMEOUT) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}
$_SESSION['last_activity'] = time();

/** Stop the request unless the POSTed CSRF token is valid. */
function require_csrf(): void
{
    if (!csrf_valid()) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, reload the page and try again.');
    }
}
