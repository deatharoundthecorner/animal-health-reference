<?php
declare(strict_types=1);

/*
 * Upgrade an EXISTING installation to the current version WITHOUT losing data.
 *
 *   php tools/upgrade.php              add the feeding guide tables and, if they are empty,
 *                                      the feeding sample data
 *   php tools/upgrade.php --no-seed    add the tables only
 *
 * It only runs CREATE TABLE IF NOT EXISTS statements, so it is safe to run more than once.
 * Back up your database first anyway.
 */

require_once __DIR__ . '/sql-runner.php';
require_once __DIR__ . '/../config/db.php'; // opens $pdo using config/config.php

$noSeed = in_array('--no-seed', array_slice($argv, 1), true);

$pdo->exec('SET NAMES utf8mb4');

$required = ['animals', 'sources'];
foreach ($required as $table) {
    $found = $pdo->query("SHOW TABLES LIKE '" . $table . "'")->fetchColumn();
    if (!$found) {
        fwrite(STDERR, "The table '{$table}' does not exist. Run  php tools/install.php  first.\n");
        exit(1);
    }
}

$statements = run_sql_file($pdo, __DIR__ . '/../database/schema-feeding.sql');
echo "Feeding guide tables are in place ({$statements} statements).\n";

$feeds = (int) $pdo->query('SELECT COUNT(*) FROM feeds')->fetchColumn();
if ($noSeed) {
    echo "Skipped the sample data (--no-seed).\n";
} elseif ($feeds > 0) {
    echo "Feed types already exist ({$feeds}); the sample data was NOT loaded again.\n";
} else {
    $animals = (int) $pdo->query("SELECT COUNT(*) FROM animals WHERE common_name IN ('Dog','Cat','Cattle','Goat','Sheep','Horse','Domestic Pig','Chicken','Rabbit')")->fetchColumn();
    if ($animals === 0) {
        echo "None of the sample animals exist in this database, so feeding plans would not attach to anything.\n";
        echo "Add your own feeds and plans in the admin area instead (use --no-seed to hide this message).\n";
    } else {
        $seedStatements = run_sql_file($pdo, __DIR__ . '/../database/seed-feeding.sql');
        echo "Feeding sample data loaded ({$seedStatements} statements).\n";
        echo "NOTE: the sample data has not been expert-reviewed; every record is marked 'unreviewed'.\n";
    }
}

echo "Done.\n";
