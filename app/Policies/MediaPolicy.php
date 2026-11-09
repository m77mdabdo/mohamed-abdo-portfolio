<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Media $media): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Media $media): bool
    {
        return $user->is_active;
    }

    /** Only the owner deletes files, and only files no page, project or setting uses (checked in the action). */
    public function delete(User $user, Media $media): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
