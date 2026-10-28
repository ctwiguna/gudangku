<?php

namespace App\Policies;

use App\Models\StockOpname;
use App\Models\User;

class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Manajer hanya boleh melihat opname outlet sendiri. */
    public function view(User $user, StockOpname $opname): bool
    {
        return $user->isOwner() || $opname->outlet_id === $user->outlet_id;
    }

    /**
     * Kedua role boleh melakukan opname. Pembatasan outlet dilakukan di
     * controller: manajer selalu dipaksa ke outlet sendiri.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /** Opname yang sudah selesai tidak diubah dan tidak dihapus. */
    public function update(User $user, StockOpname $opname): bool
    {
        return false;
    }

    public function delete(User $user, StockOpname $opname): bool
    {
        return false;
    }
}
