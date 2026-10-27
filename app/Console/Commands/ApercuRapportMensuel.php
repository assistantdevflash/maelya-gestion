<?php

namespace App\Console\Commands;

use App\Mail\RapportMensuelInstitut;
use App\Models\Institut;
use App\Models\User;
use App\Services\RapportMensuelService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie un exemplaire du rapport mensuel à une adresse donnée.
 *
 * Sert à relire le contenu et la mise en page avant la campagne réelle.
 * Sans `--institut`, le mail est construit avec un jeu de données fictif mais
 * réaliste : une base de développement vide ne produit que des zéros.
 */
class ApercuRapportMensuel extends Command
{
    protected $signature = 'rapports:apercu
        {email : Adresse à laquelle envoyer l\'aperçu}
        {--institut= : Utiliser les données réelles de cet établissement}
        {--mois= : Mois au format A-M (défaut : mois écoulé)}';

    protected $description = 'Envoie un exemplaire du rapport mensuel à une adresse pour relecture.';

    public function handle(RapportMensuelService $service): int
    {
        $email = (string) $this->argument('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Adresse email invalide : {$email}");

            return self::FAILURE;
        }

        $mois = $this->option('mois')
            ? Carbon::createFromFormat('!Y-m', $this->option('mois'))
            : now()->subMonthNoOverflow();

        $institutId = $this->option('institut');

        if ($institutId) {
            $institut = Institut::find($institutId);
            if (!$institut) {
                $this->error("Établissement introuvable : {$institutId}");

                return self::FAILURE;
            }

            $rapport = $service->construire($institut, $mois->copy()->startOfMonth());
            $this->info("Aperçu construit sur les données réelles de « {$institut->nom} ».");
        } else {
            $rapport = $this->rapportDeDemonstration($mois->copy()->startOfMonth());
            $this->info('Aperçu construit sur un jeu de données de démonstration.');
        }

        $destinataire = new User([
            'prenom'      => 'Basile',
            'nom_famille' => 'Tié',
            'email'       => $email,
        ]);

        Mail::to($email)->send(new RapportMensuelInstitut($rapport, $destinataire));

        $this->info("✓ Aperçu envoyé à {$email}.");
        $this->line('Sujet : 📊 Votre bilan de ' . $rapport['label'] . ' — '
            . number_format($rapport['actuel']['ca'], 0, ',', ' ') . ' FCFA');

        return self::SUCCESS;
    }

    /**
     * Jeu de données fictif : un institut en progression, plusieurs catégories
     * de dépenses et les deux types de lignes (prestations et produits).
     *
     * @return array<string, mixed>
     */
    private function rapportDeDemonstration(Carbon $mois): array
    {
        $debut = $mois->copy()->startOfMonth();
        $fin   = $mois->copy()->endOfMonth();
        $debutPrecedent = $debut->copy()->subMonthNoOverflow()->startOfMonth();

        return [
            'institut' => (new Institut([
                'nom'   => 'Institut Orchidée',
                'ville' => 'Abidjan',
            ]))->forceFill(['id' => 'apercu']),
            'debut'    => $debut,
            'fin'      => $fin,
            'label'    => ucfirst($debut->translatedFormat('F Y')),
            'label_precedent' => ucfirst($debutPrecedent->translatedFormat('F Y')),
            'actuel' => [
                'ca'               => 1_250_000,
                'nb_ventes'        => 68,
                'panier_moyen'     => 18_382,
                'depenses'         => 420_000,
                'benefice'         => 830_000,
                'marge'            => 66.4,
                'nouveaux_clients' => 7,
                'clients_servis'   => 41,
                'rdv'              => 52,
                'rdv_termines'     => 47,
                'factures'         => 14,
                'factures_reglees' => 11,
                'commandes'        => 9,
                'commandes_livrees_payees' => 7,
            ],
            'precedent' => [
                'ca'               => 980_000,
                'nb_ventes'        => 55,
                'panier_moyen'     => 17_818,
                'depenses'         => 380_000,
                'benefice'         => 600_000,
                'marge'            => 61.2,
                'nouveaux_clients' => 4,
                'clients_servis'   => 33,
                'rdv'              => 44,
                'rdv_termines'     => 39,
                'factures'         => 11,
                'factures_reglees' => 10,
                'commandes'        => 6,
                'commandes_livrees_payees' => 5,
            ],
            'evolutions' => [
                'ca'       => 27.6,
                'depenses' => 10.5,
                'benefice' => 38.3,
                'ventes'   => 23.6,
            ],
            'top' => [
                'prestations' => [
                    ['nom' => 'Soin hydratant visage', 'quantite' => 12, 'ca' => 320_000],
                    ['nom' => 'Balayage + patine',     'quantite' => 8,  'ca' => 280_000],
                    ['nom' => 'Manucure semi-permanent', 'quantite' => 15, 'ca' => 150_000],
                ],
                'produits' => [
                    ['nom' => 'Huile de coco bio 100 ml', 'quantite' => 18, 'ca' => 90_000],
                    ['nom' => 'Shampoing karité',         'quantite' => 12, 'ca' => 60_000],
                    ['nom' => 'Crème protectrice SPF 30', 'quantite' => 9,  'ca' => 45_000],
                ],
            ],
            'depenses_categories' => [
                ['categorie' => 'salaires',   'label' => 'Salaires',   'total' => 250_000, 'part' => 59.5],
                ['categorie' => 'loyer',      'label' => 'Loyer',      'total' => 120_000, 'part' => 28.6],
                ['categorie' => 'fournitures', 'label' => 'Fournitures', 'total' => 50_000, 'part' => 11.9],
            ],
            'sante' => ['code' => 'positive', 'label' => 'Mois bénéficiaire'],
            'conseil' => [
                'niveau' => 'positif',
                'titre'  => 'Excellente progression : +27.6 %',
                'texte'  => 'Votre chiffre d\'affaires a nettement progressé par rapport au mois précédent. C\'est le moment idéal pour transformer ces nouveaux clients en habitués.',
                'action' => 'Parlez du programme de fidélité à chaque encaissement : un client qui y adhère revient en moyenne plus souvent.',
            ],
            'a_activite' => true,
        ];
    }
}
