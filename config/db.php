<?php
declare(strict_types=1);

/*
 |-------------------------------------------------------------
 | Database connection
 |-------------------------------------------------------------
 | Settings live in config/config.php (override them in
 | config/config.local.php or with BIO_DB_* environment variables).
 | Including this file creates the shared $pdo connection.
 */

require_once __DIR__ . '/connection.php';

try {
    $pdo = bio_connect();
} catch (PDOException $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    exit('Database connection failed. Check config/config.php (or config/config.local.php) and that the database has been installed.');
}
