<?php
declare(strict_types=1);

/*
 * Common start-up for every admin page: secure session, database, helpers.
 * Pages that need a signed-in administrator include _auth.php instead.
 */

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
