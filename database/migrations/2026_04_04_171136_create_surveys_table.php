<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_type_id')
                  ->constrained('survey_types')
                  ->cascadeOnDelete();
            $table->string('fecha')->nullable();
            $table->string('numero_whatsapp')->nullable();
            $table->string('nombre')->nullable();
            $table->json('data')->nullable(); // {"satisfaccion":"5","recomendacion":"si"}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};