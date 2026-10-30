<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module crédits — paramétrage et suivi des relances.
 *
 * - `instituts`  : plafond d'encours par défaut + activation des relances + modèle de message
 * - `clients`    : plafond d'encours spécifique (prioritaire sur celui de l'établissement)
 * - `credit_relances` : journal des relances envoyées (anti-doublon par palier)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('instituts')) {
            Schema::table('instituts', function (Blueprint $table) {
                if (! Schema::hasColumn('instituts', 'credit_plafond_defaut')) {
                    $table->unsignedInteger('credit_plafond_defaut')->nullable()
                        ->after('actif')
                        ->comment("Plafond d'encours par défaut, en FCFA (null = illimité)");
                }
                if (! Schema::hasColumn('instituts', 'credit_relance_active')) {
                    $table->boolean('credit_relance_active')->default(true)
                        ->after('credit_plafond_defaut')
                        ->comment('Relances automatiques des échéances');
                }
                if (! Schema::hasColumn('instituts', 'credit_message_relance')) {
                    $table->text('credit_message_relance')->nullable()
                        ->after('credit_relance_active')
                        ->comment('Modèle de message de relance (WhatsApp / SMS)');
                }
            });
        }

        if (Schema::hasTable('clients') && ! Schema::hasColumn('clients', 'credit_plafond')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedInteger('credit_plafond')->nullable()
                    ->after('actif')
                    ->comment("Plafond d'encours propre au client (prioritaire)");
            });
        }

        if (! Schema::hasTable('credit_relances')) {
            Schema::create('credit_relances', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('credit_id');
                $table->uuid('institut_id');
                $table->string('canal', 20);   // email | whatsapp | inapp
                $table->string('palier', 10);  // j-3 | j0 | j+1 | j+7 | j+15 | j+30
                $table->timestamp('envoye_le')->nullable();
                $table->timestamps();

                $table->foreign('credit_id')->references('id')->on('credits')->cascadeOnDelete();
                $table->index(['institut_id', 'palier']);
                $table->unique(['credit_id', 'palier', 'canal']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_relances');

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'credit_plafond')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('credit_plafond');
            });
        }

        if (Schema::hasTable('instituts')) {
            Schema::table('instituts', function (Blueprint $table) {
                foreach (['credit_plafond_defaut', 'credit_relance_active', 'credit_message_relance'] as $col) {
                    if (Schema::hasColumn('instituts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
