<?php

namespace App\Support;

use App\Models\Stato;
use App\Rules\CampoAnagrafico;
use Illuminate\Validation\Rule;

/**
 * Data e luogo di nascita richiesti dalle tipologie con «anagrafica completa» (per l'attestato).
 *
 * Il campo `nascita_luogo` del modulo vale `italia`, l'id di uno Stato estero (tabella `stati`) oppure `altro` (Stato non in elenco,
 * con testo libero in `nascita_stato_altro`). Comune/città è sempre richiesto; la provincia solo per l'Italia: per l'estero è
 * sempre «EE» (convenzione anagrafica italiana), impostata qui e non modificabile. Regole e mappatura sulle colonne di
 * `iscrizioni_corso` in un solo punto, usate dal modulo pubblico e dai form di segreteria.
 */
class NascitaCorso
{
    public const PROVINCIA_ESTERO = 'EE';

    /** @param string $gruppo prefisso dei campi: `nominativi.*` (modulo pubblico) o `nominativo` (segreteria) */
    public static function regole(string $gruppo): array
    {
        $valori = ['italia', 'altro', ...Stato::attivi()->pluck('id')->map(fn ($id) => (string) $id)->all()];

        return [
            "$gruppo.data_nascita" => ['required', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            "$gruppo.nascita_luogo" => ['required', Rule::in($valori)],
            "$gruppo.nascita_stato_altro" => ["required_if:$gruppo.nascita_luogo,altro", 'nullable', 'string', 'max:100', new CampoAnagrafico('nome')],
            "$gruppo.nascita_comune" => ['required', 'string', 'max:255', new CampoAnagrafico('nome')],
            "$gruppo.nascita_provincia" => ["required_if:$gruppo.nascita_luogo,italia", 'nullable', 'string', new CampoAnagrafico('provincia')],
        ];
    }

    /** Nato all'estero? (qualunque valore di `nascita_luogo` diverso da `italia`). */
    public static function estero(?string $luogo): ?bool
    {
        return $luogo === null || $luogo === '' ? null : $luogo !== 'italia';
    }

    /** Colonne di IscrizioneCorso dai campi del modulo; [] se non richiesto. */
    public static function colonne(array $n, bool $richiesta): array
    {
        if (! $richiesta) {
            return [];
        }

        $luogo = (string) ($n['nascita_luogo'] ?? 'italia');
        $estero = $luogo !== 'italia';
        $stato = self::stato($n);

        return [
            'data_nascita' => $n['data_nascita'],
            'nascita_estero' => $estero,
            'nascita_comune' => $n['nascita_comune'] ?? null,
            'nascita_provincia' => $estero ? self::PROVINCIA_ESTERO : ($n['nascita_provincia'] ?? null),
            'nascita_stato' => $stato,
        ];
    }

    /** Nome dello Stato estero dai campi del modulo (dall'elenco o scritto a mano); null per l'Italia o se manca. */
    public static function stato(array $n): ?string
    {
        $luogo = (string) ($n['nascita_luogo'] ?? 'italia');
        if ($luogo === 'italia' || $luogo === '') {
            return null;
        }

        return $luogo === 'altro' ? ($n['nascita_stato_altro'] ?? null) : Stato::find($luogo)?->nome;
    }

    /** «Bologna (BO)» / «Tirana (EE), Albania» / null se non raccolto. */
    public static function etichetta(?bool $estero, ?string $comune, ?string $provincia, ?string $stato): ?string
    {
        if ($estero === null) {
            return null;
        }

        $luogo = trim("{$comune}".($provincia ? " ({$provincia})" : ''));

        return $estero && $stato ? trim("{$luogo}, {$stato}", ' ,') : $luogo;
    }
}
