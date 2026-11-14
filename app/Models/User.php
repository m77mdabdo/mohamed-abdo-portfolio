<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property Role $role
 */
class User extends Authenticatable implements FilamentUser, HasEmailAuthentication
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'editor',
        'is_active' => true,
        'locale' => 'ar',
    ];

    /** role and is_active are never mass-assignable: they change only through the owner's user management. */
    protected $fillable = ['name', 'email', 'password', 'locale'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => Role::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function isOwner(): bool
    {
        return $this->role === Role::Owner;
    }

    /** Every sign-in is finished with a code sent to this e-mail (App\Auth\EmailSignInCode); it cannot be switched off. */
    public function hasEmailAuthentication(): bool
    {
        return true;
    }

    public function toggleEmailAuthentication(bool $condition): void {}
}
