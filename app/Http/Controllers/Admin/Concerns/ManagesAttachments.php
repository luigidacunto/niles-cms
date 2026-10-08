<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Sincronizza gli "Allegati" (documenti della Libreria collegati) di un post o una pagina — stesso
 * form (`images[{id}][...]`-style) usato dalla galleria foto dei post. Condiviso da
 * `Admin\PostController` e `Admin\PageController` per non ripetere la logica (vedi
 * `App\Models\Concerns\HasDocumentAttachments`).
 */
trait ManagesAttachments
{
    private function syncAttachments(Model $model, Request $request): void
    {
        foreach ($request->input('attachments', []) as $documentId => $fields) {
            if (filter_var($fields['delete'] ?? false, FILTER_VALIDATE_BOOL)) {
                $model->attachments()->detach($documentId);

                continue;
            }
            $model->attachments()->updateExistingPivot($documentId, [
                'order' => (int) ($fields['order'] ?? 0),
            ]);
        }

        $order = (int) $model->attachments()->max('attachable_documents.order');
        $existingIds = $model->attachments()->pluck('documents.id')->all();

        foreach ($request->input('attach_documents', []) as $documentId) {
            if (! in_array((int) $documentId, $existingIds, true)) {
                $model->attachments()->attach($documentId, ['order' => ++$order]);
            }
        }
    }
}
