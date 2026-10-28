<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Hanya owner yang boleh mengelola user dan mengganti role. */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isOwner();
    }
}
