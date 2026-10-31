<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SessionService
{
    /**
     * Mencabut sesi milik satu user langsung dari tabel sessions.
     *
     * Dipakai saat password diganti atau akun dinonaktifkan: perangkat lain
     * yang masih memakai kredensial lama harus langsung keluar, bukan menunggu
     * kedaluwarsa.
     *
     * Token remember milik user juga dihapus. Cookie recaller hidup di
     * browser, bukan di tabel sessions, sehingga tanpa langkah ini perangkat
     * yang menyimpannya masih bisa membangun sesi baru walau akun sudah
     * dinonaktifkan (temuan audit keamanan 31 Oktober 2026).
     */
    public function revokeForUser(int $userId, ?string $exceptSessionId = null): int
    {
        User::whereKey($userId)->update(['remember_token' => null]);

        $query = DB::table('sessions')->where('user_id', $userId);

        if ($exceptSessionId !== null) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->delete();
    }
}
