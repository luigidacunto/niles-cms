<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Support\DocumentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Documenti pubblicati nella pagina Trasparenza. Solo PDF (pubblicare formati
 * diversi su /trasparenza non ha senso). La libreria documenti generica — che accetta
 * anche Office — è un'altra sezione (Admin\DocumentController, /admin/documenti).
 */
class TrasparenzaDocumentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRead($request);

        return view('admin.trasparenza.documenti.index', [
            'documents' => Document::with('category')
                ->whereHas('category', fn ($q) => $q->trasparenza())
                ->orderBy('document_category_id')
                ->orderByDesc('published_at')
                ->paginate(30),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeWrite($request);

        return view('admin.trasparenza.documenti.form', [
            'document' => new Document(['published' => true, 'published_at' => now()]),
        ] + $this->formData());
    }

    public function store(Request $request)
    {
        $this->authorizeWrite($request);
        $data = $this->validated($request);

        $this->fillAndSave(new Document, $data, $request);

        return redirect()->route('admin.trasparenza-documenti.index')->with('status', 'Documento caricato.');
    }

    public function show(Request $request, Document $document)
    {
        $this->authorizeRead($request);

        return view('admin.trasparenza.documenti.show', ['document' => $document->load('category')]);
    }

    public function edit(Request $request, Document $document)
    {
        $this->authorizeWrite($request);

        return view('admin.trasparenza.documenti.form', ['document' => $document] + $this->formData());
    }

    public function update(Request $request, Document $document)
    {
        $this->authorizeWrite($request);
        $data = $this->validated($request);

        $this->fillAndSave($document, $data, $request);

        return redirect()->route('admin.trasparenza-documenti.index')->with('status', 'Documento aggiornato.');
    }

    public function destroy(Request $request, Document $document)
    {
        $this->authorizeWrite($request);

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        return redirect()->route('admin.trasparenza-documenti.index')->with('status', 'Documento eliminato.');
    }

    private function authorizeRead(Request $request): void
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('trasparenza', 'read') || $admin->hasPermission('trasparenza', 'write'), 403);
    }

    private function authorizeWrite(Request $request): Admin
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('trasparenza', 'write'), 403);

        return $admin;
    }

    private function formData(): array
    {
        return [
            'categories' => DocumentCategory::trasparenza()->orderBy('order')->orderBy('name')->get(),
            'types' => Document::query()
                ->whereNotNull('type')->where('type', '!=', '')
                ->distinct()->orderBy('type')->pluck('type'),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'document_category_id' => ['required', 'exists:document_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['nullable', 'string', 'max:255'],
            'file' => [$request->routeIs('admin.trasparenza-documenti.store') ? 'required' : 'nullable', 'file', 'mimetypes:application/pdf', 'max:20480'],
            'published_at' => ['nullable', 'date'],
            'published' => ['boolean'],
        ]);

        $data['published'] = $request->boolean('published');

        return $data;
    }

    private function fillAndSave(Document $document, array $data, Request $request): void
    {
        $document->document_category_id = $data['document_category_id'];
        $document->title = $data['title'];
        $document->description = $data['description'] ?? null;
        $document->type = filled($data['type'] ?? null) ? trim($data['type']) : null;
        $document->published = $data['published'];
        $document->published_at = $data['published_at'] ?? now();

        if ($request->hasFile('file')) {
            DocumentStore::store($request->file('file'), $document);
        }

        $document->save();
    }
}
