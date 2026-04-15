<?php
/**
 * Seed prestations pour "Beauté secondaire" et "Beauté secondaire Abatta"
 * Usage : php artisan tinker scripts/seed_prestations_beaute.php
 * OU     php scripts/run_seed.php (avec bootstrap Laravel)
 */

$instituts = [
    '019d67ad-758b-7253-8382-ac168a50eed9', // Beauté secondaire
    '019d68ce-e934-70e8-8427-300c0df683ee', // Beauté secondaire Abatta
];

// ─── Catalogue : [catégorie => [[nom, prix_fcfa], ...]] ───────────────────────
$catalogue = [

    'Package Gommage' => [
        ['Rituel de douc\'heure',  20000, 'soin de visage + gommage + enveloppement'],
        ['Queen Shine',            25000, 'gommage eclaircissant + soin visage clariffiant'],
        ['Charme d\'orient',       30000, 'savonnage + gommage marocain + enveloppement rhaoul + soin visage'],
        ['Perfect Time',           40000, 'gommage eclarcissant + soin visage clarifiant + massage 30min'],
        ['Pack Luxury',            50000, 'gommage + couverture chauffante + soin visage + enveloppement + massage'],
        ['Cocooning Zen',          60000, 'savonnage + enveloppement + gommage + soin visage + modelage'],
        ['Evasion Time',           60000, 'harra + gommage corps + soin visage + enveloppement + massage relaxant'],
        ['Africa Queen',           70000, 'savonnage + gommage + bain vapeur intime + soin visage + massage relaxant'],
    ],

    'Soin Couples ou Duos' => [
        ['Cupidon',         35000, 'gommage + soin visage + enveloppement clarifiant'],
        ['Kiss Love',       60000, 'massage relaxant 30min + gommage exfoliant + soin visage'],
        ['Romance Story',  100000, 'massage relaxant 1h + soin + harra + gommage clarifiant'],
    ],

    'Soin de Visage' => [
        ['Soin de visage purifiant/nettoyage', 10000, null],
        ['Soin de visage peau grasse',         15000, null],
        ['Soin anti-âge',                      15000, null],
        ['Soin de visage apaisant',            15000, null],
    ],

    'Soin de Visage Traitant' => [
        ['Hydra Facial',         20000, null],
        ['Micro Needling',       25000, null],
        ['Oxygenes',             30000, null],
        ['Carbo-peel',           20000, null],
        ['Lumière Pulsée',       25000, null],
        ['Méco Thérapie',        25000, null],
        ['Peeling Zena',         30000, null],
        ['Anti-âge RF',          25000, null],
        ['Soin Oxygène Sérum',   30000, null],
        ['Micro Dermabrasion',   15000, null],
    ],

    'Soin Personnalisé' => [
        ['Anti Acné',               25000, null],
        ['Anti-hyperpigmentation',  30000, null],
        ['Anti Âge',                30000, null],
        ['Anti Cerne',              25000, null],
        ['Micro Shading',           50000, null],
    ],

    'Soin de Corps' => [
        ['Gommage Clarifiant',                              15000, null],
        ['Gommage Exfoliant',                              10000, null],
        ['Gommage Éclaircissant + Enveloppement Curcuma ou Henné', 20000, null],
        ['Gommage Raffermissant Hibiscus + Enveloppement', 25000, null],
        ['Charme au Maroc (savon noir + enveloppement)',   20000, null],
    ],

    'Hamram' => [
        ['Hamram 1 séance 3min', 5000,  null],
        ['Hamram 1 heure',       15000, null],
    ],

    'Couverture Chauffante' => [
        ['Couverture Chauffante 30min', 5000, null],
    ],

    'Lifting Colombien' => [
        ['Séance 30min',   15000, null],
        ['Séance 1h',      20000, null],
        ['Forfait 5 séances', 90000, null],
    ],

    'Lipocavitation' => [
        ['Séance 30min',             25000,  null],
        ['Lipo + Mado Thérapie',     35000,  null],
        ['Forfait 5 séances Lipocavitation', 115000, null],
    ],

    'Body Sculpt' => [
        ['Mado Thérapie',        25000,  null],
        ['Forfait 5 séances',   150000, null],
    ],

    'Drainage G5' => [
        ['Drainage 30min',           20000, null],
        ['Drainage + Mado Thérapie', 35000, null],
        ['Forfait 5 séances Drainage', 90000, null],
    ],

    'Anti Cellulite' => [
        ['Séance 1h',          25000,  null],
        ['Forfait 5 séances', 115000, null],
    ],

    'Massage Post-Opératoire' => [
        ['Séance',              50000,  null],
        ['Forfait 5 séances',  230000, null],
    ],

    'Massage' => [
        ['Massage Relaxant 30min',           20000, null],
        ['Massage Relaxant 1h',              25000, null],
        ['Massage Relaxant Pierre Chaudes',  35000, null],
        ['Massage Tonique',                  30000, null],
        ['Harmah + Massage Relaxant',        40000, null],
        ['Massage Dos (30min)',              15000, null],
        ['Massage Pieds',                    10000, null],
        ['Massage Thérapeutique',            40000, null],
        ['Massage Relaxant 4 Doigts',        40000, null],
    ],

    'Vajacial' => [
        ['Exfoliation + Soin Pubis',  35000, null],
        ['Soin Antiacné Fesses',      20000, null],
        ['Vajacial + Bain Vapeur',    40000, null],
    ],

    'Épilation' => [
        ['Épilation Sourcils',       5000,  null],
        ['Épilation Menton',        10000, null],
        ['Épilation Aisselles',      5000,  null],
        ['Épilation Moustache',      5000,  null],
        ['Épilation Poitrine',      15000, null],
        ['Épilation Demi Jambes',   20000, null],
        ['Épilation Bras Entier',   25000, null],
        ['Épilation Demi Bras',     15000, null],
        ['Épilation Maillot Intégral', 20000, null],
        ['Épilation Maillot (Pubis)', 10000, null],
    ],

    'Extension de Cils' => [
        ['Pose Classique',               10000, null],
        ['Volume Simple',               20000, null],
        ['Regard de Sirène (effet tiré)', 25000, null],
        ['Regard Sirène Volume',         30000, null],
        ['Volume Russe',                 35000, null],
        ['Volume Rega',                  40000, null],
        ['Dépose',                       50000, null],
    ],

    'Pigmentation Lèvres' => [
        ['1 séance',   20000,  null],
        ['6 séances', 100000, null],
    ],

    'Onglerie' => [
        ['Résine-Gel-Acrygel',                        10000, null],
        ['Vernis Permanent Ongle Naturel',             3000,  null],
        ['Pose Capsule + Vernis Semi Permanent Mains', 5000,  null],
        ['Pose Capsule + Vernis Semi Permanent Pieds', 4000,  null],
        ['Gel sur Pieds',                             10000, null],
        ['Gel-Résine-Acrygel Charbon',                15000, null],
        ['Pédicure Manucure Femme',                    8000,  null],
        ['Pédicure Manucure Homme',                   10000, null],
        ['Pédicure Femme',                             7000,  null],
        ['Pédicure Homme',                            10000, null],
        ['Manucure Femme',                             3000,  null],
        ['Manucure Homme',                             5000,  null],
        ['Manucure Express Mains et Pieds',            5000,  null],
        ['Pose Vernis Classic',                        2000,  null],
        ['Pose Gel-Acrygel-Résine',                    5000,  null],
        ['Traitement Ongle',                           3000,  null],
        ['Déco Simple',                                5000,  null],
        ['Déco 3D',                                    1000,  null],
        ['Déco Complexe',                              2000,  null],
        ['Remplissage Gel-Acrygel Résine',             7000,  null],
    ],

    'Coiffure' => [
        ['Shampoing',                     3000,  null],
        ['Défrisage',                     5000,  null],
        ['Mise en Forme',                 5000,  null],
        ['Bain d\'Huile',                 5000,  null],
        ['Soin Capillaire Personnalisé', 10000, null],
        ['Natte pour Perruque',          20000, null],
        ['Teinture sur Mèche',           15000, null],
        ['Teinture sur Cheveux',         10000, null],
        ['Entretien Perruque',           10000, null],
    ],

    'Tresse' => [
        ['Tout Mèches sans Mèche Long',  15000, null],
        ['Tout Mèches avec Mèche Long',  20000, null],
        ['Macoursis Simple',              5000,  null],
        ['Macoursis Fantaisie',           8000,  null],
    ],

    'Pose Perruque Closure' => [
        ['Pose Simple',      5000, null],
        ['Pose avec Colle',  8000, null],
    ],

    'Pose Perruque Frontale' => [
        ['Pose sans Colle',    8000, null],
        ['Pose avec Colle',   10000, null],
        ['Customisation',      5000, null],
    ],

    'Tissage' => [
        ['Tissage Fermé',   8000,  null],
        ['Tissage Rajout',  5000,  null],
        ['Tissage Coup',   10000, null],
    ],

    'Confection Perruque' => [
        ['Lace Frontale', 15000, null],
        ['Closure',       10000, null],
        ['Frange',        15000, null],
    ],
];

// ─── Insertion ───────────────────────────────────────────────────────────────
$totalCats  = 0;
$totalPrests = 0;
$ordre = 1;

foreach ($instituts as $institutId) {
    $inst = App\Models\Institut::find($institutId);
    echo "── " . $inst->nom . " ──────────────────────────────────────\n";

    foreach ($catalogue as $nomCategorie => $prestations) {
        // Créer ou récupérer la catégorie (évite les doublons)
        $cat = App\Models\CategoriePrestation::firstOrCreate(
            ['institut_id' => $institutId, 'nom' => $nomCategorie],
            ['ordre' => $ordre++]
        );

        foreach ($prestations as [$nom, $prix, $description]) {
            App\Models\Prestation::firstOrCreate(
                ['institut_id' => $institutId, 'nom' => $nom],
                [
                    'categorie_id' => $cat->id,
                    'prix'         => $prix,
                    'duree'        => null,
                    'description'  => $description,
                    'actif'        => true,
                ]
            );
            $totalPrests++;
        }

        echo "  [OK] $nomCategorie (" . count($prestations) . " prestations)\n";
        $totalCats++;
    }

    echo "\n";
    $ordre = 1; // reset l'ordre par institut
}

echo "✓ Terminé : $totalCats catégories · $totalPrests prestations créées/vérifiées\n";
