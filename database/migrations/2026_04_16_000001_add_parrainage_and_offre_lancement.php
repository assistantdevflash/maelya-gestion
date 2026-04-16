<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // ── Parrainage : champs sur users ──────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->string('code_parrainage', 10)->nullable()->unique()->after('actif');
            $table->uuid('parraine_par')->nullable()->after('code_parrainage');
            $table->foreign('parraine_par')->references('id')->on('users')->nullOnDelete();
        });

        // Générer un code unique pour chaque utilisateur existant
        $users = \App\Models\User::whereNull('code_parrainage')->get();
        foreach ($users as $user) {
            $user->forceFill(['code_parrainage' => strtoupper(Str::random(8))])->save();
        }

        // ── Offre de lancement : prix promotionnel sur les plans ───────────────
        Schema::table('plans_abonnement', function (Blueprint $table) {
            $table->integer('prix_lancement')->nullable()->after('prix');
            $table->date('fin_offre_lancement')->nullable()->after('prix_lancement');
        });

        // ── Table de suivi des parrainages ─────────────────────────────────────
        Schema::create('parrainages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parrain_id');
            $table->uuid('filleul_id');
            $table->integer('jours_offerts_parrain')->default(0);
            $table->integer('jours_offerts_filleul')->default(0);
            $table->string('statut', 20)->default('en_attente'); // en_attente, valide
            $table->timestamps();

            $table->foreign('parrain_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('filleul_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['parrain_id', 'filleul_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parrainages');

        Schema::table('plans_abonnement', function (Blueprint $table) {
            $table->dropColumn(['prix_lancement', 'fin_offre_lancement']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parraine_par']);
            $table->dropColumn(['code_parrainage', 'parraine_par']);
        });
    }
};
