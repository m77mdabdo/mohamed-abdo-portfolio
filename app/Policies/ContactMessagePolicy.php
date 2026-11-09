<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, ContactMessage $message): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return false;
    } // messages only come from the site form

    public function update(User $user, ContactMessage $message): bool
    {
        return $user->is_active;
    }

    public function delete(User $user, ContactMessage $message): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }
}
