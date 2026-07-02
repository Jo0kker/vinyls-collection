<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecaptchaVerifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, RecaptchaVerifier $recaptcha): RedirectResponse
    {
        // Vérification manuelle case-insensitive pour le name
        if (User::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->exists()) {
            return back()->withErrors(['name' => 'Ce pseudo est déjà utilisé (la casse ne compte pas).'])->withInput();
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:'.User::class,
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'recaptcha_token' => $recaptcha->enabled() ? ['required', 'string'] : ['nullable', 'string'],
        ]);

        if (! $recaptcha->verify($request->string('recaptcha_token')->toString(), 'register', $request->ip())) {
            return back()
                ->withErrors(['recaptcha_token' => 'La vérification anti-spam a échoué. Merci de réessayer.'])
                ->withInput($request->except('password', 'password_confirmation', 'recaptcha_token'));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assigner le rôle 'user' par défaut
        $user->assignRole('user');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
