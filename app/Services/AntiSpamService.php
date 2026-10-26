<?php

namespace App\Services;

use App\Models\MessageContact;
use Illuminate\Support\Facades\Crypt;

/**
 * Détection du spam sur le formulaire de contact public.
 *
 * Principe : un score de suspicion cumulatif. Rien n'est bloqué côté
 * utilisateur — un message jugé spam est stocké puis marqué, ce qui évite
 * d'informer le robot qu'il a été détecté.
 */
class AntiSpamService
{
    /**
     * Au-delà de ce score, le message est considéré comme du spam.
     *
     * Calibré pour qu'un seul signal faible (un lien, une adresse jetable,
     * un mot-clé générique) ne suffise pas, mais que deux se cumulent.
     */
    public const SEUIL_SPAM = 40;

    /** Délai minimal (secondes) entre l'affichage et l'envoi du formulaire. */
    public const DELAI_MINIMAL = 3;

    /** Nombre d'URLs dans le corps au-delà duquel le message est suspect. */
    public const MAX_URLS = 1;

    /** Taille maximale d'un nom crédible. */
    public const MAX_LONGUEUR_NOM = 80;

    /** Signaux quasi certains à eux seuls. */
    private const MOTS_CLES_CERTAINS = [
        'backlink', 'guest post', 'casino', 'viagra', 'cialis', 'escort',
        'porn', 'replica watch', 'bulk email', 'casino en ligne',
    ];

    /** Signaux ambigus : présents aussi dans des demandes légitimes. */
    private const MOTS_CLES_SUSPECTS = [
        'seo', 'crypto', 'bitcoin', 'forex', 'loan', 'make money',
        'work from home', 'click here', 'buy now', 'limited offer', 'weight loss',
    ];

    /**
     * Analyse une soumission de contact.
     *
     * @param  array  $donnees            nom, email, message, website, _t
     * @param  string $ip                 IP du demandeur
     * @param  bool   $verifierRepetition Désactiver lors d'un rejeu sur l'historique :
     *                                    la fenêtre « 24 h » n'a pas de sens passé.
     * @return array{score:int, raisons:array<int,string>, spam:bool}
     */
    public function analyser(array $donnees, string $ip, bool $verifierRepetition = true): array
    {
        $score = 0;
        $raisons = [];

        $ajouter = function (int $points, string $raison) use (&$score, &$raisons) {
            $score += $points;
            $raisons[] = $raison;
        };

        $nom     = (string) ($donnees['nom'] ?? '');
        $email   = (string) ($donnees['email'] ?? '');
        $message = (string) ($donnees['message'] ?? '');

        // ── 1. Pièges du formulaire ────────────────────────────────────────
        if (!empty($donnees['website'])) {
            $ajouter(100, 'Champ leurre rempli');
        }

        $secondes = $this->secondesDepuisAffichage($donnees['_t'] ?? null);
        if ($secondes !== null && $secondes < self::DELAI_MINIMAL) {
            $ajouter(50, 'Formulaire rempli en ' . $secondes . ' s (trop rapide)');
        }

        // ── 2. Contenu du message ──────────────────────────────────────────
        $nbUrls = $this->compterUrls($message);
        if ($nbUrls > self::MAX_URLS) {
            $ajouter(40, $nbUrls . ' liens dans le message');
        } elseif ($nbUrls === 1) {
            $ajouter(15, '1 lien dans le message');
        }

        $contenuMinuscule = mb_strtolower($nom . ' ' . $message);

        foreach (self::MOTS_CLES_CERTAINS as $mot) {
            if (str_contains($contenuMinuscule, $mot)) {
                $ajouter(50, 'Mot-clé de spam : « ' . $mot . ' »');
                break;
            }
        }

        foreach (self::MOTS_CLES_SUSPECTS as $mot) {
            if (str_contains($contenuMinuscule, $mot)) {
                $ajouter(20, 'Terme commercial insistant : « ' . $mot . ' »');
                break;
            }
        }

        if (preg_match('/[\x{0400}-\x{04FF}]/u', $nom . $message)) {
            $ajouter(25, 'Caractères cyrilliques');
        }

        if (mb_strlen($nom) > self::MAX_LONGUEUR_NOM) {
            $ajouter(20, 'Nom anormalement long');
        }

        // Un nom qui contient une URL est un signal très fiable
        if (preg_match('~https?://|www\.~i', $nom)) {
            $ajouter(40, 'URL dans le nom');
        }

        // ── 3. Email ───────────────────────────────────────────────────────
        if ($this->estEmailSuspecte($email)) {
            $ajouter(25, 'Adresse email douteuse');
        }

        // ── 4. Répétition ──────────────────────────────────────────────────
        if ($verifierRepetition && $this->dejaRecuRecemment($email, $message, $ip)) {
            $ajouter(35, 'Message identique déjà reçu récemment');
        }

        return [
            'score'   => $score,
            'raisons' => $raisons,
            'spam'    => $score >= self::SEUIL_SPAM,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /** Jeton horodaté posé dans le formulaire au rendu de la page. */
    public static function jetonHorodatage(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    private function secondesDepuisAffichage(?string $jeton): ?int
    {
        if (!$jeton) {
            return null;
        }

        try {
            $depart = (int) Crypt::decryptString($jeton);
        } catch (\Throwable $e) {
            // Jeton falsifié ou expiré → on ne pénalise pas, on ignore
            return null;
        }

        return max(0, now()->timestamp - $depart);
    }

    private function compterUrls(string $texte): int
    {
        return preg_match_all('~https?://|www\.~i', $texte);
    }

    private function estEmailSuspecte(string $email): bool
    {
        if (!str_contains($email, '@')) {
            return false;
        }

        [, $domaine] = explode('@', $email, 2);
        $domaine = mb_strtolower($domaine);

        // Jetables / temporaires très utilisés par les robots
        $jetables = [
            'mailinator.com', 'tempmail.com', 'guerrillamail.com', '10minutemail.com',
            'yopmail.com', 'trashmail.com', 'sharklasers.com', 'getnada.com',
        ];

        if (in_array($domaine, $jetables, true)) {
            return true;
        }

        // Domaine avec beaucoup de chiffres / lettres collées = généré
        return (bool) preg_match('/^[a-z]{6,}\d{3,}\./', $domaine);
    }

    private function dejaRecuRecemment(string $email, string $message, string $ip): bool
    {
        return MessageContact::query()
            ->where('created_at', '>=', now()->subDay())
            ->where(function ($q) use ($email, $message, $ip) {
                $q->where('email', $email)
                  ->orWhere(fn ($x) => $x->where('ip_address', $ip)->where('message', $message));
            })
            ->exists();
    }
}
