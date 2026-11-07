<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\ComunicazioneSoci;
use App\Models\Corso;
use App\Models\Document;
use App\Models\DocumentoAccesso;
use App\Models\IscrizioneCorso;
use App\Models\LoginAudit;
use App\Models\Member;
use App\Models\Page;
use App\Models\Persona;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\Tag;
use App\Support\AreaSoci;
use App\Support\UpdateCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Esito della verifica aggiornamenti, risolto in async dalla dashboard (così la pagina non attende GitHub). */
    public function aggiornamenti(): JsonResponse
    {
        return response()->json(UpdateCheck::status() ?? ['stato' => 'disattivato']);
    }

    public function index(Request $request)
    {
        $admin = $request->user('admin');
        $canPages = $admin->hasPermission('pages', 'read') || $admin->hasPermission('pages', 'write');
        $canDocs = $admin->hasPermission('documents', 'read') || $admin->hasPermission('documents', 'write');

        // Un editor con categorie assegnate vede solo i suoi contenuti (come /admin/post).
        $scopedIds = ($admin->role === 'admin' || $admin->all_categories)
            ? null
            : $admin->categories()->pluck('categories.id');
        $scopePosts = fn ($q) => $scopedIds === null ? $q : $q->whereIn('category_id', $scopedIds);

        return view('admin.dashboard', [
            'admin' => $admin,
            'daFare' => $this->daFare($admin),
            'formazione' => $admin->can('manage-corsi') ? $this->formazione() : null,
            'pagamenti' => $admin->can('manage-pagamenti-corsi') ? $this->pagamenti() : null,
            'soci' => AreaSoci::attiva() ? $this->soci($admin) : null,
            'canPages' => $canPages,
            'canDocs' => $canDocs,
            'versione' => config('app.version'),
            'verificaAggiornamenti' => $admin->role === 'admin' && config('app.update_check.enabled'),
            'aggiornamentoDalPannello' => $admin->role === 'admin' && \App\Support\WebUpdate::attivo(),
            'stats' => [
                'posts' => $scopePosts(Post::query())->count(),
                'postsPublished' => $scopePosts(Post::where('published', true))->count(),
                'postsDraft' => $scopePosts(Post::where('published', false))->count(),
                'postsNoCover' => $scopePosts(Post::whereNull('cover_image'))->count(),
                'pages' => Page::count(),
                'pagesOutOfMenu' => Page::where('in_menu', false)->count(),
                'documents' => Document::count(),
                'galleryPhotos' => PostImage::whereHas('post', fn ($q) => $scopePosts($q))->count(),
                'categories' => Category::count(),
                'tags' => Tag::count(),
                'admins' => Admin::where('active', true)->count(),
            ],
            'recentPosts' => $scopePosts(Post::with('category')->orderByDesc('published_at'))->take(6)->get(),
            'draftPosts' => $scopePosts(Post::with('category')->where('published', false)->orderByDesc('published_at'))->take(8)->get(),
            'byCategory' => Category::withCount(['posts' => fn ($q) => $scopePosts($q)])
                ->when($scopedIds !== null, fn ($q) => $q->whereIn('id', $scopedIds))
                ->orderByDesc('posts_count')->orderBy('name')->get(),
        ]);
    }

    /**
     * Richieste in attesa che si chiudono quando qualcuno le gestisce (niente segnalazioni permanenti: un incasso in eccesso o
     * parziale resta tale e si vede nella pagina dei pagamenti). Ogni voce solo a chi la può gestire.
     *
     * @return list<array{testo: string, numero: int, url: string, icona: string}>
     */
    private function daFare(Admin $admin): array
    {
        $voci = [];
        if ($admin->can('manage-persone') && ($n = Persona::cancellazioneInAttesa()->count())) {
            $voci[] = ['testo' => 'Richieste di cancellazione dei dati', 'numero' => $n, 'icona' => 'fa-user-slash',
                'url' => route('admin.persone.index', ['cancellazione' => 'in_attesa'])];
        }
        if ($admin->can('manage-membri') && ($n = Member::richiestaDisattivazione()->count())) {
            $voci[] = ['testo' => 'Richieste di disattivazione dei soci', 'numero' => $n, 'icona' => 'fa-user-lock',
                'url' => route('admin.membri.index', ['presenza' => 'richieste'])];
        }

        return $voci;
    }

    private function formazione(): array
    {
        $futuri = fn ($q) => $q->whereNull('annullato_at')->where('data_inizio', '>', now());

        return [
            'aperti' => Corso::conIscrizioniAperte()->count(),
            'iscrittiFuturi' => IscrizioneCorso::attive()->whereHas('corso', $futuri)->count(),
            'iscrizioniSettimana' => IscrizioneCorso::where('created_at', '>=', now()->subDays(7))->count(),
            'prossimi' => Corso::with('tipologia')
                ->withCount(['iscrizioni as attivi_count' => fn ($q) => $q->attive()])
                ->whereNull('annullato_at')->where('data_fine', '>', now())
                ->orderBy('data_inizio')->take(5)->get(),
        ];
    }

    /** @return array{da_fatturare: int, da_incassare: int} */
    private function pagamenti(): array
    {
        // ponytail: ricalcola i blocchi di ogni corso a pagamento (poche query per corso); se i corsi diventano centinaia,
        // limitare ai corsi con blocchi ancora aperti o salvare i conteggi.
        $totali = ['da_fatturare' => 0, 'da_incassare' => 0];
        foreach (Corso::where('costo', '>', 0)->get() as $corso) {
            $r = $corso->riepilogoPagamenti();
            $totali['da_fatturare'] += $r['da_fatturare'];
            $totali['da_incassare'] += $r['da_incassare'];
        }

        return $totali;
    }

    /** Area soci accesa: ogni parte solo con il suo permesso; i registri (accessi, download) solo agli amministratori. */
    private function soci(Admin $admin): ?array
    {
        $dati = [];
        if ($admin->can('manage-membri')) {
            $attivi = Member::where('disabilitato', false);
            $dati['membri'] = [
                'totale' => (clone $attivi)->count(),
                'perRuolo' => (clone $attivi)->selectRaw('ruolo, count(*) as n')->groupBy('ruolo')->pluck('n', 'ruolo'),
                'senzaEmail' => (clone $attivi)->where(fn ($q) => $q->whereNull('email')->orWhere('email', ''))->count(),
            ];
        }
        if ($admin->can('manage-comunicazioni-soci')) {
            $dati['contenuti'] = [
                'comunicazioni' => ComunicazioneSoci::visibili()->count(),
                'documenti' => Document::riservati()->where('published', true)->count(),
            ];
        }
        if ($admin->role === 'admin') {
            $dal = now()->subDays(30);
            $dati['attivita'] = [
                'sociEntrati' => LoginAudit::where('guard', 'member')->where('event', 'success')->where('created_at', '>=', $dal)
                    ->distinct()->count('subject_id'),
                'documentiAperti' => DocumentoAccesso::where('created_at', '>=', $dal)->count(),
            ];
        }

        return $dati ?: null;
    }
}
