<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Ajouter colonnes à plans_abonnement ──────────────────────────
        if (!Schema::hasColumn('plans_abonnement', 'slug')) {
            Schema::table('plans_abonnement', function (Blueprint $table) {
                $table->string('slug', 30)->nullable()->after('nom');
            });
        }
        if (!Schema::hasColumn('plans_abonnement', 'max_employes')) {
            Schema::table('plans_abonnement', function (Blueprint $table) {
                $table->integer('max_employes')->nullable()->after('prix')->comment('null = illimité');
                $table->integer('max_instituts')->nullable()->after('max_employes')->comment('null = illimité');
            });
        }

        // ── 2. Recréer abonnements avec nouveau schéma ─────────────────────
        // SQLite ne supporte pas ALTER COLUMN / ADD ENUM, on recrée la table

        // 2a. Renommer l'ancienne table (gérer l'état intermédiaire)
        if (Schema::hasTable('abonnements') && !Schema::hasTable('abonnements_old')) {
            Schema::rename('abonnements', 'abonnements_old');
        }

        // 2a-bis. Supprimer les index de l'ancienne table (SQLite les garde après rename)
        DB::statement('DROP INDEX IF EXISTS abonnements_institut_id_statut_index');
        DB::statement('DROP INDEX IF EXISTS abonnements_expire_le_index');

        // 2b. Créer la nouvelle table (seulement si elle n'existe pas)
        if (!Schema::hasTable('abonnements')) {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->comment('Propriétaire de l\'abonnement');
            $table->uuid('plan_id');
            $table->decimal('montant', 10, 0);
            $table->string('periode', 20)->default('mensuel')->comment('mensuel, annuel, triennal');
            $table->string('statut', 20)->default('en_attente')->comment('en_attente, actif, expire, annule, rejete');
            $table->date('debut_le')->nullable();
            $table->date('expire_le')->nullable();
            $table->string('reference_transfert')->nullable();
            $table->string('preuve_paiement')->nullable()->comment('Chemin du fichier uploadé');
            $table->text('notes_admin')->nullable();
            $table->uuid('valide_par')->nullable()->comment('User ID du super admin');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans_abonnement');
            $table->foreign('valide_par')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'statut']);
            $table->index('expire_le');
        });
        } // end if !Schema::hasTable('abonnements')

        // 2c. Migrer les données existantes
        if (Schema::hasTable('abonnements_old')) {
            $oldAbonnements = DB::table('abonnements_old')->get();
        foreach ($oldAbonnements as $old) {
            // Trouver le user admin de l'institut
            $adminUser = DB::table('users')
                ->where('institut_id', $old->institut_id)
                ->where('role', 'admin')
                ->first();

            if ($adminUser) {
                DB::table('abonnements')->insert([
                    'id' => $old->id,
                    'user_id' => $adminUser->id,
                    'plan_id' => $old->plan_id,
                    'montant' => $old->montant,
                    'periode' => 'mensuel',
                    'statut' => $old->statut,
                    'debut_le' => $old->debut_le,
                    'expire_le' => $old->expire_le,
                    'reference_transfert' => $old->reference_cinetpay,
                    'metadata' => $old->metadata,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]);
            }
        }

        // 2d. Supprimer l'ancienne table
            Schema::dropIfExists('abonnements_old');
        } // end if abonnements_old exists
    }

    public function down(): void
    {
        // Retirer colonnes de plans_abonnement
        Schema::table('plans_abonnement', function (Blueprint $table) {
            $table->dropColumn(['slug', 'max_employes', 'max_instituts']);
        });

        // Recréer l'ancienne table abonnements
        Schema::rename('abonnements', 'abonnements_new');

        Schema::create('abonnements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('plan_id');
            $table->decimal('montant', 10, 0);
            $table->string('reference_cinetpay')->nullable()->unique();
            $table->enum('statut', ['en_attente', 'actif', 'expire', 'annule'])->default('en_attente');
            $table->date('debut_le')->nullable();
            $table->date('expire_le')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans_abonnement');
            $table->index(['institut_id', 'statut']);
            $table->index('expire_le');
        });

        Schema::dropIfExists('abonnements_new');
    }
};
