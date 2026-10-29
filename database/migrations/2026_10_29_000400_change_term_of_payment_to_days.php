<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Term of payment (TOP) disimpan sebagai jumlah HARI, bukan teks bebas.
     * Contoh: 7, 14, atau 30 hari.
     *
     * Kolom sebelumnya varchar dan belum pernah terisi, jadi tidak ada
     * data teks yang perlu dikonversi.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unsignedSmallInteger('term_of_payment')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('term_of_payment', 64)->nullable()->change();
        });
    }
};
