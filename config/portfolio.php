<?php

return [
    // Database client tools used by backup/restore (XAMPP MariaDB by default on this Mac).
    'mysqldump' => env('BACKUP_MYSQLDUMP', '/Applications/XAMPP/xamppfiles/bin/mysqldump'),
    'mysql' => env('BACKUP_MYSQL', '/Applications/XAMPP/xamppfiles/bin/mysql'),
    // Private folder (never served over HTTP).
    'backup_path' => storage_path('app/private/backups'),
    'keep_backups' => (int) env('BACKUP_KEEP', 14),
];
