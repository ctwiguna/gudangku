<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Karyawan dikelompokkan per outlet.
     *
     * Karyawan lama (dibuat sebelum kolom ini ada) ditugaskan ke outlet pertama
     * supaya tidak ada data yang menggantung tanpa outlet. Penghapusan outlet
     * memakai RESTRICT: outlet yang masih punya karyawan tidak bisa dihapus.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('outlet_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        $outletId = DB::table('outlets')->orderBy('id')->value('id');

        if ($outletId !== null) {
            DB::table('employees')->whereNull('outlet_id')->update(['outlet_id' => $outletId]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_id');
        });
    }
};
