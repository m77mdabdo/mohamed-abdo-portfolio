<?php

namespace Tests\Feature;

use App\Content\Publisher;
use App\Content\Site;
use App\Content\Subjects\SettingsSubject;
use App\Jobs\NotifyOwnerOfMessage;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/** 7. Messages are validated, saved exactly once, rate limited, and never lost when e-mail fails. */
class ContactTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge(['name' => 'Sara', 'email' => 'sara@example.com', 'project_type' => 'Custom platform',
            'message' => "Hello\nWe need a booking site.", 'submission_key' => (string) Str::uuid()], $extra);
    }

    public function test_message_is_saved_once_even_when_sent_twice(): void
    {
        $this->importReference();
        Bus::fake([NotifyOwnerOfMessage::class]);
        $data = $this->payload();
        $this->postJson('/en/contact-message', $data)->assertOk()->assertJson(['ok' => true]);
        $this->postJson('/en/contact-message', $data)->assertOk()->assertJsonPath('message', 'This message was already received — thank you.');
        $this->assertSame(1, ContactMessage::query()->count());
        $message = ContactMessage::query()->first();
        $this->assertSame('new', $message->status->value);
        $this->assertSame('en', $message->locale);
        $this->assertNotSame('127.0.0.1', $message->ip_hash); // hashed, not stored in clear
        Bus::assertDispatchedAfterResponse(NotifyOwnerOfMessage::class, 1);
    }

    public function test_validation_and_arabic_messages(): void
    {
        $this->importReference();
        $this->postJson('/ar/contact-message', $this->payload(['name' => '', 'email' => 'not-an-email']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email'])
            ->assertJsonPath('errors.email.0', 'يرجى إدخال بريد إلكتروني صحيح.');
        $this->postJson('/en/contact-message', $this->payload(['message' => str_repeat('x', 5001)]))->assertJsonValidationErrors(['message']);
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_form_works_without_javascript(): void
    {
        $this->importReference();
        $this->from('/en/contact')->post('/en/contact-message', $this->payload())->assertRedirect('/en/contact#contact');
        $this->assertSame(1, ContactMessage::query()->count());
        $this->get('/en/contact')->assertOk()->assertSee('name="submission_key"', false)->assertSee('name="_token"', false);
    }

    public function test_honeypot_is_silently_ignored(): void
    {
        $this->importReference();
        $this->postJson('/en/contact-message', $this->payload(['website' => 'http://spam.example']))->assertOk();
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_rate_limit(): void
    {
        $this->importReference();
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/en/contact-message', $this->payload())->assertOk();
        }
        $this->postJson('/en/contact-message', $this->payload())->assertStatus(429);
        $this->assertSame(3, ContactMessage::query()->count());
    }

    public function test_mail_failure_never_loses_the_message_and_status_is_honest(): void
    {
        $this->importReference();
        $this->owner(); // no recipient set: notices go to the owner's address
        $this->postJson('/en/contact-message', $this->payload())->assertOk();
        $message = ContactMessage::query()->firstOrFail();

        // development mailer: honestly "skipped"
        config(['mail.default' => 'log']);
        (new NotifyOwnerOfMessage($message->id))->handle(app(Site::class));
        $this->assertSame('skipped', $message->fresh()->notification_status);

        // real mailer that fails: the job reports failure, the message stays
        config(['mail.default' => 'smtp']);
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP connection refused'));
        $job = new NotifyOwnerOfMessage($message->id);
        try {
            $job->handle(app(Site::class));
        } catch (RuntimeException $e) {
            $job->failed($e);
        }
        $fresh = $message->fresh();
        $this->assertSame('failed', $fresh->notification_status);
        $this->assertStringContainsString('SMTP connection refused', $fresh->notification_error);
        $this->assertSame('sara@example.com', $fresh->email);
    }

    public function test_notice_is_skipped_when_owner_turns_it_off(): void
    {
        $this->importReference();
        SiteSetting::query()->where('group', 'contact')->update(['data' => json_encode(['recipient' => 'me@example.com', 'notify' => false])]);
        app(Publisher::class)->publish(new SettingsSubject('contact'));
        $this->postJson('/en/contact-message', $this->payload())->assertOk();
        $message = ContactMessage::query()->first();
        (new NotifyOwnerOfMessage($message->id))->handle(app(Site::class));
        $this->assertSame('skipped', $message->fresh()->notification_status);
    }
}
