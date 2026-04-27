<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Profils commerciaux ────────────────────────────────────────────────
        Schema::create('commercial_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('code', 10)->unique()->comment('Code de parrainage unique ex: BA4521');
            $table->string('telephone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // ── Parrainages commerciaux (lien commercial ↔ propriétaire d'institut) ─
        Schema::create('commercial_parrainages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('commercial_id')->comment('→ commercial_profiles.id');
            $table->uuid('proprietaire_id')->comment('→ users.id, le propriétaire parrainé');
            $table->date('expire_le')->comment('Date de fin de la période de commission');
            $table->timestamps();

            $table->foreign('commercial_id')->references('id')->on('commercial_profiles')->cascadeOnDelete();
            $table->foreign('proprietaire_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('proprietaire_id'); // un propriétaire = un seul commercial
        });

        // ── Commissions ────────────────────────────────────────────────────────
        Schema::create('commercial_commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('commercial_id')->comment('→ commercial_profiles.id');
            $table->uuid('parrainage_id');
            $table->uuid('abonnement_id')->unique()->comment('Une commission par abonnement validé');
            $table->decimal('montant_base', 10, 0)->comment('Montant de l\'abonnement');
            $table->unsignedTinyInteger('taux')->comment('Taux en % appliqué');
            $table->decimal('montant', 10, 0)->comment('montant_base * taux / 100');
            $table->string('statut', 20)->default('en_attente')->comment('en_attente, payee');
            $table->timestamp('payee_le')->nullable();
            $table->text('notes_paiement')->nullable();
            $table->timestamps();

            $table->foreign('commercial_id')->references('id')->on('commercial_profiles')->cascadeOnDelete();
            $table->foreign('parrainage_id')->references('id')->on('commercial_parrainages')->cascadeOnDelete();
            $table->foreign('abonnement_id')->references('id')->on('abonnements')->cascadeOnDelete();
            $table->index(['commercial_id', 'statut']);
        });

        // ── Paramètres commerciaux (taux et durée, table single-row) ──────────
        Schema::create('commercial_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('taux')->default(20)->comment('% de commission');
            $table->unsignedTinyInteger('duree_mois')->default(6)->comment('Durée de la période de commission en mois');
            $table->timestamps();
        });

        // Insérer la config par défaut
        DB::table('commercial_config')->insert(['taux' => 20, 'duree_mois' => 6]);
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_commissions');
        Schema::dropIfExists('commercial_parrainages');
        Schema::dropIfExists('commercial_profiles');
        Schema::dropIfExists('commercial_config');
    }
};
