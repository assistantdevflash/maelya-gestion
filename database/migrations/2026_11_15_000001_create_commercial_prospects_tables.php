<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prospection commerciale : suivi des prospects et journal des interactions.
 *
 * Un commercial travaille sur deux populations :
 *   - les prospects qu'il démarche (aucun compte créé chez nous) ;
 *   - les filleuls déjà parrainés, dont l'abonnement arrive à échéance.
 * Ces deux tables couvrent le suivi du premier cas et l'historique des
 * contacts, pour que les relances ne reposent plus sur la mémoire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_prospects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('commercial_id')->comment('→ commercial_profiles.id');

            // Établissement démarqué
            $table->string('nom_etablissement', 150);
            $table->string('type_activite', 40)->nullable();
            $table->string('ville', 80)->nullable();
            $table->string('quartier', 80)->nullable();

            // Interlocuteur
            $table->string('contact_nom', 120)->nullable();
            $table->string('contact_telephone', 30)->nullable();
            $table->string('contact_email', 150)->nullable();

            $table->string('statut', 20)->default('a_contacter')
                ->comment('a_contacter, contacte, interesse, essai, converti, perdu');
            $table->string('source', 30)->default('porte_a_porte')
                ->comment('porte_a_porte, recommandation, reseaux, appel, autre');
            $table->string('priorite', 10)->default('normale')
                ->comment('basse, normale, haute');

            $table->date('prochaine_relance_le')->nullable();
            $table->date('derniere_interaction_le')->nullable();

            $table->text('notes')->nullable();

            // Renseigné quand le prospect finit par créer son établissement
            $table->uuid('proprietaire_id')->nullable()->comment('→ users.id après conversion');
            $table->timestamp('converti_le')->nullable();

            $table->timestamps();

            $table->foreign('commercial_id')->references('id')->on('commercial_profiles')->cascadeOnDelete();
            $table->index(['commercial_id', 'statut']);
            $table->index(['commercial_id', 'prochaine_relance_le']);
            $table->index('proprietaire_id');
        });

        Schema::create('commercial_activites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prospect_id');
            $table->uuid('commercial_id');
            $table->string('type', 20)->default('note')
                ->comment('appel, visite, whatsapp, email, relance, note');
            $table->text('contenu');
            $table->date('fait_le');
            $table->timestamps();

            $table->foreign('prospect_id')->references('id')->on('commercial_prospects')->cascadeOnDelete();
            $table->foreign('commercial_id')->references('id')->on('commercial_profiles')->cascadeOnDelete();
            $table->index(['prospect_id', 'fait_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_activites');
        Schema::dropIfExists('commercial_prospects');
    }
};
