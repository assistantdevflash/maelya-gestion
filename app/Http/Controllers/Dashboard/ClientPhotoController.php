<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ClientPhotoController extends Controller
{
    /** Largeur max en pixels — au-delà on redimensionne */
    private const MAX_WIDTH = 1920;
    /** Qualité JPEG (0-100) */
    private const JPEG_QUALITY = 82;

    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'photos.*'   => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'type'       => ['required', 'in:avant,apres,avant_apres,autre'],
            'legende'    => ['nullable', 'string', 'max:255'],
            'date_prise' => ['nullable', 'date'],
        ]);

        $institutId = session('current_institut_id', Auth::user()->institut_id);

        foreach ($request->file('photos') as $file) {
            $mimeType = $file->getMimeType();
            $extension = $file->getClientOriginalExtension();
            
            // Si c'est un PDF, stocker directement sans redimensionnement
            if ($mimeType === 'application/pdf' || $extension === 'pdf') {
                $path = $file->store("clients/{$client->id}/photos", 'public');
            } else {
                // Image : redimensionner
                $path = $this->storeResized($file, "clients/{$client->id}/photos");
            }
            
            ClientPhoto::create([
                'institut_id' => $institutId,
                'client_id'   => $client->id,
                'user_id'     => Auth::id(),
                'type'        => $data['type'],
                'path'        => $path,
                'mime_type'   => $mimeType,
                'extension'   => $extension,
                'legende'     => $data['legende'] ?? null,
                'date_prise'  => $data['date_prise'] ?? now()->toDateString(),
            ]);
        }

        $count = count($request->file('photos'));
        $message = $count === 1 ? 'Fichier ajouté.' : "{$count} fichiers ajoutés.";
        
        return redirect()->route('dashboard.clients.show', ['client' => $client, 'onglet' => 'photos'])
            ->with('success', $message);
    }

    public function destroy(Client $client, ClientPhoto $photo)
    {
        abort_unless($photo->client_id === $client->id, 404);
        abort_unless(Auth::user()->isAdmin(), 403);
        $photo->delete();
        return redirect()->route('dashboard.clients.show', ['client' => $client, 'onglet' => 'photos'])
            ->with('success', 'Fichier supprimé.');
    }

    /**
     * Redimensionne et compresse l'image avec GD (natif PHP),
     * puis la stocke dans storage/app/public/{$dir}.
     * Retourne le chemin relatif au disque public.
     */
    private function storeResized(UploadedFile $file, string $dir): string
    {
        $realPath = $file->getRealPath();
        [$origW, $origH] = getimagesize($realPath);

        // Si déjà petite, on stocke directement
        if ($origW <= self::MAX_WIDTH) {
            return $file->store($dir, 'public');
        }

        // Calcul des nouvelles dimensions (ratio préservé)
        $ratio  = self::MAX_WIDTH / $origW;
        $newW   = self::MAX_WIDTH;
        $newH   = (int) round($origH * $ratio);
        $mime   = $file->getMimeType();

        // Chargement source
        $src = match ($mime) {
            'image/png'  => imagecreatefrompng($realPath),
            'image/webp' => imagecreatefromwebp($realPath),
            default      => imagecreatefromjpeg($realPath),
        };

        // Canvas destination
        $dst = imagecreatetruecolor($newW, $newH);

        // Préserver la transparence PNG
        if ($mime === 'image/png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        // Chemin de destination
        $filename = $dir . '/' . uniqid('', true) . '.jpg';
        $fullPath = Storage::disk('public')->path($filename);

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        imagejpeg($dst, $fullPath, self::JPEG_QUALITY);
        imagedestroy($dst);

        return $filename;
    }
}
