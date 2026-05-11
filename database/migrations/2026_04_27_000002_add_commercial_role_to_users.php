<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY COLUMN n'est pas supporté par SQLite (utilisé pour les tests)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'employe', 'commercial') DEFAULT 'admin'");
        }
        // Sur SQLite la colonne est VARCHAR, 'commercial' est déjà accepté sans ENUM
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'commercial')->update(['role' => 'admin']);
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'employe') DEFAULT 'admin'");
        }
    }
};
