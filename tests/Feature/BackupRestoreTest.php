<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 12. Backup and restore round-trip on a temporary database. Runs only against MariaDB
 * (composer test:mariadb); never touches the main database.
 */
class BackupRestoreTest extends TestCase
{
    public function test_backup_restores_into_a_temporary_database(): void
    {
        if (! in_array(DB::getDriverName(), ['mariadb', 'mysql'], true)) {
            $this->markTestSkipped('MySQL/MariaDB only (run: composer test:db).');
        }
        $dir = sys_get_temp_dir().'/portfolio-backup-test-'.uniqid();
        config(['portfolio.backup_path' => $dir]);
        // the migrate connection points at the test database in this run
        $this->assertStringEndsWith('_test', config('database.connections.migrate.database'));

        $this->assertSame(0, Artisan::call('portfolio:backup', ['--no-media' => true]));
        $files = glob($dir.'/db-*.sql.gz');
        $this->assertCount(1, $files);

        $this->assertSame(0, Artisan::call('portfolio:restore', ['file' => $files[0]]));
        $this->assertStringContainsString('_restore_check', Artisan::output());
        $this->assertFalse(collect(DB::connection('migrate')->select('SHOW DATABASES'))->pluck('Database')->contains(fn ($d) => str_ends_with($d, '_test_restore_check')));
        array_map('unlink', glob($dir.'/*'));
        rmdir($dir);
    }
}
