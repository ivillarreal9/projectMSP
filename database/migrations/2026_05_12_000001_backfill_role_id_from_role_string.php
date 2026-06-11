<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Subconsulta correlacionada en vez de UPDATE...JOIN (solo MySQL)
        // para que también funcione en SQLite (tests)
        DB::statement('
            UPDATE users
            SET role_id = (SELECT id FROM roles WHERE roles.slug = users.role)
            WHERE role_id IS NULL
              AND role IS NOT NULL
              AND EXISTS (SELECT 1 FROM roles WHERE roles.slug = users.role)
        ');
    }

    public function down(): void
    {
        // Irreversible intencionalmente: no se puede deducir el role_id original
    }
};
