<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Anagrafica trasversale della popolazione dei corsi, una riga per codice fiscale (anche per il referente
 * che iscrive altri). Base per promemoria scadenza attestati e newsletter — da inviare solo con consenso
 * `confermato` per la rispettiva finalità.
 */
class Persona extends Model
{
    protected $table = 'persone';

    public const FINALITA = ['promemoria', 'newsletter'];

    public const EVENTI = ['richiesto', 'confermato', 'revocato'];

    protected $fillable = ['nome', 'cognome', 'codice_fiscale', 'email', 'cellulare'];

    protected function casts(): array
    {
        return [
            'promemoria_stato_at' => 'datetime',
            'newsletter_stato_at' => 'datetime',
            'richiesta_cancellazione_at' => 'datetime',
            'anonimizzata_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Token casuale non indovinabile per i link personali (conferma consensi, disiscrizione) senza login.
        static::creating(function (self $persona) {
            $persona->token ??= Str::random(64);
        });
    }

    public function iscrizioni(): HasMany
    {
        return $this->hasMany(IscrizioneCorso::class);
    }

    public function consensi(): HasMany
    {
        return $this->hasMany(Consenso::class);
    }

    /**
     * Crea o aggiorna la persona dai dati di un modulo (iscritto o referente). Il codice fiscale è la
     * chiave; nome/cognome/contatti si aggiornano sempre col valore più recente. Non tocca i consensi:
     * restano gestiti solo da registraConsenso() (una spunta non barrata non revoca nulla).
     *
     * @param  array{nome:string,cognome:string,codice_fiscale:string,email:string,telefono?:?string}  $dati
     */
    public static function daDati(array $dati): self
    {
        // Il secondo argomento di firstOrCreate() è indispensabile: senza, la create() sottostante
        // riceve solo 'codice_fiscale' e l'insert fallisce (nome/cognome NOT NULL a DB).
        $persona = static::firstOrCreate(
            ['codice_fiscale' => strtoupper($dati['codice_fiscale'])],
            ['nome' => $dati['nome'], 'cognome' => $dati['cognome']]
        );

        $persona->nome = $dati['nome'];
        $persona->cognome = $dati['cognome'];
        $persona->email = $dati['email'];
        $persona->cellulare = $dati['telefono'] ?? null;
        $persona->save();

        return $persona;
    }

    /**
     * Unico punto in cui un consenso cambia: scrive l'evento nel registro e aggiorna lo stato attuale sulla
     * persona, nella stessa transazione. Un `richiesto` non declassa un consenso già `confermato`.
     */
    public function registraConsenso(string $finalita, string $evento, string $origine, ?IscrizioneCorso $iscrizione = null): void
    {
        if (! in_array($finalita, self::FINALITA, true) || ! in_array($evento, self::EVENTI, true)) {
            throw new InvalidArgumentException("Consenso non valido: {$finalita}/{$evento}");
        }

        if ($evento === 'richiesto' && $this->statoConsenso($finalita) === 'confermato') {
            return;
        }

        DB::transaction(function () use ($finalita, $evento, $origine, $iscrizione) {
            $this->consensi()->create([
                'finalita' => $finalita,
                'evento' => $evento,
                'origine' => $origine,
                'iscrizione_corso_id' => $iscrizione?->id,
                'testo' => $evento === 'revocato' ? null : config("consensi.{$finalita}.testo"),
            ]);

            $this->forceFill(["{$finalita}_stato" => $evento, "{$finalita}_stato_at" => now()])->save();
        });
    }

    public function statoConsenso(string $finalita): ?string
    {
        return $this->{"{$finalita}_stato"};
    }

    public function haConsenso(string $finalita): bool
    {
        return $this->statoConsenso($finalita) === 'confermato';
    }

    public function anonimizzata(): bool
    {
        return $this->anonimizzata_at !== null;
    }

    /**
     * Anonimizza i dati personali (cancellazione richiesta dall'interessato). ⚠️ Irreversibile. Si anonimizzano la
     * persona e le copie dei suoi dati nelle iscrizioni; la riga persona e quelle delle iscrizioni restano, così
     * partecipazioni e statistiche dei corsi tornano ancora e i collegamenti (iscrizione → persona, referente) puntano
     * a dati ormai anonimi. **I dati di fatturazione (`dati_fatturazione_corso`) non si toccano mai**: fatture emesse da
     * conservare per obbligo di legge. Registro dei consensi azzerato, link personale invalidato (nuovo token).
     * Idempotente.
     */
    public function anonimizza(): void
    {
        if ($this->anonimizzata()) {
            return;
        }

        DB::transaction(function () {
            // CF segnaposto unico e di 16 caratteri (il campo è unico e obbligatorio): ANONIMO + id su 9 cifre.
            $cf = 'ANONIMO'.str_pad((string) $this->id, 9, '0', STR_PAD_LEFT);

            $this->iscrizioni()->update([
                'nome' => 'Anonimo', 'cognome' => 'Anonimo', 'email' => 'anonimo@example.invalid', 'telefono' => null,
                'codice_fiscale' => $cf, 'via' => null, 'comune' => null, 'provincia' => null, 'cap' => null,
            ]);

            $this->consensi()->delete();

            $this->forceFill([
                'nome' => 'Anonimo', 'cognome' => 'Anonimo', 'codice_fiscale' => $cf, 'email' => null, 'cellulare' => null,
                'token' => Str::random(64),
                'promemoria_stato' => null, 'promemoria_stato_at' => null, 'newsletter_stato' => null, 'newsletter_stato_at' => null,
                'anonimizzata_at' => now(),
            ])->save();
        });
    }

    /** Persone a cui si può scrivere per questa finalità (consenso attuale `confermato`). */
    public function scopeConConsenso(Builder $query, string $finalita): void
    {
        $query->where("{$finalita}_stato", 'confermato');
    }
}
