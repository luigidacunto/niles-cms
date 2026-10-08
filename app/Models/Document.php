<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'document_category_id', 'title', 'description', 'type', 'file_path', 'original_filename',
        'file_size', 'published_at', 'published',
    ];

    protected $casts = [
        'published_at' => 'date',
        'published' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    /** Allegato dell'area soci (categoria scope `soci`): file su disco privato, mai raggiungibile da URL diretto. */
    public function isRiservato(): bool
    {
        return $this->category?->scope === DocumentCategory::SCOPE_SOCI;
    }

    /** Disco su cui sta il file: `local` (privato) per i riservati, `public` per tutti gli altri. */
    public function disk(): string
    {
        return $this->isRiservato() ? 'local' : 'public';
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('published', true);
    }

    /**
     * Unico punto che costruisce l'URL del documento — le view non lo fanno da sole (come
     * Post::coverUrl). Punta alla route `documenti.show`, non al file grezzo su disco, così il
     * download/preview usa `downloadName` (nome leggibile dal titolo).
     */
    protected function downloadUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => route('documenti.show', $this),
        );
    }

    /** Estensione reale del file su disco (pdf, docx, ...). */
    protected function extension(): Attribute
    {
        return Attribute::make(
            get: fn () => strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION)) ?: 'bin',
        );
    }

    /**
     * Nome "pulito" con cui il browser salva/mostra il file: dal titolo, tolti solo i caratteri non
     * validi per un nome file, con l'estensione reale. `FilesystemAdapter::response()` gestisce la
     * codifica RFC 6266.
     */
    protected function downloadName(): Attribute
    {
        return Attribute::make(get: function () {
            $base = str_replace(['/', '\\'], '-', (string) $this->title);
            $base = preg_replace('/[:*?"<>|\x00-\x1F]+/', '', $base);
            $base = trim(preg_replace('/\s+/', ' ', $base));

            return mb_substr($base !== '' ? $base : 'documento', 0, 120).'.'.$this->extension;
        });
    }

    /** I formati che il browser può mostrare inline; gli altri vanno forzati in download. */
    public function servedInline(): bool
    {
        return in_array($this->extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'txt'], true);
    }

    /**
     * Colore automatico dell'icona-documento per estensione, usato negli "Allegati" di post/pagine
     * (vedi `Post::attachments()` / `Page::attachments()`). Un'unica forma di icona condivisa
     * (`<x-file-icon>`, foglio con angolo ripiegato) tinta con questa classe Tailwind — non un'icona
     * diversa per tipo, solo il colore cambia (più la sigla dell'estensione dentro l'icona stessa).
     */
    protected function iconColorClass(): Attribute
    {
        return Attribute::make(get: fn () => match ($this->extension) {
            'pdf' => 'text-red-600',
            'doc', 'docx', 'odt' => 'text-blue-600',
            'xls', 'xlsx', 'ods', 'csv' => 'text-green-600',
            'ppt', 'pptx', 'odp' => 'text-orange-500',
            'zip' => 'text-yellow-600',
            default => 'text-gray-500',
        });
    }

    /** Icona Font Awesome equivalente per l'admin (AdminLTE la include già, nessuna dipendenza nuova). */
    protected function faIcon(): Attribute
    {
        return Attribute::make(get: fn () => match ($this->extension) {
            'pdf' => 'fa-file-pdf',
            'doc', 'docx', 'odt' => 'fa-file-word',
            'xls', 'xlsx', 'ods', 'csv' => 'fa-file-excel',
            'ppt', 'pptx', 'odp' => 'fa-file-powerpoint',
            'zip' => 'fa-file-archive',
            default => 'fa-file',
        });
    }

    /** Stessa mappatura di iconColorClass(), in classi colore Bootstrap (admin AdminLTE, non Tailwind). */
    protected function bootstrapColorClass(): Attribute
    {
        return Attribute::make(get: fn () => match ($this->extension) {
            'pdf' => 'text-danger',
            'doc', 'docx', 'odt' => 'text-primary',
            'xls', 'xlsx', 'ods', 'csv' => 'text-success',
            'ppt', 'pptx', 'odp' => 'text-warning',
            'zip' => 'text-info',
            default => 'text-secondary',
        });
    }

    protected function humanSize(): Attribute
    {
        return Attribute::make(get: function () {
            $bytes = (int) $this->file_size;
            if ($bytes <= 0) {
                return null;
            }
            $units = ['B', 'KB', 'MB', 'GB'];
            $i = (int) floor(log($bytes, 1024));
            $i = min($i, count($units) - 1);

            return round($bytes / (1024 ** $i), $i ? 1 : 0).' '.$units[$i];
        });
    }

    protected function year(): Attribute
    {
        return Attribute::make(get: fn () => $this->published_at?->year);
    }
}
