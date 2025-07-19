<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Vérification manuelle case-insensitive pour le name (si il est modifié)
        if ($request->user()->name !== $request->name) {
            if (User::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->exists()) {
                return back()->withErrors(['name' => 'Ce pseudo est déjà utilisé (la casse ne compte pas).']);
            }
        }

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's avatar.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
        ]);

        $user = $request->user();

        // Supprimer l'ancien avatar s'il existe (extraire le path de l'URL)
        if ($user->avatar) {
            $oldPath = str_replace(Storage::disk('s3')->url(''), '', $user->avatar);
            if (Storage::disk('s3')->exists($oldPath)) {
                Storage::disk('s3')->delete($oldPath);
            }
        }

        // Sauvegarder le nouvel avatar sur S3
        $avatarPath = 'avatars/' . \Str::uuid() . '.jpg';
        Storage::disk('s3')->put($avatarPath, $request->file('avatar')->getContent());
        
        // Mettre à jour l'utilisateur avec l'URL complète
        $user->update([
            'avatar' => Storage::disk('s3')->url($avatarPath),
        ]);

        return Redirect::route('profile.edit')->with('success', 'Avatar mis à jour avec succès.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
