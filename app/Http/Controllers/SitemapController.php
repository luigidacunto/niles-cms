<?php

namespace App\Http\Controllers;

use App\Models\BoardSetting;
use App\Models\Corso;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Page;
use App\Models\Post;
use App\Models\PrivacyPolicy;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * `sitemap.xml` dinamico: tutto ciò che è pubblico e di libero accesso, letto dal DB ad ogni richiesta
 * (nessuna cache/generazione).
 *
 * ⚠️ Ogni nuova sezione pubblica con una sua route va aggiunta qui. Il limite del protocollo è 50.000
 * URL per file: a questi volumi non è un problema, oltre servirebbe un indice di sitemap.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => url('/'), 'lastmod' => null],
            ['loc' => route('posts.archive'), 'lastmod' => null],
            ['loc' => route('posts.archive.comunicati'), 'lastmod' => null],
        ];

        foreach (Page::where('published', true)->get(['slug', 'category_id', 'updated_at']) as $page) {
            $urls[] = ['loc' => url('/'.$page->slug), 'lastmod' => $page->updated_at?->toAtomString()];

            // Archivio notizie di una pagina sezione (/{slug}/archivio).
            if ($page->category_id) {
                $urls[] = ['loc' => route('pages.archive.category', $page), 'lastmod' => null];
            }
        }

        foreach (Post::with('category')->where('published', true)->get(['id', 'slug', 'category_id', 'updated_at', 'last_edited_at']) as $post) {
            $urls[] = [
                'loc' => url('/'.$post->category->slug.'/'.$post->slug),
                'lastmod' => ($post->last_edited_at ?? $post->updated_at)?->toAtomString(),
            ];
        }

        // Pagina di iscrizione dei soli corsi aperti: gli altri mostrano "iscrizioni non disponibili".
        foreach (Corso::conIscrizioniAperte()->get(['slug', 'updated_at']) as $corso) {
            $urls[] = ['loc' => route('corsi.iscrizione.show', $corso), 'lastmod' => $corso->updated_at?->toAtomString()];
        }

        // Informative privacy per tipologia (solo quelle con un testo, altrimenti la pagina dà 404).
        foreach (PrivacyPolicy::distinct()->pluck('tipo') as $tipo) {
            if (array_key_exists($tipo, config('privacy_policies'))) {
                $urls[] = ['loc' => route('privacy-policy.show', $tipo), 'lastmod' => null];
            }
        }

        foreach ($this->documentiPubblici() as $documento) {
            $urls[] = ['loc' => route('documenti.show', $documento), 'lastmod' => $documento->updated_at?->toAtomString()];
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml)->header('Content-Type', 'application/xml');
    }

    /**
     * Documenti pubblicati realmente esposti dal sito: quelli elencati in /trasparenza, gli allegati e i
     * link nel testo di post/pagine pubblicati, l'organigramma della Struttura Organizzativa. ⚠️ Non i file
     * della libreria usati da nessuna parte (raggiungibili solo per URL) né mai quelli riservati ai soci:
     * l'indicizzazione di un file è una scelta, non un effetto collaterale.
     */
    private function documentiPubblici(): Collection
    {
        $postIds = Post::where('published', true)->pluck('id');
        $pageIds = Page::where('published', true)->pluck('id');

        $ids = DB::table('attachable_documents')
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('attachable_type', Post::class)->whereIn('attachable_id', $postIds))
                ->orWhere(fn ($q) => $q->where('attachable_type', Page::class)->whereIn('attachable_id', $pageIds)))
            ->pluck('document_id');

        // Link inline nel testo (`/documenti/{id}`): si leggono solo i corpi che ne contengono almeno uno.
        $corpi = Post::where('published', true)->where('body', 'like', '%/documenti/%')->pluck('body')
            ->merge(Page::where('published', true)->where('body', 'like', '%/documenti/%')->pluck('body'));
        foreach ($corpi as $corpo) {
            preg_match_all('#/documenti/(\d+)#', $corpo, $m);
            $ids = $ids->merge($m[1]);
        }

        $organigramma = BoardSetting::current()->organigramma_document_id;

        return Document::published()
            ->whereHas('category', fn ($q) => $q->where('scope', '!=', DocumentCategory::SCOPE_SOCI))
            ->where(fn ($q) => $q
                ->whereIn('id', $ids->unique()->values())
                ->when($organigramma, fn ($q) => $q->orWhere('id', $organigramma))
                ->orWhereHas('category', fn ($q) => $q->trasparenza()))
            ->get(['id', 'updated_at']);
    }
}
