<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite ne supporte pas ALTER COLUMN, on supprime l'ancienne contrainte CHECK
        // et on en crée une nouvelle incluant 'carte'
        DB::statement("
            CREATE TABLE ventes_tmp AS SELECT * FROM ventes
        ");
        DB::statement("DROP TABLE ventes");
        DB::statement("
            CREATE TABLE ventes (
                id VARCHAR(36) PRIMARY KEY NOT NULL,
                institut_id VARCHAR(36) NOT NULL,
                client_id VARCHAR(36),
                user_id VARCHAR(36) NOT NULL,
                numero VARCHAR(255) NOT NULL UNIQUE,
                total NUMERIC(10,0) NOT NULL DEFAULT 0,
                mode_paiement VARCHAR(255) NOT NULL DEFAULT 'cash' CHECK (mode_paiement IN ('cash', 'mobile_money', 'carte', 'mixte')),
                reference_paiement VARCHAR(255),
                montant_cash NUMERIC(10,0) NOT NULL DEFAULT 0,
                montant_mobile NUMERIC(10,0) NOT NULL DEFAULT 0,
                statut VARCHAR(255) NOT NULL DEFAULT 'validee' CHECK (statut IN ('validee', 'annulee')),
                notes TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                FOREIGN KEY (institut_id) REFERENCES instituts(id) ON DELETE CASCADE,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
            )
        ");
        DB::statement("CREATE INDEX ventes_institut_id_created_at_index ON ventes (institut_id, created_at)");
        DB::statement("CREATE INDEX ventes_institut_id_statut_index ON ventes (institut_id, statut)");
        DB::statement("INSERT INTO ventes SELECT * FROM ventes_tmp");
        DB::statement("DROP TABLE ventes_tmp");
    }

    public function down(): void
    {
        DB::statement("
            CREATE TABLE ventes_tmp AS SELECT * FROM ventes
        ");
        DB::statement("DROP TABLE ventes");
        DB::statement("
            CREATE TABLE ventes (
                id VARCHAR(36) PRIMARY KEY NOT NULL,
                institut_id VARCHAR(36) NOT NULL,
                client_id VARCHAR(36),
                user_id VARCHAR(36) NOT NULL,
                numero VARCHAR(255) NOT NULL UNIQUE,
                total NUMERIC(10,0) NOT NULL DEFAULT 0,
                mode_paiement VARCHAR(255) NOT NULL DEFAULT 'cash' CHECK (mode_paiement IN ('cash', 'mobile_money', 'mixte')),
                reference_paiement VARCHAR(255),
                montant_cash NUMERIC(10,0) NOT NULL DEFAULT 0,
                montant_mobile NUMERIC(10,0) NOT NULL DEFAULT 0,
                statut VARCHAR(255) NOT NULL DEFAULT 'validee' CHECK (statut IN ('validee', 'annulee')),
                notes TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                FOREIGN KEY (institut_id) REFERENCES instituts(id) ON DELETE CASCADE,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
            )
        ");
        DB::statement("CREATE INDEX ventes_institut_id_created_at_index ON ventes (institut_id, created_at)");
        DB::statement("CREATE INDEX ventes_institut_id_statut_index ON ventes (institut_id, statut)");
        DB::statement("INSERT INTO ventes SELECT * FROM ventes_tmp");
        DB::statement("DROP TABLE ventes_tmp");
    }
};
