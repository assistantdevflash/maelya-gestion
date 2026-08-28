<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CodeVerificationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->email_verified_at) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.verification-email', [
            'joursRestants' => max(0, 3 - $user->created_at->diffInDays(now())),
            'codeEnvoye'    => session('code_envoye', false),
            'expireA'       => $user->code_verification_expire_le,
        ]);
    }

    public function envoyer(Request $request)
    {
        $user = auth()->user();

        if ($user->email_verified_at) {
            return redirect()->route('dashboard.index');
        }

        // Limite : un code toutes les 2 minutes
        if ($user->code_verification_expire_le && $user->code_verification_expire_le->diffInMinutes(now()) < 13) {
            return back()->with('info', 'Un code a déjà été envoyé. Vérifiez votre boîte mail.');
        }

        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $user->update([
            'code_verification_email'      => $code,
            'code_verification_expire_le'  => now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new CodeVerificationEmail($user, $code));

        return back()->with('code_envoye', true);
    }

    public function verifier(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/']], [
            'code.required' => 'Veuillez saisir le code reçu par email.',
            'code.size'     => 'Le code doit contenir exactement 4 chiffres.',
            'code.regex'    => 'Le code ne doit contenir que des chiffres.',
        ]);

        $user = auth()->user();

        if ($user->email_verified_at) {
            return redirect()->route('dashboard.index');
        }

        if (!$user->code_verification_email || $user->code_verification_email !== $request->code) {
            return back()->withErrors(['code' => 'Code incorrect.'])->withInput();
        }

        if (!$user->code_verification_expire_le || $user->code_verification_expire_le->isPast()) {
            return back()->withErrors(['code' => 'Ce code a expiré. Demandez-en un nouveau.'])->withInput();
        }

        $user->update([
            'email_verified_at'            => now(),
            'code_verification_email'      => null,
            'code_verification_expire_le'  => null,
        ]);

        return redirect()->route('dashboard.index')
            ->with('success', '✅ Votre adresse email a été vérifiée avec succès !');
    }
}
