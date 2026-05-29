<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ClientPhotoController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'photos.*'   => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'type'       => ['required', 'in:avant,apres,avant_apres,autre'],
            'legende'    => ['nullable', 'string', 'max:255'],
            'date_prise' => ['nullable', 'date'],
        ]);

        $institutId = session('current_institut_id', Auth::user()->institut_id);

        foreach ($request->file('photos') as $file) {
            $path = $file->store("clients/{$client->id}/photos", 'public');
            ClientPhoto::create([
                'institut_id' => $institutId,
                'client_id'   => $client->id,
                'user_id'     => Auth::id(),
                'type'        => $data['type'],
                'path'        => $path,
                'legende'     => $data['legende'] ?? null,
                'date_prise'  => $data['date_prise'] ?? now()->toDateString(),
            ]);
        }

        return back()->with('success', count($request->file('photos')) . ' photo(s) ajoutée(s).');
    }

    public function destroy(Client $client, ClientPhoto $photo)
    {
        abort_unless($photo->client_id === $client->id, 404);
        abort_unless(Auth::user()->isAdmin(), 403);
        $photo->delete();
        return back()->with('success', 'Photo supprimée.');
    }
}
