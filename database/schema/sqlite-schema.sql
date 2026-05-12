CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" varchar,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "categories_prestations"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "nom" varchar not null,
  "ordre" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "categories_produits"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "nom" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "clients"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "prenom" varchar not null,
  "nom" varchar not null,
  "telephone" varchar not null,
  "email" varchar,
  "notes" text,
  "actif" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "points_fidelite" integer not null default '0',
  "date_naissance" varchar,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  primary key("id")
);
CREATE INDEX "clients_institut_id_actif_index" on "clients"(
  "institut_id",
  "actif"
);
CREATE TABLE IF NOT EXISTS "plans_abonnement"(
  "id" varchar not null,
  "nom" varchar not null,
  "duree_type" varchar check("duree_type" in('essai', 'mensuel', 'trimestriel', 'annuel')) not null,
  "duree_jours" integer not null,
  "prix" numeric not null,
  "economie_pct" integer not null default '0',
  "description" text,
  "actif" tinyint(1) not null default '1',
  "mis_en_avant" tinyint(1) not null default '0',
  "ordre" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "slug" varchar,
  "max_employes" integer,
  "max_instituts" integer,
  "prix_lancement" integer,
  "fin_offre_lancement" date,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "prestations"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "categorie_id" varchar not null,
  "nom" varchar not null,
  "prix" numeric not null,
  "duree" integer,
  "description" text,
  "actif" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  foreign key("categorie_id") references "categories_prestations"("id"),
  primary key("id")
);
CREATE INDEX "prestations_institut_id_actif_index" on "prestations"(
  "institut_id",
  "actif"
);
CREATE TABLE IF NOT EXISTS "produits"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "categorie_id" varchar,
  "nom" varchar not null,
  "reference" varchar,
  "prix_achat" numeric not null default '0',
  "prix_vente" numeric not null,
  "stock" integer not null default '0',
  "seuil_alerte" integer not null default '5',
  "unite" varchar not null default 'pièce',
  "description" text,
  "actif" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  foreign key("categorie_id") references "categories_produits"("id") on delete set null,
  primary key("id")
);
CREATE INDEX "produits_institut_id_actif_index" on "produits"(
  "institut_id",
  "actif"
);
CREATE TABLE IF NOT EXISTS "ventes"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "client_id" varchar,
  "user_id" varchar not null,
  "numero" varchar not null,
  "total" numeric not null,
  "mode_paiement" varchar check("mode_paiement" in('cash', 'mobile_money', 'mixte')) not null default 'cash',
  "reference_paiement" varchar,
  "montant_cash" numeric not null default '0',
  "montant_mobile" numeric not null default '0',
  "statut" varchar check("statut" in('validee', 'annulee')) not null default 'validee',
  "notes" text,
  "ip_address" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "remise" integer not null default '0',
  "code_reduction_id" varchar,
  "montant_carte" numeric not null default '0',
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  foreign key("client_id") references "clients"("id") on delete set null,
  primary key("id")
);
CREATE INDEX "ventes_institut_id_created_at_index" on "ventes"(
  "institut_id",
  "created_at"
);
CREATE INDEX "ventes_institut_id_statut_index" on "ventes"(
  "institut_id",
  "statut"
);
CREATE UNIQUE INDEX "ventes_numero_unique" on "ventes"("numero");
CREATE TABLE IF NOT EXISTS "depenses"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "user_id" varchar,
  "description" varchar not null,
  "categorie" varchar check("categorie" in('loyer', 'salaires', 'fournitures', 'produits', 'equipement', 'marketing', 'autres')) not null default 'autres',
  "montant" numeric not null,
  "date" date not null,
  "justificatif" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  primary key("id")
);
CREATE INDEX "depenses_institut_id_date_index" on "depenses"(
  "institut_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "messages_contact"(
  "id" varchar not null,
  "nom" varchar not null,
  "email" varchar not null,
  "telephone" varchar,
  "message" text not null,
  "lu" tinyint(1) not null default '0',
  "honeypot" varchar,
  "ip_address" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "mouvements_stock"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "produit_id" varchar not null,
  "user_id" varchar,
  "vente_id" varchar,
  "type" varchar check("type" in('entree', 'sortie_vente', 'correction', 'annulation_vente')) not null,
  "quantite" integer not null,
  "stock_avant" integer not null,
  "stock_apres" integer not null,
  "note" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  foreign key("produit_id") references "produits"("id") on delete cascade,
  primary key("id")
);
CREATE INDEX "mouvements_stock_institut_id_produit_id_index" on "mouvements_stock"(
  "institut_id",
  "produit_id"
);
CREATE TABLE IF NOT EXISTS "vente_items"(
  "id" varchar not null,
  "vente_id" varchar not null,
  "type" varchar check("type" in('prestation', 'produit')) not null,
  "item_id" varchar not null,
  "nom_snapshot" varchar not null,
  "prix_snapshot" numeric not null,
  "quantite" integer not null default '1',
  "sous_total" numeric not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("vente_id") references "ventes"("id") on delete cascade,
  primary key("id")
);
CREATE INDEX "vente_items_vente_id_index" on "vente_items"("vente_id");
CREATE TABLE IF NOT EXISTS "abonnements"(
  "id" varchar not null,
  "user_id" varchar not null,
  "plan_id" varchar not null,
  "montant" numeric not null,
  "periode" varchar not null default 'mensuel',
  "statut" varchar not null default 'en_attente',
  "debut_le" date,
  "expire_le" date,
  "reference_transfert" varchar,
  "preuve_paiement" varchar,
  "notes_admin" text,
  "valide_par" varchar,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("plan_id") references "plans_abonnement"("id"),
  foreign key("valide_par") references "users"("id") on delete set null,
  primary key("id")
);
CREATE INDEX "abonnements_user_id_statut_index" on "abonnements"(
  "user_id",
  "statut"
);
CREATE INDEX "abonnements_expire_le_index" on "abonnements"("expire_le");
CREATE TABLE IF NOT EXISTS "codes_reduction"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "code" varchar not null,
  "description" varchar,
  "type" varchar not null default('pourcentage'),
  "valeur" integer not null default('0'),
  "montant_minimum" integer,
  "date_debut" date,
  "date_fin" date,
  "limite_utilisation" integer,
  "nb_utilisations" integer not null default('0'),
  "actif" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "client_id" varchar,
  foreign key("institut_id") references instituts("id") on delete cascade on update no action,
  foreign key("client_id") references "clients"("id") on delete set null,
  primary key("id")
);
CREATE UNIQUE INDEX "codes_reduction_institut_id_code_unique" on "codes_reduction"(
  "institut_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "instituts"(
  "id" varchar not null,
  "nom" varchar not null,
  "slug" varchar not null,
  "email" varchar not null,
  "telephone" varchar not null,
  "ville" varchar not null,
  "type" varchar not null,
  "logo" varchar,
  "actif" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "proprietaire_id" varchar,
  foreign key("proprietaire_id") references "users"("id") on delete set null,
  primary key("id")
);
CREATE UNIQUE INDEX "instituts_email_unique" on "instituts"("email");
CREATE UNIQUE INDEX "instituts_slug_unique" on "instituts"("slug");
CREATE TABLE IF NOT EXISTS "users"(
  "id" varchar not null,
  "name" varchar,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "institut_id" varchar,
  "prenom" varchar not null,
  "nom_famille" varchar not null,
  "telephone" varchar,
  "role" varchar not null default('admin'),
  "avatar" varchar,
  "actif" tinyint(1) not null default('1'),
  "code_parrainage" varchar,
  "parraine_par" varchar,
  foreign key("institut_id") references instituts("id") on delete cascade on update no action,
  foreign key("parraine_par") references "users"("id") on delete set null,
  primary key("id")
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE UNIQUE INDEX "users_code_parrainage_unique" on "users"(
  "code_parrainage"
);
CREATE TABLE IF NOT EXISTS "parrainages"(
  "id" varchar not null,
  "parrain_id" varchar not null,
  "filleul_id" varchar not null,
  "jours_offerts_parrain" integer not null default '0',
  "jours_offerts_filleul" integer not null default '0',
  "statut" varchar not null default 'en_attente',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("parrain_id") references "users"("id") on delete cascade,
  foreign key("filleul_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE UNIQUE INDEX "parrainages_parrain_id_filleul_id_unique" on "parrainages"(
  "parrain_id",
  "filleul_id"
);
CREATE TABLE IF NOT EXISTS "programme_fidelite"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "actif" tinyint(1) not null default '0',
  "tranche_fcfa" integer not null default '1000',
  "points_par_tranche" integer not null default '1',
  "seuil_recompense" integer not null default '100',
  "type_recompense" varchar not null default 'pourcentage',
  "valeur_recompense" integer not null default '10',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  primary key("id")
);
CREATE UNIQUE INDEX "programme_fidelite_institut_id_unique" on "programme_fidelite"(
  "institut_id"
);
CREATE TABLE IF NOT EXISTS "historique_points"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "client_id" varchar not null,
  "vente_id" varchar,
  "points" integer not null,
  "type" varchar not null,
  "description" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  foreign key("client_id") references "clients"("id") on delete cascade,
  primary key("id")
);
CREATE INDEX "historique_points_institut_id_client_id_index" on "historique_points"(
  "institut_id",
  "client_id"
);
CREATE INDEX "historique_points_created_at_index" on "historique_points"(
  "created_at"
);
CREATE TABLE IF NOT EXISTS "offres_promotionnelles"(
  "id" varchar not null,
  "nom" varchar not null,
  "description" text,
  "type_reduction" varchar check("type_reduction" in('pourcentage', 'montant_fixe')) not null,
  "valeur_reduction" integer not null,
  "date_debut" date not null,
  "date_fin" date not null,
  "actif" tinyint(1) not null default '1',
  "plans_concernes" text,
  "periodes_concernees" text,
  "badge_texte" varchar not null default 'Offre spéciale',
  "badge_couleur" varchar not null default 'amber',
  "priorite" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "notifier_jusqu_au" date,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "commercial_profiles"(
  "id" varchar not null,
  "user_id" varchar not null,
  "code" varchar not null,
  "telephone" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE UNIQUE INDEX "commercial_profiles_user_id_unique" on "commercial_profiles"(
  "user_id"
);
CREATE UNIQUE INDEX "commercial_profiles_code_unique" on "commercial_profiles"(
  "code"
);
CREATE TABLE IF NOT EXISTS "commercial_parrainages"(
  "id" varchar not null,
  "commercial_id" varchar not null,
  "proprietaire_id" varchar not null,
  "expire_le" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("commercial_id") references "commercial_profiles"("id") on delete cascade,
  foreign key("proprietaire_id") references "users"("id") on delete cascade,
  primary key("id")
);
CREATE UNIQUE INDEX "commercial_parrainages_proprietaire_id_unique" on "commercial_parrainages"(
  "proprietaire_id"
);
CREATE TABLE IF NOT EXISTS "commercial_commissions"(
  "id" varchar not null,
  "commercial_id" varchar not null,
  "parrainage_id" varchar not null,
  "abonnement_id" varchar not null,
  "montant_base" numeric not null,
  "taux" integer not null,
  "montant" numeric not null,
  "statut" varchar not null default 'en_attente',
  "payee_le" datetime,
  "notes_paiement" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("commercial_id") references "commercial_profiles"("id") on delete cascade,
  foreign key("parrainage_id") references "commercial_parrainages"("id") on delete cascade,
  foreign key("abonnement_id") references "abonnements"("id") on delete cascade,
  primary key("id")
);
CREATE INDEX "commercial_commissions_commercial_id_statut_index" on "commercial_commissions"(
  "commercial_id",
  "statut"
);
CREATE UNIQUE INDEX "commercial_commissions_abonnement_id_unique" on "commercial_commissions"(
  "abonnement_id"
);
CREATE TABLE IF NOT EXISTS "commercial_config"(
  "id" integer primary key autoincrement not null,
  "taux" integer not null default '20',
  "duree_mois" integer not null default '6',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "email_campagnes"(
  "id" integer primary key autoincrement not null,
  "envoye_par" varchar,
  "sujet" varchar not null,
  "corps" text not null,
  "mode" varchar not null,
  "destinataires_emails" text not null,
  "nb_envoyes" integer not null default '0',
  "nb_echecs" integer not null default '0',
  "erreurs" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("envoye_par") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "push_subscriptions"(
  "id" integer primary key autoincrement not null,
  "user_id" varchar not null,
  "endpoint" text not null,
  "public_key" text not null,
  "auth_token" text not null,
  "user_agent" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "push_user_endpoint_unique" on "push_subscriptions"(
  "user_id",
  "endpoint"
);
CREATE TABLE IF NOT EXISTS "rendez_vous"(
  "id" varchar not null,
  "institut_id" varchar not null,
  "client_id" varchar,
  "client_nom" varchar not null,
  "client_telephone" varchar,
  "client_email" varchar,
  "employe_id" varchar,
  "debut_le" datetime not null,
  "duree_minutes" integer not null default '30',
  "statut" varchar not null default 'en_attente',
  "notes" text,
  "prestation_libre" varchar,
  "rappel_envoye" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("institut_id") references "instituts"("id") on delete cascade,
  foreign key("client_id") references "clients"("id") on delete set null,
  foreign key("employe_id") references "users"("id") on delete set null,
  primary key("id")
);
CREATE INDEX "rendez_vous_institut_id_debut_le_index" on "rendez_vous"(
  "institut_id",
  "debut_le"
);
CREATE INDEX "rendez_vous_institut_id_statut_index" on "rendez_vous"(
  "institut_id",
  "statut"
);
CREATE INDEX "rendez_vous_client_id_index" on "rendez_vous"("client_id");
CREATE TABLE IF NOT EXISTS "rendez_vous_prestations"(
  "rendez_vous_id" varchar not null,
  "prestation_id" varchar not null,
  foreign key("rendez_vous_id") references "rendez_vous"("id") on delete cascade,
  foreign key("prestation_id") references "prestations"("id") on delete cascade,
  primary key("rendez_vous_id", "prestation_id")
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2026_04_01_144000_create_instituts_table',1);
INSERT INTO migrations VALUES(5,'2026_04_01_144055_create_categories_prestations_table',1);
INSERT INTO migrations VALUES(6,'2026_04_01_144055_create_categories_produits_table',1);
INSERT INTO migrations VALUES(7,'2026_04_01_144055_create_clients_table',1);
INSERT INTO migrations VALUES(8,'2026_04_01_144055_create_plans_abonnement_table',1);
INSERT INTO migrations VALUES(9,'2026_04_01_144055_create_prestations_table',1);
INSERT INTO migrations VALUES(10,'2026_04_01_144055_create_produits_table',1);
INSERT INTO migrations VALUES(11,'2026_04_01_144055_create_ventes_table',1);
INSERT INTO migrations VALUES(12,'2026_04_01_144056_add_institut_fields_to_users_table',1);
INSERT INTO migrations VALUES(13,'2026_04_01_144056_create_abonnements_table',1);
INSERT INTO migrations VALUES(14,'2026_04_01_144056_create_depenses_table',1);
INSERT INTO migrations VALUES(15,'2026_04_01_144056_create_messages_contact_table',1);
INSERT INTO migrations VALUES(16,'2026_04_01_144056_create_mouvements_stock_table',1);
INSERT INTO migrations VALUES(17,'2026_04_01_144056_create_vente_items_table',1);
INSERT INTO migrations VALUES(18,'2026_04_02_133304_add_carte_to_ventes_mode_paiement',1);
INSERT INTO migrations VALUES(19,'2026_04_03_000001_refactor_abonnement_system',1);
INSERT INTO migrations VALUES(20,'2026_04_07_000001_create_codes_reduction_table',1);
INSERT INTO migrations VALUES(21,'2026_04_07_152914_add_client_id_to_codes_reduction_table',1);
INSERT INTO migrations VALUES(22,'2026_04_07_160333_add_proprietaire_id_to_instituts_table',1);
INSERT INTO migrations VALUES(23,'2026_04_16_000001_add_parrainage_and_offre_lancement',1);
INSERT INTO migrations VALUES(24,'2026_04_16_200000_create_fidelite_system',1);
INSERT INTO migrations VALUES(25,'2026_04_17_000001_create_offres_promotionnelles_table',1);
INSERT INTO migrations VALUES(26,'2026_04_17_100000_add_notifier_jusqu_au_to_offres_promotionnelles',1);
INSERT INTO migrations VALUES(27,'2026_04_20_085544_clear_old_prix_lancement_from_plans',1);
INSERT INTO migrations VALUES(28,'2026_04_20_105332_add_montant_carte_to_ventes_table',1);
INSERT INTO migrations VALUES(29,'2026_04_20_112543_change_date_naissance_to_mois_jour_in_clients_table',1);
INSERT INTO migrations VALUES(30,'2026_04_27_000001_create_commercial_tables',1);
INSERT INTO migrations VALUES(31,'2026_04_27_000002_add_commercial_role_to_users',1);
INSERT INTO migrations VALUES(32,'2026_05_05_153058_create_email_campagnes_table',1);
INSERT INTO migrations VALUES(33,'2026_05_05_170533_create_push_subscriptions_table',1);
INSERT INTO migrations VALUES(34,'2026_05_11_000001_create_rendez_vous_table',1);
