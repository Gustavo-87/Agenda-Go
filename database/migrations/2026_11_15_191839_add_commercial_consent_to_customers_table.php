<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consentimiento comercial independiente del tratamiento de datos
     * necesario para reservar (Dec-12).
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('commercial_consent')->default(false);
            $table->timestamp('commercial_consent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['commercial_consent', 'commercial_consent_at']);
        });
    }
};
