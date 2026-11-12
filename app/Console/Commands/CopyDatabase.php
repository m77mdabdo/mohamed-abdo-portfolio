<?php

namespace App\Console\Commands;

use App\Content\Publisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies every row from one database connection to another whose schema was created by the same migrations
 * (used to move the site from MariaDB to MySQL). Refuses to write into a target that already has content,
 * copies in id order in chunks, keeps ids, and checks row counts table by table afterwards.
 */
class CopyDatabase extends Command
{
    protected $signature = 'portfolio:copy-database {from : source connection} {to : target connection (already migrated, empty)}';

    protected $description = 'Copy all data between two migrated connections and verify the row counts';

    /** Laravel tables that should not be carried over. */
    private const SKIP = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    public function handle(): int
    {
        $from = DB::connection($this->argument('from'));
        $to = DB::connection($this->argument('to'));
        $tables = array_values(array_diff(
            array_map(fn ($t) => $t['name'], Schema::connection($this->argument('to'))->getTables()),
            self::SKIP,
        ));

        foreach ($tables as $table) {
            if ($to->table($table)->exists() && $table !== 'users') {
                $this->error("Target table \"{$table}\" is not empty. Nothing was copied.");

                return self::FAILURE;
            }
        }

        $to->statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $to->transaction(function () use ($from, $to, $tables) {
                foreach ($tables as $table) {
                    $to->table($table)->delete();
                    $order = Schema::connection($this->argument('from'))->hasColumn($table, 'id') ? 'id' : null;
                    $query = $from->table($table);
                    $rows = $order ? $query->orderBy($order)->get() : $query->get();
                    foreach ($rows->chunk(200) as $chunk) {
                        $to->table($table)->insert($chunk->map(fn ($r) => (array) $r)->all());
                    }
                }
            });
        } finally {
            $to->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $report = [];
        $ok = true;
        foreach ($tables as $table) {
            $a = $from->table($table)->count();
            $b = $to->table($table)->count();
            $ok = $ok && $a === $b;
            $report[] = [$table, $a, $b, $a === $b ? 'ok' : 'DIFFERENT'];
        }
        $this->table(['table', 'source rows', 'target rows', 'check'], $report);

        if (! $ok) {
            $this->error('Row counts differ.');

            return self::FAILURE;
        }
        DB::setDefaultConnection($this->argument('to'));
        $rehashed = Publisher::rehashAll();
        $this->info("All tables copied and verified ({$rehashed} version fingerprints refreshed for the new engine).");

        return self::SUCCESS;
    }
}
