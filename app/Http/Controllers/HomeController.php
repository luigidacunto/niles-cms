<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private const PER_PAGE = 5;

    public function index()
    {
        // ponytail: "più recente in hero" è un placeholder — la logica di cosa va in evidenza
        // (flag editoriale dedicato, non solo "il più recente") si rivede quando costruiamo il CRUD post.
        $hero = Post::where('published', true)->with('category')->orderByDesc('published_at')->first();

        $news = Post::where('published', true)
            ->with('category')
            ->when($hero, fn ($q) => $q->whereKeyNot($hero->id))
            ->orderByDesc('published_at')
            ->take(self::PER_PAGE)
            ->get();

        return view('home', [
            'hero' => $hero,
            'news' => $news,
            'nextOffset' => self::PER_PAGE,
            'hasMore' => $this->hasMore($hero?->id, self::PER_PAGE),
            'services' => $this->services(),
            'comunicatiStampa' => $this->comunicatiStampa(),
        ]);
    }

    /**
     * Blocco "Comunicati Stampa" in home — 3 più recenti della categoria comunicato-stampa. Prima di
     * questa modifica (2026-08-17) erano 3 righe finte hardcoded con date random, mai collegate a dati
     * reali.
     */
    private function comunicatiStampa()
    {
        $categoryId = Category::where('slug', 'comunicato-stampa')->value('id');

        return Post::where('category_id', $categoryId)
            ->where('published', true)
            ->orderByDesc('published_at')
            ->take(3)
            ->get();
    }

    /**
     * Blocco "Come fare per richiedere..." — figlie pubblicate della pagina-contenitore "servizi" (non
     * pubblicata/fuori menu di per sé). Numero e
     * contenuto sono interamente editoriali: aggiungere/togliere un servizio è una normale modifica di
     * pagina dal pannello, non tocca questo codice.
     */
    private function services()
    {
        $parent = Page::where('slug', 'servizi')->first();

        return $parent
            ? $parent->children()->published()->orderBy('order')->get()
            : collect();
    }

    public function moreNews(Request $request)
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $hero = Post::where('published', true)->orderByDesc('published_at')->first();

        $news = Post::where('published', true)
            ->with('category')
            ->when($hero, fn ($q) => $q->whereKeyNot($hero->id))
            ->orderByDesc('published_at')
            ->skip($offset)
            ->take(self::PER_PAGE)
            ->get();

        return response()->json([
            'html' => view('partials.news-items', ['news' => $news])->render(),
            'hasMore' => $this->hasMore($hero?->id, $offset + self::PER_PAGE),
        ]);
    }

    private function hasMore(?int $heroId, int $offset): bool
    {
        return Post::where('published', true)
            ->when($heroId, fn ($q) => $q->whereKeyNot($heroId))
            ->orderByDesc('published_at')
            ->skip($offset)
            ->limit(1)
            ->exists();
    }
}
