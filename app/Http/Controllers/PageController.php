<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /** Ultime notizie mostrate in fondo a una "pagina sezione" (vedi Page::category()). */
    private const FEED_LIMIT = 5;

    public function show(Page $page)
    {
        abort_unless($page->published || auth('admin')->check(), 404);

        $page->load('attachments');

        $news = $page->category_id
            ? $this->categoryPosts($page)->limit(self::FEED_LIMIT)->get()
            : collect();

        return view('pages.show', ['page' => $page, 'news' => $news]);
    }

    /**
     * Archivio completo di una pagina sezione: /{slug}/archivio. Non è una riga `pages` e non sta nel
     * menu — esiste solo dietro il pulsante in fondo al feed. Stesso pattern async degli altri archivi
     * (risposta JSON su richiesta AJAX, pagina intera altrimenti — vedi x-async-pagination).
     */
    public function archive(Request $request, Page $page)
    {
        abort_unless($page->published && $page->category_id, 404);

        $posts = $this->categoryPosts($page)->paginate(15);

        if ($request->ajax()) {
            return response()->json(['html' => view('partials.archive-items', ['posts' => $posts])->render()]);
        }

        return view('pages.archive', ['page' => $page, 'posts' => $posts]);
    }

    private function categoryPosts(Page $page)
    {
        return Post::where('category_id', $page->category_id)
            ->where('published', true)
            ->with('category')
            ->orderByDesc('published_at');
    }
}
