<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            // Ledger stok. Saldo adalah jumlah kolom quantity, bukan kolom di produk.
            $table->id();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // in: penerimaan, out: pembatalan penerimaan, adjustment: opname dan koreksi.
            $table->enum('type', ['in', 'out', 'adjustment']);
            // Bertanda: positif barang masuk, negatif barang keluar.
            $table->integer('quantity');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->dateTime('moved_at');
            $table->timestamps();

            $table->index(['outlet_id', 'product_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['outlet_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
