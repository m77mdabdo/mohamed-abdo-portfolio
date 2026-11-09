<?php

namespace Tests\Feature;

use App\Content\Media\MediaLibrary;
use App\Content\Media\MediaUsage;
use App\Content\Publisher;
use App\Content\Subjects\SettingsSubject;
use App\Models\Media;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Tests\TestCase;

/** 6 + 8. CV download switches on with a real PDF, never 404s without one; unsafe uploads are refused. */
class CvAndMediaTest extends TestCase
{
    use RefreshDatabase;

    private function pdf(string $name = 'cv.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << >>\n%%EOF\n");
    }

    public function test_cv_download_follows_the_uploaded_file(): void
    {
        $this->importReference();
        $this->get('/en/cv/download')->assertRedirect('/en/resume#cv');
        $this->followingRedirects()->get('/en/cv/download')->assertOk()->assertSee('The CV file is not available yet');
        $this->get('/en/resume')->assertSee('CV available on request')->assertDontSee('/en/cv/download"', false);

        $file = app(MediaLibrary::class)->store($this->pdf(), 'document');
        SiteSetting::query()->where('group', 'cv')->update(['data' => json_encode(['file' => $file->id])]);
        app(Publisher::class)->publish(new SettingsSubject('cv'));

        $this->get('/en/resume')->assertSee('Download CV (PDF)');
        $response = $this->get('/en/cv/download')->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('mohamed-abdo-CV.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
        $view = $this->get('/en/cv/download?view=1')->assertOk();
        $this->assertStringStartsWith('inline', $view->headers->get('Content-Disposition'));
        $this->get('/ar/resume')->assertSee('عرض السيرة الذاتية')->assertSee('cv/download?view=1', false);
    }

    public function test_dangerous_files_are_refused(): void
    {
        $this->importReference();
        $library = app(MediaLibrary::class);
        $cases = [
            ['evil.pdf', '<html><script>alert(1)</script></html>', 'document'],
            ['fake.pdf', "%PDF-1.4\n<script>alert(1)</script>", 'document'],
            ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'image'],
            ['shell.jpg', '<?php system($_GET["c"]); ?>', 'image'],
            ['page.html', '<h1>x</h1>', 'image'],
        ];
        foreach ($cases as [$name, $content, $kind]) {
            try {
                $library->store(UploadedFile::fake()->createWithContent($name, $content), $kind);
                $this->fail("{$name} was accepted");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(0, Media::query()->where('original_name', 'like', '%evil%')->count());
    }

    public function test_stored_files_get_random_names_and_used_files_cannot_be_deleted(): void
    {
        $this->importReference();
        $media = app(MediaLibrary::class)->store(UploadedFile::fake()->image('My Photo.jpg', 800, 600), 'image');
        $this->assertMatchesRegularExpression('#^media/\d{4}/\d{2}/[0-9a-z]{26}\.jpg$#', $media->path);

        $portrait = Media::query()->where('source_key', 'reference:me/portrait-cut.webp')->first();
        $this->assertNotEmpty(app(MediaUsage::class)->find($portrait->id));
        $this->assertSame([], app(MediaUsage::class)->find($media->id));
    }

    public function test_private_files_are_not_served(): void
    {
        $this->get('/storage/backups/anything.sql.gz')->assertNotFound();
        $this->get('/storage/app/private/backups')->assertNotFound();
    }
}
