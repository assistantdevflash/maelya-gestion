<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CategoriePrestation;
use App\Models\Prestation;
use Illuminate\Http\Request;

class PrestationController extends Controller
{
    public function index(Request $request)
    {
        $search      = $request->input('search');
        $categorieId = $request->input('categorie_id');

        $categoriesForFilter = CategoriePrestation::orderBy('ordre')->orderBy('nom')->get();

        $categories = CategoriePrestation::with(['prestations' => function ($q) use ($search) {
            $q->orderBy('nom');
            if ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('nom', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%");
                });
            }
        }])
        ->when($categorieId, fn ($q) => $q->where('id', $categorieId))
        ->orderBy('ordre')
        ->get();

        if ($search) {
            $categories = $categories->filter(fn ($cat) => $cat->prestations->count() > 0);
        }

        return view('dashboard.prestations.index', compact('categories', 'categoriesForFilter', 'search', 'categorieId'));
    }

    public function create()
    {
        $categories = CategoriePrestation::orderBy('nom')->get();
        return view('dashboard.prestations.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'categorie_prestation_id' => ['required', 'uuid'],
            'nom' => ['required', 'string', 'max:150'],
            'prix' => ['required', 'integer', 'min:0'],
            'duree' => ['nullable', 'integer', 'min:0', 'max:480'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['categorie_id'] = $data['categorie_prestation_id'];
        unset($data['categorie_prestation_id']);

        Prestation::create($data);

        return redirect()->route('dashboard.prestations.index')
            ->with('success', 'Prestation ajoutée.');
    }

    public function edit(Prestation $prestation)
    {
        $categories = CategoriePrestation::orderBy('nom')->get();
        return view('dashboard.prestations.edit', compact('prestation', 'categories'));
    }

    public function update(Request $request, Prestation $prestation)
    {
        $data = $request->validate([
            'categorie_prestation_id' => ['required', 'uuid'],
            'nom' => ['required', 'string', 'max:150'],
            'prix' => ['required', 'integer', 'min:0'],
            'duree' => ['nullable', 'integer', 'min:0', 'max:480'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['categorie_id'] = $data['categorie_prestation_id'];
        unset($data['categorie_prestation_id']);

        $prestation->update($data);

        return redirect()->route('dashboard.prestations.index')
            ->with('success', 'Prestation mise à jour.');
    }

    public function destroy(Prestation $prestation)
    {
        $prestation->delete();
        return redirect()->route('dashboard.prestations.index')
            ->with('success', 'Prestation supprimée.');
    }

    public function toggle(Prestation $prestation)
    {
        $prestation->update(['actif' => !$prestation->actif]);
        return back()->with('success', $prestation->actif ? 'Prestation activée.' : 'Prestation désactivée.');
    }
}
