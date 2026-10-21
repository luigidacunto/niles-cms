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
        Senza nessun link la colonna non compare. Le altre due colonne (contatti e pagine del sito) sono fisse.
    </p>

    @php
        // Dopo un errore di validazione si ripropone quanto digitato; altrimenti le righe salvate + 3 righe vuote.
        $righe = old('links') ?? $links->map(fn ($l) => ['id' => $l->id, 'label' => $l->label, 'url' => $l->url, 'order' => $l->order])->all();
        if (! old('links')) {
            for ($n = 0; $n < 3; $n++) {
                $righe[] = ['id' => null, 'label' => '', 'url' => '', 'order' => count($righe) + 1];
            }
        }
    @endphp

    <form method="POST" action="{{ route('admin.footer.update') }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:90px">Ordine</th>
                            <th>Testo</th>
                            <th>Link</th>
                            <th style="width:90px" class="text-center">Elimina</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($righe as $i => $riga)
                            <tr>
                                <td>
                                    <input type="hidden" name="links[{{ $i }}][id]" value="{{ $riga['id'] ?? '' }}">
                                    <input type="number" min="0" max="9999" name="links[{{ $i }}][order]" value="{{ $riga['order'] ?? 0 }}" class="form-control form-control-sm">
                                </td>
                                <td><input type="text" maxlength="100" name="links[{{ $i }}][label]" value="{{ $riga['label'] ?? '' }}" class="form-control form-control-sm" placeholder="Es. Bandi di gara (cri.it)"></td>
                                <td><input type="text" maxlength="2000" name="links[{{ $i }}][url]" value="{{ $riga['url'] ?? '' }}" class="form-control form-control-sm" placeholder="https://www.cri.it/…"></td>
                                <td class="text-center">
                                    @if (! empty($riga['id']))
                                        <input type="checkbox" name="links[{{ $i }}][delete]" value="1" @checked(! empty($riga['delete']))>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salva</button>
                <span class="text-muted ml-2">Le righe vuote vengono ignorate: salva e riapri la pagina per aggiungerne altre.</span>
            </div>
        </div>
    </form>
@stop
