<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Una foto della galleria di un post (vedi `Post::images()`).
 * Il file fisico sta in `storage/app/public/post-images/`.
 */
class PostImage extends Model
{
    protected $fillable = ['post_id', 'path', 'caption', 'alt', 'order'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** Path relativo → URL servibile. Un record ha sempre un file reale, nessun placeholder. */
    protected function url(): Attribute
    {
        return Attribute::make(get: fn () => Storage::url($this->path));
    }
}
