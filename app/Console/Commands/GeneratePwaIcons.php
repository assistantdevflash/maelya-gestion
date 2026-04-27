<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePwaIcons extends Command
{
    protected $signature   = 'pwa:generate-icons';
    protected $description = 'Génère les icônes PNG nécessaires à la PWA';

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('L\'extension PHP GD est requise.');
            return self::FAILURE;
        }

        $outDir = public_path('icons');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $sizes = [
            ['file' => 'icon-192.png',        'size' => 192, 'maskable' => false],
            ['file' => 'icon-512.png',        'size' => 512, 'maskable' => false],
            ['file' => 'icon-maskable-192.png','size' => 192, 'maskable' => true],
            ['file' => 'icon-maskable-512.png','size' => 512, 'maskable' => true],
            ['file' => 'apple-touch-icon.png', 'size' => 180, 'maskable' => false],
        ];

        foreach ($sizes as $spec) {
            $this->generateIcon(
                $outDir . '/' . $spec['file'],
                $spec['size'],
                $spec['maskable']
            );
            $this->info("✓ {$spec['file']} ({$spec['size']}×{$spec['size']})");
        }

        $this->newLine();
        $this->info('Icônes PWA générées dans public/icons/');
        return self::SUCCESS;
    }

    private function generateIcon(string $path, int $size, bool $maskable): void
    {
        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        // Couleurs : violet #9333ea → rose #ec4899 (diagonale)
        $r1 = 0x93; $g1 = 0x33; $b1 = 0xea;
        $r2 = 0xec; $g2 = 0x48; $b2 = 0x99;

        // Remplissage dégradé diagonal
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $t   = ($x + $y) / (2 * ($size - 1));
                $r   = (int) ($r1 + ($r2 - $r1) * $t);
                $g   = (int) ($g1 + ($g2 - $g1) * $t);
                $b   = (int) ($b1 + ($b2 - $b1) * $t);
                $col = imagecolorallocate($img, $r, $g, $b);
                imagesetpixel($img, $x, $y, $col);
            }
        }

        // Coins arrondis (sauf maskable qui utilise fond plein)
        if (! $maskable) {
            $radius = (int) ($size * 0.25); // ~25% = style iOS
            $this->applyRoundedCorners($img, $size, $radius);
        }

        // Lettre "M" blanche centrée
        $this->drawLetter($img, $size, $maskable);

        imagepng($img, $path);
        imagedestroy($img);
    }

    private function applyRoundedCorners(\GdImage $img, int $size, int $radius): void
    {
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

        // Créer un masque pour les coins
        $corners = [
            [0,          0,          $radius, $radius,   0,      0],
            [$size - $radius, 0,          $size,   $radius,   $size - $radius, 0],
            [0,          $size - $radius, $radius, $size,     0,      $size - $radius],
            [$size - $radius, $size - $radius, $size,   $size,     $size - $radius, $size - $radius],
        ];

        foreach ($corners as [$x1, $y1, $x2, $y2, $cx, $cy]) {
            imagefilledrectangle($img, $x1, $y1, $x2, $y2, $transparent);
        }

        // Dessiner des quarts de cercle pleins aux coins (avec la couleur de fond)
        $tmpImg = imagecreatetruecolor($size, $size);
        imagealphablending($tmpImg, false);
        imagesavealpha($tmpImg, true);
        $trans = imagecolorallocatealpha($tmpImg, 0, 0, 0, 127);
        imagefill($tmpImg, 0, 0, $trans);

        // Recopier le gradient sur un fond transparent
        imagecopy($tmpImg, $img, 0, 0, 0, 0, $size, $size);

        // Effacer les coins dans l'image originale
        imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, imagecolorallocatealpha($img, 0, 0, 0, 0));

        // Remplir coins arrondis avec imagefilledellipse
        imagealphablending($img, false);
        $white = imagecolorallocate($tmpImg, 255, 255, 255);

        // Masque : cercle blanc sur fond transparent
        $mask = imagecreatetruecolor($size, $size);
        imagealphablending($mask, false);
        imagesavealpha($mask, true);
        imagefill($mask, 0, 0, imagecolorallocatealpha($mask, 0, 0, 0, 127));
        $maskColor = imagecolorallocate($mask, 255, 255, 255);
        imagefilledrectangle($mask, $radius, 0, $size - $radius, $size, $maskColor);
        imagefilledrectangle($mask, 0, $radius, $size, $size - $radius, $maskColor);
        imagefilledellipse($mask, $radius, $radius, $radius * 2, $radius * 2, $maskColor);
        imagefilledellipse($mask, $size - $radius, $radius, $radius * 2, $radius * 2, $maskColor);
        imagefilledellipse($mask, $radius, $size - $radius, $radius * 2, $radius * 2, $maskColor);
        imagefilledellipse($mask, $size - $radius, $size - $radius, $radius * 2, $radius * 2, $maskColor);

        // Appliquer le masque
        $result = imagecreatetruecolor($size, $size);
        imagealphablending($result, false);
        imagesavealpha($result, true);
        $transResult = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transResult);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $maskPx = imagecolorat($mask, $x, $y);
                $r      = ($maskPx >> 16) & 0xFF;
                if ($r > 0) {
                    $srcPx = imagecolorat($tmpImg, $x, $y);
                    imagesetpixel($result, $x, $y, $srcPx);
                }
            }
        }

        imagecopy($img, $result, 0, 0, 0, 0, $size, $size);
        imagedestroy($tmpImg);
        imagedestroy($mask);
        imagedestroy($result);
    }

    private function drawLetter(\GdImage $img, int $size, bool $maskable): void
    {
        $white = imagecolorallocate($img, 255, 255, 255);

        // Utiliser la police built-in GD (pas de TTF requis)
        // Taille de police GD disponible : 1-5 (5 = 9×15px)
        $fontIndex = 5; // plus grande police built-in
        $charW     = imagefontwidth($fontIndex);
        $charH     = imagefontheight($fontIndex);

        // On dessine "M" en le répétant en grille pour simuler une taille plus grande
        $scale = (int) ($size / 64); // à 192px → scale=3, 512px → scale=8
        $scale = max(2, $scale);

        $letterW = $charW * $scale;
        $letterH = $charH * $scale;
        $startX  = (int) (($size - $letterW) / 2);
        $startY  = (int) (($size - $letterH) / 2);

        // Dessiner lettre sur une petite image temporaire puis agrandir
        $tmp  = imagecreatetruecolor($charW, $charH);
        $bg   = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, $bg);
        imagealphablending($tmp, true);
        $wh   = imagecolorallocate($tmp, 255, 255, 255);
        imagechar($tmp, $fontIndex, 0, 0, 'M', $wh);

        // Agrandir et copier sur l'image principale
        imagealphablending($img, true);
        imagecopyresized($img, $tmp, $startX, $startY, 0, 0, $letterW, $letterH, $charW, $charH);
        imagedestroy($tmp);
    }
}
