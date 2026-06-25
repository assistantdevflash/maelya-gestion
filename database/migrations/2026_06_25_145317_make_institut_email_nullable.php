<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: rendre email nullable (l'index unique reste, NULL ne duplique pas)
        DB::statement('ALTER TABLE instituts MODIFY COLUMN email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE instituts SET email = CONCAT('no-email-', id, '@maelya.ci') WHERE email IS NULL");
        DB::statement('ALTER TABLE instituts MODIFY COLUMN email VARCHAR(255) NOT NULL');
    }
};
