<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentAttachments;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\PurifiesBody;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasDocumentAttachments;
    use HasUniqueSlug;
    use PurifiesBody;

    protected $fillable = [
        'category_id', 'slug', 'title', 'subtitle', 'excerpt', 'body', 'cover_image',
        'published', 'published_at', 'last_edited_at', 'author_name',
    ];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
        'last_edited_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** Foto della galleria del post, già ordinate (campo `order`, poi id). */
    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class)->orderBy('order')->orderBy('id');
    }

    /**
     * Punto unico che trasforma il percorso relativo salvato in un URL servibile: viste e controller non
     * devono mai costruirlo da sé, così un futuro cambio di disco o CDN è una modifica di una riga qui e non
     * una caccia in tutti i file blade. Restituisce sempre qualcosa di mostrabile: senza copertina ripiega
     * su un segnaposto generico (la copertina è facoltativa: non ogni post ha una foto sensata, es. un
     * comunicato stampa), così negli elenchi non resta mai un buco. Le viste non devono controllare da sole
     * la mancanza della copertina.
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cover_image ? Storage::url($this->cover_image) : asset('images/post-cover-placeholder.svg'),
        );
    }

}
