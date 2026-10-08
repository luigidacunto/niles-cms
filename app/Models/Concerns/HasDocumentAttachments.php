<?php

namespace App\Models\Concerns;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Allegati (documenti della Libreria collegati a questo contenuto) — usato da `Post` e `Page`.
 * Il legame sta nella tabella `attachable_documents` (vedi la migration).
 */
trait HasDocumentAttachments
{
    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Document::class, 'attachable', 'attachable_documents')
            ->withPivot('order')
            ->orderBy('attachable_documents.order');
    }
}
