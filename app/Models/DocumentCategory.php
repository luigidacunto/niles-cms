<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    use HasUniqueSlug;

    /** Scope riservato: allegati dell'area soci, su disco privato (vedi App\Support\AreaSoci). */
    public const SCOPE_SOCI = 'soci';

    /** Categoria riservata di sistema «Senza categoria»: i suoi documenti sono visibili a tutti i soci. */
    public const SLUG_GENERALE = 'riservati-soci';

    protected $fillable = ['name', 'slug', 'description', 'order', 'scope', 'selectable'];

    protected $casts = [
        'selectable' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** Soci a cui è stata assegnata questa categoria riservata (la «generale» non ne ha bisogno). */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_document_category');
    }

    public function isGenerale(): bool
    {
        return $this->scope === self::SCOPE_SOCI && $this->slug === self::SLUG_GENERALE;
    }

    /** Nome mostrato: la categoria di sistema dei documenti aperti a tutti i soci si chiama «Senza categoria». */
    public function label(): string
    {
        return $this->isGenerale() ? 'Senza categoria' : $this->name;
    }

    public function scopeTrasparenza(Builder $query): void
    {
        $query->where('scope', 'trasparenza');
    }

    public function scopeSoci(Builder $query): void
    {
        $query->where('scope', self::SCOPE_SOCI);
    }

    public function scopeLibrary(Builder $query): void
    {
        $query->where('scope', 'library');
    }
}
