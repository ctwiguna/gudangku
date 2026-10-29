<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal update harga terakhir per barang. Diisi otomatis oleh
     * ProductController setiap kali harga satuan disimpan.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('price_updated_at')->nullable()->after('price_buy');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price_updated_at');
        });
    }
};
