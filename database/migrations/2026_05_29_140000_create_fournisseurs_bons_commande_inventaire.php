<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id')->index();
            $table->string('nom', 150);
            $table->string('telephone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('contact_principal', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('bons_commande', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id')->index();
            $table->uuid('fournisseur_id')->nullable()->index();
            $table->uuid('user_id')->nullable();
            $table->string('numero', 30)->unique();
            $table->date('date_commande');
            $table->date('date_livraison_prevue')->nullable();
            $table->string('statut', 20)->default('brouillon'); // brouillon, envoye, recu_partiel, recu, annule
            $table->integer('total_ht')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bon_commande_lignes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bon_commande_id')->index();
            $table->uuid('produit_id')->nullable();
            $table->string('libelle', 200);
            $table->integer('quantite_commandee');
            $table->integer('quantite_recue')->default(0);
            $table->integer('prix_unitaire');
            $table->integer('sous_total');
            $table->timestamps();
        });

        Schema::create('inventaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id')->index();
            $table->uuid('user_id')->nullable();
            $table->date('date_inventaire');
            $table->string('statut', 20)->default('en_cours'); // en_cours, valide, annule
            $table->integer('total_ecart_valeur')->default(0); // FCFA (positif = surplus, négatif = perte)
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventaire_lignes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventaire_id')->index();
            $table->uuid('produit_id');
            $table->integer('stock_theorique');
            $table->integer('stock_compte');
            $table->integer('ecart');           // compte - theorique
            $table->integer('valeur_ecart');    // ecart * cmp
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaire_lignes');
        Schema::dropIfExists('inventaires');
        Schema::dropIfExists('bon_commande_lignes');
        Schema::dropIfExists('bons_commande');
        Schema::dropIfExists('fournisseurs');
    }
};
