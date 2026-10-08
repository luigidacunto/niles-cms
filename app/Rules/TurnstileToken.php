<?php

namespace App\Rules;

use App\Models\SicurezzaForm;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifica server-side del token Cloudflare Turnstile. Gira solo se il CAPTCHA è attivo e configurato
 * (SicurezzaForm::turnstileAttivo()); altrimenti passa. Il "campo obbligatorio" è a carico di
 * Rule::requiredIf nella FormRequest, questa regola scatta solo se il token è presente.
 *
 * Fail-open: se Cloudflare non risponde o dà un errore (5xx, timeout, rete) la
 * richiesta passa e si scrive un warning — un disservizio esterno non deve bloccare le iscrizioni.
 * Blocca solo se Cloudflare risponde esplicitamente `success: false`.
 */
class TurnstileToken implements ValidationRule
{
    private const ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sicurezza = SicurezzaForm::current();
        if (! $sicurezza->turnstileAttivo()) {
            return;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::ENDPOINT, [
                'secret' => $sicurezza->turnstile_secret_key,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Turnstile non raggiungibile, richiesta lasciata passare: '.$e->getMessage());

            return;
        }

        if ($response->serverError()) {
            Log::warning('Turnstile ha risposto '.$response->status().', richiesta lasciata passare.');

            return;
        }

        if ($response->json('success') !== true) {
            $fail('Verifica anti-robot non superata, riprova.');
        }
    }
}
