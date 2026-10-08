@props(['posts', 'partial'])

{{-- Wrapper Alpine condiviso da tutti gli archivi paginati (news, comunicati stampa, futuri): click su un
     link dentro [data-pagination] (generato da $posts->links()) viene intercettato, fetchato con
     l'header AJAX, il risultato sostituisce il contenuto e l'URL si aggiorna con pushState — niente
     reload. Il controller deve
     rispondere con {html: ...} quando $request->ajax() è vero, pagina intera altrimenti. --}}
<div x-data="{
    loading: false,
    load(url) {
        if (this.loading) return;
        this.loading = true;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                this.$refs.results.innerHTML = data.html;
                history.pushState(null, '', url);
                this.$refs.results.scrollIntoView({ behavior: 'smooth', block: 'start' });
                this.loading = false;
            });
    }
}" @click="
    const link = $event.target.closest('[data-pagination] a');
    if (link) { $event.preventDefault(); load(link.href); }
">
    <div x-ref="results">
        @include($partial, ['posts' => $posts])
    </div>
</div>
