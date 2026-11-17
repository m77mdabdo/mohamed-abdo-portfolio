<?php

namespace Tests\Feature;

use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/** Regression tests for the November 2026 hardening pass (passwords, dashboard headers). */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_refuses_passwords_weaker_than_the_site_rule(): void
    {
        $user = $this->editor();
        $user->forceFill(['password' => 'Current-Password-123'])->save();
        $this->actingAs($user);
        Filament::setCurrentPanel('admin');

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => 'short123', 'passwordConfirmation' => 'short123', 'currentPassword' => 'Current-Password-123'])
            ->call('save')
            ->assertHasFormErrors(['password']);
        $this->assertTrue(Hash::check('Current-Password-123', $user->refresh()->password));

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => 'New-Password-2026', 'passwordConfirmation' => 'New-Password-2026', 'currentPassword' => 'Current-Password-123'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertTrue(Hash::check('New-Password-2026', $user->refresh()->password));
    }
}
