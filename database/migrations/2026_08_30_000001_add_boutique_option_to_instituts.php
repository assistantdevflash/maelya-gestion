<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Option boutique en ligne par établissement (facturation par boutique).
     *
     * L'option devient un attribut de l'établissement (et non de l'abonnement du
     * compte), ce qui permet à un compte multi-établissements d'activer/payer
     * la boutique pour CHAQUE établissement séparément (3 900 F/mois chacun).
     *
     * - boutique_option_active   : l'établissement a payé l'option
     * - boutique_option_expire_le: fin de validité (alignée sur l'abonnement du propriétaire)
     * - boutique_option_prix     : prix mensuel payé (défaut 3900)
     */
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->boolean('boutique_option_active')->default(false)->after('boutique_conditions');
            $table->date('boutique_option_expire_le')->nullable()->after('boutique_option_active');
            $table->integer('boutique_option_prix')->default(3900)->after('boutique_option_expire_le');
        });

        // ── Backfill : transférer l'option boutique de l'abonnement vers les établissements ──
        // Avant ce changement, l'option était stockée dans abonnements.metadata['boutique']
        // (une seule option pour tout le compte). On l'applique à TOUS les établissements
        // du propriétaire (principal + secondaires), avec expiration alignée sur l'abonnement.
        $abonnements = \Illuminate\Support\Facades\DB::table('abonnements')
            ->where('statut', 'actif')
            ->get(['id', 'user_id', 'expire_le', 'metadata']);

        foreach ($abonnements as $abo) {
            $meta = is_string($abo->metadata) ? json_decode($abo->metadata, true) : ($abo->metadata ?? []);
            if (!($meta['boutique'] ?? false)) {
                continue;
            }

            // Tous les instituts du propriétaire (proprietaire_id) + son établissement principal
            $ids = \Illuminate\Support\Facades\DB::table('instituts')
                ->where('proprietaire_id', $abo->user_id)
                ->orWhere('id', function ($q) use ($abo) {
                    $q->select('institut_id')->from('users')->where('id', $abo->user_id)->limit(1);
                })
                ->pluck('id')
                ->all();

            if (!$ids) {
                continue;
            }

            \Illuminate\Support\Facades\DB::table('instituts')
                ->whereIn('id', $ids)
                ->update([
                    'boutique_option_active' => true,
                    'boutique_option_expire_le' => $abo->expire_le,
                    'boutique_option_prix' => $meta['boutique_prix'] ?? 3900,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn(['boutique_option_active', 'boutique_option_expire_le', 'boutique_option_prix']);
        });
    }
};
