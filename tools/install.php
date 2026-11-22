<?php
declare(strict_types=1);

/*
 * Command-line installer: creates the database, the tables and the sample data.
 *
 *   php tools/install.php              install (refuses to touch an existing, non-empty database)
 *   php tools/install.php --force      drop and recreate every table (ALL DATA IS LOST)
 *   php tools/install.php --no-seed    create the empty schema only (no sample data)
 *
 * To add the feeding guide to an EXISTING installation without losing data, use
 *   php tools/upgrade.php
 *
 * Connection settings come from config/config.php (see config/config.local.php
 * and the BIO_DB_* environment variables).
 */

require_once __DIR__ . '/sql-runner.php';

$options = array_slice($argv, 1);
$force = in_array('--force', $options, true);
$noSeed = in_array('--no-seed', $options, true);

$config = require __DIR__ . '/../config/config.php';
$dbName = $config['db_name'];

if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
    fwrite(STDERR, "Invalid database name in config: only letters, digits and underscores are allowed.\n");
    exit(1);
}

require_once __DIR__ . '/../config/connection.php'; // defines bio_connect(); does not connect by itself

try {
    $server = bio_connect(false);
} catch (PDOException $e) {
    fwrite(STDERR, 'Cannot connect to the database server: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$exists = (bool) $server->query("SHOW DATABASES LIKE '" . $dbName . "'")->fetchColumn();
if ($exists) {
    $tables = (int) $server->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . $dbName . "'"
    )->fetchColumn();
    if ($tables > 0 && !$force) {
        fwrite(STDERR, "Database '{$dbName}' already contains {$tables} table(s).\n");
        fwrite(STDERR, "Re-run with --force to DROP and recreate them (all data will be lost),\n");
        fwrite(STDERR, "or run  php tools/upgrade.php  to add new features to an existing installation.\n");
        exit(1);
    }
}

$server->exec('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$server->exec('USE `' . $dbName . '`');
$server->exec('SET NAMES utf8mb4');

$schemaStatements = run_sql_file($server, __DIR__ . '/../database/schema.sql');
$schemaStatements += run_sql_file($server, __DIR__ . '/../database/schema-feeding.sql');
echo "Schema installed ({$schemaStatements} statements).\n";

if (!$noSeed) {
    $seedStatements = run_sql_file($server, __DIR__ . '/../database/seed.sql');
    $seedStatements += run_sql_file($server, __DIR__ . '/../database/seed-feeding.sql');
    echo "Sample data loaded ({$seedStatements} statements).\n";
    echo "NOTE: the sample data has not been expert-reviewed; every record is marked 'unreviewed'.\n";
}

echo "\nNext step: create an administrator account:\n";
echo "  php tools/create-admin.php admin\n";
