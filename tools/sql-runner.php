<?php
declare(strict_types=1);

/*
 * Shared helpers for the command-line tools (install.php, upgrade.php).
 * Not meant to be run on its own.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

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

/** Run a SQL file, skipping its CREATE DATABASE / USE lines (the tools select the database themselves). */
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
