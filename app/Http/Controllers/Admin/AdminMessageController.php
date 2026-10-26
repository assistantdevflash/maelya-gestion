<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageContact;
use Illuminate\Http\Request;

class AdminMessageController extends Controller
{
    public function index(Request $request)
    {
        $statut = $request->input('statut', 'tous');
        $search = trim((string) $request->input('q'));

        $messages = MessageContact::query()
            ->when($statut === 'non_lus', fn ($q) => $q->nonLus())
            ->when($statut === 'lus', fn ($q) => $q->where('lu', true)->where('est_spam', false))
            ->when($statut === 'spam', fn ($q) => $q->spam())
            ->when($search !== '', fn ($q) => $q->where(
                fn ($x) => $x->where('nom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        // Compteurs globaux, indépendants du filtre courant
        $stats = MessageContact::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN lu = 0 AND est_spam = 0 THEN 1 ELSE 0 END), 0) as non_lus')
            ->selectRaw('COALESCE(SUM(CASE WHEN est_spam = 1 THEN 1 ELSE 0 END), 0) as spam')
            ->first();

        return view('admin.messages.index', [
            'messages' => $messages,
            'total'    => (int) $stats->total,
            'nonLus'   => (int) $stats->non_lus,
            'spam'     => (int) $stats->spam,
            'statut'   => $statut,
            'search'   => $search,
        ]);
    }

    public function show(MessageContact $message)
    {
        // Ouvrir un message le marque comme lu (sauf s'il est classé spam)
        if (!$message->lu && !$message->est_spam) {
            $message->update(['lu' => true]);
        }

        $precedent = MessageContact::where('created_at', '<', $message->created_at)
            ->latest('created_at')->first();

        $suivant = MessageContact::where('created_at', '>', $message->created_at)
            ->oldest('created_at')->first();

        return view('admin.messages.show', compact('message', 'precedent', 'suivant'));
    }

    public function marquerLu(MessageContact $message)
    {
        $message->update(['lu' => true, 'est_spam' => false]);

        return back()->with('success', 'Message marqué comme lu.');
    }

    public function marquerToutLu()
    {
        $requete = MessageContact::where('lu', false)->where('est_spam', false);
        $nb = $requete->count();
        $requete->update(['lu' => true]);

        return back()->with('success', $nb . ' message(s) marqué(s) comme lu(s).');
    }

    public function destroy(MessageContact $message)
    {
        $message->delete();

        return redirect()->route('admin.messages.index')
            ->with('success', 'Message supprimé.');
    }

    public function viderSpam()
    {
        $nb = MessageContact::spam()->count();
        MessageContact::spam()->delete();

        return back()->with('success', $nb . ' message(s) indésirable(s) supprimé(s).');
    }
}
