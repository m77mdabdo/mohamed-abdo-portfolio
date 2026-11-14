<?php

namespace App\Console\Commands\Concerns;

/**
 * Where the mysql / mysqldump clients connect: the socket when one is configured (this Mac),
 * otherwise host and port (typical hosting). The password always travels in MYSQL_PWD, never in argv.
 */
trait MysqlClient
{
    /** @param array<string, mixed> $conn */
    protected function clientTarget(array $conn): string
    {
        return filled($conn['unix_socket'] ?? null)
            ? '--socket="$SOCK"'
            : '--host="$HOST" --port="$PORT"';
    }

    /** @param array<string, mixed> $conn
     * @return array<string, string> */
    protected function clientEnv(array $conn): array
    {
        return [
            'SOCK' => (string) ($conn['unix_socket'] ?? ''),
            'HOST' => (string) ($conn['host'] ?? '127.0.0.1'),
            'PORT' => (string) ($conn['port'] ?? '3306'),
            'USER_NAME' => (string) $conn['username'],
            'MYSQL_PWD' => (string) $conn['password'],
        ];
    }
}
