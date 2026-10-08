<?php

namespace App\Rules;

use App\Support\CodiceFiscaleInfo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blocca i minorenni (età ricavata dal codice fiscale) nei moduli online: devono rivolgersi alla
 * segreteria. Un codice senza struttura valida è respinto come errore di digitazione.
 */
class Maggiorenne implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $maggiorenne = CodiceFiscaleInfo::maggiorenne((string) $value);

        if ($maggiorenne === null) {
            $fail('Il codice fiscale non risulta corretto: controlla di averlo scritto bene.');
        } elseif (! $maggiorenne) {
            $fail('L\'iscrizione online è riservata ai maggiorenni: per i minorenni contatta la segreteria del Comitato.');
        }
    }
}
