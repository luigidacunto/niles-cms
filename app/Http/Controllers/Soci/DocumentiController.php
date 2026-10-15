<?php

namespace App\Http\Controllers\Soci;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentoAccesso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Documenti riservati per i soci loggati (route dietro auth:member + area.soci, vedi routes/soci.php).
 * Un socio vede la categoria «Senza categoria» e quelle che gli sono state assegnate. Il file si apre sempre
 * prima in anteprima; il download parte solo dal pulsante esplicito, ed entrambi finiscono nel registro.
 */
class DocumentiController extends Controller
{
    /** Anteprima incorporabile nel browser; gli altri formati non sono ammessi tra i riservati. */
    private const IMMAGINI = ['jpg', 'jpeg', 'png', 'webp'];

    public function index(Request $request)
    {
        $member = $request->user('member');

        $categorie = DocumentCategory::soci()
            ->where(fn ($q) => $q->where('slug', DocumentCategory::SLUG_GENERALE)
                ->orWhereIn('id', $member->categorieRiservate()->pluck('document_categories.id')))
            ->with(['documents' => fn ($q) => $q->published()->orderBy('title')])
            ->orderByRaw('slug = ? desc', [DocumentCategory::SLUG_GENERALE])
            ->orderBy('order')->orderBy('name')
            ->get()
            ->filter(fn (DocumentCategory $c) => $c->documents->isNotEmpty());

        return view('soci.documenti.index', ['categorie' => $categorie]);
    }

    public function anteprima(Request $request, Document $document)
    {
        $member = $this->autorizza($request, $document);
        DocumentoAccesso::record($document, $member, DocumentoAccesso::ANTEPRIMA, $request);

        return view('soci.documenti.anteprima', [
            'document' => $document->load('category'),
            'immagine' => in_array($document->extension, self::IMMAGINI, true),
        ]);
    }

    /** File grezzo inline: serve solo all'anteprima (iframe/img), non finisce nel registro. */
    public function file(Request $request, Document $document)
    {
        $this->autorizza($request, $document);

        return $this->risposta($document, 'inline');
    }

    public function scarica(Request $request, Document $document)
    {
        $member = $this->autorizza($request, $document);
        DocumentoAccesso::record($document, $member, DocumentoAccesso::DOWNLOAD, $request);

        return $this->risposta($document, 'attachment');
    }

    private function autorizza(Request $request, Document $document)
    {
        $member = $request->user('member');
        abort_unless($member->puoVedere($document->loadMissing('category')), 404);

        return $member;
    }

    private function risposta(Document $document, string $disposition)
    {
        $disk = Storage::disk($document->disk());
        abort_unless($document->file_path && $disk->exists($document->file_path), 404);

        return $disk->response($document->file_path, $document->download_name, [], $disposition);
    }
}
