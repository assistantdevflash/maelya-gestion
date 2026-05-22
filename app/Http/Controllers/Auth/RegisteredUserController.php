<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\BienvenueMaelya;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'website' => ['max:0'], // Honeypot : doit rester vide
        ], [
            'website.max' => 'Une erreur est survenue. Veuillez réessayer.',
        ]);

        $user = User::create([
            'name'        => $request->name,
            'prenom'      => $request->name,
            'nom_famille' => '',
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // Email de bienvenue
        Mail::to($user->email)->send(new BienvenueMaelya($user));
        try {
            app(\App\Services\PushNotificationService::class)->sendToUser(
                $user,
                '🎉 Bienvenue sur ' . config('app.name') . ' !',
                'Votre compte est créé. Commencez par configurer votre établissement.',
                '/dashboard'
            );
        } catch (\Throwable $e) { \Log::warning('[Push Bienvenue] ' . $e->getMessage()); }
        \App\Services\NotificationService::notifyUser(
            $user,
            'bienvenue',
            '🎉 Bienvenue sur ' . config('app.name') . ' !',
            'Votre compte est créé. Commencez par configurer votre établissement.',
            '/dashboard'
        );

        Auth::login($user);

        return redirect(route('dashboard.index', absolute: false));
    }
}
