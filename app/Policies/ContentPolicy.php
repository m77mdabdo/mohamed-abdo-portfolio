<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pages, projects and shared lists. Any active staff member edits the draft; deleting is owner-only
 * (deleting is the one change that reaches visitors without a publish step). Publishing uses Gate "publish".
 */
class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Model $model): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Model $model): bool
    {
        return $user->is_active;
    }

    public function reorder(User $user): bool
    {
        return $user->is_active;
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }
}
