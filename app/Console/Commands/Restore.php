<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Restores a database backup into a database. By default into a separate check database
 * (mohamed_abdo_portfolio_restore_check) so a backup can be verified without touching live data;
 * restoring into the main database needs --into-main and a typed confirmation.
 */
class Restore extends Command
{
    protected $signature = 'portfolio:restore {file : Path to a db-*.sql.gz backup} {--into-main : Replace the main database (asks for confirmation)} {--keep : Keep the check database afterwards}';

    protected $description = 'Restore a database backup (verifies into a temporary database unless --into-main)';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file) || ! str_ends_with($file, '.sql.gz')) {
            $this->error('Backup file not found (expected a .sql.gz created by portfolio:backup).');

            return self::FAILURE;
        }
        $conn = config('database.connections.mariadb_migrate');
        $target = $this->option('into-main') ? $conn['database'] : $conn['database'].'_restore_check';

        if ($this->option('into-main')) {
            $answer = $this->ask("This replaces ALL data in \"{$target}\". Type the database name to continue");
            if ($answer !== $target) {
                $this->warn('Cancelled.');

                return self::FAILURE;
            }
        } else {
            DB::connection('mariadb_migrate')->statement("DROP DATABASE IF EXISTS `{$target}`");
            DB::connection('mariadb_migrate')->statement("CREATE DATABASE `{$target}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $restore = Process::fromShellCommandline(
            'gunzip -c "$IN" | "$BIN" --default-character-set=utf8mb4 --socket="$SOCK" -u"$USER_NAME" "$DB"',
            null,
            ['IN' => $file, 'BIN' => config('portfolio.mysql'), 'SOCK' => $conn['unix_socket'], 'USER_NAME' => $conn['username'], 'MYSQL_PWD' => $conn['password'], 'DB' => $target],
            null,
            600,
        );
        $restore->run();
        if (! $restore->isSuccessful()) {
            $this->error('Restore failed: '.trim($restore->getErrorOutput()));

            return self::FAILURE;
        }

        $counts = [];
        foreach (['pages', 'page_sections', 'projects', 'media', 'content_revisions', 'contact_messages', 'users'] as $table) {
            $counts[$table] = DB::connection('mariadb_migrate')->table($target.'.'.$table)->count();
        }
        $this->table(['table', 'rows'], collect($counts)->map(fn ($n, $t) => [$t, $n])->values()->all());
        $this->info("Restored into \"{$target}\".");

        if (! $this->option('into-main') && ! $this->option('keep')) {
            DB::connection('mariadb_migrate')->statement("DROP DATABASE IF EXISTS `{$target}`");
            $this->line('The check database was removed again (use --keep to inspect it).');
        }

        return self::SUCCESS;
    }
}
