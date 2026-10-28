<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Kategori tidak boleh dihapus selama masih dipakai produk.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            // Supplier boleh dihapus, produk menjadi tanpa supplier.
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku', 64)->unique();
            $table->string('unit', 16)->default('pcs');
            $table->unsignedInteger('min_stock')->default(0);
            // Harga acuan global per produk, hanya boleh diubah owner.
            $table->decimal('price_buy', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
