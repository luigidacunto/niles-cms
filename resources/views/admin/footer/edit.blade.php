@extends('adminlte::page')

@section('title', 'Link del footer')

@section('content_header')
    <h1>Link del footer</h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $errore)
                    <li>{{ $errore }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="text-muted">
        Compaiono nella terza colonna del footer del sito, sotto il titolo «Link utili», nell'ordine indicato (numero più basso = più in alto).
        Per un sito esterno scrivi l'indirizzo completo (<code>https://…</code>); per una pagina di questo sito basta il percorso (<code>/trasparenza</code>).
        Senza nessun link la colonna non compare. Le altre due colonne (contatti e pagine del sito) sono fisse. Aggiunte, modifiche e righe rimosse con la <strong>X</strong> si applicano quando premi <strong>Salva</strong>.
    </p>

    @php
        // Dopo un errore di validazione si ripropone quanto digitato; altrimenti le righe salvate.
        $righe = old('links') ?? $links->map(fn ($l) => ['id' => $l->id, 'label' => $l->label, 'url' => $l->url, 'order' => $l->order])->all();
        $righe = array_values($righe);
    @endphp

    <form method="POST" action="{{ route('admin.footer.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="links_inviati" value="1">

        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width:90px">Ordine</th>
                            <th>Testo</th>
                            <th>Link</th>
                            <th style="width:90px" class="text-center">Rimuovi</th>
                        </tr>
                    </thead>
                    <tbody id="righe-link">
                        @foreach ($righe as $i => $riga)
                            @include('admin.footer.riga', ['i' => $i, 'riga' => $riga])
                        @endforeach
                    </tbody>
                </table>
                <p id="nessun-link" class="text-muted text-center my-4" @if (count($righe)) style="display:none" @endif>
                    Nessun link nel footer. Premi «Aggiungi link» per inserirne uno.
                </p>
            </div>
            <div class="card-footer">
                <button type="button" id="aggiungi-link" class="btn btn-outline-primary"><i class="fas fa-plus"></i> Aggiungi link</button>
                <button type="submit" class="btn btn-primary ml-2"><i class="fas fa-save"></i> Salva</button>
            </div>
        </div>
    </form>

    {{-- Modello della riga nuova: «__I__» diventa l'indice progressivo quando si preme «Aggiungi link». --}}
    <template id="modello-riga">
        @include('admin.footer.riga', ['i' => '__I__', 'riga' => ['id' => null, 'label' => '', 'url' => '', 'order' => ''], 'modello' => true])
    </template>
@stop

@section('js')
    <script>
        (function () {
            var corpo = document.getElementById('righe-link');
            var modello = document.getElementById('modello-riga');
            var vuoto = document.getElementById('nessun-link');
            var prossimo = corpo.querySelectorAll('tr').length; // indice progressivo, mai riusato

            function aggiornaVuoto() {
                vuoto.style.display = corpo.querySelectorAll('tr').length ? 'none' : '';
            }

            document.getElementById('aggiungi-link').addEventListener('click', function () {
                var ordine = 0;
                corpo.querySelectorAll('input[name$="[order]"]').forEach(function (campo) {
                    ordine = Math.max(ordine, parseInt(campo.value, 10) || 0);
                });
                corpo.insertAdjacentHTML('beforeend', modello.innerHTML.replace(/__I__/g, prossimo++));
                var riga = corpo.lastElementChild;
                riga.querySelector('input[name$="[order]"]').value = ordine + 1;
                riga.querySelector('input[name$="[label]"]').focus();
                aggiornaVuoto();
            });

            // La X toglie la riga dalla pagina; la cancellazione vera avviene al salvataggio (una riga che non arriva più al server si elimina).
            corpo.addEventListener('click', function (e) {
                var bottone = e.target.closest('.rimuovi-riga');
                if (bottone) {
                    bottone.closest('tr').remove();
                    aggiornaVuoto();
                }
            });
        })();
    </script>
@stop
