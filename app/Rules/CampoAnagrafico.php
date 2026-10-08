<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Caratteri ammessi nei campi anagrafici dei moduli (igiene dei dati, oltre alla protezione che già c'è in uscita/su
 * database): niente `< > ; { } = " |` e simili in nomi, indirizzi, telefoni. Whitelist per tipo di campo: nome/cognome/comune
 * (lettere, spazi, apostrofi, punti, trattini), via (anche numeri e , / °), provincia (2 lettere), CAP (5 cifre), telefono.
 */
class CampoAnagrafico implements ValidationRule
{
    private const REGOLE = [
        'nome' => ['/^[\p{L}\p{M}][\p{L}\p{M}\s\'’.\-]*$/u', 'può contenere solo lettere, spazi, apostrofi, punti e trattini.'],
        'via' => ['/^[\p{L}\p{M}\p{N}][\p{L}\p{M}\p{N}\s\'’.,\/°ºª\-]*$/u', 'può contenere solo lettere, numeri, spazi e i simboli . , / \' - °.'],
        'provincia' => ['/^[A-Za-z]{2}$/', 'deve essere la sigla di due lettere (es. AR).'],
        'cap' => ['/^\d{5}$/', 'deve essere di 5 cifre.'],
        'telefono' => ['/^[0-9+\s().\-]{3,30}$/', 'può contenere solo numeri, spazi e i simboli + ( ) . -'],
    ];

    public function __construct(private string $tipo) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        [$pattern, $messaggio] = self::REGOLE[$this->tipo];

        if (! is_string($value) || ! preg_match($pattern, $value)) {
            $fail("Il campo :attribute {$messaggio}");
        }
    }
}
