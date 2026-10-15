<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una foto della galleria di una comunicazione soci. Il file sta sul disco privato (`local`) e si raggiunge solo
 * dalla route autenticata `soci.comunicazioni.immagine`, mai da un URL diretto.
 */
class ComunicazioneImage extends Model
{
    protected $fillable = ['comunicazione_id', 'path', 'caption', 'order'];

    public function comunicazione(): BelongsTo
    {
        return $this->belongsTo(ComunicazioneSoci::class, 'comunicazione_id');
    }

    protected function url(): Attribute
    {
        return Attribute::make(get: fn () => route('soci.comunicazioni.immagine', $this));
    }
}
