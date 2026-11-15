<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuota de mensajería por negocio (RF-MEN-001). Los valores se
     * definen por autorización expresa del superadministrador; sin
     * definir permanecen en null (no se inventan cuotas).
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedInteger('messaging_included')->nullable();
            $table->unsignedInteger('messaging_overage_authorized')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['messaging_included', 'messaging_overage_authorized']);
        });
    }
};
