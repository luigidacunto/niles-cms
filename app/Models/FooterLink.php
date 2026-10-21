<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Link della terza colonna del footer pubblico («Link utili»), gestiti da Amministrazione → Link del footer.
 * Nessun valore predefinito: la colonna compare solo se c'è almeno un link.
 */
class FooterLink extends Model
{
    protected $fillable = ['label', 'url', 'order'];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('id');
    }

    /** Un indirizzo che non comincia con "/" è un sito esterno: si apre in una nuova scheda. */
    public function isEsterno(): bool
    {
        return ! str_starts_with($this->url, '/');
    }
}
