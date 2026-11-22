<?php
declare(strict_types=1);

/*
 |-------------------------------------------------------------
 | Application configuration
 |-------------------------------------------------------------
 | Defaults match a stock XAMPP install (root user, empty password).
 |
 | To change them WITHOUT editing this tracked file, either:
 |   1. create config/config.local.php that returns an array, e.g.
 |        <?php return ['db_user' => 'bio', 'db_pass' => 'secret'];
 |   2. or set environment variables BIO_DB_HOST, BIO_DB_PORT,
 |      BIO_DB_NAME, BIO_DB_USER, BIO_DB_PASS.
 |
 | Order of precedence (last one wins): defaults, config/config.local.php,
 | environment variables. config/config.local.php is git-ignored so
 | credentials are never committed.
 */

$config = [
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_name' => 'biodiversity_system',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4',
];

$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $override = require $localFile;
    if (is_array($override)) {
        $config = array_merge($config, $override);
    }
}

$environment = [
    'db_host' => 'BIO_DB_HOST',
    'db_port' => 'BIO_DB_PORT',
    'db_name' => 'BIO_DB_NAME',
    'db_user' => 'BIO_DB_USER',
    'db_pass' => 'BIO_DB_PASS',
];
foreach ($environment as $key => $variable) {
    $value = getenv($variable);
    if ($value !== false) {
        $config[$key] = $value;
    }
}

return $config;
