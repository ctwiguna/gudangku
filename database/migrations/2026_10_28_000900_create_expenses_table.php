<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // Kategori tetap, tidak bisa ditambah user.
            $table->enum('category', ['listrik', 'air', 'ipal', 'gaji', 'sewa', 'lain']);
            $table->date('expense_date');
            // Wajib diisi untuk kategori gaji, boleh kosong untuk kategori lain.
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('amount', 14, 2);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'expense_date']);
            $table->index(['category', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
