<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EtablissementType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminEtablissementTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $types = EtablissementType::ordered()->get();
        return view('admin.etablissement-types.index', compact('types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.etablissement-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:200',
            'code' => 'nullable|string|max:100|unique:etablissement_types,code',
            'position' => 'nullable|integer|min:0',
            'actif' => 'nullable|boolean',
        ]);

        // Générer le code automatiquement depuis le libellé si absent
        if (empty($validated['code'])) {
            $validated['code'] = Str::slug(Str::ascii($validated['libelle']), '_');
        }

        // Valeurs par défaut
        $validated['actif'] = $request->has('actif');
        $validated['position'] = $validated['position'] ?? 0;

        EtablissementType::create($validated);

        return redirect()->route('admin.etablissement-types.index')
            ->with('success', 'Type d\'établissement créé avec succès.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EtablissementType $etablissementType)
    {
        return view('admin.etablissement-types.edit', compact('etablissementType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EtablissementType $etablissementType)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:200',
            'code' => 'required|string|max:100|unique:etablissement_types,code,' . $etablissementType->id,
            'position' => 'nullable|integer|min:0',
            'actif' => 'nullable|boolean',
        ]);

        $validated['actif'] = $request->has('actif');
        $validated['position'] = $validated['position'] ?? 0;

        $etablissementType->update($validated);

        return redirect()->route('admin.etablissement-types.index')
            ->with('success', 'Type d\'établissement modifié avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EtablissementType $etablissementType)
    {
        $etablissementType->delete();

        return redirect()->route('admin.etablissement-types.index')
            ->with('success', 'Type d\'établissement supprimé avec succès.');
    }

    /**
     * Toggle actif status via AJAX
     */
    public function toggle(EtablissementType $etablissementType)
    {
        $etablissementType->update(['actif' => !$etablissementType->actif]);
        
        return response()->json([
            'success' => true,
            'actif' => $etablissementType->actif
        ]);
    }
}
