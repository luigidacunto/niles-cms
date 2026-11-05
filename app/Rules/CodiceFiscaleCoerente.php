<?php

namespace App\Rules;

use App\Support\CodiceFiscaleInfo;
use App\Support\NascitaCorso;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Controlli di coerenza del codice fiscale dei moduli corsi, da aggiungere a `CodiceFiscale` (che resta il solo controllo di
 * formato, usato anche dall'anagrafica membri): lettere e data di nascita sempre; coerenza con nome e cognome del *gruppo di
 * campi fratello* (`nominativi.0.nome`, `richiedente.nome`…) se `$controllaNomi`. I form di segreteria lo disattivano:
 * chi ha il documento davanti può iscrivere anche chi ha un codice emesso con grafia diversa. Dettagli in CodiceFiscaleInfo.
 */
class CodiceFiscaleCoerente implements DataAwareRule, ValidationRule
{
    private array $data = [];

    /** $anagraficaCompleta: tipologie con data/luogo di nascita richiesti → controllo anche del carattere di controllo e della coerenza con data e Italia/estero indicati. */
    public function __construct(private readonly bool $controllaNomi = true, private readonly bool $anagraficaCompleta = false) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $gruppo = Str::beforeLast($attribute, '.');
        $nome = $this->controllaNomi ? Arr::get($this->data, "$gruppo.nome") : null;
        $cognome = $this->controllaNomi ? Arr::get($this->data, "$gruppo.cognome") : null;

        foreach (CodiceFiscaleInfo::incoerenze((string) $value, is_string($nome) ? $nome : null, is_string($cognome) ? $cognome : null) as $problema) {
            // La regola di formato (CodiceFiscale) segnala già la lunghezza: qui solo le incoerenze di contenuto.
            if (! str_starts_with($problema, 'Il codice fiscale deve essere')) {
                $fail($problema);
            }
        }

        if ($this->anagraficaCompleta) {
            $data = Arr::get($this->data, "$gruppo.data_nascita");
            $luogo = Arr::get($this->data, "$gruppo.nascita_luogo");
            try {
                $data = is_string($data) ? Carbon::createFromFormat('!Y-m-d', $data) : null;
            } catch (\Throwable) {
                $data = null;   // data non valida: la segnala la sua regola
            }

            $stato = NascitaCorso::stato((array) Arr::get($this->data, $gruppo, []));
            foreach (CodiceFiscaleInfo::incoerenzeAnagrafica((string) $value, $data ?: null, NascitaCorso::estero(is_string($luogo) ? $luogo : null), is_string($stato) ? $stato : null) as $problema) {
                $fail($problema);
            }
        }
    }
}
