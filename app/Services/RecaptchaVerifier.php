<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaVerifier
{
    public function enabled(): bool
    {
        return (bool) config('services.recaptcha.enabled', false)
            && filled(config('services.recaptcha.site_key'))
            && filled(config('services.recaptcha.secret_key'));
    }

    public function siteKey(): ?string
    {
        return config('services.recaptcha.site_key');
    }

    public function verify(?string $token, string $expectedAction, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', array_filter([
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification failed with an HTTP error.', [
                'action' => $expectedAction,
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $response->ok()) {
            return false;
        }

        $payload = $response->json();
        $minimumScore = (float) config('services.recaptcha.minimum_score', 0.5);

        return ($payload['success'] ?? false) === true
            && ($payload['action'] ?? null) === $expectedAction
            && (float) ($payload['score'] ?? 0) >= $minimumScore;
    }
}
