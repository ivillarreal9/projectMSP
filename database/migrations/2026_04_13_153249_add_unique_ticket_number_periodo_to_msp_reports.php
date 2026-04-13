<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('msp_reports', function (Blueprint $table) {
            $table->unique(['ticket_number', 'periodo'], 'unique_ticket_periodo');
        });
    }

    public function down(): void
    {
        Schema::table('msp_reports', function (Blueprint $table) {
            $table->dropUnique('unique_ticket_periodo');
        });
    }
};
