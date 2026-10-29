<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data karyawan: nama, gaji, dan satuan gaji.
     *
     * Catatan: tabel ini menyimpan data acuan, bukan transaksi pengeluaran.
     * Pengeluaran gaji per outlet tetap dicatat di tabel expenses kategori gaji.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('salary', 14, 2);
            $table->enum('salary_unit', ['harian', 'mingguan', 'bulanan']);
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
