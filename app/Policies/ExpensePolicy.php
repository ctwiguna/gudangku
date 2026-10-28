<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Data keuangan hanya untuk owner. Manajer outlet tidak boleh
     * melihat, apalagi mengubah, pengeluaran dan laporan.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->isOwner();
    }
}
