<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro dei consensi: una riga per ogni evento (richiesto/confermato/revocato), mai modificata né
 * cancellata dall'applicazione. Si scrive solo tramite Persona::registraConsenso(), che aggiorna anche lo
 * stato attuale sulla persona.
 */
class Consenso extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'consensi';

    protected $fillable = ['persona_id', 'finalita', 'evento', 'origine', 'iscrizione_corso_id', 'testo'];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
