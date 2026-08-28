<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('code_verification_email', 4)->nullable()->after('email_verified_at');
            $table->timestamp('code_verification_expire_le')->nullable()->after('code_verification_email');
        });

        // Les comptes existants sont considérés vérifiés (ils utilisent déjà l'appli)
        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['code_verification_email', 'code_verification_expire_le']);
        });
    }
};
