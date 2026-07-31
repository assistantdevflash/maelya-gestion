<?php

namespace App\Helpers;

class CouleurHelper
{
    /**
     * Détermine si une couleur hex est foncée (retourne true → texte blanc conseillé).
     */
    public static function estFoncee(string $hex): bool
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance < 0.5;
    }

    /**
     * Ratio de contraste WCAG entre deux couleurs hex.
     */
    public static function ratioContraste(string $hex1, string $hex2): float
    {
        $l1 = self::luminanceRelative($hex1);
        $l2 = self::luminanceRelative($hex2);
        $clair = max($l1, $l2);
        $fonce = min($l1, $l2);
        return ($clair + 0.05) / ($fonce + 0.05);
    }

    /**
     * Vérifie si le contraste est suffisant pour WCAG AA (≥ 4.5:1).
     */
    public static function estAccessible(string $fond, string $texte = '#ffffff'): bool
    {
        return self::ratioContraste($fond, $texte) >= 4.5;
    }

    private static function luminanceRelative(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
