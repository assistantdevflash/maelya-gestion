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
        // On supprime les FK de l'ancienne table avant de la dropper
        // pour éviter les conflits de noms de contraintes sur MariaDB/MySQL
        if (Schema::hasTable('abonnements')) {
            // Supprimer les FK de l'ancienne table
            try { Schema::table('abonnements', function (Blueprint $table) { $table->dropForeign(['plan_id']); }); } catch (\Throwable $e) {}
            try { Schema::table('abonnements', function (Blueprint $table) { $table->dropForeign(['user_id']); }); } catch (\Throwable $e) {}
            try { Schema::table('abonnements', function (Blueprint $table) { $table->dropForeign(['institut_id']); }); } catch (\Throwable $e) {}
            Schema::drop('abonnements');
        }

        // 2b. Créer la nouvelle table
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
