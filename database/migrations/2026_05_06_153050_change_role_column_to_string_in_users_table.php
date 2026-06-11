<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Convierte la columna `role` de ENUM a VARCHAR(50) para soportar
     * roles dinámicos. En MySQL se usa SQL crudo (MODIFY); en SQLite
     * (tests) se recrea vía Schema porque no soporta MODIFY y además
     * hay que eliminar el CHECK constraint que emula al ENUM.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY role VARCHAR(50) NULL DEFAULT NULL');
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','editor','user') NULL DEFAULT NULL");
        }
        // SQLite: la columna queda como string — no hay ENUM que restaurar
    }
};
