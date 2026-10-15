<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentAttachments;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\PurifiesBody;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Comunicazione interna riservata ai soci (area soci). Come un Post ma senza categoria/tag. Copertina e galleria
 * stanno sul disco privato; gli allegati sono Document riservati (categorie con scope `soci`) già in archivio.
 */
class ComunicazioneSoci extends Model
{
    use HasDocumentAttachments;
    use HasUniqueSlug;
    use PurifiesBody;

    protected $table = 'comunicazioni_soci';

    protected $fillable = ['slug', 'title', 'excerpt', 'cover_image', 'body', 'published', 'published_at', 'last_edited_at', 'author_name'];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
        'last_edited_at' => 'datetime',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(ComunicazioneImage::class, 'comunicazione_id')->orderBy('order')->orderBy('id');
    }

    /** URL della copertina (route autenticata), null se assente. */
    public function coverUrl(): ?string
    {
        return $this->cover_image ? route('soci.comunicazioni.copertina', $this) : null;
    }

    public function scopeVisibili(Builder $query): void
    {
        $query->where('published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }
}
