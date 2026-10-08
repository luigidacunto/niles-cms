{{-- Lista sola-testo (nessuna immagine, i comunicati stampa non ne hanno) — stesso standard hover di
     card-link, in una lista invece di una griglia. Estratto a parte per lo stesso motivo di
     archive-items.blade.php: markup identico tra primo caricamento e risposta AJAX della paginazione. --}}
<div class="flex justify-end mb-6" data-pagination>
    {{ $posts->links() }}
</div>

<div class="divide-y divide-gray-100">
    @foreach ($posts as $post)
        <x-card-link :href="route('posts.show', [$post->category, $post])"
                     :title="$post->title"
                     :excerpt="$post->published_at?->format('d/m/Y')" class="-mx-3" />
    @endforeach
</div>

<div class="flex justify-end mt-10" data-pagination>
    {{ $posts->links() }}
</div>
