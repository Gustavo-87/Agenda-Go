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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->boolean('data_consent')->default(false);
            $table->timestamp('data_consent_at')->nullable();
            $table->unsignedInteger('absences')->default(0);
            $table->timestamps();
            $table->unique(['business_id', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
