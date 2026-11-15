<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trazabilidad de correo: registrado (aceptado por la cola de
     * envío), enviado al proveedor (transporte SMTP aceptado) y
     * fallido. La entrega final no es verificable por SMTP.
     */
    public function up(): void
    {
        Schema::create('mail_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('notification')->nullable();
            $table->string('recipient');
            $table->string('status')->default('registered'); // registered | accepted | failed
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['recipient', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_deliveries');
    }
};
