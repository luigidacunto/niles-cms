<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Document;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\Tag;
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
            'canPages' => $canPages,
            'canDocs' => $canDocs,
            'versione' => config('app.version'),
            'verificaAggiornamenti' => $admin->role === 'admin' && config('app.update_check.enabled'),
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
}
