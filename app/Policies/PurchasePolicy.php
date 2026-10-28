<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    /**
     * Daftar penerimaan boleh dilihat kedua role.
     * Pembatasan outlet dilakukan di query, bukan di sini.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Manajer hanya boleh melihat pembelian outlet yang ditugaskan. */
    public function view(User $user, Purchase $purchase): bool
    {
        return $user->isOwner() || $purchase->outlet_id === $user->outlet_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Manajer boleh mengubah jumlah penerimaan selama pembelian belum berharga
     * dan hanya untuk outlet sendiri. Setelah owner mengisi harga, jumlah terkunci.
     */
    public function update(User $user, Purchase $purchase): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $purchase->outlet_id === $user->outlet_id && ! $purchase->isPriced();
    }

    /** Hanya owner, dan hanya selama belum berharga serta belum dibayar. */
    public function delete(User $user, Purchase $purchase): bool
    {
        return $user->isOwner() && ! $purchase->isPriced() && ! $purchase->isPaid();
    }

    /** Melengkapi harga item hanya untuk owner. */
    public function price(User $user, Purchase $purchase): bool
    {
        return $user->isOwner();
    }

    /** Menandai pembayaran hanya untuk owner, dan hanya setelah berharga. */
    public function markPaid(User $user, Purchase $purchase): bool
    {
        return $user->isOwner() && $purchase->isPriced() && ! $purchase->isPaid();
    }
}
