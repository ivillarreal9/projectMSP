<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('msp_credentials');
    }

    public function down(): void
    {
        Schema::create('msp_credentials', function ($table) {
            $table->id();
            $table->string('username');
            $table->text('password');
            $table->string('base_url')->default('https://api.mspmanager.com/odata');
            $table->timestamps();
        });
    }
};