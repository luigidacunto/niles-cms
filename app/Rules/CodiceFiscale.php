<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Controllo di formato (16 alfanumerici), non il calcolo del carattere di controllo ufficiale — se
 * in futuro servisse una validazione più rigorosa, si estende qui senza toccare i form che la usano.
 */
class CodiceFiscale implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[A-Za-z0-9]{16}$/', (string) $value)) {
            $fail('Il codice fiscale deve essere di 16 caratteri alfanumerici.');
        }
    }
}
