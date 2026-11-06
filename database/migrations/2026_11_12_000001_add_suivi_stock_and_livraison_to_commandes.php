<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            // Réservation du stock : le stock est décrémenté dès l'acceptation
            // de la commande, plus seulement à l'encaissement.
            $table->timestamp('stock_decremente_at')->nullable()->after('payee_at');

            // Livraison promise au client
            $table->date('date_livraison_prevue')->nullable()->after('client_adresse');
            $table->string('creneau_livraison', 40)->nullable()->after('date_livraison_prevue');

            // Lien de suivi / annulation côté client
            $table->string('token', 64)->nullable()->after('numero');
            $table->index('token');

            // Traçabilité de l'annulation
            $table->string('annulee_par', 20)->nullable()->after('annulee_at');
            $table->string('motif_annulation', 255)->nullable()->after('annulee_par');
        });

        // Les commandes déjà encaissées ont, de fait, leur stock décrémenté.
        DB::table('commandes')
            ->where('payee', true)
            ->whereNull('stock_decremente_at')
            ->update(['stock_decremente_at' => DB::raw('payee_at')]);
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropIndex(['token']);
            $table->dropColumn([
                'stock_decremente_at', 'date_livraison_prevue', 'creneau_livraison',
                'token', 'annulee_par', 'motif_annulation',
            ]);
        });
    }
};
