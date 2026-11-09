<?php

namespace Database\Seeders;

use App\Content\Import\ReferenceImporter;
use Illuminate\Database\Seeder;

/**
 * Imports the original content. Safe to run again: existing records and the owner's edits are kept.
 * No user account is created here — the owner account is created with `php artisan portfolio:create-owner`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(ReferenceImporter $importer): void
    {
        $created = $importer->run();
        $this->command?->info('Reference import: '.($created ? json_encode($created) : 'nothing new (already imported)'));
    }
}
