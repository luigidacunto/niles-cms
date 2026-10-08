<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IscrizioneCorso extends Model
{
    protected $table = 'iscrizioni_corso';

    protected $fillable = [
        'corso_id', 'dati_fatturazione_corso_id', 'persona_id', 'referente_persona_id', 'nome', 'cognome', 'email',
        'telefono', 'codice_fiscale', 'via', 'comune', 'provincia', 'cap',
        'privacy_accettata_at', 'autodichiarazione_terzi_at', 'presenza_confermata_at', 'ritirata_at', 'nota_ritiro',
    ];

    protected function casts(): array
    {
        return [
            'privacy_accettata_at' => 'datetime',
            'autodichiarazione_terzi_at' => 'datetime',
            'presenza_confermata_at' => 'datetime',
            'ritirata_at' => 'datetime',
        ];
    }

    /** Ritirato dal corso (non frequenta più): la riga resta per storico e conteggi, ma è fuori da elenchi operativi, email e pagamenti. */
    public function ritirata(): bool
    {
        return $this->ritirata_at !== null;
    }

    public function scopeAttive(Builder $query): void
    {
        $query->whereNull('ritirata_at');
    }

    public function scopeRitirate(Builder $query): void
    {
        $query->whereNotNull('ritirata_at');
    }

    /** La presenza si può confermare finché il corso non si è svolto né annullato e l'iscritto non si è ritirato. */
    public function presenzaConfermabile(): bool
    {
        return ! $this->ritirata() && ! $this->corso->annullato() && ! $this->corso->data_fine->isPast();
    }

    public function corso(): BelongsTo
    {
        return $this->belongsTo(Corso::class);
    }

    public function datiFatturazione(): BelongsTo
    {
        return $this->belongsTo(DatiFatturazioneCorso::class, 'dati_fatturazione_corso_id');
    }

    /** Chi frequenta il corso. */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /** Chi ha compilato il modulo per conto dell'iscritto; null se l'iscritto si è iscritto da solo. */
    public function referente(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'referente_persona_id');
    }
}
