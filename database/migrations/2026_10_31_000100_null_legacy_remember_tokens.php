<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fitur "Ingat saya" dihapus setelah audit keamanan 31 Oktober 2026:
     * cookie recaller melewati pemeriksaan is_active dan batas absolute sesi.
     *
     * Token remember lama dinolkan supaya cookie recaller yang masih tersimpan
     * di browser tidak bisa membangun sesi baru pada akun mana pun.
     */
    public function up(): void
    {
        DB::table('users')->update(['remember_token' => null]);
    }

    public function down(): void
    {
        // Tidak ada data yang bisa dikembalikan; token lama sudah tidak valid.
    }
};
