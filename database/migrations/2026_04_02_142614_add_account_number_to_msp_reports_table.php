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
        Schema::table('msp_reports', function (Blueprint $table) {
            $table->string('numero_cuenta')->nullable()->after('email_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('msp_reports', function (Blueprint $table) {
            $table->dropColumn('numero_cuenta');
        });
    }
};
