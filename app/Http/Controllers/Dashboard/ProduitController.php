<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CategorieProduit;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
        $produits = Produit::with('categorie')
            ->when($request->input('q'), fn($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            }))
            ->when($request->input('categorie'), fn($q, $catId) => $q->where('categorie_id', $catId))
            ->orderBy('nom')
            ->paginate(25)
            ->withQueryString();
        $categories = CategorieProduit::orderBy('nom')->get();
        return view('dashboard.produits.index', compact('produits', 'categories'));
    }

    public function create()
    {
        $categories = CategorieProduit::orderBy('nom')->get();
        return view('dashboard.produits.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'categorie_id' => ['nullable', 'uuid'],
            'reference' => ['nullable', 'string', 'max:50'],
            'prix_achat' => ['nullable', 'integer', 'min:0'],
            'prix_vente' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'seuil_alerte' => ['required', 'integer', 'min:0'],
            'unite' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if (isset($data['prix_achat']) && $data['prix_vente'] < $data['prix_achat']) {
            return back()->withErrors(['prix_vente' => 'Le prix de vente ne peut pas être inférieur au prix d\'achat.'])->withInput();
        }

        Produit::create($data);

        return redirect()->route('dashboard.produits.index')
            ->with('success', 'Produit ajouté.');
    }

    public function edit(Produit $produit)
    {
        $categories = CategorieProduit::orderBy('nom')->get();
        return view('dashboard.produits.edit', compact('produit', 'categories'));
    }

    public function update(Request $request, Produit $produit)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'categorie_id' => ['nullable', 'uuid'],
            'reference' => ['nullable', 'string', 'max:50'],
            'prix_achat' => ['nullable', 'integer', 'min:0'],
            'prix_vente' => ['required', 'integer', 'min:0'],
            'seuil_alerte' => ['required', 'integer', 'min:0'],
            'unite' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if (isset($data['prix_achat']) && $data['prix_vente'] < $data['prix_achat']) {
            return back()->withErrors(['prix_vente' => 'Le prix de vente ne peut pas être inférieur au prix d\'achat.'])->withInput();
        }

        $produit->update($data);

        return redirect()->route('dashboard.produits.index')
            ->with('success', 'Produit mis à jour.');
    }

    public function destroy(Produit $produit)
    {
        $produit->delete();
        return redirect()->route('dashboard.produits.index')
            ->with('success', 'Produit supprimé.');
    }
}
