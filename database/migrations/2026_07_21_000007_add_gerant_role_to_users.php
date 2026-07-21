<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // MySQL/MariaDB : modifier l'ENUM
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'gerant', 'employe', 'commercial') DEFAULT 'admin'");
        }
        // SQLite/PostgreSQL : la colonne est en VARCHAR, pas besoin de migration
        // La validation applicative (modèle + middleware) suffit
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'employe', 'commercial') DEFAULT 'admin'");
        }
    }
};
