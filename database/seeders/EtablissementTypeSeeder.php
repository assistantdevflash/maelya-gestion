<?php

namespace Database\Seeders;

use App\Models\EtablissementType;
use Illuminate\Database\Seeder;

class EtablissementTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'salon_coiffure', 'libelle' => 'Salon de coiffure', 'position' => 1],
            ['code' => 'barbier', 'libelle' => 'Barbier', 'position' => 2],
            ['code' => 'institut_beaute', 'libelle' => 'Institut de beauté', 'position' => 3],
            ['code' => 'centre_esthetique', 'libelle' => 'Centre esthétique', 'position' => 4],
            ['code' => 'boutique_mode', 'libelle' => 'Boutique de mode', 'position' => 5],
            ['code' => 'auto_ecole', 'libelle' => 'Auto-école', 'position' => 6],
            ['code' => 'cabinet_medical', 'libelle' => 'Cabinet médical & paramédical', 'position' => 7],
            ['code' => 'atelier_technique', 'libelle' => 'Atelier & Service technique', 'position' => 8],
            ['code' => 'centre_formation', 'libelle' => 'Centre de formation', 'position' => 9],
            ['code' => 'imprimerie', 'libelle' => 'Imprimerie', 'position' => 10],
            ['code' => 'lavage_auto', 'libelle' => 'Lavage auto', 'position' => 11],
            ['code' => 'pressing', 'libelle' => 'Pressing / Laverie', 'position' => 12],
            ['code' => 'business_center', 'libelle' => 'Business center', 'position' => 13],
            ['code' => 'depot_gaz', 'libelle' => 'Dépôt de gaz', 'position' => 14],
            ['code' => 'commerce', 'libelle' => 'Commerce / Alimentation', 'position' => 15],
            ['code' => 'evenementiel', 'libelle' => 'Évènementiel', 'position' => 16],
            ['code' => 'informatique_telephonie', 'libelle' => 'Informatique / Téléphonie', 'position' => 17],
            ['code' => 'autre', 'libelle' => 'Autre', 'position' => 99],
        ];

        foreach ($types as $type) {
            EtablissementType::updateOrCreate(
                ['code' => $type['code']],
                array_merge($type, ['actif' => true])
            );
        }
    }
}
