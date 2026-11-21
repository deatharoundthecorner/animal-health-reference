<?php
declare(strict_types=1);

/*
 * Create an administrator account, or reset the password of an existing one.
 *
 *   php tools/create-admin.php <username>              generates a random password and prints it once
 *   php tools/create-admin.php <username> <password>   uses the password you give (min. 10 characters)
 *
 * Note: a password passed on the command line may be kept in your shell history.
 * Prefer the generated password and change it afterwards if you wish.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/db.php';

$username = trim($argv[1] ?? '');
$password = $argv[2] ?? '';

if ($username === '' || !preg_match('/^[A-Za-z0-9_.-]{3,100}$/', $username)) {
    fwrite(STDERR, "Usage: php tools/create-admin.php <username> [password]\n");
    fwrite(STDERR, "Username: 3-100 characters (letters, digits, '.', '_' or '-').\n");
    exit(1);
}

$generated = false;
if ($password === '') {
    $password = bin2hex(random_bytes(9)); // 18 hex characters
    $generated = true;
} elseif (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$existing = $pdo->prepare('SELECT id FROM admins WHERE username = :username');
$existing->execute(['username' => $username]);
$id = $existing->fetchColumn();

if ($id) {
    $stmt = $pdo->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
    $stmt->execute(['hash' => $hash, 'id' => $id]);
    echo "Password updated for administrator '{$username}'.\n";
} else {
    $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:username, :hash)');
    $stmt->execute(['username' => $username, 'hash' => $hash]);
    echo "Administrator '{$username}' created.\n";
}

if ($generated) {
    echo "Generated password (shown only once): {$password}\n";
}
echo "Log in at /admin/login.php\n";
