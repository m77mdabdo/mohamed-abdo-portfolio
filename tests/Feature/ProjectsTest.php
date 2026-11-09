<?php

namespace Tests\Feature;

use App\Content\Media\MediaLibrary;
use App\Content\Publisher;
use App\Content\Subjects\ProjectSubject;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/** 4. A project with desktop and mobile galleries appears in the list and on its page; slugs are unique and renames redirect. */
class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_nine_reference_projects_are_live_with_galleries(): void
    {
        $this->importReference();
        $html = $this->get('/en/work')->assertOk()->getContent();
        foreach (['creative-mark', 'dar-el-jamila', 'rehlet-sehha', 'anas-fareed', 'masar', 'riyadat-aljawhara', 'saudi-investment-check', 'brand-makers', 'cashier'] as $slug) {
            $this->assertStringContainsString('/en/projects/'.$slug, $html);
            $this->get('/ar/projects/'.$slug)->assertOk();
        }
        $page = $this->get('/en/projects/creative-mark')->getContent();
        $this->assertSame(4, substr_count($page, '— screen '));        // 4 desktop shots (alt text)
        $this->assertSame(3, substr_count($page, 'mobile screen ')); // 3 phone shots
    }

    public function test_create_project_in_dashboard_then_publish(): void
    {
        $this->importReference();
        $this->actingAs($this->owner());
        $library = app(MediaLibrary::class);
        $shot = $library->store(UploadedFile::fake()->image('d1.jpg', 1600, 1000), 'image');
        $phone = $library->store(UploadedFile::fake()->image('m1.jpg', 600, 1300), 'image');

        Livewire::test(CreateProject::class)
            ->fillForm([
                'title' => ['en' => 'Atlas', 'ar' => 'أطلس'], 'kind' => ['en' => 'Booking platform', 'ar' => 'منصة حجز'],
                'summary' => ['en' => 'Atlas summary', 'ar' => 'ملخص أطلس'], 'status_label' => ['en' => 'Live', 'ar' => 'منشور'],
                'status_tone' => 'live', 'slug' => 'atlas', 'is_visible' => true, 'sort_order' => 99,
                'desktop_shots' => [['media_id' => $shot->id, 'caption' => ['en' => 'Atlas home', 'ar' => 'رئيسية أطلس']]],
                'mobile_shots' => [['media_id' => $phone->id, 'caption' => ['en' => '', 'ar' => '']]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('slug', 'atlas')->firstOrFail();
        $this->assertSame(['desktop', 'mobile'], $project->gallery()->pluck('kind')->all());
        $this->get('/en/projects/atlas')->assertNotFound(); // a draft until published

        app(Publisher::class)->publish(new ProjectSubject($project));
        $this->get('/en/work')->assertSee('Atlas');
        $this->get('/en/projects/atlas')->assertOk()->assertSee('Atlas home')->assertSee('Atlas — mobile screen 1');
        $this->get('/ar/projects/atlas')->assertOk()->assertSee('ملخص أطلس');

        // duplicate slug refused
        Livewire::test(CreateProject::class)
            ->fillForm(['title' => ['en' => 'Copy', 'ar' => 'نسخة'], 'kind' => ['en' => 'k', 'ar' => 'k'], 'summary' => ['en' => 's', 'ar' => 's'],
                'status_label' => ['en' => 's', 'ar' => 's'], 'slug' => 'atlas'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_renamed_project_keeps_old_link(): void
    {
        $this->importReference();
        $project = Project::query()->where('slug', 'masar')->first();
        $project->update(['slug' => 'masar-news']);
        app(Publisher::class)->publish(new ProjectSubject($project));
        $this->get('/en/projects/masar')->assertStatus(301)->assertRedirect('/en/projects/masar-news');
        $this->get('/en/projects/masar-news')->assertOk();
    }

    public function test_hidden_and_deleted_projects_disappear(): void
    {
        $this->importReference();
        $project = Project::query()->where('slug', 'cashier')->first();
        $project->update(['is_visible' => false]);
        $this->get('/en/projects/cashier')->assertOk(); // still live until published
        app(Publisher::class)->publish(new ProjectSubject($project));
        $this->get('/en/projects/cashier')->assertNotFound();

        Project::query()->where('slug', 'masar')->first()->delete();
        Cache::flush();
        $this->get('/en/work')->assertDontSee('/en/projects/masar"', false);
    }
}
