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
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

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

        try {
            // Créer le client S3 avec la même config que les commandes
            $config = [
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ];

            // Support pour Cloudflare R2 ou autres endpoints
            if (env('AWS_ENDPOINT')) {
                $config['endpoint'] = env('AWS_ENDPOINT');
                $config['use_path_style_endpoint'] = env('AWS_USE_PATH_STYLE_ENDPOINT', false);
            }

            $s3Client = new S3Client($config);
            $bucket = env('AWS_BUCKET');

            // Supprimer l'ancien avatar s'il existe
            if ($user->avatar) {
                $oldPath = str_replace(env('AWS_URL') . '/', '', $user->avatar);
                try {
                    $s3Client->deleteObject([
                        'Bucket' => $bucket,
                        'Key' => $oldPath,
                    ]);
                } catch (AwsException $e) {
                    // Échec silencieux si l'ancien fichier n'existe pas
                }
            }

            // Sauvegarder le nouvel avatar sur S3
            $avatarPath = 'avatars/' . \Str::uuid() . '.jpg';
            $result = $s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $avatarPath,
                'Body' => $request->file('avatar')->getContent(),
                'ACL' => 'public-read',
                'ContentType' => 'image/jpeg',
            ]);

            // Générer l'URL finale
            $baseUrl = env('AWS_URL');
            $avatarUrl = rtrim($baseUrl, '/') . '/' . $avatarPath;
            
            // Mettre à jour l'utilisateur avec l'URL complète
            $user->update([
                'avatar' => $avatarUrl,
            ]);

            return Redirect::route('profile.edit')->with('success', 'Avatar mis à jour avec succès.')->with('refresh', true);

        } catch (\Exception $e) {
            return Redirect::route('profile.edit')->withErrors(['avatar' => 'Erreur lors de l\'upload de l\'avatar: ' . $e->getMessage()]);
        }
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
