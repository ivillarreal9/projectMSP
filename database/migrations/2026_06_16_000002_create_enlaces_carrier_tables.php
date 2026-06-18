<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reemplaza la tabla simple enlaces por el modelo de datos de circuitos carrier
        Schema::dropIfExists('enlaces');

        Schema::create('enlaces_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('sharepoint_item_id')->nullable();
            $table->string('referencia_tecnica')->nullable();
            $table->string('source_modified_at')->nullable();  // lastModifiedDateTime del Excel en SharePoint
            $table->integer('total_registros')->default(0);
            $table->timestamps();
        });

        Schema::create('enlaces_carrier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained('enlaces_batches')->nullOnDelete();
            $table->string('cliente');
            $table->string('ubicacion')->nullable();
            $table->string('pais')->nullable();
            $table->string('carrier')->nullable();
            $table->enum('estado', ['activo', 'incidente', 'mantenimiento'])->default('activo');
            $table->string('so_ref')->nullable();
            $table->string('id_circuito')->nullable()->unique();  // clave de upsert (evita duplicados)
            $table->unsignedInteger('capacidad')->nullable();  // MB
            $table->string('gateway')->nullable();
            $table->string('ip_disponible')->nullable();
            $table->string('mascara')->nullable();
            $table->string('dns')->nullable();              // DNS primario
            $table->string('dns_secundario')->nullable();
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_telefono')->nullable();
            $table->string('contacto_email')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enlaces_carrier');
        Schema::dropIfExists('enlaces_batches');
    }
};
