<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** portfolio:preflight refuses an installation that is not safe to make public, and passes a ready one. */
class PreflightTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_settings_are_refused(): void
    {
        config(['app.debug' => true, 'mail.default' => 'log']);

        $this->artisan('portfolio:preflight')
            ->expectsOutputToContain('APP_DEBUG is off')
            ->expectsOutputToContain('Set MAIL_MAILER=smtp')
            ->assertExitCode(1);
    }

    public function test_a_ready_installation_passes(): void
    {
        $this->importReference();
        User::factory()->owner()->create(['email' => 'owner@example.com']);
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'app.debug' => false, 'app.url' => 'https://example.com', 'session.secure' => true,
            'mail.default' => 'smtp', 'mail.from.address' => 'hello@example.com',
        ]);

        $this->artisan('portfolio:preflight')->expectsOutputToContain('Ready for production.')->assertExitCode(0);
    }
}
