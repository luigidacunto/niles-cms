<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComunicazioneSoci;
use App\Models\Document;
use App\Support\AreaSoci;
use App\Support\DocumentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Comunicazioni interne ai soci. Permesso unico 'comunicazioni_soci' (lettura+scrittura insieme, copre post e
 * allegati). Gli allegati sono Document della categoria riservata (AreaSoci::categoriaDocumenti): file su disco
 * privato, creati e cancellati insieme alla comunicazione (non passano dalla Libreria documenti pubblica).
 */
class ComunicazioneSociController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeComunicazioni($request);

        $filters = ['q' => trim((string) $request->input('q')), 'stato' => (string) $request->input('stato')];

        $items = ComunicazioneSoci::orderByDesc('published_at');
        if ($filters['q'] !== '') {
            $items->where('title', 'like', "%{$filters['q']}%");
        }
        if ($filters['stato'] === 'pubblicati') {
            $items->where('published', true);
        } elseif ($filters['stato'] === 'bozze') {
            $items->where('published', false);
        }

        return view('admin.comunicazioni-soci.index', ['items' => $items->paginate(20)->withQueryString(), 'filters' => $filters]);
    }

    public function create(Request $request)
    {
        $this->authorizeComunicazioni($request);

        return view('admin.comunicazioni-soci.form', ['item' => new ComunicazioneSoci(['published' => true])]);
    }

    public function store(Request $request)
    {
        $admin = $this->authorizeComunicazioni($request);
        $data = $this->validated($request);

        $item = new ComunicazioneSoci;
        $item->slug = ComunicazioneSoci::uniqueSlug(Str::slug($data['title']) ?: 'comunicazione', null, now());
        $item->author_name = $admin->name;
        $this->fillAndSave($item, $data, $request);

        return redirect()->route('admin.comunicazioni-soci.index')->with('status', 'Comunicazione creata.');
    }

    public function edit(Request $request, ComunicazioneSoci $comunicazione)
    {
        $this->authorizeComunicazioni($request);

        return view('admin.comunicazioni-soci.form', ['item' => $comunicazione->load('attachments')]);
    }

    public function update(Request $request, ComunicazioneSoci $comunicazione)
    {
        $this->authorizeComunicazioni($request);
        $this->fillAndSave($comunicazione, $this->validated($request), $request);

        return redirect()->route('admin.comunicazioni-soci.index')->with('status', 'Comunicazione aggiornata.');
    }

    public function destroy(Request $request, ComunicazioneSoci $comunicazione)
    {
        $this->authorizeComunicazioni($request);

        foreach ($comunicazione->attachments as $document) {
            $this->deleteAttachment($comunicazione, $document);
        }
        $comunicazione->delete();

        return redirect()->route('admin.comunicazioni-soci.index')->with('status', 'Comunicazione eliminata.');
    }

    private function fillAndSave(ComunicazioneSoci $item, array $data, Request $request): void
    {
        if ($item->exists) {
            $item->last_edited_at = now();
        }
        $item->fill([
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'published' => $request->boolean('published'),
            'published_at' => $data['published_at'] ?? now(),
        ])->save();

        // Allegati esistenti: elimina (file compreso) / riordina.
        foreach ($item->attachments as $document) {
            $fields = $request->input("attachments.{$document->id}", []);
            if (filter_var($fields['delete'] ?? false, FILTER_VALIDATE_BOOL)) {
                $this->deleteAttachment($item, $document);
            } else {
                $item->attachments()->updateExistingPivot($document->id, ['order' => (int) ($fields['order'] ?? 0)]);
            }
        }

        // Nuovi allegati: Document riservati, file sul disco privato.
        $order = (int) $item->attachments()->max('attachable_documents.order');
        foreach ($request->file('allegati', []) as $file) {
            $document = new Document([
                'document_category_id' => AreaSoci::categoriaDocumenti()->id,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'published' => true,
                'published_at' => now(),
            ]);
            DocumentStore::store($file, $document);
            $document->save();
            $item->attachments()->attach($document->id, ['order' => ++$order]);
        }
    }

    private function deleteAttachment(ComunicazioneSoci $item, Document $document): void
    {
        $item->attachments()->detach($document->id);
        if ($document->file_path) {
            Storage::disk($document->disk())->delete($document->file_path);
        }
        $document->delete();
    }

    private function authorizeComunicazioni(Request $request)
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('comunicazioni_soci'), 403);

        return $admin;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'allegati' => ['nullable', 'array', 'max:20'],
            'allegati.*' => ['file', 'mimes:'.DocumentController::MIMES, 'max:20480'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.order' => ['nullable', 'integer', 'min:0'],
            'attachments.*.delete' => ['nullable', 'boolean'],
        ]);
    }
}
