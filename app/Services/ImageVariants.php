<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Variantes d'images pour la vitrine publique.
 *
 * Les photos de produits étaient servies en taille originale (plusieurs Mo
 * pour une photo de téléphone). On génère ici des vignettes à la demande, puis
 * on les sert via srcset. Aucune commande de rattrapage n'est nécessaire : la
 * variante est créée la première fois qu'elle est demandée.
 */
class ImageVariants
{
    /** Largeur cible par variante. */
    private const LARGEURS = [
        'thumb'  => 400,
        'medium' => 800,
    ];

    private const QUALITE_JPEG = 82;

    /**
     * URL de la variante demandée, ou de l'original si la génération échoue.
     */
    public static function url(?string $chemin, string $taille = 'medium'): ?string
    {
        if (! $chemin) {
            return null;
        }

        $original = asset('storage/' . $chemin);

        if (! isset(self::LARGEURS[$taille])) {
            return $original;
        }

        $disque  = Storage::disk('public');
        $source  = $disque->path($chemin);

        if (! is_file($source)) {
            return $original;
        }

        $variante = self::cheminVariante($chemin, self::LARGEURS[$taille]);

        if (! $disque->exists($variante)) {
            try {
                self::generer($source, $disque->path($variante), self::LARGEURS[$taille]);
            } catch (\Throwable $e) {
                report($e);

                return $original;
            }
        }

        return $disque->exists($variante)
            ? asset('storage/' . $variante)
            : $original;
    }

    /**
     * Attribut srcset prêt à l'emploi (vignette + moyenne + original).
     */
    public static function srcset(?string $chemin): ?string
    {
        if (! $chemin) {
            return null;
        }

        $entrees = [];

        foreach (self::LARGEURS as $taille => $largeur) {
            $url = self::url($chemin, $taille);
            if ($url) {
                $entrees[] = $url . ' ' . $largeur . 'w';
            }
        }

        $entrees[] = asset('storage/' . $chemin) . ' 1600w';

        return implode(', ', $entrees);
    }

    private static function cheminVariante(string $chemin, int $largeur): string
    {
        $info = pathinfo($chemin);

        return ($info['dirname'] !== '.' ? $info['dirname'] . '/' : '')
            . $info['filename'] . '-' . $largeur . '.jpg';
    }

    /**
     * Redimensionne l'image en conservant les proportions, puis l'enregistre
     * en JPEG (le format le plus léger pour des photos de produits).
     */
    private static function generer(string $source, string $destination, int $largeurCible): void
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new \RuntimeException('Extension GD indisponible.');
        }

        $contenu = @file_get_contents($source);
        if ($contenu === false) {
            throw new \RuntimeException('Image illisible : ' . $source);
        }

        $image = @imagecreatefromstring($contenu);
        if ($image === false) {
            throw new \RuntimeException('Format non pris en charge : ' . $source);
        }

        $largeur  = imagesx($image);
        $hauteur  = imagesy($image);

        // Inutile d'agrandir une image déjà plus petite que la cible
        if ($largeur > $largeurCible) {
            $nouvelleHauteur = (int) round($hauteur * $largeurCible / $largeur);
            $redimensionnee  = imagescale($image, $largeurCible, $nouvelleHauteur, IMG_BICUBIC);

            if ($redimensionnee !== false) {
                imagedestroy($image);
                $image = $redimensionnee;
            }
        }

        // Aplatir la transparence sur fond blanc avant l'encodage JPEG
        $plat = imagecreatetruecolor(imagesx($image), imagesy($image));
        $blanc = imagecolorallocate($plat, 255, 255, 255);
        imagefill($plat, 0, 0, $blanc);
        imagecopy($plat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        @mkdir(dirname($destination), 0775, true);
        imagejpeg($plat, $destination, self::QUALITE_JPEG);

        imagedestroy($plat);
        imagedestroy($image);
    }
}
