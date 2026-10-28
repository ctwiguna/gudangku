<?php

namespace App\Policies;

use App\Models\Outlet;
use App\Models\User;

class OutletPolicy
{
    /** Pengaturan outlet hanya untuk owner. Manajer mendapat 403. */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, Outlet $outlet): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Outlet $outlet): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Outlet $outlet): bool
    {
        return $user->isOwner();
    }
}
