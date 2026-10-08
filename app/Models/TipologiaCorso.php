<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TipologiaCorso extends Model
{
    protected $table = 'tipologie_corso';

    /** Tipi di validità dell'attestato: scadenza a fine anno dopo N anni, oppure a data precisa dopo N mesi. */
    public const VALIDITA_TIPI = ['fine_anno' => 'Fine anno, dopo N anni', 'mesi' => 'Data precisa, dopo N mesi'];

    protected $fillable = [
        'nome', 'sigla', 'rilascia_attestato', 'costo_predefinito', 'validita_tipo', 'validita_valore',
        'immagine', 'attivo', 'order',
    ];

    protected function casts(): array
    {
        return [
            'rilascia_attestato' => 'boolean',
            'costo_predefinito' => 'decimal:2',
            'attivo' => 'boolean',
        ];
    }

    public function corsi(): HasMany
    {
        return $this->hasMany(Corso::class);
    }

    /**
     * Scadenza di un attestato rilasciato da un corso di questa tipologia, o null se non scade.
     * `fine_anno`: 31/12 dell'anno del corso + N (corso 10/10/2026, 2 anni → 31/12/2028).
     * `mesi`: data del corso + N mesi (10/10/2026, 24 mesi → 10/10/2028; mai oltre fine mese).
     */
    public function scadenzaDa(CarbonInterface $dataCorso): ?Carbon
    {
        if (! $this->validita_tipo || ! $this->validita_valore) {
            return null;
        }

        return match ($this->validita_tipo) {
            'fine_anno' => Carbon::create($dataCorso->year + $this->validita_valore, 12, 31)->startOfDay(),
            'mesi' => Carbon::parse($dataCorso)->addMonthsNoOverflow($this->validita_valore)->startOfDay(),
            default => null,
        };
    }

    /** Descrizione leggibile della validità, per elenchi e form. */
    public function validitaLabel(): string
    {
        if (! $this->validita_tipo || ! $this->validita_valore) {
            return 'Non scade';
        }

        return $this->validita_tipo === 'mesi'
            ? "{$this->validita_valore} mesi dalla data del corso"
            : "Fine anno, dopo {$this->validita_valore} ".($this->validita_valore === 1 ? 'anno' : 'anni');
    }

    /** URL dell'immagine di default della tipologia, null se non impostata (chi la usa decide il fallback). */
    protected function immagineUrl(): Attribute
    {
        return Attribute::get(fn () => $this->immagine ? Storage::url($this->immagine) : null);
    }
}
