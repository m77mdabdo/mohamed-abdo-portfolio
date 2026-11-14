<?php

namespace Tests\Feature;

use App\Mail\SignInCodeMail;
use App\Models\User;
use App\Notifications\SignInCode;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/** Every dashboard sign-in finishes with a one-time code e-mailed to the person signing in. */
class SignInCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_signing_in_needs_the_code_sent_to_the_email(): void
    {
        Notification::fake();
        $user = User::factory()->editor()->create(['password' => 'Long-Password-123456']);

        $login = Livewire::test(Login::class)->fillForm(['email' => $user->email, 'password' => 'Long-Password-123456'])->call('authenticate');
        $this->assertGuest();

        $code = null;
        Notification::assertSentTo($user, SignInCode::class, function (SignInCode $n) use (&$code) {
            $code = $n->code;

            return $n->codeExpiryMinutes === 10;
        });
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        // A wrong code is refused
        $login->set('data.multiFactor.email_code.code', $code === '000000' ? '111111' : '000000')->call('authenticate')->assertHasErrors();
        $this->assertGuest();

        $login->set('data.multiFactor.email_code.code', $code)->call('authenticate')->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_sends_no_code(): void
    {
        Notification::fake();
        $user = User::factory()->owner()->create(['password' => 'Long-Password-123456']);

        Livewire::test(Login::class)->fillForm(['email' => $user->email, 'password' => 'wrong-password-1'])->call('authenticate')->assertHasErrors();

        $this->assertGuest();
        Notification::assertNothingSent();
    }

    public function test_a_deactivated_account_gets_no_code(): void
    {
        Notification::fake();
        $user = User::factory()->editor()->create(['password' => 'Long-Password-123456']);
        $user->forceFill(['is_active' => false])->save();

        Livewire::test(Login::class)->fillForm(['email' => $user->email, 'password' => 'Long-Password-123456'])->call('authenticate');

        $this->assertGuest();
        Notification::assertNothingSent();
    }

    public function test_the_code_email_is_sent_at_once_in_the_dashboard_language(): void
    {
        $user = User::factory()->editor()->create(['locale' => 'ar']);
        $mail = (new SignInCode('482913', 10))->toMail($user);

        $this->assertInstanceOf(SignInCodeMail::class, $mail);
        $this->assertNotInstanceOf(ShouldQueue::class, new SignInCode('1', 1));
        $text = $mail->render();
        $this->assertStringContainsString('482913', $text);
        $this->assertStringContainsString('كود', $text);
        $this->assertTrue($mail->hasTo($user->email));
    }
}
