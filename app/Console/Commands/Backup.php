<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Backs up the database (mysqldump, gzip) and the uploaded files (tar.gz) into storage/app/private/backups.
 * Uses the migration user; the password goes through the environment, never the command line.
 * Keeps the newest N backups (config portfolio.keep_backups).
 */
class Backup extends Command
{
    protected $signature = 'portfolio:backup {--no-media : Database only}';

    protected $description = 'Back up the database and uploaded files to storage/app/private/backups';

    public function handle(): int
    {
        $conn = config('database.connections.mariadb_migrate');
        $dir = config('portfolio.backup_path');
        File::ensureDirectoryExists($dir, 0700);
        $stamp = now()->format('Ymd-His');
        $sql = "{$dir}/db-{$conn['database']}-{$stamp}.sql.gz";

        $dump = Process::fromShellCommandline(
            '"$BIN" --single-transaction --routines --no-tablespaces --default-character-set=utf8mb4 '
            .'--socket="$SOCK" -u"$USER_NAME" "$DB" | gzip -9 > "$OUT"',
            null,
            ['BIN' => config('portfolio.mysqldump'), 'SOCK' => $conn['unix_socket'], 'USER_NAME' => $conn['username'],
                'MYSQL_PWD' => $conn['password'], 'DB' => $conn['database'], 'OUT' => $sql],
            null,
            600,
        );
        $dump->run();
        if (! $dump->isSuccessful() || filesize($sql) < 200) {
            @unlink($sql);
            $this->error('Database backup failed: '.trim($dump->getErrorOutput()));

            return self::FAILURE;
        }
        chmod($sql, 0600);
        $this->info('Database: '.$sql.' ('.round(filesize($sql) / 1024).' KB)');

        if (! $this->option('no-media')) {
            $media = "{$dir}/media-{$stamp}.tar.gz";
            $tar = new Process(['tar', '-czf', $media, '-C', storage_path('app/public'), 'media'], null, null, null, 600);
            $tar->run();
            if ($tar->isSuccessful()) {
                chmod($media, 0600);
                $this->info('Files: '.$media.' ('.round(filesize($media) / 1024 / 1024, 1).' MB)');
            } else {
                $this->warn('Files backup failed: '.trim($tar->getErrorOutput()));
            }
        }

        $this->prune($dir);

        return self::SUCCESS;
    }

    private function prune(string $dir): void
    {
        foreach (['db-*.sql.gz', 'media-*.tar.gz'] as $pattern) {
            $files = glob($dir.'/'.$pattern) ?: [];
            rsort($files);
            foreach (array_slice($files, config('portfolio.keep_backups')) as $old) {
                @unlink($old);
            }
        }
    }
}
