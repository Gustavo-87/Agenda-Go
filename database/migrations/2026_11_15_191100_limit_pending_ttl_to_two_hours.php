<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-RES-003: el vencimiento máximo de solicitudes pendientes es
     * de 2 horas (120 minutos).
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedInteger('booking_pending_ttl_minutes')->default(120)->change();
        });

        DB::table('businesses')
            ->where('booking_pending_ttl_minutes', '>', 120)
            ->update(['booking_pending_ttl_minutes' => 120]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedInteger('booking_pending_ttl_minutes')->default(30)->change();
        });
    }
};
