<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('msp_credentials', function (Blueprint $table) {
            $table->string('username')->after('id');
            $table->string('password')->after('username');
            $table->string('base_url')->default('https://api.mspmanager.com/odata')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('msp_credentials', function (Blueprint $table) {
            $table->dropColumn(['username', 'password', 'base_url']);
        });
    }
};