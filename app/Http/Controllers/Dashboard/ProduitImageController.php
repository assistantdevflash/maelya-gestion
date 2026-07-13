<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\ProduitImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProduitImageController extends Controller
{
    /**
     * Retourner les images en JSON (pour le chargement AJAX dans la modale)
     */
    public function indexJson(Produit $produit)
    {
        return response()->json(
            $produit->images()->get()->map(fn($img) => [
                'id' => $img->id,
                'url' => asset('storage/' . $img->chemin),
                'is_principale' => $img->is_principale,
                'ordre' => $img->ordre,
            ])
        );
    }

    /**
     * Ajouter une ou plusieurs images à un produit (max 5 au total) — réponse JSON
     */
    public function store(Request $request, Produit $produit)
    {
        $request->validate([
            'images'   => 'required|array|min:1|max:5',
            'images.*' => 'required|image|max:5120', // 5 Mo max
        ]);

        $existingCount = $produit->images()->count();
        $newCount      = count($request->file('images'));

        if ($existingCount + $newCount > 5) {
            return response()->json([
                'error' => "Maximum 5 images par produit. Ce produit en a déjà {$existingCount}.",
            ], 422);
        }

        $nextOrdre = $produit->images()->max('ordre') + 1;
        $isFirst   = $existingCount === 0 && $produit->photo === null;
        $created   = [];

        foreach ($request->file('images') as $i => $file) {
            $chemin = $file->store('produits/galerie', 'public');
            $img = ProduitImage::create([
                'produit_id'   => $produit->id,
                'chemin'       => $chemin,
                'ordre'        => $nextOrdre + $i,
                'is_principale' => ($isFirst && $i === 0),
            ]);
            $created[] = [
                'id'           => $img->id,
                'url'          => asset('storage/' . $img->chemin),
                'is_principale' => $img->is_principale,
            ];
        }

        $this->clearCaches($produit);

        return response()->json(['images' => $created]);
    }

    /**
     * Supprimer une image — réponse JSON
     */
    public function destroy(Produit $produit, ProduitImage $image)
    {
        if ($image->produit_id !== $produit->id) abort(403);

        $wasPrincipale = $image->is_principale;
        Storage::disk('public')->delete($image->chemin);
        $image->delete();

        $newPrincipale = null;
        if ($wasPrincipale) {
            $next = $produit->images()->orderBy('ordre')->first();
            if ($next) {
                $next->update(['is_principale' => true]);
                $newPrincipale = $next->id;
            }
        }

        $this->clearCaches($produit);
        return response()->json(['ok' => true, 'new_principale_id' => $newPrincipale]);
    }

    /**
     * Définir une image comme image principale — réponse JSON
     */
    public function setPrincipale(Produit $produit, ProduitImage $image)
    {
        if ($image->produit_id !== $produit->id) abort(403);

        $produit->images()->update(['is_principale' => false]);
        $image->update(['is_principale' => true]);

        $this->clearCaches($produit);
        return response()->json(['ok' => true]);
    }

    /**
     * Ajouter une ou plusieurs images à un produit (max 5 au total) — réponse JSON
     */
    public function store(Request $request, Produit $produit)
    {
        $request->validate([
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'required|image|max:2048',
        ]);

        $existingCount = $produit->images()->count();
        $newCount = count($request->file('images'));

        if ($existingCount + $newCount > 5) {
            return back()->with('error', "Maximum 5 images par produit. Ce produit en a déjà {$existingCount}.");
        }

        $nextOrdre = $produit->images()->max('ordre') + 1;
        $isFirst = $existingCount === 0 && $produit->photo === null;

        foreach ($request->file('images') as $i => $file) {
            $chemin = $file->store('produits/galerie', 'public');
            ProduitImage::create([
                'produit_id' => $produit->id,
                'chemin' => $chemin,
                'ordre' => $nextOrdre + $i,
                'is_principale' => ($isFirst && $i === 0),
            ]);
        }

        $this->clearCaches($produit);

        return back()->with('success', "{$newCount} image(s) ajoutée(s).");
    }

    /**
     * Supprimer une image
     */
    public function destroy(Produit $produit, ProduitImage $image)
    {
        if ($image->produit_id !== $produit->id) {
            abort(403);
        }

        $wasPrincipale = $image->is_principale;
        Storage::disk('public')->delete($image->chemin);
        $image->delete();

        // Si on a supprimé la principale, on met la suivante comme principale
        if ($wasPrincipale) {
            $next = $produit->images()->orderBy('ordre')->first();
            if ($next) {
                $next->update(['is_principale' => true]);
            }
        }

        $this->clearCaches($produit);
        return back()->with('success', 'Image supprimée.');
    }

    /**
     * Définir une image comme image principale (couverture)
     */
    public function setPrincipale(Produit $produit, ProduitImage $image)
    {
        if ($image->produit_id !== $produit->id) {
            abort(403);
        }

        // Retirer le flag principale de toutes les autres
        $produit->images()->update(['is_principale' => false]);
        $image->update(['is_principale' => true]);

        $this->clearCaches($produit);
        return back()->with('success', 'Image de couverture mise à jour.');
    }

    private function clearCaches(Produit $produit): void
    {
        Cache::forget('boutique_' . $produit->institut_id . '_produits');
        Cache::forget('caisse_catalog_' . $produit->institut_id);
    }
}
