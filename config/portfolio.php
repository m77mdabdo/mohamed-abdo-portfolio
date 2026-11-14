<?php

return [
    // Database client tools used by backup/restore (XAMPP MariaDB by default on this Mac).
    // Defaults follow the engine in use: Homebrew MySQL 8.4 for "mysql", XAMPP for "mariadb".
    // On a server without these paths the clients on PATH are used.
    'mysqldump' => env('BACKUP_MYSQLDUMP') ?: (is_file($dump = env('DB_CONNECTION') === 'mariadb' ? '/Applications/XAMPP/xamppfiles/bin/mysqldump' : '/opt/homebrew/opt/mysql@8.4/bin/mysqldump') ? $dump : 'mysqldump'),
    'mysql' => env('BACKUP_MYSQL') ?: (is_file($client = env('DB_CONNECTION') === 'mariadb' ? '/Applications/XAMPP/xamppfiles/bin/mysql' : '/opt/homebrew/opt/mysql@8.4/bin/mysql') ? $client : 'mysql'),
    // Private folder (never served over HTTP).
    'backup_path' => storage_path('app/private/backups'),
    'keep_backups' => (int) env('BACKUP_KEEP', 14),
];
