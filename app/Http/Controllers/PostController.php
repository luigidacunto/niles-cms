<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function show(Category $category, Post $post)
    {
        abort_unless(
            $post->category_id === $category->id && ($post->published || auth('admin')->check()),
            404
        );

        $post->load('images', 'attachments');

        return view('posts.show', ['post' => $post]);
    }

    /**
     * Archivio completo, paginazione reale via query string (?page=N — non l'offset/async della home).
     * La stessa URL serve sia
     * una navigazione diretta (pagina intera, per link condivisi/SEO/no-JS) sia il click su un numero di
     * pagina intercettato via Alpine (risposta JSON, aggiorna il DOM + pushState senza reload) — la vista
     * cambia in base a $request->ajax(), non un endpoint separato.
     */
    public function archive(Request $request)
    {
        $posts = Post::where('published', true)
            ->with('category')
            ->orderByDesc('published_at')
            ->paginate(15);

        return $this->archiveResponse($request, 'posts.archive', 'partials.archive-items', $posts);
    }

    /**
     * Stessa idea di archive(), filtrata alla categoria comunicato-stampa — lista sola-testo (vedi
     * partials/comunicati-items.blade.php), non la griglia con immagini dell'archivio notizie.
     */
    public function archiveComunicati(Request $request)
    {
        $categoryId = Category::where('slug', 'comunicato-stampa')->value('id');

        $posts = Post::where('category_id', $categoryId)
            ->where('published', true)
            ->with('category')
            ->orderByDesc('published_at')
            ->paginate(15);

        return $this->archiveResponse($request, 'posts.archive-comunicati', 'partials.comunicati-items', $posts);
    }

    private function archiveResponse(Request $request, string $view, string $partial, $posts)
    {
        if ($request->ajax()) {
            return response()->json(['html' => view($partial, ['posts' => $posts])->render()]);
        }

        return view($view, ['posts' => $posts]);
    }
}
