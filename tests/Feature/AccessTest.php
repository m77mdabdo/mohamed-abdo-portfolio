<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/** 1. Visitors cannot reach the dashboard; editors do not have owner powers. */
class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_sign_in_and_there_is_no_registration(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/pages')->assertRedirect('/admin/login');
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_inactive_users_cannot_enter(): void
    {
        $user = User::factory()->owner()->create();
        $user->forceFill(['is_active' => false])->save();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_editor_edits_drafts_but_cannot_publish_or_manage_people(): void
    {
        $this->importReference();
        $editor = $this->editor();
        $this->actingAs($editor);

        $this->get('/admin/pages')->assertOk();
        $this->get('/admin/pages/'.$this->home()->id.'/edit')->assertOk()->assertDontSee('wire:click="mountAction(\'publish\')', false);
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/activity')->assertForbidden();
        $this->get('/admin/settings/seo')->assertForbidden();
        $this->assertFalse(Gate::allows('publish'));
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_owner_has_every_screen(): void
    {
        $this->importReference();
        $this->actingAs($this->owner());
        foreach (['/admin', '/admin/pages', '/admin/projects', '/admin/media', '/admin/menus', '/admin/contact-messages',
            '/admin/users', '/admin/activity', '/admin/settings/seo', '/admin/settings/interface-texts', '/admin/lists/services'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->assertTrue(Gate::allows('publish'));
    }

    public function test_mass_assignment_cannot_grant_owner_role(): void
    {
        $user = new User(['name' => 'x', 'email' => 'x@example.com', 'password' => 'secret-password-1', 'role' => 'owner', 'is_active' => false]);
        $this->assertSame('editor', $user->role->value);
        $this->assertTrue($user->is_active);
    }

    public function test_last_owner_cannot_be_deleted_and_nobody_deletes_themselves(): void
    {
        $owner = $this->owner();
        $other = $this->editor();
        $this->assertFalse($owner->can('delete', $owner));
        $this->assertTrue($owner->can('delete', $other));
        $second = $this->owner();
        $this->assertTrue($owner->can('delete', $second));
        $second->forceFill(['is_active' => false])->save();
        $this->assertFalse($second->fresh()->can('delete', $owner));
    }
}
