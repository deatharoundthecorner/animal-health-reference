<?php
declare(strict_types=1);

/*
 * Command-line installer: creates the database, the tables and the sample data.
 *
 *   php tools/install.php              install (refuses to touch an existing, non-empty database)
 *   php tools/install.php --force      drop and recreate every table (ALL DATA IS LOST)
 *   php tools/install.php --no-seed    create the empty schema only (no sample data)
 *
 * Connection settings come from config/config.php (see config/config.local.php
 * and the BIO_DB_* environment variables).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

$options = array_slice($argv, 1);
$force = in_array('--force', $options, true);
$noSeed = in_array('--no-seed', $options, true);

/**
 * Split a SQL script into statements, ignoring semicolons that appear inside
 * quoted strings, backtick identifiers and comments.
 *
 * @return list<string>
 */
function split_sql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $quote = null; // current quote character, or null

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($quote !== null) {
            $buffer .= $char;
            if ($char === '\\' && $quote !== '`') {
                $buffer .= $next;
                $i++;
            } elseif ($char === $quote) {
                if ($next === $quote) { // doubled quote = escaped quote
                    $buffer .= $next;
                    $i++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }

        if ($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $buffer .= "\n";
            continue;
        }
        if ($char === '#') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $buffer .= "\n";
            continue;
        }
        if ($char === '/' && $next === '*') {
            $end = strpos($sql, '*/', $i + 2);
            $i = $end === false ? $length : $end + 1;
            continue;
        }
        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }
        if ($char === ';') {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }
    return $statements;
}

/** Run a SQL file, skipping its CREATE DATABASE / USE lines (the installer handles those). */
function run_sql_file(PDO $pdo, string $path): int
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Cannot read ' . $path);
    }
    $count = 0;
    foreach (split_sql($sql) as $statement) {
        if (preg_match('/^(CREATE\s+DATABASE|USE)\b/i', $statement)) {
            continue;
        }
        $pdo->exec($statement);
        $count++;
    }
    return $count;
}

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
        fwrite(STDERR, "Re-run with --force to DROP and recreate them (all data will be lost).\n");
        exit(1);
    }
}

$server->exec('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$server->exec('USE `' . $dbName . '`');
$server->exec('SET NAMES utf8mb4');

$schemaStatements = run_sql_file($server, __DIR__ . '/../database/schema.sql');
echo "Schema installed ({$schemaStatements} statements).\n";

if (!$noSeed) {
    $seedStatements = run_sql_file($server, __DIR__ . '/../database/seed.sql');
    echo "Sample data loaded ({$seedStatements} statements).\n";
    echo "NOTE: the sample data has not been expert-reviewed; every record is marked 'unreviewed'.\n";
}

echo "\nNext step: create an administrator account:\n";
echo "  php tools/create-admin.php admin\n";
