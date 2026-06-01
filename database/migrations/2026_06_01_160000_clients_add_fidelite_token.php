<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('fidelite_token', 64)->nullable()->unique()->after('points_fidelite');
        });

        // Backfill des tokens pour les clients existants
        \App\Models\Client::withoutGlobalScopes()
            ->whereNull('fidelite_token')
            ->cursor()
            ->each(function ($c) {
                $c->fidelite_token = \Illuminate\Support\Str::random(40);
                $c->saveQuietly();
            });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('fidelite_token');
        });
    }
};
