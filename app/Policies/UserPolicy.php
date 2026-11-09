<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/** People management is owner-only. Nobody can delete or demote themselves, and the last owner stays. */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, User $model): bool
    {
        if (! $this->viewAny($user) || $user->is($model)) {
            return false;
        }

        return ! ($model->role === Role::Owner && User::query()->where('role', Role::Owner->value)->where('is_active', true)->count() <= 1);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
