<?php

namespace Tests\Feature;

use App\Content\Import\ReferenceImporter;
use App\Models\ContentRevision;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 12. The original content imports completely, twice without duplicates, never overwriting the owner's edits. */
class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_is_complete_and_repeatable(): void
    {
        $this->importReference();
        $counts = fn () => [Page::count(), PageSection::count(), Project::count(), Media::count(), Service::count(), ContentRevision::count()];
        $first = $counts();
        $this->assertSame([6, 28, 9, 62, 4], array_slice($first, 0, 5));

        Project::query()->where('slug', 'masar')->update(['year' => '2025']);
        Service::query()->first()->update(['title' => ['en' => 'Owner edit', 'ar' => 'تعديل المالك']]);

        $created = app(ReferenceImporter::class)->run();
        $this->assertSame([], $created);
        $this->assertSame($first, $counts());
        $this->assertSame('2025', Project::query()->where('slug', 'masar')->value('year'));
        $this->assertSame('Owner edit', Service::query()->first()->title['en']);
    }

    public function test_no_owner_account_or_password_is_seeded(): void
    {
        $this->importReference();
        $this->assertSame(0, User::query()->count());
    }
}
