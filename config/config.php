<?php
declare(strict_types=1);

/*
 |-------------------------------------------------------------
 | Application configuration
 |-------------------------------------------------------------
 | Defaults match a stock XAMPP install (root user, empty password).
 |
 | To override them WITHOUT editing this tracked file, either:
 |   1. create config/config.local.php that returns an array, e.g.
 |        <?php return ['db_user' => 'bio', 'db_pass' => 'secret'];
 |   2. or set environment variables BIO_DB_HOST, BIO_DB_PORT,
 |      BIO_DB_NAME, BIO_DB_USER, BIO_DB_PASS.
 |
 | config/config.local.php is git-ignored so credentials are never committed.
 */

$envOr = static function (string $name, string $default): string {
    $value = getenv($name);
    return $value === false ? $default : $value;
};

$config = [
    'db_host' => $envOr('BIO_DB_HOST', 'localhost'),
    'db_port' => $envOr('BIO_DB_PORT', '3306'),
    'db_name' => $envOr('BIO_DB_NAME', 'biodiversity_system'),
    'db_user' => $envOr('BIO_DB_USER', 'root'),
    'db_pass' => $envOr('BIO_DB_PASS', ''),
    'db_charset' => 'utf8mb4',
];

$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $override = require $localFile;
    if (is_array($override)) {
        $config = array_merge($config, $override);
    }
}

return $config;
