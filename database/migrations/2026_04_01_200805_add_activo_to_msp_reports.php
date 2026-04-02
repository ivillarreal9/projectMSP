<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('msp_reports', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('periodo')->index();
        });

        Schema::table('msp_upload_batches', function (Blueprint $table) {
            $table->string('fuente')->default('manual')->after('clientes_unicos'); // 'manual' | 'sharepoint'
        });
    }

    public function down(): void
    {
        Schema::table('msp_reports', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
        Schema::table('msp_upload_batches', function (Blueprint $table) {
            $table->dropColumn('fuente');
        });
    }
};
