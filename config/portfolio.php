<?php

return [
    // Database client tools used by backup/restore (XAMPP MariaDB by default on this Mac).
    // Defaults follow the engine in use: Homebrew MySQL 8.4 for "mysql", XAMPP for "mariadb".
    'mysqldump' => env('BACKUP_MYSQLDUMP', env('DB_CONNECTION') === 'mariadb' ? '/Applications/XAMPP/xamppfiles/bin/mysqldump' : '/opt/homebrew/opt/mysql@8.4/bin/mysqldump'),
    'mysql' => env('BACKUP_MYSQL', env('DB_CONNECTION') === 'mariadb' ? '/Applications/XAMPP/xamppfiles/bin/mysql' : '/opt/homebrew/opt/mysql@8.4/bin/mysql'),
    // Private folder (never served over HTTP).
    'backup_path' => storage_path('app/private/backups'),
    'keep_backups' => (int) env('BACKUP_KEEP', 14),
];
