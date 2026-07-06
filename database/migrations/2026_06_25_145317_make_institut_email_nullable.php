<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: rendre email nullable
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE instituts MODIFY COLUMN email VARCHAR(255) NULL');
        }
        // SQLite: déjà nullable par défaut
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE instituts SET email = CONCAT('no-email-', id, '@maelya.ci') WHERE email IS NULL");
            DB::statement('ALTER TABLE instituts MODIFY COLUMN email VARCHAR(255) NOT NULL');
        }
    }
};
