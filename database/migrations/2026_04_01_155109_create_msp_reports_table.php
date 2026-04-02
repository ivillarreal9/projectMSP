<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('msp_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('ticket_number')->nullable();
            $table->string('customer_name')->nullable()->index();
            $table->string('location_name')->nullable();
            $table->text('ticket_title')->nullable();
            $table->string('ticket_type')->nullable();
            $table->datetime('fecha_creacion')->nullable();
            $table->datetime('fecha_cierre')->nullable();
            $table->decimal('tiempo_vida_ticket', 10, 4)->nullable(); // en días
            $table->string('semana')->nullable();
            $table->string('mes_cierre')->nullable();
            $table->string('tipo_ticket')->nullable(); // Incidente / Solicitud
            $table->string('clasificacion_eventos')->nullable();
            $table->string('causa_dano')->nullable();
            $table->text('solucion')->nullable();
            $table->text('detalle')->nullable();
            $table->string('tipo_cliente')->nullable();
            $table->string('ubicacion_hopsa')->nullable();
            $table->text('solucion_definitiva')->nullable();
            $table->string('tipo_reporte')->nullable(); // Alarma / Reportado
            $table->string('email_cliente')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('periodo')->nullable(); // ej: "Febrero 2026"
            $table->timestamps();
        });

        Schema::create('msp_upload_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('periodo')->nullable();
            $table->integer('total_registros')->default(0);
            $table->integer('clientes_unicos')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('msp_reports');
        Schema::dropIfExists('msp_upload_batches');
    }
};
