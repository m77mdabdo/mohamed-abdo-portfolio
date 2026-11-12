<?php

/*
 * Runs the test suite against the separate test database (DB_TEST_DATABASE) on the real engine
 * (DB_CONNECTION: mysql or mariadb) with the migration user, so migrations, JSON columns and
 * backup/restore are checked for real. Never uses the main database. Usage: composer test:db
 */
require __DIR__.'/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$test = $_ENV['DB_TEST_DATABASE'] ?? '';
if ($test === '' || $test === ($_ENV['DB_DATABASE'] ?? '')) {
    fwrite(STDERR, "DB_TEST_DATABASE must be set and different from DB_DATABASE.\n");
    exit(1);
}
$env = [
    'DB_CONNECTION' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'DB_SOCKET' => $_ENV['DB_SOCKET'] ?? '',
    'DB_DATABASE' => $test,
    'DB_USERNAME' => $_ENV['DB_MIGRATE_USERNAME'] ?? '',
    'DB_PASSWORD' => $_ENV['DB_MIGRATE_PASSWORD'] ?? '',
];
$process = proc_open([PHP_BINARY, 'artisan', 'test', ...array_slice($argv, 1)], [STDIN, STDOUT, STDERR], $pipes, dirname(__DIR__), array_merge(getenv(), $env));
exit(proc_close($process));
