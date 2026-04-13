<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('msp_clients', function (Blueprint $table) {
            $table->string('email_cliente')->nullable()->after('customer_name');
            $table->string('numero_cuenta')->nullable()->after('email_cliente');
            $table->string('logo_path')->nullable()->after('numero_cuenta');
        });
    }

    public function down(): void
    {
        Schema::table('msp_clients', function (Blueprint $table) {
            $table->dropColumn(['email_cliente', 'numero_cuenta', 'logo_path']);
        });
    }
};
