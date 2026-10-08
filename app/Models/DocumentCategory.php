<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    /** Scope riservato: allegati dell'area soci, su disco privato (vedi App\Support\AreaSoci). */
    public const SCOPE_SOCI = 'soci';

    protected $fillable = ['name', 'slug', 'description', 'order', 'scope', 'selectable'];

    protected $casts = [
        'selectable' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
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
