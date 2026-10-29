<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SessionService
{
    /**
     * Mencabut sesi milik satu user langsung dari tabel sessions.
     *
     * Dipakai saat password diganti: perangkat lain yang masih memakai
     * password lama harus langsung keluar, bukan menunggu kedaluwarsa.
     */
    public function revokeForUser(int $userId, ?string $exceptSessionId = null): int
    {
        $query = DB::table('sessions')->where('user_id', $userId);

        if ($exceptSessionId !== null) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->delete();
    }
}
