<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentAttachments;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\PurifiesBody;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Comunicazione interna riservata ai soci (area soci). Come un Post ma senza categoria/tag/copertina/galleria;
 * gli allegati sono Document della categoria riservata (scope `soci`, disco privato).
 */
class ComunicazioneSoci extends Model
{
    use HasDocumentAttachments;
    use HasUniqueSlug;
    use PurifiesBody;

    protected $table = 'comunicazioni_soci';

    protected $fillable = ['slug', 'title', 'excerpt', 'body', 'published', 'published_at', 'last_edited_at', 'author_name'];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
        'last_edited_at' => 'datetime',
    ];

    public function scopeVisibili(Builder $query): void
    {
        $query->where('published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }
}
