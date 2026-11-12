<?php

namespace Tests\Feature;

use App\Content\Media\MediaLibrary;
use App\Content\Publisher;
use App\Content\Site;
use App\Content\Subjects\SettingsSubject;
use App\Jobs\NotifyOwnerOfMessage;
use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

/** Findings of the security audit, kept fixed: web exposure, Host header, notice recipient, image size. */
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // The trusted-host list is static on Symfony's Request.
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    public function test_project_folder_refuses_web_requests(): void
    {
        // A local Apache pointed at htdocs must never serve .env, .git, storage or logs.
        $this->assertFileExists(base_path('.htaccess'));
        $this->assertStringContainsString('Require all denied', (string) file_get_contents(base_path('.htaccess')));
    }

    private function production(): void
    {
        config(['app.url' => 'https://site.example.com']);
        $this->app->detectEnvironment(fn () => 'production');
        $this->app->getProvider(AppServiceProvider::class)->boot();
    }

    public function test_a_forged_host_does_not_change_links_in_the_owner_notice(): void
    {
        $this->production();
        $forged = Request::create('http://attacker.test/en/contact-message', 'POST');
        $this->app->instance('request', $forged);
        $this->app['url']->setRequest($forged);

        $message = ContactMessage::query()->forceCreate(['name' => 'Sara', 'email' => 'sara@example.com',
            'message' => 'Hello', 'submission_key' => (string) Str::uuid(), 'locale' => 'en']);
        $body = (new NewContactMessage($message))->render();

        $this->assertStringContainsString('https://site.example.com/admin/contact-messages/'.$message->id, $body);
        $this->assertStringNotContainsString('attacker.test', $body);
    }

    public function test_requests_for_another_host_are_refused_in_production(): void
    {
        $this->production();

        $this->get('https://site.example.com/up')->assertOk();
        $this->get('https://attacker.test/up')->assertStatus(400);
    }

    public function test_notice_without_recipient_goes_to_the_owner_not_the_editable_public_email(): void
    {
        $this->importReference();
        $owner = $this->owner();
        foreach (['identity' => ['email' => 'editor-chosen@evil.test'], 'contact' => ['recipient' => '', 'notify' => true]] as $group => $changes) {
            $setting = SiteSetting::query()->where('group', $group)->firstOrFail();
            $setting->update(['data' => array_merge($setting->data ?? [], $changes)]);
            app(Publisher::class)->publish(new SettingsSubject($group));
        }
        Mail::fake();
        config(['mail.default' => 'smtp']);

        $message = ContactMessage::query()->forceCreate(['name' => 'Sara', 'email' => 'sara@example.com',
            'message' => 'Hello', 'submission_key' => (string) Str::uuid(), 'locale' => 'en']);
        (new NotifyOwnerOfMessage($message->id))->handle(app(Site::class));

        Mail::assertSent(NewContactMessage::class, fn ($mail) => $mail->hasTo($owner->email));
        Mail::assertNotSent(NewContactMessage::class, fn ($mail) => $mail->hasTo('editor-chosen@evil.test'));
    }

    public function test_images_with_huge_pixel_dimensions_are_refused(): void
    {
        // A tiny PNG whose header declares 20000 x 20000 pixels (1.6 GB to decode).
        $png = "\x89PNG\r\n\x1a\n".$this->chunk('IHDR', pack('NNCCCCC', 20000, 20000, 8, 6, 0, 0, 0))
            .$this->chunk('IDAT', (string) gzcompress(''))
            .$this->chunk('IEND', '');
        $path = tempnam(sys_get_temp_dir(), 'big');
        file_put_contents($path, $png);

        try {
            app(MediaLibrary::class)->storePath($path, 'big.png');
            $this->fail('A 400-megapixel image was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertSame(0, Media::query()->count());
        } finally {
            @unlink($path);
        }
    }

    private function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
