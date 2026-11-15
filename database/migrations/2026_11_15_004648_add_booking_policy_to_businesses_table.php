<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedInteger('booking_min_notice_minutes')->default(60);
            $table->unsignedInteger('booking_max_advance_days')->default(30);
            $table->boolean('booking_auto_confirm')->default(true);
            $table->unsignedInteger('booking_pending_ttl_minutes')->default(30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'booking_min_notice_minutes',
                'booking_max_advance_days',
                'booking_auto_confirm',
                'booking_pending_ttl_minutes',
            ]);
        });
    }
};
