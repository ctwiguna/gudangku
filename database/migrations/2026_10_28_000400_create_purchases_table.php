<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            // Riwayat pembelian tidak boleh kehilangan relasi.
            $table->id();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('invoice_no')->nullable();
            // Tanggal barang diterima di outlet.
            $table->date('received_at');
            // Diisi owner setelah semua item berharga. Kosong berarti menunggu harga.
            $table->date('priced_at')->nullable();
            // Diisi owner saat pembelian ditandai lunas.
            $table->date('paid_at')->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'received_at']);
            $table->index(['priced_at', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
