<?php

namespace Tests\Feature;

use App\Content\Media\MediaLibrary;
use App\Filament\Pages\Settings\EditIdentity;
use App\Filament\Resources\Pages\Pages\EditPageContent;
use App\Jobs\OptimizeImage;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** The dashboard screens themselves: saving drafts through the real forms, and image optimisation. */
class DashboardEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_saves_a_settings_draft_that_does_not_reach_the_site(): void
    {
        $this->importReference();
        $this->actingAs($this->editor());

        Livewire::test(EditIdentity::class)
            ->set('data.availability.en', 'Open to new projects')
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->assertActionHidden('publish');

        $this->get('/en')->assertDontSee('Open to new projects');
    }

    public function test_page_editor_form_saves_sections_in_the_order_shown(): void
    {
        $this->importReference();
        $this->actingAs($this->owner());
        $home = $this->home();

        $component = Livewire::test(EditPageContent::class, ['record' => $home->id]);
        $sections = $component->get('data.sections');
        $keys = array_keys($sections);
        // move the last section (contact) to the top and hide the statement
        $reordered = [end($keys) => $sections[end($keys)]] + array_diff_key($sections, [end($keys) => true]);
        foreach ($reordered as $k => $item) {
            if ($item['type'] === 'statement') {
                $reordered[$k]['data']['visible'] = false;
            }
        }
        $component->set('data.sections', $reordered)->call('saveDraft')->assertHasNoErrors();

        $types = $home->sections()->pluck('type')->all();
        $this->assertSame('contact', $types[0]);
        $this->assertFalse((bool) $home->sections()->where('type', 'statement')->value('is_visible'));
        $this->get('/en')->assertSee('class="statement wrap"', false); // hidden only in the draft: the live page still shows it
    }

    public function test_uploaded_images_get_optimised_copies(): void
    {
        Storage::fake('public');
        $media = app(MediaLibrary::class)->store(UploadedFile::fake()->image('shot.jpg', 2000, 1250), 'image');
        (new OptimizeImage($media->id))->handle();
        $media->refresh();

        $this->assertSame('ready', $media->status);
        $formats = collect($media->variants)->pluck('format')->unique()->values()->all();
        $this->assertContains('webp', $formats);
        $this->assertSame([640, 1280, 1920, 2000], collect($media->variants)->where('format', 'webp')->pluck('width')->sort()->values()->all());
        foreach ($media->variants as $variant) {
            Storage::disk('public')->assertExists($variant['path']);
        }
        $this->assertNotNull($media->srcset('webp'));
    }

    public function test_broken_image_is_marked_failed_and_keeps_the_original(): void
    {
        Storage::fake('public');
        $media = app(MediaLibrary::class)->store(UploadedFile::fake()->image('ok.png', 400, 300), 'image');
        Storage::disk('public')->put($media->path, 'not an image any more');
        (new OptimizeImage($media->id))->handle();
        $this->assertSame('failed', Media::query()->find($media->id)->status);
        Storage::disk('public')->assertExists($media->path);
    }
}
