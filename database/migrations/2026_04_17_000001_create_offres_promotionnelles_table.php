<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offres_promotionnelles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom', 100);
            $table->text('description')->nullable();
            $table->enum('type_reduction', ['pourcentage', 'montant_fixe']);
            $table->unsignedInteger('valeur_reduction');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('actif')->default(true);
            $table->json('plans_concernes')->nullable(); // null = tous les plans
            $table->json('periodes_concernees')->nullable(); // null = toutes les périodes
            $table->string('badge_texte', 80)->default('Offre spéciale');
            $table->string('badge_couleur', 20)->default('amber');
            $table->unsignedSmallInteger('priorite')->default(0);
            $table->timestamps();
        });

        // Migrer les offres de lancement existantes vers la nouvelle table
        $plans = DB::table('plans_abonnement')
            ->whereNotNull('prix_lancement')
            ->whereNotNull('fin_offre_lancement')
            ->get();

        foreach ($plans as $plan) {
            if ($plan->prix > 0 && $plan->prix_lancement < $plan->prix) {
                $reduction = (int) round((1 - $plan->prix_lancement / $plan->prix) * 100);
                DB::table('offres_promotionnelles')->insert([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'nom' => 'Offre de lancement',
                    'description' => "Offre de lancement migrée automatiquement pour le plan {$plan->nom}",
                    'type_reduction' => 'pourcentage',
                    'valeur_reduction' => $reduction,
                    'date_debut' => now()->format('Y-m-d'),
                    'date_fin' => $plan->fin_offre_lancement,
                    'actif' => true,
                    'plans_concernes' => json_encode([$plan->id]),
                    'periodes_concernees' => null,
                    'badge_texte' => '🔥 Offre de lancement',
                    'badge_couleur' => 'amber',
                    'priorite' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('offres_promotionnelles');
    }
};
