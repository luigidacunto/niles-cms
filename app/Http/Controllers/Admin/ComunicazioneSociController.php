<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesAttachments;
use App\Http\Controllers\Controller;
use App\Models\ComunicazioneImage;
use App\Models\ComunicazioneSoci;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Comunicazioni interne ai soci. Permesso unico 'comunicazioni_soci' (lettura+scrittura insieme, copre post,
 * documenti e categorie riservati). Copertina e galleria stanno sul disco privato; gli allegati sono documenti
 * riservati già presenti in archivio (Documenti riservati), mai file caricati qui: un file, un solo posto.
 */
class ComunicazioneSociController extends Controller
{
    use ManagesAttachments;

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

        return view('admin.comunicazioni-soci.form', $this->formData(new ComunicazioneSoci(['published' => true])));
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

        return view('admin.comunicazioni-soci.form', $this->formData($comunicazione->load(['attachments', 'images'])));
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

        // Gli allegati sono documenti riservati condivisi: si scollegano, non si cancellano.
        $comunicazione->attachments()->detach();
        foreach ($comunicazione->images as $image) {
            Storage::disk('local')->delete($image->path); // le righe cadono con la comunicazione (FK cascade)
        }
        if ($comunicazione->cover_image) {
            Storage::disk('local')->delete($comunicazione->cover_image);
        }
        $comunicazione->delete();

        return redirect()->route('admin.comunicazioni-soci.index')->with('status', 'Comunicazione eliminata.');
    }

    /** Anteprima di copertina/foto nel pannello: i file sono privati, senza URL pubblico. */
    public function copertina(Request $request, ComunicazioneSoci $comunicazione)
    {
        $this->authorizeComunicazioni($request);

        return $this->serve($comunicazione->cover_image);
    }

    public function immagine(Request $request, ComunicazioneImage $immagine)
    {
        $this->authorizeComunicazioni($request);

        return $this->serve($immagine->path);
    }

    private function serve(?string $path)
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function formData(ComunicazioneSoci $item): array
    {
        return [
            'item' => $item,
            'availableDocuments' => Document::riservati()->published()->orderBy('title')->get(),
        ];
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
        ]);

        if (($request->boolean('remove_cover') || $request->hasFile('cover_image')) && $item->cover_image) {
            Storage::disk('local')->delete($item->cover_image);
            $item->cover_image = null;
        }
        if ($request->hasFile('cover_image')) {
            $item->cover_image = ImageUpload::storeResized($request->file('cover_image'), 'comunicazioni/copertine', $data['title'], 1600, 'local');
        }

        $item->save();

        $this->syncImages($item, $request);
        $this->syncAttachments($item, $request);
    }

    private function syncImages(ComunicazioneSoci $item, Request $request): void
    {
        foreach ($request->input('images', []) as $id => $fields) {
            $image = $item->images()->whereKey($id)->first();
            if (! $image) {
                continue;
            }

            if (filter_var($fields['delete'] ?? false, FILTER_VALIDATE_BOOL)) {
                Storage::disk('local')->delete($image->path);
                $image->delete();

                continue;
            }

            $image->update(['caption' => $fields['caption'] ?? null, 'order' => (int) ($fields['order'] ?? 0)]);
        }

        $order = (int) $item->images()->max('order');
        foreach ($request->file('gallery_images', []) as $file) {
            $item->images()->create([
                'path' => ImageUpload::storeResized($file, 'comunicazioni/galleria', $item->title, 1920, 'local'),
                'order' => ++$order,
            ]);
        }
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
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8192'],
            'gallery_images' => ['nullable', 'array', 'max:60'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png', 'max:8192'],
            // Solo documenti riservati già in archivio.
            'attach_documents' => ['nullable', 'array'],
            'attach_documents.*' => ['integer', Rule::exists('documents', 'id')->whereIn('document_category_id', DocumentCategory::soci()->select('id'))],
            'attachments' => ['nullable', 'array'],
            'attachments.*.order' => ['nullable', 'integer', 'min:0'],
            'attachments.*.delete' => ['nullable', 'boolean'],
        ]);
    }
}
