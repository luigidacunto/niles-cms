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
 * Libreria documenti generica: file (PDF, Office, ...) da linkare dentro post e pagine,
 * con storico. Diversa dalla sezione Trasparenza (Admin\TrasparenzaDocumentController),
 * che pubblica solo PDF su /trasparenza. Categorie = `document_categories` con
 * `scope='library'`; quelle `selectable=false` (es. Organigramma) si vedono qui ma non
 * sono un bersaglio di upload manuale — ci finiscono solo i file dei costrutti.
 */
class DocumentController extends Controller
{
    /** Formati accettati in libreria (Trasparenza resta solo PDF). */
    public const MIMES = 'pdf,doc,docx,ppt,pptx,xls,xlsx,odt,ods,odp,txt,csv';

    public function index(Request $request)
    {
        $this->authorizeRead($request);

        $documents = Document::with('category')
            ->where(fn ($q) => $q->whereHas('category', fn ($c) => $c->library())->orWhereNull('document_category_id'))
            ->when($request->filled('categoria'), fn ($q) => $request->input('categoria') === 'none'
                ? $q->whereNull('document_category_id')
                : $q->where('document_category_id', $request->integer('categoria')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.documenti.index', [
            'documents' => $documents,
            'categories' => DocumentCategory::library()->orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeWrite($request);

        return view('admin.documenti.form', [
            'document' => new Document(['published' => true]),
            'categories' => $this->selectableCategories(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeWrite($request);
        $data = $this->validated($request, creating: true);

        $this->fillAndSave(new Document, $data, $request);

        return redirect()->route('admin.documents.index')->with('status', 'Documento caricato in libreria.');
    }

    public function show(Request $request, Document $document)
    {
        $this->authorizeRead($request);
        $this->ensureLibrary($document);

        return view('admin.documenti.show', ['document' => $document->load('category')]);
    }

    public function edit(Request $request, Document $document)
    {
        $this->authorizeWrite($request);
        $this->ensureLibrary($document);

        $categories = $this->selectableCategories();
        // La categoria attuale resta selezionabile anche se riservata, per non perderla salvando.
        if ($document->category) {
            $categories = $categories->push($document->category)->unique('id')->sortBy('name')->values();
        }

        return view('admin.documenti.form', [
            'document' => $document,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $this->authorizeWrite($request);
        $this->ensureLibrary($document);
        $data = $this->validated($request, creating: false);

        $this->fillAndSave($document, $data, $request);

        return redirect()->route('admin.documents.index')->with('status', 'Documento aggiornato.');
    }

    public function destroy(Request $request, Document $document)
    {
        $this->authorizeWrite($request);
        $this->ensureLibrary($document);

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        return redirect()->route('admin.documents.index')->with('status', 'Documento eliminato dalla libreria.');
    }

    /**
     * Elenco JSON per il picker dell'editor Summernote (post/pagine): solo documenti di
     * libreria pubblicati. Accessibile a chi può editare post o pagine — restituisce solo
     * titolo + URL pubblico di file già raggiungibili, niente dati sensibili.
     */
    public function picker(Request $request)
    {
        $admin = $request->user('admin');
        abort_unless(
            $admin->hasPermission('posts', 'write') || $admin->hasPermission('pages', 'write') || $admin->hasPermission('documents', 'read'),
            403
        );

        $documents = Document::with('category')
            ->where('published', true)
            ->where(fn ($q) => $q->whereHas('category', fn ($c) => $c->library())->orWhereNull('document_category_id'))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderBy('title')
            ->limit(100)
            ->get()
            ->map(fn (Document $d) => [
                'title' => $d->title,
                'category' => $d->category?->name,
                'ext' => $d->extension,
                'url' => $d->downloadUrl,
            ]);

        return response()->json($documents);
    }

    private function ensureLibrary(Document $document): void
    {
        // Senza categoria = documento di libreria per convenzione (la Trasparenza esige sempre un'area).
        abort_unless($document->document_category_id === null || $document->category?->scope === 'library', 404);
    }

    private function selectableCategories()
    {
        return DocumentCategory::library()->where('selectable', true)->orderBy('order')->orderBy('name')->get();
    }

    private function authorizeRead(Request $request): void
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('documents', 'read') || $admin->hasPermission('documents', 'write'), 403);
    }

    private function authorizeWrite(Request $request): Admin
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('documents', 'write'), 403);

        return $admin;
    }

    private function validated(Request $request, bool $creating): array
    {
        $selectable = $this->selectableCategories()->pluck('id');

        $data = $request->validate([
            // Categoria opzionale in libreria; se c'è dev'essere una categoria library selezionabile.
            'document_category_id' => ['nullable', 'integer', 'in:'.$selectable->implode(',')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => [$creating ? 'required' : 'nullable', 'file', 'mimes:'.self::MIMES, 'max:20480'],
            'published' => ['boolean'],
        ]);

        $data['published'] = $request->boolean('published');

        return $data;
    }

    private function fillAndSave(Document $document, array $data, Request $request): void
    {
        $document->document_category_id = $data['document_category_id'] ?? null;
        $document->title = $data['title'];
        $document->description = $data['description'] ?? null;
        $document->published = $data['published'];
        $document->published_at ??= now();

        if ($request->hasFile('file')) {
            DocumentStore::store($request->file('file'), $document);
        }

        $document->save();
    }
}
