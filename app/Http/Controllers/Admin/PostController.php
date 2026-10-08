<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesAttachments;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Document;
use App\Models\Post;
use App\Models\Tag;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    use ManagesAttachments;

    public function index(Request $request)
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('posts', 'read') || $admin->hasPermission('posts', 'write'), 403);

        $posts = Post::with('category')->orderByDesc('published_at');
        $this->scopeToOwnCategories($posts, $admin);

        // Cast a stringa: un parametro vuoto nell'URL (?stato=) arriva come null (ConvertEmptyStringsToNull),
        // non come '' — e input('key', '') NON usa il default se la chiave esiste con valore null.
        $filters = [
            'q' => trim((string) $request->input('q')),
            'stato' => (string) $request->input('stato'),      // '' | pubblicati | bozze
            'categoria' => (string) $request->input('categoria'), // '' | id categoria
        ];

        if ($filters['q'] !== '') {
            $posts->where(fn ($w) => $w->where('title', 'like', "%{$filters['q']}%")->orWhere('slug', 'like', "%{$filters['q']}%"));
        }
        if ($filters['stato'] === 'pubblicati') {
            $posts->where('published', true);
        } elseif ($filters['stato'] === 'bozze') {
            $posts->where('published', false);
        }
        if ($filters['categoria'] !== '') {
            $posts->where('category_id', $filters['categoria']);
        }

        return view('admin.posts.index', [
            'posts' => $posts->paginate(20)->withQueryString(),
            'filters' => $filters,
            'categories' => $this->availableCategories($admin),
        ]);
    }

    public function create(Request $request)
    {
        $admin = $this->authorizeWrite($request);

        return view('admin.posts.form', [
            'post' => new Post(['published' => true]),
            'categories' => $this->availableCategories($admin),
            'availableDocuments' => $this->availableDocuments(),
        ]);
    }

    public function store(Request $request)
    {
        $admin = $this->authorizeWrite($request);
        $data = $this->validated($request);

        $category = $data['category_id'] ? Category::find($data['category_id']) : null;
        abort_unless($admin->canManageCategory($category), 403, 'Non puoi assegnare questa categoria.');

        $post = new Post();
        $this->fillAndSave($post, $data, $request, $admin);

        return redirect()->route('admin.posts.index')->with('status', 'Post creato.');
    }

    public function edit(Request $request, Post $post)
    {
        $admin = $this->authorizeWrite($request, $post);

        return view('admin.posts.form', [
            'post' => $post,
            'categories' => $this->availableCategories($admin),
            'availableDocuments' => $this->availableDocuments(),
        ]);
    }

    /**
     * Vista sola-lettura, per un editor con solo posts:read (write ci arriva comunque, ma passa dritto
     * da edit() nella index — questa route esiste apposta per chi non può scrivere).
     */
    public function show(Request $request, Post $post)
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('posts', 'read') || $admin->hasPermission('posts', 'write'), 403);
        abort_unless($admin->canManageCategory($post->category), 403, 'Non gestisci la categoria di questo post.');

        return view('admin.posts.show', ['post' => $post]);
    }

    public function update(Request $request, Post $post)
    {
        $admin = $this->authorizeWrite($request, $post);
        $data = $this->validated($request, $post);

        $category = $data['category_id'] ? Category::find($data['category_id']) : null;
        abort_unless($admin->canManageCategory($category), 403, 'Non puoi assegnare questa categoria.');

        $this->fillAndSave($post, $data, $request, $admin);

        return redirect()->route('admin.posts.index')->with('status', 'Post aggiornato.');
    }

    public function destroy(Request $request, Post $post)
    {
        $this->authorizeWrite($request, $post);

        if ($post->cover_image) {
            Storage::disk('public')->delete($post->cover_image);
        }
        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        $post->attachments()->detach();
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', 'Post eliminato.');
    }

    /**
     * role='admin' o all_categories non applicano alcun filtro; un editor limitato ad alcune categorie vede
     * solo quelle (nulla, se per qualche motivo non ne ha assegnate) e mai i post senza categoria, che
     * riguardano solo gli admin.
     */
    private function scopeToOwnCategories($query, Admin $admin): void
    {
        if ($admin->role === 'admin' || $admin->all_categories) {
            return;
        }

        $query->whereIn('category_id', $admin->categories()->pluck('categories.id'));
    }

    private function availableCategories(Admin $admin)
    {
        if ($admin->role === 'admin' || $admin->all_categories) {
            return Category::orderBy('order')->orderBy('name')->get();
        }

        return $admin->categories()->orderBy('order')->orderBy('name')->get();
    }

    /** Documenti di libreria pubblicati, per il selettore "Allegati" nel form. */
    private function availableDocuments()
    {
        return Document::published()
            ->where(fn ($q) => $q->whereHas('category', fn ($c) => $c->library())->orWhereNull('document_category_id'))
            ->orderBy('title')->get();
    }

    private function authorizeWrite(Request $request, ?Post $post = null): Admin
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('posts', 'write'), 403);

        if ($post) {
            abort_unless($admin->canManageCategory($post->category), 403, 'Non gestisci la categoria di questo post.');
        }

        return $admin;
    }

    private function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:8192'],
            'published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'string'],
            // Galleria foto: nuovi file da caricare + modifiche/eliminazioni a quelle esistenti.
            'gallery_images' => ['nullable', 'array', 'max:60'],
            'gallery_images.*' => ['image', 'max:8192'],
            'images' => ['nullable', 'array'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.order' => ['nullable', 'integer', 'min:0'],
            'images.*.delete' => ['nullable', 'boolean'],
            // Allegati: documenti già in libreria da collegare/scollegare/riordinare.
            'attach_documents' => ['nullable', 'array'],
            'attach_documents.*' => ['integer', 'exists:documents,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.order' => ['nullable', 'integer', 'min:0'],
            'attachments.*.delete' => ['nullable', 'boolean'],
        ]);

        $data['published'] = $request->boolean('published');

        return $data;
    }

    private function fillAndSave(Post $post, array $data, Request $request, Admin $admin): void
    {
        $publishedAt = $data['published_at'] ?? now();

        // Solo un admin può scegliere lo slug a mano; un editor lo vede ma non può cambiarlo (il campo è
        // disabled in HTML, quindi comunque non arriverebbe nella request — questo blocca anche un
        // tentativo di richiesta forgiata).
        $requestedSlug = $admin->role === 'admin' ? ($data['slug'] ?? null) : null;
        $baseSlug = $requestedSlug ? Str::slug($requestedSlug) : Str::slug($data['title']);
        $post->slug = Post::uniqueSlug($baseSlug, $post->id, Carbon::parse($publishedAt));

        $post->category_id = $data['category_id'] ?? null;
        $post->title = $data['title'];
        $post->subtitle = $data['subtitle'] ?? null;
        $post->excerpt = $data['excerpt'] ?? null;
        $post->body = $data['body'] ?? null;
        $post->published = $data['published'];
        $post->published_at = $publishedAt;
        $post->last_edited_at = now();
        $post->author_name = $post->author_name ?? $request->user('admin')->name;

        if ($request->hasFile('cover_image')) {
            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }
            $post->cover_image = ImageUpload::storeResized(
                $request->file('cover_image'), 'post-covers', $data['title'], 1600
            );
        }

        $post->save();

        $tagIds = collect(explode(',', (string) ($data['tags'] ?? '')))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->map(fn ($name) => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id);

        $post->tags()->sync($tagIds);

        $this->syncImages($post, $request);
        $this->syncAttachments($post, $request);
    }

    /**
     * Galleria foto: applica didascalia/ordine/eliminazione alle foto esistenti, poi carica i nuovi file.
     * Le foto ereditano il perimetro di categoria del post (nessun controllo in più): ci si arriva solo
     * da store()/update() già autorizzati. Eliminare una foto ne cancella anche il file dal disco.
     */
    private function syncImages(Post $post, Request $request): void
    {
        foreach ($request->input('images', []) as $id => $fields) {
            $image = $post->images()->whereKey($id)->first();
            if (! $image) {
                continue;
            }

            if (filter_var($fields['delete'] ?? false, FILTER_VALIDATE_BOOL)) {
                Storage::disk('public')->delete($image->path);
                $image->delete();

                continue;
            }

            $image->update([
                'caption' => $fields['caption'] ?? null,
                'order' => (int) ($fields['order'] ?? 0),
            ]);
        }

        $order = (int) $post->images()->max('order');
        foreach ($request->file('gallery_images', []) as $file) {
            $post->images()->create([
                'path' => ImageUpload::storeResized($file, 'post-images', $post->title, 1920),
                'order' => ++$order,
            ]);
        }
    }
}
