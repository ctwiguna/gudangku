<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Melengkapi data supplier agar mudah dihubungi dan dibandingkan:
     * lama pengiriman (lead time) dan termin pembayaran (TOP).
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unsignedSmallInteger('lead_time_days')->nullable()->after('phone');
            $table->string('term_of_payment', 64)->nullable()->after('lead_time_days');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['lead_time_days', 'term_of_payment']);
        });
    }
};
