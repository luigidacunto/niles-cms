<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riga singola (id=1) con le impostazioni della sezione Struttura Organizzativa.
 * Usare `BoardSetting::current()` per ottenerla (la crea se manca).
 */
class BoardSetting extends Model
{
    protected $fillable = ['organigramma_document_id'];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public function organigramma(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'organigramma_document_id');
    }
}
