<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Controllo di formato (11 cifre), non il calcolo del carattere di controllo ufficiale. */
class PartitaIva implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\d{11}$/', (string) $value)) {
            $fail('La partita IVA deve essere di 11 cifre.');
        }
    }
}
