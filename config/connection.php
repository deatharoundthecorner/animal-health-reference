<?php
declare(strict_types=1);

/**
 * Open a PDO connection using the application configuration.
 *
 * This file only defines the function; it does not connect. Web pages and CLI
 * tools that need the shared $pdo should include config/db.php instead.
 *
 * @param bool $selectDatabase pass false to connect to the server only (used by the installer)
 */
function bio_connect(bool $selectDatabase = true): PDO
{
    $config = require __DIR__ . '/config.php';

    $dsn = sprintf('mysql:host=%s;port=%s;', $config['db_host'], $config['db_port']);
    if ($selectDatabase) {
        $dsn .= 'dbname=' . $config['db_name'] . ';';
    }
    $dsn .= 'charset=' . $config['db_charset'];

    return new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
