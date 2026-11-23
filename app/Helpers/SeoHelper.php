<?php

namespace App\Helpers;

use App\Models\Institut;
use Illuminate\Support\Facades\Storage;

/**
 * Visuels de partage (Open Graph / Twitter Card).
 *
 * WhatsApp, Facebook et LinkedIn n'acceptent ni SVG ni URL cassée : ils
 * affichent alors une vignette vide. On renvoie donc toujours un PNG qui
 * existe réellement sur le disque.
 *
 * Règle : le logo de l'établissement quand il y en a un, le visuel officiel
 * Maëlya Gestion sinon.
 */
class SeoHelper
{
    /** Visuel officiel : logo + nom, 1200 × 630. */
    public const IMAGE_DEFAUT = 'images/og-maelya.png';

    public const LARGEUR_DEFAUT = 1200;

    public const HAUTEUR_DEFAUT = 630;

    public static function imageDefaut(): string
    {
        return asset(self::IMAGE_DEFAUT);
    }

    /**
     * Logo de l'établissement, avec repli sur le visuel Maëlya Gestion.
     *
     * On vérifie l'existence du fichier : un logo supprimé du disque mais
     * resté en base produisait une vignette vide.
     */
    public static function imagePourInstitut(?Institut $institut): string
    {
        $logo = $institut?->logo;

        if (filled($logo) && Storage::disk('public')->exists($logo)) {
            return Storage::disk('public')->url($logo);
        }

        return self::imageDefaut();
    }

    /**
     * Logo d'un établissement ou photo d'un produit, avec le même repli.
     */
    public static function imagePourProduit(?string $photo, ?Institut $institut): string
    {
        if (filled($photo) && Storage::disk('public')->exists($photo)) {
            return Storage::disk('public')->url($photo);
        }

        return self::imagePourInstitut($institut);
    }

    /**
     * Vrai si l'image est le visuel par défaut, dont on connaît les
     * dimensions. Pour un logo d'établissement, elles sont inconnues :
     * mieux vaut ne rien déclarer qu'annoncer 1200 × 630 à tort.
     */
    public static function estImageDefaut(string $url): bool
    {
        return $url === self::imageDefaut();
    }
}
