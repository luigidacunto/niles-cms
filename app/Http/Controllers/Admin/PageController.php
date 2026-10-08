<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesAttachments;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Document;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    use ManagesAttachments;

    public function index(Request $request)
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('pages', 'read') || $admin->hasPermission('pages', 'write'), 403);

        // Cast a stringa: un parametro vuoto nell'URL (?stato=) arriva come null (ConvertEmptyStringsToNull),
        // non come '' — e input('key', '') NON usa il default se la chiave esiste con valore null.
        $filters = [
            'q' => trim((string) $request->input('q')),
            'stato' => (string) $request->input('stato'),   // '' | pubblicate | bozze
            'menu' => (string) $request->input('menu'),     // '' | si | no
        ];

        // Con un filtro attivo si mostra una lista piatta filtrata (l'albero/indentazione non ha senso su
        // un sottoinsieme); senza filtri, l'albero completo per parentela.
        $filtering = $filters['q'] !== '' || $filters['stato'] !== '' || $filters['menu'] !== '';

        if ($filtering) {
            $query = Page::orderBy('title');
            if ($filters['q'] !== '') {
                $query->where(fn ($w) => $w->where('title', 'like', "%{$filters['q']}%")->orWhere('slug', 'like', "%{$filters['q']}%"));
            }
            if ($filters['stato'] === 'pubblicate') {
                $query->where('published', true);
            } elseif ($filters['stato'] === 'bozze') {
                $query->where('published', false);
            }
            if ($filters['menu'] === 'si') {
                $query->where('in_menu', true);
            } elseif ($filters['menu'] === 'no') {
                $query->where('in_menu', false);
            }
            $pages = $query->get();
        } else {
            $pages = Page::orderedTree();
        }

        return view('admin.pages.index', ['pages' => $pages, 'filters' => $filters, 'filtering' => $filtering]);
    }

    public function create(Request $request)
    {
        $this->authorizeWrite($request);

        return view('admin.pages.form', [
            'page' => new Page(['published' => true]),
            'parents' => Page::orderBy('title')->get(),
            'availableDocuments' => $this->availableDocuments(),
        ]);
    }

    public function store(Request $request)
    {
        $admin = $this->authorizeWrite($request);
        $data = $this->validated($request);

        $page = new Page();
        $this->fillAndSave($page, $data, $admin, $request);

        return redirect()->route('admin.pages.index')->with('status', 'Pagina creata.');
    }

    public function edit(Request $request, Page $page)
    {
        $this->authorizeWrite($request);

        return view('admin.pages.form', [
            'page' => $page,
            'parents' => Page::whereKeyNot($page->id)->orderBy('title')->get(),
            'availableDocuments' => $this->availableDocuments(),
        ]);
    }

    /**
     * Vista sola-lettura per un editor con solo pages:read — stesso ruolo di PostController::show().
     */
    public function show(Request $request, Page $page)
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('pages', 'read') || $admin->hasPermission('pages', 'write'), 403);

        return view('admin.pages.show', ['page' => $page]);
    }

    public function update(Request $request, Page $page)
    {
        $admin = $this->authorizeWrite($request);
        $data = $this->validated($request, $page);

        $this->fillAndSave($page, $data, $admin, $request);

        return redirect()->route('admin.pages.index')->with('status', 'Pagina aggiornata.');
    }

    public function destroy(Request $request, Page $page)
    {
        $this->authorizeWrite($request);
        abort_if($page->system, 403, 'Pagina di sistema: non eliminabile.');

        $page->attachments()->detach();
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Pagina eliminata.');
    }

    private function authorizeWrite(Request $request): Admin
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('pages', 'write'), 403);

        return $admin;
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:pages,id', Rule::notIn([$page?->id])],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'template' => ['nullable', 'alpha_dash', 'max:255'],
            'body' => ['nullable', 'string'],
            'embed_html' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'published' => ['boolean'],
            'in_menu' => ['boolean'],
            // Allegati: documenti già in libreria da collegare/scollegare/riordinare.
            'attach_documents' => ['nullable', 'array'],
            'attach_documents.*' => ['integer', 'exists:documents,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.order' => ['nullable', 'integer', 'min:0'],
            'attachments.*.delete' => ['nullable', 'boolean'],
        ]);

        $data['published'] = $request->boolean('published');
        $data['in_menu'] = $request->boolean('in_menu');

        return $data;
    }

    /** Documenti di libreria pubblicati, per il selettore "Allegati" nel form. */
    private function availableDocuments()
    {
        return Document::published()
            ->where(fn ($q) => $q->whereHas('category', fn ($c) => $c->library())->orWhereNull('document_category_id'))
            ->orderBy('title')->get();
    }

    private function fillAndSave(Page $page, array $data, Admin $admin, Request $request): void
    {
        // Pagina di sistema: slug/parentela/menu/template sono fissati da seeder/codice e non si
        // toccano dal pannello (nemmeno da un admin, nemmeno forzando la request). Restano editabili
        // il testo introduttivo (titolo/estratto/corpo), lo stato published e l'**ordine** nel menu
        // (posizione, non struttura).
        $locked = $page->structureLocked();

        if (! $locked) {
            $requestedSlug = $admin->role === 'admin' ? ($data['slug'] ?? null) : null;
            $baseSlug = $requestedSlug ? Str::slug($requestedSlug) : Str::slug($data['title']);
            $page->slug = Page::uniqueSlug($baseSlug, $page->id);
        }

        $page->title = $data['title'];
        $page->excerpt = $data['excerpt'] ?? null;
        $page->body = $data['body'] ?? null;
        $page->published = $data['published'];
        $page->order = $data['order'] ?? $page->order ?? 0;

        // Parentela (parent_id, per il breadcrumb), presenza nel menu (in_menu) e "template" (chiave di
        // una vista scritta a mano nel codice) sono struttura del sito, non
        // contenuto: solo un admin li gestisce, un editor lavora solo su testo/slug/ordine.
        // Per un editor questi campi restano semplicemente quello che erano già sulla riga.
        if ($admin->role === 'admin' && ! $locked) {
            $page->parent_id = $data['parent_id'] ?? null;
            $page->in_menu = $data['in_menu'];
            $page->template = $data['template'] ?? null;
        }

        // Blocco embed grezzo (script/form delle piattaforme di donazione): reso senza sanitizer,
        // quindi solo un admin può scriverlo — anche su una pagina di sistema (è il caso d'uso:
        // /donazioni). Un editor non lo vede nel form e non può impostarlo forzando la request.
        if ($admin->role === 'admin') {
            $page->embed_html = $data['embed_html'] ?? null;
        }

        $page->save();

        $this->resequenceSiblings($page);
        $this->syncAttachments($page, $request);
    }

    /**
     * Rinumera 0,1,2,... il gruppo di pagine con lo stesso genitore, inserendo `$page` alla posizione
     * che ha chiesto (il suo `order` corrente). Così cambiare l'ordine di una pagina fa scalare le
     * altre; se la posizione era libera, di fatto resta dov'è. Update diretto sui fratelli (niente
     * eventi del model / PurifiesBody sui loro body).
     */
    private function resequenceSiblings(Page $page): void
    {
        $siblings = Page::where('parent_id', $page->parent_id)
            ->whereKeyNot($page->id)
            ->orderBy('order')->orderBy('id')
            ->get();

        $pos = max(0, min((int) $page->order, $siblings->count()));
        $ordered = $siblings->values();
        $ordered->splice($pos, 0, [$page]);

        foreach ($ordered as $i => $p) {
            if ((int) $p->order !== $i) {
                Page::whereKey($p->id)->update(['order' => $i]);
            }
        }
    }
}
