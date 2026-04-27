<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'employe', 'commercial') DEFAULT 'admin'");
    }

    public function down(): void
    {
        // Retire les commerciaux avant de supprimer la valeur de l'enum
        DB::table('users')->where('role', 'commercial')->update(['role' => 'admin']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'employe') DEFAULT 'admin'");
    }
};
