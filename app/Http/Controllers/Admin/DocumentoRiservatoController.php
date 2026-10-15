<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Support\AreaSoci;
use App\Support\DocumentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Documenti riservati ai soci: file sul disco privato, in una categoria `soci` (chi la vede lo decide la categoria)
 * e con una sottocategoria libera solo per organizzarli nella pagina dei soci. Solo PDF e immagini, perché si
 * aprono sempre in anteprima. Permesso unico 'comunicazioni_soci'.
 */
class DocumentoRiservatoController extends Controller
{
    /** Formati accettati: gli unici che il browser mostra in anteprima senza servizi esterni. */
    public const MIMES = 'pdf,jpg,jpeg,png,webp';

    public function index(Request $request)
    {
        $this->authorizeRiservati($request);

        $documents = Document::riservati()->with('category')
            ->when($request->filled('categoria'), fn ($q) => $q->where('document_category_id', $request->integer('categoria')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.riservati.documenti.index', ['documents' => $documents, 'categories' => $this->categorie()]);
    }

    public function create(Request $request)
    {
        $this->authorizeRiservati($request);

        return view('admin.riservati.documenti.form', $this->formData(new Document(['published' => true])));
    }

    public function store(Request $request)
    {
        $this->authorizeRiservati($request);

        $this->fillAndSave(new Document, $this->validated($request, creating: true), $request);

        return redirect()->route('admin.documenti-riservati.index')->with('status', 'Documento caricato.');
    }

    public function edit(Request $request, Document $document)
    {
        $this->authorizeDocumento($request, $document);

        return view('admin.riservati.documenti.form', $this->formData($document));
    }

    public function update(Request $request, Document $document)
    {
        $this->authorizeDocumento($request, $document);

        $this->fillAndSave($document, $this->validated($request, creating: false), $request);

        return redirect()->route('admin.documenti-riservati.index')->with('status', 'Documento aggiornato.');
    }

    public function destroy(Request $request, Document $document)
    {
        $this->authorizeDocumento($request, $document);

        if ($document->file_path) {
            Storage::disk($document->disk())->delete($document->file_path);
        }
        $document->delete(); // i legami con le comunicazioni cadono con il documento (FK cascade)

        return redirect()->route('admin.documenti-riservati.index')->with('status', 'Documento eliminato.');
    }

    private function formData(Document $document): array
    {
        return [
            'document' => $document,
            'categories' => $this->categorie(),
            'sottocategorie' => Document::riservati()->whereNotNull('sottocategoria')->distinct()->orderBy('sottocategoria')->pluck('sottocategoria'),
        ];
    }

    private function categorie()
    {
        AreaSoci::categoriaDocumenti(); // «Senza categoria» esiste sempre, anche su installazioni senza il seeder dell'area soci

        return DocumentCategory::soci()->orderByRaw('slug = ? desc', [DocumentCategory::SLUG_GENERALE])->orderBy('order')->orderBy('name')->get();
    }

    private function authorizeDocumento(Request $request, Document $document): void
    {
        $this->authorizeRiservati($request);
        abort_unless($document->isRiservato(), 404);
    }

    private function authorizeRiservati(Request $request): void
    {
        abort_unless($request->user('admin')->hasPermission('comunicazioni_soci'), 403);
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'document_category_id' => ['required', 'integer', 'in:'.$this->categorie()->pluck('id')->implode(',')],
            'sottocategoria' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => [$creating ? 'required' : 'nullable', 'file', 'mimes:'.self::MIMES, 'max:20480'],
            'published' => ['boolean'],
        ]);

        $data['published'] = $request->boolean('published');
        $data['sottocategoria'] = trim((string) ($data['sottocategoria'] ?? '')) ?: null;

        return $data;
    }

    private function fillAndSave(Document $document, array $data, Request $request): void
    {
        $document->document_category_id = $data['document_category_id'];
        $document->unsetRelation('category'); // il disco dipende dalla categoria: ricarica quella nuova
        $document->title = $data['title'];
        $document->description = $data['description'] ?? null;
        $document->sottocategoria = $data['sottocategoria'];
        $document->published = $data['published'];
        $document->published_at ??= now();

        if ($request->hasFile('file')) {
            DocumentStore::store($request->file('file'), $document);
        }

        $document->save();
    }
}
